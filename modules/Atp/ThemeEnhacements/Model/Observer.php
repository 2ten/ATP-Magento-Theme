<?php

class Atp_ThemeEnhacements_Model_Observer
{

  public function appendStoreUpdate(Varien_Event_Observer $observer) {
    $store = Mage::app()->getStore()->getCode();
    xdebug_break();
    $observer->getEvent()->getLayout()->getUpdate()->addHandle('store_' . $store);
  }

}

