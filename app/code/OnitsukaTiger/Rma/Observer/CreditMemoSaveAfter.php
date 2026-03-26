<?php

declare(strict_types=1);

namespace OnitsukaTiger\Rma\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\CreditmemoRepository;
use OnitsukaTiger\Logger\Logger;
use OnitsukaTiger\Rma\Helper\Data;
use OnitsukaTigerKorea\SftpImportExport\Helper\Data as KoreaSftpHelperData;
use Amasty\Rma\Model\Request\Repository as RmaRepository;

class CreditMemoSaveAfter implements ObserverInterface
{

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CreditmemoRepository
     */
    protected $creditmemoRepository;

    /**
     * @var KoreaSftpHelperData
     */
    protected $koreaSftpHelperData;

    /**
     * @var RmaRepository
     */
    protected $rmaRequestRepository;

    /**
     * @var Data
     */
    protected $rmaHelperData;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * CreditMemoSaveAfter constructor.
     * @param RequestInterface $request
     * @param CreditmemoRepository $creditmemoRepository
     * @param Data $rmaHelperData
     * @param Logger $logger
     * @param KoreaSftpHelperData $koreaSftpHelperData
     * @param RmaRepository $rmaRequestRepository
     */
    public function __construct(
        RequestInterface $request,
        CreditmemoRepository $creditmemoRepository,
        Data $rmaHelperData,
        Logger $logger,
        KoreaSftpHelperData $koreaSftpHelperData,
        RmaRepository $rmaRequestRepository
    )
    {
        $this->request = $request;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->rmaHelperData = $rmaHelperData;
        $this->logger = $logger;
        $this->koreaSftpHelperData = $koreaSftpHelperData;
        $this->rmaRequestRepository = $rmaRequestRepository;
    }

    /**
     * @param Observer $observer
     * @throws LocalizedException
     */
    public function execute(Observer $observer)
    {
        /* @var $creditmemo \Magento\Sales\Model\Order\Creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $rmaRequestId = $this->request->getParam('rma_request_id');
        if ($rmaRequestId && $this->rmaHelperData->getRmaToCreditMemoConfig($creditmemo->getStoreId())) {
            try {
                $model = $this->creditmemoRepository->get($creditmemo->getId());
                $model->setData('rma_request_id', $rmaRequestId);
                $this->creditmemoRepository->save($model);
                $rmaRequest = $this->rmaRequestRepository->getById($rmaRequestId);
                $rmaRequest->setStatus($this->koreaSftpHelperData->getAllowedRmaStatusExportData());
                $this->rmaRequestRepository->save($rmaRequest);
            } catch (\Exception $e) {
                $this->logger->error(sprintf("Can't save RMA request to credit memo %s: Error %s", $model->getIncrementId(), $e->getMessage()));
                throw new LocalizedException(
                    __("Can't save RMA request to credit memo", $e->getMessage())
                );
            }
        }
    }
}
