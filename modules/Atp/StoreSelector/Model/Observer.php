<?php

class Atp_StoreSelector_Model_Observer
{

  protected $_enable_display = false;

  /**
   * Retrieve cookie object
   *
   * @return Mage_Core_Model_Cookie
   */
  public function getCookie()
  {
    return Mage::getSingleton('core/cookie');
  }

  public function checkHomePage(Varien_Event_Observer $observer) {
    //$action = $observer->getEvent()->getAction();
    $page = $observer->getEvent()->getPage();
    $homePageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_HOME_PAGE);
    //xdebug_break();
    if ($page->getIdentifier() != $homePageId) return;

    $params = Mage::registry('application_params');
    if (!empty($params['scope_code']) || isset($_GET['___store'])) {
    }

    //  || ($this->getCookie()->get() && $this->getCookie()->get(Mage_Core_Model_Store::COOKIE_NAME))) return;
    // check if rendering page is default homepage
    // check if there is not a store requested by get or cookie params
    // set enable display to true

  }

}