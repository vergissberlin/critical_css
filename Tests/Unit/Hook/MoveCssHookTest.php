<?php

namespace Nemo64\CriticalCss\Tests\Unit\Hook;


use Nemo64\CriticalCss\Hook\MoveCssHook;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class MoveCssHookTest extends UnitTestCase
{
    /** @var MoveCssHook */
    protected $hook;

    protected function setUp()
    {
        parent::setUp();
        $this->hook = new MoveCssHook();
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    public function testWithExternal()
    {
        $params = [
            'cssFiles' => [
                [
                    'file' => 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css',
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
            ->with('')
        ;

        // since they are passed as reference, i'll have to copy them
        $originalParams = $params;
        $this->hook->postCssTransform($params, $pageRenderer);
        $this->assertEquals($originalParams, $params);
    }
}
