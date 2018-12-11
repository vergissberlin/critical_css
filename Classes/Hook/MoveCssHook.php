<?php

namespace Nemo64\CriticalCss\Hook;


use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use GuzzleHttp\Psr7\Response;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\Psr16CacheStorage;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Nemo64\CriticalCss\Cache\Typo3CacheToPsr16Adapter;
use Nemo64\CriticalCss\Domain\Model\HtmlStatistics;
use Nemo64\CriticalCss\Service\CriticalCssExtractorService;
use Nemo64\CriticalCss\Service\HtmlStatisticService;
use Sabberworm\CSS\OutputFormat;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Property\Import;
use Sabberworm\CSS\Renderable;
use Sabberworm\CSS\Value\CSSString;
use Sabberworm\CSS\Value\URL;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class MoveCssHook
{
    const MARKER_BELOW_THE_FOLD = '<!-- critical_css: below the fold -->';

    /**
     * @var string
     * @internal used to overwrite the PATH_site constant during testing
     */
    public $pathSite = PATH_site ?? '';

    /**
     * @var Client
     */
    private $guzzleClient;

    public function postCssTransform(array &$params, PageRenderer $pageRenderer): void
    {
        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = $GLOBALS['TSFE'];
        if ($tsfe->no_cache) {
            return;
        }

        // if the marker for "below the fold" does not exist, than there is nothing we can do here
        $markerPosition = strrpos($pageRenderer->getBodyContent(), self::MARKER_BELOW_THE_FOLD);
        if ($markerPosition === false) {
            return;
        }

        $htmlStatisticService = GeneralUtility::makeInstance(HtmlStatisticService::class);
        $htmlStatistics = $htmlStatisticService->createStatistic(substr($pageRenderer->getBodyContent(), 0, $markerPosition));

        $entries = [];
        foreach (array_intersect(['cssLibs', 'cssFiles'], array_keys($params)) as $category) {
            foreach ($params[$category] as $index => $file) {
                if ($file['rel'] !== 'stylesheet') {
                    continue;
                }

                $entry = [
                    'file' => $file,
                    'category' => $category,
                    'categoryIndex' => $index,
                    'inlinePromise' => $this->createInlineStyle($file, $htmlStatistics),
                ];

                if ($file['forceOnTop']) {
                    array_unshift($entries, $entry);
                } else {
                    array_push($entries, $entry);
                }
            }
        }

        // wait for all promises (which might throw an exception
        foreach ($entries as $index => $entry) {
            /** @var PromiseInterface $inlinePromise */
            $inlinePromise = $entry['inlinePromise'];
            $entries[$index]['inline'] = $inlinePromise->wait();
        }

        $cObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        $inlineStyles = "";
        foreach ($entries as $entry) {
            $file = $entry['file'];
            $filename = GeneralUtility::createVersionNumberedFilename($file['file']);
            $import = new Import(new URL(new CSSString($filename)), $file['media']);
            $inlineStyles .= "\n" . $cObject->wrap("<style>$import</style>", $file['allWrap'], $file['splitChar']);
            $pageRenderer->addCssInlineBlock($file['file'], $entry['inline'], false, false);
            unset($params[$entry['category']][$entry['categoryIndex']]);
        }

        $pageRenderer->setBodyContent(substr_replace(
            $pageRenderer->getBodyContent(),
            $inlineStyles,
            $markerPosition,
            strlen(self::MARKER_BELOW_THE_FOLD)
        ));
    }

    private function createInlineStyle(array $file, HtmlStatistics $htmlStatistics): PromiseInterface
    {
        if (preg_match('#fonts.googleapis.com#', $file['file'])) {
            return new FulfilledPromise('');
        }

        if (preg_match('#^https?#', $file['file'])) {
            $promise = $this->requestFile($file);
        } else if (file_exists($this->pathSite . $file['file'])) {
            $promise = new FulfilledPromise(file_get_contents($this->pathSite . $file['file']));
        } else {
            $promise = new RejectedPromise("path {$file['file']} can't be resolved");
        }

        return $promise->then(function ($fileContent) use ($htmlStatistics) {
            $criticalCssExtractorService = GeneralUtility::makeInstance(CriticalCssExtractorService::class);
            $css = (GeneralUtility::makeInstance(Parser::class, $fileContent))->parse();
            $criticalCssExtractorService->extract($css, $htmlStatistics);
            return $this->renderCss($css);
        });
    }

    private function requestFile(array $file): PromiseInterface
    {
        $parseResponse = function (Response $response) {
            return $response->getBody()->getContents();
        };

        return $this->getGuzzleClient()->getAsync($file['file'])->then($parseResponse);
    }

    private function getGuzzleClient(): Client
    {
        if ($this->guzzleClient !== null) {
            return $this->guzzleClient;
        }

        $handlerStack = HandlerStack::create();
        $cache = GeneralUtility::makeInstance(Typo3CacheToPsr16Adapter::class, 'critical_css_download');
        $cacheStorage = GeneralUtility::makeInstance(Psr16CacheStorage::class, $cache);
        $cacheStrategy = GeneralUtility::makeInstance(PrivateCacheStrategy::class, $cacheStorage, 3600);
        $middleware = GeneralUtility::makeInstance(CacheMiddleware::class, $cacheStrategy);
        $handlerStack->push($middleware, 'cache');

        $this->guzzleClient = GeneralUtility::makeInstance(Client::class, ['handler' => $handlerStack]);
        return $this->guzzleClient;
    }

    private function renderCss(Renderable $renderable): string
    {
        $outputFormat = OutputFormat::createCompact()->set('semicolonAfterLastRule', false);
        return $renderable->render($outputFormat);
    }
}
