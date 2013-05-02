<?php
class Atp_FacebookTab_Block_View extends Mage_Catalog_Block_Product_Abstract
{
  protected function _beforeToHtml()
  {
    $this->_prepareCollection();
    return parent::_beforeToHtml();
  }

  protected function _prepareCollection()
  {
    /* @var $collection Mage_Catalog_Model_Resource_Product_Collection */
    $collection = Mage::getModel('catalog/product')->getCollection()
      ->setStoreId(Mage::app()->getStore()->getId())
      ->addAttributeToFilter('facebook_featured', 1)
      ->addAttributeToSelect(Mage::getSingleton('catalog/config')->getProductAttributes())
      ->addAttributeToSelect('name')
      ->addAttributeToSelect('sku')
      ->addAttributeToSelect('price')
      ->addAttributeToSelect('status')
      ->addAttributeToSelect('short_description')
      ->addAttributeToSelect('small_image')
      ->addMinimalPrice()
      ->addFinalPrice()
      ->addTaxPercents()
      //->setVisibility(Mage::getSingleton('catalog/product_visibility')->getVisibleInCatalogIds())
      ;
    Mage::getSingleton('catalog/product_status')->addVisibleFilterToCollection($collection);
    $collection->load();

    Mage::getModel('review/review')->appendSummary($collection);
    $this->setProducts($collection);
    return $this;
  }

}
?>
