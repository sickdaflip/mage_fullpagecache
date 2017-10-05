<?php

/**
 * Class Potato_FullPageCache_Model_Resource_Statistics
 */
class Potato_FullPageCache_Model_Resource_Statistics extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('po_fpc/statistics', 'id');
    }
}