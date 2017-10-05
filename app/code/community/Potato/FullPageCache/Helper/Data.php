<?php

/**
 * Common helpful methods
 *
 * Class Potato_FullPageCache_Helper_Data
 */
class Potato_FullPageCache_Helper_Data extends Mage_Core_Helper_Abstract
{
    const UPDATER_REQUEST_PAGE_ID_KEY = 'po_fpc_page_id';
    /**
     * Required for correct layout generate blocks
     *
     * @return bool
     */
    static function initRequest()
    {
        $request = Mage::app()->getRequest();
        if (self::canRewriteNativeRequest()) {
            $request = new Potato_FullPageCache_Model_Request();
        }
        $request->setPathInfo();
        $request->rewritePathInfo(null);
        if (self::canRewriteNativeRequest()) {
            Mage::app()->setRequest($request);
        }
        $action = new Mage_Core_Controller_Front_Action($request, Mage::app()->getResponse());
        Mage::app()->getFrontController()->setAction($action);
        return true;
    }

    /**
     * Check can we replace Mage::app()->getRequest()
     *
     * @return mixed
     */
    static function canRewriteNativeRequest()
    {
        $magentoVersion = Mage::getVersion();
        return version_compare($magentoVersion, '1.7', '>=');
    }

    /**
     * Check is debug mode enabled
     *
     * @return bool
     */
    static function isDebugModeEnabled()
    {
        $debugIpAddresses = Potato_FullPageCache_Helper_Config::getDebugIpAddresses();
        $clientIp = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $clientIp = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $clientIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        if (Potato_FullPageCache_Helper_Config::getIsDebugEnabled() &&
            (empty($debugIpAddresses) || in_array($clientIp, $debugIpAddresses))
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get request hash
     *
     * @return string
     */
    static function getRequestHash()
    {
        return md5(self::getRequestedUrl());
    }

    /**
     * @return string
     */
    static function getRequestedUrl($removeIndexFlag=true)
    {
        $uri = 'http://';
        if (isset($_SERVER['HTTPS']) && 'on' === $_SERVER['HTTPS']) {
            $uri = 'https://';
        }
        if (isset($_SERVER['HTTP_HOST'])) {
            $uri .= $_SERVER['HTTP_HOST'];
        } else if (isset($_SERVER['SERVER_NAME'])) {
            $uri .= $_SERVER['SERVER_NAME'];
        }
        if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $uri .= $_SERVER['HTTP_X_REWRITE_URL'];
        } else if (isset($_SERVER['REQUEST_URI'])) {
            $uri .= $_SERVER['REQUEST_URI'];
        } else if (!empty($_SERVER['IIS_WasUrlRewritten']) && !empty($_SERVER['UNENCODED_URL'])) {
            $uri .= $_SERVER['UNENCODED_URL'];
        } else if (isset($_SERVER['ORIG_PATH_INFO'])) {
            $uri .= $_SERVER['ORIG_PATH_INFO'];
        }
        if (!empty($_SERVER['QUERY_STRING'])) {
            $uri .= $_SERVER['QUERY_STRING'];
            //remove google tracking param
            $uri = preg_replace('/[\?|\&]+gclid=[^&]+()/','$1', $uri);
        }
        if ($removeIndexFlag) {
            //remove index.php from request for exclude duplicate like example.com and example.com/index.php
            $uri = str_replace('/index.php', '', $uri);
        }
        return $uri;
    }

    /**
     * Get defined cache tags for product|category|cms
     *
     * @return array
     */
    static function getCacheTags()
    {
        if (Mage::registry('current_product')) {
            return array(Potato_FullPageCache_Model_Cache::PRODUCT_TAG);
        }
        if (Mage::registry('current_category')) {
            return array(Potato_FullPageCache_Model_Cache::CATEGORY_TAG);
        }
        if (Mage::registry('current_cms')) {
            return array(Potato_FullPageCache_Model_Cache::CMS_TAG);
        }
        return array();
    }

    /**
     * Unique page tag e.g. for product page
     *
     * @return string
     */
    static function getPrivateTag()
    {
        if (Mage::registry('current_product')) {
            return Potato_FullPageCache_Model_Cache_Page::PRODUCT_ID_TAG_PREFIX . Mage::registry('current_product')->getId();
        }
        if (Mage::registry('current_category')) {
            return Potato_FullPageCache_Model_Cache_Page::CATEGORY_ID_TAG_PREFIX . Mage::registry('current_category')->getId();
        }
        if (Mage::registry('current_cms')) {
            return Potato_FullPageCache_Model_Cache_Page::CMS_ID_TAG_PREFIX . Mage::registry('current_cms')->getId();
        }
        return '';
    }

    /**
     * Can be request cached
     *
     * @return bool
     */
    static function canCache()
    {
        foreach (Mage::app()->getResponse()->getHeaders() as $header) {
            if ('Status' == $header['name'] && $header['value'] == '404 File not found') {
                return false;
            }
        }
        $httpCode = Mage::app()->getResponse()->getHttpResponseCode();
        if ($httpCode != '200' && $httpCode != '301' && $httpCode != '302') {
            return false;
        }
        if (!Potato_FullPageCache_Model_Processor::getIsAllowed() ||
            Mage::app()->getStore()->isAdmin() ||
            !Potato_FullPageCache_Model_Cache::getPageCache(true)->getBlockCache()->getIsAllowedAction()
        ) {
            return false;
        }
        if (trim(Mage::app()->getStore()->getCurrentCurrencyCode()) == '' ||
            trim(Mage::app()->getStore()->getId()) == ''
        ) {
            return false;
        }
        $popularity = Mage::getModel('po_fpc/popularity')->loadByRequestUrl(
            Potato_FullPageCache_Helper_Data::getRequestedUrl(),
            Mage::app()->getStore()->getId()
        );
        if ($popularity->getId() && !$popularity->getAllowToCache()) {
            return false;
        }
        if (self::isDebugModeEnabled() &&
            Potato_FullPageCache_Helper_Config::canShowBlockHint()
        ) {
            return false;
        }
        return true;
    }

    /**
     * Is updater request
     *
     * @return bool
     */
    static function isUpdater()
    {
        return Mage::app()->getRequest()->getParam(self::UPDATER_REQUEST_PAGE_ID_KEY, null) ? true : false;
    }

    /**
     * Emulate request for needed module/controller/action route
     * needed for correct block updates
     *
     * @param array $requestData
     *
     * @return array
     */
    static function emulateRequest($requestData)
    {
        $request = Mage::app()->getRequest();
        $oldRequest = array(
            'module_name'     => $request->getModuleName(),
            'controller_name' => $request->getControllerName(),
            'action_name'     => $request->getActionName(),
            'params'          => $request->getParams(),
            'request_uri'     => $request->getRequestUri(),
            'path_info'       => $request->getPathInfo(),
            'alias'           => $request->getAlias(Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS)
        );
        if (array_key_exists('module_name', $requestData)) {
            $request->setModuleName($requestData['module_name']);
        }
        if (array_key_exists('controller_name', $requestData)) {
            $request->setControllerName($requestData['controller_name']);
        }
        if (array_key_exists('action_name', $requestData)) {
            $request->setActionName($requestData['action_name']);
        }
        if (array_key_exists('params', $requestData)) {
            $request->setParams($requestData['params']);
        }
        if (array_key_exists('request_uri', $requestData)) {
            $request->setRequestUri($request->getBaseUrl() . $requestData['request_uri']);
        }
        if (array_key_exists('path_info', $requestData)) {
            $request->setPathInfo($requestData['path_info']);
        }
        if (array_key_exists('alias', $requestData)) {
            $request->setAlias(Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS, $requestData['alias']);
        }
        return $oldRequest;
    }

    /**
     * Event controller_action_predispatch
     * Save visitor info
     */
    static function saveVisitorData()
    {
        $observer = new Varien_Event_Observer();
        $event = new Varien_Event(
            array(
                'controller_action' => Mage::app()->getFrontController()->getAction()
            )
        );
        $observer->setData(
            array(
                'event' => $event
            )
        );
        $visitor = Mage::getSingleton('log/visitor')->initByRequest($observer);
        $visitor->saveByRequest($observer);
        return true;
    }

    /**
     * @return bool
     */
    static function getIsWinOs()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            return false;
        }
        return true;
    }

    /**
     * @param $url
     *
     * @return bool
     */
    static function isSecureUrl($url)
    {
        return strpos($url, 'https:') !== FALSE;
    }

    /**
     * @param int|string $size
     *
     * @return string
     */
    public function formatSize($size)
    {
        if ($size >= 1073741824) {
            $size = $this->__('%s GB', number_format($size / 1073741824, 2));
        } elseif ($size >= 1048576) {
            $size = $this->__('%s MB', number_format($size / 1048576, 2));
        } elseif ($size >= 1024) {
            $size = $this->__('%s KB', number_format($size / 1024, 2));
        } elseif ($size > 1) {
            $size = $this->__('%s bytes', $size);
        } elseif ($size == 1) {
            $size = $this->__('%s byte', $size);
        } else {
            $size = $this->__('0 bytes');
        }
        return $size;
    }

    /**
     * Get device info (device type, user agent)
     *
     * @param string $useragent
     * @return array
     */
    static function getDeviceInfo($useragent='')
    {
        $device = Potato_FullPageCache_Model_Cache_Page::DESKTOP_TAG;
        $deviceType = false;
        if (!Potato_FullPageCache_Helper_Config::getIsMobileDetectEnabled() ||
            (!$useragent && !array_key_exists('HTTP_USER_AGENT', $_SERVER))
        ) {
            return array ($device, $deviceType);
        }
        if (!$useragent) {
            $useragent = $_SERVER['HTTP_USER_AGENT'];
        }
        $detect = new Potato_Fpc_Mobile_Detect;
        if ($detect->isMobile($useragent) || $detect->isTablet($useragent)) {
            $device = Potato_FullPageCache_Model_Cache_Page::MOBILE_TAG;
            if (Potato_FullPageCache_Helper_Config::canSeparateMobileDevices()) {
                $deviceType = Potato_FullPageCache_Model_Cache_Page::PHONE_TAG;
                if ($detect->isTablet($useragent)) {
                    $deviceType = Potato_FullPageCache_Model_Cache_Page::TABLET_TAG;
                }
            }
        }
        return array ($device, $deviceType);
    }

    /**
     * Update Page popularity and cache/miss statistics
     *
     * @param $storeId
     * @param bool $cached
     * @return bool
     */
    static function updateStatistics($storeId, $cached = false)
    {
        if (self::isCrawler()) {
            return false;
        }
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
        if (!class_exists('Zend_Db_Expr')) {
            include_once "Zend/Db/Expr.php";
        }
        //calculate popularity
        $date = gmdate('Y-m-d H:i:s');
        if (empty($_GET)) {
            $popularityTable = Mage::getSingleton('core/resource')->getTableName('po_fpc_popularity');

            //try update table
            $result = $write->update(
                $popularityTable,
                array('views' => new Zend_Db_Expr('views + 1'), 'updated_at' => $date),
                array(
                    'request_url = ?' => self::getRequestedUrl(),
                    'store_id = ?'    => $storeId
                )
            );
            if (!$result) {
                //new entry
                $write->insert(
                    $popularityTable,
                    array(
                        'views' => 1,
                        'request_url' => self::getRequestedUrl(),
                        'url' => self::getRequestedUrl(false),
                        'store_id' => $storeId,
                        'updated_at' => $date
                    )
                );
            }
        }

        //calculate statistics (cache miss)
        $statisticsTable = Mage::getSingleton('core/resource')->getTableName('po_fpc_statistics');
        $binds = array(
            'miss' => new Zend_Db_Expr('miss + 1')
        );
        $miss = 1;
        $hit = 0;
        if ($cached) {
            $binds = array(
                'cached' => new Zend_Db_Expr('cached + 1')
            );
            $miss = 0;
            $hit = 1;
        }

        $date = gmdate('Y-m-d H:00:00');
        //try update table
        $result = $write->update(
            $statisticsTable,
            $binds,
            array(
                'created_at = ?' => $date
            )
        );
        if (!$result) {
            //new entry
            $write->insert(
                $statisticsTable,
                array(
                    'created_at' => $date,
                    'miss'       => $miss,
                    'cached'     => $hit
                )
            );
        }
        return true;
    }

    /**
     * Load cache xml rules from file
     *
     * @return Mage_Core_Model_Config
     */
    static function loadCacheRulesFromFile()
    {
        //collect and save config files po_fpc.xml
        $cacheConfig = Mage::getConfig()->loadModulesConfiguration('po_fpc.xml');

        //load custom.xml
        $customConfig = Mage::getConfig()->getModuleDir('etc', 'Potato_FullPageCache') . DS . 'custom.xml';
        $mergeModel = new Mage_Core_Model_Config_Base();
        if ($mergeModel->loadFile($customConfig)) {
            $cacheConfig->extend($mergeModel, true);
        }
        return $cacheConfig;
    }

    /**
     * Check if is it crawler
     *
     * @return bool
     */
    static function isCrawler()
    {
        return isset($_COOKIE[Potato_FullPageCache_Model_Crawler::SELF_COOKIE_NAME]);
    }

    static function canCleanCacheByEvent($event)
    {
        if (in_array((string)$event, Potato_FullPageCache_Helper_Config::getAutoClean())) {
            return true;
        }
        return false;
    }

    static function getReferrerPage()
    {
        return @$_SERVER['HTTP_REFERER'];
    }

    static function checkReferrerDomain()
    {
        if (strpos(self::getReferrerPage(), @$_SERVER['SERVER_NAME']) !== False) {
            return true;
        }
        return false;
    }

    /**
     * @param $storeId
     * @return array|SimpleXMLElement[]
     */
    public function getSitemapUrls($storeId)
    {
        $sitemapPath = Potato_FullPageCache_Helper_Config::getCrawlerSourcePath($storeId);
        if (!$xmlContent = $this->_getFileContent($sitemapPath)) {
            return array();
        }
        $xmlContent = str_replace('xmlns=', 'xml=', $xmlContent);
        $xml = @simplexml_load_string($xmlContent);
        if (!$xml) {
            return array();
        }
        return $xml->xpath('/urlset/url/loc/text()');
    }

    /**
     * @param $sitemapPath
     * @return mixed|string
     * @throws Exception
     */
    protected function _getFileContent($sitemapPath)
    {
        if(filter_var($sitemapPath, FILTER_VALIDATE_URL)){
            return $this->_getContentFromUrl($sitemapPath);
        }
        if (!file_exists($sitemapPath)) {
            throw new Exception($sitemapPath . ' not exists');
        }
        return file_get_contents($sitemapPath);
    }

    /**
     * @param $url
     * @return mixed
     */
    protected function _getContentFromUrl($url)
    {
        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($curl_handle);
        curl_close($curl_handle);
        return $result;
    }

    /**
     * @param string $settings
     *
     * @return bool
     */
    static function isMatchingCronSettings($settings)
    {
        $e = preg_split('#\s+#', $settings, null, PREG_SPLIT_NO_EMPTY);
        if (sizeof($e) < 5 || sizeof($e) > 6) {
            return false;
        }
        /** @var Mage_Core_Model_Date $coreDateSingleton */
        $coreDateSingleton = Mage::getSingleton('core/date');
        $d = getdate($coreDateSingleton->timestamp(time()));
        /** @var Mage_Cron_Model_Schedule $cronScheduleModel */
        $cronScheduleModel = Mage::getModel('cron/schedule');
        $match = $cronScheduleModel->matchCronExpression($e[0], $d['minutes'])
            && $cronScheduleModel->matchCronExpression($e[1], $d['hours'])
            && $cronScheduleModel->matchCronExpression($e[2], $d['mday'])
            && $cronScheduleModel->matchCronExpression($e[3], $d['mon'])
            && $cronScheduleModel->matchCronExpression($e[4], $d['wday']);
        return $match;
    }
}