<?php

class Atp_StockFilter_Model_Observer {

  public static function checkStockAndDisable($observer) {
    $storeId = 0;
    $order = $observer->getEvent()->getOrder();
    xdebug_break();
    foreach ($order->getItemsCollection() as $item) {
      $stockQty = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($item->getProductId())->getQty();
      if ($stockQty == 0) {
        Mage::getModel('catalog/product_status')->updateProductStatus($item->getProductId(), $storeId, Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
      }
    }
  }
}
