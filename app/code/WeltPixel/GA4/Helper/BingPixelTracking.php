<?php
namespace WeltPixel\GA4\Helper;

use Magento\Store\Model\ScopeInterface;

class BingPixelTracking extends Data
{
    const ADDON_TYPE_NAME = 'bing';

    /**
     * @return boolean
     */
    public function isBingPixelTrackingEnabled() {
        return $this->_bingPixelOptions['general_tracking']['enable'];
    }


    /**
     * @return string
     */
    public function getBingPixelTrackingCodeSnippet() {
        $codeSnippet = trim($this->_bingPixelOptions['general_tracking']['code_snippet'] ?? '');
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
    public function getBingPixelTrackedEvents() {
        $trackedEvents = $this->_bingPixelOptions['general_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }


    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldBingPixelEventBeTracked($eventName) {
        $enableFrontendEventSending = true;
        $serverSideTrackingEnabled = $this->isServerSideTrackingEnabled();

        if ($serverSideTrackingEnabled) {
            $enableFrontendEventSending = $this->enableBingPixelFrontendEventSending();
        }

        $availableEvents = $this->getBingPixelTrackedEvents();
        return in_array($eventName, $availableEvents) && $enableFrontendEventSending;
    }

    /**
     * Returns the product id or sku based on the backend settings
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getBingProductId($product)
    {
        $idOption = $this->_bingPixelOptions['general_tracking']['id_selection'];
        $bingProductId = '';

        switch ($idOption) {
            case 'sku':
                $bingProductId = $product->getData('sku');
                break;
            case 'id':
            default:
                $bingProductId = $product->getId();
                if ($product instanceof \Magento\Sales\Model\Order\Item) {
                    $bingProductId = $product->getProductId();
                }
                break;
        }

        return $bingProductId;
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
    public function getEventMID()
    {
        $hex = bin2hex(random_bytes(16));
        return sprintf('%08s-%04s-%04s-%04s-%012s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20)
        );
    }

    /**
     * @return string
     */
    public function getJsEventIdGenerator()
    {
        return "([1e7]+-1e3+-4e3+-8e3+-1e11).replace(/[018]/g, c => (c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> c / 4).toString(16))";
    }

    /**
     * @return string
     */
    public function getAddToWishlistEventMID() {
        if (!$this->registry->registry('bingss_add_to_wishlist_event_uid')) {
            $this->registry->register('bingss_add_to_wishlist_event_uid', $this->getEventMID());
        }

        return $this->registry->registry('bingss_add_to_wishlist_event_uid');
    }

    /**
     * @return string
     */
    public function getAddToCartEventMID() {
        if (!$this->registry->registry('bingss_add_to_cart_event_uid')) {
            $this->registry->register('bingss_add_to_cart_event_uid', $this->getEventMID());
        }

        return $this->registry->registry('bingss_add_to_cart_event_uid');
    }

    /**
     * @return string
     */
    public function getSignUpEventMID() {
        if (!$this->registry->registry('bingss_signup_event_uid')) {
            $this->registry->register('bingss_signup_event_uid', $this->getEventMID());
        }

        return $this->registry->registry('bingss_signup_event_uid');
    }

    /**
     * @param $product
     * @return array
     */
    public function bingPixelAddToWishlistPushData($product)
    {
        $productPrice = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));
        $result = [
            'event' => 'event',
            'eventName' => \WeltPixel\GA4\Model\Config\Source\BingPixel\TrackingEvents::EVENT_ADD_TO_WISHLIST,
            'eventData' => [
                'currency' => $this->getCurrencyCode(),
                'ecomm_prodid' => [$this->getBingProductId($product)],
                'ecomm_pagetype' => 'product',
                'ecomm_totalvalue' => $productPrice,
                'items' => [
                    [
                        'id' => $this->getBingProductId($product),
                        'name' => addslashes(str_replace('"','&quot;',html_entity_decode($product->getName() ?? ''))),
                        'price' => $productPrice,
                        'category' => addslashes(str_replace('"','&quot;',$this->getContentCategory($product->getCategoryIds()))),
                        'quantity' => 1
                    ]
                ],
                'custom_parameters' => [
                    'mid' => $this->getAddToWishlistEventMID()
                ],
                'mid' => $this->getAddToWishlistEventMID()
            ]
        ];

        return $result;
    }

    /**
     * @param $product
     * @param int $qty
     * @return array
     */
    public function bingPixelAddToCartPushData($product, $qty = 1)
    {
        $productPrice = floatval(number_format($product->getPriceInfo()->getPrice('final_price')->getValue(), 2, '.', ''));
        $result = [
            'event' => 'event',
            'eventName' => \WeltPixel\GA4\Model\Config\Source\BingPixel\TrackingEvents::EVENT_ADD_TO_CART,
            'eventData' => [
                'currency' => $this->getCurrencyCode(),
                'ecomm_prodid' => [$this->getBingProductId($product)],
                'ecomm_pagetype' => 'cart',
                'ecomm_totalvalue' => $productPrice,
                'items' => [
                    [
                        'id' => $this->getBingProductId($product),
                        'name' => addslashes(str_replace('"','&quot;',html_entity_decode($product->getName() ?? ''))),
                        'price' => $productPrice,
                        'category' => addslashes(str_replace('"','&quot;',$this->getContentCategory($product->getCategoryIds()))),
                        'quantity' => $qty
                    ]
                ],
                'custom_parameters' => [
                    'mid' => $this->getAddToCartEventMID()
                ],
                'mid' => $this->getAddToCartEventMID()
            ]
        ];

        return $result;
    }


    /**
     * @return boolean
     */
    public function isServerSideTrackingEnabled()
    {
        if (!$this->_moduleManager->isEnabled('WeltPixel_GA4BingSS')) {
            return false;
        }
        $isServerSideTrackingEnabled = $this->_bingPixelOptions['serverside_tracking']['enable'];
        if (empty($isServerSideTrackingEnabled)) {
            return false;
        }
        return $isServerSideTrackingEnabled;
    }

    /**
     * @return boolean
     */
    public function enableBingPixelFrontendEventSending()
    {
        return $this->_bingPixelOptions['serverside_tracking']['enable_frontend_event_sending'] ?? true;
    }

    /**
     * @param $storeId
     * @return bool
     */
    public function shouldServerSidePurchaseEventBeTracked($storeId)
    {
        $this->reloadConfigOptions($storeId);
        return $this->isServerSideTrackingEnabled() && $this->shouldBingServerSideEventBeTracked(\WeltPixel\GA4\Model\Config\Source\BingPixel\TrackingEvents::EVENT_PURCHASE);
    }

    /**
     * @param $storeId
     * @return void
     */
    public function reloadConfigOptions($storeId) {
        $this->_bingPixelOptions = $this->scopeConfig->getValue('weltpixel_ga4_bing_pixel', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }


    /**
     * @return array
     */
    public function getBingServerSideTrackedEvents() {
        $trackedEvents = $this->_bingPixelOptions['serverside_tracking']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param string $eventName
     * @return bool
     */
    public function shouldBingServerSideEventBeTracked($eventName) {
        $availableEvents = $this->getBingServerSideTrackedEvents();
        return in_array($eventName, $availableEvents);
    }

    public function getBingUetTagID()
    {
        return trim($this->_bingPixelOptions['serverside_tracking']['bing_uet_tag_id'] ?? '');
    }

    /**
     * @return bool
     */
    public function isEnabledFileLog() {
        return (boolean) ($this->_bingPixelOptions['serverside_tracking']['enable_file_log'] ?? false);
    }

    /**
     * @return string
     */
    public function getBingSSTrackUrl()
    {
        return $this->_getUrl('wpx_bing/pixel/tracker');
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
        return (boolean)($this->_bingPixelOptions['serverside_tracking']['track_specific_customer_groups'] ?? false);
    }

    /**
     * @return array
     */
    public function getAllowedCustomerGroups()
    {
        $groups = $this->_bingPixelOptions['serverside_tracking']['allowed_customer_groups'] ?? '';
        if (empty($groups)) {
            return [];
        }
        if (is_array($groups)) {
            return $groups;
        }
        return explode(',', $groups);
    }

    /**
     * @return bool
     */
    public function sendToMultipleEndpoints()
    {
        return (boolean) ($this->_bingPixelOptions['serverside_tracking']['send_to_multiple_endpoints'] ?? false);
    }

    /**
     * @return array
     */
    public function getMultipleEndpointsConfiguration()
    {
        $multipleEndpointsConfiguration = $this->_bingPixelOptions['serverside_tracking']['multiple_endpoints_configuration'];
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
