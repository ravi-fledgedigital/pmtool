<?php
namespace OnitsukaTiger\Ninja\Service;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use OnitsukaTiger\Logger\Ninja\Logger;

class ApiService
{
    const DOMAIN_PRODUCTION = 'https://api.ninjavan.co/';
    const DOMAIN_SANDBOX = 'https://api-sandbox.ninjavan.co/';
    const ORDER_MAX_RETRY = 3;
    const URL_AUTH = '/2.0/oauth/access_token';
    const URL_ORDER = '/4.2/orders';
    const SINGAPORE_COUNTRY_CODE = "SG";
    const MALAYSIA_COUNTRY_CODE = "MY";

    /**
     * @var Logger
     */
    protected $logger;
    /**
     * @var \Magento\Framework\HTTP\LaminasClientFactory
     */
    protected $httpClientFactory;
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
     * @var \OnitsukaTiger\Ninja\Model\AccessTokenFactory
     */
    protected $accessTokenFactory;
    /**
     * @var \OnitsukaTiger\Ninja\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @var ResourceModel\AccessToken
     */
    protected $accessTokenResource;
    /**
     * @var \OnitsukaTiger\Ninja\Model\Config
     */
    protected $config;
    /**
     * @var ResourceModel\Order
     */
    protected $orderResource;
    private ScopeConfigInterface $scopeConfig;

