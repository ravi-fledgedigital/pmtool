<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Plugin\Sales\Model\Order\Pdf;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Collection;

class ShipmentPlugin
{
    /**
     * @var \Amasty\PDFCustom\Model\Order\Pdf\ShipmentFactory
     */
    private $shipmentFactory;

    /**
     * @var \Amasty\PDFCustom\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        \Amasty\PDFCustom\Model\Order\Pdf\ShipmentFactory $shipmentFactory,
        \Amasty\PDFCustom\Model\ConfigProvider $configProvider,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository
    ) {
        $this->shipmentFactory = $shipmentFactory;
        $this->configProvider = $configProvider;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @param \Magento\Sales\Model\Order\Pdf\Shipment $subject
     * @param callable $proceed
     * @param array $shipments
     *
     * @return \Zend_Pdf
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundGetPdf(\Magento\Sales\Model\Order\Pdf\Shipment $subject, callable $proceed, $shipments = [])
    {
        if ($shipments instanceof Collection) {
            $shipment = $shipments->getFirstItem();
        } else {
            $shipment = current($shipments);
        }

        if (!$shipment) {
            return $proceed($shipments);
        }
        /** @var Order $order */
        $order = $shipment->getOrder();

        if (!$this->configProvider->isEnabled() ||
            $this->templateRepository->getShipmentTemplateId($order->getStoreId(), $order->getCustomerGroupId()) == '0'
        ) {
            return $proceed($shipments);
        }

        /** @var \Amasty\PDFCustom\Model\Order\Pdf\Shipment $pdfRender */
        $pdfRender = $this->shipmentFactory->create();

        return $pdfRender->getPdf($shipments)->convertToZendPDF();
    }
}
