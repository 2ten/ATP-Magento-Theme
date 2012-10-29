<?php

class Atp_ThemeEnhacements_Block_CheckoutLinks extends Mage_Core_Block_Template
{

  /**
   * Add shopping cart link to parent block
   *
   * @return Mage_Checkout_Block_Links
   */
  public function addCartLink()
  {
    $parentBlock = $this->getParentBlock();
    if ($parentBlock && Mage::helper('core')->isModuleOutputEnabled('Mage_Checkout')) {
      $count = $this->getSummaryQty() ? $this->getSummaryQty()
        : $this->helper('checkout/cart')->getSummaryCount();
      if ($count > 0) {
        $text = $this->__('My Cart (%s)', $count);
      } else {
        $text = $this->__('My Cart');
      }
        /*
      if ($count == 1) {
        $text = $this->__('My Cart (%s item)', $count);
      } elseif ($count > 0) {
        $text = $this->__('My Cart (%s items)', $count);
      } else {
        $text = $this->__('My Cart');
      }
      */

      $parentBlock->removeLinkByUrl($this->getUrl('checkout/cart'));
      $parentBlock->addLink($text, 'checkout/cart', $text, true, array(), 50, null, 'class="top-link-cart"', '<i class="icon-shopping-cart"></i> ');
    }
    return $this;
  }

  /**
   * Add link on checkout page to parent block
   *
   * @return Mage_Checkout_Block_Links
   */
  public function addCheckoutLink()
  {
    if (!$this->helper('checkout')->canOnepageCheckout()) {
      return $this;
    }

    $parentBlock = $this->getParentBlock();
    if ($parentBlock && Mage::helper('core')->isModuleOutputEnabled('Mage_Checkout')) {
      $text = $this->__('Checkout');
      $parentBlock->addLink(
                            $text, 'checkout', $text,
                            true, array('_secure' => true), 60, null,
                            'class="top-link-checkout"',
                            '<i class="icon-ok-sign"></i> '
                            );
    }
    return $this;
  }

}
