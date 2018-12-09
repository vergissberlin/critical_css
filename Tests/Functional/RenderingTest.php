<?php

namespace Nemo64\CriticalCss\Tests\Functional;


use Nemo64\CriticalCss\Hook\MoveCssHook;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;

class RenderingTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3conf/ext/critical_css',
    ];

    protected $publicStylesheetPath;

    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/Pages.xml');
        $this->publicStylesheetPath = '/root/typo3conf/ext/critical_css/Tests/Fixtures/Styles.css?' . filemtime(__DIR__ . '/../Fixtures/Styles.css');
    }

    public function testDefaultPageRendering()
    {
        $this->setUpFrontendRootPage(1, [
            'EXT:critical_css/Tests/Fixtures/Renderer.t3s',
        ]);
        $response = $this->getFrontendResponse(1);
        $this->assertEquals('success', $response->getStatus());
        $this->assertContains(
            '<link rel="stylesheet" type="text/css" href="' . $this->publicStylesheetPath . '" media="all">',
            $response->getContent(),
            "expect link to stylesheet"
        );
    }

    public function testModifiedRendering()
    {
        $this->setUpFrontendRootPage(1, [
            'EXT:critical_css/Configuration/TypoScript/BasedOnContentElement/setup.txt',
            'EXT:critical_css/Tests/Fixtures/Renderer.t3s',
        ]);
        $response = $this->getFrontendResponse(1);
        $this->assertEquals('success', $response->getStatus());
        $this->assertContains(
            '<style>@import url("' . $this->publicStylesheetPath . '") all;</style>',
            $response->getContent(),
            "expect link to stylesheet"
        );
    }

    public function testNoCacheRendering()
    {
        $this->setUpFrontendRootPage(1, [
            'EXT:critical_css/Configuration/TypoScript/BasedOnContentElement/setup.txt',
            'EXT:critical_css/Tests/Fixtures/Renderer.t3s',
            'EXT:critical_css/Tests/Fixtures/NoCache.t3s',
        ]);
        $response = $this->getFrontendResponse(1);
        $this->assertEquals('success', $response->getStatus());
        $this->assertNotContains(
            '<style>@import url("' . $this->publicStylesheetPath . '") all;</style>',
            $response->getContent(),
            "expect link to stylesheet"
        );
    }

    public function testStylePositioning()
    {
        $this->setUpFrontendRootPage(1, [
            'EXT:critical_css/Tests/Fixtures/Renderer.t3s',
            'EXT:critical_css/Tests/Fixtures/Positioning.t3s',
        ]);
        $response = $this->getFrontendResponse(1);
        $this->assertEquals('success', $response->getStatus());
        $content = $response->getContent();
        $style = '<style>@import url("' . $this->publicStylesheetPath . '") all;</style>';

        $firstContentElementPosition = strpos($content, 'first content element');
        $secondContentElementPosition = strpos($content, 'second content element');
        $stylePosition = strpos($content, $style);
        $this->assertGreaterThan(0, $firstContentElementPosition, 'firstContentElementPosition');
        $this->assertGreaterThan(0, $secondContentElementPosition, 'secondContentElementPosition');
        $this->assertGreaterThan(0, $stylePosition, 'stylePosition');
        $this->assertGreaterThan($firstContentElementPosition, $stylePosition, 'first content element before style');
        $this->assertLessThan($secondContentElementPosition, $stylePosition, 'second content element after style');
    }

    public function testMarkerRenderedOnce()
    {
        $this->setUpFrontendRootPage(1, [
            'EXT:critical_css/Configuration/TypoScript/BasedOnContentElement/setup.txt',
            'EXT:critical_css/Tests/Fixtures/Renderer.t3s',
            'EXT:critical_css/Tests/Fixtures/NoCache.t3s', // prevent the marker from being replaced
        ]);

        $this->getDatabaseConnection()->insertArray('tt_content', ['pid' => 1, 'CType' => 'header']);
        $this->getDatabaseConnection()->insertArray('tt_content', ['pid' => 1, 'CType' => 'header']);
        $this->getDatabaseConnection()->insertArray('tt_content', ['pid' => 1, 'CType' => 'header']);

        $response = $this->getFrontendResponse(1);
        $this->assertEquals('success', $response->getStatus());
        $matches = substr_count($response->getContent(), MoveCssHook::MARKER_BELOW_THE_FOLD);
        $this->assertEquals(1, $matches);
    }
}
