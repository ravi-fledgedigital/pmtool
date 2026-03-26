<?php
/**
 * phpcs:ignoreFile
 */
declare(strict_types=1);

namespace OnitsukaTiger\Restock\Plugin\Magento\ProductAlert\Controller\Add;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTiger\Restock\Model\ReservationOptions as ReservationFlag;
use OnitsukaTiger\Restock\Model\CollectionFactory;
use Magento\ProductAlert\Model\ResourceModel\Stock\CollectionFactory as StockCollectionFactory;

class Stock
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \OnitsukaTiger\Restock\Model\GridRestockFactory
     */
    protected $restockFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockCollectionFactory
     */
    private $stockCollectionFactory;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    private \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var CustomerSession
     */
    protected $customerSession;
    /**
     * @var ResultFactory
     */
    protected $resultFactory;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $_redirect;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \OnitsukaTiger\Restock\Model\GridRestockFactory $restockFactory
     * @param StockCollectionFactory $stockCollectionFactory
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param StoreManagerInterface|null $storeManager
     */

    public function __construct(
        Context                                          $context,
        CustomerSession                                  $customerSession,
        ProductRepositoryInterface                       $productRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \OnitsukaTiger\Restock\Model\GridRestockFactory  $restockFactory,
        StockCollectionFactory                           $stockCollectionFactory,
        \Magento\Framework\Data\Form\FormKey\Validator   $formKeyValidator,
        StoreManagerInterface                            $storeManager = null
    ) {
        $this->productRepository = $productRepository;
        $this->_objectManager = $context->getObjectManager();
        $this->customerSession = $customerSession;
        $this->restockFactory = $restockFactory;
        $this->storeManager = $storeManager ?: $this->_objectManager
            ->get(\Magento\Store\Model\StoreManagerInterface::class);
        $this->resultFactory = $context->getResultFactory();
        $this->messageManager = $context->getMessageManager();
        $this->_redirect = $context->getRedirect();
        $this->resultJsonFactory = $resultJsonFactory;
        $this->stockCollectionFactory = $stockCollectionFactory;
        $this->formKeyValidator = $formKeyValidator;
    }

    public function aroundExecute(
        \Magento\ProductAlert\Controller\Add\Stock $subject,
        \Closure                                   $proceed
    ) {
        $backUrl = $subject->getRequest()->getParam(Action::PARAM_NAME_URL_ENCODED);
        $productId = (int)$subject->getRequest()->getParam('product_id');

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultJson = $this->resultJsonFactory->create();
        if (!$backUrl || !$productId) {
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }
        try {
            $params = $subject->getRequest()->getParams();
            if (!$this->formKeyValidator->validate($subject->getRequest())) {
                $resultJson->setData([
                    "message" => __("Invalid form key. Please refresh the page and try again."),
                    "success" => false
                ]);
                return $resultJson;
            }

            /* @var $product \Magento\Catalog\Model\Product */
            $product = $this->productRepository->getById($productId);
            $store = $this->storeManager->getStore();
            $customerId = $this->customerSession->getCustomerId();

            // Pre-Order Flag
            $reservationFlag = ($product->getRestockNotificationFlag() == 2) ? 1 : 0;

            $collection = $this->stockCollectionFactory->create();
            $collection->addFieldToFilter('product_id', $product->getId())
                ->addWebsiteFilter($store->getWebsiteId())
                ->addFieldToFilter('customer_id', $customerId)
                ->addStatusFilter(0);

            if (!$collection->getSize()) {
                $restockModel = $this->restockFactory->create()
                    ->setProductId($product->getId())
                    ->setCustomerId($customerId)
                    ->setProductImage($product->getImage())
                    ->setProductName($product->getName())
                    ->setProductType($product->getTypeId())
                    ->setProductSku($product->getSku())
                    ->setProductPrice($product->getPrice())
                    ->setProductQty($product->getQuantityAndStockStatus() ? $product->getQuantityAndStockStatus()['qty'] : 0)
                    ->setWebsiteId($store->getWebsiteId())
                    ->setStoreId($store->getId())
                    ->setSendCount(0)
                    ->setStatus(0)
                    ->setAddDate(date('Y-m-d H:i:s'))
                    ->setAlertType($reservationFlag)
                    ->setLastArrivalContactDate('')
                    ->setTotalNumberRestock(0)
                    ->setRestockNotSent(1)
                    ->setRestockSent(0);
                $restockModel->save();
            } else {
                $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
                $resultJson->setData(["message" => __("This product is already subscribed."), "suceess" => true]);
                return $resultJson;
            }

            /** @var \Magento\ProductAlert\Model\Stock $model */
            $model = $this->_objectManager->create(\Magento\ProductAlert\Model\Stock::class)
                ->setCustomerId($customerId)
                ->setProductId($product->getId())
                ->setWebsiteId($store->getWebsiteId())
                ->setStoreId($store->getId())
                ->setAlertType($reservationFlag);
            $model->save();
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData(["message" => __("You will be notified when the item is available."), "suceess" => true]);
            return $resultJson;
        } catch (NoSuchEntityException $noEntityException) {
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData(["message" => ("There are not enough parameters."), "suceess" => false]);
            return $resultJson;

            $this->messageManager->addErrorMessage(__('There are not enough parameters.'));
            $resultRedirect->setUrl($backUrl);
            return $resultRedirect;
        } catch (\Exception $e) {
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData(["message" => ("The alert subscription couldn't update at this time. Please try again later."), "suceess" => false]);
            return $resultJson;

            $this->messageManager->addExceptionMessage(
                $e,
                __("The alert subscription couldn't update at this time. Please try again later.")
            );
        }

        $result = $proceed();
        return $result;
    }

    /**
     * Pre Order Status
     *
     * @param $child
     * @param $updateStatus
     * @return bool
     */
    public function preOrderStatus($child, $updateStatus = false)
    {
        $reservationFlag = ($child->getAttributeText('reservation_flag') == ReservationFlag::RESERVATION_PRE_ORDER) ? true : false;
        if ($reservationFlag && $child->getReservationFrom() != '') {
            date_default_timezone_set("Asia/Tokyo");
            $today = date('Y-m-d H:i:s');
            $from = date('Y-m-d H:i:s', strtotime($child->getReservationFrom()));
            $to = date('Y-m-d H:i:s', strtotime($child->getReservationTo()));
            if ($today >= $from && $today <= $to) {
                return true;
            }
        }
        return false;
    }
}
