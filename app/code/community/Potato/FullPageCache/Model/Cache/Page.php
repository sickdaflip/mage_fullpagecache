<?php

/**
 * Page cache instance
 *
 * Class Potato_FullPageCache_Model_Cache_Page
 */
class Potato_FullPageCache_Model_Cache_Page extends Potato_FullPageCache_Model_Cache_Default
{
    const DESKTOP_TAG                  = 'DESKTOP';
    const MOBILE_TAG                   = 'MOBILE';
    const PHONE_TAG                    = 'PHONE';
    const TABLET_TAG                   = 'TABLET';
    const HTTP_TAG                     = 'HTTP';
    const HTTPS_TAG                    = 'HTTPS';
    const SESSION_NAMESPACE            = 'frontend';
    const STORE_ID_TAG_PREFIX          = 'STORE_ID_';
    const CUSTOMER_GROUP_ID_TAG_PREFIX = 'CUSTOMER_GROUP_ID_';
    const PRODUCT_ID_TAG_PREFIX        = 'PRODUCT_ID_';
    const CATEGORY_ID_TAG_PREFIX       = 'CATEGORY_ID_';
    const CMS_ID_TAG_PREFIX            = 'CMS_ID_';
    const SORT_ORDER_TAG_PREFIX        = 'SORT_ORDER_';
    const SORT_DIR_TAG_PREFIX          = 'SORT_DIR_';
    const CLIENT_UID                   = 'fpc_client_uid';

    //new block content for replace
    protected $_contentForReplace = '';

    protected $_content = '';

    //page layout xml
    protected $_layout  = false;

    //layout update
    protected $_layoutUpdate  = array();

    //page request string
    protected $_request = array();

    //init layout flag
    protected $_isLayoutInitCompleteFlag = false;

    //page headers array
    protected $_headers = false;

    //block cache
    protected $_blockCache = null;

    protected $_canUseStoreDataFlag = false;

    protected $_clientUid = null;

    protected $_defaultStoreId = null;

    protected $_defaultCurrency = null;

    protected $_platformConfigInitFlag = false;

    protected $_needUpdateScript = false;

    protected $_referrerIncludedFlag = false;

    protected $_scripts = array();

    /**
     * @return null|Potato_FullPageCache_Model_Cache_Page_Block
     */
    public function getBlockCache()
    {
        if (null === $this->_blockCache) {
            $this->_blockCache = new Potato_FullPageCache_Model_Cache_Page_Block(
                $this, Potato_FullPageCache_Model_Cache::getCacheConfig()
            );
        }
        return $this->_blockCache;
    }

