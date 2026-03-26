<?php

namespace WeltPixel\GA4\Helper;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RedditPixelTracking extends Data
{
    const ADDON_TYPE_NAME = 'reddit';

    /**
     * @return boolean
     */
    public function isRedditPixelTrackingEnabled() {
        return $this->_redditPixelOptions['general_tracking']['enable'];
    }

    /**
     * @return string
     */
    public function getRedditPixelCodeSnippet() {
        $codeSnippet = trim($this->_redditPixelOptions['general_tracking']['code_snippet'] ?? '');
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
    public function getRedditPixelTrackedEvents() {
        $trackedEvents = $this->_redditPixelOptions['general_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldRedditPixelEventBeTracked($eventName) {
        $enableFrontendEventSending = true;
        $serverSideTrackingEnabled = $this->isServerSideTrackingEnabled();

        if ($serverSideTrackingEnabled) {
            $enableFrontendEventSending = $this->enableRedditPixelFrontendEventSending();
        }

        $availableEvents = $this->getRedditPixelTrackedEvents();
        return in_array($eventName, $availableEvents) && $enableFrontendEventSending;
    }

    /**
     * @param $product
     * @return array
     */
    public function redditPixelAddToWishlistPushData($product)
    {
        $result = [
            'track' => 'track',
            'eventName' => \WeltPixel\GA4\Model\Config\Source\RedditPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST,
            'eventData' => [
                'value' => floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', '')),
                'currency' => $this->getCurrencyCode(),
                'conversionId' => $this->getAddToWishlistEventConversionID(),
                'products' => [
                    [
                        'id' => $this->getRedditProductId($product),
                        'name' => addslashes(str_replace('"','&quot;',html_entity_decode($product->getName() ?? ''))),
                        'category' => addslashes(str_replace('"','&quot;',$this->getContentCategory($product->getCategoryIds())))
                    ]
                ]
            ]
        ];

        return $result;
    }

    /**
     * @param $product
     * @param int $qty
     * @return array
     */
    public function redditPixelAddToCartPushData($product, $qty = 1)
    {
        $result = [
            'track' => 'track',
            'eventName' => \WeltPixel\GA4\Model\Config\Source\RedditPixel\TrackingEvents::EVENT_ADD_TO_CART,
            'eventData' => [
                'value' => floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', '')),
                'currency' => $this->getCurrencyCode(),
                'itemCount' => $qty,
                'conversionId' => $this->getAddToCartEventConversionID(),
                'products' => [
                    [
                        'id' => $this->getRedditProductId($product),
                        'name' => addslashes(str_replace('"','&quot;',html_entity_decode($product->getName() ?? ''))),
                        'category' => addslashes(str_replace('"','&quot;',$this->getContentCategory($product->getCategoryIds())))
                    ]
                ]
            ]
        ];

        return $result;
    }

    /**
     * Returns the product id or sku based on the backend settings
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getRedditProductId($product)
    {
        $idOption = $this->_redditPixelOptions['general_tracking']['id_selection'];
        $redditProductId = '';

        switch ($idOption) {
            case 'sku':
                $redditProductId = $product->getData('sku');
                break;
            case 'id':
            default:
                $redditProductId = $product->getId();
                if ($product instanceof \Magento\Sales\Model\Order\Item) {
                    $redditProductId = $product->getProductId();
                }
                break;
        }

        return $redditProductId;
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
     * @return string
     */
    public function getEventConversionID()
    {
        $randomString = [];
        for ($i=1; $i<8; $i++) {
            $randomString[] =  substr(hash('md5', random_int(0, getrandmax())), 0, 8);
        }

        return implode('', $randomString);
    }

    /**
     * @return string
     */
    public function getJsEventIdGenerator()
    {
        return "Array(7).fill(0).map(() => Math.random().toString(16).substring(2, 10)).join('')";
    }

    /**
     * @return string
     */
    public function getAddToWishlistEventConversionID() {
        if (!$this->registry->registry('redditkss_add_to_wishlist_event_uid')) {
            $this->registry->register('redditkss_add_to_wishlist_event_uid', $this->getEventConversionID());
        }

        return $this->registry->registry('redditkss_add_to_wishlist_event_uid');
    }

    /**
     * @return string
     */
    public function getAddToCartEventConversionID() {
        if (!$this->registry->registry('redditss_add_to_cart_event_uid')) {
            $this->registry->register('redditss_add_to_cart_event_uid', $this->getEventConversionID());
        }

        return $this->registry->registry('redditss_add_to_cart_event_uid');
    }

    /**
     * @return string
     */
    public function getSignUpEventConversionID() {
        if (!$this->registry->registry('redditss_signup_event_uid')) {
            $this->registry->register('redditss_signup_event_uid', $this->getEventConversionID());
        }

        return $this->registry->registry('redditss_signup_event_uid');
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function shouldServerSidePurchaseEventBeTracked($storeId)
    {
        $this->reloadConfigOptions($storeId);
        return $this->isServerSideTrackingEnabled() && $this->shouldRedditServerSideEventBeTracked(\WeltPixel\GA4\Model\Config\Source\RedditPixel\TrackingEvents::EVENT_PURCHASE);
    }

    /**
     * @param $storeId
     * @return void
     */
    public function reloadConfigOptions($storeId) {
        $this->_redditPixelOptions = $this->scopeConfig->getValue('weltpixel_ga4_reddit_pixel', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return boolean
     */
    public function isServerSideTrackingEnabled()
    {
        if (!$this->_moduleManager->isEnabled('WeltPixel_GA4RedditSS')) {
            return false;
        }
        $isServerSideTrackingEnabled = $this->_redditPixelOptions['serverside_tracking']['enable'];
        if (empty($isServerSideTrackingEnabled)) {
            return false;
        }
        return $isServerSideTrackingEnabled;
    }

    /**
     * @return boolean
     */
    public function enableRedditPixelFrontendEventSending()
    {
        return $this->_redditPixelOptions['serverside_tracking']['enable_frontend_event_sending'] ?? true;
    }

    /**
     * @return array
     */
    public function getRedditServerSideTrackedEvents() {
        $trackedEvents = $this->_redditPixelOptions['serverside_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldRedditServerSideEventBeTracked($eventName) {
        $availableEvents = $this->getRedditServerSideTrackedEvents();
        return in_array($eventName, $availableEvents);
    }

    /**
     * @return string
     */
    public function getRedditPixelId() {
        return trim($this->_redditPixelOptions['serverside_tracking']['reddit_pixel_id'] ?? '');
    }

    /**
     * @return string
     */
    public function getRedditPixelApiAccessToken() {
        return trim($this->_redditPixelOptions['serverside_tracking']['reddit_api_access_token'] ?? '');
    }

    /**
     * @return boolean
     */
    public function isRedditPixelTestModeEnabled()
    {
        return $this->_redditPixelOptions['serverside_tracking']['enable_test_mode'] ?? false;
    }

    /**
     * @return bool
     */
    public function isEnabledFileLog() {
        return (boolean) ($this->_redditPixelOptions['serverside_tracking']['enable_file_log'] ?? false);
    }

    /**
     * @return string
     */
    public function getRedditSSTrackUrl()
    {
        return $this->_getUrl('wpx_reddit/pixel/tracker');
    }

    /**
     * @return mixed
     */
    public function getStoreCurrenUrl()
    {
        return $this->storeManager->getStore()->getCurrentUrl(false);
    }

    /**
     * @return bool
     */
    public function getTrackSpecificCustomerGroups()
    {
        return (boolean)($this->_redditPixelOptions['serverside_tracking']['track_specific_customer_groups'] ?? false);
    }

    /**
     * @return array
     */
    public function getAllowedCustomerGroups()
    {
        $groups = $this->_redditPixelOptions['serverside_tracking']['allowed_customer_groups'] ?? '';
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
        return $this->_redditPixelOptions['grand_total_calculation']['order_total_calculation'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonTaxFromTransaction()
    {
        return $this->_redditPixelOptions['grand_total_calculation']['exclude_tax_from_transaction'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonShippingFromTransaction()
    {
        return $this->_redditPixelOptions['grand_total_calculation']['exclude_shipping_from_transaction'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonShippingFromTransactionIncludingTax()
    {
        return $this->_redditPixelOptions['grand_total_calculation']['exclude_shipping_from_transaction_including_tax'];
    }

    /**
     * @return bool
     */
    public function sendToMultipleEndpoints()
    {
        return (boolean) ($this->_redditPixelOptions['serverside_tracking']['send_to_multiple_endpoints'] ?? false);
    }

    /**
     * @return array
     */
    public function getMultipleEndpointsConfiguration()
    {
        $multipleEndpointsConfiguration = $this->_redditPixelOptions['serverside_tracking']['multiple_endpoints_configuration'];
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
