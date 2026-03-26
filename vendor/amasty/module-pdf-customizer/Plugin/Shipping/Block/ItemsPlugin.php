<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Plugin\Shipping\Block;

use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\Source\LinkType;
use Magento\Customer\Model\Context;

class ItemsPlugin
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    private $httpContext;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    private $currentShipment;

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        ConfigProvider $configProvider,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Framework\Registry $registry,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository
    ) {
        $this->configProvider = $configProvider;
        $this->httpContext = $httpContext;
        $this->registry = $registry;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @param \Magento\Shipping\Block\Items $subject
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     */
    public function beforeGetPrintShipmentUrl($subject, $shipment)
    {
        $this->currentShipment = $shipment;
    }

    /**
     * @param \Magento\Shipping\Block\Items $subject
     * @param string $result
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetPrintShipmentUrl($subject, $result)
    {
        $order = $subject->getOrder();
        if (!$this->isEnabledLinkReplace($order)) {
            return $result;
        }
        if (!$this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $subject->getUrl('custompdf/guest/shipment', ['shipment_id' => $this->currentShipment->getId()]);
        }

        return $subject->getUrl('custompdf/sales/shipment', ['shipment_id' => $this->currentShipment->getId()]);
    }

    /**
     * @param \Magento\Shipping\Block\Items $subject
     * @param string $result
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function afterGetPrintAllShipmentsUrl($subject, $result)
    {
        $order = $subject->getOrder();
        if (!$this->isEnabledLinkReplace($order)) {
            return $result;
        }
        if (!$this->httpContext->getValue(Context::CONTEXT_AUTH)) {
            return $subject->getUrl('custompdf/guest/shipment', ['order_id' => $order->getId()]);
        }

        return $subject->getUrl('custompdf/sales/shipment', ['order_id' => $order->getId()]);
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     */
    private function isEnabledLinkReplace($order)
    {
        $storeId = $order->getStoreId();
        $customerGroupId = $order->getCustomerGroupId();
        if (!$this->configProvider->isEnabled($storeId) ||
            !$this->templateRepository->getShipmentTemplateId($storeId, $customerGroupId)
        ) {
            return false;
        }
        $shipmentTypeLink = $this->configProvider->getShipmentLinkType($storeId);

        return $shipmentTypeLink == LinkType::TYPE_REPLACE;
    }
}
