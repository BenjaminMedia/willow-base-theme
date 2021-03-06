<?php

namespace Bonnier\Willow\Base\Commands;

use Bonnier\Willow\Base\Models\WpComposite;
use Bonnier\Willow\MuPlugins\Helpers\LanguageProvider;
use Bonnier\Willow\Base\Repositories\CxenseSearchRepository;
use Bonnier\WP\Cxense\Services\CxenseApi;
use Bonnier\WP\Cxense\WpCxense;
use Bonnier\WP\Cxense\Exceptions\HttpException;
use WP_CLI;

class CxenseSync extends \WP_CLI_Command
{
    private const CMD_NAMESPACE = 'cxense';
    private const ALLOWED_COMMANDS = ['cleanup', 'sync', 'info', 'wp-urls', 'cxense-urls', 'urls'];

    protected $searchRepository;

    protected $inCxense;
    protected $wait;
    protected $sleepSearch;
    protected $sleepRequest;
    protected $syncStartId;
    protected $syncSkipIds;

    protected $onlyLocale;
    protected $skipLocale;

    protected $deletedDidNotExist;
    protected $deletedDifferentUrl;

    protected $pushCount;
    protected $errorUrls;
    protected $skipCount;

    public static function register()
    {
        WP_CLI::add_command(static::CMD_NAMESPACE, __CLASS__);
    }

