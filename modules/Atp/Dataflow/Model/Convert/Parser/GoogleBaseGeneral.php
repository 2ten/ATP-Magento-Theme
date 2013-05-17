<?php

// all except tag rings
class Atp_Dataflow_Model_Convert_Parser_GoogleBaseGeneral extends Atp_Dataflow_Model_Convert_Parser_GoogleBase
{
  const RINGS_ATTRSET_ID = 84;

  protected function exclude($product) {
    $visibility = $product->getVisibility();
    return $visibility > 1 && $product->isAvailable() && $this->getProductStock($product);
  }

}
