<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Block\Adminhtml;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\Invoice;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreShipping;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTiger\NetSuiteStoreShipping\Model\StoreSEA;
use OnitsukaTiger\Shipment\Model\ShipmentStatus;
/**
 * Class View
 * @package OnitsukaTiger\NetSuiteStoreShipping\Block\Adminhtml
 */
class View extends \Magento\Shipping\Block\Adminhtml\View
{
    /**
     * @var StoreShipping
     */
    protected $storeShipping;
    private StoreSEA $storeSEA;

    /**
     * View constructor.
     * @param StoreShipping $storeShipping
     * @param StoreSEA $storeSEA
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        StoreShipping $storeShipping,
        StoreSEA    $storeSEA,
        Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->storeShipping = $storeShipping;
        $this->storeSEA = $storeSEA;
        parent::__construct($context, $registry, $data);
    }

    protected function _construct()
    {
        $this->_objectId = 'shipment_id';
        $this->_mode = 'view';

        parent::_construct();

        $this->buttonList->remove('reset');
        $this->buttonList->remove('delete');
        if (!$this->getShipment()) {
            return;
        }
        $sourceCode = $this->getShipment()->getExtensionAttributes()->getSourceCode();
        $shipmentStatus = $this->getShipment()->getExtensionAttributes()->getStatus();
        $fulfilmentId = $this->getShipment()->getExtensionAttributes()->getNetsuiteFulfillmentId();
        if ($this->storeShipping->isShippingFromWareHouse($sourceCode)) {
            if ($this->_authorization->isAllowed('Magento_Sales::emails')) {
                $this->buttonList->update('save', 'label', __('Send Tracking Information'));
                $this->buttonList->update(
                    'save',
                    'onclick',
                    "deleteConfirm('" . __(
                        'Are you sure you want to send a Shipment email to customer?'
                    ) . "', '" . $this->getEmailUrl() . "')"
                );
            }

            if ($this->getShipment()->getId()) {
                $this->buttonList->add(
                    'print',
                    [
                        'label' => __('Print'),
                        'class' => 'save',
                        'onclick' => 'setLocation(\'' . $this->getPrintUrl() . '\')'
                    ]
                );
            }
            if ($this->_authorization->isAllowed('Magento_Sales::ship_recover_pack')) {
                if (OrderStatus::STATUS_PREPACKED == $shipmentStatus &&
                    $this->storeSEA->isScopeSEA($this->getShipment()->getStoreId())
                ) {
                    $this->buttonList->add(
                        'recover_packed',
                        [
                            'label' => __('Recover Packed'),
                            'class' => 'recover_packed',
                            'onclick' => 'setLocation(\'' . $this->getRecoverPackedUrl($this->getShipment()->getIncrementId(), $fulfilmentId) . '\')'
                        ]
                    );
                }
            }
        } else {
            $this->buttonList->remove('save');
            $this->buttonList->remove('cancel-shipment');
            $this->buttonList->remove('print');
            if ($shipmentStatus === 'processing' || $shipmentStatus === 'prepacked') {
                $this->buttonList->add(
                    'delete-reject',
                    [
                        'label' => __('Delete (reject)'),
                        'class' => 'delete-reject',
                        'onclick' => 'confirmSetLocation(\'' . __('Are you sure you want to cancel this shipment?') . '\',\'' . $this->callApiDelete($this->getShipment()->getId()) . '\')'
                    ]
                );
                $this->buttonList->add(
                    'print-packing-list',
                    [
                        'label' => __('Print (Packing List)'),
                        'class' => 'print-packing-list',
                        'onclick' => 'setLocation(\'' . $this->getPrintPackingListUrl($this->getShipment()->getId()) . '\')'
                    ]
                );
                $this->buttonList->add(
                    'pack',
                    [
                        'label' => __('Pack'),
                        'class' => 'pack',
                        'onclick' => 'confirmSetLocation(\'' . __('Are you sure you want to <strong>Pack</strong> this shipment?') . '\',\'' . $this->callApiPack($this->getShipment()->getId()) . '\')'
                    ]
                );
            } elseif ($shipmentStatus === 'packed') {
                $this->defaultButton();
                $this->buttonList->add(
                    'ship',
                    [
                        'label' => __('Ship'),
                        'class' => 'save',
                        'onclick' => 'confirmSetLocation(\'' . __('Are you sure you want to <strong>Ship</strong> this shipment?') . '\',\'' . $this->callApiShip($this->getShipment()->getId()) . '\')'
                    ]
                );
            } else {
                $this->defaultButton();
            }
        }
        if ($this->_authorization->isAllowed('Magento_Sales::ship_recover_processing')) {
            if (ShipmentStatus::STATUS_PACKED == $shipmentStatus &&
                $this->storeSEA->isScopeSEA($this->getShipment()->getStoreId())
            ) {
                $this->buttonList->add(
                    'recover_packed',
                    [
                        'label' => __('Recover Processing'),
                        'class' => 'recover_processing',
                        'onclick' => 'setLocation(\'' . $this->getRecoverProcessingUrl($this->getShipment()->getIncrementId()) . '\')'
                    ]
                );
            }
        }

        if (in_array($this->getShipment()->getStoreId(), [8, 10]) && $this->getShipment()->getExtensionAttributes()->getStatus() != 'item_lost') {
            $this->buttonList->add(
                'item_lost',
                [
                    'label' => __('Item Lost'),
                    'class' => 'item_lost',
                    'onclick' => 'setLocation(\'' . $this->getItemLostUrl($this->getShipment()->getIncrementId()) . '\')'
                ]
            );
        }

        if ($this->getShipment()->getStoreId() == 5) {
            $this->buttonList->remove('print');
        }
    }

    public function defaultButton()
    {
        $order = $this->getShipment()->getOrder();
        /** @var Invoice $invoice */
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        $this->buttonList->add(
            'print-invoice',
            [
                'label' => __('Print (invoice)'),
                'class' => 'save',
                'onclick' => 'setLocation(\'' . $this->getPrintInvoiceUrl($invoice->getId()) . '\')'
            ]
        );

