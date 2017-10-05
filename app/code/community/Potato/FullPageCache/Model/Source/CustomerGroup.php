<?php

/**
 * Class Potato_FullPageCache_Model_Source_CustomerGroup
 */
class Potato_FullPageCache_Model_Source_CustomerGroup
{
    protected $_options;

    /**
     * @return mixed
     */
    public function toOptionArray()
    {
        if (!$this->_options) {
            $this->_options = Mage::getResourceModel('customer/group_collection')
                ->loadData()
                ->toOptionArray()
            ;
        }
        return $this->_options;
    }
}