<?php

class Potato_FullPageCache_Adminhtml_Potato_QueueController extends Mage_Adminhtml_Controller_Action
{
    public function clearAction()
    {
        try {
            Mage::getResourceModel('po_fpc/crawler_queue')->clear();
            $this->_getSession()->addSuccess(
                $this->__('Queue has been cleared.')
            );
        } catch (Exception $e) {
            $this->_getSession()->addError($e->getMessage());
        }
        $this->_redirectReferer();
    }

    protected function _isAllowed()
    {
        return true;
    }
}