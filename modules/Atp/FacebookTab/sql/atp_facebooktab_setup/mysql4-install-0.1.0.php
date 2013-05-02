<?php

$installer = Mage::getResourceModel('catalog/setup', 'core_setup');

$installer->startSetup();
if (!$installer->getAttributeId('catalog_product', 'facebook_featured')) {
  $installer->addAttribute('catalog_product', 'facebook_featured', array(
    'group' => 'General',
    'label' => 'Show in Facebook Tab',
    'required' => false,
    'input' => 'select',
    'source' => 'eav/entity_attribute_source_boolean',
    'default' => 'none',
    'position' => 1,
    'sort_order' => 15,
    'visible' => 1,
    'used_in_product_listing' => 1
  ));
}
$installer->endSetup();
