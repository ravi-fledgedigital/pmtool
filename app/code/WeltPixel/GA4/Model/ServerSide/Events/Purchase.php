<?php

namespace WeltPixel\GA4\Model\ServerSide\Events;

use WeltPixel\GA4\Api\ServerSide\Events\PurchaseInterface;
use WeltPixel\GA4\Api\ServerSide\Events\PurchaseItemInterface;

class Purchase implements PurchaseInterface
{
    /**
     * @var int
     */
    protected $orderId;

    /**
     * @var array
     */
    protected $payloadData;

    /**
     * @var array
     */
    protected $eventParams;

    /**
     * @var array
     */
    protected $purchaseItems;

    /**
     * @var array
     */
    protected $purchaseEvent;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var integer
     */
    protected $storeId;

    /**
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        $this->purchaseEvent = [];
        $this->payloadData = [];
        $this->payloadData['events'] = [];
        $this->purchaseEvent['name'] = 'purchase';
        $this->eventParams = [];
        $this->purchaseItems = [];
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param bool $debugMode
     * @return array
     */
    public function getParams($debugMode = false)
    {
        if ($debugMode) {
            $this->eventParams['debug_mode'] = 1;
        }
        $this->eventParams['items'] = $this->purchaseItems;
        $this->purchaseEvent['params'] = $this->eventParams;

        array_push($this->payloadData['events'], $this->purchaseEvent);
        return $this->payloadData;
    }

    /**
     * @param $pageLocation
     * @return PurchaseInterface
     */
    public function setPageLocation($pageLocation)
    {
        $this->eventParams['page_location'] = (string)$pageLocation;
        return $this;
    }

    /**
     * @param $gclid
     * @return $this
     */
    public function setGclid($gclid)
    {
        $this->eventParams['gclid'] = (string)$gclid;
        return $this;
    }

    /**
     * @param $clientId
     * @return PurchaseInterface
     */
    public function setClientId($clientId)
    {
        $this->payloadData['client_id'] = (string)$clientId;
        return $this;
    }

    /**
     * @param $userProperties
     * @return PurchaseInterface
     */
    public function setUserProperties($userProperties)
    {
        $this->payloadData['user_properties'] = $userProperties;
        return $this;
    }

    /**
     * @param $sessionId
     * @return PurchaseInterface
     */
    public function setSessionId($sessionId)
    {
        $this->eventParams['session_id'] =(string)$sessionId;
        return $this;
    }

    /**
     * @param $timestamp
     * @return PurchaseInterface
     */
    public function setTimestamp($timestamp)
    {
        $this->payloadData['timestamp_micros'] = (string)$timestamp;
        return $this;
    }

    /**
     * @param $userId
     * @return PurchaseInterface
     */
    public function setUserId($userId)
    {
        $this->payloadData['user_id'] = (string)$userId;
        $this->payloadData['user_data'] = (object)[];
        return $this;
    }

