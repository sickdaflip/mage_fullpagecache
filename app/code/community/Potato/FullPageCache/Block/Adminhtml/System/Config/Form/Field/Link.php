<?php

class Potato_FullPageCache_Block_Adminhtml_System_Config_Form_Field_Link extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    /**
     * @param Varien_Data_Form_Element_Abstract $element
     *
     * @return string
     */
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        /* @var $fieldConfig Varien_Simplexml_Element */
        $fieldConfig = $element->getData('field_config');
        $fieldConfigArray = $fieldConfig->asArray();

        if (array_key_exists('href', $fieldConfigArray)) {
            $element->setData('href', $fieldConfigArray['href']);
        }
        if (array_key_exists('target', $fieldConfigArray)) {
            $element->setData('target', $fieldConfigArray['target']);
        }
        $element->setScope(null);
        return parent::_getElementHtml($element);
    }
}