<?php

/**
 * Remove cache by tag action
 *
 * Class Potato_FullPageCache_Adminhtml_Potato_Storage_CacheController
 */
class Potato_FullPageCache_Adminhtml_Potato_Storage_CacheController extends Mage_Adminhtml_Controller_Action
{
    public function removeAction()
    {
        $tag = $this->getRequest()->getParam('tag', false);
        if ($tag) {
            try {
                Potato_FullPageCache_Model_Cache::cleanByTags(array($tag));
                Mage::getSingleton('adminhtml/session')->addSuccess(Mage::helper('po_fpc')->__('Cache by tag "%s" has been removed.', $tag));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirectReferer();
    }

    protected function _isAllowed()
    {
        return true;
    }
}