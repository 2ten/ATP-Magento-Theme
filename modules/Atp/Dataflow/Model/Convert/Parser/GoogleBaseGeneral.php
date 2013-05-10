<?php

// all except tag rings
class Atp_Dataflow_Model_Convert_Parser_GoogleBaseGeneral extends Atp_Dataflow_Model_Convert_Parser_GoogleBase
{
  const RINGS_ATTRSET_ID = 84;

  protected function exclude($product) {
    return $product->getAttributeSetId() == self::RINGS_ATTRSET_ID;
  }
}
