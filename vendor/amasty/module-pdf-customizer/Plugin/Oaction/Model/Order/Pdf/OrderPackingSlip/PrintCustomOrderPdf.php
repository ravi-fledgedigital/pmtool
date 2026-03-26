<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Plugin\Oaction\Model\Order\Pdf\OrderPackingSlip;

use Amasty\Oaction\Model\Order\Pdf\OrderPackingSlip;
use Amasty\PDFCustom\Model\ConfigProvider;
use Amasty\PDFCustom\Model\Order\Pdf\OrderFactory;
use Amasty\PDFCustom\Model\ResourceModel\TemplateRepository;

class PrintCustomOrderPdf
{
    /**
     * @var OrderFactory
     */
    private $orderPdfFactory;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        OrderFactory $orderPdfFactory,
        ConfigProvider $configProvider,
        TemplateRepository $templateRepository
    ) {
        $this->orderPdfFactory = $orderPdfFactory;
        $this->configProvider = $configProvider;
        $this->templateRepository = $templateRepository;
    }

    public function aroundGetPdf(
        OrderPackingSlip $subject,
        callable $proceed,
        array $orders = []
    ): \Zend_Pdf {
        if (empty($orders)) {
            return $proceed($orders);
        }

        $order = current($orders);

        if (!$this->configProvider->isEnabled()
            || !$this->templateRepository->getOrderTemplateId($order->getStoreId(), $order->getCustomerGroupId())
        ) {
            return $proceed($orders);
        }

        $pdfRender = $this->orderPdfFactory->create();

        return $pdfRender->getPdf($orders)->convertToZendPDF();
    }
}
