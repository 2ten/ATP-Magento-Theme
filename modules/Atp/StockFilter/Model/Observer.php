<?php

class Atp_StockFilter_Model_Observer
{

  const STORE_ADMIN = 0;
  const STORE_JEWELRY = 1;
  const STORE_CERAMICS = 2;

  public function checkStockAndDisable($observer) {
    $order = $observer->getEvent()->getOrder();
    foreach ($order->getItemsCollection() as $item) {
      $storeId = strtolower(substr($item->getSku(), 0, 3)) == 'cer' ? self::STORE_CERAMICS : self::STORE_ADMIN;
      $stockQty = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($item->getProductId())->getQty();
      if ($stockQty == 0) {
        Mage::getModel('catalog/product_status')->updateProductStatus($item->getProductId(), $storeId, Mage_Catalog_Model_Product_Status::STATUS_DISABLED);
      }
    }
  }

}
