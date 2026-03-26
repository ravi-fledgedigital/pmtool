<?php
namespace OnitsukaTiger\NetSuiteStoreShipping\Controller\Adminhtml\PackingList;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Sales\Model\Order\ShipmentRepository;
use OnitsukaTiger\NetSuiteStoreShipping\Model\Pdf\PackingList;
use OnitsukaTiger\Shipment\Model\CoreEventHandle;

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
     * @var PackingList
     */
    protected $packingListPdf;

    /**
     * @var CoreEventHandle
     */
    protected $shipmentCoreEventHandle;

    /**
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param ForwardFactory $resultForwardFactory
     * @param ShipmentRepository $shipmentRepository
     * @param PackingList $packingListPdf
     * @param CoreEventHandle $shipmentCoreEventHandle
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        ForwardFactory $resultForwardFactory,
        ShipmentRepository $shipmentRepository,
        PackingList $packingListPdf,
        CoreEventHandle $shipmentCoreEventHandle
    ) {
        $this->_fileFactory = $fileFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->shipmentRepository = $shipmentRepository;
        $this->packingListPdf = $packingListPdf;
        $this->shipmentCoreEventHandle = $shipmentCoreEventHandle;
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|\Magento\Backend\Model\View\Result\Forward
     * @throws \Exception
     */
    public function execute()
    {
        $shipmentId = $this->getRequest()->getParam('shipment_id');
        if ($shipmentId) {
            $shipment = $this->shipmentRepository->get($shipmentId);
            if ($shipment) {

                if (!$shipment->getData('shipment_number')) {
                    $this->shipmentCoreEventHandle->saveShipmentNumber($shipment);
                }

                $pdfContent = $this->packingListPdf->getPdf([$shipment])->render();
                $date = $this->_objectManager->get(
                    \Magento\Framework\Stdlib\DateTime\DateTime::class
                )->date('Y-m-d_H-i-s');
                $fileContent = ['type' => 'string', 'value' => $pdfContent, 'rm' => true];

                return $this->_fileFactory->create(
                    'packing_list' . $date . '.pdf',
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
