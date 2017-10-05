<?php

/**
 * Save/Load store id by request
 *
 * Class Potato_FullPageCache_Helper_CacheStore
 */
class Potato_FullPageCache_Helper_CacheStore extends Mage_Core_Helper_Abstract
{
    /**
     * Get prepared cache instance
     *
     * @return Potato_FullPageCache_Model_Cache_Default
     */
    static function getCacheStoreInstance()
    {
        return Potato_FullPageCache_Model_Cache::getOutputCache(Potato_FullPageCache_Helper_Data::getRequestHash(),
            array('lifetime' => Potato_FullPageCache_Model_Cache::CONFIG_CACHE_LIFETIME)
        );
    }

    /**
     * Load store id by request
     *
     * @return bool
     */
    static function loadStoreByRequest()
    {
        $cache = self::getCacheStoreInstance();
        if ($cache->test()) {
            $result = $cache->load();
            if (!is_array($result)) {
                return false;
            }
            asort($result);
            return $result[0]['store_id'];
        }
        return false;
    }

    /**
     * Load store id by request
     *
     * @return bool
     */
    static function loadStoreByCode($code)
    {
        $cache = self::getCacheStoreInstance();
        if ($cache->test()) {
            $result = $cache->load();
            if (!is_array($result)) {
                return false;
            }
            foreach ($result as $info) {
                if ($info['code'] == $code) {
                    return $info['store_id'];
                }
            }
        }
        return false;
    }

    /**
     * Load default currency code by first request
     *
     * @return bool
     */
    static function loadDefaultCurrencyCodeByRequest()
    {
        $cache = self::getCacheStoreInstance();
        if ($cache->test()) {
            $result = $cache->load();
            if (!is_array($result)) {
                return false;
            }
            asort($result);
            return $result[0]['default_currency'];
        }
        return false;
    }

    /**
     * Load default currency code by first request
     *
     * @return bool
     */
    static function loadDefaultSortOrderByRequest()
    {
        $cache = self::getCacheStoreInstance();
        if ($cache->test()) {
            $result = $cache->load();
            if (!is_array($result)) {
                return false;
            }
            asort($result);
            return $result[0]['sort_order'];
        }
        return false;
    }

    /**
     * Load default currency code by first request
     *
     * @return bool
     */
    static function loadDefaultSortDirByRequest()
    {
        $cache = self::getCacheStoreInstance();
        if ($cache->test()) {
            $result = $cache->load();
            if (!is_array($result)) {
                return false;
            }
            asort($result);
            return $result[0]['sort_dir'];
        }
        return false;
    }

    /**
     * Save store id by request
     *
     * @return bool
     */
    static function saveStoreByRequest()
    {
        $cache = self::getCacheStoreInstance();
        $result = array();
        if ($cache->test()) {
            $result = $cache->load();
        }
        $order = $direction = false;
        if ($categoryBlock = Mage::app()->getLayout()->getBlock('product_list')) {
            $order = $categoryBlock->getToolbarBlock()->getCurrentDirection();
            $direction = current(array_keys($categoryBlock->getToolbarBlock()->getModes()));
        }
        $result[] = array(
            'store_id' => Mage::app()->getStore()->getId(),
            'default_currency' => Mage::app()->getStore()->getDefaultCurrencyCode(),
            'store_code' => Mage::app()->getStore()->getCode(),
            'sort_order' => $order,
            'sort_dir'   => $direction
        );
        $cache->save($result, null, array(Potato_FullPageCache_Model_Cache::CACHE_STORE));
        return true;
    }
}