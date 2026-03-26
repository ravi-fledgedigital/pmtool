<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace OnitsukaTiger\Restock\Controller\Unsubscribe;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\ProductAlert\Controller\Unsubscribe as UnsubscribeController;
use Magento\ProductAlert\Model\StockFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\ProductAlert\Controller\Unsubscribe\Stock as StockController;

/**
 * Unsubscribing from 'back in stock alert'.
 */
class Stock extends StockController
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface|null
     */
    private $storeManager;

    /**
     * @var StockFactory|null
     */
    private $stockFactory;

    /**
     * @var OnitsukaTiger\Restock\Helper\Data
     */
    private $helperData;

    /**
     * @param Context $context
     * @param CustomerSession $customerSession
     * @param ProductRepositoryInterface $productRepository
     * @param \OnitsukaTiger\Restock\Helper\Data $helperData
     * @param StoreManagerInterface|null $storeManager
     * @param StockFactory|null $stockFactory
     */
    public function __construct(
        Context $context,
        CustomerSession $customerSession,
        ProductRepositoryInterface $productRepository,
        \OnitsukaTiger\Restock\Helper\Data $helperData,
        StoreManagerInterface $storeManager = null,
        StockFactory $stockFactory = null
    ) {
        $this->productRepository = $productRepository;
        $this->helperData = $helperData;
        $this->storeManager = $storeManager ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->stockFactory = $stockFactory ?? ObjectManager::getInstance()->get(StockFactory::class);
        parent::__construct($context, $customerSession, $productRepository, $storeManager, $stockFactory);
    }

    /**
     * Unsubscribing from 'back in stock alert'.
     *
     * @return Redirect
     */
    public function execute()
    {
        $productId = (int)$this->getRequest()->getParam('product');
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$productId) {
            $resultRedirect->setPath('/');
            return $resultRedirect;
        }

        try {
            $product = $this->retrieveProduct($productId);
            $model = $this->stockFactory->create()
                ->setCustomerId($this->customerSession->getCustomerId())
                ->setProductId($product->getId())
                ->setWebsiteId(
                    $this->storeManager
                        ->getStore()
                        ->getWebsiteId()
                )->setStoreId(
                    $this->storeManager
                        ->getStore()
                        ->getId()
                )
                ->loadByParam();
            if ($model->getId()) {
                $model->delete();
            }
            $this->messageManager->addSuccessMessage(__('You will no longer receive stock alerts for this product.'));
        } catch (NoSuchEntityException $noEntityException) {
            $this->messageManager->addErrorMessage(__('The product was not found.'));
            $resultRedirect->setPath('customer/account/');
            return $resultRedirect;
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __("The alert subscription couldn't update at this time. Please try again later.")
            );
        }
        /*get parent product url*/
       	$parentProductUrl =  $this->helperData->getParentUrlBySku($product->getSku());
        $resultRedirect->setUrl($parentProductUrl);
        return $resultRedirect;
    }

    /**
     * Retrieving product
     *
     * @param int $productId
     *
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function retrieveProduct(int $productId): ProductInterface
    {
        $product = $this->productRepository->getById($productId);
        if (!$product->isVisibleInCatalog()) {
            throw new NoSuchEntityException();
        }
        return $product;
    }
}
