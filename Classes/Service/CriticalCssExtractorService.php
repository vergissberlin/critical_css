<?php

namespace Nemo64\CriticalCss\Service;


use Nemo64\CriticalCss\Domain\Model\HtmlStatistics;
use Sabberworm\CSS\CSSList\AtRuleBlockList;
use Sabberworm\CSS\CSSList\CSSList;
use Sabberworm\CSS\CSSList\Document;
use Sabberworm\CSS\CSSList\KeyFrame;
use Sabberworm\CSS\Property\Selector;
use Sabberworm\CSS\Rule\Rule;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use Sabberworm\CSS\RuleSet\RuleSet;
use TYPO3\CMS\Core\SingletonInterface;

class CriticalCssExtractorService implements SingletonInterface
{
    /**
     * Creates a CSS Document with only the rules needed to render the passed html.
     *
     * @param Document $css The css document to extract the critical css from
     * @param HtmlStatistics $statistics
     */
    public function extract(Document $css, HtmlStatistics $statistics)
    {
        $selectorPattern = $this->createSelectorPattern($statistics);
        $this->filter($css, $selectorPattern);
    }

    private function filter(CSSList $list, string $selectorPattern)
    {
        foreach ($list->getContents() as $content) {
            if ($content instanceof KeyFrame) {
                $list->remove($content);
                continue;
            }

            if ($content instanceof DeclarationBlock) {
                $matchingSelectors = array_filter($content->getSelectors(), function (Selector $selector) use ($selectorPattern) {
                    return preg_match($selectorPattern, $selector->getSelector());
                });

                if (!empty($matchingSelectors)) {
                    $content->setSelectors($matchingSelectors);
                } else {
                    $list->remove($content);
                    continue;
                }
            }

            if ($content instanceof RuleSet) {
                $this->filterRuleSet($content);
                if (empty($content->getRules())) {
                    $list->remove($content);
                    continue;
                }
            }

            if ($content instanceof AtRuleBlockList) {
                if ($content->atRuleName() === 'media' && $content->atRuleArgs() === 'print') {
                    $list->remove($content);
                    continue;
                }

                $this->filter($content, $selectorPattern);
                if (empty($content->getContents())) {
                    $list->remove($content);
                    continue;
                }
            }
        }
    }

    private function filterRuleSet(RuleSet $ruleSet)
    {
        $ruleSet->removeRule('animation-');
        $ruleSet->removeRule('transition-');
        $ruleSet->removeRule('page-break-');
    }

    private function createSelectorPattern(HtmlStatistics $statistics): string
    {
        $quote = function ($string) {
            return preg_quote($string, '#');
        };

        $string = '';

        $tagNames = $statistics->getTagNames();
        if ($tagNames) {
            $string .= '(\\*|' . implode('|', array_map($quote, $tagNames)) . ')?';
        }

        $interchangeably = [];

        $ids = $statistics->getIds();
        foreach ($ids as $id) {
            $interchangeably[] = '\\#' . $quote($id);
        }

        $classNames = $statistics->getClassNames();
        foreach ($classNames as $className) {
            $interchangeably[] = '\\.' . $quote($className);
        }

        $attributes = $statistics->getAttributes();
        foreach ($attributes as $attributeName => $attributeValues) {
            $interchangeably[] = '\\[\s*' . $quote($attributeName) . '\s*\\]';
            $interchangeably[] = '\\[\s*' . $quote($attributeName) . '\s*[*^$]=\s*("[^"]*"|\'[^\']*\'|[^\\]]*)\s*\\]'; // TODO escaping

            foreach ($attributeValues as $attributeValue) {
                if (preg_match('#^\w+$#', $attributeValue)) {
                    $interchangeably[] = '\\[\s*' . $quote($attributeName) . '\s*=\s*["\']?' . $quote($attributeValue) . '["\']?\s*\\]';
                } else {
                    $attributeValue = strtr($quote($attributeValue), ['"' => '\\\\?"', "'" => "\\\\?'"]);
                    $interchangeably[] = '\\[\s*' . $quote($attributeName) . '\s*=\s*["\']' . $attributeValue . '["\']\s*\\]';
                }
            }
        }

        // pseudo selectors can't be correctly handled
        // TODO this won't parse selectors like :matches(foo > bar) correctly because of the space within them
        $interchangeably[] = ':[^\.\#\s]+';

        if ($interchangeably) {
            $string .= '(' . implode('|', $interchangeably) . ')*';
        }

        if (empty($string)) {
            throw new \Exception("No useful selector could be extracted");
        }

        return "#^(\s*[>~+]?\s*$string)+$#uisS";
    }

    private function matches(DeclarationBlock $declarationBlock, string $selectorParttern): bool
    {
        /** @var Selector $selector */
        foreach ($declarationBlock->getSelectors() as $selector) {
            if (preg_match($selectorParttern, $selector->getSelector())) {
                return true;
            }
        }

        return false;
    }
}
