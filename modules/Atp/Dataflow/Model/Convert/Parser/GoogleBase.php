<?php

// see google-base.xml
class Atp_Dataflow_Model_Convert_Parser_GoogleBase extends Mage_Dataflow_Model_Convert_Parser_Csv
{

  const STORE_ADMIN = 0;
  const STORE_JEWELRY = 1;
  const STORE_CERAMICS = 2;

  const MAX_FREE_SHIPPING_WEIGHT = 0.81;
  const FREE_SHIPPING_TEMPLATE = 'US::Express:0 USD';
  const TAX_FREE_TEMPLATE = 'US::0:';

  const IN_STOCK = 'in stock';

  /**
   * Read data collection and write to temporary file
   *
   * @return Mage_Dataflow_Model_Convert_Parser_Csv
   */
  public function unparse()
  {
    $batchExport = $this->getBatchExportModel()
      ->setBatchId($this->getBatchModel()->getId());

    $fieldList = $this->getBatchModel()->getFieldList();
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

    //$store = $this->getVar('store', null);
    foreach ($batchExportIds as $batchExportId) {
      $csvData = array();
      $batchExport->load($batchExportId);
      $row = $batchExport->getBatchData();
      $row = $this->processRow($row);
      if (!$row) continue;
      foreach ($fieldList as $field) {
        $csvData[] = isset($row[$field]) ? $row[$field] : '';
      }
      $csvData = $this->getCsvString($csvData);
      $io->write($csvData);
    }

    $io->close();

    return $this;
  }

  protected function getAdditionalFields() {
    // We add extra fields here
    $fieldList = array();
    $fieldList['id'] = 'id'; // Product id
    $fieldList['link'] = 'link';
    $fieldList['image_link'] = 'image_link';
    $fieldList['additional_image_link'] = 'additional_image_link';
    $fieldList['shipping'] = 'shipping';
    $fieldList['availability'] = 'availability';
    return $fieldList;
  }

  protected function exclude($product) {
    $visibility = $product->getVisibility();
    return $visibility > 1 && $product->isAvailable() && $this->getProductStock($product);
  }

  protected function getStoreBySkuPrefix($sku) {
    $prefix = strtolower(substr($sku, 0, 3));
    $store = $prefix == 'cer' || $prefix == 'atp' ? self::STORE_CERAMICS : self::STORE_JEWELRY;
    return $store;
  }

  protected function processRow($row) {
    $sku = $row['mpn'];

    if (!isset($sku) || !$sku) return null;

    $store = $this->getStoreBySkuPrefix($sku);
    $product = $this->getProduct($sku, $store, 'sku');
    if ($this->exclude($product)) return null;
    $image_attribute = $product->getResource()->getAttribute('image');
    $weight = floatval($row['shipping_weight']);

    if (!$weight || !$image_attribute) return null;

    $google_color = $row['color'];
    if ($store == self::STORE_JEWELRY && !$google_color) return null;

    $row['title'] = trim($row['title']);
    $row['id'] = $product->getId();
    $row['image_link'] = $image_attribute->getFrontend()->getUrl($product);
    $row['link'] = $product->getProductUrl();
    $row['product_type'] = !empty($row['product_type']) ? $row['product_type'] : $this->trailCategories($product, ' / ');
    $row['shipping'] = $store == self::STORE_JEWELRY && $weight < self::MAX_FREE_SHIPPING_WEIGHT ? self::FREE_SHIPPING_TEMPLATE : '';
    $row['availability'] = self::IN_STOCK;
    $row['price'] = number_format(floatval($row['price']), 2);
    $qty = isset($row['quantity']) ? floatval($row['quantity']) : $this->getProductStock($product);
    if (!$qty) return null;
    $row['quantity'] = number_format($qty, 2);
    $row['shipping_weight'] = number_format($weight, 2) . ' lb';

    $row['material'] = $this->getProductAttribute($store == self::STORE_JEWELRY ? 'material' : 'pc_ceramic_material', $product, $store);

    // TODO: Use iterator syntax next time
    $mediaGallery = $product->getMediaGalleryImages();
    $otherImages = array();
    foreach ($mediaGallery as $media) {
      $otherImages[] = $media->getUrl();
    }
    $row['additional_image_link'] = implode(',', $otherImages);
    return $row;
  }

  /**
   * Return a string of concatenated category names using $separator
   *
   * @param  Mage_Catalog_Model_Product $product
   * @param  string $separator
   * @return string
   */
    protected function trailCategories(Mage_Catalog_Model_Product $product, $separator = ' > ') {
      $result = '';

      $categories = $product->getCategoryCollection()->load();

      if (!$categories) {
        return $result;
      }

      $category = null;
      foreach ($categories as $category) {
        if ($category) {
          $category = Mage::getModel('catalog/category')->load($category->getId());
          break;
        }
      } /*  */

      if (!$category) {
        return $result;
      }

      $crumbs = array();
      if ($path = $category->getPath()) {
        $path = explode('/', $path);
        $count = count($path);
        for ($i = 2; $i < $count; $i++) {
          if ($c = Mage::getModel('catalog/category')->load($path[$i])) {
            $crumbs[] = $c->getName();
          }
        }
      }

      $result = implode($separator, $crumbs);

      return $result;
    }

    /**
     * Return loaded product instance
     *
     * @param  int|string $productId (SKU or ID)
     * @param  int $store
     * @param  string $identifierType
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct($productId, $store, $identifierType = null)
    {
        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore($store)->getId());

        $expectedIdType = false;
        if ($identifierType === null) {
            if (is_string($productId) && !preg_match("/^[+-]?[1-9][0-9]*$|^0$/", $productId)) {
                $expectedIdType = 'sku';
            }
        }

        if ($identifierType == 'sku' || $expectedIdType == 'sku') {
            $idBySku = $product->getIdBySku($productId);
            if ($idBySku) {
                $productId = $idBySku;
            } else if ($identifierType == 'sku') {
                // Return empty product because it was not found by originally specified SKU identifier
                return $product;
            }
        }

        if ($productId && is_numeric($productId)) {
            $product->load((int) $productId);
        }

        return $product;
    }

    public function getProductAttribute($attribute, $product, $store) {
      return $product->getResource()->getAttribute($attribute)->setStoreId($store)->getFrontend()->getValue($product);
    }

    public function getProductStock($product) {
      $stock = (int)Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
      return $stock;
    }
}

