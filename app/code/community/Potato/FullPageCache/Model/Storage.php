<?php

/**
 * Class Potato_FullPageCache_Model_Storage
 */
class Potato_FullPageCache_Model_Storage extends Mage_Core_Model_Abstract
{
    public function _construct()
    {
        parent::_construct();
        $this->_init('po_fpc/storage');
    }

    /**
     * @param $cacheId
     *
     * @return Mage_Core_Model_Abstract
     */
    public function loadByCacheId($cacheId)
    {
        return $this->load($cacheId, 'cache_id');
    }
}