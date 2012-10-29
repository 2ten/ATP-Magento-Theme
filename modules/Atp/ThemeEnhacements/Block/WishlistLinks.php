<?php

class Atp_ThemeEnhacements_Block_WishlistLinks extends Mage_Wishlist_Block_Links
{

    protected function _createLabel($count)
    {
        if ($count > 0) {
            return $this->__('My Wishlist (%d)', $count);
        } else {
            return $this->__('My Wishlist');
        }
    }
}