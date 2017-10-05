<?php

/**
 * Class Potato_FullPageCache_Model_Processor_Block_Session_Messages
 */
class Potato_FullPageCache_Model_Processor_Block_Session_Messages extends Potato_FullPageCache_Model_Processor_Block_Session
{
    protected $_messageStoreTypes = array(
        'core/session',
        'customer/session',
        'catalog/session',
        'checkout/session',
        'tag/session'
    );

    /**
     * @param Mage_Core_Block_Abstract $block
     *
     * @return mixed
     */
    public function getBlockHtml(Mage_Core_Block_Abstract $block)
    {
        foreach ($this->_messageStoreTypes as $type) {
            $this->_addMessagesToBlock($type, $block);
        }
        return parent::getBlockHtml($block);
    }

    /**
     * @param                          $messagesStorage
     * @param Mage_Core_Block_Messages $block
     *
     * @return $this
     */
    protected function _addMessagesToBlock($messagesStorage, Mage_Core_Block_Messages $block)
    {
        if ($storage = Mage::getSingleton($messagesStorage)) {
            $block->addMessages($storage->getMessages(true));
            $block->setEscapeMessageFlag($storage->getEscapeMessages(true));
        }
        return $this;
    }

    /**
     * @param $data
     * @param $index
     *
     * @return $this
     */
    public function save($data, $index)
    {
        //don't save message to cache
        $data['html'] = '';
        return parent::save($data, $index);
    }

    public function isAllowEvent($eventName)
    {
        if ($eventName == Potato_FullPageCache_Model_Observer_Updater::EVENT_MESSAGE) {
            return true;
        }
        return false;
    }

    public function update($index, $blockData, $eventName)
    {
        if (!$this->isAllowEvent($eventName)) {
            return false;
        }
        $this->remove($index);
        return $this;
    }
}