<?php namespace Mushroom;

use WillWashburn\Canonical;
use WillWashburn\Curl;

/**
 * Expand links to their final destination
 *
 * @package Mushroom
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
        $this->curl      = is_null($curl) ? new Curl : $curl;
        $this->canonical = is_null($canonical) ? new Canonical() : $canonical;
    }

    /**
     * Expands to the canonical url, according to the 'rel=canonical' tag
     * at the end of all redirects for a given link
     *
     * @param       $urls
     * @param array $options
     *
     * @return string|array
     */
    public function canonical($urls, array $options = [])
    {
        $options['canonical'] = true;

        return $this->expand($urls, $options);

    }

    /**
     * @param       $urls
     * @param array $options an array of options
     *
     * @return string|array
     */
    public function expand($urls, array $options = [])
    {
        if ( !is_array($urls) ) {
            return $this->followToLocation($urls, $options);
        }

        return $this->batchFollow($urls, $options);
    }

    /**
     * @param       $urls
     * @param array $options
     *
     * @return array
     */
    private function batchFollow($urls, array $options)
    {
        if (!$urls) {
            return [];
        }

        $mh = $this->curl->curl_multi_init();

        $x = 0;
        foreach ( $urls as $key => $url ) {

            $$x = $this->getHandle($url, $options);

            $this->curl->curl_multi_add_handle($mh, $$x);

            $x++;
        }

        $running = null;
        do {
            $this->curl->curl_multi_exec($mh, $running);
        } while ( $running );

        ///add each result to an array
        $y         = 0;
        $locations = array();

        foreach ( $urls as $key => $url ) {

            $locations[$key] = $this->getUrlFromHandle($$y, $options);

            $y++;
        }


        $this->curl->curl_multi_close($mh);

        return $locations;
    }

    /**
     * @param $url
     * @param $options
     *
     * @return string
     */
    private function followToLocation($url, array $options)
    {
        $ch = $this->getHandle($url, $options);
        $this->curl->curl_exec($ch);
        $url = $this->getUrlFromHandle($ch, $options);
        $this->curl->curl_close($ch);

        return $url;
    }

    /**
     * @param       $url
     * @param array $options
     *
     * @return resource
     */
    private function getHandle($url, array $options)
    {
        $ch = $this->curl->curl_init($url);

        // Sane default options for the handle
        $curl_opts = [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 100,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false, // suppress certain SSL errors
            CURLOPT_SSL_VERIFYPEER => false,

            // Some hosts don't respond well if you're a bot
            // so we lie
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.11; rv:45.0) Gecko/20100101 Firefox/45.0',
            CURLOPT_AUTOREFERER    => true
        ];

        // If we passed in other handle options, add them here
        if ( isset($options['curl_opts']) ) {
            $curl_opts = $options['curl_opts'] + $curl_opts;
        }

        $this->curl->curl_setopt_array($ch, $curl_opts);



        return $ch;
    }

    /**
     * @param       $ch
     * @param array $options
     *
     * @return string
     */
    private function getUrlFromHandle($ch, array $options)
    {
        if ( array_key_exists('canonical', $options) && $options['canonical'] === true ) {

            $url = $this->canonical->url($this->curl->curl_multi_getcontent($ch));

            if ( $url ) {
                return $url;
            }
        }

        return $this->curl->curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    }

}