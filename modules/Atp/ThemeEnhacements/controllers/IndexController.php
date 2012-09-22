<?php

require_once("Mage/Cms/controllers/IndexController.php");

class Atp_ThemeEnhacements_IndexController extends Mage_Cms_IndexController
{
    /**
     * Renders CMS Home page
     *
     * @param string $coreRoute
     */
    public function indexAction($coreRoute = null)
    {
      $pageId = Mage::getStoreConfig(Mage_Cms_Helper_Page::XML_PATH_HOME_PAGE);
      if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
        $this->_forward('defaultIndex');
      }
    }
}