<?php

class Potato_FullPageCache_Model_Processor_Block_Session extends Potato_FullPageCache_Model_Processor_Block_Default
{
    /**
     * @return null|string
     */
    public function getId()
    {
        return $this->getBlockCache()->getPageCache()->getClientUid()
            . $this->getBlockCache()->getPageCache()->getCurrentCurrencyCode()
        ;
    }

    /**
     * save cache
     *
     * @param $data
     * @param $index
     *
     * @return $this
     */
    public function save($data, $index)
    {
        $sessionBlockCache = Potato_FullPageCache_Model_Cache::getOutputCache($index . $this->getId(),
            array('lifetime' => $this->getBlockCache()->getBlockLifetime($index))
        );
        return $sessionBlockCache->save($data, null, $this->_getTags());
    }

    /**
     * @param string $prefix
     *
     * @return bool|mixed
     */
    public function load($prefix = '')
    {
        $sessionBlockCache = Potato_FullPageCache_Model_Cache::getOutputCache($prefix . $this->getId(),
            array('lifetime' => $this->getBlockCache()->getBlockLifetime($prefix))
        );
        return $sessionBlockCache->test() ? $sessionBlockCache->load() : false;
    }

    /**
     * @param string $prefix
     *
     * @return $this
     */
    public function remove($prefix = '')
    {
        $sessionBlockCache = Potato_FullPageCache_Model_Cache::getOutputCache($prefix . $this->getId(),
            array('lifetime' => $this->getBlockCache()->getBlockLifetime($prefix))
        );
        $sessionBlockCache->delete();
        return $this;
    }

    protected function _getTags()
    {
        return array(Potato_FullPageCache_Model_Cache::SESSION_BLOCK_TAG);
    }

    public function isAllowEvent($eventName)
    {
        return true;
    }

    public function update($index, $blockData, $eventName)
    {
        if (!$this->isAllowEvent($eventName)) {
            return false;
        }

        $this->remove($index);

        $_block = Mage::app()->getLayout()->getBlock($index);
        if (!$_block && is_array($blockData) && array_key_exists('type', $blockData)) {
            try {
                $_block = Mage::app()->getLayout()->createBlock($blockData['type'], $index);
                if (array_key_exists('params', $blockData)) {
                    $_block->addData($blockData['params']);
                }
                if (array_key_exists('template', $blockData)) {
                    $_block->setTemplate($blockData['template']);
                }
            } catch (Exception $e) {
                return false;
            }
        }
        if ($_block) {
            try {
                //get updated content from block processor
                $blockHtml = $this->getBlockHtml($_block);
            } catch (Exception $e) {
                Mage::logException($e);
                $blockHtml = '';
            }
            $this->save(
                array(
                    'html'           => $blockHtml,
                    'name_in_layout' => $index
                ),
                $index
            );
            return true;
        }
        return false;
    }
}