<?php

namespace Nemo64\CriticalCss\Tests\Functional;


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

        $this->importDataSet('ntf://Database/pages.xml');
        $this->importDataSet('ntf://Database/tt_content.xml');

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
}
