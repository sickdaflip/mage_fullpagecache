<?php

class Potato_FullPageCache_Model_Cron
{
    public function updateByCatalogRule()
    {
        if (!Mage::app()->useCache('po_fpc')) {
            return $this;
        }

        if (!Potato_FullPageCache_Helper_Data::isMatchingCronSettings(Potato_FullPageCache_Helper_Config::getCatalogRuleCronJob())) {
            return $this;
        }

        try {
            $this->_removeExpiredCache();
        } catch (Exception $e) {
            Mage::logException($e);
        }
    }

    protected function _removeExpiredCache()
    {
        $read = Mage::getResourceModel('po_fpc/crawler')->getReadConnection();
        $select = $read->select();

        $select->from(Mage::getResourceModel('po_fpc/crawler')->getTable('catalogrule/rule_product'), 'product_id');
        //filter on expired rules
        $select->where('to_time < ?', Mage::getModel('core/date')->gmtTimestamp());
        //filter on expired today
        $select->where('to_time > ?', Mage::getModel('core/date')->gmtTimestamp() - 86400);
        $select->distinct();
        $productIds = $read->fetchCol($select->__toString());
        foreach ($productIds as $productId) {
            $product = Mage::getModel('catalog/product')->load($productId);
            $observer = new Varien_Event_Observer();
            $observer->setData(
                array(
                    'product' => $product
                )
            );
            Mage::getModel('po_fpc/observer_autoClean')->updateProductCache($observer);
        }
        return $this;
    }
}