        $this->buttonList->add(
            'print-packing-list',
            [
                'label' => __('Print (Packing List)'),
                'class' => 'print-packing-list',
                'onclick' => 'setLocation(\'' . $this->getPrintPackingListUrl($this->getShipment()->getId()) . '\')'
            ]
        );

        $this->buttonList->add(
            'print-awb',
            [
                'label' => __('Print (AWB)'),
                'class' => 'save',
                'onclick' => 'setLocation(\'' . $this->getPrintAwbUrl($this->getShipment()->getId()) . '\')'
            ]
        );
    }

    /**
     * @param $shipmentId
     * @return string
     */
    public function getPrintPackingListUrl($shipmentId)
    {
        return $this->getUrl(
            'store_shipping/packinglist/print',
            [
                'shipment_id' => $shipmentId
            ]
        );
    }

    /**
     * @param $invoiceId
     * @return string
     */
    public function getPrintInvoiceUrl($invoiceId)
    {
        return $this->getUrl(
            'store_shipping/invoice/print',
            [
                'invoice_id' => $invoiceId
            ]
        );
    }

    /**
     * @param $shipmentId
     * @return string
     */
    public function getPrintAwbUrl($shipmentId)
    {
        return $this->getUrl(
            'store_shipping/shipment/print',
            [
                'shipment_id' => $shipmentId
            ]
        );
    }

    /**
     * @param bool $flag
     * @return \Magento\Shipping\Block\Adminhtml\View
     */
    public function updateBackButtonUrl($flag)
    {
        if ($flag) {
            $sourceCode = $this->getShipment()->getExtensionAttributes()->getSourceCode();
            if ($flag === StoreShipping::STORE_SHIPPING_ROUTE) {
                return $this->buttonList->update(
                    'back',
                    'onclick',
                    'setLocation(\'' . $this->getUrl('store_shipping/shipment/', ['source_code' => $sourceCode]) . '\')'
                );
            }

            if ($this->getShipment()->getBackUrl()) {
                return $this->buttonList->update(
                    'back',
                    'onclick',
                    'setLocation(\'' . $this->getShipment()->getBackUrl() . '\')'
                );
            }

            return $this->buttonList->update(
                'back',
                'onclick',
                'setLocation(\'' . $this->getUrl('sales/shipment/') . '\')'
            );
        }
        return $this;
    }

    /**
     * @param $shipmentId
     * @return string
     */
    public function callApiPack($shipmentId)
    {
        return $this->getUrl(
            'store_shipping/shipment/pack',
            [
                'shipment_id' => $shipmentId
            ]
        );
    }

    /**
     * @param $shipmentId
     * @return string
     */
    public function callApiShip($shipmentId)
    {
        return $this->getUrl(
            'store_shipping/shipment/ship',
            [
                'shipment_id' => $shipmentId
            ]
        );
    }

    /**
     * @param $shipmentId
     * @return string
     */
    public function callApiDelete($shipmentId)
    {
        return $this->getUrl(
            'store_shipping/shipment/delete',
            [
                'shipment_id' => $shipmentId,
                'come_from' => $this->getRequest()->getParam('come_from')
            ]
        );
    }

    /**
     * @param $shipmentId
     * @return string
     */
    public function getRecoverPackedUrl($shipmentId, $fulfilmentId)
    {
        return $this->getUrl(
            'store_shipping/shipment/recoverPacked',
            [
                'shipment_increment_id' => $shipmentId,
                'fulfillment_id' => $fulfilmentId
            ]
        );
    }

    public function getRecoverProcessingUrl($shipmentId)
    {
        return $this->getUrl(
            'store_shipping/shipment/recoverProcessing',
            [
                'shipment_increment_id' => $shipmentId,
            ]
        );
    }

    private function getItemLostUrl($shipmentId)
    {
        return $this->getUrl(
            'store_shipping/shipment/itemLost',
            [
                'shipment_id' => $shipmentId,
            ]
        );
    }


}
