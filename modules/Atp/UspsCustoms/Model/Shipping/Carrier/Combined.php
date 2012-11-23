<?php

class Atp_UspsCustoms_Model_Shipping_Carrier_Combined
    extends Mage_Usa_Model_Shipping_Carrier_Abstract
    implements Mage_Shipping_Model_Carrier_Interface
{
      /**
     * Code of the carrier
     *
     * @var string
     */
    const CODE = 'combined';

    /**
     * Code of the carrier
     *
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * Rate request data
     *
     * @var Mage_Shipping_Model_Rate_Request|null
     */
    protected $_request = null;


    /**
     * Rate result data
     *
     * @var Mage_Shipping_Model_Rate_Result|null
     */
    protected $_result = null;

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

      $this->_request = $request;
      $this->_result = $this->_getQuotes();
      return $this->getResult();
    }

    /**
     * Get result of request
     *
     * @return mixed
     */
    public function getResult()
    {
       return $this->_result;
    }

    public function isTrackingAvailable()
    {
        return false;
    }

    protected function _getQuotes() {
      $result = Mage::getModel('shipping/rate_result');
      $rate = Mage::getModel('shipping/rate_result_method');
      $rate->setCarrier($this->getCode());
      $rate->setCarrierTitle($this->getConfigData('title'));
      // TODO: set proper titles
      $rate->setMethod('Combined');
      $rate->setMethodTitle('Combined Shipping');
      $rate->setCost(0);
      $rate->setPrice(0);
      $result->append($rate);
      return $result;
    }

}
