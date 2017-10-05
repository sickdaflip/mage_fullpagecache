<?php

class Potato_FullPageCache_Model_Resource_Crawler_Queue_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('po_fpc/crawler_queue');
    }

    public function setLimit($limit)
    {
        $this->getSelect()->limit($limit);
        return $this;
    }
}