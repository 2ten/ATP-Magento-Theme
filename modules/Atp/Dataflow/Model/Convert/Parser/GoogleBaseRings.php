<?php

// all except tag rings
class Atp_Dataflow_Model_Convert_Parser_GoogleBaseRings extends Atp_Dataflow_Model_Convert_Parser_GoogleBase
{
  /**
   * Read data collection and write to temporary file
   *
   * @return Mage_Dataflow_Model_Convert_Parser_Csv
   */
  public function unparse()
  {
    $batchExport = $this->getBatchExportModel()
      ->setBatchId($this->getBatchModel()->getId());

    $originalFieldList = $fieldList = $this->getBatchModel()->getFieldList();
    $additional = $this->getAdditionalFields();
    $fieldList = array_merge($fieldList, $additional);

    $batchExportIds = $batchExport->getIdCollection();
    $io = $this->getBatchModel()->getIoAdapter();
    $io->open();

    if (!$batchExportIds) {
      $io->write("");
      $io->close();
      return $this;
    }

    if ($this->getVar('fieldnames')) {
      $csvData = $this->getCsvString($fieldList);
      $io->write($csvData);
    }

    $skus = array();
    foreach ($batchExportIds as $batchExportId) {
      $csvData = array();
      $batchExport->load($batchExportId);
      $row = $batchExport->getBatchData();
      $sku = $row['mpn'];
      if (!$sku) continue;
      $store = $this->getStoreBySkuPrefix($sku);
      $product = $this->getProduct($sku, $store);
      if (!$product) continue;
      $skus[$product->getId()] = $sku;
    }

    foreach ($skus as $confId => $confSku) {
      $store = $this->getStoreBySkuPrefix($sku);
      $product = $this->getProduct($confSku, $store);
      $url = $product->getProductUrl();
      $children = Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);
      $configurableAttributeCollection = $product->getTypeInstance()->getConfigurableAttributes();
      foreach ($children as $child) {
        $row = $this->getSimpleRow($child);
        $row = $this->processRow($row);
        if (!$row) continue;
        $select = array();
        foreach($configurableAttributeCollection as $attribute) {
          $attrId = $attribute->getProductAttribute()->getAttributeId();
          $select[$attrId] = $child->getData($attribute->getProductAttribute()->getAttributeCode());
        }
        $row['item_group_id'] = $confSku;
        $row['link'] = $url . '#' . http_build_query($select);
        $csvData = array();
        foreach ($fieldList as $field) {
          $csvData[] = isset($row[$field]) ? $row[$field] : '';
        }
        $csvData = $this->getCsvString($csvData);
        $io->write($csvData);
      }
    }

    $io->close();

    return $this;
  }

  protected function getAdditionalFields() {
    $fields = parent::getAdditionalFields();
    $fields['item_group_id'] = 'item_group_id';
    $fields['size'] = 'size';
    $fields['color'] = 'color';
    return $fields;
  }

  protected function getSimpleRow($product) {
    $row = array();
    if (!$product) return $row;
    $sku = $product->getSku();
    $store = $this->getStoreBySkuPrefix($sku);
    $row['mpn'] = $sku;
    $map = array('title_thefind' => 'title',
                 'description' => 'description',
                 'google_category' => 'google_product_category',
                 'thefind_category' => 'product_type',
                 'google_condition' => 'condition',
                 'price' => 'price',
                 'brand' => 'brand',
                 'google_gender' => 'gender',
                 'google_age_group' => 'age_group',
                 'weight' => 'shipping_weight',
                 'tagua_color' => 'color',
                 'ring_size' => 'size'
                 );
    foreach ($map as $attr => $field) {
      $row[$field] = $this->getProductAttribute($attr, $product, $store);
    }
    return $row;
  }


}