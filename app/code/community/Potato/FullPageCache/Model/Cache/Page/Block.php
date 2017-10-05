<?php

/**
 * Class Potato_FullPageCache_Model_Cache_Page_Block
 */
class Potato_FullPageCache_Model_Cache_Page_Block
{
    //block cache lifetime 30 min
    const BLOCK_CACHE_LIFETIME = 86400;

    //default block processor class name
    const DEFAULT_BLOCK_PROCESSOR_CLASS = 'Potato_FullPageCache_Model_Processor_Block_Default';
    const SESSION_BLOCK_PROCESSOR_CLASS = 'Potato_FullPageCache_Model_Processor_Block_Session';

    protected $_pageCache     = null;
    protected $_config        = null;
    protected $_skippedBlocks = null;
    protected $_sessionBlocks = null;
    protected $_actionBlocks  = null;
    protected $_actionConfig  = null;

    /**
     * @param Potato_FullPageCache_Model_Cache_Page $page
     * @param Mage_Core_Model_Config_Element        $config
     */
    public function __construct(Potato_FullPageCache_Model_Cache_Page $page, $config)
    {
        $this->_pageCache = $page;
        $this->_config = $config;
    }

    /**
     * @return null|Potato_FullPageCache_Model_Cache_Page
     */
    public function getPageCache()
    {
        return $this->_pageCache;
    }

    /**
     * return true if block name not specified in po_fpc.xml
     *
     * @param string $blockName
     *
     * @return bool
     */
    public function getIsCanCache($blockName)
    {
        if (!$this->_config) {
            return true;
        }
        return !array_key_exists($blockName, $this->getSkippedBlocks());
    }

    /**
     * @return array
     */
    public function getSkippedBlocks()
    {
        if (null === $this->_skippedBlocks) {
            $this->_skippedBlocks = array_merge($this->getSessionBlocks(), $this->getActionBlocks());
        }
        return $this->_skippedBlocks;
    }

    /**
     * @return array
     */
    public function getSessionBlocks()
    {
        if (null === $this->_sessionBlocks) {
            $sessionBlocks = array();
            if ($this->_config) {
                $sessionBlocks = $this->_config->getNode('session_blocks')->asArray();
            }
            $this->_sessionBlocks = array();
            foreach ($sessionBlocks as $index => $block) {
                if (is_array($block) && array_key_exists('name_in_layout', $block)) {
                    $this->_sessionBlocks[$block['name_in_layout']] = $block;
                } else {
                    $this->_sessionBlocks[$index] = $block;
                }
            }
        }
        return $this->_sessionBlocks;
    }

    /**
     * @return array
     */
    public function getActionBlocks()
    {
        if (null === $this->_actionBlocks) {
            $actionBlocks = array();
            if ($this->_config) {
                $actionConfig = $this->getActionConfig();
                if (array_key_exists('skip_blocks', $actionConfig) && is_array($actionConfig['skip_blocks'])) {
                    $actionBlocks = $actionConfig['skip_blocks'];
                }
            }
            $this->_actionBlocks = array();
            foreach ($actionBlocks as $index => $block) {
                if (is_array($block) && array_key_exists('name_in_layout', $block)) {
                    $this->_actionBlocks[$block['name_in_layout']] = $block;
                } else {
                    $this->_actionBlocks[$index] = $block;
                }
            }
        }
        return $this->_actionBlocks;
    }

    /**
     * @return array
     */
    public function getActionConfig()
    {
        if (null === $this->_actionConfig || empty($this->_actionConfig)) {
            $this->_actionConfig = array();
            if (!$this->_config || !$this->_config->getNode('allowed_routers')) {
                return $this->_actionConfig;
            }
            $request = $this->_pageCache->getRequest();
            foreach($this->_config->getNode('allowed_routers') as $action) {
                $actionData = $action->asArray();
                $moduleName = $request->getModuleName();
                if (!array_key_exists($moduleName, $actionData)) {
                    continue;
                }
                if (!array_key_exists('controllers', $actionData[$moduleName])) {
                    continue;
                }
                $controllerName = $request->getControllerName();
                $actionName = $request->getActionName();

                if (!array_key_exists($controllerName, $actionData[$moduleName]['controllers'])) {
                    continue;
                }
                $actions = explode(',', $actionData[$moduleName]['controllers'][$controllerName]);
                if (in_array($actionName, $actions) || in_array('*', $actions)) {
                    if (array_key_exists('parameters', $actionData[$moduleName]) &&
                        !empty($actionData[$moduleName]['parameters']))
                    {
                        $this->_actionConfig = $actionData[$moduleName]['parameters'];
                        return $this->_actionConfig;
                    }
                    $this->_actionConfig = $actionData[$moduleName];
                    return $this->_actionConfig;
                }
            }
        }
        return $this->_actionConfig;
    }

