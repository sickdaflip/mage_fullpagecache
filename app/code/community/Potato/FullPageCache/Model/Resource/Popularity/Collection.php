<?php

/**
 * Class Potato_FullPageCache_Model_Resource_Popularity_Collection
 */
class Potato_FullPageCache_Model_Resource_Popularity_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('po_fpc/popularity');
    }

    /**
     * Join cache status column
     *
     * @return $this
     */
    public function addCacheStatus()
    {
        $this->getSelect()
            ->joinLeft(
                array(
                    'storage' => new Zend_Db_Expr(
                        '(SELECT request_url, store_id FROM ' . $this->getTable('po_fpc/storage') . ' GROUP BY request_url, store_id)'
                    )
                ),
                'main_table.request_url = storage.request_url AND storage.store_id = main_table.store_id',
                array('is_cached' => new Zend_Db_Expr('IF (storage.request_url IS NOT NULL, 1, 0)'))
            )
        ;
        return $this;
    }
}