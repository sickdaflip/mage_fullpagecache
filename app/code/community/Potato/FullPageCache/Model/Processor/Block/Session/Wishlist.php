<?php

/**
 * Class Potato_FullPageCache_Model_Processor_Block_Session_Wishlist
 */
class Potato_FullPageCache_Model_Processor_Block_Session_Wishlist
    extends Potato_FullPageCache_Model_Processor_Block_Session
{
    public function isAllowEvent($eventName)
    {
        if ($eventName == Potato_FullPageCache_Model_Observer_Updater::EVENT_WISHLIST) {
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