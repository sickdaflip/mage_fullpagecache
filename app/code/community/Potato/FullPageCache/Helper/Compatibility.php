<?php

/**
 * Please use this class for compatibility methods
 *
 * Class Potato_FullPageCache_Helper_Compatibility
 */
class Potato_FullPageCache_Helper_Compatibility extends Mage_Core_Helper_Abstract
{
    /**
     * compatibility with Olegnax_Ajaxcart
     *
     * @return bool
     */
    static function useOlegnaxAjaxcart()
    {
        if (@class_exists('Olegnax_Ajaxcart_Model_Observer', false)) {
            $_currentUrl = Mage::helper('core/url')->getCurrentUrl();
            if (strpos($_currentUrl, Olegnax_Ajaxcart_Model_Observer::AJAXCART_ROUTE ) === false) {
                Mage::getSingleton('core/session', array('name' => 'frontend'))->setData('oxajax_referrer', $_currentUrl);
            }
        }
        return true;
    }

    /**
     * compatibility with EM themes
     * event controller_action_layout_generate_xml_before
     *
     * @param $layout
     *
     * @return bool
     */
    static function useEMThemeBeforeLayoutGenerateBlocks($layout)
    {
        if (file_exists(BP . DS . 'app' . DS . 'code' . DS . 'local' . DS . 'EM' . DS . 'Themeframework' . DS . 'Model' . DS . 'Observer.php') &&
            !@class_exists('EM_Themeframework_Model_Observer', false) &&
            !Mage::registry('_singleton/themeframework/observer')
        ) {
            require_once BP . DS . 'app' . DS . 'code' . DS . 'local' . DS . 'EM' . DS . 'Themeframework' . DS . 'Model' . DS . 'Observer.php';
        }

        if (@class_exists('EM_Themeframework_Model_Observer', false) ) {
            $observer = new Varien_Event_Observer();
            $event = new Varien_Event(
                array(
                    'layout' => $layout,
                    'action' => Mage::app()->getFrontController()->getAction()
                )
            );
            $observer->setData(
                array(
                    'event' => $event
                )
            );
            $thObserver = Mage::getModel('themeframework/observer');
            if (method_exists($thObserver, 'changeLayoutEvent')) {
                Mage::getModel('themeframework/observer')->changeLayoutEvent($observer);
            }
        }
        return true;
    }

    /**
     * compatibility with EM themes
     * event controller_action_layout_generate_blocks_after
     *
     * @param $layout
     *
     * @return bool
     */
    static function useEMThemeAfterLayoutGenerateBlocks($layout)
    {
        if (@class_exists('EM_Themeframework_Model_Observer', false) &&
            !Mage::registry('_singleton/themeframework/observer')
        ) {
            $observer = new Varien_Event_Observer();
            $event = new Varien_Event(
                array(
                    'layout' => $layout,
                    'action' => Mage::app()->getFrontController()->getAction()
                )
            );
            $observer->setData(
                array(
                    'event' => $event
                )
            );
            $thObserver = Mage::getModel('themeframework/observer');
            if (method_exists($thObserver, 'changeTemplateEvent')) {
                Mage::getModel('themeframework/observer')->changeTemplateEvent($observer);
            }
        }
        return true;
    }

    /**
     * Compatibility with GeneBraintree module
     *
     * @return bool
     */
    static function useGeneBraintree()
    {
        if (@class_exists('Gene_Braintree_Model_Observer', true) &&
            !Mage::registry('_singleton/gene_braintree/observer')
        ) {
            $observer = new Varien_Event_Observer();
            $event = new Varien_Event(
                array(
                    'front' => Mage::app()->getFrontController()
                )
            );
            $observer->setData(
                array(
                    'event' => $event
                )
            );
            Mage::getModel('gene_braintree/observer')->addIncludePath($observer);
        }
        return true;
    }
}