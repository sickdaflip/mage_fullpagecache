<?php

/**
 * Class Potato_FullPageCache_Model_Cache
 */
class Potato_FullPageCache_Model_Cache
{
    //store id cookie - used in page id and init current store
    const STORE_COOKIE_NAME             = 'fpc_store';

    //current currency - used in page id
    const CURRENCY_COOKIE_NAME          = 'fpc_currency';

    //native currency cookie name - used in page id
    const NATIVE_CURRENCY_COOKIE_NAME   = 'currency';

    //current customer group - used in page id
    const CUSTOMER_GROUP_ID_COOKIE_NAME = 'fpc_group';

    //current sort order
    const SORT_ORDER_COOKIE_NAME        = 'fpc_order';

    //current sort dir
    const SORT_DIR_COOKIE_NAME          = 'fpc_dir';

    //config cache id
    const CONFIG_CACHE_ID               = 'PO_FPC_MAGE_CONFIG';

    //cache config id
    const PO_FPC_CONFIG_CACHE_ID        = 'PO_FPC_SELF_CONFIG';

    //config cache lifetime 3 days
    const CONFIG_CACHE_LIFETIME         = 259200;

    //block cache tag
    const BLOCK_TAG                     = 'ROUTER_DYNAMIC_BLOCK';

    //block cache tag
    const SESSION_BLOCK_TAG             = 'SESSION_DYNAMIC_BLOCK';

    //system cache tag
    const SYSTEM_TAG                    = 'SYSTEM';

    //product cache tag
    const PRODUCT_TAG                   = 'PRODUCT';

    //product cache tag
    const CATEGORY_TAG                  = 'CATEGORY';

    //product cache tag
    const CMS_TAG                       = 'CMS';

    //global blocks index
    const BLOCK_TYPE_GLOBAL             = 'global';

    const CACHE_STORE                   = 'STORE_INFO';

    const ANY_TAGS                      = 'any';

    const MATCH_TAGS                    = 'match';

    //cached self config
    static private $_cacheConfig        = null;

    /**
     * Init page cache
     *
     * @param bool  $canUseStoreDataFlag
     * @param array $frontendOptions
     * @param array $backendOptions
     *
     * @return Potato_FullPageCache_Model_Cache_Page
     */
    static function getPageCache($canUseStoreDataFlag = false, $frontendOptions = array(), $backendOptions = array())
    {
        $pageCache = Mage::registry('po_fpc_page');
        if (!$pageCache) {
            $pageCache = new Potato_FullPageCache_Model_Cache_Page(
                array(
                    'frontend_options' => $frontendOptions,
                    'backend_options' => $backendOptions,
                )
            );
            Mage::register('po_fpc_page', $pageCache, true);
        }
        $pageCache->setCanUseStoreDataFlag($canUseStoreDataFlag);
        return $pageCache;
    }

    /**
     * init simple cache instance
     *
     * @param       $name
     * @param array $frontendOptions
     * @param array $backendOptions
     *
     * @return Potato_FullPageCache_Model_Cache_Default
     */
    static function getOutputCache($name, $frontendOptions = array(), $backendOptions = array())
    {
        $outputCache = new Potato_FullPageCache_Model_Cache_Default(
            array(
                'frontend_options' => $frontendOptions,
                'backend_options' => $backendOptions,
            )
        );
        $outputCache->setId($name);
        return $outputCache;
    }

    /**
     * init and return self config
     *
     * @return Mage_Core_Model_Config
     */
    static function getCacheConfig()
    {
        if (null === self::$_cacheConfig) {
            $cache = self::getOutputCache(self::PO_FPC_CONFIG_CACHE_ID);
            if ($cache->test()) {
                //load from cache
                $config = $cache->load();
                $xml = @simplexml_load_string($config, 'Mage_Core_Model_Config_Element');
                self::$_cacheConfig = new Mage_Core_Model_Config();
                self::$_cacheConfig->setXml($xml);
                return self::$_cacheConfig;
            }
            //collect and save config files po_fpc.xml
            self::$_cacheConfig = Potato_FullPageCache_Helper_Data::loadCacheRulesFromFile();

            //save po_fpc global settings for quick access without mage config load
            self::$_cacheConfig->setNode(Potato_FullPageCache_Helper_Config::GENERAL_MOBILE_DETECT,
                (int)Mage::getConfig()->getNode(Potato_FullPageCache_Helper_Config::GENERAL_MOBILE_DETECT)
            );
            self::$_cacheConfig->setNode(Potato_FullPageCache_Helper_Config::GENERAL_MOBILE_SEPARATE,
                (int)Mage::getConfig()->getNode(Potato_FullPageCache_Helper_Config::GENERAL_MOBILE_SEPARATE)
            );
            self::$_cacheConfig->setNode(Potato_FullPageCache_Helper_Config::GENERAL_MAX_ALLOWED_SIZE,
                (int)Mage::getConfig()->getNode(Potato_FullPageCache_Helper_Config::GENERAL_MAX_ALLOWED_SIZE)
            );
            self::$_cacheConfig->setNode(Potato_FullPageCache_Helper_Config::DEBUG_ENABLED,
                (int)Mage::getConfig()->getNode(Potato_FullPageCache_Helper_Config::DEBUG_ENABLED)
            );
            self::$_cacheConfig->setNode(Potato_FullPageCache_Helper_Config::DEBUG_BLOCK_NAME_HINT,
                (int)Mage::getConfig()->getNode(Potato_FullPageCache_Helper_Config::DEBUG_BLOCK_NAME_HINT)
            );
            self::$_cacheConfig->setNode(Potato_FullPageCache_Helper_Config::DEBUG_IP_ADDRESSES,
                (string)Mage::getConfig()->getNode(Potato_FullPageCache_Helper_Config::DEBUG_IP_ADDRESSES)
            );
            $cache->save(self::$_cacheConfig->getXmlString(), null, array(self::SYSTEM_TAG));
        }
        return self::$_cacheConfig;
    }