    public function run($params)
    {
        global $polylang, $locale;

        $this->inCxense = collect();
        $this->wait = false;
        $this->sleepSearch = 200000; // 0,2 second
        $this->sleepRequest = 1000000; // 1 second
        $this->syncStartId = null;
        $this->syncSkipIds = collect();

        // CleanUp stats
        $this->deletedDidNotExist = 0;
        $this->deletedDifferentUrl = 0;

        // Sync stats
        $this->pushCount = 0;
        $this->errorUrls = collect();
        $this->skipCount = 0;

        if (sizeof($params) == 0) {
            WP_CLI::line('Run one of the follwoing:');
            WP_CLI::line('');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run cleanup');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run sync');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run sync cleanup');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run cleanup sync');
            WP_CLI::line('');
            WP_CLI::line('\'cleanup\' will go through all content in Cxense.');
            WP_CLI::line('If an article has different urls in Cxense and WP, the article will be deleted in Cxense,');
            WP_CLI::line('and the WP article will (if it\'s published) be pushed to Cxense with correct url.');
            WP_CLI::line('');
            WP_CLI::line('\'sync\' will go through all published composites in WP and push them to Cxense.');
            WP_CLI::line('This will create the data in Cxense or update it if the url was already in Cxense.');
            WP_CLI::line('');
            WP_CLI::line('It is also possible to add only:locale or skip:locale, e.g.');
            WP_CLI::line('');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run sync only:da_DK');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run cleanup skip:nb_NO');
            WP_CLI::line('');
            WP_CLI::line('Add \'sync-start-id:5000\' to get sync to skip all composites with an id lower than 5000');
            WP_CLI::line('');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run sync sync-start-id:5000 only:da_DK');
            WP_CLI::line('');
            WP_CLI::line('Add \'sync-skip-ids:100,200\' to get sync to skip composites with ids 100 and 200');
            WP_CLI::line('');
            WP_CLI::line('Add \'wait\' to be prompted to press enter before each update/delete call to Cxense.');
            WP_CLI::line('When prompted you can write \'go\' to disable the prompting without restarting. E.g.');
            WP_CLI::line('');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run cleanup wait');
            WP_CLI::line('');
            WP_CLI::line('Add \'sleep-search:2000000\' to sleep 2 seconds when getting a page from Cxense.');
            WP_CLI::line('Add \'sleep-request:2000000\' to sleep 2 seconds when pushing to / deleting from Cxense.');
            WP_CLI::line('');
            WP_CLI::line('Use \'info\' to get info about a locale. E.g.');
            WP_CLI::line('');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run info only:da_DK');
            WP_CLI::line('');
            WP_CLI::line('Use \'cxense-urls\' or \'wp-urls\' to get all cxense or wordpress urls. E.g.');
            WP_CLI::line('');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run cxense-urls only:da_DK');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run wp-urls only:da_DK');
            WP_CLI::line('');
            WP_CLI::line('Use \'urls\' get the difference and intersection of the urls in cxense and wordpress.');
            WP_CLI::line('');
            WP_CLI::line('wp ' . self::CMD_NAMESPACE . ' run urls only:da_DK');
            WP_CLI::line('');
            WP_CLI::line('Remember to run sync before cleanup if you are moving a site to a new address,');
            WP_CLI::line('e.g. beta.illvid.dk to illvid.dk');
            WP_CLI::line('');
            WP_CLI::error('Argument is missing');
        }

        // Parse 'skip' and 'only' arguments
        $params = $this->parseArguments($params);

        // Check for illegal arguments
        if (sizeof(array_diff($params, self::ALLOWED_COMMANDS)) > 0) {
            WP_CLI::error('You have entered illegal arguments. Run the following command for info: wp ' .
                self::CMD_NAMESPACE . ' run');
        }

        WP_CLI::line('Sleep search:  ' . $this->sleepSearch/1000000);
        WP_CLI::line('Sleep request: ' . $this->sleepRequest/1000000);
        sleep(1);

        collect($polylang->model->get_languages_list())->each(function (\PLL_Language $language) use (&$polylang,
            &$locale, $params) {

            $locale = $language->locale;

            if ($this->onlyLocale && $locale !== $this->onlyLocale) {
                return;
            }

            if ($this->skipLocale && $locale === $this->skipLocale) {
                return;
            }

            $polylang->curlang = $language;
            $this->searchRepository = new CxenseSearchRepository();

            WP_CLI::line('$language->locale: ' . $language->locale);
            WP_CLI::line('get_locale(): ' . get_locale());

            $this->runCommands($params);
        });

        WP_CLI::line('-- DONE --');
    }

    private function parseArguments($params)
    {
        $returnParams = [];
        foreach ($params as $param) {
            if (substr($param, 0, 5) === 'only:') {
                $this->onlyLocale = substr($param, 5, strlen($param) - 5);
                continue;
            }
            if (substr($param, 0, 5) === 'skip:') {
                $this->skipLocale = substr($param, 5, strlen($param) - 5);
                continue;
            }
            if ($param === 'wait') {
                $this->wait = true;
                continue;
            }
            if (substr($param, 0, 13) === 'sleep-search:') {
                $this->sleepSearch = (int)substr($param, 13, strlen($param) - 13);
                continue;
            }
            if (substr($param, 0, 14) === 'sleep-request:') {
                $this->sleepRequest = (int)substr($param, 14, strlen($param) - 14);
                continue;
            }
            if (substr($param, 0, 14) === 'sync-start-id:') {
                $this->syncStartId = (int)substr($param, 14, strlen($param) - 14);
                continue;
            }
            if (substr($param, 0, 14) === 'sync-skip-ids:') {
                $this->syncSkipIds = collect(explode(',', substr($param, 14, strlen($param) - 14)))
                ->map(function ($id) {
                    return intval($id);
                });
                continue;
            }
            $returnParams[] = $param;
        }
        return $returnParams;
    }

    private function runCommands($commands)
    {
        foreach ($commands as $command) {
            if ($command === 'cleanup') {
                WP_CLI::line('-- cleanup --');
                $this->cleanupCxense();
                continue;
            }
            if ($command === 'sync') {
                WP_CLI::line('-- sync --');
                $this->syncCompositesIntoCxense();
                continue;
            }
            if ($command === 'info') {
                $this->info();
                continue;
            }
            if ($command === 'cxense-urls') {
                $this->outputCxenseUrls();
                continue;
            }
            if ($command === 'wp-urls') {
                $this->outputwpUrls();
                continue;
            }
            if ($command === 'urls') {
                $this->outputUrls();
                continue;
            }
        }
    }

    private function outputUrls()
    {
        $cxenseUrls = $this->cxenseUrls();
        $wpUrls = $this->wpUrls();

        $onlyCxenseUrls = $cxenseUrls->diff($wpUrls);
        WP_CLI::line('Urls only in Cxense - ' . $onlyCxenseUrls->count());
        $onlyCxenseUrls->each(function ($url) {
            WP_CLI::line($url);
        });
        WP_CLI::line();

        $onlyWpUrls = $wpUrls->diff($cxenseUrls);
        WP_CLI::line('Urls only in Wordpress - ' . $onlyWpUrls->count());
        $onlyWpUrls->each(function ($url) {
            WP_CLI::line($url);
        });
        WP_CLI::line();

        $intersect = $cxenseUrls->intersect($wpUrls);
        WP_CLI::line('Urls in both Cxense and Wordpress - ' . $intersect->count());
        $intersect->each(function ($url) {
            WP_CLI::line($url);
        });
        WP_CLI::line();
    }

    private function cxenseObjects()
    {
        $objects = collect();

        $this->cxense_map_all(function ($obj) use (&$objects) {
            $objects->push($obj);
        });

        return $objects;
    }

    private function cxenseUrls()
    {
        $urls = collect();

        $this->cxense_map_all(function ($obj) use (&$urls) {
            $urls->push($obj->getField('url'));
        });

        return $urls;
    }

    private function outputCxenseUrls()
    {
        $cxenseUrls = $this->cxenseUrls();

        WP_CLI::line('Cxense urls:');
        $cxenseUrls->sort()->unique()->each(function ($url) {
            WP_CLI::line($url);
        });

        WP_CLI::line('Cxense urls count:        ' . $cxenseUrls->count());
        WP_CLI::line('Cxense urls unique count: ' . $cxenseUrls->unique()->count());
        WP_CLI::line();
    }

    private function wpUrls()
    {
        $urls = collect();

        WpComposite::mapAll(function (\WP_Post $post) use (&$urls) {
            $urls->push(get_permalink($post->ID));
        });

        return $urls;
    }

    private function outputWpUrls()
    {
        $urls = $this->wpUrls();

        WP_CLI::line('Wordpress urls:');
        $urls->sort()->unique()->each(function ($url) {
            WP_CLI::line($url);
        });

        WP_CLI::line('WP urls count:        ' . $urls->count());
        WP_CLI::line('WP urls unique count: ' . $urls->unique()->count());
        WP_CLI::line();
    }

    private function info()
    {
        $searchResults = $this->doCxenseSearch('*', 1, 1);
        WP_CLI::line('Total in Cxense:    ' . $searchResults->totalCount);

        $args = [
            'post_type' => WpComposite::POST_TYPE,
            'posts_per_page' => 10,
            'paged' => 1,
            'order' => 'ASC',
            'orderby' => 'ID'
        ];
        $posts = new \WP_Query($args);
        WP_CLI::line('Total in Wordpress: ' . $posts->found_posts);
        WP_CLI::line();

        usleep($this->sleepSearch);
    }

    private function cleanupStat()
    {
        WP_CLI::line('Deleted - No such post:  ' . $this->deletedDidNotExist);
        WP_CLI::line('Deleted - Different URL: ' . $this->deletedDifferentUrl);
        WP_CLI::line('In Cxense: ' . $this->inCxense->count());
    }

    private function cleanupCxense()
    {
        $this->cxenseObjects()->each(function($obj){
            print ". ";
            $postId = $obj->getField('recs-articleid');
            $cxenseUrl = $obj->getField('url');

            // Skip cleaning for non-composites
            if (!$postId) {
                return;
            }

            if (!$post = get_post($postId)) {
                // Delete in Cxense
                WP_CLI::line();
                WP_CLI::line('Delete in Cxense - No such post');
                WP_CLI::line('postId: ' . $postId);
                WP_CLI::line('Cxense-url: ' . $cxenseUrl);
                WP_CLI::line('Cxense-id: ' . $obj->id);
                $this->cxenseDeleteUrl($cxenseUrl);
                $this->deletedDidNotExist++;

                $this->cleanupStat();
                return;
            }

            $permalink = get_permalink($postId);
            if ($permalink !== $cxenseUrl) {
                // Delete in Cxense
                WP_CLI::line();
                WP_CLI::line('Delete in Cxense - Different URL');
                WP_CLI::line('postId: ' . $postId);
                WP_CLI::line('Cxense-url: ' . $cxenseUrl);
                WP_CLI::line('WP-url:     ' . $permalink);

                $this->cxenseDeleteUrl($cxenseUrl);
                $this->deletedDifferentUrl++;

                // Push the correct url to Cxense if the post is published
                if ($post->status === 'publish') {
                    WP_CLI::line('Pushing permalink to Cxense');
                    $this->cxensePush($permalink);
                }

                $this->cleanupStat();
            }

            $this->inCxense->push($postId);
        });
    }

    private function syncCompositesIntoCxense()
    {
        // Iterate over composite urls and add those to cxense that are not already there
        WpComposite::mapAll(function (\WP_Post $post) {
            if (LanguageProvider::getPostLanguage($post->ID) !== LanguageProvider::getCurrentLanguage()) {
                return;
            }

            WP_CLI::line();
            WP_CLI::line('$post->ID: ' . $post->ID);
            WP_CLI::line('locale: ' . get_locale());

            $url = get_permalink($post->ID);
            WP_CLI::line('url: ' . $url);

            // Check if the id has been handled by cleanupCxense() earlier
            if ($this->inCxense->contains($post->ID)) {
                $this->skipCount++;
                return;
            }

            // Check if this id should be skipped (the user has provided sync-start-id argument)
            if ($this->syncStartId && $this->syncStartId > $post->ID) {
                WP_CLI::line('Skipping all ids lower than: ' . $this->syncStartId);
                return;
            }

            // Check if this id should be skipped (the user has provided sync-skip-ids argument)
            if ($this->syncSkipIds->contains($post->ID)) {
                WP_CLI::line('Skipping id: ' . $post->ID);
                $this->skipCount++;
                return;
            }

            // Insert into Cxense
            $this->pushCount++;
            if (!$this->cxensePush($url)) {
                WP_CLI::error('Error pushing to Cxense', false);
                $this->errorUrls->push($url);
            } else {
                $this->inCxense->push($post->ID);
            }

            WP_CLI::line('skipCount: ' . $this->skipCount);
            WP_CLI::line('pushCount: ' . $this->pushCount);
            WP_CLI::line('in Cxense: ' . $this->inCxense->count());
            WP_CLI::line('errors: ' . $this->errorUrls->count());
            WP_CLI::line();
        });

        if ($this->errorUrls->count() > 0) {
            WP_CLI::line('ErrorUrls:');
            var_dump($this->errorUrls);
        }
    }

    private function doCxenseSearch($query, $page = 1, $perPage = 100)
    {
        WP_CLI::line();
        WP_CLI::line('Page: ' . $page);

        return $this->searchRepository->getSearchResults(
            $query,
            $page,
            $perPage,
            [],
            [
                'type' => 'time',
                'order' => 'ascending'
            ]
        );
    }

    private function cxense_map_all($callback)
    {
        $query = '*';
        $page = 1;
        $perPage = 100;

        $searchResults = $this->doCxenseSearch($query, $page, $perPage);

        while (sizeof($searchResults->matches)) {
            collect($searchResults->matches)->each(function ($obj) use ($callback) {
                $callback($obj);
            });

            usleep($this->sleepSearch);

            $page++;
            $searchResults = $this->doCxenseSearch($query, $page, $perPage);
        }
    }

    private function wait()
    {
        if ($this->wait) {
            $handle = fopen("php://stdin", "r");
            $line = rtrim(fgets($handle));
            if ($line === 'go') {
                $this->wait = false;
            }
        }
    }

    private function cxenseDeleteUrl($contentUrl)
    {
        usleep($this->sleepRequest);
        $this->wait();

        return self::cxenseRequest(CxenseApi::CXENSE_PROFILE_DELETE, [
            'siteId' => WpCxense::instance()->settings->getSiteId(),
            'url' => $contentUrl
        ]);
    }

    private function cxensePush($contentUrl)
    {
        usleep($this->sleepRequest);
        $this->wait();

        return self::cxenseRequest(CxenseApi::CXENSE_PROFILE_PUSH, [
            'url' => $contentUrl
        ]);
    }

    public static function cxenseRequest($apiPath, $args)
    {
        try {
            return CxenseApi::request($apiPath, $args);
        } catch (Exception $e) {
            if ($e instanceof HttpException) {
                error_log('WP cXense: Failed calling cXense api: ' . $apiPath . ' response code: ' .
                    $e->getCode() . ' error: ' . $e->getMessage());
            }

            if ($e->getCode() == CxenseApi::EXCEPTION_USER_NOT_DEFINED) {
                error_log('PHP Warning: To use CXense push you must define constants CXENSE_USER_NAME and CXENSE_API_KEY');
            } elseif ($e->getCode() == CxenseApi::EXCEPTION_UNAUTHORIZED) {
                error_log('PHP Warning: Could not authorize with defined CXENSE_USER_NAME and CXENSE_API_KEY');
            }
        }
    }
}