    /**
     * @param $order
     * @return $this|PurchaseInterface
     */
    public function setUserProvidedData($order, $attributes = [], $filterEnabled = false)
    {
        $userId = $order->getCustomerId() ?? '';
        $this->payloadData['user_id'] = (string)$userId;
        $billingAddress = $order->getBillingAddress();
        $userProvidedData = [];

        $availableAttributes = \WeltPixel\GA4\Model\Config\Source\EnhancedConversionAttributes::getAttributeKeys();
        if (!$filterEnabled || empty($attributes)) {
            $attributes = $availableAttributes;
        } else {
            $attributes = array_values(array_intersect($availableAttributes, $attributes));
        }

        if (in_array(\WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_EMAIL, $attributes)) {
            $userProvidedData['sha256_email_address'] = $this->hashUserProvidedDataEmail($order->getCustomerEmail() ?? '');
        }

        if ($billingAddress) {
            if (in_array(\WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_PHONE, $attributes)) {
                $userProvidedData['sha256_phone_number'] = $this->hashUserProvidedPhone($billingAddress->getTelephone() ?? '');
            }

            $addressData = [];
            if (in_array(\WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_FIRSTNAME, $attributes)) {
                $addressData['sha256_first_name'] = $this->hashUserProvidedData($billingAddress->getFirstname() ?? '');
            }
            if (in_array(\WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_LASTNAME, $attributes)) {
                $addressData['sha256_last_name'] = $this->hashUserProvidedData($billingAddress->getLastname() ?? '');
            }
            if (in_array(\WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_STREET, $attributes)) {
                $addressData['sha256_street'] = $this->hashUserProvidedStreet(implode(",", $billingAddress->getStreet() ?? []));
            }
            if (in_array(\WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_CITY, $attributes)) {
                $addressData['city'] = $billingAddress->getCity() ?? '';
            }
            if (in_array(\WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_REGION, $attributes)) {
                $addressData['region'] = $billingAddress->getRegion() ?? '';
            }
            if (in_array(\WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_POSTALCODE, $attributes)) {
                $addressData['postal_code'] = $billingAddress->getPostcode() ?? '';
            }
            if (in_array(\WeltPixel\GA4\Model\Api\ConversionTracking::FIELD_CONVERSION_TRACKING_CUSTOMER_COUNTRY, $attributes)) {
                $addressData['country'] = $billingAddress->getCountryId() ?? '';
            }

            if (!empty($addressData)) {
                $userProvidedData['address'] = $addressData;
            }
        }

        $this->payloadData['user_data'] = $userProvidedData;

        return $this;
    }

    /**
     * @param $currency
     * @return PurchaseInterface
     */
    public function setCurrency($currency)
    {
        $this->eventParams['currency'] = $currency;
        return $this;
    }

    /**
     * @param $transactionId
     * @return PurchaseInterface
     */
    public function setTransactionId($transactionId)
    {
        $this->eventParams['transaction_id'] = $transactionId;
        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->eventParams['transaction_id'];
    }

    /**
     * @param $orderId
     * @return $this|PurchaseInterface
     */
    public function setOrderId($orderId)
    {
        $this->orderId = $orderId;
        return $this;
    }

    /**
     * @return int
     */
    public function getOrderId()
    {
        return $this->orderId;
    }

    /**
     * @param $value
     * @return PurchaseInterface
     */
    public function setValue($value)
    {
        $this->eventParams['value'] = $value;
        return $this;
    }

    /**
     * @param $coupon
     * @return PurchaseInterface
     */
    public function setCoupon($coupon)
    {
        $this->eventParams['coupon'] = $coupon;
        return $this;
    }

    /**
     * @param $shipping
     * @return PurchaseInterface
     */
    public function setShipping($shipping)
    {
        $this->eventParams['shipping'] = $shipping;
        return $this;
    }

    /**
     * @param $tax
     * @return PurchaseInterface
     */
    public function setTax($tax)
    {
        $this->eventParams['tax'] = $tax;
        return $this;
    }

    /**
     * @param PurchaseItemInterface $purchaseItem
     * @return PurchaseInterface
     */
    public function addItem($purchaseItem)
    {
        $this->purchaseItems[] = $purchaseItem->getParams();
        return $this;
    }

    /**
     * @param $storeId
     * @return PurchaseInterface
     */
    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @return void
     */
    public function markAsPushed()
    {
        $incrementId = $this->getTransactionId();
        $orderId = $this->getOrderId();
        $connection = $this->resourceConnection->getConnection();
        try {
            $connection->update(
                $this->resourceConnection->getTableName('sales_order'),
                ['sent_to_measurement' => 1],
                ['increment_id = ?' => $incrementId]
            );

            $connection->insertOnDuplicate(
                $this->resourceConnection->getTableName('weltpixel_ga4_orders_pushed'),
                ['order_id' => $orderId]
            );

            $connection->delete(
                $this->resourceConnection->getTableName('weltpixel_ga4_orders_pushed_payload'),
                ['order_id = ?' => $orderId]
            );
        } catch (\Exception $e) {}
    }

