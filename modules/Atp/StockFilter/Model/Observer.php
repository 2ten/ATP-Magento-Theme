<?php

class Atp_StockFilter_Model_Observer
{
  public function checkStock($observer) {
    $order = $observer->getEvent()->getOrder();
    $storeId = 0;
    foreach ($order->getItemsCollection() as $item) {
      $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($item->getProductId());
      $qty = (int)$stock->getQty();
      if ($qty == 0) {
        $stock->setData('is_in_stock', 0);
        $stock->save();
      }
    }
  }

}
