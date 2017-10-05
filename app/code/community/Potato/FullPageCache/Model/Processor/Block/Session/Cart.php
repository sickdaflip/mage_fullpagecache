<?php

/**
 * Class Potato_FullPageCache_Model_Processor_Block_Session_Cart
 */
class Potato_FullPageCache_Model_Processor_Block_Session_Cart
    extends Potato_FullPageCache_Model_Processor_Block_Session
{
    public function isAllowEvent($eventName)
    {
        if ($eventName == Potato_FullPageCache_Model_Observer_Updater::EVENT_CART ||
            $eventName == Potato_FullPageCache_Model_Observer_Updater::EVENT_LOGIN
        ) {
            return true;
        }
        return false;
    }
}