    /**
     * generate cache id
     *
     * @return null|string md5(Potato_FullPageCache_Helper_Data::getRequestHash()
     * + store id + currency + customer group + optional(HTTP_USER_AGENT + user params) )
     */
    public function getId()
    {
        if (null === $this->_id) {
            $uri = Potato_FullPageCache_Helper_Data::getRequestHash();

            //separate cache id by store cookie
            $storeId = $this->_defaultStoreId = $this->getCurrentStoreId();

            //separate cache id by currency cookie
            $currencyCode = $this->getCurrentCurrencyCode();

            //separate cache id by customer group cookie
            $groupId = $this->getCurrentCustomerGroup();

            if (!$storeId || !$currencyCode) {
                return null;
            }
            $this
                ->addTag(self::STORE_ID_TAG_PREFIX . $storeId)
                ->addTag(self::CUSTOMER_GROUP_ID_TAG_PREFIX . $groupId)
                ->addTag($currencyCode)
            ;
            $uri .= $storeId . $groupId . $currencyCode;

            //separate cache id by devices
            list($device, $deviceType) = Potato_FullPageCache_Helper_Data::getDeviceInfo();

            $this
                ->addTag($device)
                ->addTag(
                    Potato_FullPageCache_Helper_Data::isSecureUrl(
                        Potato_FullPageCache_Helper_Data::getRequestedUrl()
                    ) ? self::HTTPS_TAG : self::HTTP_TAG
                )
            ;
            $uri .= $device;
            if ($deviceType) {
                $uri .= $deviceType;
                $this->addTag($deviceType);
            }

            /**
             * Category sorting compatibility
             */
            if (Potato_FullPageCache_Helper_Config::includeSorting()) {
                if (isset($_COOKIE[Potato_FullPageCache_Model_Cache::SORT_ORDER_COOKIE_NAME])) {
                    $uri .= $_COOKIE[Potato_FullPageCache_Model_Cache::SORT_ORDER_COOKIE_NAME];
                    $this
                        ->addTag(self::SORT_ORDER_TAG_PREFIX . $_COOKIE[Potato_FullPageCache_Model_Cache::SORT_ORDER_COOKIE_NAME])
                    ;
                } elseif($order = Potato_FullPageCache_Helper_CacheStore::loadDefaultSortOrderByRequest()) {
                    $uri .= $order;
                    $this
                        ->addTag(self::SORT_ORDER_TAG_PREFIX . $order)
                    ;
                }
                if (isset($_COOKIE[Potato_FullPageCache_Model_Cache::SORT_DIR_COOKIE_NAME])) {
                    $uri .= $_COOKIE[Potato_FullPageCache_Model_Cache::SORT_DIR_COOKIE_NAME];
                    $this
                        ->addTag(self::SORT_DIR_TAG_PREFIX . $_COOKIE[Potato_FullPageCache_Model_Cache::SORT_DIR_COOKIE_NAME])
                    ;
                } elseif($dir = Potato_FullPageCache_Helper_CacheStore::loadDefaultSortDirByRequest()) {
                    $uri .= $dir;
                    $this
                        ->addTag(self::SORT_ORDER_TAG_PREFIX . $dir)
                    ;
                }
            }
            /**
             * end
             */

            /**
             * Add custom params to id
             */
            $userParams = Potato_FullPageCache_Model_Cache::getIncludeToPageCacheId();
            if (!empty($userParams)) {
                foreach ($userParams as $param) {
                    try {
                        if (trim($param)) {
                            $_result = @call_user_func(trim($param));
                            $this->addTag($_result);
                            $uri .= $_result;
                        }
                    } catch (Exception $e) {
                        Mage::logException($e);
                    }
                }
            }
            $this->_id = md5($uri);
        }
        if (Potato_FullPageCache_Helper_Config::useProductReferrerPage() &&
            Potato_FullPageCache_Helper_Data::checkReferrerDomain() &&
            $this->_isProductPage()
        ) {
            return $this->_includeReferrerPage($this->_id);
        }
        return parent::getId();
    }

    /**
     * Protection from wrong session init if store loaded before page cache executed in Mage_Core_Model_App
     *
     * @param $value
     *
     * @return $this
     */
    public function setCanUseStoreDataFlag($value)
    {
        $this->_canUseStoreDataFlag = $value;
        return $this;
    }

    /**
     * Get store id by request
     *
     * @return bool
     */
    public function getCurrentStoreId()
    {
        if ($this->_canUseStoreDataFlag) {
            return Mage::app()->getStore()->getId();
        }
        if (isset($_COOKIE[Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME])) {
            return $_COOKIE[Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME];
        }
        $store = Potato_FullPageCache_Helper_CacheStore::loadStoreByRequest();
        if (!$store) {
            return $this->_defaultStoreId;
        }
        return $store;
    }

    /**
     * Get current currency code
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        if ($this->_canUseStoreDataFlag) {
            return Mage::app()->getStore()->getCurrentCurrencyCode();
        }
        if (isset($_COOKIE[Potato_FullPageCache_Model_Cache::NATIVE_CURRENCY_COOKIE_NAME])) {
            return $_COOKIE[Potato_FullPageCache_Model_Cache::NATIVE_CURRENCY_COOKIE_NAME];
        }
        if (isset($_COOKIE[Potato_FullPageCache_Model_Cache::CURRENCY_COOKIE_NAME])) {
            return $_COOKIE[Potato_FullPageCache_Model_Cache::CURRENCY_COOKIE_NAME];
        }
        if ($this->_defaultCurrency) {
            return $this->_defaultCurrency;
        }
        return Potato_FullPageCache_Helper_CacheStore::loadDefaultCurrencyCodeByRequest();
    }

    /**
     * Get current customer group
     *
     * @return int
     */
    public function getCurrentCustomerGroup()
    {
        if ($this->_canUseStoreDataFlag) {
            return Mage::getSingleton('customer/session')->getCustomerGroupId();
        }
        $group = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;
        if (isset($_COOKIE[Potato_FullPageCache_Model_Cache::CUSTOMER_GROUP_ID_COOKIE_NAME])) {
            $group = $_COOKIE[Potato_FullPageCache_Model_Cache::CUSTOMER_GROUP_ID_COOKIE_NAME];
        }
        return $group;
    }

