<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\PreOrders\Preference\Controller\Cart;

use Magento\Checkout\Model\Cart\RequestQuantityProcessor;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as AttributeResource;
use Magento\Framework\Filter\LocalizedToNormalized;

/**
 * Controller for processing add to cart action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart\Add
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var RequestQuantityProcessor
     */
    private $quantityProcessor;

    /**
     * @var AttributeResource
     */
    private $attributeResource;

    /**
     * @var OnitsukaTiger\PreOrders\Helper\PreOrder
     */
    protected $preOrderHelper;

    /**
     * @var OnitsukaTiger\PreOrders\Helper\Data
     */
    protected $dataHelper;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @param RequestQuantityProcessor|null $quantityProcessor
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        AttributeResource $attributeResource,
        \OnitsukaTiger\PreOrders\Helper\Data $dataHelper,
        \OnitsukaTiger\PreOrders\Helper\PreOrder $preOrderHelper,
        ?RequestQuantityProcessor $quantityProcessor = null
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository,
            $quantityProcessor
        );
        $this->productRepository = $productRepository;
        $this->attributeResource = $attributeResource;
        $this->dataHelper = $dataHelper;
        $this->preOrderHelper = $preOrderHelper;
        $this->quantityProcessor = $quantityProcessor
            ?? ObjectManager::getInstance()->get(RequestQuantityProcessor::class);
    }

    /**
     * Initialize product instance from request data
     *
     * @return \Magento\Catalog\Model\Product|false
     */
    protected function _initProduct()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        if ($productId) {
            $storeId = $this->_objectManager->get(
                \Magento\Store\Model\StoreManagerInterface::class
            )->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Add product to shopping cart action
     *
     * @return ResponseInterface|ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired')
            );
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();

        try {
            if (isset($params['qty'])) {
                $filter = new LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get(
                        \Magento\Framework\Locale\ResolverInterface::class
                    )->getLocale()]
                );
                $params['qty'] = $this->quantityProcessor->prepareQuantity($params['qty']);
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();

            $related = $this->getRequest()->getParam('related_product');

            /** Check product availability */
            if (!$product) {
                return $this->goBack();
            }

            $isPreOrder = $this->isProductPreOrder($product);
            $isCustomerLoggedIn  = $this->dataHelper->isLoggedIn();

            if ($isPreOrder && !$isCustomerLoggedIn) {
                $customRedirectionUrl = $this->_url->getUrl('customer/account/login');
                return $this->goBack($customRedirectionUrl);
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
            $this->cart->save();

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if ($this->shouldRedirectToCart()) {
                    $message = __(
                        'You added %1 to your shopping cart.',
                        $product->getName()
                    );
                    $this->messageManager->addSuccessMessage($message);
                } else {
                    $this->messageManager->addComplexSuccessMessage(
                        'addCartSuccessMessage',
                        [
                            'product_name' => $product->getName(),
                            'cart_url' => $this->getCartUrl(),
                        ]
                    );
                }
                if ($this->cart->getQuote()->getHasError()) {
                    $errors = $this->cart->getQuote()->getErrors();
                    foreach ($errors as $error) {
                        $this->messageManager->addErrorMessage($error->getText());
                    }
                }
                return $this->goBack(null, $product);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage(
                    $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage(
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);
            if (!$url) {
                $url = $this->_redirect->getRedirectUrl($this->getCartUrl());
            }

            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            return $this->goBack();
        }

        return $this->getResponse();
    }

    /**
     * Resolve response
     *
     * @param string $backUrl
     * @param \Magento\Catalog\Model\Product $product
     * @return ResponseInterface|ResultInterface
     */
    protected function goBack($backUrl = null, $product = null)
    {
        if (!$this->getRequest()->isAjax()) {
            return parent::_goBack($backUrl);
        }

        $result = [];

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $backUrl;
            $result['customRedirectUrl'] = $backUrl;
        } else {
            if ($product && !$product->getIsSalable()) {
                $result['product'] = [
                    'statusText' => __('Out of stock')
                ];
            }
        }

        $this->getResponse()->representJson(
            $this->_objectManager->get(\Magento\Framework\Json\Helper\Data::class)->jsonEncode($result)
        );

        return $this->getResponse();
    }

    /**
     * Returns cart url
     *
     * @return string
     */
    private function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }

    /**
     * Is redirect should be performed after the product was added to cart.
     *
     * @return bool
     */
    private function shouldRedirectToCart()
    {
        return $this->_scopeConfig->isSetFlag(
            'checkout/cart/redirect_to_cart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
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
        $writer = new \Zend_Log_Writer_Stream(BP . "/var/log/pre_order.log");
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

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
                $isPreOrder = $this->preOrderHelper->isProductPreOrder($product->getId());
                break;
        }

        $logger->info('is pre order - '. $isPreOrder);
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
        $selectedOptions = $this->getRequest()->getParam('bundle_option');

        foreach ($selectedOptions as $optionId) {
            $childProduct = $bundleOptionsCollection->getItemById($optionId);
            $isPreOrder = $this->preOrderHelper->isProductPreOrder($childProduct->getId());
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
        $groupedOptions = $this->getRequest()->getParam('super_group');

        foreach ($groupedOptions as $childProductId => $qty) {
            if ($qty == 0) {
                continue;
            }

            $isPreOrder = $this->preOrderHelper->isProductPreOrder($childProductId);
            if ($isPreOrder) {
                break;
            }
        }

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
        $superAttributesArray = $this->getRequest()->getParam('super_attribute');
        if (!empty($superAttributesArray)) {
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
                $isPreOrder = $this->preOrderHelper->isProductPreOrder($realProductId);
            }
        }
        return $isPreOrder;
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
