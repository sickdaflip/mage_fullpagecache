<?php

class Potato_FullPageCache_Model_Processor_Block_Default
{
    protected $_blockCache = null;

    public function __construct(Potato_FullPageCache_Model_Cache_Page_Block $block)
    {
        $this->_blockCache = $block;
    }

    public function getBlockCache()
    {
        return $this->_blockCache;
    }

    /**
     * @param Mage_Core_Block_Abstract $block
     *
     * @return string
     */
    public function getBlockHtml(Mage_Core_Block_Abstract $block)
    {
        return $block->toHtml();
    }

    /**
     * @param string $cachedHtml
     *
     * @return string
     */
    public function getPreparedHtmlFromCache($cachedHtml)
    {
        return $cachedHtml;
    }

    /**
     * ignore cache flag
     *
     * @return bool
     */
    public function getIsIgnoreCache()
    {
        return false;
    }
}