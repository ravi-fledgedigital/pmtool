<?php

namespace OnitsukaTiger\Wishlist\Controller\Index;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\App\Action\Context;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use OnitsukaTiger\Wishlist\Model\ItemCarrier;
use Magento\Framework\Controller\ResultFactory;

/**
 * Action Add Selected to Cart
 */
class Selectedcart extends \Magento\Wishlist\Controller\AbstractIndex implements HttpPostActionInterface
{
    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var \Magento\Wishlist\Model\ItemCarrier
     */
    protected $itemCarrier;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \OnitsukaTiger\Wishlist\Helper\Data
     */
    protected $wishListHelper;

    /**
     * @param Context $context
     * @param WishlistProviderInterface $wishlistProvider
     * @param Validator $formKeyValidator
     * @param ItemCarrier $itemCarrier
     */
    public function __construct(
        Context $context,
        WishlistProviderInterface $wishlistProvider,
        Validator $formKeyValidator,
        ItemCarrier $itemCarrier,
        \OnitsukaTiger\Wishlist\Helper\Data $wishListHelper,
    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->formKeyValidator = $formKeyValidator;
        $this->itemCarrier = $itemCarrier;
        $this->wishListHelper = $wishListHelper;
        parent::__construct($context);
    }

    /**
     * Add all items from wishlist to shopping cart
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $resultForward->forward('noroute');
            return $resultForward;
        }

        $wishlist = $this->wishlistProvider->getWishlist();
        if (!$wishlist) {
            $resultForward->forward('noroute');
            return $resultForward;
        }

        /* check if wish list items is pre order and restrict from addind to cart start */
        $wishlistItems = $wishlist->getItemCollection();
        $wishListItemIsPreOrder = $this->wishListHelper->getPreOrderSelectedItem($wishlistItems);
        $isPreOrderCartItem = $this->wishListHelper->getPreOrderCartItem();
        $productForceOutOfStock = $this->wishListHelper->isProductForceOutOfStock($wishlistItems);
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
        } elseif ($productForceOutOfStock) {
            $this->messageManager->addErrorMessage(__('Some of the products are out of stock.'));
            $redirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $redirect->setPath('wishlist/index/index');
            return $redirect;
        }
        /* check if wish list items is pre order and restrict from addind to cart end */

        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirectUrl = $this->itemCarrier->moveSelectedToCart($wishlist, $this->getRequest()->getParam('qty'),$this->getRequest()->getParam('items'));
        $resultRedirect->setUrl($redirectUrl);
        return $resultRedirect;
    }
}
