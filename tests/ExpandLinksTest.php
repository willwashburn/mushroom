<?php

use Mockery as M;
use Mushroom\Mushroom;

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
        $mushroom = new Mushroom();

        $this->assertEquals($expected, $mushroom->expand($input));
    }

    public function test_expand_expands_array_of_links()
    {
        $links = $this->linksProvider();

        $mushroom = new Mushroom();

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
            ['https://www.rapidtables.com/web/dev/redirect/html-redirect-test.html', 'https://www.rapidtables.com/web/dev/html-redirect.html'],
            ['https://www.midgesdaughter.com/just-say-yes-to-cannabis/', 'https://www.midgesdaughter.com/just-say-yes-to-cannabis/'],
            ['https://diply.com/article/auntyacid/pinterest-diy-easy-solutions', 'https://diply.com/article/auntyacid/pinterest-diy-easy-solutions'],
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
        $mushroom = new Mushroom();

        $this->assertEquals($expected, $mushroom->canonical($input));
    }

    public function test_expand_multiple_canonical_link()
    {
        $mushroom = new Mushroom();
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
            ['https://vimeo.com/63823593', 'https://vimeo.com/63823593'], // canonical is relative

            /// http-refresh links
            ['https://www.rapidtables.com/web/dev/redirect/html-redirect-test.html', 'https://www.rapidtables.com/web/dev/html-redirect.html'],

            // share-a-sale redirect links
            //            ['https://www.caitlinsrecommendedcreations.com/DGYBlueAgateLidCeram','https://www.darngoodyarn.com/collections/yarn-bowls/products/blue-agate-w-lid-ceramic-yarn-bowl?sscid=b1k3_2o9cf'],

            // Relative url oddities
            ['https://www.facebook.com/groups/193732801223429/?ref=group_browse_new', 'https://www.facebook.com/login/'],

            // Timeout issue
            ['https://www.midgesdaughter.com/just-say-yes-to-cannabis/', 'https://www.midgesdaughter.com/just-say-yes-to-cannabis/'],

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
               ->shouldReceive('curl_multi_init')->getMock()
               ->shouldReceive('curl_multi_add_handle')->getMock()
               ->shouldReceive('curl_multi_exec')->getMock()
               ->shouldReceive('curl_multi_getcontent')->getMock()
               ->shouldReceive('curl_multi_close')->getMock()
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

        $mushroom = new Mushroom($curl);

        $mushroom->expand('http://www.foobar.com', $expected_curl_opts);
    }

    public function test_without_follow_http_redirects()
    {

        $links = [
            ['http://bit.ly/1bdDlXc', 'https://www.google.com/?gws_rd=ssl'], // shortened
            ['https://www.google.com/', 'https://www.google.com/'], // nothing
        ];

        $mushroom = new Mushroom();
        foreach ($links as list( $link, $expected_result )) {
            $result = $mushroom->expand($link, [], false);

            $this->assertEquals($expected_result, $result);
        }
    }

    public function test_get_html_works()
    {
        $links = [
            ['http://bit.ly/1bdDlXc', 'https://www.google.com/'],
            ['http://www.tailwindapp.com', 'https://www.tailwindapp.com/'],
            ['https://diply.com/article/auntyacid/pinterest-diy-easy-solutions', 'https://diply.com/article/auntyacid/pinterest-diy-easy-solutions'],
            ['https://www.zazzle.com/cookie_monster_cookies_for_santa_dinner_plate-115773106232089655', 'https://www.zazzle.com/cookie_monster_cookies_for_santa_dinner_plate-115773106232089655'],
        ];

        $mushroom = new Mushroom();
        foreach ($links as list( $link, $expected_result )) {
            $result = $mushroom->canonical($link);

            $this->assertEquals($expected_result, $result);

            $this->assertNotFalse($mushroom->getCachedHtml($link), $link);
            $this->assertNotEmpty($mushroom->getCachedHttpStatusCode($link), $link);
        }

        $this->assertFalse($mushroom->getCachedHtml('https://www.tailwindapp.com'));
    }

    public function test_add_js_redirect_domain()
    {
        $jsRedirectDomain = 'google.com';

        $mushroom = new Mushroom();
        $mushroom->addJsRedirectDomain($jsRedirectDomain);

        $domains = $mushroom->getJsRedirectDomains();
        $this->assertTrue(in_array($jsRedirectDomain, $domains));
    }
}
