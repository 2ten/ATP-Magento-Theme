<?php
class Atp_UspsCustoms_Model_Shipping_Carrier_Usps extends Mage_Usa_Model_Shipping_Carrier_Usps
{
    /**
     * Processing additional validation to check if carrier applicable.
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Carrier_Abstract|Mage_Shipping_Model_Rate_Result_Error|boolean
     */
    public function proccessAdditionalValidation(Mage_Shipping_Model_Rate_Request $request)
    {
      // make sure every item has packaging information
      //return parent::proccessAdditionalValidation($request);
        //Skip by item validation if there is no items in request
        //xdebug_break();
        if(!count($this->getAllItems($request))) {
            return $this;
        }

        $maxAllowedWeight   = (float) $this->getConfigData('max_package_weight');
        $errorMsg           = '';
        $configErrorMsg     = $this->getConfigData('specificerrmsg');
        $defaultErrorMsg    = Mage::helper('shipping')->__('The shipping module is not available.');
        $showMethod         = $this->getConfigData('showmethod');

        foreach ($this->getAllItems($request) as $item) {
            if ($item->getProduct() && $item->getProduct()->getId()) {
                $weight         = $item->getProduct()->getWeight();
                $stockItem      = $item->getProduct()->getStockItem();
                $doValidation   = true;

                if ($stockItem->getIsQtyDecimal() && $stockItem->getIsDecimalDivided()) {
                    if ($stockItem->getEnableQtyIncrements() && $stockItem->getQtyIncrements()) {
                        $weight = $weight * $stockItem->getQtyIncrements();
                    } else {
                        $doValidation = false;
                    }
                } elseif ($stockItem->getIsQtyDecimal() && !$stockItem->getIsDecimalDivided()) {
                    $weight = $weight * $item->getQty();
                }

                $product = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                $packageLength = (float) $product->getPackageLength();
                $packageWidth = (float) $product->getPackageWidth();
                $packageHeight = (float) $product->getPackageHeight();

                if (!($weight > 0) || !($packageLength && $packageWidth && $packageHeight) || ($doValidation && $weight > $maxAllowedWeight)) {
                    $errorMsg = ($configErrorMsg) ? $configErrorMsg : $defaultErrorMsg;
                    break;
                }
            }
        }

        if (!$errorMsg && !$request->getDestPostcode() && $this->isZipCodeRequired($request->getDestCountryId())) {
            $errorMsg = Mage::helper('shipping')->__('This shipping method is not available, please specify ZIP-code');
        }

        if ($errorMsg && $showMethod) {
            $error = Mage::getModel('shipping/rate_result_error');
            $error->setCarrier($this->_code);
            $error->setCarrierTitle($this->getConfigData('title'));
            $error->setErrorMessage($errorMsg);
            return $error;
        } elseif ($errorMsg) {
            return false;
        }
        return $this;

    }


    /**
     * Collect and get rates
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Shipping_Model_Rate_Result|bool|null
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request)
    {
        if (!$this->getConfigFlag($this->_activeFlag)) {
            return false;
        }

        $this->setRequest($request);

        $this->_result = $this->_getQuotes();

        $this->_updateFreeMethodQuote($request);

        return $this->getResult();
    }

    /**
     * Prepare and set request to this instance
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return Mage_Usa_Model_Shipping_Carrier_Usps
     */
    public function setRequest(Mage_Shipping_Model_Rate_Request $request)
    {
      if ($request->getAutoConfig()) {
        return $this->setRequestAutoConfig($request);
      } else {
        return parent::setRequest($request);
      }
    }

    public function setRequestAutoConfig(Mage_Shipping_Model_Rate_Request $request) {

        $this->_request = $request;
        //xdebug_break();
        $r = new Varien_Object();

        if ($request->getUspsUserid()) {
            $userId = $request->getUspsUserid();
        } else {
            $userId = $this->getConfigData('userid');
        }
        $r->setUserId($userId);

        $dims = array($request->getLength(), $request->getWidth(), $request->getHeight());
        sort($dims);
        $length = array_pop($dims);
        $girth = 2 * ($dims[0] + $dims[1]);

        $r->setLength($request->getLength());
        $r->setWidth($request->getWidth());
        $r->setHeight($request->getHeight());
        $r->setGirth($girth);
        $r->setSize($length > 12 ? 'LARGE' : 'REGULAR');
        $r->setContainer($length > 12 ? 'RECTANGULAR' : 'VARIABLE');

        if ($request->getLimitMethod()) {
            $r->setService($request->getLimitMethod());
        } else {
            $r->setService('ALL');
        }

        if ($request->getUspsMachinable()) {
            $machinable = $request->getUspsMachinable();
        } else {
            $machinable = $this->getConfigData('machinable');
        }
        $r->setMachinable($machinable);

        if ($request->getOrigPostcode()) {
            $r->setOrigPostal($request->getOrigPostcode());
        } else {
            $r->setOrigPostal(Mage::getStoreConfig(
                Mage_Shipping_Model_Shipping::XML_PATH_STORE_ZIP,
                $request->getStoreId()
            ));
        }

        if ($request->getOrigCountryId()) {
            $r->setOrigCountryId($request->getOrigCountryId());
        } else {
            $r->setOrigCountryId(Mage::getStoreConfig(
                Mage_Shipping_Model_Shipping::XML_PATH_STORE_COUNTRY_ID,
                $request->getStoreId()
            ));
        }

        if ($request->getDestCountryId()) {
            $destCountry = $request->getDestCountryId();
        } else {
            $destCountry = self::USA_COUNTRY_ID;
        }

        $r->setDestCountryId($destCountry);

        $weight = $this->getTotalNumOfBoxes($request->getPackageWeight());
        $r->setWeightPounds(floor($weight));
        $r->setWeightOunces(round(($weight-floor($weight)) * self::OUNCES_POUND, 1));

        if (!$this->_isUSCountry($destCountry)) {
            $r->setDestCountryName($this->_getCountryName($destCountry));
        }

        if ($request->getFreeMethodWeight() != $request->getPackageWeight()) {
            $r->setFreeMethodWeight($request->getFreeMethodWeight());
        }

        if ($request->getDestPostcode()) {
            $r->setDestPostal($request->getDestPostcode());
        }

        $r->setValue($request->getPackageValue());
        $r->setValueWithDiscount($request->getPackageValueWithDiscount());

        $r->setBaseSubtotalInclTax($request->getBaseSubtotalInclTax());

        $this->_rawRequest = $r;

        return $this;

    }


    /**
     * @param Mage_Shipping_Model_Rate_Request $request
     * @return null
     */
    protected function _updateFreeMethodQuote($request)
    {
        $store = Mage::app()->getStore()->getCode();
        if ($request->getDestCountryId()) {
            $destCountry = $request->getDestCountryId();
        } else {
            $destCountry = self::USA_COUNTRY_ID;
        }

        if ($store != 'jewelry' || !$this->_isUSCountry($destCountry)) {
          return;
        }

        $freeRateId = false;
        if (is_object($this->_result)) {
            foreach ($this->_result->getAllRates() as $i=>$item) {
              if (strpos($item->getMethod(), 'First-Class') !== false) {
                    $freeRateId = $i;
                    break;
                }
            }
        }

        if ($freeRateId === false) {
            return;
        }
        $price = 0;
        $this->_result->getRateById($freeRateId)->setPrice($price);
    }


}


