<?php namespace Mushroom;

/**
 * Class Mushroom
 *
 * @package Mushroom
 */
class Mushroom
{
    /**
     * @param $urls
     *
     * @return string
     */
    public function expand($urls)
    {
        if ( ! is_array($urls) ) {
            return $this->followToLocation($urls);
        }

        $locations = [];

        foreach ( $urls as $key => $url ) {

            if ( filter_var($url, FILTER_VALIDATE_URL) === false ) {
                $locations[ $key ] = $url;
                continue;
            }

            $locations[ $key ] = $this->followToLocation($url);
        }

        return $locations;
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    private function followToLocation($url)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 100,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYHOST => false, // suppress certain SSL errors
            CURLOPT_SSL_VERIFYPEER => false,
        ));
        curl_exec($ch);
        $url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return $url;
    }

}