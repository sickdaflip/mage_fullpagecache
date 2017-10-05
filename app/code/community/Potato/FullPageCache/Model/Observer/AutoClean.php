<?php

/**
 * Class Potato_FullPageCache_Model_Observer_AutoClean
 */
class Potato_FullPageCache_Model_Observer_AutoClean
{
    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function productSaveAfter(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::PRODUCT_SAVE_VALUE)
        ) {
            return $this;
        }
        return $this->_removeProductPageCache($observer);
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function productDelete(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::PRODUCT_DELETE_VALUE)
        ) {
            return $this;
        }
        return $this->_removeProductPageCache($observer);
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function categorySaveAfter(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::CATEGORY_SAVE_VALUE)
        ) {
            return $this;
        }
        return $this->_removeCategoryPageCache($observer);
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function categoryDelete(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::CATEGORY_DELETE_VALUE)
        ) {
            return $this;
        }
        return $this->_removeCategoryPageCache($observer);
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function cmsSaveAfter(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::CMS_SAVE_VALUE)
        ) {
            return $this;
        }
        return $this->_removeCmsPageCache($observer);
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function cmsDelete(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::CMS_DELETE_VALUE)
        ) {
            return $this;
        }
        return $this->_removeCmsPageCache($observer);
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this|Potato_FullPageCache_Model_AutoClean
     */
    public function reviewSaveAfter(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::REVIEW_SAVE_VALUE)
        ) {
            return $this;
        }
        return $this->_removeProductCache($observer->getEvent()->getObject()->getEntityPkValue());
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this|Potato_FullPageCache_Model_AutoClean
     */
    public function stockItemSaveAfter(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::INVENTORY_VALUE)
        ) {
            return $this;
        }

        //Mage_CatalogInventory_Model_Stock_Item
        $stockItem = $observer->getEvent()->getItem();
        return $this->_removeCacheByProductId($stockItem->getProductId(), $stockItem->getStoreId());
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function productAttributeUpdate(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::PRODUCT_ATTRIBUTE_VALUE)
        ) {
            return $this;
        }
        $productIds = $observer->getEvent()->getProductIds();
        $storeId = $observer->getEvent()->getStoreId();
        foreach ($productIds as $productId) {
            $this->_removeCacheByProductId($productId, $storeId);
        }
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function flushImagesCache(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::CATALOG_IMAGES_CACHE_VALUE)
        ) {
            return $this;
        }
        try {
            Potato_FullPageCache_Model_Cache::clean();
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function flushMediaCache(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::MEDIA_CACHE_VALUE)
        ) {
            return $this;
        }
        try {
            Potato_FullPageCache_Model_Cache::clean();
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function saveRates(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::CURRENCY_SAVE_VALUE)
        ) {
            return $this;
        }
        $this
            ->_cleanProductCache()
            ->_cleanCategoryCache();
        ;
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function currencySymbolSave(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::CURRENCY_SYMBOL_VALUE)
        ) {
            return $this;
        }
        $this
            ->_cleanProductCache()
            ->_cleanCategoryCache();
        ;
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     * @return $this
     */
    public function catalogRuleSave(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc') ||
            !Potato_FullPageCache_Helper_Data::canCleanCacheByEvent(Potato_FullPageCache_Model_Source_AutoClean::CATALOG_RULE_SAVE_VALUE)
        ) {
            return $this;
        }
        $this
            ->_cleanProductCache()
            ->_cleanCategoryCache();
        ;
        return $this;
    }

    /**
     * update remove cached router blocks by product events
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    public function updateProductCache(Varien_Event_Observer $observer)
    {
        if (!Mage::app()->useCache('po_fpc')) {
            return $this;
        }

        //get product id
        $productId = (int) Mage::app()->getRequest()->getParam('product');
        if ($observer->getProduct()) {
            $productId = $observer->getProduct()->getId();
        } elseif ($observer->getItem()) {
            $productId = $observer->getItem()->getProductId();
        }

        if (Potato_FullPageCache_Helper_Config::canUpdateSessionBlocksCacheWithoutAjax()) {
            try {
                //remove product page cache
                $this->_removeProductCache($productId);
            } catch (Exception $e) {
                Mage::logException($e);
            }
            return $this;
        }

        try {
            //remove product specific blocks
            $this->_removeProductCache($productId, array(Potato_FullPageCache_Model_Cache::BLOCK_TAG));
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function cleanCache()
    {
        $tags = Mage::app()->getRequest()->getParam('types', array());
        if (in_array('po_fpc', $tags)) {
            try {
                $this->flushCache();
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function flushCache()
    {
        try {
            Potato_FullPageCache_Model_Cache::clean();
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function cleanSystemCache()
    {
        try {
            //remove config cache
            Potato_FullPageCache_Model_Cache::cleanByTags(array(Potato_FullPageCache_Model_Cache::SYSTEM_TAG));
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _cleanProductCache()
    {
        try {
            Potato_FullPageCache_Model_Cache::cleanByTags(
                array(Potato_FullPageCache_Model_Cache::PRODUCT_TAG)
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _cleanCategoryCache()
    {
        try {
            Potato_FullPageCache_Model_Cache::cleanByTags(
                array(Potato_FullPageCache_Model_Cache::CATEGORY_TAG)
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * @param $productId
     * @param $storeId
     * @return $this
     */
    protected function _removeCacheByProductId($productId, $storeId)
    {
        try {
            //Mage_Catalog_Model_Product
            $product = Mage::getModel('catalog/product')
                ->setStoreId($storeId)
                ->load($productId)
            ;

            //get parent product ids
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE) {
                $configurableIds = Mage::getResourceSingleton('catalog/product_type_configurable')
                    ->getParentIdsByChild($product->getId())
                ;
                $groupedIds = Mage::getModel('catalog/product_type_grouped')
                    ->getParentIdsByChild($product->getId())
                ;
                $parentIds = array_merge($configurableIds, $groupedIds);
            } else {
                $parentIds = $product->getTypeInstance()->getParentIdsByChild($product->getId());
            }

            //refresh product page
            $observer = new Varien_Event_Observer();
            $observer->setProduct($product);
            $this->_removeProductPageCache($observer);

            //refresh parent product pages
            foreach ($parentIds as $parentId) {
                $parentProduct = Mage::getModel('catalog/product')
                    ->setStoreId($storeId)
                    ->load($parentId)
                ;
                $observer->setProduct($parentProduct);
                $this->_removeProductPageCache($observer);
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * @param       $productId
     * @param array $tags
     *
     * @return $this
     */
    protected function _removeProductCache($productId, $tags=array())
    {
        try {
            //remove cache by tags
            Potato_FullPageCache_Model_Cache::cleanByPrivateTag(
                Potato_FullPageCache_Model_Cache_Page::PRODUCT_ID_TAG_PREFIX . $productId,
                $tags
            );
        } catch (Exception $e) {
            Mage::logException($e);
        }
        return $this;
    }

    /**
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    protected function _removeProductPageCache(Varien_Event_Observer $observer)
    {
        if (Mage::app()->useCache('po_fpc')) {
            try {
                //remove cache by tags
                Potato_FullPageCache_Model_Cache::cleanByPrivateTag(
                    Potato_FullPageCache_Model_Cache_Page::PRODUCT_ID_TAG_PREFIX . $observer->getProduct()->getId()
                );

                //refresh categories pages
                foreach ($observer->getProduct()->getCategoryIds() as $categoryId) {
                    //remove cache by tags
                    Potato_FullPageCache_Model_Cache::cleanByPrivateTag(
                        Potato_FullPageCache_Model_Cache_Page::CATEGORY_ID_TAG_PREFIX . $categoryId
                    );
                }
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * update cached categories page by event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    protected function _removeCategoryPageCache(Varien_Event_Observer $observer)
    {
        if (Mage::app()->useCache('po_fpc')) {
            try {
                //remove cache by tags
                Potato_FullPageCache_Model_Cache::cleanByPrivateTag(
                    Potato_FullPageCache_Model_Cache_Page::CATEGORY_ID_TAG_PREFIX . $observer->getCategory()->getId()
                );
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    /**
     * update cached cms page by event
     *
     * @param Varien_Event_Observer $observer
     *
     * @return $this
     */
    protected function _removeCmsPageCache(Varien_Event_Observer $observer)
    {
        if (Mage::app()->useCache('po_fpc')) {
            try {
                //remove cache by tags
                Potato_FullPageCache_Model_Cache::cleanByPrivateTag(
                    Potato_FullPageCache_Model_Cache_Page::CMS_ID_TAG_PREFIX . $observer->getEvent()->getObject()->getId()
                );
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }
}