<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Shipping\Model\PartialCancel;

use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Helper\Data as SalesData;
use Magento\Sales\Model\Order\Email\Sender\ShipmentSender;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\Order\Shipment\ShipmentValidatorInterface;
use Magento\Sales\Model\Order\Shipment\Validation\QuantityValidator;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\Shipping\LabelGenerator;
use OnitsukaTiger\OrderStatus\Model\OrderStatus;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpExport\Export\PartialCancel as OnitsukaTigerKoreaPartialCancel;
use OnitsukaTigerKorea\Shipping\Helper\Data;
use OnitsukaTigerKorea\Shipping\Model\PartialCancel\Process\CalculateQtyItemShip;
use OnitsukaTigerKorea\Shipping\Model\PartialCancel\Process\CancelItemProcess;
use OnitsukaTigerKorea\Shipping\Model\PartialCancel\Process\CreditMemoItemCanceled;

class PartialCancelProcess
{

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var ShipmentLoader
     */
    protected $shipmentLoader;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    /**
     * @var ShipmentValidatorInterface
     */
    private $shipmentValidator;

    /**
     * @var SalesData
     */
    private $salesData;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var LabelGenerator
     */
    protected $labelGenerator;

    /**
     * @var ShipmentSender
     */
    protected $shipmentSender;

    /**
     * @var CalculateQtyItemShip
     */
    protected $calculateQtyItemShip;

    /**
     * @var CancelItemProcess
     */
    protected $cancelItemProcess;

    /**
     * @var CreditMemoItemCanceled
     */
    protected $createCreditMemoItemCanceled;

    /**
     * @var OnitsukaTigerKoreaPartialCancel
     */
    protected $sftpCancel;

    /**
     * @var OrderStatus
     */
    protected $orderStatusModel;

    protected $partialHelper;

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param LabelGenerator $labelGenerator
     * @param ShipmentSender $shipmentSender
     * @param ObjectManagerInterface $_objectManager
     * @param ShipmentLoader $shipmentLoader
     * @param OrderRepositoryInterface $orderRepository
     * @param ResultFactory $resultFactory
     * @param CalculateQtyItemShip $calculateQtyItemShip
     * @param CancelItemProcess $cancelItemProcess
     * @param MessageManagerInterface $messageManager
     * @param CreditMemoItemCanceled $createCreditMemoItemCanceled
     * @param OnitsukaTigerKoreaPartialCancel $sftpCancel
     * @param Data $partialHelper
     * @param OrderStatus $orderStatusModel
     * @param ShipmentValidatorInterface|null $shipmentValidator
     * @param SalesData|null $salesData
     */
    public function __construct(
        RedirectFactory            $resultRedirectFactory,
        LabelGenerator             $labelGenerator,
        ShipmentSender             $shipmentSender,
        ObjectManagerInterface     $_objectManager,
        ShipmentLoader             $shipmentLoader,
        OrderRepositoryInterface   $orderRepository,
        ResultFactory              $resultFactory,
        CalculateQtyItemShip       $calculateQtyItemShip,
        CancelItemProcess          $cancelItemProcess,
        MessageManagerInterface    $messageManager,
        CreditMemoItemCanceled $createCreditMemoItemCanceled,
        OnitsukaTigerKoreaPartialCancel $sftpCancel,
        \OnitsukaTigerKorea\Shipping\Helper\Data $partialHelper,
        OrderStatus $orderStatusModel,
        ShipmentValidatorInterface $shipmentValidator = null,
        SalesData                  $salesData = null
    )
    {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->labelGenerator = $labelGenerator;
        $this->shipmentSender = $shipmentSender;
        $this->_objectManager = $_objectManager;
        $this->shipmentLoader = $shipmentLoader;
        $this->orderRepository = $orderRepository;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
        $this->calculateQtyItemShip = $calculateQtyItemShip;
        $this->cancelItemProcess = $cancelItemProcess;
        $this->createCreditMemoItemCanceled = $createCreditMemoItemCanceled;
        $this->sftpCancel = $sftpCancel;
        $this->partialHelper = $partialHelper;
        $this->orderStatusModel = $orderStatusModel;
        $this->shipmentValidator = $shipmentValidator ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(ShipmentValidatorInterface::class);
        $this->salesData = $salesData ?: \Magento\Framework\App\ObjectManager::getInstance()
            ->get(SalesData::class);
    }


