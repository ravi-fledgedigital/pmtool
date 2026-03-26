<?php

namespace OnitsukaTiger\Restock\Controller\Add;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ProductAlert\Model\ResourceModel\Stock\CollectionFactory as StockCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;

class Stock extends \Magento\Framework\App\Action\Action
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

    public function __construct(
        Context                                          $context,
        CustomerSession                                  $customerSession,
        ProductRepositoryInterface                       $productRepository,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \OnitsukaTiger\Restock\Model\GridRestockFactory  $restockFactory,
        StockCollectionFactory                           $stockCollectionFactory,
        \Magento\Framework\Data\Form\FormKey\Validator   $formKeyValidator,
        StoreManagerInterface                            $storeManager = null
    )
    {
        parent::__construct($context);
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

    /**
     * @return Json|(Json&ResultInterface)|Redirect|ResultInterface
     */
    public function execute()
    {
        $customerId = $this->customerSession->getCustomerId();

        $backUrl = $this->getRequest()->getParam(Action::PARAM_NAME_URL_ENCODED);
        $productId = (int)$this->getRequest()->getParam('product_id');

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultJson = $this->resultJsonFactory->create();
        if (!$backUrl || !$productId) {
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }
        try {
            $params = $this->getRequest()->getParams();
            if (!$this->formKeyValidator->validate($this->getRequest())) {
                $this->messageManager->addErrorMessage("Invalid form key. Please refresh the page and try again.");
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setPath('/');
                return $resultRedirect;
            }

            /* @var $product \Magento\Catalog\Model\Product */
            $product = $this->productRepository->getById($productId);
            $store = $this->storeManager->getStore();

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
                $this->messageManager->addErrorMessage(__('This product is already subscribed.'));
                /** @var Redirect $resultRedirect */
                $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
                $resultRedirect->setPath('/');
                return $resultRedirect;
            }

            /** @var \Magento\ProductAlert\Model\Stock $model */
            $model = $this->_objectManager->create(\Magento\ProductAlert\Model\Stock::class)
                ->setCustomerId($customerId)
                ->setProductId($product->getId())
                ->setWebsiteId($store->getWebsiteId())
                ->setStoreId($store->getId())
                ->setAlertType($reservationFlag);
            $model->save();
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('/');
            return $resultRedirect;
        } catch (NoSuchEntityException $noEntityException) {
            $this->messageManager->addErrorMessage(__('There are not enough parameters.'));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('/');
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("The alert subscription couldn't update at this time. Please try again later."));
            /** @var Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }
    }
}