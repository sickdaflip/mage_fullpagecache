<?php

/**
 * Need rewrite with block for add block tags
 *
 * Class Potato_FullPageCache_Block_Core_Messages
 */
class Potato_FullPageCache_Block_Core_Messages extends Mage_Core_Block_Messages
{
    /**
     * Set open and closed tags
     *
     * @return string
     */
    public function getGroupedHtml()
    {
        $html = trim(parent::getGroupedHtml());
        $fpcObserver = Mage::getModel('po_fpc/observer');
        if (!$this->_frameOpenTag || !$fpcObserver) {
            $observer = new Varien_Event_Observer();
            $observer->setBlock($this);
            $fpcObserver->setFrameTags($observer);
            if ($this->_frameOpenTag) {
                $html = '<' . $this->_frameOpenTag . '>' . $html . '<' . $this->_frameCloseTag . '>';
            }
        }
        return $html;
    }

    public function _prepareLayout()
    {
        if (!Mage::registry('render_messages_denied_flag')) {
            $this->addMessages(Mage::getSingleton('core/session')->getMessages(true));
        }
        return $this;
    }
}