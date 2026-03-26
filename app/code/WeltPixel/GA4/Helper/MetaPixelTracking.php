<?php

namespace WeltPixel\GA4\Helper;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MetaPixelTracking extends Data
{
    const ADDON_TYPE_NAME = 'meta';

    /**
     * @return boolean
     */
    public function isMetaPixelTrackingEnabled() {
        return $this->_metaPixelOptions['general_tracking']['enable'];
    }

    /**
     * @return string
     */
    public function getMetaPixelCodeSnippet() {
        $codeSnippet = trim($this->_metaPixelOptions['general_tracking']['code_snippet'] ?? '');
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
    public function getMetaPixelTrackedEvents() {
        $trackedEvents = $this->_metaPixelOptions['general_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldMetaPixelEventBeTracked($eventName) {
        $enableFrontendEventSending = true;
        $serverSideTrackingEnabled = $this->isServerSideTrackingEnabled();

        if ($serverSideTrackingEnabled) {
            $enableFrontendEventSending = $this->enableMetaPixelFrontendEventSending();
        }

        $availableEvents = $this->getMetaPixelTrackedEvents();
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
     * @return string
     */
    public function getProductType($product)
    {
        $parentProductTypes = [
            \Magento\GroupedProduct\Model\Product\Type\Grouped::TYPE_CODE,
            \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE,
            \Magento\Bundle\Model\Product\Type::TYPE_CODE
        ];

        if (in_array($product->getTypeId(), $parentProductTypes)) {
            return 'product_group';
        }

        return 'product';
    }


    /**
     * @param $product
     * @param int $qty
     * @return array
     */
    public function metaPixelAddToCartPushData($product, $qty = 1)
    {
        $result = [
            'track' => 'track',
            'eventName' => 'AddToCart',
            'eventData' => [],
            'additionalParams' => [
                'eventID' => $this->getAddToCartEventUID()
            ]
        ];

        $productId = $this->getMetaProductId($product);
        $productCategoryIds = $product->getCategoryIds();

        $result['eventData']['content_type'] = $this->getProductType($product);
        $result['eventData']['quantity'] = $qty;
        $result['eventData']['currency'] = $this->getCurrencyCode();
        $result['eventData']['content_ids'] = [$productId];
        $result['eventData']['content_name'] = addslashes(str_replace('"','&quot;', html_entity_decode($product->getName() ?? '')));
        $result['eventData']['content_category'] = addslashes(str_replace('"','&quot;',$this->getContentCategory($productCategoryIds)));
        $result['eventData']['value'] = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));

        $fbc = $this->cookieManager->getCookie('_fbc', false);
        if ($fbc) {
            $result['eventData']['fbc'] = $this->cookieManager->getCookie('_fbc');
        }
        $fbc = $this->cookieManager->getCookie('_fbp', false);
        if ($fbc) {
            $result['eventData']['fbp'] = $this->cookieManager->getCookie('_fbp');
        }

        return $result;
    }

    /**
     * @param $product
     * @return array
     */
    public function metaPixelAddToWishlistPushData($product)
    {
        $result = [
            'track' => 'track',
            'eventName' => 'AddToWishlist',
            'eventData' => [],
            'additionalParams' => [
                'eventID' => $this->getAddToWishlistEventUID()
            ]
        ];

        $productId = $this->getMetaProductId($product);
        $productCategoryIds = $product->getCategoryIds();

        $result['eventData']['content_type'] = $this->getProductType($product);
        $result['eventData']['currency'] = $this->getCurrencyCode();
        $result['eventData']['content_ids'] = [$productId];
        $result['eventData']['content_name'] = addslashes(str_replace('"','&quot;', html_entity_decode($product->getName() ?? '')));
        $result['eventData']['content_category'] = addslashes(str_replace('"','&quot;', $this->getContentCategory($productCategoryIds)));
        $result['eventData']['value'] = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));

        $fbc = $this->cookieManager->getCookie('_fbc', false);
        if ($fbc) {
            $result['eventData']['fbc'] = $this->cookieManager->getCookie('_fbc');
        }
        $fbc = $this->cookieManager->getCookie('_fbp', false);
        if ($fbc) {
            $result['eventData']['fbp'] = $this->cookieManager->getCookie('_fbp');
        }


