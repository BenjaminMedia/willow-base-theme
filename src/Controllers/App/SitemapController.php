<?php

namespace Bonnier\Willow\Base\Controllers\App;

use Bonnier\Willow\Base\Adapters\Wp\Root\SitemapAdapter;
use Bonnier\Willow\Base\Adapters\Wp\Root\SitemapCollectionAdapter;
use Bonnier\Willow\Base\Models\Base\Root\SitemapCollection;
use Bonnier\Willow\Base\Models\Base\Root\SitemapItem;
use Bonnier\Willow\Base\Transformers\Api\Root\SitemapTransformer;
use Bonnier\Willow\MuPlugins\Helpers\LanguageProvider;
use Bonnier\WP\Sitemap\WpBonnierSitemap;
use DateTime;
use Illuminate\Support\Collection;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class SitemapController extends WP_REST_Controller
{
    const PER_PAGE = 1000;

    private $sitemapRepository;

    public function __construct()
    {
        $this->sitemapRepository = WpBonnierSitemap::instance()->getSitemapRepository();
    }

    public function register_routes()
    {
        register_rest_route(
            'app', '/sitemaps', [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'sitemaps'],
            ]
        );
        register_rest_route(
            'app', '/sitemaps/(?P<type>[a-zA-Z0-9-_]+)', [
                'methods' => WP_REST_Server::READABLE,
                'callback' => [$this, 'content']
            ]
        );
    }

    public function sitemaps(WP_REST_Request $request)
    {
        $perPage = self::PER_PAGE;
        if ($queryPerPage = $request->get_param('per_page')) {
            $perPage = intval($queryPerPage);
        }
        $overview = $this->sitemapRepository->getOverview(LanguageProvider::getCurrentLanguage());

        if (empty($overview)) {
            return $this->notFound();
        }

        $response = collect($overview)->map(function ($sitemapType) use ($perPage) {
            $pages = intval(ceil($sitemapType['amount'] / $perPage));
            return [
                'type' => $sitemapType['wp_type'],
                'lastmod' => (new DateTime($sitemapType['modified_at']))->format('c'),
                'pages' => $pages,
                'urls' => Collection::times($pages, function($page) use ($sitemapType) {
                    return sprintf('%s-%s', $sitemapType['wp_type'], $page);
                })->toArray()
            ];
        })->toArray();

        return new WP_REST_Response(['data' => $response]);
    }

    public function content(WP_REST_Request $request)
    {
        $perPage = self::PER_PAGE;
        if ($queryPerPage = $request->get_param('per_page')) {
            $perPage = intval($queryPerPage);
        }
        $type = $request->get_param('type');
        $page = $request->get_param('page') ?? 1;

        $sitemaps = $this->sitemapRepository->getByType($type, $page, $perPage, LanguageProvider::getCurrentLanguage());

        if (!$sitemaps || $sitemaps->isEmpty()) {
            return $this->notFound();
        }

        return $this->sitemapResponse(new SitemapCollection(new SitemapCollectionAdapter($type, $sitemaps->map(
            function ($content) {
               $url = new SitemapAdapter($content);

               $headers = @get_headers($url->getUrl());
                if($headers && strpos( $headers[0], '200')) {
                    return new SitemapItem(new SitemapAdapter($content));
                }
            }
        ))));
    }

    public function sitemapResponse(SitemapCollection $sitemapCollection)
    {
        return new WP_REST_Response(
            (new Manager())
                ->createData(new Item($sitemapCollection, new SitemapTransformer()))
                ->toArray()
        );
    }

    public function notFound()
    {
        return new WP_REST_Response(
            [
                'code' => 'no_sitemap_found',
                'message' => 'No sitemap was found for this type',
                'data' => [
                    'status' => 404
                ]
            ],
            404
        );
    }
}
