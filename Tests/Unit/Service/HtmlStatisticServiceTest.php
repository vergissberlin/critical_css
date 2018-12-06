<?php

namespace Nemo64\CriticalCss\Tests\Unit\Service;


use Nemo64\CriticalCss\Service\HtmlStatisticService;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class HtmlStatisticServiceTest extends UnitTestCase
{
    /** @var HtmlStatisticService */
    protected $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new HtmlStatisticService();
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public static function htmlDataProvider(): array
    {
        return [
            [
                '<div class="container"></div>',
                [
                    'tagNames' => ['div'],
                    'ids' => [],
                    'classNames' => ['container'],
                    'attributes' => [
                        'class' => ['container'],
                    ],
                ],
            ],
            [
                '<div id="main" class="container"><div class=row><div class=\'col-sm-12\'></div></div></div>',
                [
                    'tagNames' => ['div'],
                    'ids' => ['main'],
                    'classNames' => ['container', 'row', 'col-sm-12'],
                    'attributes' => [
                        'id' => ['main'],
                        'class' => ['container', 'row', 'col-sm-12'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider htmlDataProvider
     */
    public function testResult($html, $expectedResult)
    {
        $result = $this->service->createStatistic($html);
        $this->assertEquals($expectedResult, $result->toArray());
    }
}
