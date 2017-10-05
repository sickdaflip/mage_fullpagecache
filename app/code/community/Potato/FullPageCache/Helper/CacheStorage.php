<?php

/**
 * This class very useful for cache control because
 * Zend_Cache_Core searching needed cache by glob function and open each file and its very slow operation
 * also its will allow visual manage cache via interface
 *
 * Class Potato_FullPageCache_Helper_CacheStorage
 */
class Potato_FullPageCache_Helper_CacheStorage extends Mage_Core_Helper_Abstract
{
    const TAGS_KEY_SEPARATOR = '|';

    /**
     * Is allowed cache size
     *
     * @param int|string $cacheSize
     *
     * @return bool
     */
    static function getIsAllowedCacheSize($cacheSize)
    {
        return (self::getCacheSize() + $cacheSize) < Potato_FullPageCache_Helper_Config::getMaxAllowedSize();
    }

    /**
     * Save cache info
     *
     * @param Potato_FullPageCache_Model_Cache_Default $cache
     * @param                                          $expire
     * @param array                                    $tags
     * @param null                                     $contentSize
     * @param string                                   $privateTag
     *
     * @return bool
     */
    static function registerCache(Potato_FullPageCache_Model_Cache_Default $cache, $expire, $tags = array(), $contentSize = null, $privateTag = '')
    {
        //save cache storage
        Mage::getModel('po_fpc/storage')
            ->loadByCacheId($cache->getId())
            ->setSize($contentSize)
            ->setCacheId($cache->getId())
            ->setTags(self::getKeyByTags($tags))
            ->setPrivateTag($privateTag)
            ->setExpire($expire)
            ->setRequestUrl(Potato_FullPageCache_Helper_Data::getRequestedUrl())
            ->setStoreId(Mage::app()->getStore()->getId())
            ->save()
        ;
        if (!$privateTag || in_array(Potato_FullPageCache_Model_Cache::BLOCK_TAG, $tags)) {
            return true;
        }
        //update statistic MISS
        Potato_FullPageCache_Helper_Data::updateStatistics(Mage::app()->getStore()->getId());
        return true;
    }

    /**
     * Explode array tags to string used as key
     *
     * @param array $tags
     *
     * @return string
     */
    static function getKeyByTags($tags)
    {
        return implode(self::TAGS_KEY_SEPARATOR, $tags);
    }

    /**
     * Remove cache info from storage
     *
     * @param string $id
     *
     * @return bool
     */
    static function unregisterCache($id)
    {
        Mage::getResourceModel('po_fpc/storage')->removeByCacheId($id);
        return true;
    }

    /**
     * Get cache ids by tags
     *
     * @param array $tags
     * @param string $mode
     *
     * @return array
     */
    static function getIdsByTags($tags, $mode)
    {
        return Mage::getResourceModel('po_fpc/storage')->getIdsByTags(self::getKeyByTags($tags), $mode);
    }

    static function getIdsByPrivateTag($privateTag, $tags=array())
    {
        return Mage::getResourceModel('po_fpc/storage')->getIdsByPrivateTag($privateTag, self::getKeyByTags($tags));
    }

    /**
     * Truncate storage collection
     *
     * @return true
     */
    static function cleanStorageData()
    {
        Mage::getResourceModel('po_fpc/storage')->cleanStorageData();
        return true;
    }

    /**
     * Get cache ids that expired
     *
     * @return array
     */
    static function getExpireIds()
    {
        return Mage::getResourceModel('po_fpc/storage')->getExpireIds();
    }

    /**
     * Get total cache size
     *
     * @return int
     */
    static function getCacheSize()
    {
        return Mage::getResourceModel('po_fpc/storage')->getStorageSize();
    }

    /**
     * Get content size (calculate by strlen function because for e.g. redis storage cache stored in DB)
     *
     * @param $content
     *
     * @return int
     */
    static function calculateSize($content)
    {
        if (is_array($content)) {
            $content = serialize($content);
        }
        return strlen($content);
    }
}