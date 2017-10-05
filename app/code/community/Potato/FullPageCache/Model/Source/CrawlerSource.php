<?php

class Potato_FullPageCache_Model_Source_CrawlerSource
{
    const DATABASE_VALUE  = 1;
    const SITEMAP_VALUE   = 2;
    const DATABASE_LABEL  = 'Database';
    const SITEMAP_LABEL   = 'Sitemap';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return array (
            self::DATABASE_VALUE => Mage::helper('po_fpc')->__(self::DATABASE_LABEL),
            self::SITEMAP_VALUE  => Mage::helper('po_fpc')->__(self::SITEMAP_LABEL)
        );
    }
}