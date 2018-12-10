<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform'][]
    = \Nemo64\CriticalCss\Hook\MoveCssHook::class . '->postCssTransform';

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['critical_css_download'] = [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'backend' => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
    'groups' => [
        'pages',
    ],
];
