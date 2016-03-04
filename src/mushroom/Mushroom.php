<?php namespace Mushroom;

use WillWashburn\Curl;

/**
 * Class Mushroom
 *
 * @package Mushroom
 */
class Mushroom
{
    /**
     * @var Curl
     */
    private $curl;

    /**
     * Mushroom constructor.
     *
     * @param Curl|null $curl
     */
    public function __construct(Curl $curl = null)
    {
        $this->curl = is_null($curl) ? new Curl : $curl;
    }

    /**
     * @param $urls
     *
     * @return string
     */
    public function expand($urls)
    {
        if ( !is_array($urls) ) {
            return $this->followToLocation($urls);
        }

        return $this->batchFollow($urls);
    }

    /**
     * @param $urls
     *
     * @return array
     */
    private function batchFollow($urls)
    {
        if ( empty($urls) ) {
            return [];
        }

        $mh = $this->curl->curl_multi_init();

        $x = 0;
        foreach ( $urls as $key => $url ) {

            $$x = $this->getHandle($url);

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

            $locations[$key] = $this->getUrlFromHandle($$y);

            $y++;
        }


        $this->curl->curl_multi_close($mh);

        return $locations;
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    private function followToLocation($url)
    {
        $ch = $this->getHandle($url);
        $this->curl->curl_exec($ch);
        $url = $this->getUrlFromHandle($ch);
        $this->curl->curl_close($ch);

        return $url;
    }

    /**
     * @param $url
     *
     * @return resource
     */
    private function getHandle($url)
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
     * @param $ch
     *
     * @return string
     */
    private function getUrlFromHandle($ch)
    {
        $url = $this->curl->curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

        return trim($url, '/');
    }

}