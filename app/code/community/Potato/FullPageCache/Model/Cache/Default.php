<?php

/**
 * Main cache instance
 *
 * Class Potato_FullPageCache_Model_Cache_Default
 */
class Potato_FullPageCache_Model_Cache_Default extends Mage_Core_Model_Cache
{
    protected $_id   = null;
    protected $_tags = array();

    /**
     * Init cache
     *
     * @param $cacheInitOptions
     */
    public function __construct($cacheInitOptions)
    {
        //get website cache config for setup cache processor
        $options = Mage::app()->getConfig()->getNode('global/full_page_cache');
        if ($options) {
            $options = $options->asArray();
        } else {
            $options = array();
        }
        $options = array_merge($cacheInitOptions, $options);
        if (!array_key_exists('backend_options', $options)) {
            $options['backend_options'] = array();
        }
        $options['backend_options']['cache_dir'] = self::getRootDir();
        Mage::app()->getConfig()->getOptions()->createDirIfNotExists(self::getRootDir());

        if (!array_key_exists('frontend_options', $options)) {
            $options['frontend_options'] = array();
        }
        $options['frontend_options']['automatic_serialization'] = true;
        parent::__construct($options);
    }

    /**
     * Add cache tag
     *
     * @param $tag
     *
     * @return $this
     */
    public function addTag($tag)
    {
        array_push($this->_tags, $tag);
        return $this;
    }

    /**
     * @param string $content
     * @param null   $id
     * @param array  $tags
     * @param bool   $lifetime
     * @param string $privateTag
     *
     * @return $this|bool
     */
    public function save($content, $id = null, $tags = array(), $lifetime = false, $privateTag = '')
    {
        if (null === $id) {
            $id = $this->getId();
        }
        $content = $this->_gzcompress($content);

        //check size limit
        $cacheSize = Potato_FullPageCache_Helper_CacheStorage::calculateSize($content);
        if (!Potato_FullPageCache_Helper_CacheStorage::getIsAllowedCacheSize($cacheSize)) {
            //Potato_FullPageCache_Model_Cache::cleanExpire();
            return $this;
        }

        //save cache
        $this->getFrontend()->save($content, $id, $tags, $lifetime);

        //load metadata
        $metadata = $this->getFrontend()->getMetadatas($id);

        //store cache info to storage
        $metadataSize = Potato_FullPageCache_Helper_CacheStorage::calculateSize($metadata);
        Potato_FullPageCache_Helper_CacheStorage::registerCache($this, $metadata['expire'], $tags, $cacheSize + $metadataSize, $privateTag);
        return $this;
    }

    /**
     * Compress saved content
     *
     * @param $content
     *
     * @return string
     */
    protected function _gzcompress($content)
    {
        if (is_string($content) && function_exists('gzcompress')) {
            //compress content
            $content = gzcompress($content);
        }
        return $content;
    }

    /**
     * Uncompress saved content
     *
     * @param $content
     *
     * @return string
     */
    protected function _gzuncompress($content)
    {
        if (is_string($content) && function_exists('gzuncompress')) {
            $content = gzuncompress($content);
        }
        return $content;
    }

    /**
     * @param string $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->_id = $id;
        return $this;
    }

    /**
     * @param null | string $id
     *
     * @return mixed
     */
    public function load($id = null)
    {
        if (null !== $id) {
            $this->_id = $id;
        }
        $content = $this->getFrontend()->load($this->getId());
        return $this->_gzuncompress($content);
    }

    /**
     * @param null | string $id
     *
     * @return mixed
     */
    public function test($id = null)
    {
        $testId = $this->getId();
        if (null === $testId && null === $id) {
            return false;
        }
        if (null !== $id) {
            $testId = $id;
        }
        return $this->getFrontend()->test($testId);
    }

    /**
     * @return string
     */
    static function getRootDir()
    {
        return Mage::getBaseDir('var') . DS . 'po_fpc';
    }

    /**
     * @return null | string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        Potato_FullPageCache_Helper_CacheStorage::unregisterCache($this->getId());
        return $this->getFrontend()->remove($this->getId());
    }
}