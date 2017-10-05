<?php

/**
 * Used for cache auto generate
 *
 * Class Potato_FullPageCache_Model_Crawler
 */
class Potato_FullPageCache_Model_Crawler extends Mage_Core_Model_Abstract
{
    //debug file name
    const DEBUG_FILENAME = 'po_fpc_crawler.log';

    const SELF_COOKIE_NAME = 'is_crawler';

    const LOCK_TIMEOUT = 1800;
    const LOCK_CACHE_INDEX = 'po_fpc_crawler_lock';

    //Varien_Http_Adapter_Curl
    protected $_curl = null;

    protected function _construct()
    {
        $this->_init('po_fpc/crawler');
    }

    /**
     * @param $storeInfo
     * @param $preparedUrls
     *
     * @return $this
     */
    protected function _executeRequests($storeInfo, $preparedUrls)
    {
        $this->_log('Process store:' . implode(',', $storeInfo));

        /**
         * Prepare crawler options
         */
        $options = array(
            CURLOPT_USERAGENT      => $storeInfo['useragent'],
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 60,
            CURLOPT_FAILONERROR    => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIE         => $storeInfo['cookie']

        );

        //number of threads
        $threads = Potato_FullPageCache_Helper_Config::getAutoGenerationThreadNumber(
            $storeInfo[Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME]
        );
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            //apache will crashed if $threads > 1
            $threads = 1;
        }

