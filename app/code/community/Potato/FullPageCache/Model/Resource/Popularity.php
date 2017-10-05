<?php

/**
 * Class Potato_FullPageCache_Model_Resource_Popularity
 */
class Potato_FullPageCache_Model_Resource_Popularity extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('po_fpc/popularity', 'id');
    }
}