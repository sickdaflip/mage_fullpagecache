<?php

/**
 * Class Potato_FullPageCache_Model_Source_Rule
 */
class Potato_FullPageCache_Model_Source_Rule
{
    const FILE_VALUE  = 1;
    const FORM_VALUE  = 2;
    const FILE_LABEL  = 'Config Files';
    const FORM_LABEL  = 'Textarea Below';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array (
            array (
                'value' => self::FILE_VALUE,
                'label' => Mage::helper('po_fpc')->__(self::FILE_LABEL)
            ),
            array (
                'value' => self::FORM_VALUE,
                'label' => Mage::helper('po_fpc')->__(self::FORM_LABEL)
            )
        );
    }
}