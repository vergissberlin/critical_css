<?php

namespace Nemo64\CriticalCss\Service;


use Nemo64\CriticalCss\Domain\Model\HtmlStatistics;
use Sabberworm\CSS\CSSList\Document;
use Sabberworm\CSS\Property\Selector;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use TYPO3\CMS\Core\SingletonInterface;

class CriticalCssExtractorService implements SingletonInterface
{
    /**
     * Creates a CSS Document with only the rules needed to render the passed html.
     *
     * @param Document $css The css document to extract the critical css from
     * @param HtmlStatistics $statistics
     *
     * @return Document with only the css required for the passed html
     */
    public function extract(Document $css, HtmlStatistics $statistics): Document
    {
        $result = clone $css;
        $selectorPattern = $this->createSelectorPattern($statistics);

        /** @var DeclarationBlock $declarationBlock */
        foreach ($result->getAllDeclarationBlocks() as $declarationBlock) {
            if ($this->matches($declarationBlock, $selectorPattern)) {
                continue;
            }

            $result->remove($declarationBlock);
        }

        return $result;
    }

    private function createSelectorPattern(HtmlStatistics $statistics): string
    {
        $quote = function ($string) {
            return preg_quote($string, '#');
        };

        $string = '';

        $tagNames = $statistics->getTagNames();
        if ($tagNames) {
            $string .= '(' . implode('|', array_map($quote, $tagNames)) . ')?';
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
