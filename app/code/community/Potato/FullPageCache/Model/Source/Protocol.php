<?php

/**
 * Class Potato_FullPageCache_Model_Source_Protocol
 */
class Potato_FullPageCache_Model_Source_Protocol
{
    const HTTP_VALUE  = 'http';
    const HTTPS_VALUE = 'https';
    const HTTP_LABEL  = 'HTTP';
    const HTTPS_LABEL = 'HTTPS';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array (
            array (
                'value' => self::HTTP_VALUE,
                'label' => Mage::helper('po_fpc')->__(self::HTTP_LABEL)
            ),
            array (
                'value' => self::HTTPS_VALUE,
                'label' => Mage::helper('po_fpc')->__(self::HTTPS_LABEL)
            )
        );
    }
}