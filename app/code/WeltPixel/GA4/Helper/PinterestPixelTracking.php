<?php

namespace WeltPixel\GA4\Helper;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PinterestPixelTracking extends Data
{
    const ADDON_TYPE_NAME = 'pinterest';

    /**
     * @return boolean
     */
    public function isPinterestPixelTrackingEnabled() {
        return $this->_pinterestPixelOptions['general_tracking']['enable'];
    }

    /**
     * @return string
     */
    public function getPinterestPixelCodeSnippet() {
        $codeSnippet = trim($this->_pinterestPixelOptions['general_tracking']['code_snippet'] ?? '');
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
    public function getPinterestPixelTrackedEvents() {
        $trackedEvents = $this->_pinterestPixelOptions['general_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldPinterestPixelEventBeTracked($eventName) {
        $enableFrontendEventSending = true;
        $serverSideTrackingEnabled = $this->isServerSideTrackingEnabled();

        if ($serverSideTrackingEnabled) {
            $enableFrontendEventSending = $this->enablePinterestPixelFrontendEventSending();
        }

        $availableEvents = $this->getPinterestPixelTrackedEvents();
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
     * @param $product
     * @param int $qty
     * @return array
     */
    public function pinterestPixelAddToCartPushData($product, $qty = 1)
    {
        $result = [
            'track' => 'track',
            'eventName' => \WeltPixel\GA4\Model\Config\Source\PinterestPixel\TrackingEvents::EVENT_ADD_TO_CART,
            'eventData' => [
                'value' => floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', '')),
                'currency' => $this->getCurrencyCode(),
                'event_id' => $this->getAddToWishlistEventUID(),
                'order_quantity' => $qty,
                'line_items' => [
                    [
                        'product_id' => $this->getPinterestProductId($product),
                        'product_name' => addslashes(str_replace('"','&quot;',html_entity_decode($product->getName() ?? ''))),
                        'product_category' => addslashes(str_replace('"','&quot;',$this->getContentCategory($product->getCategoryIds()))),
                        'product_price' => floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', '')),
                        'product_quantity' => $qty
                    ]
                ]
            ]
        ];

        return $result;
    }

    /**
     * @param $product
     * @return array
     */
    public function pinterestPixelAddToWishlistPushData($product)
    {
        $result = [
            'track' => 'track',
            'eventName' => \WeltPixel\GA4\Model\Config\Source\PinterestPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST,
            'eventData' => [
                'value' => floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', '')),
                'currency' => $this->getCurrencyCode(),
                'event_id' => $this->getAddToWishlistEventUID(),
                'line_items' => [
                    [
                        'product_id' => $this->getPinterestProductId($product),
                        'product_name' => addslashes(str_replace('"','&quot;',html_entity_decode($product->getName() ?? ''))),
                        'product_category' => addslashes(str_replace('"','&quot;',$this->getContentCategory($product->getCategoryIds()))),
                        'product_price' => floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', '')),
                        'product_quantity' => 1
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
    public function getPinterestProductId($product)
    {
        $idOption = $this->_pinterestPixelOptions['general_tracking']['id_selection'];
        $pinterestProductId = '';

        switch ($idOption) {
            case 'sku':
                $pinterestProductId = $product->getData('sku');
                break;
            case 'id':
            default:
                $pinterestProductId = $product->getId();
                if ($product instanceof \Magento\Sales\Model\Order\Item) {
                    $pinterestProductId = $product->getProductId();
                }
                break;
        }

        return $pinterestProductId;
    }

    /**
     * @return string
     */
    public function getEventUID()
    {
        $randomString = [];
        for ($i=1; $i<3; $i++) {
            $randomString[] = substr(hash('md5', random_int(0, getrandmax())), 0, 8);
        }

        return implode('-', $randomString);
    }

    /**
     * @return string
     */
    public function getJsEventIdGenerator()
    {
        return "Array(2).fill(0).map(() => Math.random().toString(16).substring(2, 10)).join('-')";
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function shouldServerSidePurchaseEventBeTracked($storeId)
    {
        $this->reloadConfigOptions($storeId);
        return $this->isServerSideTrackingEnabled() && $this->shouldPinterestServerSideEventBeTracked(\WeltPixel\GA4\Model\Config\Source\PinterestPixel\TrackingEvents::EVENT_PURCHASE);
    }

    /**
     * @param $storeId
     * @return void
     */
    public function reloadConfigOptions($storeId) {
        $this->_pinterestPixelOptions = $this->scopeConfig->getValue('weltpixel_ga4_pinterest_pixel', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return boolean
     */
    public function isServerSideTrackingEnabled()
    {
        if (!$this->_moduleManager->isEnabled('WeltPixel_GA4PinterestSS')) {
            return false;
        }
        $isServerSideTrackingEnabled = $this->_pinterestPixelOptions['serverside_tracking']['enable'];
        if (empty($isServerSideTrackingEnabled)) {
            return false;
        }
        return $isServerSideTrackingEnabled;
    }

    /**
     * @return boolean
     */
    public function enablePinterestPixelFrontendEventSending()
    {
        return $this->_pinterestPixelOptions['serverside_tracking']['enable_frontend_event_sending'] ?? true;
    }

    /**
     * @return array
     */
    public function getPinterestServerSideTrackedEvents() {
        $trackedEvents = $this->_pinterestPixelOptions['serverside_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldPinterestServerSideEventBeTracked($eventName) {
        $availableEvents = $this->getPinterestServerSideTrackedEvents();
        return in_array($eventName, $availableEvents);
    }

    /**
     * @return string
     */
    public function getPinterestAccountId() {
        return trim($this->_pinterestPixelOptions['serverside_tracking']['pinterest_account_id'] ?? '');
    }

    /**
     * @return boolean
     */
    public function isPinterestPixelTestModeEnabled()
    {
        return $this->_pinterestPixelOptions['serverside_tracking']['enable_test_mode'] ?? false;
    }

    /**
     * @return string
     */
    public function getPinterestPixelAccessToken() {
        return trim($this->_pinterestPixelOptions['serverside_tracking']['conversion_api_access_token'] ?? '');
    }

    /**
     * @return bool
     */
    public function isEnabledFileLog() {
        return (boolean) ($this->_pinterestPixelOptions['serverside_tracking']['enable_file_log'] ?? false);
    }

    /**
     * @return string
     */
    public function getPinterestSSTrackUrl()
    {
        return $this->_getUrl('wpx_pinterest/pixel/tracker');
    }

    /**
     * @return mixed
     */
    public function getStoreCurrenUrl()
    {
        $currentUrl = $this->storeManager->getStore()->getCurrentUrl();
        return $currentUrl;
    }

    /**
     * @return string
     */
    public function getAddToWishlistEventUID() {
        if (!$this->registry->registry('pinterestss_add_to_wishlist_event_uid')) {
            $this->registry->register('pinterestss_add_to_wishlist_event_uid', $this->getEventUID());
        }

        return $this->registry->registry('pinterestss_add_to_wishlist_event_uid');
    }

    /**
     * @return string
     */
    public function getAddToCartEventUID() {
        if (!$this->registry->registry('pinterestss_add_to_cart_event_uid')) {
           $this->registry->register('pinterestss_add_to_cart_event_uid', $this->getEventUID());
        }

        return $this->registry->registry('pinterestss_add_to_cart_event_uid');
    }

    /**
     * @return string
     */
    public function getSignUpEventUID() {
        if (!$this->registry->registry('pinterestss_signup_event_uid')) {
            $this->registry->register('pinterestss_signup_event_uid', $this->getEventUID());
        }

        return $this->registry->registry('pinterestss_signup_event_uid');
    }

    /**
     * @return bool
     */
    public function getTrackSpecificCustomerGroups()
    {
        return (boolean)($this->_pinterestPixelOptions['serverside_tracking']['track_specific_customer_groups'] ?? false);
    }

    /**
     * @return array
     */
    public function getAllowedCustomerGroups()
    {
        $groups = $this->_pinterestPixelOptions['serverside_tracking']['allowed_customer_groups'] ?? '';
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
        return $this->_pinterestPixelOptions['grand_total_calculation']['order_total_calculation'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonTaxFromTransaction()
    {
        return $this->_pinterestPixelOptions['grand_total_calculation']['exclude_tax_from_transaction'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonShippingFromTransaction()
    {
        return $this->_pinterestPixelOptions['grand_total_calculation']['exclude_shipping_from_transaction'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonShippingFromTransactionIncludingTax()
    {
        return $this->_pinterestPixelOptions['grand_total_calculation']['exclude_shipping_from_transaction_including_tax'];
    }

    /**
     * @return bool
     */
    public function sendToMultipleEndpoints()
    {
        return (boolean) ($this->_pinterestPixelOptions['serverside_tracking']['send_to_multiple_endpoints'] ?? false);
    }

    /**
     * @return array
     */
    public function getMultipleEndpointsConfiguration()
    {
        $multipleEndpointsConfiguration = $this->_pinterestPixelOptions['serverside_tracking']['multiple_endpoints_configuration'];
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
