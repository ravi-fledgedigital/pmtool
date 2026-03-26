<?php

namespace Vaimo\OTAdobeDataLayer\Plugin\Checkout\Model;

use Magento\Checkout\Model\Session;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Checkout\Model\ShippingInformationManagement as Subject;
use Magento\Checkout\Api\Data\ShippingInformationInterface;

/**
 * Shipping information management model
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class ShippingInformationManagement
{
 
    /**
     * @var Session
     */
    private Session $checkoutSession;

    /**
     * @var \Vaimo\OTAdobeDataLayer\Helper\Data
     */
    private $dataLayerHelper;

    /**
     * Construct
     *
     * @param Session $checkoutSession
     * @param \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper
     */
    public function __construct(
        Session $checkoutSession,
        \Vaimo\OTAdobeDataLayer\Helper\Data $dataLayerHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->dataLayerHelper = $dataLayerHelper;
    }

    /**
     * After save address information
     *
     * @param Subject $subject
     * @param \Magento\Checkout\Api\Data\PaymentDetailsInterface $result
     * @param int $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return mixed
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSaveAddressInformation(
        Subject $subject,
        $result,
        $cartId,
        ShippingInformationInterface $addressInformation
    ) {

        if($this->dataLayerHelper->isEnabledAdobeLaunch()){
            // get array of all quote items
            $itemsVisible = $this->checkoutSession->getQuote()->getAllVisibleItems();
            $currencyCode  = $this->dataLayerHelper->getCurrencyCode();
            $discountAmount = 0;
            $itemData = [];
            foreach ($itemsVisible as $items) {
                if($discount = $items->getDiscountAmount()){
                   $discountAmount += $discount;
                }
                $catName = '';
                $product = $this->dataLayerHelper->loadProductById($items->getProductId());
                if($product->getCategoryIds() && isset($product->getCategoryIds()[0])){
                    $category = $this->dataLayerHelper->getCategoryLoadById($product->getCategoryIds()[0]);
                    $catName = $category->getName();
                }
                $itemData[] =  [
                    'sku'=> $product->getSku(),
                    'name' => $product->getName(),
                    'productId' => $items->getProductId(),
                    'category' => $catName,
                    'brand' => ($product->getBrands()) ? $product->getAttributeText('brands'): '',
                    'color' => ($items->getColor()) ? $items->getAttributeText('color'): '',
                    'size' => ($product->getQaSize()) ? $product->getAttributeText('qa_size'): '',
                    'quantity'=> (int)$items->getQty(),
                    'currencyCode' => $currencyCode,
                    'priceTotal' => floatval($items->getPrice()),
                    'discountAmount' => floatval(($product->getSpecialPrice() ? $product->getPrice()-$product->getSpecialPrice(): 0)),
                    'unitOfMeasureCode' => 'ft'
                ];
            }

            $quoteData = [
                'checkout' => ['cartID' => $this->checkoutSession->getQuote()->getId()],
                'order' =>  [
                    'purchaseId' => '',
                    'currencyCode'=> $currencyCode,
                    'couponCode' => ($this->checkoutSession->getQuote()->getCouponCode() ? $this->checkoutSession->getQuote()->getCouponCode() : ''),
                    'shipping' => floatval($this->checkoutSession->getQuote()->getShippingAddress()->getShippingAmount()),
                    'discount' => floatval($discountAmount),
                    'subTotal' => floatval($this->checkoutSession->getQuote()->getSubtotal()),
                    'priceTotal' => floatval($this->checkoutSession->getQuote()->getGrandTotal()),
                    'payment' => [
                        'transactionId' => '',
                        'paymentAmount' => floatval($this->checkoutSession->getQuote()->getGrandTotal()),
                        'paymentType'   => '',
                        'currencyCode'  => $currencyCode
                    ]
                ]
            ];

            $this->dataLayerHelper->setShipingDataEvent(['data' => $quoteData, 'productListItems' => $itemData]);
        }
        return $result;
    }
}