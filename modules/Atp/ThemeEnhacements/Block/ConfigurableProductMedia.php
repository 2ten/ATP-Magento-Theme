<?php

class Atp_ThemeEnhacements_Block_ConfigurableProductMedia extends Mage_Catalog_Block_Product_View_Media {

  const ATTRIBUTE_CODE = 'media_gallery';

  public function getConfigGallery() {
    $collection = new Varien_Data_Collection();
    $configurable = $this->getLayout()->getBlock('product.info.configurable');
    $products = $configurable ? $configurable->getAllowProducts() : null;
    $this->_addConfigImages($collection, $products);
    return $collection;
  }

  protected function _getGalleryAttribute($product) {
    $attributes = $product->getTypeInstance(true)->getSetAttributes($product);
    if (!isset($attributes[self::ATTRIBUTE_CODE])) {
      return null;
    }
    return $attributes[self::ATTRIBUTE_CODE];
  }

  protected function _getProduct($productId) {
    $productHelper = Mage::helper('catalog/product');
    $product = $productHelper->getProduct($productId, Mage::app()->getStore()->getId());
    if (!($product->getId())) {
      return null;
    }
    return $product;
  }

  protected function _addConfigImages($collection, $products) {
    foreach ($products as $product) {
      $product = $this->_getProduct($product->getId());
      if (!$product || !$this->_getGalleryAttribute($product)) {
        continue;
      }
      $images = $product->getMediaGalleryImages();
      if (!$images) continue;
      foreach ($images as $image) {
        $image['product'] = $product;
        $collection->addItem($image);
      }
    }
  }
}
