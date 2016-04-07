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
     * @param Curl|null $curl
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
     * @return string
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
     * @return string
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
        if ( empty($urls) ) {
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
     * @return mixed
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
        $this->curl->curl_setopt_array($ch, array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 100,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false, // suppress certain SSL errors
            CURLOPT_SSL_VERIFYPEER => false,
        ));

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
                return $this->cleanUrl($url);
            }
        }

        return $this->cleanUrl($this->curl->curl_getinfo($ch, CURLINFO_EFFECTIVE_URL));
    }

    /**
     * @param $url
     *
     * @return string
     */
    private function cleanUrl($url)
    {
        //remove utm params via regex replace, and cleanup and artifacts left behind afterwards
        $url = preg_replace('/\?$/', '', preg_replace('/&$/', '', preg_replace('/utm_[^&]+&?/i', '', $url)));
    
        //remove any hash anchors.
        $hash_pos = strpos($url,"#");
        if ($hash_pos !== false) {
            $url = substr($url, 0, $hash_pos);
        }
        
        return $url;
    }
}
