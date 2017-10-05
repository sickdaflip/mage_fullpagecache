<?php

/**
 * Class Potato_FullPageCache_Adminhtml_Potato_PageController
 */
class Potato_FullPageCache_Adminhtml_Potato_PageController extends Mage_Adminhtml_Controller_Action
{
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('system/cache');

        $this
            ->_title($this->__('System'))
            ->_title($this->__('Cache'))
            ->_title($this->__('PotatoCommerce - Full Page Cache'))
        ;
        return $this;
    }

    public function listAction()
    {
        $this->_initAction();
        $this
            ->_title($this->__('Pages'))
        ;
        $this->renderLayout();
    }

    public function indexAction()
    {
        $this->_forward('list');
    }

    public function deleteallAction()
    {
        $ids = $this->getRequest()->getParam('ids', array());
        if (!empty($ids)) {
            try {
                foreach ($ids as $id) {
                    Mage::getModel('po_fpc/popularity')
                        ->load($id)
                        ->delete()
                    ;
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) have been deleted.', count($ids))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirectReferer();
    }

    protected function _isAllowed()
    {
        return true;
    }
}