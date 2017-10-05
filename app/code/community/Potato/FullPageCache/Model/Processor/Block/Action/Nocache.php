<?php

/**
 * Class Potato_FullPageCache_Model_Processor_Block_Action_Nocache
 */
class Potato_FullPageCache_Model_Processor_Block_Action_Nocache
    extends Potato_FullPageCache_Model_Processor_Block_Default
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