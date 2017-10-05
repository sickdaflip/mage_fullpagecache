<?php

/**
 * Class Potato_FullPageCache_Model_Resource_Storage_Collection
 */
class Potato_FullPageCache_Model_Resource_Storage_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('po_fpc/storage');
    }
}