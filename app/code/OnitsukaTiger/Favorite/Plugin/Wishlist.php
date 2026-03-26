<?php

namespace OnitsukaTiger\Favorite\Plugin;

use Magento\Framework\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Wishlist\Controller\WishlistProviderInterface;

class Wishlist
{       
    protected $resultFactory;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * Product type code
     */
    public const TYPE_CODE = 'configurable';

        /**
     * @var WishlistProviderInterface
     */
        protected $wishlistProvider;

    /**
     * @var Session
     */
    protected $_customerSession;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var EventManagerInterface
     */
    protected $_eventManager;

    /**
     * @var RedirectInterface
     */
    protected $_redirect;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;


    public function __construct(
        Context $context,
        ResultFactory $resultFactory,
        Session $customerSession,
        WishlistProviderInterface $wishlistProvider,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Validator $formKeyValidator,
        ?RedirectInterface $redirect = null,
        ?UrlInterface $urlBuilder = null
    )
    {
        $this->resultFactory = $resultFactory;
        $this->_customerSession = $customerSession;
        $this->wishlistProvider = $wishlistProvider;
        $this->productRepository = $productRepository;
        $this->formKeyValidator = $formKeyValidator;
        $this->messageManager = $messageManager;
        $this->_eventManager = $context->getEventManager();
        $this->_redirect = $context->getRedirect();
        $this->_objectManager = $context->getObjectManager();
        $this->redirect = $redirect ?: ObjectManager::getInstance()->get(RedirectInterface::class);
        $this->urlBuilder = $urlBuilder ?: ObjectManager::getInstance()->get(UrlInterface::class);
    }


    public function aroundExecute(\Magento\Wishlist\Controller\Index\Add $subject, \Closure $proceed)
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $session = $this->_customerSession;
        $requestParams = $subject->getRequest()->getParams();

        if (!$this->formKeyValidator->validate($subject->getRequest())) {
            return $resultRedirect->setPath('*/');
        }
        $buyRequest = new \Magento\Framework\DataObject($requestParams);

        $wishlist = $this->wishlistProvider->getWishlist();
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }

        $productId = isset($requestParams['productsensei']) ? (int)$requestParams['productsensei'] : (isset($requestParams['product']) ? (int)$requestParams['product'] : null);
        $miniFlag = isset($requestParams['from_minicart']) ? $requestParams['from_minicart'] : false;
        
        if (!$productId) {
            $resultRedirect->setPath('*/');
            return $resultRedirect;
        }

        try {
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            $product = null;
        }

        if (!$product || !$product->isVisibleInCatalog()) {
            $this->messageManager->addErrorMessage(__('We can\'t specify a product.'));
            $resultRedirect->setPath('*/');
            return $resultRedirect;
        }

        if($product->getTypeId() == self::TYPE_CODE){
            if(!isset($buyRequest['super_attribute'])){

             $this->messageManager->addWarningMessage(__('You need to choose options for your item.'));

             /** @var Redirect $resultRedirect */

             $resultRedirect->setPath($product->getProductUrl());
             return $resultRedirect;
         }
     }

     try {

        $result = $wishlist->addNewItem($product, $buyRequest);
        if (is_string($result)) {
            throw new LocalizedException(__($result));
        }
        if ($wishlist->isObjectNew()) {
            $wishlist->save();
        }
        $this->_eventManager->dispatch(
            'wishlist_add_product',
            ['wishlist' => $wishlist, 'product' => $product, 'item' => $result]
        );

        $referer = $session->getBeforeWishlistUrl();
        if ($referer) {
            $session->setBeforeWishlistUrl(null);
        } else {
                // phpcs:ignore
            $referer = $this->_redirect->getRefererUrl();
        }

        $this->_objectManager->get(\Magento\Wishlist\Helper\Data::class)->calculate();

        $this->messageManager->addComplexSuccessMessage(
            'addProductSuccessMessage',
            [
                'product_name' => $product->getName(),
                'referer' => $referer
            ]
        );
            // phpcs:disable Magento2.Exceptions.ThrowCatch
    } catch (LocalizedException $e) {
        $this->messageManager->addErrorMessage(
            __('We can\'t add the item to Wish List right now: %1.', $e->getMessage())
        );
    } catch (\Exception $e) {
        $this->messageManager->addExceptionMessage(
            $e,
            __('We can\'t add the item to Wish List right now.')
        );
    }

    if ($miniFlag) {
        $url = $this->urlBuilder->getUrl('*/*');
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(["success" => true]);

        return $resultJson;
    }

    if ($subject->getRequest()->isAjax()) {
        $url = $this->urlBuilder->getUrl(
            '*',
            $this->redirect->updatePathParams(
                ['wishlist_id' => $wishlist->getId()]
            )
        );
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData(['backUrl' => $url]);

        return $resultJson;
    }
    $resultRedirect->setPath('*', ['wishlist_id' => $wishlist->getId()]);

    return $resultRedirect;


        // call the core observed function 
    return $proceed();
}
}
?>