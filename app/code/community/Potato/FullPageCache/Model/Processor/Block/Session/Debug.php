<?php

class Potato_FullPageCache_Model_Processor_Block_Session_Debug
    extends Potato_FullPageCache_Model_Processor_Block_Session
{
    const ID = 'Potato_FullPageCache_Model_Processor_Block_Session_Debug';
    /**
     * @return null|string
     */
    public function getId()
    {
        return self::ID;
    }

    /**
     * @param Mage_Core_Block_Abstract $block
     *
     * @return mixed
     */
    public function getBlockHtml(Mage_Core_Block_Abstract $block)
    {
        if (!Potato_FullPageCache_Helper_Data::isDebugModeEnabled()) {
            return '';
        }
        return $block->setCacheHit(true)
            ->toHtml()
        ;
    }

    /**
     * @param string $cachedHtml
     *
     * @return string
     */
    public function getPreparedHtmlFromCache($cachedHtml)
    {
        if (!Potato_FullPageCache_Helper_Data::isDebugModeEnabled()) {
            return '';
        }
        return $cachedHtml;
    }


    /**
     * ignore cache flag
     *
     * @return bool
     */
    public function getIsIgnoreCache()
    {
        if (Potato_FullPageCache_Helper_Data::isDebugModeEnabled()) {
            return true;
        }
        return false;
    }
}