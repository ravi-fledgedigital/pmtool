<?php

namespace OnitsukaTiger\Gthk\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment;
use OnitsukaTiger\Gthk\Model\GthkFactory;
use OnitsukaTiger\Gthk\Api\GthkRepositoryInterface;
use Magento\Store\Model\ScopeInterface;

class ApiService
{
    /**
     * @var \Magento\InventoryApi\Api\SourceRepositoryInterface
     */
    protected $sourceRepository;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $shipmentRepository;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;
    private GthkFactory $gthkFactory;
    private GthkRepositoryInterface $gthkRepository;
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \OnitsukaTiger\Gthk\Model\ResourceModel\Gthk $orderResource
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\InventoryApi\Api\SourceRepositoryInterface  $sourceRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface       $shipmentRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \OnitsukaTiger\Gthk\Model\ResourceModel\Gthk         $orderResource,
        \Magento\Framework\HTTP\Client\Curl                  $curl,
        GthkFactory                                          $gthkFactory,
        ScopeConfigInterface                                 $scopeConfig,
        GthkRepositoryInterface                              $gthkRepository
    )
    {
        $this->sourceRepository = $sourceRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->timezone = $timezone;
        $this->curl = $curl;
        $this->gthkFactory = $gthkFactory;
        $this->gthkRepository = $gthkRepository;
        $this->scopeConfig = $scopeConfig;
    }

    public function getStoreValue(string $field, $storeId = null): ?string
    {
        $path = 'onitsukatiger_gthk/general/' . $field;
        return $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Send order to GHTK (fallbacks to static values when config missing
     *
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @return array
     */
    public function sendOrder(\Magento\Sales\Model\Order\Shipment $shipment): array
    {
        $date = date('Y-m-d');
        $logFile = BP . "/var/log/ghtk_api_{$date}.log";

        $writer = new \Zend_Log_Writer_Stream($logFile);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $order = $shipment->getOrder();
        $storeId = (int)$order->getStoreId();
        $isEnabled = $this->scopeConfig->isSetFlag('onitsukatiger_gthk/general/enabled', ScopeInterface::SCOPE_STORE, $storeId);
        if (!$isEnabled) {
            $msg = 'GHTK integration disabled in config.';
            $logger->info($msg);
            $result['status'] = 'disabled';
            $result['decoded'] = $msg;
            $this->addCompactOrderHistoryComment($order, $result, $logger);

            return $result;

        }
        if ($isEnabled) {
            $url = $this->scopeConfig->getValue('onitsukatiger_gthk/general/url', ScopeInterface::SCOPE_STORE, $storeId) ?: 'https://services.giaohangtietkiem.vn/services/shipment/order';
            $products = [];
            foreach ($order->getAllVisibleItems() as $item) {
                $products[] = [
                    'name' => mb_substr($item->getName() ?: $item->getSku(), 0, 255),
                    'weight' => 1,
                    'quantity' => (int)$item->getQtyOrdered(),
                    'product_code' => $item->getSku() ?: (int)$item->getProductId()
                ];
            }
            if (empty($products)) {
                $products = [
                    [
                        'name' => 'bút',
                        'weight' => 1,
                        'quantity' => 1,
                        'product_code' => 1241
                    ]
                ];
            }

            $shippingAddress = $order->getShippingAddress();
            $billingAddress = $order->getBillingAddress();
            $recipientAddress = $shippingAddress ?: $billingAddress;

            $recipientAddressString = $recipientAddress && $recipientAddress->getStreetLine(1)
                ? trim(($recipientAddress->getStreetLine(1) ?? '') . ' ' . ($recipientAddress->getStreetLine(2) ?? ''))
                : (string)$order->getCustomerAddress();
            $parsed = $this->getAddressFromStreet($recipientAddressString);

            $recipientProvince = $parsed->getProvince();
            $recipientDistrict = $parsed->getDistrict();
            $recipientWard = $parsed->getWard();
            $recipientHamlet = $parsed->getHamlet();
            if (!$recipientAddress) {
                $result['status'] = 'no_recipient_address';
                $result['decoded'] = 'No shipping or billing address available';
                $logger->info("GHTK: no recipient address, skipping API call for order {$order->getIncrementId()}");
                $this->addCompactOrderHistoryComment($order, $result, $logger);
                return $result;
            }

            $parsedIsEmpty = (
                $parsed === null
                || (
                    $parsed->getProvince() === null
                    && $parsed->getDistrict() === null
                    && $parsed->getWard() === null
                    && $parsed->getHamlet() === null
                )
            );

            if ($parsedIsEmpty) {
                $result['status'] = 'address_parse_failed';
                $result['decoded'] = 'Address parsing returned empty (province/district/ward/hamlet missing)';
                $logger->info("GHTK: address parse empty, skipping API call for order {$order->getIncrementId()}");
                $this->addCompactOrderHistoryComment($order, $result, $logger);
                return $result;
            }

            $pickName = $this->scopeConfig->getValue('onitsukatiger_gthk/general/pick_name', ScopeInterface::SCOPE_STORE, $storeId) ?: 'HCM-nội thành';
            $pickAddress = $this->scopeConfig->getValue('onitsukatiger_gthk/general/pick_address', ScopeInterface::SCOPE_STORE, $storeId) ?: '590 CMT8 P.11';
            $pickProvince = $this->scopeConfig->getValue('onitsukatiger_gthk/general/pick_province', ScopeInterface::SCOPE_STORE, $storeId) ?: 'TP. Hồ Chí Minh';
            $pickDistrict = $this->scopeConfig->getValue('onitsukatiger_gthk/general/pick_district', ScopeInterface::SCOPE_STORE, $storeId) ?: 'Quận 3';
            $pickWard = $this->scopeConfig->getValue('onitsukatiger_gthk/general/pick_ward', ScopeInterface::SCOPE_STORE, $storeId) ?: 'Phường 1';
            $pickTel = $this->scopeConfig->getValue('onitsukatiger_gthk/general/pick_tel', ScopeInterface::SCOPE_STORE, $storeId) ?: '0911222333';
            $pickDateCfg = $this->scopeConfig->getValue('onitsukatiger_gthk/general/pick_date', ScopeInterface::SCOPE_STORE, $storeId);
            $pickDate = $pickDateCfg ? date('Y-m-d', strtotime($pickDateCfg)) : date('Y-m-d');
            $pickMoneyCfg = $this->scopeConfig->getValue('onitsukatiger_gthk/general/pick_money', ScopeInterface::SCOPE_STORE, $storeId);
            $pickMoney = $pickMoneyCfg ? (float)$pickMoneyCfg : 0.0;
            $note = $this->scopeConfig->getValue('onitsukatiger_gthk/general/note', ScopeInterface::SCOPE_STORE, $storeId) ?: 'Khối lượng tính cước tối đa: 1.00 kg';
            $isFreeship = $this->scopeConfig->getValue('onitsukatiger_gthk/general/is_freeship', ScopeInterface::SCOPE_STORE, $storeId) ?: 0;
            $pickupOption = $this->scopeConfig->getValue('onitsukatiger_gthk/general/pick_option', ScopeInterface::SCOPE_STORE, $storeId) ?: 'cod';
            $transport = $this->scopeConfig->getValue('onitsukatiger_gthk/general/transport', ScopeInterface::SCOPE_STORE, $storeId) ?: 'fly';

            $grandTotal = $this->getShipmentGrandTotal($shipment);
            $logger->info(sprintf(
                'Computed shipment grand total for shipment %s: %.2f',
                $shipment->getIncrementId() ?: $shipment->getId(),
                $grandTotal
            ));

            $isValidCod = ($grandTotal >= 10000 && $grandTotal <= 20000000);
            $value = $isValidCod ? (int)round($grandTotal) : 3000000;
            $orderPayload = [
                'id' => $shipment->getIncrementId(),
                'pick_name' => $pickName,
                'pick_address' => $pickAddress,
                'pick_province' => $pickProvince,
                'pick_district' => $pickDistrict,
                'pick_ward' => $pickWard,
                'pick_tel' => $pickTel,
                'tel' => $recipientAddress->getTelephone() ? $recipientAddress->getTelephone() : ($order->getCustomerTelephone() ?? '0911222333'),
                'name' => $recipientAddress->getFirstname() ? trim($recipientAddress->getFirstname() . ' ' . $recipientAddress->getLastname()) : $order->getCustomerName(),
                'address' => $recipientAddress->getStreetLine(1) ? trim($recipientAddress->getStreetLine(1)) : $order->getCustomerAddress(),
                'province' => $recipientProvince,
                'district' => $recipientDistrict,
                'ward' => $recipientWard,
                'hamlet' => $recipientHamlet,
                'pick_date' => $pickDate ?: '2016-09-30',
                'pick_money' => $pickMoney ?: 0,
                'note' => $note ?: 'Khối lượng tính cước tối đa: 1.00 kg',
                'value' => $value ?: 3000000,
                'pick_session' => 2,
                'is_freeship' => $isFreeship,
                'pick_option' => $pickupOption,
                'transport' => $transport
            ];

            $payload = [
                'products' => $products,
                'order' => $orderPayload
            ];

            $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);
            $result = [
                'success' => false,
                'tracking_id' => null,
                'status' => null,
                'raw' => null,
                'decoded' => null,
            ];

            $token = $this->scopeConfig->getValue('onitsukatiger_gthk/general/token', ScopeInterface::SCOPE_STORE, $storeId) ?: 'B1rh1Y154DMqItStuucg1sZ0UKwNdsPQjUU52Z';
            $xClientSource = $this->scopeConfig->getValue('onitsukatiger_gthk/general/x_client_source', ScopeInterface::SCOPE_STORE, $storeId) ?: 'S23025210';
            $timeout = 10;

            $logger->info("-------------------GHTK Request Payload Start--------------------");
            $logger->info("GHTK Endpoint: " . $url);
            $logger->info("GHTK Token: " . $token);
            $logger->info("GHTK X-Client-Source: " . $xClientSource);
            $logger->info("GHTK Request Payload : " . $jsonPayload);
            $logger->info("-------------------GHTK Request Payload End--------------------");

            try {
                if (method_exists($this->curl, 'addHeader')) {
                    $this->curl->addHeader('Token', $token);
                    $this->curl->addHeader('X-Client-Source', $xClientSource);
                    $this->curl->addHeader('Content-Type', 'application/json');
                } else {
                    $httpHeaders = [
                        'Token: ' . $token,
                        'X-Client-Source: ' . $xClientSource,
                        'Content-Type: application/json'
                    ];
                    $this->curl->setOption(CURLOPT_HTTPHEADER, $httpHeaders);
                }
                $this->curl->setOption(CURLOPT_TIMEOUT, $timeout);
                $this->curl->post($url, $jsonPayload);

                $status = $this->curl->getStatus();
                $body = $this->curl->getBody();

                $result['status'] = $status;
                $result['raw'] = $body;

                $logger->info("------------------ Tracking API Response  Start ---------------------");
                $logger->info("GHTK API Response [HTTP $status] for order {$order->getIncrementId()}: " . $body);
                $logger->info("------------------ Tracking API Response END ---------------------");
                $decoded = json_decode($body, true);
                if ($status < 200 || $status >= 300) {
                    $logger->info("GHTK API returned HTTP {$status} and error code {$decoded['error_code']} for order {$order->getIncrementId()}");
                    $result['status'] = $status;
                    $result['decoded'] = (string)$body;
                    $this->addCompactOrderHistoryComment($order, $result, $logger);
                    return $result;
                }

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $logger->info('Invalid JSON from GHTK: ' . json_last_error_msg());
                    $result['status'] = $status ?? $result['status'] ?? 'invalid_json';
                    $result['raw'] = $body;
                    $result['decoded'] = (string)$body;
                    $this->addCompactOrderHistoryComment($order, $result, $logger);
                    return $result;
                }

                $result['decoded'] = is_array($decoded)
                    ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                    : (string)$decoded;

                if (!empty($decoded['success']) && !empty($decoded['order']['tracking_id'])) {
                    $result['success'] = true;
                    $result['tracking_id'] = $decoded['order']['tracking_id'];
                    try {
                        $model = $this->gthkFactory->create();
                        $model->setShipmentId($shipment->getId());
                        $model->setTrackingId($result['tracking_id']);
                        $model->setOrderId($order->getEntityId());
                        $model->setCreatedAt($order->getCreatedAt());
                        $model->setJsonData($body);
                        $model->save();
                        $this->gthkRepository->save($model);
                    } catch (\Throwable $e) {
                        $logger->info('Failed to save GHTK mapping: ' . $e->getMessage());
                        $result['status'] = $result['status'] ?? 'mapping_save_failed';
                        $result['decoded'] = $result['decoded'] ?? $e->getMessage();
                        $this->addCompactOrderHistoryComment($order, $result, $logger);
                    }
                    return $result;
                }
                $result['status'] = $status ?? $result['status'] ?? 'api_failure';
                $result['raw'] = $body;
                $result['decoded'] = is_array($decoded) ? json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) : (string)$decoded;

                $logger->info('GHTK API returned failure or missing tracking_id', ['decoded' => $decoded ?? null]);
                $this->addCompactOrderHistoryComment($order, $result, $logger);
                return $result;
            } catch (\Throwable $e) {
                $logger->info('GHTK API exception: ' . $e->getMessage());
                $result['status'] = $result['status'] ?? 'exception';
                $result['raw'] = $result['raw'] ?? $e->getMessage();
                $result['decoded'] = (string)$e->getMessage();
                $this->addCompactOrderHistoryComment($order, $result, $logger);
                return $result;
            }
        }
    }

    /**
     * Add a compact, internal-only order history comment when API fails.
     *
     * @param Order $order
     * @param array $result
     * @param \Zend_Log $logger
     * @return void
     */
    public function addCompactOrderHistoryComment(Order $order, array $result, $logger)
    {
        $httpStatus = $result['status'] ?? 'unknown';
        $shortDecodedMsg = '';
        $decoded = null;
        if (!empty($result['decoded']) && is_string($result['decoded'])) {
            $tmp = json_decode($result['decoded'], true);
            $decoded = is_array($tmp) ? $tmp : null;
        }

        if (is_array($decoded)) {
            $apiCode = $decoded['code'] ?? ($decoded['error']['code'] ?? null);
            $apiMessage = $decoded['message']
                ?? ($decoded['error']['message'] ?? null)
                ?? ($decoded['order']['message'] ?? null);

            if ($apiCode) {
                $shortDecodedMsg .= "code: {$apiCode}";
            }
            if ($apiMessage) {
                if (!empty($shortDecodedMsg)) $shortDecodedMsg .= ' - ';
                $shortDecodedMsg .= $apiMessage;
            }
        }
        if (empty($shortDecodedMsg) && !empty($result['raw'])) {
            $raw = (string)$result['raw'];
            $shortDecodedMsg = substr($raw, 0, 200);
            if (strlen($raw) > 200) $shortDecodedMsg .= '...';
        }

        if (empty($shortDecodedMsg)) {
            $shortDecodedMsg = 'Unexpected failure while processing shipment request';
        }

        $errorMsg = sprintf(
            'GHTK API failed (status: %s). %s',
            $httpStatus,
            $shortDecodedMsg
        );

        try {
            $order->addStatusHistoryComment($errorMsg)
                ->setIsCustomerNotified(false)
                ->setIsVisibleOnFront(false);
            $order->save();
            $logger->info("Order history updated for order {$order->getIncrementId()} (compact).");
        } catch (\Throwable $ex) {
            $logger->info("Failed to add compact order comment: " . $ex->getMessage());
        }
    }

    /**
     * Parse an address string (street/line1) with GHTK and return DataObject with parts.
     *
     * @param string|null $streetLine1
     * @return \Magento\Framework\DataObject
     */
    public function getAddressFromStreet(string $streetLine1 = null)
    {
        $date = date('Y-m-d');
        $logFile = BP . "/var/log/ghtk_api_Address_{$date}.log";

        $writer = new \Zend_Log_Writer_Stream($logFile);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        $emptyResult = new \Magento\Framework\DataObject([
            'province' => null,
            'district' => null,
            'ward' => null,
            'hamlet' => null,
            'raw' => null,
        ]);

        try {
            $addressString = trim((string)$streetLine1);
            if ($addressString === '') {
                $logger->info('getAddressFromStreet: empty street input, returning fallback');
                return $emptyResult;
            }

            $token = $this->scopeConfig->getValue(
                'onitsukatiger_gthk/general/token',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) ?: '34ZUATxZUnXPSzozZNUNfk0VAO1MQ6LWQ4dEOuN';

            $clientSource = $this->scopeConfig->getValue(
                'onitsukatiger_gthk/general/client_source',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) ?: 'S23025210';

            $addressUrl = $this->scopeConfig->getValue(
                'onitsukatiger_gthk/general/address_url',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            ) ?: 'https://services.giaohangtietkiem.vn';

            $url = rtrim($addressUrl, '/') . '/open/api/v1/address/parse-address?address=' . urlencode($addressString);

            if (method_exists($this->curl, 'addHeader')) {
                $this->curl->addHeader('Token', $token);
                $this->curl->addHeader('X-Client-Source', $clientSource);
                $this->curl->addHeader('Content-Type', 'application/json');
            } else {
                $this->curl->setOption(CURLOPT_HTTPHEADER, [
                    'Token: ' . $token,
                    'X-Client-Source: ' . $clientSource,
                    'Content-Type: application/json',
                ]);
            }

            if (defined('CURLOPT_CONNECTTIMEOUT')) {
                $this->curl->setOption(CURLOPT_CONNECTTIMEOUT, 3);
            }
            $this->curl->setOption(CURLOPT_TIMEOUT, 6);
            if (defined('CURLOPT_NOSIGNAL')) {
                $this->curl->setOption(CURLOPT_NOSIGNAL, 1);
            }

            $this->curl->get($url);
            $body = (string)$this->curl->getBody();

            $json = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($json['success'])) {
                $logger->info('GHTK parse-address: invalid response', ['body_snippet' => substr($body, 0, 500)]);
                return $emptyResult;
            }

            $data = $json['data'] ?? [];

            return new \Magento\Framework\DataObject([
                'province' => $data['province']['name'] ?? null,
                'district' => $data['district']['name'] ?? null,
                'ward' => $data['ward']['name'] ?? null,
                'hamlet' => $data['hamlet']['name'] ?? 'Khác',
                'raw' => $data,
            ]);
        } catch (\Throwable $e) {
            $logger->info('getAddressFromStreet failed: ' . $e->getMessage(), ['exception' => $e]);
            return $emptyResult;
        }
    }

    public function getShipmentGrandTotal(\Magento\Sales\Model\Order\Shipment $shipment)
    {
        $total = 0;

        foreach ($shipment->getAllItems() as $item) {
            $orderItem = $item->getOrderItem();
            if (!$orderItem) continue;

            $qty = (float)$item->getQty();
            if ($qty <= 0) continue;

            $row = (float)$orderItem->getRowTotalInclTax();
            $qtyOrdered = max(1, (float)$orderItem->getQtyOrdered());
            $unit = $row > 0 ? $row / $qtyOrdered : (float)$orderItem->getPrice();

            $total += $unit * $qty;
        }

        return round($total, 2);
    }

}
