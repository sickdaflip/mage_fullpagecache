<?php

class Potato_FullPageCache_Model_Processor_Router_Catalog extends Potato_FullPageCache_Model_Processor_Router_Default
{
    /**
     * @return $this|bool
     */
    public function beforeLayoutGenerateBlocks()
    {
        if (Mage::app()->getRequest()->getControllerName() == 'category') {
            return $this->_registerCurrentCategory();
        }
        return $this->_registerCurrentProduct();
    }

    /**
     * mage register current category
     *
     * @return $this
     */
    protected function _registerCurrentCategory()
    {
        if (Mage::registry('current_category')) {
            return $this;
        }
        $categoryId = (int) Mage::app()->getRequest()->getParam('id', false);
        if (!$categoryId) {
            return $this;
        }
        $category = Mage::getModel('catalog/category')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($categoryId)
        ;
        Mage::register('current_category', $category, true);
        return $this;
    }

    /**
     * mage register current product and category
     *
     * @return $this|bool
     */
    protected function _registerCurrentProduct()
    {
        if (Mage::registry('current_product')) {
            return $this;
        }
        $categoryId = (int) Mage::app()->getRequest()->getParam('category', false);
        $productId  = (int) Mage::app()->getRequest()->getParam('id');
        if (!$productId) {
            return false;
        }
        $product = Mage::getModel('catalog/product')
            ->setStoreId(Mage::app()->getStore()->getId())
            ->load($productId)
        ;
        $category = null;

        if ($categoryId) {
            $category = Mage::getModel('catalog/category')->load($categoryId);
            $product->setCategory($category);
            if (!Mage::registry('current_category')) {
                Mage::register('current_category', $category, true);
            }
        } elseif ($categoryId = Mage::getSingleton('catalog/session')->getLastVisitedCategoryId()) {
            if ($product->canBeShowInCategory($categoryId)) {
                $category = Mage::getModel('catalog/category')->load($categoryId);
                $product->setCategory($category);
                if (!Mage::registry('current_category')) {
                    Mage::register('current_category', $category, true);
                }
            }
        }
        Mage::register('current_product', $product, true);
        Mage::register('product', $product, true);
        return $this;
    }

    /**
     * @return $this
     */
    public function dispatchEvents()
    {
        if (Mage::app()->getRequest()->getControllerName() == 'product') {
            $viewedBlocks = array('left.reports.product.viewed', 'right.reports.product.viewed', 'last.viewed.products', 'last.viewed.products.widget');
            $blockCache = Potato_FullPageCache_Model_Cache::getPageCache()->getBlockCache();
            foreach ($viewedBlocks as $index) {
                $blockProcessor = $blockCache->getBlockCacheProcessor($index);
                try {
                    $blockProcessor->remove($index);
                } catch (Exception $e) {
                    Mage::logException($e);
                }
            }

            $this->_registerCurrentProduct();
            //report viewed stat
            $observer = new Varien_Event_Observer();
            $event = new Varien_Event(
                array(
                    'product' => Mage::registry('current_product')
                )
            );
            $observer->setData(
                array(
                    'event' => $event
                )
            );
            //report statistic - if not needed remove
            Mage::getModel('reports/event_observer')
                ->catalogProductView($observer)
            ;
            //attach inventory
            Mage::getModel('cataloginventory/observer')
                ->addInventoryData($observer)
            ;
            //send friend
            Mage::getModel('sendfriend/observer')
                ->register($observer)
            ;
            //viewed block update
            Mage::getModel('po_fpc/observer_updater')
                ->productViewUpdater()
            ;
        }
        return $this;
    }
}