        return $result;
    }

    /**
     * Returns the product id or sku based on the backend settings
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getMetaProductId($product)
    {
        $idOption = $this->_metaPixelOptions['general_tracking']['id_selection'];
        $metaProductId = '';

        switch ($idOption) {
            case 'sku':
                $metaProductId = $product->getData('sku');
                break;
            case 'id':
            default:
                $metaProductId = $product->getId();
                if ($product instanceof \Magento\Sales\Model\Order\Item) {
                    $metaProductId = $product->getProductId();
                }
                break;
        }

        return $metaProductId;
    }

    /**
     * @return string
     */
    public function getEventUID()
    {
        $randomString = [];
        for ($i=1; $i<5; $i++) {
            $randomString[] = substr(hash('md5', random_int(0, getrandmax())), 0, 8);
        }

        return implode('-', $randomString);
    }

    /**
     * @return string
     */
    public function getJsEventIdGenerator()
    {
        return "Array(4).fill(0).map(() => Math.random().toString(16).substring(2, 10)).join('-')";
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function shouldServerSidePurchaseEventBeTracked($storeId)
    {
        $this->reloadConfigOptions($storeId);
        return $this->isServerSideTrackingEnabled() && $this->shouldMetaServerSideEventBeTracked(\WeltPixel\GA4\Model\Config\Source\MetaPixel\TrackingEvents::EVENT_PURCHASE);
    }

    /**
     * @param $storeId
     * @return void
     */
    public function reloadConfigOptions($storeId) {
        $this->_metaPixelOptions = $this->scopeConfig->getValue('weltpixel_ga4_meta_pixel', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return boolean
     */
    public function isServerSideTrackingEnabled()
    {
        if (!$this->_moduleManager->isEnabled('WeltPixel_GA4MetaSS')) {
            return false;
        }
        $isServerSideTrackingEnabled = $this->_metaPixelOptions['serverside_tracking']['enable'];
        if (empty($isServerSideTrackingEnabled)) {
            return false;
        }
        return $isServerSideTrackingEnabled;
    }

    /**
     * @return boolean
     */
    public function enableMetaPixelFrontendEventSending()
    {
        return $this->_metaPixelOptions['serverside_tracking']['enable_frontend_event_sending'] ?? true;
    }

    /**
     * @return array
     */
    public function getMetaServerSideTrackedEvents() {
        $trackedEvents = $this->_metaPixelOptions['serverside_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldMetaServerSideEventBeTracked($eventName) {
        $availableEvents = $this->getMetaServerSideTrackedEvents();
        return in_array($eventName, $availableEvents);
    }

    /**
     * @return string
     */
    public function getMetaPixelId() {
        return trim($this->_metaPixelOptions['serverside_tracking']['metapixel_id'] ?? '');
    }

    /**
     * @return boolean
     */
    public function isMetaPixelTestModeEnabled()
    {
        return $this->_metaPixelOptions['serverside_tracking']['enable_test_mode'] ?? false;
    }

    /**
     * @return string
     */
    public function getMetaPixelTestEventCode() {
        return trim($this->_metaPixelOptions['serverside_tracking']['test_event_code'] ?? '');
    }

    /**
     * @return string
     */
    public function getMetaPixelConversionApiKey() {
        return trim($this->_metaPixelOptions['serverside_tracking']['conversion_api_access_key'] ?? '');
    }

    /**
     * @return bool
     */
    public function isEnabledFileLog() {
        return (boolean) ($this->_metaPixelOptions['serverside_tracking']['enable_file_log'] ?? false);
    }

    /**
     * @return string
     */
    public function getMetaSSTrackUrl()
    {
        return $this->_getUrl('wpx_meta/pixel/tracker');
    }

    /**
     * @return mixed
     */
    public function getStoreCurrenUrl()
    {
        $currentUrl = $this->storeManager->getStore()->getCurrentUrl();
        return $this->removeQueryString($currentUrl);
    }

    /**
     * @param $url
     * @return string
     */
    protected function removeQueryString($url) {
        $parsedUrl = parse_url($url);
        $cleanUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];

        if (isset($parsedUrl['port'])) {
            $cleanUrl .= ':' . $parsedUrl['port'];
        }

        if (isset($parsedUrl['path'])) {
            $cleanUrl .= $parsedUrl['path'];
        }

        return $cleanUrl;
    }

    /**
     * @return string
     */
    public function getAddToWishlistEventUID() {
        if (!$this->registry->registry('metass_add_to_wishlist_event_uid')) {
            $this->registry->register('metass_add_to_wishlist_event_uid', $this->getEventUID());
        }

        return $this->registry->registry('metass_add_to_wishlist_event_uid');
    }

    /**
     * @return string
     */
    public function getAddToCartEventUID() {
        if (!$this->registry->registry('metass_add_to_cart_event_uid')) {
           $this->registry->register('metass_add_to_cart_event_uid', $this->getEventUID());
        }

        return $this->registry->registry('metass_add_to_cart_event_uid');
    }

    /**
     * @return bool
     */
    public function getTrackSpecificCustomerGroups()
    {
        return (boolean)($this->_metaPixelOptions['serverside_tracking']['track_specific_customer_groups'] ?? false);
    }

    /**
     * @return array
     */
    public function getAllowedCustomerGroups()
    {
        $groups = $this->_metaPixelOptions['serverside_tracking']['allowed_customer_groups'] ?? '';
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
        return $this->_metaPixelOptions['grand_total_calculation']['order_total_calculation'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonTaxFromTransaction()
    {
        return $this->_metaPixelOptions['grand_total_calculation']['exclude_tax_from_transaction'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonShippingFromTransaction()
    {
        return $this->_metaPixelOptions['grand_total_calculation']['exclude_shipping_from_transaction'];
    }

    /**
     * @return boolean
     */
    public function excludeAddonShippingFromTransactionIncludingTax()
    {
        return $this->_metaPixelOptions['grand_total_calculation']['exclude_shipping_from_transaction_including_tax'];
    }

    /**
     * @return bool
     */
    public function sendToMultipleEndpoints()
    {
        return (boolean) ($this->_metaPixelOptions['serverside_tracking']['send_to_multiple_endpoints'] ?? false);
    }

    /**
     * @return array
     */
    public function getMultipleEndpointsConfiguration()
    {
        $multipleEndpointsConfiguration = $this->_metaPixelOptions['serverside_tracking']['multiple_endpoints_configuration'];
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
