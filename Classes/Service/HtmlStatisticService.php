<?php

namespace Nemo64\CriticalCss\Service;


use Nemo64\CriticalCss\Domain\Model\HtmlStatistics;
use TYPO3\CMS\Core\SingletonInterface;

class HtmlStatisticService implements SingletonInterface
{
    private const REGEX_ATTRIBUTE = '(?<attr_name>[^>=\s]+)(="(?<attr_val1>[^"]*)"|=\'(?<attr_val2>[^\']*)\'|=(?<attr_val3>[^\s>]*))?';
    private const REGEX_TAG = '<(?<tag_name>[^/\s]+)(?<attr>(\s*' . self::REGEX_ATTRIBUTE . ')+)\s*>';

    public function createStatistic(string $html): HtmlStatistics
    {
        $statistics = new HtmlStatistics();

        preg_match_all('#' . self::REGEX_TAG . '#ui', $html, $tagMatches, PREG_PATTERN_ORDER);

        foreach ($tagMatches['tag_name'] as $tagName) {
            $statistics->addTagName($tagName);
        }

        foreach ($tagMatches['attr'] as $attributes) {

            preg_match_all('#' . self::REGEX_ATTRIBUTE . '#ui', $attributes, $attrMatches, PREG_SET_ORDER);

            foreach ($attrMatches as $attrMatch) {
                $attrValue = $attrMatch['attr_val1'] ?: $attrMatch['attr_val2'] ?: $attrMatch['attr_val3'];
                $statistics->addAttribute($attrMatch['attr_name'], $attrValue);
            }
        }

        return $statistics;
    }
}
