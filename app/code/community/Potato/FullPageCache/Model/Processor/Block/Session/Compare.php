<?php

/**
 * Class Potato_FullPageCache_Model_Processor_Block_Session_Compare
 */
class Potato_FullPageCache_Model_Processor_Block_Session_Compare
    extends Potato_FullPageCache_Model_Processor_Block_Session
{
    public function isAllowEvent($eventName)
    {
        if ($eventName == Potato_FullPageCache_Model_Observer_Updater::EVENT_COMPARE ||
            $eventName == Potato_FullPageCache_Model_Observer_Updater::EVENT_LOGIN
        ) {
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