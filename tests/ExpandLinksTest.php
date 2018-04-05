<?php

use Mockery as M;

/**
 * Class ExpandLinkTest.
 */
class ExpandLinkTest extends PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider linksProvider
     *
     * @param $expected
     * @param $input
     */
    public function test_expand_expands_single_links($input, $expected)
    {
        $mushroom = new \Mushroom\Mushroom();

        $this->assertEquals($expected, $mushroom->expand($input));
    }

    public function test_expand_expands_array_of_links()
    {
        $links = $this->linksProvider();

        $mushroom = new \Mushroom\Mushroom();

        $inputs = array_map(function ($value) {
            return $value[0];
        }, $links);

        $expected = array_map(function ($value) {
            return $value[1];
        }, $links);

        $this->assertEquals($expected, $mushroom->expand($inputs));
    }

    /**
     * @return array
     */
    public function linksProvider()
    {
        return [
            ['http://bit.ly/1bdDlXc', 'https://www.google.com/?gws_rd=ssl'], // shortened
            ['https://www.google.com/', 'https://www.google.com/'], // nothing
            ['https://jigsaw.w3.org/HTTP/300/301.html', 'https://jigsaw.w3.org/HTTP/300/Overview.html'], // 301 redirect
            ['http://blog.tailwindapp.com/pinterest-smart-feed-pin-visibility/', 'https://blog.tailwindapp.com/pinterest-smart-feed-pin-visibility/'], // trailing slash
            ['http://wp.me//p7gsPW-Gi', 'https://traveltalesoflife.com/travel-theme-unexpected-the-co-ed-turkish-bath/'],
        ];
    }

    /**
     * @dataProvider canonicalLinksProvider
     *
     * @param $input
     * @param $expected
     */
    public function test_expand_single_canonical_link($input, $expected)
    {
        $mushroom = new \Mushroom\Mushroom();

        $this->assertEquals($expected, $mushroom->canonical($input));
    }

    public function test_expand_multiple_canonical_link()
    {
        $mushroom = new \Mushroom\Mushroom();
        $links    = $this->canonicalLinksProvider();

        $inputs = array_map(function ($value) {
            return $value[0];
        }, $links);

        $expected = array_map(function ($value) {
            return $value[1];
        }, $links);

        $this->assertEquals($expected, $mushroom->canonical($inputs));
    }

    /**
     * @return array
     */
    public function canonicalLinksProvider()
    {
        return [
            ['http://blog.tailwindapp.com/tailwind-publisher-2-0/?foo=foobar', 'https://blog.tailwindapp.com/tailwind-publisher-2-0/'],
            ['http://blog.tailwindapp.com/tailwind-publisher-2-0?foo=foobar', 'https://blog.tailwindapp.com/tailwind-publisher-2-0/'],
            ['http://www.willwashburn.com/?foo', 'http://willwashburn.com/?foo'], //no tags
            ['http://www.practicallyfunctional.com/so-creative-18-delicious-game-day-appetizers/', 'https://www.practicallyfunctional.com/so-creative-18-delicious-game-day-appetizers/'], // protocol issues
            ['https://vimeo.com/63823593', 'https://vimeo.com/63823593'], // canonical is relative
        ];
    }

    public function test_setting_curl_options_works()
    {
        $expected_curl_opts = array(
            CURLOPT_FOLLOWLOCATION => false,
        );

        $curl
            = M::mock('\WillWashburn\Curl')
               ->shouldReceive('curl_init')->getMock()
               ->shouldReceive('curl_exec')->getMock()
               ->shouldReceive('curl_close')->getMock()
               ->shouldReceive('curl_getinfo')->getMock()
               ->shouldReceive('curl_setopt_array')
               ->with(M::any(), M::on(function ($arg) use ($expected_curl_opts) {
                foreach (array_keys($expected_curl_opts) as $key) {
                    if ($arg[$key] != $expected_curl_opts[$key]) {
                        return false;
                    }
                }

                   return true;
               }))
               ->getMock();

        $mushroom = new \Mushroom\Mushroom($curl);

        $mushroom->expand('http://www.foobar.com', $expected_curl_opts);
    }
}