    /**
     * @param string $content
     * @param null   $id
     * @param array  $tags
     * @param bool   $lifetime
     * @param string $privateTag
     *
     * @return $this|bool
     */
    public function save($content, $id = null, $tags = array(), $lifetime = false, $privateTag = '')
    {
        if (!$this->getCurrentCurrencyCode()) {
            return $this;
        }

        //get request
        $request = Mage::app()->getRequest();

        //remove block contents
        $content = $this->_removeBlocksContent($content);

        //gZip content
        $content = $this->_gzcompress($content);
        $headers = array(
            array(
                'name' => 'Cache-Control',
                'value' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
            ),
            array(
                'name' => 'Pragma',
                'value' => 'no-cache',
            ),
        );

        //prepare data
        $data = array(
            'content'  => $content,
            'headers'  => array_merge($headers, Mage::app()->getResponse()->getHeaders()),
            'layout'   => $this->_gzcompress(Mage::app()->getLayout()->getXmlString()),
            'update'   => Mage::app()->getLayout()->getUpdate()->getHandles(),
            'referrer' => Potato_FullPageCache_Helper_Data::getReferrerPage(),
            'default_store_id' => $this->_defaultStoreId,
            'default_currency' => Potato_FullPageCache_Helper_CacheStore::loadDefaultCurrencyCodeByRequest(),
            'request'  => array(
                'module_name'     => $request->getModuleName(),
                'controller_name' => $request->getControllerName(),
                'action_name'     => $request->getActionName(),
                'params'          => $request->getParams(),
                'request_uri'     => $request->getRequestUri(),
                'path_info'       => $request->getPathInfo(),
                'alias'           => $request->getAlias(Mage_Core_Model_Url_Rewrite::REWRITE_REQUEST_PATH_ALIAS)
            )
        );
        //get action cache lifetime
        $actionConfig = $this->getBlockCache()->getActionConfig();
        if (array_key_exists('lifetime', $actionConfig)) {
            $lifetime = (int)$actionConfig['lifetime'];
        }
        if (isset($actionConfig['tags'])) {
            $tags = array_merge($tags, @explode(',', $actionConfig['tags']));
        }
        $this->_tags = array_merge($tags, $this->_tags);
        $data['tags'] =  $this->_tags;
        //return save result
        $_result = parent::save($data, $this->getId(), $this->_tags, $lifetime, $privateTag);
        return $_result;
    }

    /**
     * @return Varien_Object|Mage_Core_Controller_Request_Http
     */
    public function getRequest()
    {
        if ($this->_canUseStoreDataFlag) {
            return Mage::app()->getRequest();
        }
        return new Varien_Object($this->_request);
    }

    /**
     * Init magento layout
     *
     * @return $this
     */
    protected function _loadLayout()
    {
        if ($this->_layout) {
            Mage::getSingleton('core/layout')->setXml(@simplexml_load_string($this->_layout, 'Mage_Core_Model_Layout_Element'));
            if (empty($this->_layoutUpdate)) {
                return $this;
            }
            $update = Mage::app()->getLayout()->getUpdate();
            foreach ($this->_layoutUpdate as $_update) {
                $update->addHandle($_update);
            }
        }
        return $this;
    }

    /**
     * Update block html from cache
     *
     * @param $cachedBlock
     * @param $index
     *
     * @return mixed
     */
    protected function _updateBlockFromCache($cachedBlock, $index)
    {
        //get block processor
        $blockProcessor = $this->getBlockCache()->getBlockCacheProcessor($cachedBlock['name_in_layout']);
        $cachedHtml = $cachedBlock['html'];
        if ($blockProcessor) {
            //get caches html from processor
            $cachedHtml = $blockProcessor->getPreparedHtmlFromCache($cachedHtml);
        }
        $this->_updateCachedHtml($cachedHtml, $index);
        return $this;
    }

