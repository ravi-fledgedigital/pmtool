<?php
namespace WeltPixel\GA4\Helper;

use Magento\Store\Model\ScopeInterface;

class XPixelTracking extends Data
{
    const ADDON_TYPE_NAME = 'twitter';

    /**
     * @return boolean
     */
    public function isXPixelTrackingEnabled() {
        return $this->_xPixelOptions['general_tracking']['enable'];
    }


    /**
     * @return string
     */
    public function getXPixelTrackingCodeSnippet() {
        $codeSnippet = trim($this->_xPixelOptions['general_tracking']['code_snippet'] ?? '');
        if ($this->isDevMoveJsBottomEnabled()) {
            $codeSnippet = str_replace('<script', '<script data-exclude-this-tag="text/x-magento-template" ', $codeSnippet);
        }
        $scriptAttributes = $this->getScriptAttributes();
        if ($scriptAttributes) {
            $codeSnippet = str_replace('<script', '<script ' . $scriptAttributes, $codeSnippet);
        }
        return $codeSnippet;
    }


    /**
     * @return array
     */
    public function getXPixelTrackedEvents() {
        $trackedEvents = $this->_xPixelOptions['general_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }


    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldXPixelEventBeTracked($eventName) {
        $enableFrontendEventSending = true;
        $serverSideTrackingEnabled = $this->isServerSideTrackingEnabled();

        if ($serverSideTrackingEnabled) {
            $enableFrontendEventSending = $this->enableXPixelFrontendEventSending();
        }

        $availableEvents = $this->getXPixelTrackedEvents();
        return in_array($eventName, $availableEvents) && $enableFrontendEventSending;
    }

    /**
     * Returns the product id or sku based on the backend settings
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getXProductId($product)
    {
        $idOption = $this->_xPixelOptions['general_tracking']['id_selection'];
        $xProductId = '';

        switch ($idOption) {
            case 'sku':
                $xProductId = $product->getData('sku');
                break;
            case 'id':
            default:
            $xProductId = $product->getId();
                break;
        }

        return $xProductId;
    }

    /**
     * @param array $categoryIds
     * @return string
     */
    public function getContentCategory($categoryIds)
    {
        $categoriesArray = $this->getGA4CategoriesFromCategoryIds($categoryIds);
        return implode(" > ", $categoriesArray);
    }

    /**
     * @return string
     */
    public function getEventConversionId($eventType = 'event')
    {
        $eventType = strtolower($eventType);
        $timestamp = round(microtime(true) * 1000); // Current time in milliseconds
        $baseId = $eventType . '_' . $timestamp;
        $hashInput = $baseId . '_' . uniqid('', true);
        $hash = substr(hash('md5', $hashInput), 0, 8); // First 8 chars of MD5 hash

        return "{$eventType}_{$hash}_{$timestamp}";
    }

    /**
     * @param string $eventType
     * @return string
     */
    public function getJsEventIdGenerator($eventType = 'event')
    {
        return "'" . strtolower($eventType) . "_' + Math.random().toString(16).substring(2, 10) + '_' + Date.now()";
    }

    /**
     * @return string
     */
    public function getAddToWishlistEventConversionID() {
        if (!$this->registry->registry('xss_add_to_wishlist_event_uid')) {
            $this->registry->register('xss_add_to_wishlist_event_uid', $this->getEventConversionId('add_to_wishlist'));
        }

        return $this->registry->registry('xss_add_to_wishlist_event_uid');
    }

    /**
     * @return string
     */
    public function getAddToCartEventConversionID() {
        if (!$this->registry->registry('xss_add_to_cart_event_uid')) {
            $this->registry->register('xss_add_to_cart_event_uid', $this->getEventConversionId('add_to_cart'));
        }

        return $this->registry->registry('xss_add_to_cart_event_uid');
    }

    /**
     * @param $product
     * @return array
     */
    public function xPixelAddToWishlistPushData($product)
    {
        $productPrice = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));
        $productName = addslashes(str_replace('"','&quot;',html_entity_decode($product->getName() ?? '')));
        $result = [
            'event' => 'event',
            'eventName' => $this->getTwitterEventId(\WeltPixel\GA4\Model\Config\Source\XPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST),
            'eventData' => [
                'value' => $productPrice,
                'currency' => $this->getCurrencyCode(),
                'contents' => [
                    [
                        'content_id' => $this->getXProductId($product),
                        'content_name' => $productName,
                        'content_price' => $productPrice,
                        'num_items' => 1
                    ]
                ],
                'description' => 'Added ' . $productName . ' to wishlist',
                'conversion_id' => $this->getAddToWishlistEventConversionID()
            ]
        ];

        return $result;
    }