    /**
     * @param Logger $logger
     * @param \Magento\Framework\HTTP\LaminasClientFactory $httpClientFactory
     * @param \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository
     * @param \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \OnitsukaTiger\Ninja\Model\AccessTokenFactory $accessTokenFactory
     * @param \OnitsukaTiger\Ninja\Model\OrderFactory $orderFactory
     * @param \OnitsukaTiger\Ninja\Model\Config $config
     * @param \OnitsukaTiger\Ninja\Model\ResourceModel\AccessToken $accessTokenResource
     * @param \OnitsukaTiger\Ninja\Model\ResourceModel\Order $orderResource
     */
    public function __construct(
        Logger $logger,
        \Laminas\Http\ClientFactory $httpClientFactory,
        \Magento\InventoryApi\Api\SourceRepositoryInterface $sourceRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \OnitsukaTiger\Ninja\Model\AccessTokenFactory $accessTokenFactory,
        \OnitsukaTiger\Ninja\Model\OrderFactory $orderFactory,
        \OnitsukaTiger\Ninja\Model\Config $config,
        \OnitsukaTiger\Ninja\Model\ResourceModel\AccessToken $accessTokenResource,
        ScopeConfigInterface        $scopeConfig,
        \OnitsukaTiger\Ninja\Model\ResourceModel\Order $orderResource
    ) {
        $this->logger = $logger;
        $this->httpClientFactory = $httpClientFactory;
        $this->sourceRepository = $sourceRepository;
        $this->shipmentRepository = $shipmentRepository;
        $this->timezone = $timezone;
        $this->accessTokenFactory = $accessTokenFactory;
        $this->orderFactory = $orderFactory;
        $this->accessTokenResource = $accessTokenResource;
        $this->orderResource = $orderResource;
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get auth token from Ninja
     * @param $websiteId
     * @param $forceUpdate
     * @return mixed
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function getAuthToken($websiteId, $forceUpdate=false)
    {
        $countryCode = $this->config->get(\OnitsukaTiger\Ninja\Model\Config::PATH_COUNTRY_CODE, $websiteId);
        $model = $this->accessTokenFactory->create();
        $this->accessTokenResource->loadByCountryCode($model, $countryCode);
        $this->logger->info($countryCode . ' Force Update: ' . $forceUpdate);
        if (!$forceUpdate && $model->getAccessToken()) {
            $expires = $model->getExpires();
            $ts = time();
            if ($expires > $ts) {
                $this->logger->info($countryCode . ' Old access token: ' . $model->getAccessToken());
                // return saved access token if it's not expired yet
                return $model->getAccessToken();
            }
            $this->logger->info($countryCode . ' Access token expired');
        }

        // get new access token
        $this->logger->info('Get new access token. countryCode : ' . $countryCode);
        $clientId = $this->config->get(\OnitsukaTiger\Ninja\Model\Config::PATH_CLIENT_ID, $websiteId);
        $clientSecret = $this->config->get(\OnitsukaTiger\Ninja\Model\Config::PATH_CLIENT_SECRET, $websiteId);
        $this->logger->info('Client ID : ' . $clientId);
        $this->logger->info('Client Secret : ' . $clientSecret);
        $result = $this->execute(
            $this->buildUrl(self::URL_AUTH, $websiteId),
            [
                'client_id'=> $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials'
            ]
        );
        $response = $result->getBody();
        $json = json_decode($response, true);
        if (!array_key_exists('access_token', $json)) {
            $msg = 'could not take access token : ' . $response;
            $this->logger->error($msg);
            throw new \Exception($msg);
        }

        // save new access token
        $model->setCountryCode($countryCode);
        $model->setExpires($json['expires']);
        $model->setExpiresIn($json['expires_in']);
        $model->setAccessToken($json['access_token']);
        $this->accessTokenResource->save($model);

        return $model->getAccessToken();
    }

    /**
     * Send order to Ninja
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendOrder(
        \Magento\Sales\Model\Order\Shipment $shipment
    ) {
        $websiteId = $shipment->getStore()->getWebsiteId();
        $order = $shipment->getOrder();
        $storeId = $order->getStoreId();
        $enable = $this->scopeConfig->getValue('ninja/general/enabled', ScopeInterface::SCOPE_STORE, $storeId);
        if ($enable) {
            $address = $order->getShippingAddress();
            $countryCode = $this->config->get(\OnitsukaTiger\Ninja\Model\Config::PATH_COUNTRY_CODE, $websiteId);
            $from = $this->getSenderInformation($shipment, $countryCode);

            ($countryCode == self::MALAYSIA_COUNTRY_CODE) ? $state = $address->getRegion() : $state = "";
            $isPickupRequired = $countryCode == self::SINGAPORE_COUNTRY_CODE;
            $timeZone = $this->timezone->getConfigTimezone(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, $websiteId);

            /*if (in_array($shipment->getStoreId(), [8,10])) {
                $timeZone = $this->timezone->getConfigTimezone(\Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE, 1);
            }*/

            $serviceLevel = 'Standard';
            if ($websiteId == 1) {
                $serviceLevel = 'Nextday';
            }

            $deliveryEndTime = $this->config->get(\OnitsukaTiger\Ninja\Model\Config::PATH_DELIVERY_END_TIME, $websiteId);
            $pickupEndTime = $this->config->get(\OnitsukaTiger\Ninja\Model\Config::PATH_PICKUP_END_TIME, $websiteId);

            if (empty($deliveryEndTime)) {
                $deliveryEndTime = '22:00';
            }

            if (empty($pickupEndTime)) {
                $pickupEndTime = '22:00';
            }

            if ($websiteId == 6) {
                $isPickupRequired = true;
            }

            // TODO need to fix after address format is fix for each country
            $body = [
                "service_type" => "Parcel",
                "service_level" => $serviceLevel,
//            "requested_tracking_number" => "1234-56789",
                "reference" => [
                    "merchant_order_number" => $shipment->getIncrementId()
                ],
                "from" => $from,
                "to" => [
                    "name" => $address->getName(),
                    "phone_number" => $address->getTelephone(),
                    "email" => $address->getEmail(),
                    "address" => [
                        "address1" => implode(', ', $address->getStreet()),
                        "address2" => "",
                        //"area" => "Taman Sri Delima",
                        "city" => $address->getCity(),
                        "state" => $state,
                        "country" => $countryCode,
                        "postcode" => $address->getPostcode()
                    ]
                ],
                "parcel_job" => [
                    "is_pickup_required" => $isPickupRequired,
                    "pickup_date" => date('Y-m-d'),
                    "pickup_timeslot" => [
                        'start_time' => '09:00',
                        'end_time' => $pickupEndTime,
                        'timezone' => $timeZone
                    ],
                    "pickup_service_type" => "Scheduled",
                    "pickup_service_level" => "Standard",
                    'delivery_start_date' => date('Y-m-d'),
                    'delivery_timeslot' => [
                        'start_time' => '09:00',
                        'end_time' => $deliveryEndTime,
                        'timezone' => $timeZone
                    ],
                    'dimensions' => [
                        'size' => 'S'
                    ]
                ]
            ];

            $this->logger->info('Body Request Ninja', $body);

            $response = '{}';
            $forceUpdateToken = false;
            $this->logger->info('NV URL: ' . $this->buildUrl(self::URL_ORDER, $websiteId));
            for ($i = 0; $i < self::ORDER_MAX_RETRY; $i++) {
                $result = $this->execute(
                    $this->buildUrl(self::URL_ORDER, $websiteId),
                    $body,
                    $this->getAuthToken($websiteId, $forceUpdateToken)
                );
                $status = $result->getStatusCode();
                if ($status != \Laminas\Http\Response::STATUS_CODE_200) {
                    sleep(1);

                    if ($status == \Laminas\Http\Response::STATUS_CODE_401) {
                        // retry with force update flag
                        $forceUpdateToken = true;
                    }

                    $this->logger->error('HTTP Status : ' . $result->getStatusCode());
                } else {
                    $response = $result->getBody();
                    break;
                }
            }
            $json = json_decode($response, true);
            if (!array_key_exists('tracking_number', $json)) {
                $msg = 'could not take tracking_number : ' . $response;
                $this->logger->error($msg);
                $this->logger->error('HTTP Status : ' . $result->getStatusCode());
                $this->logger->error($result->__toString());
                throw new \Exception($msg);
            }
            $model = $this->orderFactory->create();
            $model->setShipmentId($shipment->getId());
            $model->setTrackingId($json['tracking_number']);
            $model->setWebsiteId($websiteId);
            $model->setJson($response);
            $this->orderResource->save($model);

            return $json;
        }
    }

    /**
     * @param $url
     * @param $body
     * @param $token
     * @return \Laminas\Http\Response
     */
    public function execute($url, $body, $token=null)
    {
        $client = $this->httpClientFactory->create();
        $client->setUri($url);
        /*$client->setConfig(['maxredirects' => 0, 'timeout' => 30]);*/
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];

        if ($token) {
            $headers['Authorization'] = 'Bearer ' . $token;
        }
        $client->setHeaders($headers);
        $client->setRawBody(json_encode($body));
        $client->setMethod("POST");

        return $client->send();
    }

    /**
     * Build URL
     * @param $url
     * @param $websiteId
     * @return string
     */
    private function buildUrl($url, $websiteId)
    {
        $this->logger->info('Building URL: ' . $url);
        $this->logger->info('Website ID: ' . $websiteId);
        $sandbox = $this->config->get(\OnitsukaTiger\Ninja\Model\Config::PATH_SANDBOX, $websiteId);
        $countryCode = $this->config->get(\OnitsukaTiger\Ninja\Model\Config::PATH_COUNTRY_CODE, $websiteId);
        /*if ($websiteId == 6) {
            $countryCode = "SG";
        }*/
        $domain = ($sandbox) ? self::DOMAIN_SANDBOX : self::DOMAIN_PRODUCTION;
        $this->logger->info('Domain: ' . $domain);
        $this->logger->info('Country Code: ' . $countryCode);
        return $domain . $countryCode . $url;
    }

    /**
     * Get sender information
     * @param \Magento\Sales\Model\Order\Shipment $shipment
     * @param $countryCode
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function getSenderInformation(
        \Magento\Sales\Model\Order\Shipment $shipment,
                                            $countryCode
    ) {

        // Cannot load extension attribute if shipment get from order, need to load.
        $shipment = $this->shipmentRepository->get($shipment->getId());
        $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();

        /** @type \Magento\InventoryApi\Api\Data\SourceInterface $source */
        $source = $this->sourceRepository->get($sourceCode);

        $ret = [
            "name" => $source->getContactName(),
            "phone_number" => $source->getPhone(),
            "email" => $source->getEmail(),
            "address" => []
        ];
        $ret['address']['address1'] = $source->getStreet();
        $ret['address']['city'] = $source->getCity();
        $ret['address']['country'] = $countryCode;
        $ret['address']['postcode'] = $source->getPostcode();

        return $ret;
    }
}
