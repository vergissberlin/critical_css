<?php

namespace Nemo64\CriticalCss\Tests\Unit\Service;


use Nemo64\CriticalCss\Service\CriticalCssExtractorService;
use Nemo64\CriticalCss\Service\HtmlStatisticService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CriticalCssExtractorServiceTest extends UnitTestCase
{
    /** @var CriticalCssExtractorService */
    protected $service;

    /** @var HtmlStatisticService */
    protected $htmlStatisticService;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new CriticalCssExtractorService();
        $this->htmlStatisticService = new HtmlStatisticService();
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public static function sets()
    {
        return [
            [
                'div {width: 100%} p {padding-bottom: 15px}',
                '<div></div>',
                'div {width: 100%}',
            ],
            [
                '.cls1 {width: 100%} .cls2 {padding-bottom: 15px}',
                '<div class="cls2"></div>',
                '.cls2 {padding-bottom: 15px}',
            ],
            [
                '[hi] {width: 100%} [du] {padding-bottom: 15px}',
                '<div hi></div>',
                '[hi] {width: 100%}',
            ],
            [
                'div.cls1 {width: 100%} div[class="cls1"] {height: 100%} div.cls2 {padding-bottom: 15px} div[class="cls2"] {height: 100%}',
                '<div class="cls1"></div>',
                'div.cls1 {width: 100%} div[class="cls1"] {height: 100%}',
            ],
            [
                'a {color: red} a:hover {color: blue} .some:hover {color: black}',
                '<a href="#"></a>',
                'a {color: red} a:hover {color: blue}',
            ],
            [
                'ul > li {list-style: none} ol > li {list-style: none}',
                '<ul><li></li></ul>',
                'ul > li {list-style: none}',
            ],
            [
                '#foo {width: 100%} #bar {width: 50%}',
                '<div id="foo"></div>',
                '#foo {width: 100%}',
            ],
            [
                'div#with.everything[used][used="yes"] {width: 100%} div {display: block} #with {height: 1px}',
                '<div id="with" class="everything" used="yes"></div>',
                'div#with.everything[used][used="yes"] {width: 100%} div {display: block} #with {height: 1px}',
            ],
            [
                'i[class*=icon] {width: 100%} i[class^=icon] {width: 100%} i[class$=red] {width: 100%}',
                '<i class="icon-red"></i>',
                'i[class*=icon] {width: 100%} i[class^=icon] {width: 100%} i[class$=red] {width: 100%}',
            ],
            [
                "[class='don\'t'] {width: 100%} [class*='don\'t'] {width: 100%} [class^='don\'t'] {width: 100%}",
                '<i class="don\'t"></i>',
                "[class='don\'t'] {width: 100%} [class*='don\'t'] {width: 100%} [class^='don\'t'] {width: 100%}",
            ],
            [
                '.multi.class {width: 100%} .multi {color: red} .class {color: blue} .test.class {color: white}',
                '<i class="class multi"></i>',
                '.multi.class {width: 100%} .multi {color: red} .class {color: blue}',
            ],
            [
                'div {animation: blink} @keyframes blink {0% {color: red} 100% {color: blue}}',
                '<div></div>',
                '',
            ],
            [
                '@media print {div{color: black}}',
                '<div></div>',
                '',
            ],
        ];
    }

    /**
     * @dataProvider sets
     */
    public function testExtract($css, $html, $expectedCss)
    {
        $html = $this->htmlStatisticService->createStatistic($html);
        $cssParser = new Parser($css);
        $css = $cssParser->parse();

        $this->service->extract($css, $html);
        $outputFormat = OutputFormat::create()->set('spaceBetweenBlocks', ' ')->set('semicolonAfterLastRule', false);
        $this->assertEquals($expectedCss, $css->render($outputFormat));
    }
}
