<?php

/**
 * Render cache tag links at System -> Cache Management
 *
 * Class Potato_FullPageCache_Block_Adminhtml_Cache_Additional
 */
class Potato_FullPageCache_Block_Adminhtml_Cache_Additional extends Mage_Adminhtml_Block_Template
{
    protected $_infoItems = null;

    const LINKS_SEPARATOR = ', ';

    /**
     * @return string
     */
    public function getRegisteredCacheTags()
    {
        return Mage::getResourceModel('po_fpc/storage')->getRegisteredCacheTags();
    }

    /**
     * Remove Cache By Tag Url
     *
     * @param $tag
     *
     * @return string
     */
    public function getRemoveTagUrl($tag)
    {
        return $this->getUrl('adminhtml/potato_storage_cache/remove', array('tag' => $tag));
    }

    /**
     * Get Cache Detailed Information
     *
     * @return null|array
     */
    public function getInfoItems()
    {
        if (null === $this->_infoItems) {
            $this->_infoItems = Mage::getResourceModel('po_fpc/storage')->getInfo();
        }
        return $this->_infoItems;
    }

    /**
     * Cache Tag Description
     *
     * @param $tag
     * @return mixed
     */
    public function getDescription($tag)
    {
        return Mage::getModel('po_fpc/source_tagDescription')->getDescription($tag);
    }

    /**
     * Hit/Miss statistics
     *
     * @return string
     */
    public function getCacheMissData()
    {
        $statistics = Mage::getModel('po_fpc/statistics')->getCollection()->getCacheMiss();
        $result = array();
        foreach ($statistics as $date => $data) {
            $date = Mage::app()->getLocale()->date(strtotime($date), null, null);
            $result[] = array(
                $date->toString('MMM d, h:m a'),
                intval($data['cached']),
                intval($data['miss']),
            );
        }
        return $result;
    }
}