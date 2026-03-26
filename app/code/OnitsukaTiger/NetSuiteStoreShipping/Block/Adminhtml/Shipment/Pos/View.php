<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Block\Adminhtml\Shipment\Pos;

/**
 * Shipment pos control form
 */
class View extends \Magento\Backend\Block\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Prepares layout of block
     *
     * @return void
     */
    protected function _prepareLayout()
    {
        $onclick = "savePosReceiptInfo($('shipment_pos').parentNode, '" . $this->getSubmitUrl() . "')";
        $this->addChild(
            'save_pos_button',
            \Magento\Backend\Block\Widget\Button::class,
            ['label' => __('Add'), 'class' => 'save', 'onclick' => $onclick]
        );
    }

    /**
     * Retrieve shipment model instance
     *
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getShipment()
    {
        return $this->_coreRegistry->registry('current_shipment');
    }

    /**
     * Retrieve save url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl(
            'store_shipping/shipment/addPos/',
            [
                'shipment_id' => $this->getShipment()->getEntityId()
            ]
        );
    }

    /**
     * Retrieve remove url
     *
     * @return string
     */
    public function getRemoveUrl()
    {
        return $this->getUrl(
            'store_shipping/shipment/removePos/',
            [
                'shipment_id' => $this->getShipment()->getEntityId()
            ]
        );
    }

    /**
     * Retrieve save button html
     *
     * @return string
     */
    public function getSaveButtonHtml()
    {
        return $this->getChildHtml('save_pos_button');
    }

    /**
     * @return string|null
     */
    public function getPosReceiptNumber()
    {
        $shipment = $this->getShipment();
        if ($shipment->getExtensionAttributes()->getPosReceiptNumber() != '') {
            return $shipment->getExtensionAttributes()->getPosReceiptNumber();
        }
        return null;
    }
}
