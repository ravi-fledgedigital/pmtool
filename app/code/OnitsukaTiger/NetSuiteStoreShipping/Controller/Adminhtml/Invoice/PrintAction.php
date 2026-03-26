<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Invoice;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\Order\Pdf\Invoice;

class PrintAction extends \Magento\Backend\App\Action
{
    /**
     * @var FileFactory
     */
    protected $_fileFactory;

    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepository;

    /**
     * @var Invoice
     */
    protected $invoicePdf;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param ForwardFactory $resultForwardFactory
     * @param InvoiceRepository $invoiceRepository
     * @param Invoice $invoicePdf
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        ForwardFactory $resultForwardFactory,
        InvoiceRepository $invoiceRepository,
        Invoice $invoicePdf
    ) {
        $this->_fileFactory = $fileFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->invoicePdf = $invoicePdf;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Backend\Model\View\Result\Forward
     * @throws \Exception
     */
    public function execute()
    {
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        if ($invoiceId) {
            $invoice = $this->invoiceRepository->get($invoiceId);
            if ($invoice) {
                $pdfContent = $this->invoicePdf->getPdf([$invoice])->render();
                $date = $this->_objectManager->get(
                    \Magento\Framework\Stdlib\DateTime\DateTime::class
                )->date('Y-m-d_H-i-s');
                $fileContent = ['type' => 'string', 'value' => $pdfContent, 'rm' => true];

                return $this->_fileFactory->create(
                    'invoice' . $date . '.pdf',
                    $fileContent,
                    DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            }
        } else {
            /** @var \Magento\Backend\Model\View\Result\Forward $resultForward */
            $resultForward = $this->resultForwardFactory->create();
            return $resultForward->forward('noroute');
        }
    }
}
