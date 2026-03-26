<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\Shipment;

use Exception;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Sales\Model\Order\ShipmentRepository;
use Magento\Sales\Model\Order\Pdf\Shipment;
use OnitsukaTiger\Logger\StoreShipping\Logger;

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
     * @var ShipmentRepository
     */
    protected $shipmentRepository;

    /**
     * @var Shipment
     */
    protected $shipmentPdf;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param ForwardFactory $resultForwardFactory
     * @param ShipmentRepository $shipmentRepository
     * @param Shipment $shipmentPdf
     * @param Logger $logger
     */
    public function __construct(
        Context            $context,
        FileFactory        $fileFactory,
        ForwardFactory     $resultForwardFactory,
        ShipmentRepository $shipmentRepository,
        Shipment           $shipmentPdf,
        Logger             $logger
    )
    {
        $this->_fileFactory = $fileFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->shipmentPdf = $shipmentPdf;
        $this->logger = $logger;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Backend\Model\View\Result\Forward
     * @throws Exception
     */
    public function execute()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        try {
            if ($shipmentId) {
                $shipment = $this->shipmentRepository->get($shipmentId);
                if ($shipment) {
                    $pdfContent = $this->shipmentPdf->getPdf([$shipment])->render();
                    $date = $this->_objectManager->get(
                        \Magento\Framework\Stdlib\DateTime\DateTime::class
                    )->date('Y-m-d_H-i-s');
                    $fileContent = ['type' => 'string', 'value' => $pdfContent, 'rm' => true];
                    $fileName = 'awb' . $date . '.pdf';
                    return $this->_fileFactory->create(
                        $fileName,
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
        } catch (Exception $e) {
            $this->logger->error(sprintf('SPS: Error print shipment [%s]. Message: [%s]', $shipmentId, $e->getMessage()));
        }
    }
}
