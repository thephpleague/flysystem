<?php

namespace League\Flysystem\Adapter;

use League\Flysystem\Util;
use League\Flysystem\Util\ContentListingFormatter;
use PHPUnit\Framework\TestCase;

class ContentListingFormatterTest extends TestCase
{
    /**
     * @test
     * @dataProvider formatterDataProvider
     */
    public function formatting_a_listing($root, $recurse, $caseSensitive, array $listing, array $expected)
    {
        $formatted = (new ContentListingFormatter($root, $recurse, $caseSensitive))->formatListing($listing);
        $expected = array_map([$this, 'addPathInfo'], $expected);

        $this->assertEquals($expected, $formatted);
    }

    public function formatterDataProvider()
    {
        $recurse = true;
        $noRecursion = false;
        $caseSensitive = true;
        $notCaseSensitive = false;

        return [
            /* normal cases */
            ['/dirname', $noRecursion, $caseSensitive, [['path' => '/dirname/here.txt']], [['path' => '/dirname/here.txt']]],
            ['/dirname', $noRecursion, $notCaseSensitive, [['path' => '/dirname/here.txt']], [['path' => '/dirname/here.txt']]],
            ['/dirname', $recurse, $caseSensitive, [['path' => '/dirname/here.txt']], [['path' => '/dirname/here.txt']]],
            ['/dirname', $recurse, $notCaseSensitive, [['path' => '/dirname/here.txt']], [['path' => '/dirname/here.txt']]],

            /* normal cases */
            ['/dirName', $noRecursion, $caseSensitive, [['path' => '/dirname/here.txt']], []],
            ['/dirName', $noRecursion, $notCaseSensitive, [['path' => '/dirname/here.txt']], [['path' => '/dirname/here.txt']]],
            ['/dirName', $recurse, $caseSensitive, [['path' => '/dirname/here.txt']], []],
            ['/dirName', $recurse, $notCaseSensitive, [['path' => '/dirname/here.txt']], [['path' => '/dirname/here.txt']]],
        ];
    }

    private function addPathInfo(array $entry)
    {
        return $entry + Util::pathinfo($entry['path']);
    }
}
