<?php

class Potato_FullPageCache_Model_Source_Backend_Serialized extends
    Mage_Adminhtml_Model_System_Config_Backend_Serialized_Array
{
    protected function _afterLoad()
    {
        $value = $this->getValue();
        if ($value instanceof Mage_Core_Model_Config_Element) {
            $value = $value->asArray();
            $this->setValue($value);
        }
        return parent::_afterLoad();
    }
}