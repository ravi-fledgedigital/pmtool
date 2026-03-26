<?php

namespace WeltPixel\GA4\Model\Config\Source\PinterestPixel;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class TrackingEvents
 *
 * @package WeltPixel\GA4\Model\Config\Source\PinterestPixel
 */
class TrackingEvents implements ArrayInterface
{
    const EVENT_PURCHASE = 'checkout';
    const EVENT_ADD_PAYMENT_INFO = 'addpaymentinfo';
    const EVENT_ADD_TO_CART = 'addtocart';
    const EVENT_ADD_TO_WISHLIST = 'addtowishlist';
    const EVENT_INITIATE_CHECKOUT = 'initiatecheckout';
    const EVENT_SEARCH = 'search';
    const EVENT_VIEW_CONTENT = 'viewcontent';
    const EVENT_VIEW_CATEGORY = 'viewcategory';
    const EVENT_SIGN_UP = 'signup';
    const EVENT_PAGE_VISIT = 'pagevisit';


    /**
     * Return list of Id Options
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::EVENT_PAGE_VISIT,
                'label' => __('PageVisit')
            ),
            array(
                'value' => self::EVENT_PURCHASE,
                'label' => __('Checkout (Purchase)')
            ),
            array(
                'value' => self::EVENT_ADD_PAYMENT_INFO,
                'label' => __('AddPaymentInfo')
            ),
            array(
                'value' => self::EVENT_ADD_TO_CART,
                'label' => __('AddToCart')
            ),
            array(
                'value' => self::EVENT_ADD_TO_WISHLIST,
                'label' => __('AddToWishlist')
            ),
            array(
                'value' => self::EVENT_INITIATE_CHECKOUT,
                'label' => __('InitiateCheckout')
            ),
            array(
                'value' => self::EVENT_SEARCH,
                'label' => __('Search')
            ),
            array(
                'value' => self::EVENT_VIEW_CONTENT,
                'label' => __('ViewContent')
            ),
            array(
                'value' => self::EVENT_VIEW_CATEGORY,
                'label' => __('ViewCategory')
            ),
            array(
                'value' => self::EVENT_SIGN_UP,
                'label' => __('Signup')
            )
        );
    }
}
