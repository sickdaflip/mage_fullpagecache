<?php

/**
 * Class Potato_FullPageCache_Model_Source_AutoClean
 */
class Potato_FullPageCache_Model_Source_AutoClean
{
    const PRODUCT_SAVE_VALUE         = 1;
    const PRODUCT_DELETE_VALUE       = 2;
    const CATEGORY_SAVE_VALUE        = 3;
    const CATEGORY_DELETE_VALUE      = 4;
    const CMS_SAVE_VALUE             = 5;
    const CMS_DELETE_VALUE           = 6;
    const REVIEW_SAVE_VALUE          = 7;
    const INVENTORY_VALUE            = 8;
    const PRODUCT_ATTRIBUTE_VALUE    = 9;
    const CATALOG_IMAGES_CACHE_VALUE = 10;
    const MEDIA_CACHE_VALUE          = 11;
    const CURRENCY_SAVE_VALUE        = 12;
    const CURRENCY_SYMBOL_VALUE      = 13;
    const CATALOG_RULE_SAVE_VALUE    = 14;

    const PRODUCT_SAVE_LABEL         = 'Product Update';
    const PRODUCT_DELETE_LABEL       = 'Product Delete';
    const CATEGORY_SAVE_LABEL        = 'Category Update';
    const CATEGORY_DELETE_LABEL      = 'Category Delete';
    const CMS_SAVE_LABEL             = 'Cms Update';
    const CMS_DELETE_LABEL           = 'Cms Delete';
    const REVIEW_SAVE_LABEL          = 'Review Update';
    const INVENTORY_LABEL            = 'Inventory Update';
    const PRODUCT_ATTRIBUTE_LABEL    = 'Product Attribute Update';
    const CATALOG_IMAGES_CACHE_LABEL = 'Flush Catalog Images Cache';
    const MEDIA_CACHE_LABEL          = 'Flush JavaScript/CSS Cache';
    const CURRENCY_SAVE_LABEL        = 'Store Currency Update';
    const CURRENCY_SYMBOL_LABEL      = 'Currency Symbol Update';
    const CATALOG_RULE_SAVE_LABEL    = 'Catalog Rule Update';

    /**
     * @return mixed
     */
    public function toOptionArray()
    {
        return array(
            array (
                'label' => Mage::helper('po_fpc')->__(self::PRODUCT_SAVE_LABEL),
                'value' => self::PRODUCT_SAVE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::PRODUCT_DELETE_LABEL),
                'value' => self::PRODUCT_DELETE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::CATEGORY_SAVE_LABEL),
                'value' => self::CATEGORY_SAVE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::CATEGORY_DELETE_LABEL),
                'value' => self::CATEGORY_DELETE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::CMS_SAVE_LABEL),
                'value' => self::CMS_SAVE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::CMS_DELETE_LABEL),
                'value' => self::CMS_DELETE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::REVIEW_SAVE_LABEL),
                'value' => self::REVIEW_SAVE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::INVENTORY_LABEL),
                'value' => self::INVENTORY_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::PRODUCT_ATTRIBUTE_LABEL),
                'value' => self::PRODUCT_ATTRIBUTE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::CATALOG_IMAGES_CACHE_LABEL),
                'value' => self::CATALOG_IMAGES_CACHE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::MEDIA_CACHE_LABEL),
                'value' => self::MEDIA_CACHE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::CURRENCY_SAVE_LABEL),
                'value' => self::CURRENCY_SAVE_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::CURRENCY_SYMBOL_LABEL),
                'value' => self::CURRENCY_SYMBOL_VALUE
            ),
            array (
                'label' => Mage::helper('po_fpc')->__(self::CATALOG_RULE_SAVE_LABEL),
                'value' => self::CATALOG_RULE_SAVE_VALUE
            )
        );
    }
}