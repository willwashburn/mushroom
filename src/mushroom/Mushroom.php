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
        return $this->expand($urls, $curlOptions, $canonical = true);
    }

    /**
     * @param       $urls
     * @param array $curlOptions
     * @param bool  $canonical
     *
     * @return string|array
     */
    public function expand($urls, array $curlOptions = [], $canonical = false)
    {
        if (!is_array($urls)) {
            return $this->followToLocation($urls, $curlOptions, $canonical);
        }

        return $this->batchFollow($urls, $curlOptions, $canonical);
    }

    /**
     * @param       $urls
     * @param       $curlOptions
     * @param       $findCanonical
     *
     * @return array
     */
    private function batchFollow($urls, $curlOptions, $findCanonical)
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

        ///add each result to an array
        $y         = 0;
        $locations = [];

        foreach ($urls as $key => $url) {
            $locations[$key] = $this->getUrlFromHandle($$y, $findCanonical);

            $y++;
        }

        $this->curl->curl_multi_close($mh);

        return $locations;
    }

    /**
     * @param string $url
     * @param array  $curlOptions
     * @param bool   $findCanonical
     *
     * @return string
     */
    private function followToLocation($url, array $curlOptions, $findCanonical)
    {
        $ch = $this->getHandle($url, $curlOptions);
        $this->curl->curl_exec($ch);
        $url = $this->getUrlFromHandle($ch, $findCanonical);
        $this->curl->curl_close($ch);

        return $url;
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
     * @param bool     $findCanonical
     *
     * @return string
     */
    private function getUrlFromHandle($ch, $findCanonical)
    {
        if ($findCanonical) {
            // Canonical will read tags to find rel=canonical and og tags
            $url = $this->canonical->url($this->curl->curl_multi_getcontent($ch));

            if ($url) {
                // Canonical urls should have a scheme and a host;
                // if they do not, we'll use the effective url from the curl
                // request to determine what it should be
                $scheme = parse_url($url, PHP_URL_SCHEME);
                $host   = parse_url($url, PHP_URL_HOST);

                // If there is a scheme, we can return the url as is
                if ($scheme && $host) {
                    return $url;
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
                return $this->unparseUrl($parsed);
            }
        }

        return $this->curl->curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
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
