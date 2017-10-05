<?php

class Potato_FullPageCache_Block_Adminhtml_System_Config_Form_Field_Useragent
    extends Mage_Adminhtml_Block_System_Config_Form_Field_Array_Abstract
{
    public function __construct()
    {
        $this->addColumn('title', array(
            'label' => Mage::helper('adminhtml')->__('Title'),
            'style' => 'width:120px',
        ));
        $this->addColumn('useragent', array(
            'label' => Mage::helper('adminhtml')->__('User Agent'),
            'style' => 'width:120px',
        ));
        $this->_addAfter = false;
        $this->_addButtonLabel = Mage::helper('adminhtml')->__('Add');
        parent::__construct();
    }
}