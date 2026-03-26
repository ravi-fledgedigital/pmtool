<?php
declare(strict_types=1);

namespace OnitsukaTiger\ProductAlert\Rewrite\Magento\ProductAlert\Model\Mailing;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Data;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime;
use Magento\ProductAlert\Model\Email;
use Magento\ProductAlert\Model\EmailFactory;
use Magento\ProductAlert\Model\Mailing\ErrorEmailSender;
use Magento\ProductAlert\Model\Price;
use Magento\ProductAlert\Model\ProductSalability;
use Magento\ProductAlert\Model\ResourceModel\Price\CollectionFactory as PriceCollectionFactory;
use Magento\ProductAlert\Model\ResourceModel\Stock\CollectionFactory as StockCollectionFactory;
use Magento\ProductAlert\Model\Stock;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\Website;

class AlertProcessor extends \Magento\ProductAlert\Model\Mailing\AlertProcessor
{
    public const ALERT_TYPE_STOCK = 'stock';
    public const ALERT_TYPE_PRICE = 'price';
    public const ALERT_TYPE_STOCK_PREORDER = 'stock_preorder';

    /**
     * @var EmailFactory
     */
    private $emailFactory;

    /**
     * @var PriceCollectionFactory
     */
    private $priceCollectionFactory;

    /**
     * @var StockCollectionFactory
     */
    private $stockCollectionFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var Data
     */
    private $catalogData;

    /**
     * @var ProductSalability
     */
    private $productSalability;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ErrorEmailSender
     */
    private $errorEmailSender;

    /**
     * @var \OnitsukaTiger\Restock\Model\GridRestockFactory
     */
    protected $restockGridFactory;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @param EmailFactory $emailFactory
     * @param PriceCollectionFactory $priceCollectionFactory
     * @param StockCollectionFactory $stockCollectionFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Data $catalogData
     * @param ProductSalability $productSalability
     * @param StoreManagerInterface $storeManager
     * @param ErrorEmailSender $errorEmailSender
     * @param \OnitsukaTiger\Restock\Model\GridRestockFactory $restockGridFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     */
    public function __construct(
        EmailFactory $emailFactory,
        PriceCollectionFactory $priceCollectionFactory,
        StockCollectionFactory $stockCollectionFactory,
        CustomerRepositoryInterface $customerRepository,
        ProductRepositoryInterface $productRepository,
        Data $catalogData,
        ProductSalability $productSalability,
        StoreManagerInterface $storeManager,
        ErrorEmailSender $errorEmailSender,
        \OnitsukaTiger\Restock\Model\GridRestockFactory $restockGridFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory
    ) {
        $this->emailFactory = $emailFactory;
        $this->priceCollectionFactory = $priceCollectionFactory;
        $this->stockCollectionFactory = $stockCollectionFactory;
        $this->customerRepository = $customerRepository;
        $this->productRepository = $productRepository;
        $this->catalogData = $catalogData;
        $this->productSalability = $productSalability;
        $this->storeManager = $storeManager;
        $this->errorEmailSender = $errorEmailSender;
        $this->restockGridFactory = $restockGridFactory;
        $this->productFactory = $productFactory;
        parent::__construct(
            $emailFactory,
            $priceCollectionFactory,
            $stockCollectionFactory,
            $customerRepository,
            $productRepository,
            $catalogData,
            $productSalability,
            $storeManager,
            $errorEmailSender
        );
    }

    /**
     * Process product alerts
     *
     * @param string $alertType
     * @param array $customerIds
     * @param int $websiteId
     * @throws \Exception
     */
    public function process(string $alertType, array $customerIds, int $websiteId): void
    {
        $this->validateAlertType($alertType);
        $errors = $this->processAlerts($alertType, $customerIds, $websiteId);

        if (!empty($errors)) {
            /** @var Website $website */
            $website = $this->storeManager->getWebsite($websiteId);
            $defaultStoreId = (int)$website->getDefaultStore()->getId();
            $this->errorEmailSender->execute($errors, $defaultStoreId);
        }
    }

