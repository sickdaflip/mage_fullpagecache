<?php

/**
 * Class Potato_FullPageCache_Model_Source_Sort
 */
class Potato_FullPageCache_Model_Source_Sort
{
    const DEFAULT_VALUE  = 1;
    const VIEWS_VALUE    = 2;
    const POSITION_VALUE = 3;
    const DEFAULT_LABEL  = 'Default ("core_url_rewrite" table)';
    const VIEWS_LABEL    = 'Page Views';

    public function toOptionArray()
    {
        return array (
            array (
                'value' => self::DEFAULT_VALUE,
                'label' => Mage::helper('po_fpc')->__(self::DEFAULT_LABEL)
            ),
            array (
                'value' => self::VIEWS_VALUE,
                'label' => Mage::helper('po_fpc')->__(self::VIEWS_LABEL)
            )
        );
    }
}