<?php

class Atp_ThemeEnhacements_Block_ProductList extends Mage_Catalog_Block_Product_List {


  const SIZE_CATEGORY_VIEW_GRID = 'category_grid';
  const SIZE_CATEGORY_VIEW_LIST = 'category_list';
  const SIZE_PRODUCT_VIEW = 'product_view';
  const SIZE_UPSELL_BLOCK = 'upsell_block';
  const SIZE_RELATED_BLOCK = 'related_block';


  protected $_productImageSizes;

  public function registerImageSize($alias, $width, $height) {
    $this->_productImageSizes[$alias] = array($width, $height);
  }

  public function getImageSize($alias) {
    if (isset($this->_productImageSizes[$alias]))
      return $this->_productImageSizes[$alias];

    return null;
  }

}
