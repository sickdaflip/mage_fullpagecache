<?php

class Potato_FullPageCache_Block_Adminhtml_Page extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller = 'adminhtml_page';
        $this->_blockGroup = 'po_fpc';
        $this->_headerText = $this->__('Pages');
        parent::__construct();
        $this->removeButton('add');
    }
}