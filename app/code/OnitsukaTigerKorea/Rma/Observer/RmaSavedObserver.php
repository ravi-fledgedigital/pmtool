<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Rma\Observer;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Model\OptionSource\State;
use Amasty\Rma\Observer\RmaEventNames;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use OnitsukaTiger\Store\Helper\Data;
use OnitsukaTigerKorea\Sales\Model\OrderXmlId;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export\ReturnIF;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\ExportXml;

/**
 * Class RmaSavedObserver
 * @package OnitsukaTigerKorea\Rma\Observer
 */
class RmaSavedObserver implements ObserverInterface
{
    const NEED_TO_SYNC = 0;

    const SYNCED = 1;

    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var ReturnIF
     */
    protected $returnIF;

    /**
     * @var RequestRepositoryInterface
     */
    private $repository;

    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var ExportXml
     */
    protected $exportXml;

    /**
     * @var Data
     */
    protected $helperData;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var OrderXmlId
     */
    protected $orderXmlId;

    /**
     * @var \OnitsukaTigerKorea\Rma\Helper\Data
     */
    protected $koreaRmaHelper;

    /**
     * RmaSavedObserver constructor.
     *
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param StatusRepositoryInterface $statusRepository
     * @param RequestRepositoryInterface $repository
     * @param ManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     * @param ReturnIF $returnIF
     * @param Data $helperData
     * @param OrderXmlId $orderXmlId
     * @param ExportXml $exportXml
     * @param \OnitsukaTigerKorea\Rma\Helper\Data $koreaRmaHelper
     */
    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        StatusRepositoryInterface $statusRepository,
        RequestRepositoryInterface $repository,
        ManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory,
        ReturnIF $returnIF,
        Data $helperData,
        OrderXmlId $orderXmlId,
        ExportXml $exportXml,
        \OnitsukaTigerKorea\Rma\Helper\Data $koreaRmaHelper
    ) {
        $this->eventManager = $eventManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->statusRepository = $statusRepository;
        $this->repository = $repository;
        $this->returnIF = $returnIF;
        $this->helperData = $helperData;
        $this->orderXmlId = $orderXmlId;
        $this->exportXml = $exportXml;
        $this->koreaRmaHelper = $koreaRmaHelper;
    }

    /**
     * @param Observer $observer
     * @throws NoSuchEntityException|\Magento\Framework\Exception\CouldNotSaveException
     */
    public function execute(Observer $observer)
    {
        /** @var RequestInterface $request */
        $request = $observer->getData('request');
        $status = $this->statusRepository->getById($request->getStatus());

        if ($request->getStoreId() != 5) {
            return;
        }

        if ($this->orderXmlId->isNotExistReturnXmlId($request)) {
            $this->orderXmlId->updateOrderXmlId($request->getOrderId(), $request->getRequestId(), ExportXml::PREFIX_RETURN);
        }

        // if request approved then export xml to SFTP
        if (
            ($status->getState() === State::AUTHORIZED) &&
            ($request->getData('rma_synced') == self::NEED_TO_SYNC) &&
            ($request->getStoreId() == \OnitsukaTiger\Store\Model\Store::KO_KR)
        ) {
            $response = $this->returnIF->execute($request);

            $this->eventManager->dispatch(
                RmaEventNames::STATUS_CHANGED,
                ['request' => $request, 'original_status' => '1', 'new_status' => $request->getStatus()]
            );
            if ($response == 'success') {
                // update netsuite internal id of rma request
                $request->setData('rma_synced', self::SYNCED);
                $this->repository->save($request);
            } else {
//                $request->setStatus($request->getOrigData('status'));
                $this->repository->save($request);
            }
        }
    }
}