    /**
     * Load and render cached page
     *
     * @param bool $load
     * @return bool|mixed|string
     */
    public function extractContent($load=true)
    {
        if ($load) {
            $this->load();
        }

        //init response headers
        if ($this->_headers) {
            foreach ($this->_headers as $header) {
                Mage::app()->getResponse()->setHeader($header['name'], $header['value'], true);
            }
            Mage::app()->getResponse()->setHeader('X-Cache', 'HIT', true);
        }
        if (Potato_FullPageCache_Helper_Config::canUseAjax()) {
            //update dynamic blocks from cache
            $this->_updateBlocksFromCache();

            foreach ($this->_scripts as $js) {
                $this->_content = preg_replace("/<\/body>/", $js . '</body>', $this->_content);
            }
            if ($this->_needUpdateScript) {
                $this->_content = preg_replace("/<\/body>/", $this->_getUpdaterScript() . '</body>', $this->_content);
            }
            return $this->_content;
        }

        if (!$this->extractBlocks()) {
            return false;
        }
        //debug on local ~320ms
        $this->_content = preg_replace('/PO_FPC_FORM_KEY/', Mage::getSingleton('core/session')->getFormKey(), $this->_content);
        $uenc = base64_encode($this->_request['request_uri']);
        $this->_content = preg_replace('/uenc\/(.*)/', 'uenc/' . $uenc . '/', $this->_content);


        //Notice: Trying to get property of non-object
        Mage::app()->addEventArea(Mage_Core_Model_App_Area::AREA_FRONTEND);
        return $this->_content;
    }

    /**
     * Update from cache
     *
     * @return $this
     */
    protected function _updateBlocksFromCache()
    {
        //get blocks from config
        $actionBlocks = $this->getBlockCache()->getActionBlocks();
        foreach ($actionBlocks as $index => $blockData) {
            if (!$this->_getIsBlockOnPage($index)) {
                continue;
            }
            //get block processor
            $blockProcessor = $this->getBlockCache()->getBlockCacheProcessor($index);
            $actionCachedBlock = $this->getBlockCache()->getActionBlockCache($index);
            if ($actionCachedBlock && !$blockProcessor->getIsIgnoreCache()) {
                $this->_updateBlockFromCache($actionCachedBlock, $index);
                continue;
            }
            $this->_needUpdateScript = true;
        }

        //get blocks from config
        $sessionBlocks = $this->getBlockCache()->getSessionBlocks();
        foreach ($sessionBlocks as $index => $blockData) {
            if (!$this->_getIsBlockOnPage($index)) {
                continue;
            }
            $blockProcessor = $this->getBlockCache()->getBlockCacheProcessor($index);
            $sessionBlockCache = $blockProcessor->load($index);
            if ($sessionBlockCache && !$blockProcessor->getIsIgnoreCache()) {
                $this->_updateBlockFromCache($sessionBlockCache, $index);
                continue;
            }
            $this->_needUpdateScript = true;
        }
        return $this;
    }