        $urls = array();
        foreach ($preparedUrls as $url) {
            //log url
            $this->_log($url);

            $urls[] = $url;
            if (count($urls) == $threads) {
                //get pages via curl
                $this->_getCurl()->multiRequest($urls, $options);
                $urls = array();
            }
        }
        if (count($urls)) {
            $this->_getCurl()->multiRequest($urls, $options);
        }
        return $this;
    }

    /**
     * @return null|Varien_Http_Adapter_Curl
     */
    protected function _getCurl()
    {
        if (null === $this->_curl) {
            $this->_curl = new Varien_Http_Adapter_Curl();
        }
        return $this->_curl;
    }

    /**
     * @param $rewriteRow
     * @param $baseUrl
     * @param $storeId
     *
     * @return string
     * @throws Exception
     */
    protected function _getUrlByRewriteRow($rewriteRow, $baseUrl, $storeId)
    {
        switch ($rewriteRow['entity_type']) {
            case Enterprise_Catalog_Model_Product::URL_REWRITE_ENTITY_TYPE:
                $url = $baseUrl . Mage::helper('enterprise_catalog')->getProductRequestPath(
                    $rewriteRow['request_path'], $storeId, $rewriteRow['category_id']
                );
                break;
            case Enterprise_Catalog_Model_Category::URL_REWRITE_ENTITY_TYPE:
                $url = $baseUrl . Mage::helper('enterprise_catalog')->getCategoryRequestPath(
                    $rewriteRow['request_path'], $storeId
                );
                break;
            default:
                throw new Exception('Unknown entity type ' . $rewriteRow['entity_type']);
                break;
        }
        return $url;
    }

    /**
     * @return Potato_FullPageCache_Model_Crawler
     */
    public function cronProcess()
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Config::isCrawlerEnabled() ||
            self::isLocked()
            || !$this->_initCrawlerQueue()
        ) {
            return $this;
        }

        if (!Potato_FullPageCache_Helper_Data::isMatchingCronSettings(Potato_FullPageCache_Helper_Config::getGenerationCronJob())) {
            return $this;
        }

        $this
            ->_process()
        ;
        return $this;
    }

    protected function _initCrawlerQueue()
    {
        $collection = Mage::getResourceModel('po_fpc/crawler_queue_collection');
        if ($collection->getSize()) {
            return true;
        }

        try {
            $this->_log('Clean expired cache.');
            //remove expire cache
            Potato_FullPageCache_Model_Cache::cleanExpire();
        } catch (Exception $e) {
            Mage::logException($e);
        }

        foreach ($this->_getStoresInfo() as $_storeInfo) {
            //processed store id
            $storeId = $_storeInfo[Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME];

            //store customer group list
            $_storeCustomerGroup = Potato_FullPageCache_Helper_Config::getCustomerGroups($storeId);

            foreach ($_storeCustomerGroup as $customerGroupId) {
                //set cookies
                $storeInfo = $_storeInfo;
                $storeInfo[Potato_FullPageCache_Model_Cache::CUSTOMER_GROUP_ID_COOKIE_NAME] = $customerGroupId;
                $storeInfo['cookie'] .= Potato_FullPageCache_Model_Cache::CUSTOMER_GROUP_ID_COOKIE_NAME
                    . '=' . $customerGroupId . ';path=/;'
                ;
                if (Potato_FullPageCache_Helper_Config::getCrawlerSource($storeId) == Potato_FullPageCache_Model_Source_CrawlerSource::SITEMAP_VALUE) {
                    try {
                        $this->_addToQueue($this->_getSitemapUrls($storeId), $storeInfo);
                    } catch(Exception $e) {
                        Mage::printException($e);
                    }
                    continue;
                }
                try {
                    /**
                     * Add Popular pages first (sorted by viewed)
                     */
                    $this->_addToQueue($this->_getPopularUrls($storeInfo[Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME]), $storeInfo);
                    /**
                     * Add CMS pages
                     */
                    $this->_addToQueue($this->_getCmsPageUrls($storeInfo), $storeInfo);
                    /**
                     * Add Category
                     */
                    $this->_addToQueue($this->_getCategoryPageUrls($storeInfo), $storeInfo);
                } catch(Exception $e) {
                    Mage::printException($e);
                }
            }
        }
        Mage::app()->removeCache(self::LOCK_CACHE_INDEX);
        return false;
    }

    protected function _getSitemapUrls($storeId)
    {
        $urls = array();
        foreach (Mage::helper('po_fpc')->getSitemapUrls($storeId) as $url) {
            $urls[] = (string)$url;
        }
        return $urls;
    }

    protected function _addToQueue($urls, $options)
    {
        foreach ($urls as $url) {
            if ($this->_getResource()->isReadyCached($options, $url)) {
                //skip if already cached
                continue;
            }
            Mage::getModel('po_fpc/crawler_queue')
                ->setUrl($url)
                ->setOptions($options)
                ->save()
            ;
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _process()
    {
        $this->_log('Start Crawler.');
        $collection = Mage::getResourceModel('po_fpc/crawler_queue_collection');
        $collection
            ->setLimit(Potato_FullPageCache_Helper_Config::getNumberPagesPerCycle())
        ;
        $preparedUrls = array();
        foreach ($collection as $item) {
            $hash = md5($item->getOptions());
            if (!array_key_exists($hash, $preparedUrls)) {
                $preparedUrls[$hash] = array(
                    'urls'    => array(),
                    'options' => $item->getOptions()
                );
            }
            $preparedUrls[$hash]['urls'][] = $item->getUrl();
            try {
                $item->delete();
            } catch(Exception $e) {
                Mage::printException($e);
            }
        }

        foreach ($preparedUrls as $item) {
            try {
                $this->_executeRequests(unserialize($item['options']), $item['urls']);
            } catch(Exception $e) {
                Mage::printException($e);
            }
        }

        $this->_log('Complete.');
        Mage::app()->removeCache(self::LOCK_CACHE_INDEX);
        return $this;
    }

    protected function _getPopularUrls($storeId)
    {
        return $this->_getResource()->getPopularUrls($storeId);
    }

    protected function _getCategoryPageUrls($storeInfo)
    {
        /**
         * Url rewrites (catalog and product urls
         */
        $storeId = $storeInfo[Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME];
        $result = array();
        foreach ($this->_getResource()->getRequestPaths($storeId) as $rewriteRow) {
            if (@class_exists('Enterprise_UrlRewrite_Model_Resource_Url_Rewrite', false)) {
                //for Enterprise version
                $url = $this->_getUrlByRewriteRow($rewriteRow, $storeInfo['base_url'], $storeId);
            } else {
                $url = $storeInfo['base_url'] . $this->_encodeUrlPath($rewriteRow['request_path']);
            }
            array_push($result, $url);
        }
        return $result;
    }

    protected function _getCmsPageUrls($storeInfo)
    {
        /**
         * Cms pages
         */
        $storeId = $storeInfo[Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME];
        $result = array();
        foreach ($this->_getResource()->getCmsUrls($storeId) as $cms) {
            $url = $storeInfo['base_url'] . $this->_encodeUrlPath($cms['identifier']);
            if ($cms['identifier'] == 'home') {
                $url = $storeInfo['base_url'];
            }
            array_push($result, $url);
        }
        return $result;
    }

    /**
     * Get stores information
     *
     * @return array
     */
    protected function _getStoresInfo()
    {
        $result = array();
        foreach (Mage::app()->getStores() as $store) {
            if (!$store->getIsActive() || !Potato_FullPageCache_Helper_Config::isCrawlerEnabled($store)) {
                continue;
            }
            $_store = Mage::app()->getStore($store);
            foreach (Potato_FullPageCache_Helper_Config::getProtocols($store) as $_protocol) {
                if ($_protocol == Potato_FullPageCache_Model_Source_Protocol::HTTPS_VALUE &&
                    $store->isFrontUrlSecure()
                ) {
                    $result = array_merge($result, $this->_getPreparedStoreData($_store, true));
                    continue;
                }
                $result = array_merge($result, $this->_getPreparedStoreData($_store));
            }
        }
        return $result;
    }

    /**
     * Prepare store information
     *
     * @param      $store
     * @param bool $secure
     *
     * @return array
     */
    protected function _getPreparedStoreData($store, $secure=false)
    {
        $baseUrl	= $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB, $secure);
        $baseUrl   .= $store->getConfig(Mage_Core_Model_Store::XML_PATH_USE_REWRITES) ? '' : 'index.php/';
        $cookie     = Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME . '=' . $store->getId()
            . ';' . self::SELF_COOKIE_NAME . '=true;';
        $currencies = $store->getAvailableCurrencyCodes(true);
        $result = array();
        if ($store->getConfig(Mage_Core_Model_Store::XML_PATH_STORE_IN_URL)) {
            $baseUrl = trim($baseUrl, '/');
            $baseUrl .= '/' . $store->getCode() . '/';
        }
        foreach ($currencies as $currencyCode) {
            foreach (Potato_FullPageCache_Helper_Config::getUserAgents($store) as $_useragent) {
                $result[]= array(
                    Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME    => $store->getId(),
                    'useragent'                                            => $_useragent['useragent'],
                    'base_url'                                             => $baseUrl,
                    'is_secure'                                            => Potato_FullPageCache_Helper_Data::isSecureUrl($baseUrl) ? true : false,
                    'is_mobile'                                            => false,
                    'website_id'                                           => $store->getWebsiteId(),
                    Potato_FullPageCache_Model_Cache::CURRENCY_COOKIE_NAME => $currencyCode,
                    'cookie'                                               => $cookie
                        . Potato_FullPageCache_Model_Cache::CURRENCY_COOKIE_NAME  . '=' . $currencyCode . ';'
                        . 'store=' . $store->getCode() . ';'
                );
            }
        }
        return $result;
    }

    /**
     * @param $path
     *
     * @return string
     */
    protected function _encodeUrlPath($path)
    {
        return implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    /**
     * @param $message
     *
     * @return $this
     */
    protected function _log($message)
    {
        if (Potato_FullPageCache_Helper_Config::canUseCrawlerDebug()) {
            Mage::log($message, 1, self::DEBUG_FILENAME, true);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public static function isLocked()
    {
        if (Mage::app()->loadCache(self::LOCK_CACHE_INDEX)) {
            return true;
        }
        Mage::app()->saveCache(time(), self::LOCK_CACHE_INDEX, array(), self::LOCK_TIMEOUT);
        return false;
    }
}