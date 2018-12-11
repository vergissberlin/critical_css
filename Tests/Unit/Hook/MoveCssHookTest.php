<?php

namespace Nemo64\CriticalCss\Tests\Unit\Hook;


use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Nemo64\CriticalCss\Cache\Typo3CacheToPsr16Adapter;
use Nemo64\CriticalCss\Hook\MoveCssHook;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class MoveCssHookTest extends UnitTestCase
{
    /** @var MoveCssHook */
    protected $hook;

    /** @var TypoScriptFrontendController */
    protected $tsfe;

    protected function setUp()
    {
        parent::setUp();
        $this->hook = new MoveCssHook();

        $this->tsfe = $GLOBALS['TSFE'] = $this->createMock(TypoScriptFrontendController::class);
    }

    protected function tearDown()
    {
        unset($GLOBALS['TSFE']);
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    protected static function createCssFileDefinition(string $file, bool $process = true, bool $forceOnTop = false)
    {
        return [
            'file' => $file,
            'rel' => 'stylesheet',
            'media' => 'all',
            'title' => '',
            'compress' => $process,
            'forceOnTop' => $forceOnTop,
            'allWrap' => '',
            'excludeFromConcatenation' => !$process,
            'splitChar' => '|',
            'inline' => false,
        ];
    }

    protected function assertStylesheets(array $stylesheets, MockObject $pageRenderer)
    {
        $expectedBodyContent = '';
        $expectedHeaderData = [];
        foreach ($stylesheets as $stylesheet) {
            $expectedBodyContent .= "\n<style>@import url(\"$stylesheet\") all;</style>";
            $expectedHeaderData[] = ['<link rel="preload" href="' . htmlspecialchars($stylesheet) . '" as="style" media="all">'];
        }

        $pageRenderer->expects($this->atLeastOnce())
            ->method('getBodyContent')
            ->willReturn('<!-- critical_css: below the fold -->')
        ;

        $pageRenderer->expects($this->once())
            ->method('setBodyContent')
            ->with($expectedBodyContent)
        ;

        $pageRenderer->expects($this->exactly(count($expectedHeaderData)))
            ->method('addHeaderData')
            ->withConsecutive(...$expectedHeaderData)
        ;
    }

    public function testFileMovement()
    {
        $params = [
            'cssFiles' => [
                self::createCssFileDefinition('typo3conf/ext/critical_css/Tests/Fixtures/Styles.css', true),
            ],
        ];

        $pageRenderer = $this->createMock(PageRenderer::class);
        $this->assertStylesheets(['typo3conf/ext/critical_css/Tests/Fixtures/Styles.css'], $pageRenderer);

        $this->hook->postCssTransform($params, $pageRenderer);
        $this->assertEquals(['cssFiles' => []], $params);
    }

    public function testExternalRequest()
    {
        $params = [
            'cssFiles' => [
                self::createCssFileDefinition('http://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css', false),
            ],
        ];

        $guzzle = $this->createMock(Client::class);
        GeneralUtility::addInstance(Client::class, $guzzle);

        $guzzle->expects($this->once())
            ->method('__call')
            ->with('getAsync', ['http://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css'])
            ->willReturn(new FulfilledPromise(new Response(200, [], 'body {color: red}')))
        ;

        GeneralUtility::addInstance(Typo3CacheToPsr16Adapter::class, $this->createMock(Typo3CacheToPsr16Adapter::class));

        $pageRenderer = $this->createMock(PageRenderer::class);
        $this->assertStylesheets(['http://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css'], $pageRenderer);

        $this->hook->postCssTransform($params, $pageRenderer);
        $this->assertEquals(['cssFiles' => []], $params);
    }

    public function testForceOnTop()
    {
        $params = [
            'cssFiles' => [
                self::createCssFileDefinition('typo3conf/ext/critical_css/Tests/Fixtures/Styles.css', false, false),
                self::createCssFileDefinition('typo3conf/ext/critical_css/Tests/Fixtures/Styles2.css', false, true),
            ],
        ];

        $pageRenderer = $this->createMock(PageRenderer::class);
        $this->assertStylesheets([
            'typo3conf/ext/critical_css/Tests/Fixtures/Styles2.css',
            'typo3conf/ext/critical_css/Tests/Fixtures/Styles.css',
        ], $pageRenderer);

        $this->hook->postCssTransform($params, $pageRenderer);
        $this->assertEquals(['cssFiles' => []], $params);
    }
}
