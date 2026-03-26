<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Vaimo\OTAdobeDataLayer\Preference\WishList\Controller\Index;

use Magento\Framework\App\Action;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Wishlist\Controller\WishlistProviderInterface;
use Magento\Wishlist\Model\Item;
use Magento\Wishlist\Model\Product\AttributeValueProvider;
use Vaimo\OTAdobeDataLayer\Helper\Data;

/**
 * Wishlist Remove Controller
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Remove extends \Magento\Wishlist\Controller\Index\Remove
{
    /**
     * @var WishlistProviderInterface
     */
    protected $wishlistProvider;

    /**
     * @var Validator
     */
    protected $formKeyValidator;

    /**
     * @var AttributeValueProvider
     */
    private $attributeValueProvider;

    /**
     * @var Validator
     */
    protected Data $dataLayerHelper;

    /**
     * @param Action\Context $context
     * @param WishlistProviderInterface $wishlistProvider
     * @param Validator $formKeyValidator
     * @param AttributeValueProvider|null $attributeValueProvider
     */
    public function __construct(
        Action\Context $context,
        WishlistProviderInterface $wishlistProvider,
        Validator $formKeyValidator,
        AttributeValueProvider $attributeValueProvider = null,
        Data $dataLayerHelper

    ) {
        $this->wishlistProvider = $wishlistProvider;
        $this->formKeyValidator = $formKeyValidator;
        $this->attributeValueProvider = $attributeValueProvider
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(AttributeValueProvider::class);
        $this->dataLayerHelper = $dataLayerHelper;
        parent::__construct($context, $wishlistProvider, $formKeyValidator, $attributeValueProvider);
    }

    /**
     * Remove item
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws NotFoundException
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setPath('*/*/');
        }

        $id = (int)$this->getRequest()->getParam('item');
        /** @var Item $item */
        $item = $this->_objectManager->create(Item::class)->load($id);
        if (!$item->getId()) {
            throw new NotFoundException(__('Page not found.'));
        }
        $wishlist = $this->wishlistProvider->getWishlist($item->getWishlistId());
        if (!$wishlist) {
            throw new NotFoundException(__('Page not found.'));
        }
        try {
            if($this->dataLayerHelper->isEnabledAdobeLaunch()){
                /*add remove wishlist datalater*/
                $returnData = $this->dataLayerHelper->getProductListItem($item->getProductId());

                if(!empty($returnData)){
                    $data = ['item' => $returnData];
                    $this->dataLayerHelper->setRemoveWishListData($data);
                }
            }

            $item->delete();
            $wishlist->save();
            $productName = $this->attributeValueProvider
                ->getRawAttributeValue($item->getProductId(), 'name');
            $this->messageManager->addComplexSuccessMessage(
                'removeWishlistItemSuccessMessage',
                [
                    'product_name' => $productName,
                ]
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addErrorMessage(
                __('We can\'t delete the item from Wish List right now because of an error: %1.', $e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('We can\'t delete the item from the Wish List right now.'));
        }

        $this->_objectManager->get(\Magento\Wishlist\Helper\Data::class)->calculate();
        $refererUrl = $this->_redirect->getRefererUrl();
        if ($refererUrl) {
            $redirectUrl = $refererUrl;
        } else {
            $redirectUrl = $this->_redirect->getRedirectUrl($this->_url->getUrl('*/*'));
        }
        $resultRedirect->setUrl($redirectUrl);
        return $resultRedirect;
    }
}
