<?php
/**
 * Class Potato_FullPageCache_Model_Processor
 */
class Potato_FullPageCache_Model_Processor
{
    const DEFAULT_HTTP_PORT  = 80;
    const DEFAULT_HTTPS_PORT = 443;

    /**
     * @param $content
     *
     * @return string
     */
    public function extractContent($content)
    {
        $cache = Potato_FullPageCache_Model_Cache::getOutputCache(Potato_FullPageCache_Model_Cache::PO_FPC_CONFIG_CACHE_ID);
        $mageConfig = Potato_FullPageCache_Model_Cache::getOutputCache(Potato_FullPageCache_Model_Cache::CONFIG_CACHE_ID);
        if (!$this->getIsAllowed() || $content || !$cache->test() || !$mageConfig->test()) {
            return $content;
        }
        if (Potato_FullPageCache_Helper_Data::isDebugModeEnabled() &&
            Potato_FullPageCache_Helper_Config::canShowBlockHint()
        ) {
            return false;
        }
        if (Potato_FullPageCache_Helper_Data::isUpdater()) {
            /**
             * Ajax updater
             */
            $pageId = Mage::app()->getRequest()->getParam('po_fpc_page_id', null);
            unset($_GET['po_fpc_page_id']);
            try {
                $content = $this->_extractBlocks($pageId);
                $response = Mage::app()->getResponse();
                $response
                    ->setHeader('Content-type', 'text/html; charset=UTF-8', true)
                    ->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT', true)
                    ->setHeader('Last-Modified', gmdate("D, d M Y H:i:s") . ' GMT', true)
                    ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate', true)
                    ->setHeader('Cache-Control', 'post-check=0, pre-check=0', false)
                    ->setHeader('Pragma', 'no-cache', true)
                    ->appendBody($content)
                    ->sendHeaders()
                ;
                $response->outputBody();
                exit();
            } catch (Exception $e) {
                Mage::log($e->getMessage(), 1, 'po_fpc_except.log');
                Potato_FullPageCache_Model_Cache::removeMageConfigXmlCache();
                return false;
            }
        }
        /**
         * Load prepared cache
         */
        $pageCache = Potato_FullPageCache_Model_Cache::getPageCache();
        if (Potato_FullPageCache_Helper_Config::useProductReferrerPage() &&
            Potato_FullPageCache_Helper_Data::checkReferrerDomain() &&
            $pageCache->loadByReferrerUrl()
        ) {
            //try load by referrer url (needed for product pages in few categories)
            try {
                $content = $pageCache->extractContent(false);
            } catch (Exception $e) {
                Mage::log($e->getMessage(), 1, 'po_fpc_except.log');
                $content = false;
            }
        } else if ($pageCache->test()) {
            //try load without referrer url
            try {
                $content = $pageCache->extractContent();
            } catch (Exception $e) {
                Mage::log($e->getMessage(), 1, 'po_fpc_except.log');
                return false;
            }
        }
        if ($content) {
            //update statistic HIT
            Potato_FullPageCache_Helper_Data::updateStatistics(
                $pageCache->getCurrentStoreId(), true
            );
        }
        return $content;
    }

    /**
     * Compatibility with Mana filter
     *
     * @return bool
     */
    static function isManaFilter()
    {
        if (Mage::registry('mana_filter') ||  Mage::registry('m_original_request_uri')) {
            return true;
        }
        if (isset($_GET['m-ajax'])) {
            Mage::register('mana_filter', true, true);
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    static function getIsAllowed()
    {
        if (isset($_COOKIE['NO_CACHE']) || isset($_GET['no_cache']) || isset($_GET['___store']) ||
            self::isManaFilter() || !Mage::app()->useCache('po_fpc') ||
            isset($_GET['isAjax']) || isset($_POST['isAjax']) || isset($_GET['isLayerAjax']) || isset($_GET['is_ajax'])
        ) {
            return false;
        }
        return true;
    }

    /**
     * Prepare dynamic block content for ajax updater
     *
     * @param $pageId
     *
     * @return string
     */
    protected function _extractBlocks($pageId)
    {
        $pageCache = Potato_FullPageCache_Model_Cache::getPageCache();
        if (!$pageCache->test($pageId)) {
            return '    ';
        }
        $pageCache->setReferrerFlag(true)->load($pageId);

        $sessUid = Mage::app()->getRequest()->getParam(Potato_FullPageCache_Model_Cache_Page::CLIENT_UID, null);
        if ($sessUid) {
            $pageCache->setClientUid($sessUid);
            unset($_GET[Potato_FullPageCache_Model_Cache_Page::CLIENT_UID]);
        }

        //magento v 1.7 for correct work Mage_Core_Helper_Url::getCurrentUrl()
        $this->_setRequestUri();
        if (!$blocks = $pageCache->extractBlocks()) {
            return '    ';
        }
        $js = $pageCache->getReplacedJs();
        $response = Mage::helper('core')->jsonEncode(
            array(
                'blocks' => $blocks,
                'js'     => empty($js) ? false : $js
            )
        );
        return $response;
    }

    /**
     * magento v 1.7 for correct work Mage_Core_Helper_Url::getCurrentUrl()
     * @return $this
     */
    protected function _setRequestUri()
    {
        if (array_key_exists('HTTP_REFERER', $_SERVER)) {
            $request = Mage::app()->getRequest();
            $port = $request->getServer('SERVER_PORT');
            if ($port) {
                $defaultPorts = array(
                    self::DEFAULT_HTTP_PORT,
                    self::DEFAULT_HTTPS_PORT
                );
                $port = (in_array($port, $defaultPorts)) ? '' : ':' . $port;
            }
            $url = 'http://' . $request->getHttpHost() . $port;
            $secureUrl = 'https://' . $request->getHttpHost() . $port;
            $_SERVER['REQUEST_URI'] = str_replace($url, '', $_SERVER['HTTP_REFERER']);
            $_SERVER['REQUEST_URI'] = str_replace($secureUrl, '', $_SERVER['REQUEST_URI']);
        }
        return $this;
    }
}