    /**
     * return true if action specified in po_fpc.xml
     *
     * @return bool
     */
    public function getIsAllowedAction()
    {
        $actionConfig = $this->getActionConfig();
        return empty($actionConfig) ? false : true;
    }

    /**
     * return skipped block processor instance or false
     *
     * @param $blockName
     *
     * @return bool | instance Potato_FullPageCache_Model_Processor_Block_Default
     */
    public function getBlockCacheProcessor($blockName)
    {
        if (!$this->_config) {
            return false;
        }
        $skippedBlocks = $this->getSkippedBlocks();
        if (!array_key_exists($blockName, $skippedBlocks)) {
            return false;
        }
        $processor = self::DEFAULT_BLOCK_PROCESSOR_CLASS;
        if (array_key_exists($blockName, $this->getSessionBlocks())) {
            $processor = self::SESSION_BLOCK_PROCESSOR_CLASS;
        }
        if (is_array($skippedBlocks[$blockName]) &&
            array_key_exists('processor', $skippedBlocks[$blockName])
        ) {
            $processor = $skippedBlocks[$blockName]['processor'];
        }
        return new $processor($this);
    }

    /**
     * Skipped blocks its dynamical blocks (see po_fpc.xml skip_blocks and session_blocks tags)
     *
     * @param $data array('html', 'name_in_layout')
     * @param $index
     *
     * @return bool
     */
    public function saveSkippedBlockCache($data, $index)
    {
        if (array_key_exists($index, $this->getSessionBlocks())) {
            $blockProcessor = $this->getBlockCacheProcessor($index);
            return $blockProcessor->save($data, $index);
        }
        return $this->saveActionBlockCache($data, $index);
    }

    /**
     * Save blocks by skip_blocks tag
     *
     * @param $data
     * @param $index
     *
     * @return bool
     */
    public function saveActionBlockCache($data, $index)
    {
        $cache = $this->getActionBlockCacheInstance($index);
        $cache->save($data, null,
            array_merge(
                array(Potato_FullPageCache_Model_Cache::BLOCK_TAG),
                Potato_FullPageCache_Helper_Data::getCacheTags()
            ),
            false,
            Potato_FullPageCache_Helper_Data::getPrivateTag()
        );
        return true;
    }

    /**
     * Prepare cache instance for blocks by skip_blocks tag per request
     *
     * @return Potato_FullPageCache_Model_Cache_Default
     */
    public function getActionBlockCacheInstance($index)
    {
        return Potato_FullPageCache_Model_Cache::getOutputCache('blocks_'
            . $this->_pageCache->getId() . '_' . $index,
            array('lifetime' => $this->getBlockLifetime($index))
        );
    }

    public function getBlockLifetime($index)
    {
        $lifetime = self::BLOCK_CACHE_LIFETIME;
        if (!$this->_config) {
            return $lifetime;
        }
        $skippedBlocks = $this->getSkippedBlocks();
        if (!array_key_exists($index, $skippedBlocks)) {
            return $lifetime;
        }
        if (is_array($skippedBlocks[$index]) &&
            array_key_exists('lifetime', $skippedBlocks[$index])
        ) {
            $lifetime = $skippedBlocks[$index]['lifetime'];
        }
        return $lifetime;
    }

    /**
     * Load cached blocks by skip_blocks tag for current request
     *
     * @return array|mixed
     */
    public function getActionBlockCache($index)
    {
        $result = array();
        $cache = $this->getActionBlockCacheInstance($index);
        if ($cache->test()) {
            $result = $cache->load();
        }
        return $result;
    }
}