    /**
     * get user params for cache id
     *
     * @return array
     */
    static function getIncludeToPageCacheId()
    {
        return (array) self::getCacheConfig()->getNode('include_to_page_cache_id');
    }

    /**
     * init Mage config from cache
     *
     * @return bool
     */
    static function loadMageConfig()
    {
        $xml = self::getSavedMageConfigXml();
        /** @var Mage_Core_Model_Config $config */
        $config = Mage::getConfig();
        if (!$xml || !$xml instanceof Mage_Core_Model_Config_Element) {
            $config
                ->loadModules()
                ->loadDb()
            ;
            return true;
        }
        //init Mage config from cache
        $config->setXml($xml);
        return true;
    }

    /**
     * set store id cookie
     *
     * @param $storeId
     *
     * @return $this
     */
    static function setStoreCookie($storeId)
    {
        Mage::getSingleton('core/cookie')->set(self::STORE_COOKIE_NAME, $storeId, true, '/');
        return true;
    }

    /**
     * set customer group id cookie
     *
     * @param $groupId
     *
     * @return $this
     */
    static function setCustomerGroupCookie($groupId)
    {
        Mage::getSingleton('core/cookie')->set(self::CUSTOMER_GROUP_ID_COOKIE_NAME, $groupId, true, '/');
        return true;
    }

    /**
     * set currency code cookie
     *
     * @param $code
     *
     * @return $this
     */
    static function setCurrencyCookie($code)
    {
        Mage::getSingleton('core/cookie')->set(self::CURRENCY_COOKIE_NAME, $code, true, '/');
        return true;
    }

    /**
     * set sort order cookie
     *
     * @param $order
     *
     * @return $this
     */
    static function setSortOrderCookie($order)
    {
        Mage::getSingleton('core/cookie')->set(self::SORT_ORDER_COOKIE_NAME, $order, true, '/');
        return true;
    }

    /**
     * set sort dir cookie
     *
     * @param $dir
     *
     * @return $this
     */
    static function setSortDirCookie($dir)
    {
        Mage::getSingleton('core/cookie')->set(self::SORT_DIR_COOKIE_NAME, $dir, true, '/');
        return true;
    }

    /**
     * @return bool|SimpleXMLElement
     */
    static function getSavedMageConfigXml()
    {
        //get mage config from cache
        $cache = self::getOutputCache(self::CONFIG_CACHE_ID, array('lifetime' => self::CONFIG_CACHE_LIFETIME));
        if ($cache->test()) {
            $data = $cache->load();
            return @simplexml_load_string($data, 'Mage_Core_Model_Config_Element');
        }
        return false;
    }

    /**
     * save mage config
     *
     * @return bool
     */
    static function saveMageConfigXml()
    {
        $cache = self::getOutputCache(self::CONFIG_CACHE_ID, array('lifetime' => self::CONFIG_CACHE_LIFETIME));
        if ($cache->test()) {
            //already saved
            return true;
        }
        $config = Mage::getConfig();
        if (Mage::app()->useCache('config')) {
            //if config cache is enabled - need config re-init
            $config->reinit();
        }
        //save mage config
        $cache->save($config->getXmlString(), null, array(self::SYSTEM_TAG));
        return true;
    }

    /**
     * @return bool
     */
    static function removeMageConfigXmlCache()
    {
        $cache = self::getOutputCache(self::CONFIG_CACHE_ID, array('lifetime' => self::CONFIG_CACHE_LIFETIME));
        if ($cache->test()) {
            $cache->delete();
            return true;
        }
        return false;
    }

    /**
     * @return $this
     */
    static function clean()
    {
        $cache = self::getOutputCache(null);
        $cache->flush();
        Potato_FullPageCache_Helper_CacheStorage::cleanStorageData();
        return true;
    }

    /**
     * @param array  $tags
     * @param string $mode
     *
     * @return bool
     */
    static function cleanByTags($tags, $mode = self::ANY_TAGS)
    {
        $ids = Potato_FullPageCache_Helper_CacheStorage::getIdsByTags($tags, $mode);
        foreach ($ids as $id) {
            self::remove($id);
        }
        return true;
    }

    static function cleanByPrivateTag($privateTag, $tags=array())
    {
        $ids = Potato_FullPageCache_Helper_CacheStorage::getIdsByPrivateTag($privateTag, $tags);
        foreach ($ids as $id) {
            self::remove($id);
        }
        return true;
    }

    /**
     * clean expired cache
     *
     * @return bool
     */
    static function cleanExpire()
    {
        $ids = Potato_FullPageCache_Helper_CacheStorage::getExpireIds();
        foreach ($ids as $id) {
            self::remove($id);
        }
        return true;
    }

    /**
     * @param $id
     *
     * @return bool
     */
    static function remove($id)
    {
        $cache = self::getOutputCache(null);
        $cache->getFrontend()->remove($id);
        Potato_FullPageCache_Helper_CacheStorage::unregisterCache($id);
        return true;
    }
}