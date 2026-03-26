<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use OnitsukaTiger\Logger\Api\Logger;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport;

/**
 * Class Import Receipt | This I/F update RMA status to "Return Accepted and Waiting Refund".
 * @package OnitsukaTigerKorea\SftpImportExport\Model\SftpImport
 */
class ImportReceipt extends SftpImport {

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var RequestRepositoryInterface
     */
    protected $rmaRequest;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StatusRepositoryInterface
     */
    protected $rmaStatusRepository;

    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    protected $actReturnDate;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * ImportReceipt constructor.
     * @param OrderRepositoryInterface $orderRepository
     * @param RequestRepositoryInterface $requestRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param StatusRepositoryInterface $rmaStatusRepository
     * @param ManagerInterface $eventManager
     * @param Logger $logger
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        RequestRepositoryInterface $requestRepository,
        ScopeConfigInterface $scopeConfig,
        StatusRepositoryInterface $rmaStatusRepository,
        ManagerInterface $eventManager,
        Logger $logger
    ){
        $this->orderRepository = $orderRepository;
        $this->rmaRequest = $requestRepository;
        $this->scopeConfig = $scopeConfig;
        $this->rmaStatusRepository = $rmaStatusRepository;
        $this->eventManager = $eventManager;
        $this->logger = $logger;
    }

    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        if($data){
            $result['Rma'] = [];
            foreach($data as $key => $rma) {
                $rmaId = $this->removePrefix($rma['rtn_order_no']);
                $orderId = $this->removePrefix($rma['origin_order_no']);
                $count = $rma['order_line_no'];
                $productSku = $rma['product_sku'];
                $productQty = $rma['product_qty'];
                if(!$this->validateProductQtyNegative($productQty)){
                    $result['Rma'][$rmaId] = [
                        'status' => 'fail',
                        'message' => sprintf('Rma has product with qty [%s] is negative', $productQty)
                    ];
                    $this->logger->debug(sprintf('Rma id [%s] has product with qty [%s] is negative', $rmaId, $productQty));
                    continue;
                }
                $this->setActReturnDate($rma['act_rtn_date']);
                $id = $this->scopeConfig->getValue('sftp_korea/sftp_api/rma_accept');
                try {
                    $rma = $this->rmaRequest->getById($rmaId);
                    $order = $this->orderRepository->get($orderId);
                    $result['Rma'][$rmaId] =  $this->updateRmaStatus($rma, $order, $id);
                }catch (\Exception $e) {
                    $result['Rma'][$rmaId] = [
                        'status' => 'fail',
                        'message' => 'Message:' . $e->getMessage()
                    ];
                    $this->logger->debug(sprintf('Rma id [%s] has something wrong: [%s]', $rmaId, $e->getMessage()));
                }
            }
            return $result;
        }
    }

    /**
     * @param RequestInterface $rma
     * @param OrderInterface $order
     * @param $id
     * @return array
     */
    public function updateRmaStatus(RequestInterface $rma, OrderInterface $order, $id): array
    {
        try {
            $status = $this->rmaStatusRepository->getById($id);
            $oldStatus = $rma->getStatus();
            $oldStatusLabel = $this->rmaStatusRepository->getById($oldStatus);
            if ($rma) {
                $rma->setStatus($status->getStatusId());
                $rma->setModifiedAt($this->getActReturnDate());

                $this->rmaRequest->save($rma);
                // add history
                $this->eventManager->dispatch(
                    \Amasty\Rma\Observer\RmaEventNames::STATUS_AUTOMATICALLY_CHANGED,
                    ['from' => $oldStatus, 'to' => $status->getStatusId(), 'request' => $rma]
                );

                $order->addCommentToStatusHistory('Event: Change Rma status from '. $oldStatusLabel->getTitle() . ' to ' . $status->getTitle())
                    ->save();

                $this->logger->debug(sprintf('Message: Rma id [%s] updated  status to [%s] successfully', $rma->getRequestId(), $status->getTitle()));

                return [
                    'status' => 'success',
                    'message' => sprintf('Message: Update Rma status to [%s] successfully', $status->getTitle())
                ];
            }
        } catch (\Exception $e) {
            $this->logger->debug(sprintf('Message: Update Rma id [%s] status fail', $rma->getRequestId()));

            return [
                'status' => 'fail',
                'message' => 'Update Rma status fail'
            ];
        }
    }

    /**
     * @param $date
     */
    public function setActReturnDate($date)
    {
        $this->actReturnDate = $date;
    }

    /**
     * @return mixed
     */
    public function getActReturnDate()
    {
        return $this->actReturnDate;
    }
}
