<?php

namespace Bonnier\Willow\Base\Repositories\WhiteAlbum;

use Bonnier\WP\Redirect\Helpers\LocaleHelper;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\UriInterface;

/**
 * Class RedirectRepository
 *
 * @package \Bonnier\Willow\Base\Repositories\WhiteAlbum
 */
class RedirectRepository
{
    const WA_ROUTE_RESOLVES_TABLE = 'whitealbum_route_resolves';
    const WA_ROUTE_RESOLVES_TABLE_CREATED = 'whitealbum_route_resolves_created';
    const WA_ROUTE_RESOLVES_TABLE_VERSION = '1';

    protected $domain;
    protected $client;
    protected $locale;

    /**
     * RedirectRepository constructor.
     *
     * @param string $domain
     * @param string $locale
     */
    public function __construct(string $domain, string $locale)
    {
        $this->domain = $domain;
        $this->locale = $locale;
        $this->client = new Client([
            'base_uri' => sprintf('http://old.%s', $this->domain),
        ]);
        $this->createRouteResolvesTable();
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param Client $client
     *
     * @return RedirectRepository
     */
    public function setClient(Client $client): RedirectRepository
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param $path
     *
     * @return mixed
     */
    public function resolve($path)
    {
        $redirect = $this->findRelsovedRedirectInDb($path) ?: $this->recursiveRedirectResolve($path);
        if ($redirect && $redirect->to !== $path && in_array($redirect->code, [301, 302, 200])) {
            return $redirect;
        }
        return null;
    }

    private function waTestUrl($to)
    {
        $domains = LocaleHelper::getLocalizedUrls();

        $url = null;
        $domain = $domains[$this->locale] ?? null;
        if (substr($to->getTo(), 0, 1) !== '/') {
            $url = $to->getTo();
        } else if ($domain) {
            $url = sprintf('%s/%s', rtrim($domain, '/'), ltrim($to->getTo(), '/'));
        }

        return $url;
    }

    private function recursiveRedirectResolve($url)
    {
        /** @var null|UriInterface $destination */
        $destination = null;
        try {
            $response = $this->client->head($url, [
                'on_stats' => function (TransferStats $stats) use (&$destination) {
                    $destination = $stats->getEffectiveUri();
                }
            ]);
        } catch (RequestException $exception) {
            $response = $exception->getResponse();
        }
        $to = optional($destination)->getPath() ?: '/';

        try {
            $secondUrl= $this->waTestUrl($to);
            $client = new Client();
            $client->head($secondUrl);
        } catch (RequestException $exception) {
            // error 600 means not found on from url and to url
            $this->storeResolvedRedirect(
                $url,
                $to,
                600
            );

            return null;
        }

        if (
            $destination &&
            ($destination->getHost() !== $this->domain && $destination->getHost() !== sprintf('old.%s', $this->domain))
        ) {
            // Only keep destination host, if it differs from the current domain
            // with and without the 'old.' prefix
            $to = sprintf(
                '%s://%s%s',
                $destination->getScheme(),
                $destination->getHost(),
                $destination->getPath()
            );
        }

        if ($destination->getQuery()) {
            $to = sprintf('%s?%s', $to, $destination->getQuery());
        }

        $this->storeResolvedRedirect(
            $url,
            $to,
            optional($response)->getStatusCode() ?? 301
        );
        return $this->findRelsovedRedirectInDb($url);
    }

    public function createRouteResolvesTable()
    {
        if (get_option(static::WA_ROUTE_RESOLVES_TABLE_CREATED) === static::WA_ROUTE_RESOLVES_TABLE_VERSION) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . static::WA_ROUTE_RESOLVES_TABLE;
        $charset = $wpdb->get_charset_collate();

        $sql = "SET sql_notes = 1;
            CREATE TABLE `$table` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `from` text CHARACTER SET utf8 NOT NULL,
              `from_hash` char(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `to` text CHARACTER SET utf8 NOT NULL,
              `to_hash` char(32) COLLATE utf8mb4_unicode_520_ci NOT NULL,
              `locale` varchar(2) CHARACTER SET utf8 NOT NULL,
              `code` int(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `from_hash` (`from_hash`,`to_hash`,`locale`),
              KEY `from_hash_2` (`from_hash`,`to_hash`,`locale`),
              KEY `from_hash_3` (`from_hash`)
            ) $charset;
            SET sql_notes = 1;
            ";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        update_option(static::WA_ROUTE_RESOLVES_TABLE_CREATED, static::WA_ROUTE_RESOLVES_TABLE_VERSION, true);
    }

    /**
     * @param $path
     *
     * @return array|null|object
     */
    private function findRelsovedRedirectInDb($path)
    {
        global $wpdb;
        $table = $wpdb->prefix . static::WA_ROUTE_RESOLVES_TABLE;
        $fromUrl = parse_url($path, PHP_URL_PATH);
        try {
            return $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `$table` WHERE `from_hash` = MD5(%s) AND `locale` = %s",
                    [
                        $fromUrl,
                        $this->locale
                    ]
                )
            );
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @param $fromUrl
     * @param $toUrl
     * @param $code
     *
     * @return mixed
     */
    private function storeResolvedRedirect($fromUrl, $toUrl, $code)
    {
        global $wpdb;
        $fromUrl = parse_url($fromUrl, PHP_URL_PATH);
        $table = $wpdb->prefix . static::WA_ROUTE_RESOLVES_TABLE;
        try {
            return $wpdb->query(
                $wpdb->prepare(
                    "INSERT IGNORE INTO `$table` 
                    (`from`, `from_hash`, `to`, `to_hash`, `locale`, `code`) 
                    VALUES (%s, MD5(%s), %s, MD5(%s), %s, %d)",
                    [
                        $fromUrl,
                        $fromUrl,
                        $toUrl,
                        $toUrl,
                        $this->locale,
                        $code
                    ]
                )
            );
        } catch (\Exception $e) {
            return null;
        }
    }
}
