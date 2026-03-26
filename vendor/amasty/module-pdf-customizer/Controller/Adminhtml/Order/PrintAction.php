<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Adminhtml\Order;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;

class PrintAction extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Sales::actions_view';

    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    private $fileFactory;

    /**
     * @var \Amasty\PDFCustom\Model\Order\Pdf\Order
     */
    private $orderPdf;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    private $templateRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Amasty\PDFCustom\Model\Order\Pdf\Order $orderPdf,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->fileFactory = $fileFactory;
        $this->orderPdf = $orderPdf;
        $this->dateTime = $dateTime;
        $this->orderRepository = $orderRepository;
        $this->templateRepository = $templateRepository;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws NoSuchEntityException
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        if ($orderId) {
            $order = $this->orderRepository->get($orderId);
            if ($order) {
                $templateId = $this->templateRepository->getOrderTemplateId(
                    $order->getStoreId(),
                    $order->getCustomerGroupId()
                );
                if (!$templateId) {
                    $storeName = $this->storeManager->getStore($order->getStoreId())->getName();
                    $this->messageManager->addErrorMessage(__('No Order templates assigned for this Order\'s '.
                        'storeview. Please assign one to %1 storeview at Marketing > Promotions > PDF Templates '.
                        '> PDF template of your choice, Stores & Customer Groups multiselect, then re-attempt Print '.
                        'Order.', $storeName));
                    /** @var Redirect $resultRedirect */
                    $resultRedirect = $this->resultRedirectFactory->create();

                    return $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
                }
                $pdf = $this->orderPdf->getPdf([$order]);
                $date = $this->dateTime->date('Y-m-d_H-i-s');

                return $this->fileFactory->create(
                    'order' . $date . '.pdf',
                    $pdf->render(),
                    DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            }
        }

        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        $resultForward->forward('noroute');
        
        return $resultForward;
    }
}
