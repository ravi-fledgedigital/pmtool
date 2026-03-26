<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\Wishlist\Controller\Index;

use Magento\Catalog\Model\Product\Exception as ProductException;
use Magento\Framework\App\Action;
use Magento\Framework\Controller\ResultFactory;
use OnitsukaTiger\Fixture\Helper\Data as HelperData;
use OnitsukaTiger\Catalog\Helper\Data as HelperCatalog;
use OnitsukaTiger\PreOrders\Helper\PreOrder;

/**
 * Add wishlist item to shopping cart and remove from wishlist controller.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Tocart extends \Magento\Wishlist\Controller\AbstractIndex implements Action\HttpPostActionInterface
{
    /**
     * @var \Magento\Wishlist\Controller\WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var \Magento\Wishlist\Model\LocaleQuantityProcessor
     */
    protected $quantityProcessor;

    /**
     * @var \Magento\Wishlist\Model\ItemFactory
     */
    protected $itemFactory;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $cartHelper;

    /**
     * @var \Magento\Wishlist\Model\Item\OptionFactory
     */
    private $optionFactory;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Magento\Wishlist\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    private HelperData $helperData;

    private HelperCatalog $helperCatalog;

    /**
     * @var PreOrder
     */
    protected $preOrderHelper;

    /**
     * @param Action\Context $context
     * @param \Magento\Wishlist\Controller\WishlistProviderInterface $wishlistProvider
     * @param \Magento\Wishlist\Model\LocaleQuantityProcessor $quantityProcessor
     * @param \Magento\Wishlist\Model\ItemFactory $itemFactory
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Wishlist\Model\Item\OptionFactory $optionFactory
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Wishlist\Helper\Data $helper
     * @param \Magento\Checkout\Helper\Cart $cartHelper
     * @param HelperData $helperData
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param PreOrder $preOrderHelper
     */
    public function __construct(
        Action\Context $context,
        \Magento\Wishlist\Controller\WishlistProviderInterface $wishlistProvider,
        \Magento\Wishlist\Model\LocaleQuantityProcessor $quantityProcessor,
        \Magento\Wishlist\Model\ItemFactory $itemFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Wishlist\Model\Item\OptionFactory $optionFactory,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Framework\Escaper $escaper,
        \Magento\Wishlist\Helper\Data $helper,
        \Magento\Checkout\Helper\Cart $cartHelper,
        HelperData $helperData,
        HelperCatalog $helperCatalog,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        PreOrder $preOrderHelper
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->quantityProcessor = $quantityProcessor;
        $this->itemFactory = $itemFactory;
        $this->cart = $cart;
        $this->optionFactory = $optionFactory;
        $this->productHelper = $productHelper;
        $this->escaper = $escaper;
        $this->helper = $helper;
        $this->cartHelper = $cartHelper;
        $this->formKeyValidator = $formKeyValidator;
        $this->helperData = $helperData;
        $this->helperCatalog = $helperCatalog;
        $this->preOrderHelper = $preOrderHelper;
        parent::__construct($context);
    }

    /**
     * Add wishlist item to shopping cart and remove from wishlist
     *
     * If Product has required options - item removed from wishlist and redirect
     * to product view page with message about needed defined required options
     *
     * @return \Magento\Framework\Controller\ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/*/');
        }

        $itemId = (int)$this->getRequest()->getParam('item');
        /* @var $item \Magento\Wishlist\Model\Item */
        $item = $this->itemFactory->create()->load($itemId);
        if (!$item->getId()) {
            $resultRedirect->setPath('*/*');
            return $resultRedirect;
        }
        $wishlist = $this->wishlistProvider->getWishlist($item->getWishlistId());
        if (!$wishlist) {
            $resultRedirect->setPath('*/*');
            return $resultRedirect;
        }

        // Set qty
        $qty = $this->getRequest()->getParam('qty');
        $postQty = $this->getRequest()->getPostValue('qty');
        if ($postQty !== null && $qty !== $postQty) {
            $qty = $postQty;
        }
        if (is_array($qty)) {
            if (isset($qty[$itemId])) {
                $qty = $qty[$itemId];
            } else {
                $qty = 1;
            }
        }
        $qty = $this->quantityProcessor->process($qty);
        if ($qty) {
            $item->setQty($qty);
        }

        $redirectUrl = $this->_url->getUrl('*/*');
        $configureUrl = $this->_url->getUrl(
            '*/*/configure/',
            [
                'id' => $item->getId(),
                'product_id' => $item->getProductId(),
            ]
        );

        try {
            /** @var \Magento\Wishlist\Model\ResourceModel\Item\Option\Collection $options */
            $options = $this->optionFactory->create()->getCollection()->addItemFilter([$itemId]);
            $item->setOptions($options->getOptionsByItem($itemId));

            if ($this->helperData->getConfig('catalog/them_customize/qty_validate_sales')) {
                $this->helperCatalog->validateProductSalesQty($item, $qty);
            }
            $buyRequest = $this->productHelper->addParamsToBuyRequest(
                $this->getRequest()->getParams(),
                ['current_config' => $item->getBuyRequest()]
            );

            /* check if wish list items is pre order and restrict from addind to cart start */
            $productId = $this->helperCatalog->getProduct($item->getProduct()->getSku())->getId();

            $wishListItemIsPreOrder = '';
            $isPreOrderCartItem = '';

            $wishListItemIsPreOrder = $this->preOrderHelper->isProductPreOrder($productId);
            $currentCartItems = $this->cart->getQuote()->getAllItems();

            foreach ($currentCartItems as $cartItems) {
                if($cartItems->getProductType() == 'simple'){
                    $isPreOrderCartItem  = $this->preOrderHelper->isProductPreOrder($cartItems->getProductId());
                    if($isPreOrderCartItem){
                        break;
                    }
                }
            }

            if($isPreOrderCartItem && !$wishListItemIsPreOrder){
                $this->messageManager->addErrorMessage(__('Please note: Mix of regular and pre-order items is not allowed.'));
                $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
                $redirect->setPath('wishlist/index/index');

                return $redirect;
            }elseif(!$isPreOrderCartItem && $wishListItemIsPreOrder){
                $this->messageManager->addErrorMessage(__('Please note: Mix of regular and pre-order items is not allowed.'));
                $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
                $redirect->setPath('wishlist/index/index');

                return $redirect;
            }elseif($isPreOrderCartItem && $wishListItemIsPreOrder){
                $this->messageManager->addErrorMessage(__('Please note: Mix of regular and pre-order items is not allowed.'));
                $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
                $redirect->setPath('wishlist/index/index');

                return $redirect;
            }
            /* check if wish list items is pre order and restrict from addind to cart end */

            $item->mergeBuyRequest($buyRequest);
            $item->addToCart($this->cart, false);
            $this->cart->save()->getQuote()->collectTotals();
            $wishlist->save();

            if (!$this->cart->getQuote()->getHasError()) {
                $message = __(
                    'You added %1 to your shopping cart.',
                    $this->escaper->escapeHtml($item->getProduct()->getName())
                );
                $this->messageManager->addSuccessMessage($message);
            }

            $redirectUrl = $this->cartHelper->getCartUrl();
        } catch (ProductException $e) {
            $this->messageManager->addErrorMessage(__('This product(s) is out of stock.'));
        } catch (\Magento\Framework\Exception\ValidatorException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $redirectUrl = $configureUrl;
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addNoticeMessage($e->getMessage());
            $redirectUrl = $configureUrl;
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('We can\'t add the item to the cart right now.'));
        }

        $this->helper->calculate();

        if ($this->getRequest()->isAjax()) {
            /** @var \Magento\Framework\Controller\Result\Json $resultJson */
            $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
            $resultJson->setData(['backUrl' => $redirectUrl]);
            return $resultJson;
        }

        $resultRedirect->setUrl($redirectUrl);
        return $resultRedirect;
    }
}
