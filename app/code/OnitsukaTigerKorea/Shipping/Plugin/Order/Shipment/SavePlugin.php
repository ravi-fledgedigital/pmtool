<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\Shipping\Plugin\Order\Shipment;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use OnitsukaTigerKorea\Shipping\Helper\Data;
use OnitsukaTigerKorea\Shipping\Model\PartialCancel\IsPartialCancel;
use OnitsukaTigerKorea\Shipping\Model\PartialCancel\PartialCancelProcess;

class SavePlugin
{
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;

    /**
     * @var IsPartialCancel
     */
    private $isPartialCancel;

    /**
     * @var PartialCancelProcess
     */
    private $partialCancelProcess;

    /**
     * @var Data
     */
    protected $partialCancel;

    /**
     * @var FormKeyValidator
     */
    protected $_formKeyValidator;

    /**
     * @param RedirectFactory $resultRedirectFactory
     * @param FormKeyValidator $_formKeyValidator
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param MessageManagerInterface $messageManager
     * @param IsPartialCancel $isPartialCancel
     * @param PartialCancelProcess $partialCancelProcess
     * @param Data $shippingHelper
     */
    public function __construct(
        RedirectFactory              $resultRedirectFactory,
        FormKeyValidator             $_formKeyValidator,
        OrderRepositoryInterface     $orderRepository,
        OrderItemRepositoryInterface $orderItemRepository,
        MessageManagerInterface      $messageManager,
        IsPartialCancel              $isPartialCancel,
        PartialCancelProcess         $partialCancelProcess,
        Data                         $shippingHelper
    )
    {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->_formKeyValidator = $_formKeyValidator;
        $this->orderRepository = $orderRepository;
        $this->orderItemRepository = $orderItemRepository;
        $this->messageManager = $messageManager;
        $this->isPartialCancel = $isPartialCancel;
        $this->partialCancelProcess = $partialCancelProcess;
        $this->partialCancel = $shippingHelper;
    }

    public function aroundExecute(\Magento\Shipping\Controller\Adminhtml\Order\Shipment\Save $subject, callable $proceed)
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $formKeyIsValid = $this->_formKeyValidator->validate($subject->getRequest());
        $isPost = $subject->getRequest()->isPost();
        if (!$formKeyIsValid || !$isPost) {
            $this->messageManager->addErrorMessage(__('We can\'t save the shipment right now.'));
            return $resultRedirect->setPath('sales/order/index');
        }

        $data = $subject->getRequest()->getParam('shipment');
        $orderId = $subject->getRequest()->getParam('order_id');
        $order = $this->orderRepository->get($orderId);

        if(!$this->partialCancel->isEnabled($order->getStoreId())) {
            return $proceed();
        }

        // check cancel, Check has item cancel then process cancel
        $flag = $this->isPartialCancel->execute($data);
        if ($flag['status']) {
            return $this->partialCancelProcess->execute($subject);

        }  else {
            return $proceed();
        }
    }
}