    /**
     * Ajax updater script
     *
     * @return string
     */
    protected function _getUpdaterScript()
    {
        $url = '//' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '/'
            . 'po_fpc/updater/blocks?'
            . Potato_FullPageCache_Helper_Data::UPDATER_REQUEST_PAGE_ID_KEY . '=' . $this->getId()
            . '&' . Potato_FullPageCache_Model_Cache_Page::CLIENT_UID . '=' . $this->getClientUid()
        ;
        $documentLoadSuspend = Potato_FullPageCache_Helper_Config::canDocumentLoadSuspend() ? 'true' : 'false';

        return '<script rel="nofollow" type="text/javascript">

            if (' . $documentLoadSuspend . ' && typeof(jQuery) !== "undefined" && typeof(jQuery.holdReady) !== "undefined") {
                jQuery.holdReady(true);
            }

            if (typeof(poFpcThemeCompatibility) === "undefined") {
                var poFpcThemeCompatibility = function() {
                    if (typeof deleteItem === "function") {
                        try {
                            //compatibility with EM themes
                            deleteItem();
                        }
                        catch(err) {
                            console.log(err);
                        }
                    }
                };
            }
            var fpcUpdater = function() {
                new Ajax.Request(' . Zend_Json::encode($url) . ', {
                    method: "get",
                    onSuccess: function(transport){
                        var json = JSON.parse(transport.responseText)
                        if (json.blocks) {
                            window.form_key = json.form_key;
                            for(key in json.blocks) {
                                $$("#po_fpc_" + key.replace(/\./g, "_")).each(function(el) {
                                    el.replace(json.blocks[key]);
                                });
                            }
                        }
                        if (Array.isArray(json.js)) {
                            for (var i=0; i < json.js.length; i++) {
                                document.body.innerHTML += json.js[i];
                            }
                        }
                        $$(\'input[name="form_key"]\').each(function(el){
                            el.value = json.form_key;
                        });
                        try {
                            poFpcThemeCompatibility();
                        }
                        catch(err) {}
    
                        if (' . $documentLoadSuspend . ' && typeof(jQuery) !== "undefined") {
                            jQuery.holdReady(false);
                        }
                    },
                    onFailure:  function(){
                        if (' . $documentLoadSuspend . ' && typeof(jQuery) !== "undefined") {
                            jQuery.holdReady(false);
                        }
                    }
                });
            }
            if (typeof(Ajax) == "undefined") {
                document.addEventListener("DOMContentLoaded", function(event) {
                    fpcUpdater();
                });
            } else {
                fpcUpdater();
            }
        </script>';
    }

    /**
     * Remove dynamic content from page content
     *
     * @param $content
     *
     * @return mixed
     */
    protected function _removeBlocksContent($content)
    {
        //blocks from po_fpc.xml skip_blocks tag
        $actionBlocks = $this->getBlockCache()->getActionBlocks();
        foreach ($actionBlocks as $index => $blockData) {
            $content = $this->_removeBlockContent($index, $content);
        }
        //blocks from po_fpc.xml session_blocks tag
        $sessionBlocks = $this->getBlockCache()->getSessionBlocks();
        foreach ($sessionBlocks as $index => $blockData) {
            $content = $this->_removeBlockContent($index, $content);
        }
        return $content;
    }

    /**
     * Replace block content on page on updater marker
     *
     * @param $index
     * @param $content
     *
     * @return mixed
     */
    protected function _removeBlockContent($index, $content)
    {
        $htmlForReplace = '<div class="po-fpc-updater" id="po_fpc_' . $this->_makeCompatibleWithJs($index) . '"></div>';
        $this->_contentForReplace = $htmlForReplace;
        $replaceResult = @preg_replace_callback("/<!--\[{$index}\]-->(.*?)<!--\[{$index}\]-->/ims",
            array($this, '_replaceContent'), $content
        );
        if ($replaceResult) {
            $content = $replaceResult;
        }
        return $content;
    }

    /**
     * Get dynamic content for cached page
     *
     * @return array
     */
    public function extractBlocks()
    {
        //debug on local: initCurrentStore ~ +(50ms - 70ms)
        if (!$this->initCurrentStore()) {
            return false;
        }

        //load events
        Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_GLOBAL, Mage_Core_Model_App_Area::PART_EVENTS);
        Mage::app()->loadAreaPart(Mage_Core_Model_App_Area::AREA_FRONTEND, Mage_Core_Model_App_Area::PART_EVENTS);

        //debug on local: initRequest ~ +10ms
        //init request (for correct redirects from controller)
        Potato_FullPageCache_Helper_Data::initRequest();

        //emulate request
        Potato_FullPageCache_Helper_Data::emulateRequest($this->_request);

        //debug on local: session ~ +10ms
        //set session cookie
        Mage::getSingleton('core/session', array('name' => self::SESSION_NAMESPACE))->start();

        //debug on local: saveVisitorData ~ +80ms
        //dispatch event for save visitor data
        Potato_FullPageCache_Helper_Data::saveVisitorData();

        //dispatch router events e.g. register current product
        $this->_dispatchRouterEvents();

        //debug on local: dynamic blocks ~ +60ms
        //collect dynamic blocks
        $blocks = $this->_getUpdatedRouterBlocks() + $this->_getUpdatedSessionBlocks();
        return $blocks;
    }

    /**
     * Update routers blocks (po_fpc.xml skip_blocks tag)
     *
     * @return bool
     */
    protected function _getUpdatedRouterBlocks()
    {
        $blocksData = array();

        //get blocks from config
        $actionBlocks = $this->getBlockCache()->getActionBlocks();
        foreach ($actionBlocks as $index => $blockData) {
            if (!$this->_getIsBlockOnPage($index)) {
                continue;
            }
            //get block processor
            $blockProcessor = $this->getBlockCache()->getBlockCacheProcessor($index);
            $actionCachedBlock = $this->getBlockCache()->getActionBlockCache($index);
            if ($blockProcessor->getIsIgnoreCache() || !empty($actionCachedBlock)) {
                //update block cache if cache not exists or block processor ignore it
                if ($content = $this->_updateBlock($index, $blockData)) {
                    $blocksData[$index] = $content;
                }
                continue;
            }
        }
        return $blocksData;
    }

    /**
     * Is block on current page
     *
     * @param $blockIndex
     *
     * @return bool
     */
    protected function _getIsBlockOnPage($blockIndex)
    {
        return strpos($this->_content, 'id="po_fpc_' . $this->_makeCompatibleWithJs($blockIndex) . '"') !== false;
    }

    protected function _makeCompatibleWithJs($index)
    {
        return str_replace('.', '_', $index);
    }

    /**
     * @return string
     */
    public function getClientUid()
    {
        if (null === $this->_clientUid) {
            if (isset($_COOKIE[self::SESSION_NAMESPACE])) {
                $this->_clientUid = $_COOKIE[self::SESSION_NAMESPACE];
                return $this->_clientUid;
            }
            $uid = $this->getCurrentCustomerGroup() //customer group
                . null                                    //quote id
                . $this->getCurrentStoreId()         //store id
                . $this->getCurrentCurrencyCode()    //currency
            ;
            $this->_clientUid = md5($uid);
        }
        return $this->_clientUid;
    }

    public function setClientUid($uid)
    {
        $this->_clientUid = $uid;
        return $this;
    }

    /**
     * @return array
     */
    protected function _getUpdatedSessionBlocks()
    {
        $blocksData = array();

        //get blocks from config
        $sessionBlocks = $this->getBlockCache()->getSessionBlocks();
        foreach ($sessionBlocks as $index => $blockData) {
            if (!$this->_getIsBlockOnPage($index)) {
                continue;
            }
            $blockProcessor = $this->getBlockCache()->getBlockCacheProcessor($index);
            $sessionBlockCache = $blockProcessor->load($index);
            if ($sessionBlockCache && !$blockProcessor->getIsIgnoreCache()) {
                //$blocksData[$index] = $sessionBlockCache['html'];
                continue;
            }
            if ($content = $this->_updateBlock($index, $blockData)) {
                $blocksData[$index] = $content;
            }
        }
        return $blocksData;
    }

    /**
     * Update block html from saved layout
     *
     * @param       $index
     * @param array $blockData
     *
     * @return bool|string
     */
    protected function _updateBlock($index, $blockData = array())
    {
        if (!$this->_initLayout()) {
            //return false if mage config not init
            return false;
        }
        //get block processor
        $blockProcessor = $this->getBlockCache()->getBlockCacheProcessor($index);

        //get block from layout
        $block = Mage::app()->getLayout()->getBlock($index);
        if (!$block && is_array($blockData) && array_key_exists('type', $blockData)) {
            try {
                $block = Mage::app()->getLayout()->createBlock($blockData['type'], $index);
                if (array_key_exists('params', $blockData)) {
                    $block->addData($blockData['params']);
                }
                if (array_key_exists('template', $blockData)) {
                    $block->setTemplate($blockData['template']);
                }
            } catch (Exception $e) {
                return false;
            }
        }
        if ($blockProcessor && $block) {
            try {
                //get updated content from block processor
                $blockHtml = $blockProcessor->getBlockHtml($block);
            } catch (Exception $e) {
                Mage::logException($e);
                $blockHtml = '';
            }

            //save block cache
            $this->getBlockCache()->saveSkippedBlockCache(
                array(
                    'html'           => $blockHtml,
                    'name_in_layout' => $block->getNameInLayout()
                ),
                $index
            );
        }
        if (Potato_FullPageCache_Helper_Data::isDebugModeEnabled()) {
            $blockHtml = '<div style="border:1px solid green;width:auto;height:auto;"><div style="color:green;">'
                . $block->getNameInLayout() . '</div>' . $blockHtml . '</div>'
            ;
        }
        Mage::dispatchEvent('po_fpc_block_updated', array('html' => &$blockHtml));
        if (!Potato_FullPageCache_Helper_Config::canUseAjax()) {
            $this->_updateCachedHtml($blockHtml, $index);
        } else {
            $blockHtml = $this->_replaceJs($blockHtml);
        }
        return $blockHtml;
    }

    public function getReplacedJs()
    {
        return $this->_scripts;
    }

    protected function _replaceJs($body)
    {
        //get all script tags
        preg_match_all('/<script\b[^>]*>(.*?)<\/script>/is', $body, $matches);
        if (empty($matches)) {
            return $body;
        }
        $resultBody = $body;
        $ifDirectiveData = $this->_getIfDirectiveData($body);

        foreach ($matches[0] as $line) {
            $scriptLine = $line;

            //ignore <script type="application/ld+json">
            preg_match('@application/ld.json@', $scriptLine, $match);
            if (!empty($match)) {
                continue;
            }

            //ignore <script type="text/x-handlebars-template">
            preg_match('@text/x-handlebars-template@', $scriptLine, $match);
            if (!empty($match)) {
                continue;
            }

            //remove script from body
            $resultBody = str_replace($line, '', $resultBody);

            //issue if line like // ]> </script>
            $scriptLine = str_replace('</script>', "\n</script>", $scriptLine);

            //issue if line like ]--> <script>
            $scriptLine = str_replace('<script>', "\n<script>", $scriptLine);

            $content = $scriptLine;

            //check is if directive needed
            $linePosition = strpos($body, $line);
            foreach ($ifDirectiveData as $ifData) {
                if ($linePosition > $ifData['startPosition']
                    && $linePosition < $ifData['endPosition'])
                {
                    $content = $ifData['startString'] . $content
                        . $ifData['endString']
                    ;
                    break;
                }
            }
            array_push($this->_scripts, $content);
        }
        return $resultBody;
    }

    private function _getIfDirectiveData($body)
    {
        preg_match_all('/<!-{0,2}\[if[^>]*>/', $body, $ifDirectiveMatch, PREG_OFFSET_CAPTURE);
        preg_match_all('/<!\[endif\]-{0,2}>/', $body, $endifDirectiveMatch, PREG_OFFSET_CAPTURE);
        if (empty($ifDirectiveMatch)) {
            return array();
        }
        $data =array();
        foreach ($ifDirectiveMatch[0] as $key => $if) {
            $data[] = array(
                'startString' => $if[0],
                'endString' => $endifDirectiveMatch[0][$key][0],
                'startPosition' => $if[1],
                'endPosition' => $endifDirectiveMatch[0][$key][1]
            );
        }
        return $data;
    }

    /**
     * @param $htmlForReplace
     * @param $index
     *
     * @return $this
     */
    protected function _updateCachedHtml($htmlForReplace, $index)
    {
        $htmlForReplace = $this->_replaceJs($htmlForReplace);
        $this->_content = preg_replace('/<div class="po-fpc-updater" id="po_fpc_' . $index . '"><\/div>/',
            strtr($htmlForReplace, array('\\\\' => '\\\\\\\\', '$' => '\\$')), $this->_content
        );
        return $this;
    }

    /**
     * Generate layout blocks
     *
     * @return bool
     */
    protected function _initLayout()
    {
        //check flag
        if ($this->_isLayoutInitCompleteFlag) {
            return true;
        }
        //init translate, events
        Mage::app()->loadArea(Mage_Core_Model_App_Area::AREA_FRONTEND);

        //disable session in url
        Mage::app()->setUseSessionInUrl(false);

        /**
         * compatibility with Gene_Braintree start
         */
        Potato_FullPageCache_Helper_Compatibility::useGeneBraintree();
        /**
         * compatibility with Gene_Braintree end
         */

        /**
         * compatibility with OlegnaxAjaxcart start
         */
        Potato_FullPageCache_Helper_Compatibility::useOlegnaxAjaxcart();
        /**
         * compatibility with OlegnaxAjaxcart end
         */

        //init Mage_Core_Model_Layout
        $this->_loadLayout();
        $layout = Mage::app()->getLayout();

        /**
         * compatibility with EM themes start
         */
        Potato_FullPageCache_Helper_Compatibility::useEMThemeBeforeLayoutGenerateBlocks($layout);
        /**
         * compatibility with EM themes end
         */

        //init router data before generate blocks
        $this->_beforeLayoutGenerateBlocks();

        //generate layout blocks
        $layout->generateBlocks();

        /**
         * compatibility with EM themes start
         */
        Potato_FullPageCache_Helper_Compatibility::useEMThemeAfterLayoutGenerateBlocks($layout);
        /**
         * compatibility with EM themes end
         */

        //init router data after generate blocks
        $this->_afterLayoutGenerateBlocks();

        //set flag
        $this->_isLayoutInitCompleteFlag = true;
        return true;
    }

    /**
     * Some skipped block can be required some specific actions before generate layout
     *
     * @return $this
     */
    protected function _beforeLayoutGenerateBlocks()
    {
        $actionConfig = $this->getBlockCache()->getActionConfig();
        if (array_key_exists('processor', $actionConfig)) {
            Mage::getModel($actionConfig['processor'])->beforeLayoutGenerateBlocks();
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _dispatchRouterEvents()
    {
        $actionConfig = $this->getBlockCache()->getActionConfig();
        if (array_key_exists('processor', $actionConfig)) {
            Mage::getModel($actionConfig['processor'])->dispatchEvents();
        }
        return $this;
    }

    /**
     * Some skipped block can be required some specific actions after generate layout
     *
     * @return $this
     */
    protected function _afterLayoutGenerateBlocks()
    {
        $actionConfig = $this->getBlockCache()->getActionConfig();
        if (array_key_exists('processor', $actionConfig)) {
            Mage::getModel($actionConfig['processor'])->afterLayoutGenerateBlocks();
        }
        return $this;
    }

    /**
     * @param null | string $id
     *
     * @return mixed |string
     */
    public function load($id = null)
    {
        $_result = parent::load($id);

        if (array_key_exists('tags', $_result)) {
            $this->_tags = $_result['tags'];
        }

        if (!$this->_referrerIncludedFlag &&
            Potato_FullPageCache_Helper_Config::useProductReferrerPage() &&
            Potato_FullPageCache_Helper_Data::checkReferrerDomain() &&
            $this->_isProductPage() &&
            $_result['referrer'] != Potato_FullPageCache_Helper_Data::getReferrerPage()
        ) {
            //if its product page -> compare referrer urls
            return false;
        }
        $this->_content = $this->_gzuncompress($_result['content']);
        $this->_layout = $this->_gzuncompress($_result['layout']);
        $this->_layoutUpdate = $_result['update'];
        $this->_request = $_result['request'];
        $this->_headers = $_result['headers'];
        $this->_defaultStoreId = $_result['default_store_id'];
        $this->_defaultCurrency = $_result['default_currency'];
        return $this;
    }

    public function setReferrerFlag($value)
    {
        $this->_referrerIncludedFlag = $value;
        return $this;
    }

    public function loadByReferrerUrl($id = null)
    {
        if (null == $id) {
            $id = $this->getId();
        }
        $id = $this->_includeReferrerPage($id);
        if (!$this->test($id)) {
            return false;
        }
        $this->load($id);
        $this->_referrerIncludedFlag = true;
        return $this;
    }

    protected function _includeReferrerPage($id)
    {
        if ($this->_referrerIncludedFlag) {
            return $id;
        }
        return md5($id . Potato_FullPageCache_Helper_Data::getReferrerPage());
    }

    protected function _isProductPage()
    {
        if (!is_array($this->_tags)) {
            return false;
        }
        return in_array(Potato_FullPageCache_Model_Cache::PRODUCT_TAG, $this->_tags);
    }

    /**
     * Set frame tags for each dynamic blocks
     *
     * @param Mage_Core_Block_Abstract $block
     *
     * @return $this
     */
    public function setFrameTags($block)
    {
        $block->setFrameTags('!--[' . $block->getNameInLayout() . ']--', '!--[' . $block->getNameInLayout() . ']--');
        return $this;
    }

    /**
     * @param array $matches
     *
     * @return mixed
     */
    protected function _replaceContent($matches)
    {
        return $this->_contentForReplace;
    }

    /**
     * Init request store info
     *
     * @return bool
     */
    public function initCurrentStore()
    {
        if ($this->_platformConfigInitFlag) {
            return true;
        }
        if (!Potato_FullPageCache_Model_Cache::loadMageConfig()) {
            //return if mage config not saved
            return false;
        }
        //load store id by request
        $storeId = Potato_FullPageCache_Helper_CacheStore::loadStoreByRequest() ?
            Potato_FullPageCache_Helper_CacheStore::loadStoreByRequest() : $this->_defaultStoreId
        ;
        if ($_COOKIE && isset($_COOKIE[Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME])) {
            //get store id from cookie
            $storeId = $_COOKIE[Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME];
        }
        if (!$storeId) {
            return false;
        }
        //init current store
        Mage::app()->setCurrentStore($storeId);

        //not required but some modules used Mage::app()->getWebsite(true)
        Mage::app()->reinitStores();
        $this->_platformConfigInitFlag = true;
        return true;
    }
}