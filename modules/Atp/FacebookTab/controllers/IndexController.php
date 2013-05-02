<?php
class Atp_FacebookTab_IndexController extends Mage_Core_Controller_Front_Action
{
  /**
   * index action
   */
  public function indexAction()
  {
    $this->loadLayout('facebooktab_page');
    $this->renderLayout();
  }

}

