<?php

namespace Nemo64\CriticalCss\Cache;


use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CacheStorageAdapter implements CacheStorageInterface
{
    /**
     * @var \TYPO3\CMS\Core\Cache\Frontend\FrontendInterface
     */
    private $cache;

    public function __construct(string $cacheName)
    {
        $this->cache = GeneralUtility::makeInstance(CacheManager::class)->getCache($cacheName);
    }

    /**
     * @param string $key
     *
     * @return CacheEntry|null the data or false
     */
    public function fetch($key)
    {
        $result = $this->cache->get($key);
        if ($result instanceof CacheEntry) {
            return $result;
        }

        return null;
    }

    /**
     * @param string $key
     * @param CacheEntry $data
     *
     * @return bool
     */
    public function save($key, CacheEntry $data)
    {
        $this->cache->set($key, $data);
        return true;
    }

    /**
     * @param string $key
     *
     * @return bool
     */
    public function delete($key)
    {
        return $this->cache->remove($key);
    }
}
