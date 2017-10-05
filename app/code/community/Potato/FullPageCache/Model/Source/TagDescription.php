<?php

/**
 * Class Potato_FullPageCache_Model_Source_TagDescription
 */
class Potato_FullPageCache_Model_Source_TagDescription
{
    const CMS           = '(All CMS Pages)';
    const BLOCK         = '(All Dynamic Blocks Of The Pages e.g. Product Availability)';
    const SESSION_BLOCK = '(All Dynamic Blocks Dependent Session e.g. Shopping Cart)';
    const CACHE_STORE   = '(Service Cache)';

    /**
     * @param string $tag
     *
     * @return string
     */
    public function getDescription($tag)
    {
        if ($tag == Potato_FullPageCache_Model_Cache::CMS_TAG) {
            return Mage::helper('po_fpc')->__(self::CMS);
        }
        if ($tag == Potato_FullPageCache_Model_Cache::BLOCK_TAG) {
            return Mage::helper('po_fpc')->__(self::BLOCK);
        }
        if ($tag == Potato_FullPageCache_Model_Cache::CACHE_STORE ||
            $tag == Potato_FullPageCache_Model_Cache::SYSTEM_TAG
        ) {
            return Mage::helper('po_fpc')->__(self::CACHE_STORE);
        }
        if ($tag == Potato_FullPageCache_Model_Cache::SESSION_BLOCK_TAG) {
            return Mage::helper('po_fpc')->__(self::SESSION_BLOCK);
        }
        if (strpos($tag, Potato_FullPageCache_Model_Cache_Page::STORE_ID_TAG_PREFIX) !== FALSE) {
            preg_match('!\d+!', $tag, $matches);
            $storeId = $matches[0];
            try {
                $store = Mage::app()->getStore($storeId);
                return '(' . $store->getName() . ')';
            } catch (Exception $e) {
                return 'DELETED STORE ID ' . $storeId;
            }
        }
        if (strpos($tag, Potato_FullPageCache_Model_Cache_Page::CUSTOMER_GROUP_ID_TAG_PREFIX) !== FALSE) {
            preg_match('!\d+!', $tag, $matches);
            $customerGroupId = $matches[0];
            $_group = Mage::getModel('customer/group')->load($customerGroupId);
            return '(' . $_group->getCode() . ')';
        }
        return '';
    }
}