    /**
     * @param $product
     * @param int $qty
     * @return array
     */
    public function xPixelAddToCartPushData($product, $qty = 1)
    {
        $productPrice = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));
        $productName = addslashes(str_replace('"','&quot;',html_entity_decode($product->getName() ?? '')));
        $result = [
            'event' => 'event',
            'eventName' => $this->getTwitterEventId(\WeltPixel\GA4\Model\Config\Source\XPixel\TrackingEvents::EVENT_ADD_TO_CART),
            'eventData' => [
                'value' => $productPrice,
                'currency' => $this->getCurrencyCode(),
                'contents' => [
                    [
                        'content_id' => $this->getXProductId($product),
                        'content_name' => $productName,
                        'content_price' => $productPrice,
                        'num_items' => $qty
                    ]
                ],
                'description' => 'Added ' . $productName . ' to cart',
                'conversion_id' => $this->getAddToCartEventConversionID()
            ]
        ];

        return $result;
    }


    /**
     * @return boolean
     */
    public function isServerSideTrackingEnabled()
    {
        if (!$this->_moduleManager->isEnabled('WeltPixel_GA4TwitterSS')) {
            return false;
        }
        $isServerSideTrackingEnabled = $this->_xPixelOptions['serverside_tracking']['enable'];
        if (empty($isServerSideTrackingEnabled)) {
            return false;
        }
        return $isServerSideTrackingEnabled;
    }

    /**
     * @return boolean
     */
    public function enableXPixelFrontendEventSending()
    {
        return $this->_xPixelOptions['serverside_tracking']['enable_frontend_event_sending'] ?? true;
    }

    /**
     * Get Twitter ID for a specific event
     *
     * @param string $eventName
     * @return string|null
     */
    public function getTwitterEventId($eventName)
    {
        if (!isset($this->_xPixelOptions['general_tracking']['twitter_events'])) {
            return null;
        }

        $twitterEvents = $this->serializer->unserialize($this->_xPixelOptions['general_tracking']['twitter_events']);
        if (!is_array($twitterEvents)) {
            return null;
        }

        foreach ($twitterEvents as $event) {
            if ($event['event'] == $eventName && !empty($event['twitter_id'])) {
                return $event['twitter_id'];
            }
        }

        return null;
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function shouldServerSidePurchaseEventBeTracked($storeId)
    {
        $this->reloadConfigOptions($storeId);
        return $this->isServerSideTrackingEnabled() && $this->shouldXServerSideEventBeTracked(\WeltPixel\GA4\Model\Config\Source\XPixel\TrackingEvents::EVENT_PURCHASE);
    }

    /**
     * @param $storeId
     * @return void
     */
    public function reloadConfigOptions($storeId) {
        $this->_xPixelOptions = $this->scopeConfig->getValue('weltpixel_ga4_x_pixel', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }


    /**
     * @return array
     */
    public function getXServerSideTrackedEvents() {
        $trackedEvents = $this->_xPixelOptions['serverside_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldXServerSideEventBeTracked($eventName) {
        $availableEvents = $this->getXServerSideTrackedEvents();
        return in_array($eventName, $availableEvents);
    }

    public function getXPixelId()
    {
        return trim($this->_xPixelOptions['serverside_tracking']['pixel_id'] ?? '');
    }

    /**
     * @return string
     */
    public function getXConsumerApiKey() {
        return trim($this->_xPixelOptions['serverside_tracking']['consumer_api_key'] ?? '');
    }

    /**
     * @return string
     */
    public function getXConsumerApiSecret() {
        return trim($this->_xPixelOptions['serverside_tracking']['consumer_api_secret'] ?? '');
    }

    /**
     * @return string
     */
    public function getXAuthenticationAccessToken() {
        return trim($this->_xPixelOptions['serverside_tracking']['authentication_access_token'] ?? '');
    }

    /**
     * @return string
     */
    public function getXAuthenticationAccessSecret() {
        return trim($this->_xPixelOptions['serverside_tracking']['authentication_access_secret'] ?? '');
    }

    /**
     * @return bool
     */
    public function isEnabledFileLog() {
        return (boolean) ($this->_xPixelOptions['serverside_tracking']['enable_file_log'] ?? false);
    }

    /**
     * @return string
     */
    public function getTwitterSSTrackUrl()
    {
        return $this->_getUrl('wpx_twitter/pixel/tracker');
    }

    /**
     * @return bool
     */
    public function getTrackSpecificCustomerGroups()
    {
        return (boolean)($this->_xPixelOptions['serverside_tracking']['track_specific_customer_groups'] ?? false);
    }

    /**
     * @return array
     */
    public function getAllowedCustomerGroups()
    {
        $groups = $this->_xPixelOptions['serverside_tracking']['allowed_customer_groups'] ?? '';
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
        return $this->_xPixelOptions['grand_total_calculation']['order_total_calculation'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonTaxFromTransaction()
    {
        return $this->_xPixelOptions['grand_total_calculation']['exclude_tax_from_transaction'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonShippingFromTransaction()
    {
        return $this->_xPixelOptions['grand_total_calculation']['exclude_shipping_from_transaction'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonShippingFromTransactionIncludingTax()
    {
        return $this->_xPixelOptions['grand_total_calculation']['exclude_shipping_from_transaction_including_tax'];
    }

}