    /**
     * Process product alerts
     *
     * @param string $alertType
     * @param array $customerIds
     * @param int $websiteId
     * @return array
     * @throws \Exception
     */
    private function processAlerts(string $alertType, array $customerIds, int $websiteId): array
    {
        /** @var Email $email */
        $email = $this->emailFactory->create();
        $email->setType($alertType);
        $email->setWebsiteId($websiteId);
        $errors = [];

        try {
            $collection = $this->getAlertCollection($alertType, $customerIds, $websiteId);
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            return $errors;
        }

        /** @var CustomerInterface $customer */
        $customer = null;
        /** @var Website $website */
        $website = $this->storeManager->getWebsite($websiteId);
        $defaultStoreId = $website->getDefaultStore()->getId();

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/restock_alert_products.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        /** @var Price|Stock $alert */
        foreach ($collection as $alert) {
            try {
                $product = $this->productRepository->getById($alert->getProductId(), false, $defaultStoreId);
                if ($product->getStatus() == 2) {
                    continue;
                }
                if ($alert->getStoreId()) {
                    $email->setStoreId($alert->getStoreId());
                }
                if ($customer === null) {
                    $customer = $this->customerRepository->getById($alert->getCustomerId());
                } elseif ((int)$customer->getId() !== (int)$alert->getCustomerId()) {
                    $this->sendEmail($customer, $email);
                    $customer = $this->customerRepository->getById($alert->getCustomerId());
                }

                switch ($alertType) {
                    case self::ALERT_TYPE_STOCK:
                        $this->saveStockAlert($alert, $product, $website, $email);
                        break;
                    case self::ALERT_TYPE_PRICE:
                        $this->savePriceAlert($alert, $product, $customer, $email);
                        break;
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if ($customer !== null) {
            try {
                $this->sendEmail($customer, $email);
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return $errors;
    }

    /**
     * Validate Alert Type
     *
     * @param string $alertType
     * @throws \InvalidArgumentException
     */
    private function validateAlertType(string $alertType): void
    {
        if (!in_array($alertType, [self::ALERT_TYPE_STOCK, self::ALERT_TYPE_PRICE])) {
            throw new \InvalidArgumentException('Invalid alert type');
        }
    }

    /**
     * Get alert collection
     *
     * @param string $alertType
     * @param array $customerIds
     * @param int $websiteId
     * @return AbstractCollection
     * @throws \InvalidArgumentException
     */
    private function getAlertCollection(string $alertType, array $customerIds, int $websiteId): AbstractCollection
    {
        switch ($alertType) {
            case self::ALERT_TYPE_STOCK:
                $collection = $this->stockCollectionFactory->create();
                $collection->addFieldToFilter('customer_id', ['in' => $customerIds])
                    ->addWebsiteFilter($websiteId)
                    ->addStatusFilter(0)
                    ->setCustomerOrder()
                    ->addOrder('product_id');
                break;
            case self::ALERT_TYPE_PRICE:
                $collection = $this->priceCollectionFactory->create();
                $collection->addFieldToFilter('customer_id', ['in' => $customerIds])
                    ->addWebsiteFilter($websiteId)
                    ->setCustomerOrder()
                    ->addOrder('product_id');
                break;
            default:
                throw new \InvalidArgumentException('Invalid alert type');
        }

        return $collection;
    }

    /**
     * Save Price Alert
     *
     * @param Price $alert
     * @param ProductInterface $product
     * @param CustomerInterface $customer
     * @param Email $email
     */
    private function savePriceAlert(
        Price $alert,
        ProductInterface $product,
        CustomerInterface $customer,
        Email $email
    ): void {
        $product->setCustomerGroupId($customer->getGroupId());
        $finalPrice = $product->getFinalPrice();
        if ($alert->getPrice() <= $finalPrice) {
            return;
        }

        $product->setFinalPrice($this->catalogData->getTaxPrice($product, $finalPrice));
        $product->setPrice($this->catalogData->getTaxPrice($product, $product->getPrice()));

        $alert->setPrice($finalPrice);
        $alert->setLastSendDate(date(DateTime::DATETIME_PHP_FORMAT));
        $alert->setSendCount($alert->getSendCount() + 1);
        $alert->setStatus(1);
        $alert->save();

        $email->addPriceProduct($product);
    }

    /**
     * Save stock alert
     *
     * @param Stock $alert
     * @param ProductInterface $product
     * @param WebsiteInterface $website
     * @param Email $email
     */
    private function saveStockAlert(
        Stock $alert,
        ProductInterface $product,
        WebsiteInterface $website,
        Email $email
    ): void {
        if (!$this->productSalability->isSalable($product, $website)) {
            return;
        }

        $alert->setSendDate(date(DateTime::DATETIME_PHP_FORMAT));
        $alert->setSendCount($alert->getSendCount() + 1);
        $alert->setStatus(1);
        $alert->save();

        $customer = $this->customerRepository->getById($alert->getCustomerId());

        $logWriter = new \Zend_Log_Writer_Stream(BP . "/var/log/restock_alert_products.log");
        $stockLogger = new \Zend_Log();
        $stockLogger->addWriter($logWriter);

        $stockLogger->info("Store Id : " . $alert->getStoreId());
        $stockLogger->info("Restock Email send to product SKU : " . $product->getSku());
        $stockLogger->info("Customer Email : " . $customer->getEmail());

        // adding logger for restocked email send data
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/stock_grid.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $restockCollection =  $this->restockGridFactory->create()->getCollection()
            ->addFieldToFilter('product_id', $product->getId())
            ->addFieldToFilter('customer_id', $alert->getCustomerId());

        try {
            if ($restockCollection->getSize()) {
                /*update the restock grid data*/
                foreach ($restockCollection as $restock) {
                    $restock->setSendCount($alert->getSendCount());
                    $restock->setRestockSent($alert->getStatus());
                    $restock->setStatus($alert->getStatus());
                    if ($alert->getStatus()) {
                        $restock->setRestockNotSent(0);
                    } else {
                        $restock->setRestockNotSent(1);
                    }
                    $restock->setLastArrivalContactDate($alert->getSendDate());
                    $restock->setTotalNumberRestock($alert->getSendCount());
                    $restock->setSendDate($alert->getSendDate());
                    $restock->save();
                }
            } else {
                /*insert the restock grid data*/
                $restockGridObj = $this->restockGridFactory->create();
                $restockGridObj->setProductId($product->getId());
                $restockGridObj->setCustomerId($alert->getCustomerId());
                $restockGridObj->setProductImage($product->getImage());
                $restockGridObj->setProductName($product->getName());
                $restockGridObj->setProductType($product->getTypeId());
                $restockGridObj->setProductSku($product->getSku());
                $restockGridObj->setProductPrice($product->getPrice());
                $restockGridObj->setProductQty(
                    $product->getQuantityAndStockStatus() ? $product->getQuantityAndStockStatus()['qty'] : 0
                );
                $restockGridObj->setWebsiteId($alert->getWebsiteId());
                $restockGridObj->setStoreId($alert->getId());
                $restockGridObj->setStatus($alert->getStatus());
                $restockGridObj->setAddDate(date('Y-m-d H:i:s'));
                $restockGridObj->setAlertType($alert->getAlertType());
                $restockGridObj->setTotalNumberRestock($alert->getSendCount());
                $restockGridObj->setStatus($alert->getStatus());
                if ($alert->getStatus()) {
                    $restockGridObj->setRestockNotSent(0);
                } else {
                    $restockGridObj->setRestockNotSent(1);
                }
                $restockGridObj->setSendCount($alert->getSendCount());
                $restockGridObj->setRestockSent($alert->getStatus());
                $restockGridObj->setLastArrivalContactDate($alert->getSendDate());
                $restockGridObj->setSendDate($alert->getSendDate());
                $restockGridObj->save();
            }
        } catch (\Exception $e) {
            $logger->info("save grid data Exception");
            $logger->info(var_export($e->getMessage(), true));
        }

        $email->addStockProduct($product);

        $productObj = $this->productFactory->create()->load($product->getId());
        $productObj->setRestockNotificationFlag('')->save();
    }

    /**
     * Save stock preorder alert
     *
     * @param Stock $alert
     * @param ProductInterface $product
     * @param WebsiteInterface $website
     * @param Email $email
     */
    private function saveStockPreorderAlert(
        Stock $alert,
        ProductInterface $product,
        WebsiteInterface $website,
        Email $email
    ): void {
        if (!$this->productSalability->isSalable($product, $website) && $product->getRestockNotificationFlag() == 2) {
            return;
        }

        $alert->setSendDate(date(DateTime::DATETIME_PHP_FORMAT));
        $alert->setSendCount($alert->getSendCount() + 1);
        $alert->setStatus(1);
        $alert->save();

        $email->addStockProduct($product);
    }

    /**
     * Send alert email
     *
     * @param CustomerInterface $customer
     * @param Email $email
     */
    private function sendEmail(CustomerInterface $customer, Email $email): void
    {
        $email->setCustomerData($customer);
        $email->send();
        $email->clean();
    }
}
