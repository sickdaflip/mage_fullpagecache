<?php

/**
 * Class Potato_FullPageCache_Model_Popularity
 */
class Potato_FullPageCache_Model_Popularity extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('po_fpc/popularity');
    }

    /**
     * @param $url
     * @param $storeId
     * @return Mage_Core_Model_Abstract
     */
    public function loadByRequestUrl($url, $storeId)
    {
        return $this->getCollection()
            ->addFieldToFilter('request_url', $url)
            ->addFieldToFilter('store_id', $storeId)
            ->getFirstItem()
        ;
    }

    /**
     * Processing object before save data
     *
     * @return Mage_Core_Model_Abstract
     */
    protected function _beforeSave()
    {
        $this->setUpdatedAt(Mage::getModel('core/date')->gmtDate());
        return parent::_beforeSave();
    }
}