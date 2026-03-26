<?php
namespace WeltPixel\GA4\Helper;

class KlaviyoPixelTracking extends Data
{
    const ADDON_TYPE_NAME = 'klaviyo';

    /**
     * @return boolean
     */
    public function isKlaviyoPixelTrackingEnabled()
    {
        return $this->_klaviyoPixelOptions['general_tracking']['enable'];
    }

    /**
     * @return mixed
     */
    public function getKlaviyoPublicApiKey()
    {
        return trim($this->_klaviyoPixelOptions['general_tracking']['public_api_key'] ?? '');
    }

    /**
     * @return array
     */
    public function getKlaviyPixelTrackedEvents()
    {
        $trackedEvents = $this->_klaviyoPixelOptions['general_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldKlaviyoPixelEventBeTracked($eventName)
    {
        $enableFrontendEventSending = true;
        $serverSideTrackingEnabled = $this->isServerSideTrackingEnabled();

        if ($serverSideTrackingEnabled) {
            $enableFrontendEventSending = $this->enableKlaviyoPixelFrontendEventSending();
        }

        $availableEvents = $this->getKlaviyPixelTrackedEvents();
        return in_array($eventName, $availableEvents) && $enableFrontendEventSending;
    }

    /**
     * @param array $categoryIds
     * @return string
     */
    public function getContentCategory($categoryIds)
    {
        $categoriesArray = $this->getGA4CategoriesFromCategoryIds($categoryIds);
        return implode(", ", $categoriesArray);
    }

    /**
     * @param $categoryIds
     * @return array
     */
    public function getCategoryPath($categoryIds)
    {
        $categoriesArray = $this->getGA4CategoriesFromCategoryIds($categoryIds);
        return array_values(array_unique($categoriesArray));
    }

    /**
     * @return string
     */
    public function getEventId()
    {
        $timestamp = time();
        $randomComponent = substr(hash('md5', uniqid(random_int(0, getrandmax()), true)), 0, 8);

        return $timestamp . '_' . $randomComponent;
    }

    /**
     * @return string
     */
    public function getJsEventIdGenerator()
    {
        return "Math.floor(Date.now() / 1000) + '_' + Math.random().toString(16).substring(2, 10)";
    }

    /**
     * @return string
     */
    public function getAddToWishlistEventID()
    {
        if (!$this->registry->registry('klaviyoss_add_to_wishlist_event_uid')) {
            $this->registry->register('klaviyoss_add_to_wishlist_event_uid', $this->getEventId());
        }

        return $this->registry->registry('klaviyoss_add_to_wishlist_event_uid');
    }

    /**
     * @return string
     */
    public function getAddToCartEventID()
    {
        if (!$this->registry->registry('klaviyoss_add_to_cart_event_uid')) {
            $this->registry->register('klaviyoss_add_to_cart_event_uid', $this->getEventId());
        }

        return $this->registry->registry('klaviyoss_add_to_cart_event_uid');
    }

    /**
     * @return string
     */
    public function getSignUpEventID()
    {
        if (!$this->registry->registry('klaviyoss_signup_event_uid')) {
            $this->registry->register('klaviyoss_signup_event_uid', $this->getEventId());
        }

        return $this->registry->registry('klaviyoss_signup_event_uid');
    }

    /**
     * @param $product
     * @return array
     */
    public function klaviyoPixelAddToWishlistPushData($product)
    {
        $price = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));
        $result = [
            'eventName' => $this->getKlaviyoEventName(\WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_ADDED_TO_WISHLIST),
            'eventData' => [
                '$event_id' => $this->getAddToWishlistEventID(),
                '$value' => $price,
                'Currency' => $this->getCurrencyCode(),
                'ProductID' => $product->getId(),
                'SKU' => $product->getSku(),
                'ProductName' => addslashes(str_replace('"', '&quot;', html_entity_decode($product->getName() ?? ''))),
                'Categories' => json_encode($this->getCategoryPath($product->getCategoryIds())),
                'Price' => $price,
                'ImageURL' => $this->getImageUrl($product),
                'URL' => $this->productUrlHelper->getProductUrl($product),
            ]
        ];

        return $result;
    }

