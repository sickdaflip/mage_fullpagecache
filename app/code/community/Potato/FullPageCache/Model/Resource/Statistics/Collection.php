<?php

/**
 * Class Potato_FullPageCache_Model_Resource_Statistics_Collection
 */
class Potato_FullPageCache_Model_Resource_Statistics_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    const MAX_PERIOD     = '1209600'; //2 weeks
    const CACHE_TAG      = 'Potato_FullPageCache_Model_Resource_Statistics_Collection';
    const CACHE_LIFETIME = '3600'; // 1 hour

    protected function _construct()
    {
        parent::_construct();
        $this->_init('po_fpc/statistics');
    }

    /**
     * Save collection data to cache
     *
     * @param array $data
     * @param Zend_Db_Select $select
     * @return Mage_Core_Model_Resource_Db_Collection_Abstract
     */
    protected function _saveCache($data, $select)
    {
        Mage::app()->saveCache(serialize($data), $this->_getSelectCacheId($select), $this->_getCacheTags(), self::CACHE_LIFETIME);
        return $this;
    }

    /**
     * @return array
     */
    public function getCacheMiss()
    {
        $currentDate = Mage::getModel('core/date')->gmtDate('Y-m-d H:00:00');
        $this->addFieldToFilter('created_at',
            array(
                "from" => date('Y-m-d H:00:00', Mage::getModel('core/date')->gmtTimestamp() - self::MAX_PERIOD),
                "to"   => $currentDate
            )
        );
        $res = array();
        foreach ($this as $item) {
            $res[$item->getData('created_at')] = array(
                'cached' => $item->getData('cached'),
                'miss'   => $item->getData('miss')
            );
        }
        return $res;
    }
}