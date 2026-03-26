<?php
namespace WeltPixel\GA4\Model\Config\Source\BingPixel;

class TrackingEvents implements \Magento\Framework\Option\ArrayInterface
{
    const EVENT_PURCHASE = 'purchase';
    const EVENT_ADD_PAYMENT_INFO = 'add_payment_info';
    const EVENT_ADD_TO_CART = 'add_to_cart';
    const EVENT_ADD_TO_WISHLIST = 'add_to_wishlist';
    const EVENT_BEGIN_CHECKOUT = 'begin_checkout';
    const EVENT_SEARCH = 'search';
    const EVENT_VIEW_ITEM = 'view_item';
    const EVENT_VIEW_ITEM_LIST = 'view_category';
    const EVENT_SIGNUP = 'signup';

    /**
     * Return list of Tracking Events
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::EVENT_PURCHASE,
                'label' => __('Purchase')
            ],
            [
                'value' => self::EVENT_ADD_TO_CART,
                'label' => __('Add to Cart')
            ],
            [
                'value' => self::EVENT_VIEW_ITEM,
                'label' => __('Product View')
            ],
            [
                'value' => self::EVENT_ADD_TO_WISHLIST,
                'label' => __('Add to Wishlist')
            ],
            [
                'value' => self::EVENT_BEGIN_CHECKOUT,
                'label' => __('Begin Checkout')
            ],
            [
                'value' => self::EVENT_ADD_PAYMENT_INFO,
                'label' => __('Add Payment Info')
            ],
            [
                'value' => self::EVENT_VIEW_ITEM_LIST,
                'label' => __('View Category')
            ],
            [
                'value' => self::EVENT_SEARCH,
                'label' => __('Search')
            ],
            [
                'value' => self::EVENT_SIGNUP,
                'label' => __('Signup')
            ]
        ];
    }
}
