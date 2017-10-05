<?php

/**
 * Class Potato_FullPageCache_Model_Statistics
 */
class Potato_FullPageCache_Model_Statistics extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('po_fpc/statistics');
    }

    /**
     * @param $date
     *
     * @return Mage_Core_Model_Abstract
     */
    public function loadByDateTime($date)
    {
        return $this->load($date, 'created_at');
    }
}