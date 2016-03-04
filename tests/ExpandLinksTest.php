<?php

/**
 * Class ExpandLinkTest
 */
class ExpandLinkTest extends PHPUnit_Framework_TestCase
{
    /**
     *
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
            ['http://bit.ly/1bdDlXc', 'http://www.google.com/'], // shortened
            ['http://www.google.com', 'http://www.google.com/'], // nothing
            ['http://pinreach.com', 'https://www.tailwindapp.com/'], //redirect
        ];
    }

}