    /**
     * @param $product
     * @param int $qty
     * @return array
     */
    public function klaviyoPixelAddToCartPushData($product, $qty = 1)
    {
        $price = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));
        $result = [
            'eventName' => $this->getKlaviyoEventName(\WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_ADDED_TO_CART),
            'eventData' => [
                '$event_id' => $this->getAddToCartEventID(),
                '$value' => $price,
                'Currency' => $this->getCurrencyCode(),
                'ProductID' => $product->getId(),
                'SKU' => $product->getSku(),
                'ProductName' => addslashes(str_replace('"', '&quot;', html_entity_decode($product->getName() ?? ''))),
                'Categories' => json_encode($this->getCategoryPath($product->getCategoryIds())),
                'Quantity' => $qty,
                'Price' => $price,
                'ImageURL' => $this->getImageUrl($product),
                'URL' => $this->productUrlHelper->getProductUrl($product),
            ]
        ];

        return $result;
    }

    /**
     * @return boolean
     */
    public function isServerSideTrackingEnabled()
    {
        if (!$this->_moduleManager->isEnabled('WeltPixel_GA4KlaviyoSS')) {
            return false;
        }
        $isServerSideTrackingEnabled = $this->_klaviyoPixelOptions['serverside_tracking']['enable'];
        if (empty($isServerSideTrackingEnabled)) {
            return false;
        }
        return $isServerSideTrackingEnabled;
    }

    /**
     * @return boolean
     */
    public function enableKlaviyoPixelFrontendEventSending()
    {
        return $this->_klaviyoPixelOptions['serverside_tracking']['enable_frontend_event_sending'] ?? true;
    }

    /**
     * @param string $eventName
     * @return string|null
     */
    public function getKlaviyoEventName($eventName)
    {
        switch ($eventName) {
            case \WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_PLACED_ORDER:
                return 'Placed Order';
            case \WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_ADD_PAYMENT_INFO:
                return 'Checkout Step Completed';
            case \WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_ADDED_TO_CART:
                return 'Added to Cart';
            case \WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_ADDED_TO_WISHLIST:
                return 'Added to Wishlist';
            case \WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_CHECKOUT_STARTED:
                return 'Checkout Started';
            case \WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_PRODUCT_SEARCHED:
                return 'Product Searched';
            case \WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_VIEWED_PRODUCT:
                return 'Viewed Product';
            case \WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_VIEWED_CATEGORY:
                return 'Viewed Category';
            case \WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_CREATED_ACCOUNT:
                return 'Created Account';
            case \WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_VIEWED_PAGE:
                return 'Viewed Page';
            default:
                return $eventName;
        }
    }

    /**
     * @param $product
     * @return bool|string
     */
    public function getProductUrl($product)
    {
        return $this->productUrlHelper->getProductUrl($product);
    }

    /**
     * Retrieve product image url
     *
     * @param \Magento\Catalog\Model\Product|\Magento\Catalog\Api\Data\ProductInterface $product
     * @return \Magento\Catalog\Block\Product\Image
     */
    public function getImageUrl($product)
    {
        try {
            $store = $this->storeManager->getStore();
            $mediaBaseUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

            // Get image path
            $imagePath = $product->getData('image');

            // Return empty if no image
            if (!$imagePath || $imagePath == 'no_selection') {
                return '';
            }

            // Clean up image path
            $imagePath = ltrim(str_replace('\\', '/', $imagePath), '/');

            // For product images, prepend catalog/product
            if (strpos($imagePath, 'catalog/product') !== 0) {
                $imagePath = 'catalog/product/' . $imagePath;
            }

            // Construct full URL
            return $mediaBaseUrl . $imagePath;
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function shouldServerSidePurchaseEventBeTracked($storeId)
    {
        $this->reloadConfigOptions($storeId);
        return $this->isServerSideTrackingEnabled() && $this->shouldKlaviyoServerSideEventBeTracked(\WeltPixel\GA4\Model\Config\Source\KlaviyoPixel\TrackingEvents::EVENT_PLACED_ORDER);
    }

    /**
     * @param $storeId
     * @return void
     */
    public function reloadConfigOptions($storeId)
    {
        $this->_klaviyoPixelOptions = $this->scopeConfig->getValue('weltpixel_ga4_klaviyo_pixel', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return array
     */
    public function getKlaviyoServerSideTrackedEvents()
    {
        $trackedEvents = $this->_klaviyoPixelOptions['serverside_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldKlaviyoServerSideEventBeTracked($eventName)
    {
        $availableEvents = $this->getKlaviyoServerSideTrackedEvents();
        return in_array($eventName, $availableEvents);
    }

    /**
     * @return bool
     */
    public function sendServerSideEventsOnlyForEmail()
    {
        return (boolean) ($this->_klaviyoPixelOptions['serverside_tracking']['send_events_only_for_email'] ?? false);
    }

    /**
     * @return string
     */
    public function getKlaviyoPrivateApiKey()
    {
        return trim($this->_klaviyoPixelOptions['serverside_tracking']['private_api_key'] ?? '');
    }

    /**
     * @return bool
     */
    public function isEnabledFileLog()
    {
        return (boolean) ($this->_klaviyoPixelOptions['serverside_tracking']['enable_file_log'] ?? false);
    }

    /**
     * @return string
     */
    public function getKlaviyoSSTrackUrl()
    {
        return $this->_getUrl('wpx_klaviyo/pixel/tracker');
    }

    /**
     * @return bool
     */
    public function getTrackSpecificCustomerGroups()
    {
        return (boolean)($this->_klaviyoPixelOptions['serverside_tracking']['track_specific_customer_groups'] ?? false);
    }

    /**
     * @return array
     */
    public function getAllowedCustomerGroups()
    {
        $groups = $this->_klaviyoPixelOptions['serverside_tracking']['allowed_customer_groups'] ?? '';
        if (empty($groups)) {
            return [];
        }
        if (is_array($groups)) {
            return $groups;
        }
        return explode(',', $groups);
    }

    /**
     * @return string
     */
    public function getAddonOrderTotalCalculation()
    {
        return $this->_klaviyoPixelOptions['grand_total_calculation']['order_total_calculation'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonTaxFromTransaction()
    {
        return $this->_klaviyoPixelOptions['grand_total_calculation']['exclude_tax_from_transaction'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonShippingFromTransaction()
    {
        return $this->_klaviyoPixelOptions['grand_total_calculation']['exclude_shipping_from_transaction'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonShippingFromTransactionIncludingTax()
    {
        return $this->_klaviyoPixelOptions['grand_total_calculation']['exclude_shipping_from_transaction_including_tax'];
    }

    /**
     * @return bool
     */
    public function sendToMultipleEndpoints()
    {
        return (boolean) ($this->_klaviyoPixelOptions['serverside_tracking']['send_to_multiple_endpoints'] ?? false);
    }

    /**
     * @return array
     */
    public function getMultipleEndpointsConfiguration()
    {
        $multipleEndpointsConfiguration = $this->_klaviyoPixelOptions['serverside_tracking']['multiple_endpoints_configuration'];
        if (isset($multipleEndpointsConfiguration) && strlen($multipleEndpointsConfiguration)) {
            try {
                $endpointOptions = $this->serializer->unserialize($multipleEndpointsConfiguration);
                return $endpointOptions;
            } catch (\Exception $e) {
                return [];
            }
        }

        return [];
    }

}
