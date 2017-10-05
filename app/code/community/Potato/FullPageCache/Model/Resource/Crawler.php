<?php

/**
 * Class Potato_FullPageCache_Model_Resource_Crawler
 */
class Potato_FullPageCache_Model_Resource_Crawler extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('core/url_rewrite', 'url_rewrite_id');
    }

    /**
     * @param $storeId
     * @param $limit
     * @param $offset
     *
     * @return mixed
     */
    public function getRequestPaths($storeId, $limit=null, $offset=null)
    {
        return $this->_getReadAdapter()->fetchAll($this->_getRequestSelect($storeId, $limit, $offset));
    }

    /**
     * @param $storeId
     *
     * @return int
     */
    public function getTotalRequestPaths($storeId)
    {
        return $this->getTotalRewrites($storeId) + $this->getTotalCmsPages($storeId);
    }

    /**
     * @param $storeId
     *
     * @return int
     */
    public function getTotalRewrites($storeId)
    {
        $idsSelect = $this->_getRequestSelect($storeId);
        $idsSelect->reset(Zend_Db_Select::ORDER);
        $idsSelect->reset(Zend_Db_Select::LIMIT_COUNT);
        $idsSelect->reset(Zend_Db_Select::LIMIT_OFFSET);
        $idsSelect->reset(Zend_Db_Select::COLUMNS);
        $idsSelect->columns(new Zend_Db_Expr('COUNT(url_rewrite_id)'));
        return $this->_getReadAdapter()->fetchOne($idsSelect);
    }

    /**
     * @param $storeId
     *
     * @return int
     */
    protected function _getDisabledCategoriesIds($storeId)
    {
        $categories = Mage::getModel('catalog/category')
            ->setStoreId($storeId)
            ->getCollection()
            ->addFieldToFilter('is_active','0')
        ;
        return $categories->getAllIds();
    }

    /**
     * @param $storeId
     *
     * @return int
     */
    protected function _getDisabledProductsIds($storeId)
    {
        $products = Mage::getModel('catalog/product')
            ->setStoreId($storeId)
            ->getCollection()
            ->addAttributeToFilter(
                array(
                    array('attribute'=> 'status', 'eq' => Mage_Catalog_Model_Product_Status::STATUS_DISABLED),
                    array('attribute'=> 'visibility', 'eq' => Mage_Catalog_Model_Product_Visibility::VISIBILITY_NOT_VISIBLE)
                )
            )
        ;
        return $products->getAllIds();
    }

    /**
     * @param $storeId
     *
     * @return int
     */
    public function getTotalCmsPages($storeId)
    {
        return count($this->getCmsUrls($storeId));
    }

    /**
     * @param $storeInfo
     * @param $url
     *
     * @return bool
     */
    public function isReadyCached($storeInfo, $url)
    {
        $url = str_replace('/index.php', '', $url);
        list($device, $deviceType) = Potato_FullPageCache_Helper_Data::getDeviceInfo($storeInfo['useragent']);
        $tags = array(
            Potato_FullPageCache_Model_Cache_Page::STORE_ID_TAG_PREFIX . $storeInfo[Potato_FullPageCache_Model_Cache::STORE_COOKIE_NAME],
            Potato_FullPageCache_Model_Cache_Page::CUSTOMER_GROUP_ID_TAG_PREFIX . $storeInfo[Potato_FullPageCache_Model_Cache::CUSTOMER_GROUP_ID_COOKIE_NAME],
            $storeInfo[Potato_FullPageCache_Model_Cache::CURRENCY_COOKIE_NAME],
            $device,
            $storeInfo['is_secure'] ? Potato_FullPageCache_Model_Cache_Page::HTTPS_TAG : Potato_FullPageCache_Model_Cache_Page::HTTP_TAG
        );
        if ($deviceType) {
            array_push($tags, $deviceType);
        }
        $read = $this->_getReadAdapter();
        $select = $read->select();
        $select->from($this->getTable('po_fpc/storage'), 'cache_id');
        $select->where('tags LIKE ?', '%' . Potato_FullPageCache_Helper_CacheStorage::getKeyByTags($tags));
        $select->where('request_url =?', $url);
        return (bool)$read->fetchOne($select->__toString());
    }

    /**
     * @param      $storeId
     * @param null $limit
     * @param null $offset
     *
     * @return mixed
     */
    protected function _getRequestSelect($storeId, $limit=null, $offset=null)
    {
        if (@class_exists('Enterprise_UrlRewrite_Model_Resource_Url_Rewrite', false)) {
            return $this->_getEERequestSelect($storeId, $limit, $offset);
        }
        $select =
            $this->_getReadAdapter()->select()
                ->from(
                    $this->getTable('core/url_rewrite')
                    ,array('store_id', 'request_path')
                )
                ->where('store_id=?', $storeId)
                ->where('is_system=1')
                ->limit($limit, $offset)
        ;
        $disabledProductsIds = $this->_getDisabledProductsIds($storeId);
        if (count($disabledProductsIds) > 0) {
            $select->where('product_id NOT IN(' . implode(',', $disabledProductsIds) . ') OR product_id IS NULL');
        }
        $disabledCategoriesIds = $this->_getDisabledCategoriesIds($storeId);
        if (count($disabledCategoriesIds) > 0) {
            $select->where('category_id NOT IN(' . implode(',', $disabledCategoriesIds) . ') OR category_id IS NULL');
        }
        return $select;
    }

    /**
     * @param $storeId
     * @param $limit
     * @param $offset
     *
     * @return mixed
     */
    protected function _getEERequestSelect($storeId, $limit=null, $offset=null)
    {
        $store = Mage::app()->getStore($storeId);

        $rootCategoryId = $store->getRootCategoryId();

        $selectProduct = $this->_getReadAdapter()->select()
            ->from(array('url_product_default' => $this->getTable('enterprise_catalog/product')), array(''))
            ->joinInner(array('url_rewrite' => $this->getTable('enterprise_urlrewrite/url_rewrite')),
                'url_rewrite.url_rewrite_id = url_product_default.url_rewrite_id',
                array('request_path', 'entity_type')
            )
            ->joinInner(array('cp' => $this->getTable('catalog/category_product_index')),
                'url_product_default.product_id = cp.product_id',
                array('category_id')
            )
            ->where('url_rewrite.entity_type = ?', Enterprise_Catalog_Model_Product::URL_REWRITE_ENTITY_TYPE)
            ->where('cp.store_id = ?', (int) $storeId)
            ->where('cp.category_id != ?', (int) $rootCategoryId)
            ->limit($limit, $offset)
        ;
        $disabledProductsIds = $this->_getDisabledProductsIds($storeId);
        if (count($disabledProductsIds) > 0) {
            $selectProduct->where('product_id NOT IN(' . implode(',', $disabledProductsIds) . ') OR product_id IS NULL');
        }

        $selectCategory = $this->_getReadAdapter()->select()
            ->from(array('url_rewrite' => $this->getTable('enterprise_urlrewrite/url_rewrite')),
                array(
                    'request_path',
                    'entity_type',
                    'category_id' => new Zend_Db_Expr('NULL'),
                )
            )
            ->where('url_rewrite.store_id = ?', $storeId)
            ->where('url_rewrite.entity_type = ?', Enterprise_Catalog_Model_Category::URL_REWRITE_ENTITY_TYPE)
            ->limit($limit, $offset)
        ;
        $disabledCategoriesIds = $this->_getDisabledCategoriesIds($storeId);
        if (count($disabledCategoriesIds) > 0) {
            $selectCategory->where('category_id NOT IN(' . implode(',', $disabledCategoriesIds) . ') OR category_id IS NULL');
        }
        $select = $this->_getReadAdapter()->select()
            ->union(array('(' . $selectProduct . ')', '(' . $selectCategory . ')'))
        ;
        return $select;
    }

    /**
     * @param $storeId
     *
     * @return mixed
     */
    public function getCmsUrls($storeId)
    {
        $cms = $this->_getReadAdapter()->select()
            ->from(array('cms' => $this->getTable('cms/page')), array('identifier'))
            ->join(array('store' => $this->getTable('cms/page_store')),
                'cms.page_id = store.page_id',
                array()
            )
            ->where('store.store_id IN (?)', array(0, $storeId))
            ->where('cms.identifier !=?', 'no-route')
            ->where('cms.is_active =?', '1')
        ;
        return $this->_getReadAdapter()->fetchAll($cms);
    }

    /**
     * @param $storeId
     * @param null $limit
     * @param null $offset
     * @return array
     */
    public function getPopularUrls($storeId, $limit=null, $offset=null)
    {
        $select = $this->_getReadAdapter()->select()
            ->from(array('main_table' => $this->getTable('po_fpc/popularity')),
                array(
                    'url'
                )
            )
            ->where('main_table.store_id =?', $storeId)
            ->order('main_table.views ' . Zend_Db_Select::SQL_DESC)
            ->limit($limit, $offset)
        ;
        return $this->_getReadAdapter()->fetchCol($select);
    }
}