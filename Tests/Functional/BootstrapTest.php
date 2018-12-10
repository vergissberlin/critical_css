<?php

namespace Nemo64\CriticalCss\Tests\Functional;


use Nimut\TestingFramework\TestCase\FunctionalTestCase;

class BootstrapTest extends FunctionalTestCase
{
    protected $testExtensionsToLoad = [
        'typo3conf/ext/critical_css',
    ];

    protected $pathsToLinkInTestInstance = [
        '../../../../vendor' => 'vendor',
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/Pages.xml');
    }

    public function testBasicBootstrapLayout()
    {
        $this->setUpFrontendRootPage(1, [
            'EXT:critical_css/Tests/Fixtures/Renderer.t3s',
            'EXT:critical_css/Tests/Fixtures/Bootstrap.t3s',
        ]);
        $response = $this->getFrontendResponse(1);
        $this->assertEquals('success', $response->getStatus());

        $content = $response->getContent();
        $size = strpos($content, '</style>') - strpos($content, '<style type="text/css">');
        $this->assertGreaterThan(1000, $size, $content);
        $this->assertLessThan(2000, $size, $content);
    }

    public function testExternalBootstrapLayout()
    {
        $this->setUpFrontendRootPage(1, [
            'EXT:critical_css/Tests/Fixtures/Renderer.t3s',
            'EXT:critical_css/Tests/Fixtures/BootstrapExternal.t3s',
        ]);
        $response = $this->getFrontendResponse(1);
        $this->assertEquals('success', $response->getStatus());

        $content = $response->getContent();
        $this->assertContains('bootstrapcdn.com', $content, $content);
        $size = strpos($content, '</style>') - strpos($content, '<style type="text/css">');
        $this->assertGreaterThan(1000, $size, $content);
        $this->assertLessThan(2000, $size, $content);
    }
}
