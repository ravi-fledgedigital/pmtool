<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Sales;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Invoice extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface
     */
    private $orderAuthorization;

    /**
     * @var \Magento\Sales\Controller\AbstractController\OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Pdf\Invoice
     */
    private $invoicePdf;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    public function __construct(
        \Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface $orderAuthorization,
        \Magento\Sales\Controller\AbstractController\OrderLoaderInterface $orderLoader,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Amasty\PDFCustom\Model\Order\Pdf\Invoice $invoicePdf,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->orderAuthorization = $orderAuthorization;
        $this->orderLoader = $orderLoader;
        $this->invoiceRepository = $invoiceRepository;
        $this->invoicePdf = $invoicePdf;
        $this->registry = $registry;
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * @return bool|ResponseInterface|ResultInterface|void
     */
    public function execute()
    {
        $invoiceId = (int)$this->getRequest()->getParam('invoice_id');
        if ($invoiceId) {
            /** @var \Magento\Sales\Model\Order\Invoice $invoice */
            $invoice = $this->invoiceRepository->get($invoiceId);
            $order = $invoice->getOrder();
            $this->registry->register('current_order', $order);
        } else {
            $result = $this->orderLoader->load($this->_request);
            if ($result instanceof ResultInterface) {
                return $result;
            }
            /** @var \Magento\Sales\Model\Order $order */
            $order = $this->registry->registry('current_order');
        }

        try {
            if (!$this->orderAuthorization->canView($order)) {
                return $this->getRedirect();
            }

            /** @var \Amasty\PDFCustom\Model\PdfLib\PdfInterface $pdf */
            if (isset($invoice)) {
                $filename = 'invoice' . $invoice->getIncrementId() . '.pdf';
                $pdf = $this->invoicePdf->getPdf([$invoice]);
            } else {
                $filename = 'invoices_of_order' . $order->getIncrementId() . '.pdf';
                $pdf = $this->invoicePdf->getPdf($order->getInvoiceCollection());
            }

            $content = $pdf->render();

            $response = $this->fileFactory->create(
                $filename,
                $content,
                \Magento\Framework\App\Filesystem\DirectoryList::TMP,
                'application/pdf',
                strlen($content)
            );
            // avoid double headers or double content
            return $response;
        } catch (\Exception $e) {
            return $this->getRedirect();
        }
    }

    protected function getRedirect()
    {
        return $this->_redirect('sales/order/history');
    }
}
