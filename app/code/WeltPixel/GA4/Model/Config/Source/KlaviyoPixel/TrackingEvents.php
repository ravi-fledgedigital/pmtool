<?php

namespace WeltPixel\GA4\Model\Config\Source\KlaviyoPixel;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class TrackingEvents
 *
 * @package WeltPixel\GA4\Model\Config\Source\KlaviyoPixel
 */
class TrackingEvents implements ArrayInterface
{
    const EVENT_PLACED_ORDER = 'placed_order';
    const EVENT_ADD_PAYMENT_INFO = 'add_payment_info';
    const EVENT_ADDED_TO_CART = 'added_to_cart';
    const EVENT_ADDED_TO_WISHLIST = 'added_to_wishlist';
    const EVENT_CHECKOUT_STARTED = 'checkout_started';
    const EVENT_PRODUCT_SEARCHED = 'product_searched';
    const EVENT_VIEWED_PRODUCT = 'viewed_product';
    const EVENT_VIEWED_CATEGORY = 'viewed_category';
    const EVENT_CREATED_ACCOUNT = 'created_account';
    const EVENT_VIEWED_PAGE = 'viewed_page'; 


    /**
     * Return list of Id Options
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::EVENT_VIEWED_PAGE,
                'label' => __('View Page')
            ),
            array(
                'value' => self::EVENT_PLACED_ORDER,
                'label' => __('Placed Order')
            ),
            array(
                'value' => self::EVENT_ADDED_TO_CART,
                'label' => __('Added to Cart')
            ),
            array(
                'value' => self::EVENT_ADDED_TO_WISHLIST,
                'label' => __('Added to Wishlist')
            ),
            array(
                'value' => self::EVENT_CHECKOUT_STARTED,
                'label' => __('Checkout Started')
            ),
            array(
                'value' => self::EVENT_PRODUCT_SEARCHED,
                'label' => __('Product Searched')
            ),
            array(
                'value' => self::EVENT_VIEWED_PRODUCT,
                'label' => __('Viewed Product')
            ),
            array(
                'value' => self::EVENT_VIEWED_CATEGORY,
                'label' => __('Viewed Category')
            ),
            array(
                'value' => self::EVENT_CREATED_ACCOUNT,
                'label' => __('Created Account')
            ),
            array(
                'value' => self::EVENT_ADD_PAYMENT_INFO,
                'label' => __('Selected Payment Method (Payment Information)')
            )
        );
    }
}
