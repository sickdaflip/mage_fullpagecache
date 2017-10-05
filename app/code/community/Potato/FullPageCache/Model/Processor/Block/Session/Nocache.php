<?php

/**
 * Class Potato_FullPageCache_Model_Processor_Block_Session_Nocache
 */
class Potato_FullPageCache_Model_Processor_Block_Session_Nocache
    extends Potato_FullPageCache_Model_Processor_Block_Session
{
    /**
     * ignore cache flag
     *
     * @return bool
     */
    public function getIsIgnoreCache()
    {
        return true;
    }
}