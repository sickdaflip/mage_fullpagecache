<?php

/**
 * Class Potato_FullPageCache_Model_Request
 */
class Potato_FullPageCache_Model_Request extends Mage_Core_Controller_Request_Http
{
    /**
     * @param $actionName
     *
     * @return $this
     */
    public function setActionName($actionName)
    {
        $this->_action = $actionName;
        return $this;
    }

    /**
     * @param $moduleName
     *
     * @return $this
     */
    public function setModuleName($moduleName)
    {
        $this->_module = $moduleName;
        return $this;
    }

    /**
     * @param null $requestUri
     *
     * @return $this
     */
    public function setRequestUri($requestUri = null)
    {
        $this->_requestUri = $requestUri;
        return $this;
    }

    /**
     * @param null $pathInfo
     *
     * @return $this|Zend_Controller_Request_Http
     */
    public function setPathInfo($pathInfo = null)
    {
        $this->_pathInfo = $pathInfo;
        return $this;
    }
}