    public function execute($subject)
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $data = $subject->getRequest()->getParam('shipment');
        $orderId = $subject->getRequest()->getParam('order_id');
        //$data = $this->calculateQtyItemShip->execute($data);
        $order = $this->orderRepository->get($orderId);

        if (!empty($data['comment_text'])) {
            $this->_objectManager->get(\Magento\Backend\Model\Session::class)->setCommentText($data['comment_text']);
        }

        $isNeedCreateLabel = isset($data['create_shipping_label']) && $data['create_shipping_label'];
        $responseAjax = new \Magento\Framework\DataObject();
        try {
            $this->shipmentLoader->setOrderId($orderId);
            $this->shipmentLoader->setShipmentId($subject->getRequest()->getParam('shipment_id'));
            $this->shipmentLoader->setShipment($data);
            $this->shipmentLoader->setTracking($subject->getRequest()->getParam('tracking'));
            $shipment = $this->shipmentLoader->load();

            if (!$shipment) {
                return $this->resultFactory->create(ResultFactory::TYPE_FORWARD)->forward('noroute');
            }

            if (!empty($data['comment_text'])) {
                $shipment->addComment(
                    $data['comment_text'],
                    isset($data['comment_customer_notify']),
                    isset($data['is_visible_on_front'])
                );

                $shipment->setCustomerNote($data['comment_text']);
                $shipment->setCustomerNoteNotify(isset($data['comment_customer_notify']));
            }
            $validationResult = $this->shipmentValidator->validate($shipment, [QuantityValidator::class]);

            if ($validationResult->hasMessages()) {
                $this->messageManager->addErrorMessage(
                    __("Shipment Document Validation Error(s):\n" . implode("\n", $validationResult->getMessages()))
                );
                return $resultRedirect->setPath('*/*/new', ['order_id' => $orderId]);
            }
            $shipment->register();

            $shipment->getOrder()->setCustomerNoteNotify(!empty($data['send_email']));

            if ($isNeedCreateLabel) {
                $this->labelGenerator->create($shipment, $subject->_request);
                $responseAjax->setOk(true);
            }

            $this->_saveShipment($shipment);

            // cancel item
            $order = $this->cancelItemProcess->execute($order, $data);
            // export partial cancel xml
            $this->sftpCancel->execute($order, $shipment, $data);
            // create credit memo with item cancel
            if($this->partialHelper->enableAutoCreateCreditMemo()){
                $this->createCreditMemoItemCanceled->execute($order, $data);
            }
            // Update order status
            $this->orderStatusModel->setOrderStatus($order);
            $this->orderRepository->save($order);

            if (!empty($data['send_email']) && $this->salesData->canSendNewShipmentEmail()) {
                $this->shipmentSender->send($shipment);
            }

            $shipmentCreatedMessage = __('The shipment has been created.');
            $labelCreatedMessage = __('You created the shipping label.');

            $this->messageManager->addSuccessMessage(
                $isNeedCreateLabel ? $shipmentCreatedMessage . ' ' . $labelCreatedMessage : $shipmentCreatedMessage
            );
            $this->_objectManager->get(\Magento\Backend\Model\Session::class)->getCommentText(true);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setPath('*/*/new', ['order_id' => $orderId]);
        } catch (\Exception $e) {
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            $this->messageManager->addErrorMessage(__('Cannot save shipment.'));
            return $resultRedirect->setPath('*/*/new', ['order_id' => $orderId]);
        }
        if ($isNeedCreateLabel) {
            return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setJsonData($responseAjax->toJson());
        }

        return $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
    }

    protected function _saveShipment($shipment)
    {
        $shipment->getOrder()->setIsInProcess(true);
        $transaction = $this->_objectManager->create(
            \Magento\Framework\DB\Transaction::class
        );
        $transaction->addObject(
            $shipment
        )->addObject(
            $shipment->getOrder()
        )->save();

        return $this;
    }
}
