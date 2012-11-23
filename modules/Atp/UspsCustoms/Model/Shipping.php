<?php

class Atp_UspsCustoms_Model_Shipping extends Mage_Shipping_Model_Shipping
{


    /**
     * Collect rates of given carrier
     *
     * @param string                           $carrierCode
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Shipping
     */
    public function collectCarrierRates($carrierCode, $request)
    {
        /* @var $carrier Mage_Shipping_Model_Carrier_Abstract */
        $carrier = $this->getCarrierByCode($carrierCode, $request->getStoreId());
        if (!$carrier) {
            return $this;
        }
        $carrier->setActiveFlag($this->_availabilityConfigField);
        $result = $carrier->checkAvailableShipCountries($request);
        //xdebug_break();
        if (false !== $result && !($result instanceof Mage_Shipping_Model_Rate_Result_Error)) {
            $result = $carrier->proccessAdditionalValidation($request);
        }
        /*
        * Result will be false if the admin set not to show the shipping module
        * if the delivery country is not within specific countries
        */
        if (false !== $result){
            if (!$result instanceof Mage_Shipping_Model_Rate_Result_Error) {
                if ($carrier->getConfigData('shipment_requesttype')) {
                    $packages = $this->composePackagesForCarrier($carrier, $request);
                    if (!empty($packages)) {
                        $sumResults = array();
                        foreach ($packages as $packageIndex => $packageData) {
                          if (is_array($packageData)) {
                            list($packageCount, $weight, $width, $height, $length) = $packageData;
                            $request->setWidth($width);
                            $request->setHeight($height);
                            $request->setLength($length);
                            $request->setAutoConfig(true);
                          } else {
                            $weight = $packageIndex;
                            $packageCount = $packageData;
                          }
                            $request->setPackageWeight($weight);
                            $result = $carrier->collectRates($request);
                            if (!$result) {
                                return $this;
                            } else {
                                $result->updateRatePrice($packageCount);
                            }
                            $sumResults[] = $result;
                        }
                        if (!empty($sumResults) && count($sumResults) > 1) {
                            $result = array();
                            foreach ($sumResults as $res) {
                                if (empty($result)) {
                                    $result = $res;
                                    continue;
                                }
                                foreach ($res->getAllRates() as $method) {
                                    foreach ($result->getAllRates() as $resultMethod) {
                                        if ($method->getMethod() == $resultMethod->getMethod()) {
                                            $resultMethod->setPrice($method->getPrice() + $resultMethod->getPrice());
                                            continue;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        $result = $carrier->collectRates($request);
                    }
                } else {
                    $result = $carrier->collectRates($request);
                }
                if (!$result) {
                    return $this;
                }
            }
            if ($carrier->getConfigData('showmethod') == 0 && $result->getError()) {
                return $this;
            }
            // sort rates by price
            if (method_exists($result, 'sortRatesByPrice')) {
                $result->sortRatesByPrice();
            }
            $this->getResult()->append($result);
        }
        return $this;
    }


    /**
     * Compose Packages For Carrier.
     * Devides order into items and items into parts if it's neccesary
     *
     * @param Mage_Shipping_Model_Carrier_Abstract $carrier
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return array [int, float]
     */
    public function composePackagesForCarrier($carrier, $request)
    {
        $allItems   = $request->getAllItems();
        $fullItems  = array();
        $packageItems = array();

        $maxWeight  = (float) $carrier->getConfigData('max_package_weight');

        foreach ($allItems as $item) {
            if ($item->getProductType() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
                && $item->getProduct()->getShipmentType()
            ) {
                continue;
            }

            $qty            = $item->getQty();
            $changeQty      = true;
            $checkWeight    = true;
            $decimalItems   = array();

            if ($item->getParentItem()) {
                if (!$item->getParentItem()->getProduct()->getShipmentType()) {
                    continue;
                }
                $qty = $item->getIsQtyDecimal()
                    ? $item->getParentItem()->getQty()
                    : $item->getParentItem()->getQty() * $item->getQty();
            }

            $itemWeight = $item->getWeight();
            if ($item->getIsQtyDecimal() && $item->getProductType() != Mage_Catalog_Model_Product_Type::TYPE_BUNDLE) {
                $stockItem = $item->getProduct()->getStockItem();
                if ($stockItem->getIsDecimalDivided()) {
                   if ($stockItem->getEnableQtyIncrements() && $stockItem->getQtyIncrements()) {
                        $itemWeight = $itemWeight * $stockItem->getQtyIncrements();
                        $qty        = round(($item->getWeight() / $itemWeight) * $qty);
                        $changeQty  = false;
                   } else {
                       $itemWeight = $itemWeight * $item->getQty();
                       if ($itemWeight > $maxWeight) {
                           $qtyItem = floor($itemWeight / $maxWeight);
                           $decimalItems[] = array('weight' => $maxWeight, 'qty' => $qtyItem);
                           $weightItem = Mage::helper('core')->getExactDivision($itemWeight, $maxWeight);
                           if ($weightItem) {
                               $decimalItems[] = array('weight' => $weightItem, 'qty' => 1);
                           }
                           $checkWeight = false;
                       } else {
                           $itemWeight = $itemWeight * $item->getQty();
                       }
                   }
                } else {
                    $itemWeight = $itemWeight * $item->getQty();
                }
            }

            if ($checkWeight && $maxWeight && $itemWeight > $maxWeight) {
                return array();
            }

            if ($changeQty && !$item->getParentItem() && $item->getIsQtyDecimal()
                && $item->getProductType() != Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
            ) {
                $qty = 1;
            }

            if (!empty($decimalItems)) {
                foreach ($decimalItems as $decimalItem) {
                    $fullItems = array_merge($fullItems,
                        array_fill(0, $decimalItem['qty'] * $qty, $decimalItem['weight'])
                    );
                }
            } else {
              //xdebug_break();
              $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
              $packageLength = (float) $product->getPackageLength();
              $packageWidth = (float) $product->getPackageWidth();
              $packageHeight = (float) $product->getPackageHeight();
              if ($packageLength && $packageWidth && $packageHeight){ 
                $packageItems[] = array($qty, $itemWeight, $packageWidth, $packageHeight, $packageLength);
              } else {
                $fullItems = array_merge($fullItems, array_fill(0, $qty, $itemWeight));
              }
            }
        }
        sort($fullItems);

        $sliced = $this->_makePieces($fullItems, $maxWeight);
        return $all = array_merge($sliced, $packageItems);
    }

}