    /**
     * @return boolean
     */
    public function isPushed()
    {
        $incrementId = $this->getTransactionId();
        $orderId = $this->getOrderId();
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('sales_order'), ['sent_to_measurement'])
            ->where('increment_id = ?', $incrementId);
        $result = $connection->fetchOne($select);

        $pushedSelect = $connection->select()
            ->from($this->resourceConnection->getTableName('weltpixel_ga4_orders_pushed'), ['order_id'])
            ->where('order_id = ?', $orderId);
        $pushedResult = $connection->fetchOne($pushedSelect);

        if ($result === false) {
            return true;
        }
        if ($result || $pushedResult) {
            return true;
        }

        return false;
    }

    /**
     * @param $orderId
     * @return int
     */
    public function isOrderPushed($orderId)
    {
        $connection = $this->resourceConnection->getConnection();

        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('sales_order'), ['sent_to_measurement'])
            ->where('entity_id = ?', $orderId);
        $result = $connection->fetchOne($select);

        $pushedSelect = $connection->select()
            ->from($this->resourceConnection->getTableName('weltpixel_ga4_orders_pushed'), ['order_id'])
            ->where('order_id = ?', $orderId);
        $pushedResult = $connection->fetchOne($pushedSelect);

        if ($result && $pushedResult) {
            return 2;
        }

        if ($pushedResult && !$result) {
            return 1;
        }

        return 0;
    }

    /**
     * Standardize and hash user provided data
     *
     * @param string $customerData
     * @return string
     */
    protected function hashUserProvidedData($customerData)
    {
        // Remove leading/trailing whitespace
        $customerData = trim($customerData);

        // Convert to lowercase
        $customerData = strtolower($customerData);

        // Remove digits and symbol characters, keep only letters and spaces
        $customerData = preg_replace('/[^a-z ]/', '', $customerData);

        // Generate SHA-256 hash
        return hash('sha256', $customerData);
    }

    /**
     * Standardize and hash user provided data
     *
     * @param string $street
     * @return string
     */
    public function hashUserProvidedStreet($street)
    {
        // Remove leading/trailing whitespace
        $customerData = trim($street);

        // Convert to lowercase
        $customerData = strtolower($street);

        // Remove symbol characters, keep letters, digits and spaces
        $street = preg_replace('/[^a-z0-9 ]/', '', $street);

        // Generate SHA-256 hash
        return hash('sha256', $street);
    }

    /**
     * Standardize and hash user provided telephone
     *
     * @param string $telephone
     * @return string
     */
    protected function hashUserProvidedPhone($telephone)
    {
        // Remove leading/trailing whitespace
        $telephone = trim($telephone);

        // Convert to lowercase
        $telephone = strtolower($telephone);

        // Remove all non-digit characters but keep leading '+' if present
        $telephone = preg_replace('/(?!^\+)\D+/', '', $telephone);

        // Generate SHA-256 hash
        return hash('sha256', $telephone);
    }

    /**
     * Standardize and hash email address
     *
     * @param string $email
     * @return string
     */
    protected function hashUserProvidedDataEmail($email)
    {
        // Remove leading/trailing whitespace
        $email = trim($email);

        // Convert to lowercase
        $email = strtolower($email);

        // Handle email addresses for gmail/googlemail
        if (strpos($email, '@gmail.') !== false || strpos($email, '@googlemail.') !== false) {
            // Remove dots before @gmail.com or @googlemail.com
            list($localPart, $domain) = explode('@', $email);
            $localPart = str_replace('.', '', $localPart);
            $email = $localPart . '@' . $domain;
        }

        // Generate SHA-256 hash
        return hash('sha256', $email);
    }
}
