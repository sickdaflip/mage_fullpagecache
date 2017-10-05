<?php

/**
 * Class Potato_FullPageCache_Model_Observer
 */
class Potato_FullPageCache_Model_Observer
{
    /**
     * set block frames in block html
     *
     * @param $observer
     *
     * @return $this
     */
    public function setFrameTags($observer)
    {
        if (!Potato_FullPageCache_Helper_Data::canCache() || Potato_FullPageCache_Helper_Data::isUpdater()) {
            return $this;
        }
        $block = $observer->getBlock();
        $pageCache = Potato_FullPageCache_Model_Cache::getPageCache(true);
        if ($pageCache->getBlockCache()->getIsCanCache($block->getNameInLayout())) {
            return $this;
        }
        $pageCache = Potato_FullPageCache_Model_Cache::getPageCache();
        $pageCache->setFrameTags($block);
        return $this;
    }

    /**
     * cache skipped blocks content
     *
     * @param $observer
     *
     * @return $this
     */
    public function cacheSkippedBlocks($observer)
    {
        if (!Mage::app()->useCache('po_fpc') || empty($_COOKIE)) {
            return $this;
        }
        $block = $observer->getBlock();
        $transport = $observer->getTransport();
        $pageCache = Potato_FullPageCache_Model_Cache::getPageCache(true);
        if (!$pageCache->getBlockCache()->getIsCanCache($block->getNameInLayout())) {
            try {
                $pageCache->getBlockCache()->saveSkippedBlockCache(
                    array(
                        'html'           => $transport->getHtml(),
                        'name_in_layout' => $block->getNameInLayout()
                    ),
                    $block->getNameInLayout()
                );
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        if (Potato_FullPageCache_Helper_Data::isDebugModeEnabled() &&
            Potato_FullPageCache_Helper_Config::canShowBlockHint()
        ) {
            $blockHtml = '<div style="border:1px solid green;width:auto;height:auto;"><div style="color:green;">'
                . $block->getNameInLayout() . '</div>' . $transport->getHtml() . '</div>'
            ;
            $transport->setHtml($blockHtml);
        }
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function cacheResponse(Varien_Event_Observer $observer)
    {
        if (!Potato_FullPageCache_Helper_Data::canCache()) {
            if (isset($_GET['___store'])) {
                //set store cookie
                Potato_FullPageCache_Model_Cache::setStoreCookie(Mage::app()->getStore()->getId());//save current store id
            }
            return $this;
        }
        //save current store id
        Potato_FullPageCache_Model_Cache::setStoreCookie(Mage::app()->getStore()->getId());

        //save current customer group id
        Potato_FullPageCache_Model_Cache::setCustomerGroupCookie(Mage::getSingleton('customer/session')->getCustomerGroupId());

        //save current currency
        Potato_FullPageCache_Model_Cache::setCurrencyCookie(Mage::app()->getStore()->getCurrentCurrencyCode());

        /**
         * Category sorting compatibility
         */
        if (Potato_FullPageCache_Helper_Config::includeSorting()) {
            if (isset($_GET['dir'])) {
                //save sort order
                Potato_FullPageCache_Model_Cache::setSortOrderCookie($_GET['dir']);
            } elseif(Mage::getSingleton('catalog/session')->getSortDirection()) {
                //save sort order
                Potato_FullPageCache_Model_Cache::setSortOrderCookie(
                    Mage::getSingleton('catalog/session')->getSortDirection()
                );
            }
            if (isset($_GET['order'])) {
                Potato_FullPageCache_Model_Cache::setSortDirCookie($_GET['order']);
            } elseif (Mage::getSingleton('catalog/session')->getDisplayMode()) {
                //save sort dir
                Potato_FullPageCache_Model_Cache::setSortDirCookie(
                    Mage::getSingleton('catalog/session')->getDisplayMode()
                );
            }
        }
        /**
         * end
         */

        if (Potato_FullPageCache_Helper_Config::includeSorting() && (isset($_GET['order']) || isset($_GET['dir']))) {
            //skip caching its sort order|sort dir switching
            return $this;
        }

        //save mage config
        Potato_FullPageCache_Model_Cache::saveMageConfigXml();

        //save store id
        Potato_FullPageCache_Helper_CacheStore::saveStoreByRequest();
        $response = $observer->getEvent()->getResponse();
        $pageCache = Potato_FullPageCache_Model_Cache::getPageCache();
        if (null === $pageCache->getId()) {
            return $this;
        }
        $pageCache->setCanUseStoreDataFlag(true);
        $content = $response->getBody();

        //replace old form key
        $content = str_replace('/' . Mage::getSingleton('core/session')->getFormKey() . '/', '/' . Potato_FullPageCache_Model_Core_Session::STATIC_FORM_KEY . '/', $content);

        try {
            //save response body
            $pageCache->save($content, null, Potato_FullPageCache_Helper_Data::getCacheTags(), false, Potato_FullPageCache_Helper_Data::getPrivateTag());
            Mage::app()->getResponse()
                ->setHeader('X-Cache', 'MISS', true)
            ;
        } catch (Exception $e) {
            Mage::logException($e);
        }

        //set response
        $response->setBody($response->getBody());
        return $this;
    }

    /**
     * save current cms - used for cache tags
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function registerCmsPage(Varien_Event_Observer $observer)
    {
        if (Mage::app()->useCache('po_fpc')) {
            Mage::register('current_cms', $observer->getEvent()->getPage(), true);
        }
        return $this;
    }

    /**
     * update customer group cookie after customer login logout
     *
     * @return $this
     */
    public function updateCustomerGroupCookie()
    {
        try {
            Potato_FullPageCache_Model_Cache::setCustomerGroupCookie(Mage::getSingleton('customer/session')->getCustomerGroupId());
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * For set currency to store for crawler
     *
     * @return $this
     */
    public function setCurrencyForCrawler()
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::isCrawler() ||
            !isset($_COOKIE[Potato_FullPageCache_Model_Cache::CURRENCY_COOKIE_NAME])
        ) {
            return $this;
        }
        $coreSession = Mage::getModel('core/session')
            ->init('store_' . Mage::app()->getStore()->getCode())
        ;
        $coreSession->setCurrencyCode($_COOKIE[Potato_FullPageCache_Model_Cache::CURRENCY_COOKIE_NAME]);
        return $this;
    }

    /**
     * Create crawler customer session
     *
     * @return $this
     */
    public function setCustomerForCrawler()
    {
        if (Mage::app()->useCache('po_fpc') &&
            Potato_FullPageCache_Helper_Data::isCrawler() &&
            isset($_COOKIE[Potato_FullPageCache_Model_Cache::CUSTOMER_GROUP_ID_COOKIE_NAME])
        ) {
            //set session id
            session_id(Potato_FullPageCache_Model_Crawler_Customer_Session::ID);

            //start session
            Mage::getSingleton('core/session', array('name' => Potato_FullPageCache_Model_Cache_Page::SESSION_NAMESPACE))->start();

            //register fake crawler customer
            Mage::register('_singleton/customer/session', Mage::getModel('po_fpc/crawler_customer_session'), true);
        }
        return $this;
    }
}