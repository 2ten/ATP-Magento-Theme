<?php

/**

Intended to work with an advanced Dataflow profile with following action XML:

<action type="catalog/convert_adapter_product" method="load">
    <var name="store"><![CDATA[0]]></var>
    <!-- <var name="filter/attribute_set"><![CDATA[77]]></var> -->
    <var name="filter/visibility"><![CDATA[4]]></var>
    <var name="filter/status"><![CDATA[1]]></var>
</action>

<action type="catalog/convert_parser_product" method="unparse">
    <var name="store"><![CDATA[0]]></var>
    <var name="url_field"><![CDATA[0]]></var>
</action>

<action type="dataflow/convert_mapper_column" method="map">
    <var name="map">
        <map name="sku"><![CDATA[SKU]]></map>
        <map name="title_thefind"><![CDATA[Title]]></map>
        <map name="description"><![CDATA[Description]]></map>
        <map name="price"><![CDATA[Price]]></map>
        <map name="qty"><![CDATA[Stock_Quantity]]></map>
        <map name="brand"><![CDATA[Brand]]></map>
        <map name="country_of_manufacture"><![CDATA[Made_In]]></map>
        <map name="meta_keyword"><![CDATA[Tags_Keywords]]></map>
        <map name="weight"><![CDATA[Weight]]></map>
        <map name="measure_length"><![CDATA[Length]]></map>
        <map name="material"><![CDATA[Material]]></map>
        <map name="measure_width"><![CDATA[Width]]></map>
        <map name="name"><![CDATA[_Support_Name]]></map>
        <map name="charm"><![CDATA[_Support_Charm]]></map>
        <map name="attribute_set"><![CDATA[_Support_Attr_Set]]></map>
    </var>
    <var name="_only_specified">true</var>
</action>

<action type="Atp_Dataflow_Model_Convert_Parser_TheFind" method="unparse">
    <var name="store"><![CDATA[0]]></var>
    <var name="delimiter"><![CDATA[,]]></var>
    <var name="enclose"><![CDATA["]]></var>
    <var name="fieldnames">true</var>
</action>

<action type="dataflow/convert_adapter_io" method="save">
    <var name="type">file</var>
    <var name="path">var/export</var>
    <var name="filename"><![CDATA[the_find_base.csv]]></var>
</action>
*/

class Atp_Dataflow_Model_Convert_Parser_TheFind extends Mage_Dataflow_Model_Convert_Parser_Csv
{

  const STORE_ADMIN = 0;
  const STORE_JEWELRY = 1;
  const STORE_CERAMICS = 2;

    /**
     * Read data collection and write to temporary file
     *
     * @return Mage_Dataflow_Model_Convert_Parser_Csv
     */
    public function unparse()
    {
        $batchExport = $this->getBatchExportModel()
            ->setBatchId($this->getBatchModel()->getId());

        $fieldList = $this->getBatchModel()->getFieldList();
        // We add extra fields here
        $fieldList['Unique_ID'] = 'Unique_ID'; // Product id
        $fieldList['Image_URL'] = 'Image_URL';
        $fieldList['Page_URL'] = 'Page_URL';
        $fieldList['Categories'] = 'Categories';
        $fieldList['Online_Only'] = 'Online_Only'; // Always true
        $fieldList['Department'] = 'Department'; // Women
        $fieldList['Alt_Image_1'] = 'Alt_Image_1'; // Extra images
        $fieldList['Alt_Image_2'] = 'Alt_Image_2'; // Extra images
        $fieldList['Alt_Image_3'] = 'Alt_Image_3'; // Extra images

        $batchExportIds = $batchExport->getIdCollection();

        $io = $this->getBatchModel()->getIoAdapter();
        $io->open();

        if (!$batchExportIds) {
            $io->write("");
            $io->close();
            return $this;
        }

        if ($this->getVar('fieldnames')) {
            $csvData = $this->getCsvString($fieldList);
            $io->write($csvData);
        }

        //$store = $this->getVar('store', null);
        foreach ($batchExportIds as $batchExportId) {
            $csvData = array();
            $batchExport->load($batchExportId);
            $row = $batchExport->getBatchData();

            if (isset($row['SKU']) && $row['SKU']) {
              //xdebug_break();

              $product = $this->getProduct($row['SKU'], strtolower(substr($row['SKU'], 0, 2)) == 'cer' ? self::STORE_CERAMICS : self::STORE_JEWELRY, 'sku');

              $row['Title'] = trim($row['Title']);
              $row['Unique_ID'] = $product->getId();
              $row['Online_Only'] = 'True';
              $row['Department'] = 'Women';
              if ($attribute = $product->getResource()->getAttribute('image')) {
                $row['Image_URL'] = $attribute->getFrontend()->getUrl($product);
              } else {
                $row['Image_URL'] = '';
              }
              $row['Page_URL'] = $product->getProductUrl();
              $row['Tags_Keywords'] = $this->flattenKeywords($row['Tags_Keywords'], 10);
              $row['Categories'] = $this->trailCategories($product, ' > ');

              // TODO: Use iterator syntax next time
              $mediaGallery = $product->getMediaGalleryImages()->toArray();
              if ($mediaGallery) {
                $count = $mediaGallery['totalRecords'];
                $count = $count < 3 ? $count : 3;
                for ($i = 0; $i < $count; $i++) {
                  $row['Alt_Image_' . ($i + 1)] = $mediaGallery['items'][$i]['url'];
                }
              }
            }

            foreach ($fieldList as $field) {
                $csvData[] = isset($row[$field]) ? $row[$field] : '';
            }
            $csvData = $this->getCsvString($csvData);
            $io->write($csvData);
        }

        $io->close();

        return $this;
    }

