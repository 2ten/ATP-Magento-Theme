<?php
/*

<action type="catalog/convert_adapter_product" method="load">
    <var name="store"><![CDATA[0]]></var>
    <var name="filter/attribute_set"><![CDATA[68]]></var>
</action>

<action type="catalog/convert_parser_product" method="unparse">
    <var name="store"><![CDATA[0]]></var>
    <var name="url_field"><![CDATA[0]]></var>
</action>

<action type="dataflow/convert_mapper_column" method="map">
    <var name="map">
        <map name="sku"><![CDATA[sku]]></map>
        <map name="title_thefind"><![CDATA[product-name]]></map>
        <map name="brand"><![CDATA[brand]]></map>
        <map name="description"><![CDATA[product-description]]></map>
        <map name="handbag_strap_type"><![CDATA[bullet-point1]]></map>
        <map name="handbag_motif"><![CDATA[bullet-point2]]></map>
        <map name="price"><![CDATA[item-price]]></map>
        <map name="weight"><![CDATA[shipping-weight]]></map>
        <map name="qty"><![CDATA[quantity]]></map>
        <map name="handbag_size"><![CDATA[size]]></map>
        <map name="measure_weight"><![CDATA[item-weight]]></map>
        <map name="measure_length"><![CDATA[item-length]]></map>
        <map name="measure_width"><![CDATA[item-width]]></map>
        <map name="measure_height"><![CDATA[item-height]]></map>
        <map name="measure_strap_drop"><![CDATA[measure_strap_drop]]></map>
    </var>
    <var name="_only_specified">true</var>
</action>

<action type="dataflow/convert_parser_csv" method="unparse">
    <var name="delimiter"><![CDATA[\t]]></var>
    <var name="enclose"><![CDATA["]]></var>
    <var name="fieldnames">true</var>
</action>

<action type="dataflow/convert_adapter_io" method="save">
    <var name="type">file</var>
    <var name="path">var/export</var>
    <var name="filename"><![CDATA[amazon-mochilas.csv]]></var>
</action>

*/

class Atp_Dataflow_Model_Convert_Parser_JewelryParserForAmazon extends Mage_Dataflow_Model_Convert_Parser_Csv
{

  const STORE_ADMIN = 0;

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

        /*
        $fieldList['bullet-point3'] = 'bullet-point3';
        $fieldList['bullet-point4'] = 'bullet-point4';
        $fieldList['bullet-point5'] = 'bullet-point5';
        $fieldList['currency'] = 'currency';
        $fieldList['shipping-weight-unit-measure'] = 'shipping-weight-unit-measure';
        $fieldList['leadtime-to-ship'] = 'leadtime-to-ship';
        $fieldList['search-terms1'] = 'search-terms1';
        $fieldList['search-terms2'] = 'search-terms2';
        $fieldList['search-terms3'] = 'search-terms3';
        $fieldList['item-type'] = 'item-type';
        $fieldList['department'] = 'department';
        $fieldList['color'] = 'color';
        $fieldList['color-map'] = 'color-map';
        $fieldList['apparel-closure-type'] = 'apparel-closure-type';
        $fieldList['is-stain-resistant'] = 'is-stain-resistant';
        $fieldList['material-type1'] = 'material-type1';
        $fieldList['import-designation'] = 'import-designation';
        $fieldList['country-as-labeled'] = 'country-as-labeled';
        $fieldList['pattern-style'] = 'pattern-style';
        $fieldList['occasion-lifestyle'] = 'occasion-lifestyle';
        $fieldList['item-package-quantity'] = 'item-package-quantity';
        $fieldList['item-length-unit-of-measure'] = 'item-length-unit-of-measure';
        $fieldList['item-weight-unit-of-measure_weight'] = 'item-weight-unit-of-measure_weight';
        $fieldList['shoulder-strap-drop-unit-of-measure'] = 'shoulder-strap-drop-unit-of-measure';
        $fieldList['is-gift-message-available'] = 'is-gift-message-available';
        $fieldList['is-gift-wrap-available'] = 'is-gift-wrap-available';
        */
        $fieldList['MainImageURL'] = 'MainImageURL';
        // Extra Images
        for ($i=0; $i<8; $i++) {
          $k = 'OtherImageURL' . ($i+1);
          $fieldList[$k] = $k;
        }

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
              if (strtolower(substr($row['SKU'], 0, 3)) == 'cer') continue;
              //xdebug_break();

              $product = $this->getProduct($row['SKU'], self::STORE_ADMIN);
              /*
              $row['bullet-point1'] = sprintf('Strap Type: %s', $row['bullet-point1']);
              $row['bullet-point2'] = sprintf('Motif: %s', $row['bullet-point2']);
              $row['bullet-point3'] = 'Authentic Wayuu Mochila Bag';
              $row['bullet-point4'] = 'Made in La Guajira, Colombia';
              $row['bullet-point5'] = 'Handmade';
              $row['currency'] = 'USD';
              $row['shipping-weight-unit-measure'] = 'LB';
              $row['leadtime-to-ship'] = 1;
              $row['search-terms1'] = 'Wayuu Mochila';
              $row['search-terms2'] = 'Mochila Bag';
              $row['search-terms3'] = 'Shoulder Bag';
              $row['item-type'] = 'hobo-style-handbags';
              $row['department'] = 'womens';
              $row['color'] = 'multicolor';
              $row['color-map'] = 'multi';
              $row['apparel-closure-type'] = 'no-closure';
              $row['is-stain-resistant'] = 'false';
              $row['material-type1'] = '100% cotton';
              $row['import-designation'] = 'Imported';
              $row['country-as-labeled'] = 'CO';
              $row['pattern-style'] = 'woven';
              $row['occasion-lifestyle'] = 'Informal';
              $row['item-package-quantity'] = 1;
              $row['item-length-unit-of-measure'] = 'IN';
              $row['item-weight-unit-of-measure_weight'] = 'OZ';
              $row['item-length'] = $this->getInchesFromText($row['item-length']);
              $row['item-width'] = $this->getInchesFromText($row['item-width']);
              $row['item-height'] = $this->getInchesFromText($row['item-height']);
              $row['shoulder-strap-drop-unit-of-measure'] = 'IN';
              $row['is-gift-message-available'] = 'true';
              $row['is-gift-wrap-available'] = 'true';
              $row['registered-parameter'] = '';
              $row['update-delete'] = 'Update';
              */
              if ($attribute = $product->getResource()->getAttribute('image')) {
                $row['MainImageURL'] = $attribute->getFrontend()->getUrl($product);
              } else {
                $row['MainImageURL'] = '';
              }

              // TODO: Use iterator syntax next time
              $mediaGallery = $product->getMediaGalleryImages()->toArray();
              if ($mediaGallery) {
                $count = $mediaGallery['totalRecords'];
                $count = $count < 8 ? $count : 8;
                for ($i = 0; $i < $count; $i++) {
                  $row['OtherImageURL' . ($i + 1)] = $mediaGallery['items'][$i]['url'];
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
     * It may sound funny but indeed, this one extract an inches value from a given text
     *
     * @return number
     */
    protected function getInchesFromText($text) {
      return $text;
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

