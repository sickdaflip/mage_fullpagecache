<?php

/**
 * For crawler customer session
 *
 * Class Potato_FullPageCache_Model_Crawler_Customer_Session
 */
class Potato_FullPageCache_Model_Crawler_Customer_Session extends Mage_Customer_Model_Session
{
    //session id
    const ID = '99999999999999999999999999999999';

    /**
     * Set custom session id
     *
     * @param string $id
     * @return Mage_Core_Model_Session_Abstract_Varien
     */
    public function setSessionId($id=null)
    {
        session_id(self::ID);
        return $this;
    }

    /**
     * @return Potato_FullPageCache_Model_Crawler_Customer
     */
    public function getCustomer()
    {
        return Mage::getSingleton('po_fpc/crawler_customer');
    }

    /**
     * @return int|mixed|null
     */
    public function getCustomerId()
    {
        return $this->getCustomer()->getId();
    }

    /**
     * @return int
     */
    public function getCustomerGroupId()
    {
        return $this->getCustomer()->getGroupId();
    }

    /**
     * @return bool
     */
    public function isLoggedIn()
    {
        return (bool)$this->getCustomer()->getGroupId();
    }

    /**
     * @param int $customerId
     *
     * @return bool
     */
    public function checkCustomerId($customerId)
    {
        return (bool)$this->getCustomer()->getGroupId();
    }
}