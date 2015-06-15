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
        if (!is_array($urls)) {
            $urls = [$urls];
        }

        $locations = [];

        foreach ($urls as $url) {

            if ( filter_var($url, FILTER_VALIDATE_URL) === false ) {
                return $url;
            }

            $headers = get_headers($url, 1);

            $location = (array_key_exists('Location', $headers)) ? $headers['Location'] : $url;

            if ( is_array($location) ) {
                $locations[$url] = end($location);
                continue;
            }

            $locations[$url] = $location;

            continue;
        }
    }
}