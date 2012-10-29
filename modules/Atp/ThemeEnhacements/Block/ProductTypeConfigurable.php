<?php

class Atp_ThemeEnhacements_Block_ProductTypeConfigurable extends Mage_Catalog_Block_Product_View_Type_Configurable
{

    /**
     * Composes configuration for js
     *
     * @return string
     */
    public function getJsonImagesConfig()
    {
      $images = array();
      //$currentProduct = $this->getProduct();
      foreach ($this->getAllowProducts() as $product) {
        if ($product->getImage() == 'no_selection' || !$product->getImage()) {
          continue;
        }
        $productId  = $product->getId();
        $thumbnail = Mage::helper('catalog/image')->init($product, 'thumbnail')->resize(56)->__toString();
        //xdebug_break();
        $polaroid = Mage::helper('catalog/image')->init($product, 'image')->resize(265)->__toString();
        $full = Mage::helper('catalog/image')->init($product, 'image')->__toString();
        $label = $product->getImageLabel();
        $images[$productId] = compact('thumbnail', 'polaroid', 'full', 'label');
      }

      return Mage::helper('core')->jsonEncode($images);
    }


}

