<?php

class Potato_FullPageCache_Helper_Config extends Mage_Core_Helper_Abstract
{
    const GENERAL_MAX_ALLOWED_SIZE   = 'default/po_fpc/general/max_allowed_size';
    const GENERAL_MOBILE_DETECT      = 'default/po_fpc/general/mobile_detect';
    const GENERAL_MOBILE_SEPARATE    = 'default/po_fpc/general/mobile_separate';
    const GENERAL_AUTO_CLEAN         = 'po_fpc/general/auto_clean';
    const GENERAL_CRONJOB            = 'po_fpc/general/cronjob';
    const GENERAL_DEFAULT_CRONJOB    = '1 0 * * *';

    const GENERATION_ENABLED         = 'po_fpc/auto_generation/enabled';
    const GENERATION_THREAD_NUMBER   = 'po_fpc/auto_generation/thread_number';
    const GENERATION_PAGES_PER_CYCLE = 'po_fpc/auto_generation/pages_per_cycle';
    const GENERATION_CUSTOMER_GROUP  = 'po_fpc/auto_generation/customer_group';
    const GENERATION_PROTOCOL        = 'po_fpc/auto_generation/protocol';
    const GENERATION_USERAGENT       = 'po_fpc/auto_generation/useragent';
    const GENERATION_DEBUG           = 'po_fpc/auto_generation/debug';
    const GENERATION_SOURCE          = 'po_fpc/auto_generation/source';
    const GENERATION_SOURCE_PATH     = 'po_fpc/auto_generation/source_path';
    const GENERATION_CRONJOB         = 'po_fpc/auto_generation/cronjob';
    const GENERATION_DEFAULT_CRONJOB = '*/1 * * * *';

    const DEBUG_ENABLED              = 'default/po_fpc/debug/enabled';
    const DEBUG_IP_ADDRESSES         = 'default/po_fpc/debug/ip_addresses';
    const DEBUG_BLOCK_NAME_HINT      = 'default/po_fpc/debug/block_name_hint';

    static function getCatalogRuleCronJob()
    {
        $value = Mage::getStoreConfig(self::GENERAL_CRONJOB);
        if (empty($value)) {
            $value = self::GENERAL_DEFAULT_CRONJOB;
        }
        return $value;
    }

    static function getGenerationCronJob()
    {
        $value = Mage::getStoreConfig(self::GENERATION_CRONJOB);
        if (empty($value)) {
            $value = self::GENERATION_DEFAULT_CRONJOB;
        }
        return $value;
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    static function getCrawlerSource($storeId = null)
    {
        return (int)Mage::getStoreConfig(self::GENERATION_SOURCE, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    static function getCrawlerSourcePath($storeId = null)
    {
        return Mage::getStoreConfig(self::GENERATION_SOURCE_PATH, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return bool
     */
    static function isCrawlerEnabled($storeId = null)
    {
        return (bool)Mage::getStoreConfig(self::GENERATION_ENABLED, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return int
     */
    static function getSortOrder($storeId = null)
    {
        return Potato_FullPageCache_Model_Source_Sort::VIEWS_VALUE;
    }

    /**
     * @return int
     */
    static function getIsMobileDetectEnabled()
    {
        return (int)Potato_FullPageCache_Model_Cache::getCacheConfig()->getNode(self::GENERAL_MOBILE_DETECT);
    }

    /**
     * @return int
     */
    static function canSeparateMobileDevices()
    {
        return (int)Potato_FullPageCache_Model_Cache::getCacheConfig()->getNode(self::GENERAL_MOBILE_SEPARATE);
    }

    /**
     * @param null $store
     *
     * @return array
     */
    static function getCustomerGroups($store = null)
    {
        $value = trim((string)Mage::app()->getStore($store)->getConfig(self::GENERATION_CUSTOMER_GROUP));
        $_result = array();
        if (null !== $value && false !== $value) {
            $_result = explode(',', $value);
        }
        return $_result;
    }

    /**
     * @param null $store
     *
     * @return array
     */
    static function getAutoClean($store = null)
    {
        $value = trim((string)Mage::app()->getStore($store)->getConfig(self::GENERAL_AUTO_CLEAN));
        $_result = array();
        if (null !== $value && false !== $value) {
            $_result = explode(',', $value);
        }
        return $_result;
    }

    /**
     * @param null $store
     *
     * @return array
     */
    static function getProtocols($store = null)
    {
        $value = trim((string)Mage::app()->getStore($store)->getConfig(self::GENERATION_PROTOCOL));
        $_result = array();
        if (null !== $value && false !== $value) {
            $_result = explode(',', $value);
        }
        return $_result;
    }

    /**
     * @param null $store
     *
     * @return bool
     */
    static function canUseCrawlerDebug($store = null)
    {
        return (bool)Mage::app()->getStore($store)->getConfig(self::GENERATION_DEBUG);
    }

    /**
     * @return int
     */
    static function getIsDebugEnabled()
    {
        return (int)Potato_FullPageCache_Model_Cache::getCacheConfig()->getNode(self::DEBUG_ENABLED);
    }

    /**
     * @return int
     */
    static function canShowBlockHint()
    {
        return (int)Potato_FullPageCache_Model_Cache::getCacheConfig()->getNode(self::DEBUG_BLOCK_NAME_HINT);
    }

    /**
     * @return array
     */
    static function getDebugIpAddresses()
    {
        $value = trim((string)Potato_FullPageCache_Model_Cache::getCacheConfig()->getNode(self::DEBUG_IP_ADDRESSES));
        $_result = array();
        if ($value) {
            $_result = explode(',', $value);
        }
        return $_result;
    }

    /**
     * @return int
     */
    static function getMaxAllowedSize()
    {
        return (int)Potato_FullPageCache_Model_Cache::getCacheConfig()->getNode(self::GENERAL_MAX_ALLOWED_SIZE) * 1024 * 1024;
    }

    /**
     * @param null $store
     *
     * @return int
     */
    static function getNumberPagesPerCycle($store = null)
    {
        return max((int)Mage::getStoreConfig(self::GENERATION_PAGES_PER_CYCLE, $store), 1);
    }

    /**
     * @param null $store
     *
     * @return int
     */
    static function getAutoGenerationThreadNumber($store = null)
    {
        return max((int)Mage::getStoreConfig(self::GENERATION_THREAD_NUMBER, $store), 1);
    }

    /**
     * Switch mode (ajax) hidden option
     *
     * @return bool
     */
    static function canUseAjax()
    {
        return true;
    }

    /**
     * Category sorting compatibility hidden option
     *
     * @return bool
     */
    static function includeSorting()
    {
        return false;
    }

    /**
     * @return bool
     */
    static function canDocumentLoadSuspend()
    {
        return false;
    }

    /**
     * @return bool
     */
    static function canUpdateSessionBlocksCacheWithoutAjax()
    {
        return true;
    }

    /**
     * @return bool
     */
    static function useProductReferrerPage()
    {
        return false;
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    static function getUserAgents($store = null)
    {
        $excludes = Mage::getStoreConfig(self::GENERATION_USERAGENT, $store);
        return unserialize($excludes);
    }
}