    /**
     * Return $count keywords from $keys. $keys should be separated by commas.
     *
     * @return string
     */
    protected function flattenKeywords($keys, $count = 10) {
      $result = '';
      $list = explode(',', $keys);
      $listCount = count($list);

      if (!$list || !$listCount) {
        return $result;
      }

      $list = array_map('trim', $list);
      $result = implode(', ', $listCount < $count ? $list : array_slice($list, 0, $count));
      return $result;
    }

    function getConcatenatedGallery($sku, $store, $separator = ',') {

      $result = '';
      $product = $this->getProduct($sku, $store, 'sku');
      if (!$product) return $result;

      $mediaGallery = $product->getMediaGalleryImages();
      if (!$mediaGallery) return $result;

      $mediaGallery = $mediaGallery['images'];
      $images = array();
      foreach ($mediaGallery as $image) {
        $images[] = $image['file'];
      }
      return $i = implode($separator, $images);
    }


    /**
     * Return a string of concatenated category names using $separator
     *
     * @param  Mage_Catalog_Model_Product $product
     * @param  string $separator
     * @return string
     */
    protected function trailCategories(Mage_Catalog_Model_Product $product, $separator = ' > ') {
      $result = '';

      $categories = $product->getCategoryCollection()->load();

      if (!$categories) {
        return $result;
      }

      $category = null;
      foreach ($categories as $category) {
        if ($category) {
          $category = Mage::getModel('catalog/category')->load($category->getId());
          break;
        }
      }

      if (!$category) {
        return $result;
      }

      $crumbs = array();
      if ($path = $category->getPath()) {
        $path = explode('/', $path);
        $count = count($path);
        for ($i = 2; $i < $count; $i++) {
          if ($c = Mage::getModel('catalog/category')->load($path[$i])) {
            $crumbs[] = $c->getName();
          }
        }
      }

      $result = implode($separator, $crumbs);

      return $result;
    }

    /**
     * Return loaded product instance
     *
     * @param  int|string $productId (SKU or ID)
     * @param  int $store
     * @param  string $identifierType
     * @return Mage_Catalog_Model_Product
     */
    public function getProduct($productId, $store, $identifierType = null)
    {
        /** @var $product Mage_Catalog_Model_Product */
        $product = Mage::getModel('catalog/product')->setStoreId(Mage::app()->getStore($store)->getId());

        $expectedIdType = false;
        if ($identifierType === null) {
            if (is_string($productId) && !preg_match("/^[+-]?[1-9][0-9]*$|^0$/", $productId)) {
                $expectedIdType = 'sku';
            }
        }

        if ($identifierType == 'sku' || $expectedIdType == 'sku') {
            $idBySku = $product->getIdBySku($productId);
            if ($idBySku) {
                $productId = $idBySku;
            } else if ($identifierType == 'sku') {
                // Return empty product because it was not found by originally specified SKU identifier
                return $product;
            }
        }

        if ($productId && is_numeric($productId)) {
            $product->load((int) $productId);
        }

        return $product;
    }


}

