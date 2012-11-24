<?php

class Atp_ThemeEnhacements_Model_Observer
{

  public function appendStoreUpdate(Varien_Event_Observer $observer) {
    $store = Mage::app()->getStore()->getCode();
    $action = $observer->getEvent()->getAction()->getFullActionName();
    $update = 'STORE_' . $store . '_' . strtolower($action);
    $observer->getEvent()->getLayout()->getUpdate()->addHandle($update);
  }

  public function appendStoreClass(Varien_Event_Observer $observer) {
    $store = Mage::app()->getStore()->getCode();
    $root = $observer->getEvent()->getLayout()->getBlock('root');
    if ($root && method_exists($root, 'addBodyClass')) {
      //xdebug_break();
      $root->addBodyClass('store-' . $store);
    }
  }

}

