<?php
/**
 * Copyright © Adobe. All rights reserved.
 */
namespace OnitsukaTiger\PreOrders\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as AttributeResource;

class ValidateAddToCart implements ObserverInterface
{
    const TYPE_EMPTY = 1;
    const TYPE_REGULAR = 2;
    const TYPE_PRE_ORDER = 3;
    const TYPE_MIXED = 4;
    const CURRENT_CART_TYPE = 'onitsukatiger_pre_order_current_cart_type';

    /**
     * @var SettingsHelper
     */
    private $settingsHelper;

    /**
     * @var ManagerInterface
     */
    private $messageManager;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var AttributeResource
     */
    private $attributeResource;

    /**
     * @var OnitsukaTiger\PreOrders\Helper\PreOrder
     */
    protected $perOrderHelper;

    /**
     * ValidateAddToCart constructor.
     *
     * @param SettingsHelper $settingsHelper
     * @param ManagerInterface $messageManager
     * @param Registry $registry
     * @param StoreManagerInterface $storeManager
     * @param ProductRepositoryInterface $productRepository
     * @param RequestInterface $request
     * @param AttributeResource $attributeResource
     * @param OnitsukaTiger\PreOrders\Helper\PreOrder $perOrderHelper
     */
    public function __construct(
        // SettingsHelper $settingsHelper,
        ManagerInterface $messageManager,
        Registry $registry,
        StoreManagerInterface $storeManager,
        ProductRepositoryInterface $productRepository,
        RequestInterface $request,
        AttributeResource $attributeResource,
        \OnitsukaTiger\PreOrders\Helper\PreOrder $perOrderHelper
    ) {
        // $this->settingsHelper = $settingsHelper;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->request = $request;
        $this->attributeResource = $attributeResource;
        $this->perOrderHelper = $perOrderHelper;
    }

    /**
     * Validate product after adding to cart
     *
     * @param Observer $observer
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        $productId = $this->request->getParam('product');

        $product = $this->getProduct($productId);

        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/pre_order.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        if (!$product) {
            return;
        }

        $this->checkMixedGroupedProduct($product);

        $cartType = $this->getCartType();
        $logger->info('cartType - '.$cartType);
        if ($cartType !== false) {
            switch ($cartType) {
                case self::TYPE_REGULAR:
                    $logger->info('processRegularAdding - product id - '.$product->getId());
                    $this->processRegularAdding($product);
                    break;
                case self::TYPE_PRE_ORDER:
                    $logger->info('processPreOrderAdding - product id - '.$product->getId());
                    $this->processPreOrderAdding($product);
                    break;
                case self::TYPE_MIXED:
                    $logger->info('processMixedAdding - product id - '.$product->getId());
                    $this->processMixedAdding();
                    break;
            }
        }
    }

    /**
     * Process pre order adding
     *
     * @param ProductInterface $product
     * @throws LocalizedException
     */
    private function processPreOrderAdding(ProductInterface $product)
    {
        if (!$this->isProductPreOrder($product)) {
            $this->processError();
        }
    }

    /**
     * Process regular adding
     *
     * @param ProductInterface $product
     * @throws LocalizedException
     */
    private function processRegularAdding(ProductInterface $product)
    {
        if ($this->isProductPreOrder($product)) {
            $this->processError();
        }
    }

    /**
     * Add notice after adding product to mixed cart
     */
    private function processMixedAdding()
    {
        $this->messageManager->addNoticeMessage("Shopping cart contain both Regular and Pre-Order products!");
    }

    /**
     * Check mixed grouped product
     *
     * @param ProductInterface $product
     * @throws LocalizedException
     */
    private function checkMixedGroupedProduct(ProductInterface $product)
    {
        if ($product->getTypeId() == 'grouped' && $this->isMixedGroupedProduct()) {
            $this->processError();
        }
    }

