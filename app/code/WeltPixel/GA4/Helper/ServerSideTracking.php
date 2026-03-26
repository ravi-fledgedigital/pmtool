<?php

namespace WeltPixel\GA4\Helper;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ServerSideTracking extends Data
{
    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $currentOrder;

    /**
     * @return boolean
     */
    public function isServerSideTrakingEnabled()
    {
        return (boolean) ($this->_gtmOptions['serverside_measurement']['enable'] ?? false);
    }

    /**
     * @return string
     */
    public function getMeasurementId()
    {
        return trim($this->_gtmOptions['serverside_measurement']['measurement_id'] ?? '');
    }

    /**
     * @return string
     */
    public function getApiSecret()
    {
        return trim($this->_gtmOptions['serverside_measurement']['api_secret'] ?? '');
    }

    /**
     * @return bool
     */
    public function getDebugFileEnabled()
    {
        return (boolean) ($this->_gtmOptions['serverside_measurement']['enable_file_log'] ?? false);
    }

    /**
     * @return bool
     */
    public function getDebugCollectEnabled()
    {
        return (boolean) ($this->_gtmOptions['serverside_measurement']['enable_debug_collect'] ?? false);
    }

    /**
     * @return bool
     */
    public function useRedisCache()
    {
        return (boolean) ($this->_gtmOptions['serverside_measurement']['use_redis_cache'] ?? false);
    }

    /**
     * @return bool
     */
    public function sendToMultipleEndpoints()
    {
        return (boolean) ($this->_gtmOptions['serverside_measurement']['send_to_multiple_endpoints'] ?? false);
    }

