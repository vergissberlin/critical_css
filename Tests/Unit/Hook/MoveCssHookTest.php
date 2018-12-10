<?php

namespace Nemo64\CriticalCss\Tests\Unit\Hook;


use GuzzleHttp\Client;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Response;
use Nemo64\CriticalCss\Cache\Typo3CacheToPsr16Adapter;
use Nemo64\CriticalCss\Hook\MoveCssHook;
use Nimut\TestingFramework\TestCase\UnitTestCase;
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

    public function testFileMovement()
    {
        $params = [
            'cssFiles' => [
                [
                    'file' => 'typo3conf/ext/critical_css/Tests/Fixtures/Styles.css',
                    'rel' => 'stylesheet',
                    'media' => 'all',
                    'title' => '',
                    'compress' => true,
                    'forceOnTop' => false,
                    'allWrap' => '',
                    'excludeFromConcatenation' => false,
                    'splitChar' => '|',
                    'inline' => false,
                ],
            ],
        ];

        $pageRenderer = $this->createMock(PageRenderer::class);

        $pageRenderer->expects($this->atLeastOnce())
            ->method('getBodyContent')
            ->willReturn('<!-- critical_css: below the fold -->')
        ;

        // the comment should be replaced
        $pageRenderer->expects($this->once())
            ->method('setBodyContent')
            ->with("\n<style>@import url(\"typo3conf/ext/critical_css/Tests/Fixtures/Styles.css\") all;</style>")
        ;

        $this->hook->postCssTransform($params, $pageRenderer);
        $this->assertEquals(['cssFiles' => []], $params);
    }

    public function testExternalRequest()
    {
        $params = [
            'cssFiles' => [
                [
                    'file' => 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css',
                    'rel' => 'stylesheet',
                    'media' => 'all',
                    'title' => '',
                    'compress' => false,
                    'forceOnTop' => false,
                    'allWrap' => '',
                    'excludeFromConcatenation' => true,
                    'splitChar' => '|',
                    'inline' => false,
                ],
            ],
        ];

        $guzzle = $this->createMock(Client::class);
        GeneralUtility::addInstance(Client::class, $guzzle);

        $guzzle->expects($this->once())
            ->method('__call')
            ->with('getAsync', ['https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css'])
            ->willReturn(new FulfilledPromise(new Response(200, [], 'body {color: red}')))
        ;

        GeneralUtility::addInstance(Typo3CacheToPsr16Adapter::class, $this->createMock(Typo3CacheToPsr16Adapter::class));

        $pageRenderer = $this->createMock(PageRenderer::class);

        $pageRenderer->expects($this->atLeastOnce())
            ->method('getBodyContent')
            ->willReturn('<!-- critical_css: below the fold -->')
        ;

        // the comment should be replaced
        $pageRenderer->expects($this->once())
            ->method('setBodyContent')
            ->with("\n<style>@import url(\"https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css\") all;</style>")
        ;

        $this->hook->postCssTransform($params, $pageRenderer);
        $this->assertEquals(['cssFiles' => []], $params);
    }
}
