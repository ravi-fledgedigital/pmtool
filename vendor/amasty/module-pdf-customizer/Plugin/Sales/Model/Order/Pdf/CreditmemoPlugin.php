<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Plugin\Sales\Model\Order\Pdf;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Pdf\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\Collection;

class CreditmemoPlugin
{
    /**
     * @var \Amasty\PDFCustom\Model\Order\Pdf\CreditmemoFactory
     */
    private $creditmemoFactory;

    /**
     * @var \Amasty\PDFCustom\Model\ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        \Amasty\PDFCustom\Model\Order\Pdf\CreditmemoFactory $creditmemoFactory,
        \Amasty\PDFCustom\Model\ConfigProvider $configProvider,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository
    ) {
        $this->creditmemoFactory = $creditmemoFactory;
        $this->configProvider = $configProvider;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @param Creditmemo $subject
     * @param callable $proceed
     * @param array $creditmemos
     *
     * @return \Zend_Pdf
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundGetPdf(Creditmemo $subject, callable $proceed, $creditmemos = [])
    {
        if ($creditmemos instanceof Collection) {
            $creditmemo = $creditmemos->getFirstItem();
        } else {
            $creditmemo = current($creditmemos);
        }

        if (!$creditmemo) {
            return $proceed($creditmemos);
        }
        /** @var Order $order */
        $order = $creditmemo->getOrder();
        if (!$this->configProvider->isEnabled() ||
            $this->templateRepository->getCreditmemoTemplateId(
                $order->getStoreId(),
                $order->getCustomerGroupId()
            ) == '0'
        ) {
            return $proceed($creditmemos);
        }

        /** @var \Amasty\PDFCustom\Model\Order\Pdf\Creditmemo $pdfRender */
        $pdfRender = $this->creditmemoFactory->create();

        return $pdfRender->getPdf($creditmemos)->convertToZendPDF();
    }
}
