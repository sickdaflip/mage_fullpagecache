<?php

class Potato_FullPageCache_Model_Crawler_Queue extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('po_fpc/crawler_queue', 'id');
    }

    public function loadByUrl($url)
    {
        return $this->load($url, 'url');
    }
}