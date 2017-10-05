<?php

/**
 * Class Potato_FullPageCache_Model_Observer_Updater
 */
class Potato_FullPageCache_Model_Observer_Updater
{
    const EVENT_LOGIN        = 1;
    const EVENT_COMPARE      = 2;
    const EVENT_PRODUCT_VIEW = 3;
    const EVENT_CART         = 4;
    const EVENT_VOTE         = 5;
    const EVENT_WISHLIST     = 6;
    const EVENT_MESSAGE      = 7;

    public function login()
    {
        return $this->_update(self::EVENT_COMPARE);
    }

    public function cartUpdate()
    {
        return $this->_update(self::EVENT_CART);
    }

    public function compare()
    {
        return $this->_update(self::EVENT_COMPARE);
    }

    public function wishlist()
    {
        return $this->_update(self::EVENT_WISHLIST);
    }

    public function productViewUpdater()
    {
        $blockCache = Potato_FullPageCache_Model_Cache::getPageCache(true)->getBlockCache();
        $sessionBlocks = $blockCache->getSessionBlocks();
        foreach ($sessionBlocks as $index => $blockData) {
            $blockProcessor = $blockCache->getBlockCacheProcessor($index);
            if (!$blockProcessor instanceof Potato_FullPageCache_Model_Processor_Block_Session_Viewed) {
                continue;
            }

            try {
                $blockProcessor
                    ->remove($index)
                ;
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }

    public function messagesUpdate()
    {
        return $this->_update(self::EVENT_MESSAGE);
    }

    public function productView()
    {
        return $this->_update(self::EVENT_PRODUCT_VIEW);
    }

    public function pollVoteAdd()
    {
        return $this->_update(self::EVENT_VOTE);
    }

    protected function _update($eventName)
    {
        if (!Mage::app()->useCache('po_fpc')) {
            return $this;
        }

        $handles = Mage::app()->getLayout()->getUpdate()->getHandles();
        if (empty($handles) && Mage::app()->getRequest()->getModuleName() != 'ajaxcart' && Mage::app()->getRequest()->getModuleName() != 'ajax') {
            $_layout = Mage::app()->getLayout();
            if (!in_array('default', $_layout->getUpdate()->getHandles())) {
                Mage::register('render_messages_denied_flag', 1, true);
                $_layout->getUpdate()->merge('default');
                Potato_FullPageCache_Helper_Compatibility::useEMThemeBeforeLayoutGenerateBlocks($_layout);
                $_layout
                    ->generateXml()
                    ->generateBlocks()
                ;
                Potato_FullPageCache_Helper_Compatibility::useEMThemeAfterLayoutGenerateBlocks($_layout);
            }
        }

        $blockCache = Potato_FullPageCache_Model_Cache::getPageCache(true)->getBlockCache();
        $sessionBlocks = $blockCache->getSessionBlocks();
        foreach ($sessionBlocks as $index => $blockData) {
            $blockProcessor = $blockCache->getBlockCacheProcessor($index);
            try {
                $blockProcessor
                    ->update($index, $blockData, $eventName)
                ;
            } catch (Exception $e) {
                Mage::logException($e);
            }
        }
        return $this;
    }
}