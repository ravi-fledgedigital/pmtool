<?php
declare(strict_types=1);

namespace OnitsukaTiger\Shipment\Model;

use Magento\Framework\Model\AbstractModel;
use OnitsukaTiger\Shipment\Model\ResourceModel\ShipmentAttributes as ResourceModel;

/**
 * Class ShipmentAttributes
 * @package OnitsukaTiger\Shipment\Model
 */
class ShipmentAttributes extends AbstractModel
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @return int
     */
    public function getShipmentId() : int
    {
        return (int)$this->getData('shipment_id');
    }

    /**
     * @param int $shipmentId
     *
     * @return $this
     */
    public function setShipmentId(int $shipmentId)
    {
        return $this->setData('shipment_id', $shipmentId);
    }

    /**
     * @return string
     */
    public function getNetsuiteFulfillmentId() : string
    {
        return (string) $this->getData('netsuite_fulfillment_id');
    }

    /**
     * @param string $netsuiteFulfillmentId
     *
     * @return $this
     */
    public function setNetsuiteFulfillmentId(string $netsuiteFulfillmentId)
    {
        return $this->setData('netsuite_fulfillment_id', $netsuiteFulfillmentId);
    }

    /**
     * @return string
     */
    public function getStatus() : string
    {
        return (string) $this->getData('status');
    }

    /**
     * @param string $status
     *
     * @return ShipmentAttributes
     */
    public function setStatus(string $status)
    {
        return $this->setData('status', $status);
    }

    /**
     * @return string
     */
    public function getNetsuiteInternalId() : string
    {
        return (string) $this->getData('netsuite_internal_id');
    }

    /**
     * @param string $netsuiteInternalId
     *
     * @return ShipmentAttributes
     */
    public function setNetsuiteInternalId(string $netsuiteInternalId)
    {
        return $this->setData('netsuite_internal_id', $netsuiteInternalId);
    }

    /**
     * @return string
     */
    public function getActReleaseDate() : string
    {
        return (string) $this->getData('act_release_date');
    }

    /**
     * @param string $releaseDate
     *
     * @return ShipmentAttributes
     */
    public function setActReleaseDate(string $releaseDate)
    {
        return $this->setData('act_release_date', $releaseDate);
    }

    /**
     * @return string
     */
    public function getPosReceiptNumber() : string
    {
        return (string) $this->getData('pos_receipt_number');
    }

    /**
     * @param $posReceiptNumber
     *
     * @return ShipmentAttributes
     */
    public function setPosReceiptNumber($posReceiptNumber)
    {
        return $this->setData('pos_receipt_number', $posReceiptNumber);
    }

    /**
     * @return string
     */
    public function getStockPosFlag() : string
    {
        return (string) $this->getData('stock_pos_flag');
    }

    /**
     * @param $number
     *
     * @return ShipmentAttributes
     */
    public function setStockPosFlag($number)
    {
        $this->setData('stock_pos_flag', $number);
    }

    /**
     * @return string
     */
    public function getCustbodyAvnEinvBillingName() : string
    {
        return (string) $this->getData('custbody_avn_einv_billing_name');
    }

    /**
     * @param $name
     * @return void
     */
    public function setCustbodyAvnEinvBillingName($name)
    {
        $this->setData('custbody_avn_einv_billing_name', $name);
    }

    /**
     * @return string
     */
    public function getCustbodyAvnEinvBillingAdd() : string
    {
        return (string) $this->getData('custbody_avn_einv_billing_add');
    }

    /**
     * @param $name
     * @return void
     */
    public function setCustbodyAvnEinvBillingAdd($name)
    {
        $this->setData('custbody_avn_einv_billing_add', $name);
    }

    /**
     * @return string
     */
    public function getCustbodyAvnEinvBillingVatno() : string
    {
        return (string) $this->getData('custbody_avn_einv_billing_vatno');
    }

    /**
     * @param $name
     * @return void
     */
    public function setCustbodyAvnEinvBillingVatno($name)
    {
        $this->setData('custbody_avn_einv_billing_vatno', $name);
    }

    /**
     * @return string
     */
    public function getCustbodyAvnEinvBillingEmail() : string
    {
        return (string) $this->getData('custbody_avn_einv_billing_email');
    }

    /**
     * @param $name
     * @return void
     */
    public function setCustbodyAvnEinvBillingEmail($name)
    {
        $this->setData('custbody_avn_einv_billing_email', $name);
    }

    /**
     * @return string
     */
    public function getCustbodyAvnEinvBillingPhoneno() : string
    {
        return (string) $this->getData('custbody_avn_einv_billing_phoneno');
    }

    /**
     * @param $name
     * @return void
     */
    public function setCustbodyAvnEinvBillingPhoneno($name)
    {
        $this->setData('custbody_avn_einv_billing_phoneno', $name);
    }

    /**
     * @return string
     */
    public function getCustbodyAvnEinvPurchaserName() : string
    {
        return (string) $this->getData('custbody_avn_einv_purchaser_name');
    }

    /**
     * @param $name
     * @return void
     */
    public function setCustbodyAvnEinvPurchaserName($name)
    {
        $this->setData('custbody_avn_einv_purchaser_name', $name);
    }
}