    /**
     * @return array
     */
    public function getMultipleEndpointsConfiguration()
    {
        $multipleEndpointsConfiguration = $this->_gtmOptions['serverside_measurement']['multiple_endpoints_configuration'];
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

    /**
     * @return bool
     */
    public function isDataLayerEventDisabled()
    {
        return (boolean)($this->_gtmOptions['serverside_measurement']['disable_datalayer_events'] ?? false);
    }

    /**
     * @return bool
     */
    public function sendUserIdInEvents()
    {
        return (boolean)($this->_gtmOptions['serverside_measurement']['send_user_id'] ?? false);
    }

    /**
     * @return bool
     */
    public function sendUserProvidedData()
    {
        return (boolean)($this->_gtmOptions['serverside_measurement']['send_user_provided_data'] ?? false);
    }

    /**
     * @return array
     */
    public function getEnhancedConversionSelectedAttributes()
    {
        $attributes = $this->_gtmOptions['serverside_measurement']['enhanced_conversion_attributes'] ?? '';

        if (is_string($attributes)) {
            $attributes = strlen($attributes) ? explode(',', $attributes) : [];
        }

        if (!is_array($attributes)) {
            return [];
        }

        return array_filter(array_map('trim', $attributes));
    }

    /**
     * @return array
     */
    public function getEnhancedConversionEffectiveAttributes()
    {
        $availableAttributes = \WeltPixel\GA4\Model\Config\Source\EnhancedConversionAttributes::getAttributeKeys();

        if (!$this->isEnhancedConversionFilterEnabled()) {
            return $availableAttributes;
        }

        return array_values(array_intersect(
            $availableAttributes,
            $this->getEnhancedConversionSelectedAttributes()
        ));
    }

    /**
     * @return bool
     */
    public function isEnhancedConversionFilterEnabled()
    {
        return (boolean)($this->_gtmOptions['serverside_measurement']['enable_enhanced_conversion_filter'] ?? false);
    }

    /**
     * @return bool
     */
    public function enableUserPropertiesSending()
    {
        return (boolean)($this->_gtmOptions['serverside_measurement']['enable_user_properties_sending'] ?? false);
    }

    /**
     * @return bool
     */
    public function getTrackSpecificCustomerGroups()
    {
        return (boolean)($this->_gtmOptions['serverside_measurement']['track_specific_customer_groups'] ?? false);
    }

    /**
     * @return array
     */
    public function getAllowedCustomerGroups()
    {
        $groups = $this->_gtmOptions['serverside_measurement']['allowed_customer_groups'] ?? '';
        if (empty($groups)) {
            return [];
        }
        if (is_array($groups)) {
            return $groups;
        }
        return explode(',', $groups);
    }

    /**
     * @return array
     */
    public function getTrackedEvents()
    {
        $trackedEvents = $this->_gtmOptions['serverside_measurement']['events'] ?? '';
        return explode(',', $trackedEvents);
    }

    /**
     * @param $storeId
     * @return void
     */
    public function reloadConfigOptions($storeId)
    {
        $this->_gtmOptions = $this->scopeConfig->getValue('weltpixel_ga4', \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @return array
     */
    public function getEnabledUserProperties()
    {
        $enabledUsedProperties = $this->_gtmOptions['serverside_measurement']['user_properties'] ?? '';
        return explode(',', $enabledUsedProperties);
    }

    /**
     * @param $eventName
     * @return bool
     */
    public function shouldEventBeTracked($eventName)
    {
        $availableEvents = $this->getTrackedEvents();
        return in_array($eventName, $availableEvents);
    }

    /**
     * @deplacated
     * @return string
     */
    public function getCustomerSessionId()
    {
        $customerId = 'GUEST';
        $visitorData = $this->session->getVisitorData();
        if (isset($visitorData['visitor_id'])) {
            $customerId .= '_' . $visitorData['visitor_id'];
        }
        return $customerId;
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        $clientId = 'client_id';
        $gaCookie = $this->cookieManager->getCookie('_ga');
        if (isset($gaCookie) && strlen($gaCookie)) {
            $cookieExploded = explode('.', $gaCookie);
            if (isset($cookieExploded[2])) {
                $clientId = $cookieExploded[2];
            }
            if (isset($cookieExploded[3])) {
                $clientId .= '.' . $cookieExploded[3];
            }
        }

        return $clientId;
    }

    /**
     * @return mixed
     */
    public function getPageLocation($refererUrl = true)
    {
        if ($refererUrl) {
            return $this->redirect->getRefererUrl();
        }
        return $this->_request->getUriString();
    }

    /**
     * @return false|string
     */
    public function getGclid()
    {
        $gclAwCookie = $this->cookieManager->getCookie('_gcl_aw');
        if (isset($gclAwCookie) && strlen($gclAwCookie)) {
            $cookieExploded = explode('.', $gclAwCookie);
            if (count($cookieExploded) == 3 && isset($cookieExploded[0]) && $cookieExploded[0] === 'GCL') {
                // The GCLID is the last part after the last dot
                return end($cookieExploded);
            }
        }
        return false;
    }

    /**
     * @return array
     */
    public function getSessionIdAndTimeStamp()
    {
        $sessionId = false;
        $timestamp = false;

        $measurementId = $this->getMeasurementId();
        $gaMeasurementCookie = $this->cookieManager->getCookie('_ga_' . str_replace('G-', '', $measurementId));

        if (isset($gaMeasurementCookie) && strlen($gaMeasurementCookie)) {
            $cookieExploded = explode('.', $gaMeasurementCookie);
            $cookieVersion = $cookieExploded[0] ?? '';

            // GS1 format handling (backward compatibility)
            if ($cookieVersion === 'GS1') {
                if (isset($cookieExploded[2])) {
                    $sessionId = $cookieExploded[2];
                }
                if (isset($cookieExploded[5])) {
                    $timestamp = $cookieExploded[5] * 1000000;
                }
            }
            // GS2 format handling
            elseif ($cookieVersion === 'GS2') {
                if (isset($cookieExploded[2])) {
                    $segments = explode('$', $cookieExploded[2]);

                    // Extract session ID (s parameter)
                    foreach ($segments as $segment) {
                        if (strlen($segment) > 1) {
                            $prefix = $segment[0];
                            $value = substr($segment, 1);

                            // s prefix represents the session ID (timestamp)
                            if ($prefix === 's') {
                                $sessionId = $value;
                            }

                            // t prefix represents the last hit timestamp
                            if ($prefix === 't') {
                                $timestamp = $value * 1000000;
                            }
                        }
                    }
                }
            }
        }

        return [
            'session_id' => $sessionId,
            'timestamp' => $timestamp
        ];
    }

    /**
     * @return array|false
     */
    public function getUserProperties()
    {
        $enableUserPropertiesSending = $this->enableUserPropertiesSending();

        if (!$enableUserPropertiesSending) {
            return false;
        }

        $enabledUserProperties = $this->getEnabledUserProperties();
        if (!count($enabledUserProperties)) {
            return false;
        }

        $ua = $this->parseUserAgent();
        $userProperties = [];
        foreach ($enabledUserProperties as $property) {
            switch ($property) {
                case \WeltPixel\GA4\Model\Config\Source\ServerSide\UserProperties::USER_PROPERTY_BROWSER:
                    $userProperties[\WeltPixel\GA4\Model\Config\Source\ServerSide\UserProperties::USER_PROPERTY_BROWSER] = [
                        "value" => $ua['browser'] ?? ''
                    ];
                    break;
                case \WeltPixel\GA4\Model\Config\Source\ServerSide\UserProperties::USER_PROPERTY_BROWSER_VERSION:
                    $userProperties[\WeltPixel\GA4\Model\Config\Source\ServerSide\UserProperties::USER_PROPERTY_BROWSER_VERSION] = [
                        "value" =>  $ua['version'] ?? ''
                    ];
                    break;
                case \WeltPixel\GA4\Model\Config\Source\ServerSide\UserProperties::USER_PROPERTY_PLATFORM:
                    $userProperties[\WeltPixel\GA4\Model\Config\Source\ServerSide\UserProperties::USER_PROPERTY_PLATFORM] = [
                        "value" => $ua['platform'] ?? ''
                    ];
                    break;
                case \WeltPixel\GA4\Model\Config\Source\ServerSide\UserProperties::CUSTOMER_GROUP:
                    $userProperties[\WeltPixel\GA4\Model\Config\Source\ServerSide\UserProperties::CUSTOMER_GROUP] = [
                        "value" =>  $this->getCustomerGroup()
                    ];
                    break;
            }
        }

        return $userProperties;
    }

    /**
     * @return string
     */
    public function getCustomerGroup()
    {
        $customerGroup = 'NOT LOGGED IN';
        if ($this->customerSession->isLoggedIn()) {
            $customerGroupId = $this->customerSession->getCustomerGroupId();
            $groupObj = $this->groupRepository->getById($customerGroupId);
            $customerGroup = $groupObj->getCode();
        }

        return $customerGroup;
    }

    /**
     * @return array|null[]
     */
    public function parseUserAgent()
    {
        $u_agent = $this->_httpHeader->getHttpUserAgent();

        if ($u_agent === null) {
            throw new \InvalidArgumentException('parse_user_agent requires a user agent');
        }

        $platform = null;
        $browser  = null;
        $version  = null;

        $return = [ 'platform' => $platform, 'browser' => $browser, 'version' => $version ];

        if (!$u_agent) {
            return $return;
        }

        if (preg_match('/\((.*?)\)/m', $u_agent, $parent_matches)) {
            preg_match_all(<<<'REGEX'
/(?P<platform>BB\d+;|Android|Adr|Symbian|Sailfish|CrOS|Tizen|iPhone|iPad|iPod|Linux|(?:Open|Net|Free)BSD|Macintosh|
Windows(?:\ Phone)?|Silk|linux-gnu|BlackBerry|PlayBook|X11|(?:New\ )?Nintendo\ (?:WiiU?|3?DS|Switch)|Xbox(?:\ One)?)
(?:\ [^;]*)?
(?:;|$)/imx
REGEX
                , $parent_matches[1], $result);

            $priority = [ 'Xbox One', 'Xbox', 'Windows Phone', 'Tizen', 'Android', 'FreeBSD', 'NetBSD', 'OpenBSD', 'CrOS', 'X11', 'Sailfish' ];

            $result['platform'] = array_unique($result['platform']);
            if (count($result['platform']) > 1) {
                if ($keys = array_intersect($priority, $result['platform'])) {
                    $platform = reset($keys);
                } else {
                    $platform = $result['platform'][0];
                }
            } elseif (isset($result['platform'][0])) {
                $platform = $result['platform'][0];
            }
        }

        if ($platform == 'linux-gnu' || $platform == 'X11') {
            $platform = 'Linux';
        } elseif ($platform == 'CrOS') {
            $platform = 'Chrome OS';
        } elseif ($platform == 'Adr') {
            $platform = 'Android';
        } elseif ($platform === null) {
            if (preg_match_all('%(?P<platform>Android)[:/ ]%ix', $u_agent, $result)) {
                $platform = $result['platform'][0];
            }
        }

        preg_match_all(<<<'REGEX'
%(?P<browser>Camino|Kindle(\ Fire)?|Firefox|Iceweasel|IceCat|Safari|MSIE|Trident|AppleWebKit|
TizenBrowser|(?:Headless)?Chrome|YaBrowser|Vivaldi|IEMobile|Opera|OPR|Silk|Midori|(?-i:Edge)|EdgA?|CriOS|UCBrowser|Puffin|
OculusBrowser|SamsungBrowser|SailfishBrowser|XiaoMi/MiuiBrowser|YaApp_Android|
Baiduspider|Applebot|Facebot|Googlebot|YandexBot|bingbot|Lynx|Version|Wget|curl|
Valve\ Steam\ Tenfoot|
NintendoBrowser|PLAYSTATION\ (?:\d|Vita)+)
\)?;?
(?:[:/ ](?P<version>[0-9A-Z.]+)|/[A-Z]*)%ix
REGEX
            , $u_agent, $result);

        // If nothing matched, return null (to avoid undefined index errors)
        if (!isset($result['browser'][0]) || !isset($result['version'][0])) {
            if (preg_match('%^(?!Mozilla)(?P<browser>[A-Z0-9\-]+)([/ :](?P<version>[0-9A-Z.]+))?%ix', $u_agent, $result)) {
                return [ 'platform' => $platform ?: null, 'browser' => $result['browser'], 'version' => isset($result['version']) ? $result['version'] ?: null : null ];
            }

            return $return;
        }

        if (preg_match('/rv:(?P<version>[0-9A-Z.]+)/i', $u_agent, $rv_result)) {
            $rv_result = $rv_result['version'];
        }

        $browser = $result['browser'][0];
        $version = $result['version'][0];

        $lowerBrowser = array_map('strtolower', $result['browser']);

        $find = function ($search, &$key = null, &$value = null) use ($lowerBrowser) {
            $search = (array)$search;

            foreach ($search as $val) {
                $xkey = array_search(strtolower($val), $lowerBrowser);
                if ($xkey !== false) {
                    $value = $val;
                    $key   = $xkey;

                    return true;
                }
            }

            return false;
        };

        $findT = function (array $search, &$key = null, &$value = null) use ($find) {
            $value2 = null;
            if ($find(array_keys($search), $key, $value2)) {
                $value = $search[$value2];

                return true;
            }

            return false;
        };

        $key = 0;
        $val = '';
        if ($findT([ 'OPR' => 'Opera', 'Facebot' => 'iMessageBot', 'UCBrowser' => 'UC Browser', 'YaBrowser' => 'Yandex', 'YaApp_Android' => 'Yandex', 'Iceweasel' => 'Firefox', 'Icecat' => 'Firefox', 'CriOS' => 'Chrome', 'Edg' => 'Edge', 'EdgA' => 'Edge', 'XiaoMi/MiuiBrowser' => 'MiuiBrowser' ], $key, $browser)) {
            $version = is_numeric(substr($result['version'][$key], 0, 1)) ? $result['version'][$key] : null;
        } elseif ($find('Playstation Vita', $key, $platform)) {
            $platform = 'PlayStation Vita';
            $browser  = 'Browser';
        } elseif ($find([ 'Kindle Fire', 'Silk' ], $key, $val)) {
            $browser  = $val == 'Silk' ? 'Silk' : 'Kindle';
            $platform = 'Kindle Fire';
            if (!($version = $result['version'][$key]) || !is_numeric($version[0])) {
                $version = $result['version'][array_search('Version', $result['browser'])];
            }
        } elseif ($find('NintendoBrowser', $key) || $platform == 'Nintendo 3DS') {
            $browser = 'NintendoBrowser';
            $version = $result['version'][$key];
        } elseif ($find('Kindle', $key, $platform)) {
            $browser = $result['browser'][$key];
            $version = $result['version'][$key];
        } elseif ($find('Opera', $key, $browser)) {
            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif ($find('Puffin', $key, $browser)) {
            $version = $result['version'][$key];
            if (strlen($version) > 3) {
                $part = substr($version, -2);
                if (ctype_upper($part)) {
                    $version = substr($version, 0, -2);

                    $flags = [ 'IP' => 'iPhone', 'IT' => 'iPad', 'AP' => 'Android', 'AT' => 'Android', 'WP' => 'Windows Phone', 'WT' => 'Windows' ];
                    if (isset($flags[$part])) {
                        $platform = $flags[$part];
                    }
                }
            }
        } elseif ($find([ 'Applebot', 'IEMobile', 'Edge', 'Midori', 'Vivaldi', 'OculusBrowser', 'SamsungBrowser', 'Valve Steam Tenfoot', 'Chrome', 'HeadlessChrome', 'SailfishBrowser' ], $key, $browser)) {
            $version = $result['version'][$key];
        } elseif ($rv_result && $find('Trident')) {
            $browser = 'MSIE';
            $version = $rv_result;
        } elseif ($browser == 'AppleWebKit') {
            if ($platform == 'Android') {
                $browser = 'Android Browser';
            } elseif (strpos((string)$platform, 'BB') === 0) {
                $browser  = 'BlackBerry Browser';
                $platform = 'BlackBerry';
            } elseif ($platform == 'BlackBerry' || $platform == 'PlayBook') {
                $browser = 'BlackBerry Browser';
            } else {
                $find('Safari', $key, $browser) || $find('TizenBrowser', $key, $browser);
            }

            $find('Version', $key);
            $version = $result['version'][$key];
        } elseif ($pKey = preg_grep('/playstation \d/i', $result['browser'])) {
            $pKey = reset($pKey);

            $platform = 'PlayStation ' . preg_replace('/\D/', '', $pKey);
            $browser  = 'NetFront';
        }

        return [ 'platform' => $platform ?: null, 'browser' => $browser ?: null, 'version' => $version ?: null ];
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getCurrentOrder()
    {
        if (!$this->currentOrder) {
            $this->currentOrder = $this->checkoutSession->getLastRealOrder();
        }
        return $this->currentOrder;
    }

    /**
     * @return int
     */
    public function getCurrentOrderId()
    {
        if (!$this->currentOrder) {
            $this->currentOrder = $this->checkoutSession->getLastRealOrder();
        }
        return $this->currentOrder->getId();
    }

    /**
     * @return string
     */
    public function getServerSideTrackUrl()
    {
        return $this->_getUrl('weltpixel_qa4/track/purchase');
    }
}
