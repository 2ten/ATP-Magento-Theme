<?php

/**
 * Top menu block
 *
 * @category    Mage
 * @package     Mage_Page
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Atp_ThemeEnhacements_Block_Topmenu extends Mage_Page_Block_Html_Topmenu
{

    /**
     * Recursively generates top menu html from data that is specified in $menuTree
     *
     * @param Varien_Data_Tree_Node $menuTree
     * @param string $childrenWrapClass
     * @return string
     */
    protected function _getHtml(Varien_Data_Tree_Node $menuTree, $childrenWrapClass)
    {
        $html = '';

        $children = $menuTree->getChildren();
        $parentLevel = $menuTree->getLevel();
        $childLevel = is_null($parentLevel) ? 0 : $parentLevel + 1;

        $counter = 1;
        $childrenCount = $children->count();

        $parentPositionClass = $menuTree->getPositionClass();
        $itemPositionClassPrefix = $parentPositionClass ? $parentPositionClass . '-' : 'nav-';

        foreach ($children as $child) {

            $child->setLevel($childLevel);
            $child->setIsFirst($counter == 1);
            $child->setIsLast($counter == $childrenCount);
            $child->setPositionClass($itemPositionClassPrefix . $counter);

            $outermostClassCode = '';
            $outermostClass = $menuTree->getOutermostClass();
            //$outermostClass .= $child->hasChildren() ? ' dropdown-toggle' : '';

            if ($childLevel == 0 && $outermostClass) {
                $outermostClassCode = ' class="' . $outermostClass . '" ';
                $child->setClass($outermostClass);
            }

            $href = $child->getUrl();
            $dropdownAttributes = '';
            $dropdownAfter = '';
            /*
            if ($child->hasChildren()) {
              $dropdownAttributes = 'data-target="#" data-toggle="dropdown" ' ;
              $dropdownAfter = '<b class="caret"></b>';
            }
            */

            $html .= '<li ' . $this->_getRenderedMenuItemAttributes($child) . '>';
            $html .= '<a href="' . $href . '" ' . $outermostClassCode . $dropdownAttributes .'>'
                . $this->escapeHtml($child->getName()) . $dropdownAfter . '</a>';

            if ($child->hasChildren()) {
              $html .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown"><b class="caret"></b></a>';
                if (!empty($childrenWrapClass)) {
                  $html .= '<div class="' . $childrenWrapClass . '">';
                }
                $html .= '<ul class="level' . $childLevel . ' dropdown-menu">';
                $html .= $this->_getHtml($child, $childrenWrapClass);
                $html .= '</ul>';

                if (!empty($childrenWrapClass)) {
                    $html .= '</div>';
                }
            }
            $html .= '</li>';

            $counter++;
        }

        return $html;
    }


    /**
     * Returns array of menu item's classes
     *
     * @param Varien_Data_Tree_Node $item
     * @return array
     */
    protected function _getMenuItemClasses(Varien_Data_Tree_Node $item)
    {
        $classes = array();

        $classes[] = 'level' . $item->getLevel();
        $classes[] = $item->getPositionClass();

        if ($item->getIsFirst()) {
            $classes[] = 'first';
        }

        if ($item->getIsActive()) {
            $classes[] = 'active';
        }

        if ($item->getIsLast()) {
            $classes[] = 'last';
        }

        if ($item->getClass()) {
            $classes[] = $item->getClass();
        }

        if ($item->hasChildren()) {
            $classes[] = 'parent dropdown';
        }

        $classes[] = 'item-id-' . $item->getId();

        return $classes;
    }

    public function getBrandName()
    {
        if (empty($this->_data['brand_name'])) {
            $this->_data['brand_name'] = Mage::getStoreConfig('design/header/logo_alt');
        }
        return $this->_data['brand_name'];
    }

}
