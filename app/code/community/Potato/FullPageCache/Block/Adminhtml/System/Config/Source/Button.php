<?php

class Potato_FullPageCache_Block_Adminhtml_System_Config_Source_Button
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);

        $html = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel($this->__('Clear Queue'))
            ->setOnClick("setLocation('" . $this->getUrl('adminhtml/potato_queue/clear') . "')")
            ->toHtml()
        ;
        return $html;
    }
}