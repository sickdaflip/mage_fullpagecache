<?php

/**
 * Class Potato_FullPageCache_Model_Resource_Storage
 */
class Potato_FullPageCache_Model_Resource_Storage extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('po_fpc/storage', 'id');
    }

    /**
     * @param $id
     *
     * @return $this
     */
    public function removeByCacheId($id)
    {
        $write = $this->_getWriteAdapter();
        $where = $write->quoteInto('cache_id =?', $id);
        $write->delete($this->getTable('po_fpc/storage'), $where);
        return $this;
    }

    /**
     * @param $url
     *
     * @return $this
     */
    public function getIdsByUrl($url)
    {
        $read = $this->getReadConnection();
        $select = $read->select();
        $select->from($this->getTable('po_fpc/storage'), 'cache_id');
        $select->where('request_url = ?', $url);
        return $read->fetchCol($select->__toString());
    }

    /**
     * @param $tags
     * @param $mode
     *
     * @return mixed
     */
    public function getIdsByTags($tags, $mode)
    {
        $read = $this->getReadConnection();
        $select = $read->select();
        $select->from($this->getTable('po_fpc/storage'), 'cache_id');
        $select->where('tags = ?', $tags);
        if ($mode != Potato_FullPageCache_Model_Cache::MATCH_TAGS) {
            $select->orWhere('tags LIKE ?', $tags . '%');
            $select->orWhere('tags LIKE ?', '%' . $tags);
            $select->orWhere('tags LIKE ?', '%' . $tags . '%');
        }
        return $read->fetchCol($select->__toString());
    }

    /**
     * @param       $privateTag
     * @param string $tagKey
     *
     * @return mixed
     */
    public function getIdsByPrivateTag($privateTag, $tagKey='')
    {
        $read = $this->getReadConnection();
        $select = $read->select();
        $select->from($this->getTable('po_fpc/storage'), 'cache_id');
        $select->where('private_tag = ?', $privateTag);
        if ($tagKey) {
            $select->where('tags LIKE ?', $tagKey . '%');
        }
        return $read->fetchCol($select->__toString());
    }

    /**
     * @return $this
     */
    public function cleanStorageData()
    {
        $write = $this->_getWriteAdapter();
        $write->truncate($this->getTable('po_fpc/storage'));
        return $this;
    }

    /**
     * @return int
     */
    public function getStorageSize()
    {
        $read = $this->getReadConnection();
        $select = $read->select();
        $select
            ->from($this->getTable('po_fpc/storage'), new Zend_Db_Expr('SUM(size)'))
        ;
        return (int)$read->fetchOne($select->__toString());
    }

    /**
     * @return mixed
     */
    public function getExpireIds()
    {
        $read = $this->getReadConnection();
        $select = $read->select();
        $select
            ->from($this->getTable('po_fpc/storage'), 'cache_id')
            ->where('expire <= ?', time())
        ;
        return $read->fetchCol($select->__toString());
    }

    /**
     * @return array
     */
    public function getRegisteredCacheTags()
    {
        $read = $this->getReadConnection();
        $select = $read->select();
        $select
            ->from($this->getTable('po_fpc/storage'), 'tags')
            ->group('tags')
        ;
        $tags = $read->fetchCol($select->__toString());
        $result = array();
        foreach ($tags as $tagHash) {
            $tagGroup = explode(Potato_FullPageCache_Helper_CacheStorage::TAGS_KEY_SEPARATOR, $tagHash);
            foreach ($tagGroup as $tag) {
                if (in_array($tag, $result)) {
                    continue;
                }
                array_push($result, $tag);
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        $result = array();
        foreach ($this->getCommonPageTags() as $_pageInfo) {
            $tags = explode(Potato_FullPageCache_Helper_CacheStorage::TAGS_KEY_SEPARATOR, $_pageInfo['tags']);
            $skipTags = array(
                Potato_FullPageCache_Model_Cache::PRODUCT_TAG,
                Potato_FullPageCache_Model_Cache::CMS_TAG,
                Potato_FullPageCache_Model_Cache::CATEGORY_TAG
            );
            $index = '';
            $storeId = $store = null;
            $customerGroupId = null;
            $isMobile = Mage::helper('po_fpc')->__('No');
            $isSecure = Mage::helper('po_fpc')->__('No');
            $currency = null;
            foreach ($tags as $tag) {
                if (in_array($tag , $skipTags)) {
                    continue;
                }
                if (strpos($tag, Potato_FullPageCache_Model_Cache_Page::STORE_ID_TAG_PREFIX) !== FALSE) {
                    preg_match('!\d+!', $tag, $matches);
                    $storeId = $matches[0];
                    try {
                        $store = Mage::app()->getStore($storeId);
                    } catch (Exception $e) {
                        continue;
                    }
                } else if (strpos($tag, Potato_FullPageCache_Model_Cache_Page::CUSTOMER_GROUP_ID_TAG_PREFIX) !== FALSE) {
                    preg_match('!\d+!', $tag, $matches);
                    $customerGroupId = $matches[0];
                } else if ($tag == Potato_FullPageCache_Model_Cache_Page::HTTPS_TAG) {
                    $isSecure = Mage::helper('po_fpc')->__('Yes');
                } else if ($tag == Potato_FullPageCache_Model_Cache_Page::MOBILE_TAG) {
                    $isMobile = Mage::helper('po_fpc')->__('Yes');
                } else if ($store) {
                    $_currencyCodes = $store->getAvailableCurrencyCodes(true);
                    if (in_array($tag, $_currencyCodes)) {
                        $currency = $tag;
                    }
                }
                if ($tag == Potato_FullPageCache_Model_Cache_Page::PHONE_TAG) {
                    $isMobile = Mage::helper('po_fpc')->__('Yes (Phone)');
                }
                if ($tag == Potato_FullPageCache_Model_Cache_Page::TABLET_TAG) {
                    $isMobile = Mage::helper('po_fpc')->__('Yes (Tablet)');
                }
                if (!$index) {
                    $index = $tag;
                    continue;
                }
                $index .= Potato_FullPageCache_Helper_CacheStorage::TAGS_KEY_SEPARATOR . $tag;
            }
            $item = array();
            if (array_key_exists($index, $result)) {
                $item = $result[$index];
            }
            if (!array_key_exists('store_name', $item) && $store !== null) {
                $item['store_name'] = $store->getName();
            }
            if (!array_key_exists('group_name', $item) && $customerGroupId !== null) {
                $_group = Mage::getModel('customer/group')->load($customerGroupId);
                $item['group_name'] = $_group->getCode();
            }
            if (!array_key_exists('currency', $item) && $currency !== null) {
                $item['currency'] = $currency;
            }
            if (!array_key_exists('is_secure', $item)) {
                $item['is_secure'] = $isSecure;
            }
            if (!array_key_exists('is_mobile', $item)) {
                $item['is_mobile'] = $isMobile;
            }
            if (!array_key_exists('total', $item) && $storeId !== null) {
                if (Potato_FullPageCache_Helper_Config::getCrawlerSource($storeId) == Potato_FullPageCache_Model_Source_CrawlerSource::SITEMAP_VALUE) {
                    $item['total'] = count(Mage::helper('po_fpc')->getSitemapUrls($storeId));
                } else {
                    $item['total'] = Mage::getResourceModel('po_fpc/crawler')->getTotalRequestPaths($storeId);
                }
            }
            $progress = $_pageInfo['progress'];
            if (array_key_exists('progress', $item)) {
                $progress += $item['progress'];
            }
            $item['progress'] = min($progress, $item['total']);
            $size = $_pageInfo['size'];
            if (array_key_exists('size', $item)) {
                $size += $item['size'];
            }
            $item['size'] = $size;
            if (!array_key_exists('tags', $item)) {
                $item['tags'] = $index;
            }
            $result[$index] = $item;
        }
        return $result;
    }

    /**
     * @return mixed
     */
    public function getCommonPageTags()
    {
        $read = $this->getReadConnection();
        $select = $read->select();
        $select
            ->from(array('main_table' => $this->getTable('po_fpc/storage')),
                array(
                    'size'     => new Zend_Db_Expr('SUM((SELECT SUM(size) AS size  FROM ' . $this->getTable('po_fpc/storage') . ' WHERE request_url = main_table.request_url))'),
                    'progress' => new Zend_Db_Expr('COUNT(main_table.id)'),
                    'tags'     => 'tags'
                )
            )
            ->where('private_tag !=?', '')
            ->where('tags NOT LIKE ?', Potato_FullPageCache_Model_Cache::BLOCK_TAG . '%')
            ->group('tags')
        ;
        return $read->fetchAll($select);
    }
}