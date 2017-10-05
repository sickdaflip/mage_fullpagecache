<?php

class Potato_FullPageCache_Model_Core_Session extends Mage_Core_Model_Session
{
    const STATIC_FORM_KEY = 'zdTac9RCvERFk4zw';

    public function getFormKey()
    {
        if (!Mage::app()->getStore()->isAdmin() &&
            Mage::app()->useCache('po_fpc') &&
            (
                Mage::app()->getRequest()->getModuleName() == 'catalog' ||
                Mage::app()->getRequest()->getModuleName() == 'cms' ||
                (Mage::app()->getRequest()->getModuleName() == 'checkout' && Mage::app()->getRequest()->getControllerName() == 'cart') ||
                Mage::app()->getRequest()->getModuleName() == 'review' ||
                Mage::app()->getRequest()->getModuleName() == 'ajax' ||
                Mage::app()->getRequest()->getModuleName() == 'newsletter'
            )
        ) {
            return self::STATIC_FORM_KEY;
        }
        return parent::getFormKey();
    }
}