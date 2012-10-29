<?php

class Atp_ThemeEnhacements_Model_Observer
{

  public function appendStoreUpdate(Varien_Event_Observer $observer) {
    $store = Mage::app()->getStore()->getCode();
    $action = $observer->getEvent()->getAction()->getFullActionName();
    $update = 'STORE_' . $store . '_' . strtolower($action);
    //xdebug_break();
    $observer->getEvent()->getLayout()->getUpdate()->addHandle($update);
  }

}

