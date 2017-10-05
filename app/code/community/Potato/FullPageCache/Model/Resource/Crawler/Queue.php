<?php

class Potato_FullPageCache_Model_Resource_Crawler_Queue extends Mage_Core_Model_Mysql4_Abstract
{
    protected $_serializableFields = array(
        'options' => array(null, array())
    );

    protected function _construct()
    {
        $this->_init('po_fpc/crawler_queue', 'id');
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $write = $this->_getWriteAdapter();
        $write->truncate($this->getTable('po_fpc/crawler_queue'));
        return $this;
    }
}