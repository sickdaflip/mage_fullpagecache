<?php

class Potato_FullPageCache_Model_Processor_Block_Widget extends Potato_FullPageCache_Model_Processor_Block_Default
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