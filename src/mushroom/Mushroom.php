<?php namespace Mushroom;

use Canonical\Canonical;
use WillWashburn\Curl;

/**
 * Expand links to their final destination.
 */
class Mushroom
{
    /**
     * @var Canonical
     */
    private $canonical;

    /**
     * @var Curl
     */
    private $curl;

    /**
     * Mushroom constructor.
     *
     * @param Curl|null      $curl
     * @param Canonical|null $canonical
     */
    public function __construct(Curl $curl = null, Canonical $canonical = null)
    {
        $this->curl      = is_null($curl) ? new Curl() : $curl;
        $this->canonical = is_null($canonical) ? new Canonical() : $canonical;
    }

    /**
     * Expands to the canonical url, according to the 'rel=canonical' tag
     * at the end of all redirects for a given link.
     *
     * @param       $urls
     * @param array $curlOptions
     *
     * @return string|array
     */
    public function canonical($urls, array $curlOptions = [])
    {
        return $this->expand($urls, $curlOptions, $followHttpRefresh = true, $findCanonical = true);
    }

    /**
     * @param array|string $urls                The url or urls  to unfurl
     *
     * @param array        $curlOptions         Custom curl options that can be passed in
     *
     * @param bool         $followHttpRefresh   If the response is a 200 response but
     *                                          includes the meta tag http-refresh, follow
     *                                          that link to further expand the url
     *
     * @param bool         $findCanonical       If the response includes a meta tag for the
     *                                          canonical url, return that
     *
     * @return string|array
     */
    public function expand($urls, array $curlOptions = [], $followHttpRefresh = true, $findCanonical = false)
    {
        $single = !is_array($urls);

        $response =  $this->batchFollow($single ? [$urls] : $urls, $curlOptions, $followHttpRefresh, $findCanonical);

        if ($single) {
            return $response[0];
        }

        return $response;
    }

    /**
     * @param array $urls              The urls to unfurl
     * @param array $curlOptions       Custom curl options that can be passed in
     * @param bool  $followHttpRefresh If the response is a 200 response but
     *                                 includes the meta tag http-refresh, follow
     *                                 that link to further expand the url
     * @param bool  $findCanonical     If the response includes a meta tag for the
     *                                 canonical url, return that
     *
     * @return array
     */
    private function batchFollow($urls, $curlOptions, $followHttpRefresh, $findCanonical)
    {
        if (!$urls) {
            return [];
        }

        $mh = $this->curl->curl_multi_init();

        $x = 0;
        foreach ($urls as $key => $url) {
            $$x = $this->getHandle($url, $curlOptions);

            $this->curl->curl_multi_add_handle($mh, $$x);

            $x++;
        }

        $running = null;
        do {
            $this->curl->curl_multi_exec($mh, $running);
        } while ($running);

        $y         = 0;
        $locations = [];

        foreach ($urls as $key => $url) {
            $locations[$key] = $this->getUrlFromHandle($$y, $followHttpRefresh, $findCanonical);

            $y++;
        }

        $this->curl->curl_multi_close($mh);

        if ($followHttpRefresh) {
            $retries = [];

            foreach ($locations as $key => $url) {
                if (!$url['refresh']) {
                    $locations[$key] = $url['url'];
                } else {
                    $retries[$key] = $url['url'];
                }
            }

            if ($retries) {
                $retried = $this->batchFollow($retries, $curlOptions, false, $findCanonical);
                foreach ($retries as $key => $url) {
                    $locations[$key] = $retried[$key]['url'];
                }
            }
        }

        return $locations;
    }

    /**
     * @param       $url
     * @param array $curlOptions
     *
     * @return resource
     */
    private function getHandle($url, array $curlOptions = [])
    {
        $ch = $this->curl->curl_init($url);

        // Sane default options for the handle
        $curl_opts = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 100,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false, // suppress certain SSL errors
            CURLOPT_SSL_VERIFYPEER => false,

            // Some hosts don't respond well if you're a bot so we lie
            // @codingStandardsIgnoreLine
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:45.0) Gecko/20100101 Firefox/45.0',
            CURLOPT_AUTOREFERER    => true,
        ];

        // If we passed in other handle options, add them here
        if ($curlOptions) {
            $curl_opts = $curlOptions + $curl_opts;
        }

        $this->curl->curl_setopt_array($ch, $curl_opts);

        return $ch;
    }

    /**
     * @param resource $ch
     * @param bool     $followHttpRefresh
     * @param bool     $findCanonical
     *
     * @return array
     */
    private function getUrlFromHandle($ch, $followHttpRefresh, $findCanonical)
    {
        if ($followHttpRefresh) {
            $httpStatusCode = $this->curl->curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if ($httpStatusCode === 200) {
                /*
                 * Some HTML pages use a meta refresh tag as redirection.
                 *
                 * i.e.
                 * <meta http-equiv="refresh" content="5; url=http://example.com/">
                 *
                 * Would redirect to http://example.com/ after 5 seconds.
                 */
                $url = $this->canonical->url(
                    $this->curl->curl_multi_getcontent($ch),
                    [
                        'http-refresh' => ['meta[http-equiv="refresh"]', 'content'],
                    ]
                );

                if ($url) {
                    return [
                        'refresh' => true,
                        'url'     => $url->beforeCleaning(),
                    ];
                }
            }
        }

        if ($findCanonical) {
            // Canonical will read tags to find rel=canonical and og tags
            $url = $this->canonical->url($this->curl->curl_multi_getcontent($ch));

            if ($url) {
                $url = $url->withoutUtmParamsAndHashAnchors();

                // Canonical urls should have a scheme and a host;
                // if they do not, we'll use the effective url from the curl
                // request to determine what it should be
                $scheme = parse_url($url, PHP_URL_SCHEME);
                $host   = parse_url($url, PHP_URL_HOST);

                // If there is a scheme, we can return the url as is
                if ($scheme && $host) {
                    return [
                        'refresh' => false,
                        'url'     => $url,
                    ];
                }

                $effective_url = $this->curl->curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

                $parsed = parse_url($url);

                if (!$scheme) {
                    $parsed['scheme'] = parse_url($effective_url, PHP_URL_SCHEME);
                }

                if (!$host) {
                    $parsed['host'] = parse_url($effective_url, PHP_URL_HOST);
                }

                // Create a string of the url again
                return [
                    'refresh' => false,
                    'url'     => $this->unparseUrl($parsed),
                ];
            }
        }

        return [
            'refresh' => false,
            'url'     => $this->curl->curl_getinfo($ch, CURLINFO_EFFECTIVE_URL),
        ];
    }

    /**
     * Puts a parsed url back together again.
     *
     * @param $parsed_url
     *
     * @return string
     */
    private function unparseUrl($parsed_url)
    {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';

        return "$scheme$user$pass$host$port$path$query$fragment";
    }
}