    /**
     * Is pre order product
     *
     * @param ProductInterface $product
     * @return bool
     * @throws LocalizedException
     */
    private function isProductPreOrder(ProductInterface $product)
    {
        switch ($product->getTypeId()) {
            case 'bundle':
                $isPreOrder = $this->checkBundleProduct($product);
                break;

            case 'configurable':
                $isPreOrder = $this->checkConfigurableProduct($product);
                break;

            case 'grouped':
                $isPreOrder = $this->checkGroupedProduct();
                break;

            default:
                $isPreOrder = $this->perOrderHelper->isProductPreOrder($product->getId());
                break;
        }
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/pre_order.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $logger->info('is pre order - '. $isPreOrder);
        return $isPreOrder;
    }

    /**
     * Check configurable product
     *
     * @param ProductInterface $product
     * @return bool
     * @throws LocalizedException
     */
    private function checkConfigurableProduct(ProductInterface $product)
    {
        $isPreOrder = false;
        $realProductId = false;
        $attributeValueMap = [];
        $superAttributesArray = $this->request->getParam('super_attribute');
        foreach ($superAttributesArray as $attributeId => $value) {
            $this->attributeResource->load($attributeId);
            $code = $this->attributeResource->getAttributeCode();
            $attributeValueMap[$code] = $value;
        }
        $childProducts = $product->getTypeInstance(true)->getUsedProducts($product);
        foreach ($childProducts as $childProduct) {
            $successFind = true;
            foreach ($attributeValueMap as $code => $value) {
                $attributeValue = $childProduct->getData($code);
                if ($attributeValue != $value) {
                    $successFind = false;
                }
            }
            if ($successFind) {
                $realProductId = $childProduct->getId();
                break;
            }
        }

        if ($realProductId) {
            $isPreOrder = $this->perOrderHelper->isProductPreOrder($realProductId);
        }
        return $isPreOrder;
    }

    /**
     * Check bundle product
     *
     * @param ProductInterface $product
     * @return bool
     * @throws LocalizedException
     */
    private function checkBundleProduct(ProductInterface $product)
    {
        $isPreOrder = false;
        $bundleOptionsCollection = $product
            ->getTypeInstance(true)
            ->getSelectionsCollection(
                $product->getTypeInstance(true)->getOptionsIds($product),
                $product
            );
        $selectedOptions = $this->request->getParam('bundle_option');

        foreach ($selectedOptions as $optionId) {
            $childProduct = $bundleOptionsCollection->getItemById($optionId);
            $isPreOrder = $this->perOrderHelper->isProductPreOrder($childProduct->getId());
            if ($isPreOrder) {
                break;
            }
        }
        return $isPreOrder;
    }

    /**
     * Check grouped product
     *
     * @return bool
     * @throws LocalizedException
     */
    private function checkGroupedProduct()
    {
        $isPreOrder = false;
        $groupedOptions = $this->request->getParam('super_group');

        foreach ($groupedOptions as $childProductId => $qty) {
            if ($qty == 0) {
                continue;
            }

            $isPreOrder = $this->perOrderHelper->isProductPreOrder($childProductId);
            if ($isPreOrder) {
                break;
            }
        }

        return $isPreOrder;
    }

    /**
     * Is mixed grouped product
     *
     * @return bool
     * @throws LocalizedException
     */
    private function isMixedGroupedProduct()
    {
        $containsRegular = false;
        $containsPreOrder = false;
        $groupedOptions = $this->request->getParam('super_group');

        foreach ($groupedOptions as $childProductId => $qty) {
            if ($qty == 0) {
                continue;
            }

            $isPreOrder = $this->perOrderHelper->isProductPreOrder($childProductId);
            if ($isPreOrder) {
                $containsPreOrder = true;
            } else {
                $containsRegular = true;
            }
        }

        return $containsPreOrder && $containsRegular;
    }

    /**
     * Process error
     *
     * @throws LocalizedException
     */
    private function processError()
    {
        throw new LocalizedException(__("Please note: Mix of regular and pre-order items is not allowed."));
    }

    /**
     * Get cart type
     *
     * @return int|bool
     */
    private function getCartType()
    {
        return $this->registry->registry(self::CURRENT_CART_TYPE) ?: false;
    }

    /**
     * Get product
     *
     * @param int $productId
     * @return false|ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct($productId)
    {
        if ($productId) {
            $storeId = $this->storeManager->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }
}
