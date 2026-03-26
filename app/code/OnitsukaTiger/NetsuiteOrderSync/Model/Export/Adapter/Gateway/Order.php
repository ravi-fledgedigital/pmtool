<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio. All rights reserved.
 * @author   : thanhnv7184@co-well.com.vn
 */

namespace OnitsukaTiger\NetsuiteOrderSync\Model\Export\Adapter\Gateway;

use Firebear\ImportExport\Logger\Logger;
use Firebear\ImportExport\Traits\General as GeneralTrait;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use Magento\Sales\Model\Order\CreditmemoRepository;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\ShipmentRepository;
use NetSuite\Classes\SalesOrderItem;
use OnitsukaTiger\NetSuite\Model\SourceMapping;
use Symfony\Component\Console\Output\ConsoleOutput;

/**
 * Netsuite order gateway
 */
class Order extends \Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway\Order
{
    use GeneralTrait;

    protected $helperData;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var array
     */
    protected $customFieldReplaceData;

    /**
     * @var SourceMapping
     */
    protected $_mapping;

    /**
     * @var array
     */
    public $countryMapping = [
        'AF' => '_afghanistan',
        'AX' => '_alandIslands',
        'AL' => '_albania',
        'DZ' => '_algeria',
        'AS' => '_americanSamoa',
        'AD' => '_andorra',
        'AO' => '_angola',
        'AI' => '_anguilla',
        'AQ' => '_antarctica',
        'AG' => '_antiguaAndBarbuda',
        'AR' => '_argentina',
        'AM' => '_armenia',
        'AW' => '_aruba',
        'AU' => '_australia',
        'AT' => '_austria',
        'AZ' => '_azerbaijan',
        'BS' => '_bahamas',
        'BH' => '_bahrain',
        'BD' => '_bangladesh',
        'BB' => '_barbados',
        'BY' => '_belarus',
        'BE' => '_belgium',
        'BZ' => '_belize',
        'BJ' => '_benin',
        'BM' => '_bermuda',
        'BT' => '_bhutan',
        'BO' => '_bolivia',
        'BQ' => '_bonaireSaintEustatiusAndSaba',
        'BA' => '_bosniaAndHerzegovina',
        'BW' => '_botswana',
        'BV' => '_bouvetIsland',
        'BR' => '_brazil',
        'IO' => '_britishIndianOceanTerritory',
        'BN' => '_bruneiDarussalam',
        'BG' => '_bulgaria',
        'BF' => '_burkinaFaso',
        'BI' => '_burundi',
        'KH' => '_cambodia',
        'CM' => '_cameroon',
        'CA' => '_canada',
        'IC' => '_canaryIslands',
        'CV' => '_capeVerde',
        'KY' => '_caymanIslands',
        'CF' => '_centralAfricanRepublic',
        'EA' => '_ceutaAndMelilla',
        'TD' => '_chad',
        'CL' => '_chile',
        'CN' => '_china',
        'CX' => '_christmasIsland',
        'CC' => '_cocosKeelingIslands',
        'CO' => '_colombia',
        'KM' => '_comoros',
        'CD' => '_congoDemocraticPeoplesRepublic',
        'CG' => '_congoRepublicOf',
        'CK' => '_cookIslands',
        'CR' => '_costaRica',
        'CI' => '_coteDIvoire',
        'HR' => '_croatiaHrvatska',
        'CU' => '_cuba',
        'CW' => '_curacao',
        'CY' => '_cyprus',
        'CZ' => '_czechRepublic',
        'DK' => '_denmark',
        'DJ' => '_djibouti',
        'DM' => '_dominica',
        'DO' => '_dominicanRepublic',
        'TP' => '_eastTimor',
        'EC' => '_ecuador',
        'EG' => '_egypt',
        'SV' => '_elSalvador',
        'GQ' => '_equatorialGuinea',
        'ER' => '_eritrea',
        'EE' => '_estonia',
        'ET' => '_ethiopia',
        'FK' => '_falklandIslands',
        'FO' => '_faroeIslands',
        'FJ' => '_fiji',
        'FI' => '_finland',
        'FR' => '_france',
        'GF' => '_frenchGuiana',
        'PF' => '_frenchPolynesia',
        'TF' => '_frenchSouthernTerritories',
        'GA' => '_gabon',
        'GM' => '_gambia',
        'GE' => '_georgia',
        'DE' => '_germany',
        'GH' => '_ghana',
        'GI' => '_gibraltar',
        'GR' => '_greece',
        'GL' => '_greenland',
        'GD' => '_grenada',
        'GP' => '_guadeloupe',
        'GU' => '_guam',
        'GT' => '_guatemala',
        'GG' => '_guernsey',
        'GN' => '_guinea',
        'GW' => '_guineaBissau',
        'GY' => '_guyana',
        'HT' => '_haiti',
        'HM' => '_heardAndMcDonaldIslands',
        'VA' => '_holySeeCityVaticanState',
        'HN' => '_honduras',
        'HK' => '_hongKong',
        'HU' => '_hungary',
        'IS' => '_iceland',
        'IN' => '_india',
        'ID' => '_indonesia',
        'IR' => '_iranIslamicRepublicOf',
        'IQ' => '_iraq',
        'IE' => '_ireland',
        'IM' => '_isleOfMan',
        'IL' => '_israel',
        'IT' => '_italy',
        'JM' => '_jamaica',
        'JP' => '_japan',
        'JE' => '_jersey',
        'JO' => '_jordan',
        'KZ' => '_kazakhstan',
        'KE' => '_kenya',
        'KI' => '_kiribati',
        'KP' => '_koreaDemocraticPeoplesRepublic',
        'KR' => '_koreaRepublicOf',
        'XK' => '_kosovo',
        'KW' => '_kuwait',
        'KG' => '_kyrgyzstan',
        'LA' => '_laoPeoplesDemocraticRepublic',
        'LV' => '_latvia',
        'LB' => '_lebanon',
        'LS' => '_lesotho',
        'LR' => '_liberia',
        'LY' => '_libya',
        'LI' => '_liechtenstein',
        'LT' => '_lithuania',
        'LU' => '_luxembourg',
        'MO' => '_macau',
        'MK' => '_macedonia',
        'MG' => '_madagascar',
        'MW' => '_malawi',
        'MY' => '_malaysia',
        'MV' => '_maldives',
        'ML' => '_mali',
        'MT' => '_malta',
        'MH' => '_marshallIslands',
        'MQ' => '_martinique',
        'MR' => '_mauritania',
        'MU' => '_mauritius',
        'YT' => '_mayotte',
        'MX' => '_mexico',
        'FM' => '_micronesiaFederalStateOf',
        'MD' => '_moldovaRepublicOf',
        'MC' => '_monaco',
        'MN' => '_mongolia',
        'ME' => '_montenegro',
        'MS' => '_montserrat',
        'MA' => '_morocco',
        'MZ' => '_mozambique',
        'MM' => '_myanmar',
        'NA' => '_namibia',
        'NR' => '_nauru',
        'NP' => '_nepal',
        'NL' => '_netherlands',
        'NC' => '_newCaledonia',
        'NZ' => '_newZealand',
        'NI' => '_nicaragua',
        'NE' => '_niger',
        'NG' => '_nigeria',
        'NU' => '_niue',
        'NF' => '_norfolkIsland',
        'MP' => '_northernMarianaIslands',
        'NO' => '_norway',
        'OM' => '_oman',
        'PK' => '_pakistan',
        'PW' => '_palau',
        'PA' => '_panama',
        'PG' => '_papuaNewGuinea',
        'PY' => '_paraguay',
        'PE' => '_peru',
        'PH' => '_philippines',
        'PN' => '_pitcairnIsland',
        'PL' => '_poland',
        'PT' => '_portugal',
        'PR' => '_puertoRico',
        'QA' => '_qatar',
        'RE' => '_reunionIsland',
        'RO' => '_romania',
        'RU' => '_russianFederation',
        'RW' => '_rwanda',
        'BL' => '_saintBarthelemy',
        'SH' => '_saintHelena',
        'KN' => '_saintKittsAndNevis',
        'LC' => '_saintLucia',
        'MF' => '_saintMartin',
        'VC' => '_saintVincentAndTheGrenadines',
        'WS' => '_samoa',
        'SM' => '_sanMarino',
        'ST' => '_saoTomeAndPrincipe',
        'SA' => '_saudiArabia',
        'SN' => '_senegal',
        'RS' => '_serbia',
        'SC' => '_seychelles',
        'SL' => '_sierraLeone',
        'SG' => '_singapore',
        'SX' => '_sintMaarten',
        'SK' => '_slovakRepublic',
        'SI' => '_slovenia',
        'SB' => '_solomonIslands',
        'SO' => '_somalia',
        'ZA' => '_southAfrica',
        'GS' => '_southGeorgia',
        'SS' => '_southSudan',
        'ES' => '_spain',
        'LK' => '_sriLanka',
        'PS' => '_stateOfPalestine',
        'PM' => '_stPierreAndMiquelon',
        'SD' => '_sudan',
        'SR' => '_suriname',
        'SJ' => '_svalbardAndJanMayenIslands',
        'SZ' => '_swaziland',
        'SE' => '_sweden',
        'CH' => '_switzerland',
        'SY' => '_syrianArabRepublic',
        'TW' => '_taiwan',
        'TJ' => '_tajikistan',
        'TZ' => '_tanzania',
        'TH' => '_thailand',
        'TG' => '_togo',
        'TK' => '_tokelau',
        'TO' => '_tonga',
        'TT' => '_trinidadAndTobago',
        'TN' => '_tunisia',
        'TR' => '_turkey',
        'TM' => '_turkmenistan',
        'TC' => '_turksAndCaicosIslands',
        'TV' => '_tuvalu',
        'UG' => '_uganda',
        'UA' => '_ukraine',
        'AE' => '_unitedArabEmirates',
        'GB' => '_unitedKingdom',
        'US' => '_unitedStates',
        'UY' => '_uruguay',
        'UM' => '_uSMinorOutlyingIslands',
        'UZ' => '_uzbekistan',
        'VU' => '_vanuatu',
        'VE' => '_venezuela',
        'VN' => '_vietNam',
        'VG' => '_virginIslandsBritish',
        'VI' => '_virginIslandsUSA',
        'WF' => '_wallisAndFutunaIslands',
        'EH' => '_westernSahara',
        'YE' => '_yemen',
        'ZM' => '_zambia',
        'ZW' => '_zimbabwe'
    ];

    /**
     * @var Config data
     */
    private $config;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var DirectoryList
     */
    protected $_dir;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    protected $onitsukaCpssHelper;

    protected $storeId;

    /**
     * Order constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderItemRepositoryInterface $itemRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param CreditmemoRepository $creditMemoRepository
     * @param ShipmentRepository $shipmentRepository
     * @param InvoiceRepository $invoiceRepository
     * @param Logger $logger
     * @param ConsoleOutput $output
     * @param \OnitsukaTiger\NetsuiteOrderSync\Helper\Data $helperData
     * @param SourceMapping $mapping
     * @param DirectoryList $dir
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderItemRepositoryInterface $orderItemRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        CreditmemoRepository $creditMemoRepository,
        ShipmentRepository $shipmentRepository,
        InvoiceRepository $invoiceRepository,
        Logger $logger,
        ConsoleOutput $output,
        \OnitsukaTiger\NetsuiteOrderSync\Helper\Data $helperData,
        SourceMapping $mapping,
        DirectoryList $dir,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $onitsukaCpssHelper
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderItemRepository = $orderItemRepository;
        $this->localeDate = $localeDate;
        $this->shipmentRepository = $shipmentRepository;
        $this->scopeConfig = $scopeConfig;
        $this->_mapping = $mapping;
        $this->helperData = $helperData;
        $this->orderRepository = $orderRepository;
        $this->_dir = $dir;
        $this->onitsukaCpssHelper = $onitsukaCpssHelper;
        parent::__construct(
            $scopeConfig,
            $orderRepository,
            $customerRepository,
            $creditMemoRepository,
            $shipmentRepository,
            $invoiceRepository,
            $logger,
            $output
        );
    }

    protected function initService()
    {
        $options = [
            'connection_timeout' => 6000,
            'keep_alive' => true
        ];

        $this->service = new \NetSuite\NetSuiteService($this->getConfig(), $options);
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        if (empty($this->config)) {
            $rootDir = $this->_dir->getRoot();
            $logging = \trim($this->scopeConfig->getValue('netsuite/suitetalk/log'));
            $base = \trim($this->scopeConfig->getValue('netsuite/suitetalk/log_path'));
            $log_path = $rootDir . $base . date('ymd');
            if ($logging) {
                if (!file_exists($rootDir . $base)) {
                    mkdir($rootDir . $base);
                }
                if (!file_exists($log_path)) {
                    mkdir($log_path);
                }
            }

            $behaviorData = $this->getBehaviorData();

            if (!empty($behaviorData)) {
                $this->config = [
                    "endpoint" => \trim($behaviorData['netsuite_credentials_endpoint']),
                    "host"     => \trim($behaviorData['netsuite_credentials_host']),
                    "account"  => \trim($behaviorData['netsuite_credentials_account']),
                    "consumerKey" => \trim($behaviorData['netsuite_credentials_consumer_key']),
                    "consumerSecret" => \trim($behaviorData['netsuite_credentials_consumer_secret']),
                    "token" => \trim($behaviorData['netsuite_credentials_token']),
                    "tokenSecret" => \trim($behaviorData['netsuite_credentials_token_secret']),
                    "use_old_http_protocol_version" => \trim(
                        $behaviorData['netsuite_credentials_use_old_http_protocol_version']
                    ),
                    "logging" => $logging,
                    "log_path" => $log_path,
                ];
            } else {
                $this->config = [
                    "endpoint" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/endpoint')),
                    "host"     => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/host')),
                    "account"  => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/account')),
                    "consumerKey" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/consumerKey')),
                    "consumerSecret" => \trim(
                        $this->scopeConfig->getValue('firebear_importexport/netsuite/consumerSecret')
                    ),
                    "token" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/token')),
                    "tokenSecret" => \trim($this->scopeConfig->getValue('firebear_importexport/netsuite/tokenSecret')),
                    "logging" => $logging,
                    "log_path" => $log_path,
                ];
            }
        }
        return $this->config;
    }

    /**
     * @param $customizationTypeName
     * @return array
     */
    protected function getNetsuiteCustomFieldsMapping($customizationTypeName)
    {
        $behaviorData = $this->getBehaviorData();
        $netsuiteCustomEntityMapping = [];
        if (!empty($behaviorData['netsuite_attribute_map_export'])
            && isset($behaviorData['netsuite_attribute_map_export']['value'])
            && !empty($behaviorData['netsuite_attribute_map_system'])
            && isset($behaviorData['netsuite_attribute_map_system']['value'])) {
            $netsuiteAttributeMapExport
                = $behaviorData['netsuite_attribute_map_export']['value'];
            $netsuiteAttributeMapSystem
                = $behaviorData['netsuite_attribute_map_system']['value'];

            foreach ($netsuiteAttributeMapExport as $key => $value) {
                if (isset($netsuiteAttributeMapSystem[$key])) {
                    $netsuiteCustomEntityMapping[$value]
                        = $netsuiteAttributeMapSystem[$key];
                }
            }

            if (!empty($behaviorData['netsuite_attribute_map_replace'])
                && isset($behaviorData['netsuite_attribute_map_replace']['value'])) {
                $netsuiteAttributeMapReplace
                    = $behaviorData['netsuite_attribute_map_replace']['value'];
                foreach ($netsuiteAttributeMapExport as $key => $value) {
                    if (isset($netsuiteAttributeMapReplace[$key]) &&
                        !empty($netsuiteAttributeMapReplace[$key])
                    ) {
                        $this->customFieldReplaceData[$value] = $netsuiteAttributeMapReplace[$key];
                    }
                }
            }
        }
        return $netsuiteCustomEntityMapping;
    }

    /**
     * @param $data
     */
    protected function exportOrder($data)
    {
        /*$storeId = $data['store_id'];
        $storeIdValue = $this->storeId;*/
        $this->storeId = $data['store_id'];
        $storeId = $this->storeId;
        $entityId = $this->helperData->getNetsuiteInternalIdConfig('netsuite_entity_id', $storeId);
        $itemPriceInternalId = $this->helperData->getNetsuiteInternalIdConfig('item_price_internalId', $storeId);
        $itemPriceInternalType = $this->helperData->getNetsuiteInternalIdConfig('item_price_internalType', $storeId);
        $itemTaxCodeId = $this->helperData->getNetsuiteInternalIdConfig('item_taxcode_internalId', $storeId);
        $saleTaxCodeId = $this->helperData->getNetsuiteInternalIdConfig('netsuite_tax_code', $storeId);
        $customFormInternalid = $this->helperData->getNetsuiteInternalIdConfig('custom_form_id', $storeId);
        $customFormType = $this->helperData->getNetsuiteInternalIdConfig('custom_form_type', $storeId);
        $shippingMethodInternalId = $this->helperData->getNetsuiteInternalIdConfig('shipping_method_id', $storeId);
        $shipingItemInternalId = $this->helperData->getNetsuiteInternalIdConfig('netsuite_shipping_item', $storeId);
        $discountItemInternalId = $this->helperData->getNetsuiteInternalIdConfig('netsuite_discount_item', $storeId);
        $loyaltyDiscountItemInternalId = $this->helperData->getNetsuiteInternalIdConfig('netsuite_loyalty_discount_item', $storeId);
        $deparmentItem = $this->helperData->getNetsuiteInternalIdConfig('netsuite_deparment_id', $storeId);
        $source_code = $data['source_code'];
        /** @var Shipment $shipment */
        $shipment = $data['shipment'];

        if (empty($shipment->getExtensionAttributes()->getNetsuiteInternalId())) {
            $this->initService();
            $behaviorData = $this->getBehaviorData();
            //$customerInternalId = $this->getCustomerInternalId($data);
            $customerInternalId = $entityId;  // Got entity Id in System Config Admin
            $orderLocation = $this->_mapping->getNetSuiteLocation($source_code);

            if (empty($customerInternalId)) {
                $email = (!empty($data['email'])) ? $data['email'] : '';
                $errorMessage = __("Customer with email: %1 not found on the Netsuite.", $email);
                throw new \Magento\Framework\Exception\LocalizedException($errorMessage);
            }

            $so = new \NetSuite\Classes\SalesOrder();
            $so->entity = new \NetSuite\Classes\RecordRef();
            $so->entity->internalId = $customerInternalId;
            $so->externalId = $shipment->getIncrementId();
            $so->itemList = new \NetSuite\Classes\SalesOrderItemList();

            if (!empty($behaviorData['order_department_internal_id'] || !empty($deparmentItem))) {
                $department = new \NetSuite\Classes\RecordRef();
                $department->internalId = $behaviorData['order_department_internal_id'] ?: $deparmentItem;
                $so->department = $department;
            }

            $location = new \NetSuite\Classes\RecordRef();
            $location->externalId = $orderLocation;
            $so->location = $location;

            if (!empty($behaviorData['customer_lead_source_internal_id'])) {
                $leadSource = new \NetSuite\Classes\RecordRef();
                $leadSource->internalId = $behaviorData['customer_lead_source_internal_id'];
                $so->leadSource = $leadSource;
            }

            if (!empty($behaviorData['sales_discount_item_internal_id'])
                && !empty($data['discount_amount'])
                && ($data['discount_amount'] !== '0.0000')) {
                $discountItem = new \NetSuite\Classes\RecordRef();
                $discountItem->internalId = $behaviorData['sales_discount_item_internal_id'];
                $so->discountItem = $discountItem;
                $so->discountRate = $data['discount_amount'];
            }

            if (!empty($behaviorData['sales_tax_code_internal_id'])) {
                $shippingTaxCode = new \NetSuite\Classes\RecordRef();
                $shippingTaxCode->internalId = $behaviorData['sales_tax_code_internal_id'];
                $so->shippingTaxCode = $shippingTaxCode;
                $taxRate = $this->getOrderItemTaxPercent($data['items']);
                if ($taxRate) {
                    $taxItem = new \NetSuite\Classes\RecordRef();
                    $taxItem->internalId = $behaviorData['sales_tax_code_internal_id'];
                    $so->taxItem = $taxItem;
                    $so->taxRate = $taxRate;
                }
            }

            if (!empty($behaviorData['sales_tax_code_internal_id'])) {
                $shippingTaxCode = new \NetSuite\Classes\RecordRef();
                $shippingTaxCode->internalId = $behaviorData['sales_tax_code_internal_id'];
                $so->shippingTaxCode = $shippingTaxCode;
                $taxRate = $this->getOrderItemTaxPercent($data['items']);
                if ($taxRate) {
                    $taxItem = new \NetSuite\Classes\RecordRef();
                    $taxItem->internalId = $behaviorData['sales_tax_code_internal_id'];
                    $so->taxItem = $taxItem;
                    $so->taxRate = $taxRate;
                }
            }

            if (!empty($data['increment_id']) && $behaviorData['use_increment_id_instead_po_number']) {
                $so->otherRefNum = $data['increment_id'];
            } elseif (!empty($data['payment:po_number'])) {
                $so->otherRefNum = $data['payment:po_number'];
            }

            if (!empty($shippingMethodInternalId)) {
                $shippingMethod = new \NetSuite\Classes\RecordRef();
                $shippingMethod->internalId = $shippingMethodInternalId;
                $so->shipMethod = $shippingMethod;
            } else {
                $shippingMethod = $shipment->getOrder()->getShippingMethod();
                if (!empty($behaviorData['netsuite_shipping_map'])) {
                    foreach ($behaviorData['netsuite_shipping_map'] as $shippingMap) {
                        $method = $shippingMap['behavior_field_netsuite_shipping_map_shipping_methods'];
                        if ($method == $shippingMethod || $method . '_' . $method == $shippingMethod) {
                            $shippingMethod = new \NetSuite\Classes\RecordRef();
                            $shippingMethod->internalId = $shippingMap['behavior_field_netsuite_shipping_ns_shipping_methods'];
                            $so->shipMethod = $shippingMethod;
                        }
                    }
                }
            }

            $payment = $shipment->getOrder()->getPayment();
            $paymentMethod = $payment->getMethodInstance();
            $paymentMethodCode = $paymentMethod->getCode();
            if (!empty($behaviorData['netsuite_payment_map'])) {
                foreach ($behaviorData['netsuite_payment_map'] as $paymentMap) {
                    $method = $paymentMap['behavior_field_netsuite_payment_map_payment_methods'];
                    if ($method == $paymentMethodCode) {
                        $paymentMethod = new \NetSuite\Classes\RecordRef();
                        $paymentMethod->internalId = $paymentMap['behavior_field_netsuite_payment_ns_payment_methods'];
                        $so->paymentMethod = $paymentMethod;
                    }
                }
            }

            $customizationTypeName = \NetSuite\Classes\GetCustomizationType::transactionBodyCustomField;
            $netsuiteCustomFieldsMapping = $this->getNetsuiteCustomFieldsMapping($customizationTypeName);
            if (!empty($netsuiteCustomFieldsMapping)) {
                $customFieldList = new \NetSuite\Classes\CustomFieldList();
                foreach ($netsuiteCustomFieldsMapping as $exportAttribute => $systemAttribute) {
                    if (isset($data[$systemAttribute]) || isset($this->customFieldReplaceData[$exportAttribute])) {
                        $custentityField = new \NetSuite\Classes\StringCustomFieldRef();
                        $custentityField->scriptId = $exportAttribute;
                        $custentityField->value = isset($this->customFieldReplaceData[$exportAttribute]) ?
                            $this->customFieldReplaceData[$exportAttribute] : $data[$systemAttribute];

                        // Update format price 4 digits -> 2 digits
                        if ($exportAttribute =='custbody_mj_total_amount' || $exportAttribute =='custbody_mj_taxtotal' ||
                            $exportAttribute =='custcol_mj_unit_price' || $exportAttribute =='custcol_mj_tax_amount'
                        ) {
                            $custentityField->value = isset($this->customFieldReplaceData[$exportAttribute]) ?
                                $this->customFieldReplaceData[$exportAttribute] : $this->formatNumber($data[$systemAttribute]);
                        }

                        // update format order date
                        if ($exportAttribute =='custbody_mj_orderdate') {
                            $datetime = $data[$systemAttribute];
                            $timestamp =  strtotime($datetime);
                            $t = $this->localeDate->getConfigTimezone(
                                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                                $storeId
                            );
                            $utc = (new \DateTime("now", new \DateTimeZone($t)))->format('P');
                            $utc = str_replace(":", "", $utc);
                            $date = "/Date(" . $timestamp . $utc . ")/";
                            $custentityField->value = $date;
                        }
                        // update for shipping discount
                        if ($exportAttribute =='custbody_mj_shipping_discount') {
                            //                            $custentityField->value = ($data[$systemAttribute] == 0) ? $data[$systemAttribute] : (- $data[$systemAttribute]) ;
                            $custentityField->value = $this->formatNumber(0);
                        }
                        // update custbody_mj_voucherdiscount
                        if ($exportAttribute =='custbody_mj_voucherdiscount') {
                            $couponAttribute = $netsuiteCustomFieldsMapping['custbody_mj_vouchercode'];
                            $custentityField->value = (isset($data[$couponAttribute]) && $data[$couponAttribute]) ? $this->formatNumber(abs($data[$systemAttribute])) : 0.00;
                        }
                        // update custbody_mj_promodiscount
                        if ($exportAttribute =='custbody_mj_promodiscount') {
                            $total = $this->getProductDiscountTotal($data);
                            $custentityField->value = $this->formatNumber($total);
                        }

                        // update custbody_mj_bill_firstname & custbody_mj_bill_lastname
                        if ($exportAttribute =='custbody_mj_bill_firstname') {
                            $custentityField->value = isset($data['billing_address']['firstname']) ?
                                $data['billing_address']['firstname'] : $data[$systemAttribute];
                        }
                        if ($exportAttribute =='custbody_mj_bill_lastname') {
                            $custentityField->value = isset($data['billing_address']['lastname']) ?
                                $data['billing_address']['lastname'] : $data[$systemAttribute];
                        }
                        if ($exportAttribute =='custbody_mj_ship_firstname') {
                            $custentityField->value = isset($data['shipping_address']['firstname']) ?
                                $data['shipping_address']['firstname'] : $data[$systemAttribute];
                        }
                        if ($exportAttribute =='custbody_mj_ship_lastname') {
                            $custentityField->value = isset($data['shipping_address']['lastname']) ?
                                $data['shipping_address']['lastname'] : $data[$systemAttribute];
                        }

                        $customFieldList->customField[] = $custentityField;
                    }
                }

                // Add custom attribute for VN stores
                if (isset($data['store_id']) && in_array($data['store_id'], [8, 10])) {
                    $customFieldList->customField[] = $this->getCustbodyInvAvnSaleschannel();

                    // Add email
                    if (isset($data['email'])) {
                        $customFieldList->customField[] = $this->getCustbodyAvnEinvBillingEmail($data['email']);
                    }

                    // Add name
                    if (isset($data['firstname'])) {
                        $customFieldList->customField[] = $this->getCustbodyAvnEinvBillingName($data['firstname']);
                    }

                    // Add phone number
                    if (isset($data['phone'])) {
                        $customFieldList->customField[] = $this->getCustbodyAvnEinvBillingPhoneno($data['phone']);
                    }

                    // Add tax id
                    if (isset($data['company_tax_code']) && !empty($data['company_tax_code'])) {
                        $customFieldList->customField[] = $this->getCustbodyAvnEinvBillingVatno($data['company_tax_code']);
                    } elseif (isset($data['tax_id']) && !empty($data['tax_id'])) {
                        $customFieldList->customField[] = $this->getCustbodyAvnEinvBillingVatno($data['tax_id']);
                    } else {
                        $customFieldList->customField[] = $this->getCustbodyAvnEinvBillingVatno("");
                    }
                    // Add address
                    if (isset($data['shipping_address']) && !empty($data['shipping_address']) && isset($data['shipping_address']['street']) && !empty($data['shipping_address']['street'])) {
                        $customFieldList->customField[] = $this->getCustbodyAvnEinvBillingAdd($data['shipping_address']['street']);
                    }

                    $customFieldList->customField[] = $this->addVatInvoiceFieldPurchaserName($data);
                    //$customFieldList->customField[] = $this->addVatInvoiceFieldCompanyTaxCode($data);
                    /*$customFieldList->customField[] = $this->addVatInvoiceFieldCompanyName($data);
                    $customFieldList->customField[] = $this->addVatInvoiceFieldCustomerAddress($data);
                    $customFieldList->customField[] = $this->addVatInvoiceFieldCompanyEmailAddress($data);
                    $customFieldList->customField[] = $this->addVatInvoiceFieldCompanyEmailPhoneNumber($data);*/

                }

                if (!empty($customFieldList->customField)) {
                    $so->customFieldList = $customFieldList;
                }
            }

            $oItems = $shipment->getOrder()->getAllVisibleItems();
            $oItemArray = [];
            foreach ($oItems as $oItem) {
                $oItemArray[$oItem->getId()] = (int)$oItem->getQtyOrdered();
            }

            $orderItems = [];
            foreach ($data['items'] as $item) {
                $soi = new \NetSuite\Classes\SalesOrderItem();
                $soi->item = new \NetSuite\Classes\RecordRef();
                $soi->item->internalId = $item['internalId'];
                $soi->quantity = (int) $item['quantity'];
                $soi->grossAmt = (float)  $this->formatNumber($item['amount']);

                // Customize price and taxcode
                $price = new \NetSuite\Classes\RecordRef();
                $price->internalId = $itemPriceInternalId;
                $price->type = $itemPriceInternalType;
                $soi->price = $price;

                $taxCode = new \NetSuite\Classes\RecordRef();
                $taxCode->internalId = $itemTaxCodeId;
                $soi->taxCode = $taxCode;
                //endl

                // Customize customFieldList into Itemlist
                if (!empty($netsuiteCustomFieldsMapping)) {
                    $customFieldListItem = new \NetSuite\Classes\CustomFieldList();
                    foreach ($netsuiteCustomFieldsMapping as $exportAttribute => $systemAttribute) {
                        if (isset($item['dataItem'][$systemAttribute]) || isset($this->customFieldReplaceData[$exportAttribute])) {
                            if (strpos($systemAttribute, 'item:') !== false) {
                                $custentityFieldItem = new \NetSuite\Classes\StringCustomFieldRef();
                                $custentityFieldItem->scriptId = $exportAttribute;
                                $custentityFieldItem->value = isset($this->customFieldReplaceData[$exportAttribute]) ?
                                    $this->customFieldReplaceData[$exportAttribute] : $item['dataItem'][$systemAttribute];

                                if ($exportAttribute =='custcol_mj_unit_price' || $exportAttribute =='custcol_mj_tax_amount' || $exportAttribute =='lineitemdiscountAmt') {
                                    $custentityFieldItem->value = isset($this->customFieldReplaceData[$exportAttribute]) ?
                                        $this->customFieldReplaceData[$exportAttribute] : $this->formatNumber($item['dataItem'][$systemAttribute]);
                                }

                                if ($exportAttribute =='custcol_mj_order_line_id') {
                                    $parentItemId = isset($this->customFieldReplaceData[$exportAttribute]) ?
                                        $this->customFieldReplaceData[$exportAttribute] : ($item['dataItem'][$systemAttribute]);
                                    $simpleId = $this->getSimpleItemId($parentItemId);
                                    $custentityFieldItem->value = $simpleId;
                                }
                                $customFieldListItem->customField[] = $custentityFieldItem;
                            }
                        }
                    }
                }
                if (!empty($customFieldListItem->customField)) {
                    $soi->customFieldList = $customFieldListItem;
                }
                //endl
                $orderItems[] = $soi;

                // Discount line Item
                $orderItems[] = $this->getDiscountItem($discountItemInternalId, $saleTaxCodeId, $item, $itemPriceInternalId, $itemPriceInternalType, $oItemArray);

                // Point Item
                $orderItems[] = $this->getPointDiscount($loyaltyDiscountItemInternalId, $saleTaxCodeId, $item, $itemPriceInternalId, $itemPriceInternalType, $oItemArray);
            }

            // Subtotal Item
            $orderItems[] = $this->getSubtotalItem();

            // Shipping Item
            $orderItems[] = $this->getShippingItem($shipingItemInternalId, $data['shipping_amount'], $itemPriceInternalId, $itemPriceInternalType, $itemTaxCodeId);

            $so->itemList->item = $orderItems;

            $customForm = new \NetSuite\Classes\RecordRef();
            $customForm->internalId = $customFormInternalid;
            $customForm->type = $customFormType;
            $so->customForm = $customForm;

            $so->tranDate = date(DATE_ATOM);

            if (!empty($data['billing_address'])) {
                $so->billingAddress = $this->prepareAddress($data['billing_address']);
            }

            if (!empty($data['shipping_address'])) {
                $so->shippingAddress = $this->prepareAddress($data['shipping_address']);
            }

            $request = new \NetSuite\Classes\AddRequest();
            $request->record = $so;
            $addResponse = $this->service->add($request);
            if ($addResponse->writeResponse->status->isSuccess) {
                $successMessage = __(
                    'The order  %1 was successfully imported to Netsuite',
                    $data['increment_id']
                );
                $this->addLogWriteln($successMessage, $this->output);
                $internalId = $addResponse->writeResponse->baseRef->internalId;

                $attrExtensionShipment = $shipment->getExtensionAttributes();
                if (!$attrExtensionShipment->getNetsuiteInternalId()) {
                    $attrExtensionShipment->setNetsuiteInternalId($internalId);
                }
                $this->shipmentRepository->save($shipment);

                $payment = $shipment->getOrder()->getPayment();
                $paymentMethod = $payment->getMethodInstance();
                $paymentMethodCode = $paymentMethod->getCode();
                if (!empty($behaviorData['generate_customer_deposite'])) {
                    $this->createCustomerDeposite($internalId, $customerInternalId, $paymentMethodCode);
                }
            } else {
                $errorMessage = __(
                    'The Order not exported to the Netsuite.' .
                    ' Increment id: %1. Message: %2',
                    [
                        $data['increment_id'],
                        $addResponse->writeResponse->status->statusDetail[0]->message
                    ]
                );
                $this->addLogWriteln($errorMessage, $this->output, 'error');
            }
        }
    }

    /**
     * @param $data
     * @return \NetSuite\Classes\Address
     */
    protected function prepareAddress($data)
    {
        $netsuiteAddress = new \NetSuite\Classes\Address();
        $streets = explode("\n", str_replace(["\r\n", "\n", "\r"], "\n", $data['street']));
        $netsuiteAddress->addr1 = $streets[0];
        $netsuiteAddress->addr2 = '';
        if (count($streets) > 1) {
            $netsuiteAddress->addr2 = $streets[1];
        }
        $netsuiteAddress->addrPhone = $data['phone'];

        if (isset($this->countryMapping[$data['country']])) {
            $netsuiteAddress->country = $this->countryMapping[$data['country']];
        }

        $netsuiteAddress->city = $data['city'];
        $netsuiteAddress->state = $data['state'];
        $netsuiteAddress->zip = $data['zip'];

        return $netsuiteAddress;
    }

    /**
     * Generate Discount Item field
     * @param $internalId
     * @param $saleTaxCodeId
     * @param $data
     * @param $itemPriceInternalId
     * @param $itemPriceInternalType
     * @return \NetSuite\Classes\SalesOrderItem
     */
    private function getDiscountItem($internalId, $saleTaxCodeId, $data, $itemPriceInternalId, $itemPriceInternalType, $oItemArray)
    {
        $shippedQty = (int)$data['quantity'];
        $orderQty = (!empty($oItemArray) && array_key_exists($data['dataItem']['item:item_id'], $oItemArray)) ? $oItemArray[$data['dataItem']['item:item_id']] : 0;

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/netSuiteOrderDiscount.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Discount Log Start============================');
        $logger->info('Discount Item Data: ' . print_r($data, true));
        $usePoint = $data['dataItem']['item:used_point'] ?? 0;
        $logger->info('Use Point: ' . $usePoint);
        $actualPoint = 0;
        if ($usePoint > 0) {
            $logger->info('=====Inside If Start=====');
            $logger->info('Order QTY: ' . $orderQty);
            $logger->info('Shipped QTY: ' . $shippedQty);
            $actualPoint = ($usePoint / $orderQty) * $shippedQty;
            $logger->info('Actual Point: ' . $actualPoint);
            $logger->info('=====Inside If Start=====');
        }

        $usedPoints = abs($actualPoint);
        $logger->info('Dis Amount: ' . $data['discount_amount']);
        $logger->info('Used Point: ' . $usedPoints);
        $lessCpssDiscount = $data['discount_amount'] - $usedPoints;
        $logger->info('Discount After minus point discount: ' . $lessCpssDiscount);

        $discount = abs($lessCpssDiscount);
        $grossAmt = (float) $this->formatNumber($discount);

        $logger->info('Discount: ' . $discount);
        $logger->info('Gros Amount: ' . $grossAmt);
        $logger->info('==========================Discount Log End============================');

        $soi = new \NetSuite\Classes\SalesOrderItem();
        $soi->item = new \NetSuite\Classes\RecordRef();
        $soi->item->internalId = $internalId;
        $soi->grossAmt = ($grossAmt) ? (-$grossAmt) : 0;
        $taxCode = new \NetSuite\Classes\RecordRef();
        $taxCode->internalId = $saleTaxCodeId;
        $soi->taxCode = $taxCode;
        $soi->price = new \NetSuite\Classes\RecordRef();
        $soi->price->internalId = $itemPriceInternalId;
        $soi->price->type = $itemPriceInternalType;

        $soi->customFieldList = new \NetSuite\Classes\CustomFieldList();
        $soi->customFieldList->customField[] = $this->getCsegTxnBrand();

        return $soi;
    }

    /**
     * Generate Discount Item field
     * @param $internalId
     * @param $saleTaxCodeId
     * @param $data
     * @param $itemPriceInternalId
     * @param $itemPriceInternalType
     * @return \NetSuite\Classes\SalesOrderItem
     */
    private function getPointDiscount($internalId, $saleTaxCodeId, $data, $itemPriceInternalId, $itemPriceInternalType, $oItemArray)
    {
        $shippedQty = (int)$data['quantity'];
        $orderQty = (!empty($oItemArray) && array_key_exists($data['dataItem']['item:item_id'], $oItemArray)) ? $oItemArray[$data['dataItem']['item:item_id']] : 0;

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/netSuiteOrderPointDiscount.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Point Discount Log Start============================');
        $logger->info('Point Discount Item Data: ' . print_r($data, true));
        $usePoint = $data['dataItem']['item:used_point'] ?? 0;
        $logger->info('Use Point: ' . $usePoint);
        $actualPoint = 0;
        if ($usePoint > 0) {
            $logger->info('=====Inside If Start=====');
            $logger->info('Order QTY: ' . $orderQty);
            $logger->info('Shipped QTY: ' . $shippedQty);
            $actualPoint = ($usePoint / $orderQty) * $shippedQty;
            $logger->info('Actual Point: ' . $actualPoint);
            $logger->info('=====Inside If Start=====');
        }

        $usedPoints = abs($actualPoint);
        $grossAmt = (float) $this->formatNumber($usedPoints);
        $logger->info('Used Points: ' . $usedPoints);
        $logger->info('Gros Amount: ' . $grossAmt);
        $logger->info('==========================Point Discount Log End============================');

        $soi = new \NetSuite\Classes\SalesOrderItem();
        $soi->item = new \NetSuite\Classes\RecordRef();
        $soi->item->internalId = $internalId;
        $soi->grossAmt = ($grossAmt) ? (-$grossAmt) : 0;
        $taxCode = new \NetSuite\Classes\RecordRef();
        $taxCode->internalId = $saleTaxCodeId;
        $soi->taxCode = $taxCode;
        $soi->price = new \NetSuite\Classes\RecordRef();
        $soi->price->internalId = $itemPriceInternalId;
        $soi->price->type = $itemPriceInternalType;

        $soi->customFieldList = new \NetSuite\Classes\CustomFieldList();
        $soi->customFieldList->customField[] = $this->getCsegTxnBrand();

        return $soi;
    }

    /**
     * Generate Shipping Item field
     * @param $internalId
     * @param $shippingFee
     * @param $itemPriceInternalId
     * @param $itemPriceInternalType
     * @param $itemTaxCodeId
     * @return SalesOrderItem
     */
    private function getShippingItem($internalId, $shippingFee, $itemPriceInternalId, $itemPriceInternalType, $itemTaxCodeId)
    {
        $soi = new \NetSuite\Classes\SalesOrderItem();
        $soi->item = new \NetSuite\Classes\RecordRef();
        $soi->item->internalId = $internalId;
        $soi->grossAmt = (float) $this->formatNumber($shippingFee);
        $taxCode = new \NetSuite\Classes\RecordRef();
        $taxCode->internalId = $itemTaxCodeId;
        $soi->taxCode = $taxCode;
        $soi->price = new \NetSuite\Classes\RecordRef();
        $soi->price->internalId = $itemPriceInternalId;
        $soi->price->type = $itemPriceInternalType;

        $soi->customFieldList = new \NetSuite\Classes\CustomFieldList();
        $soi->customFieldList->customField[] = $this->getCsegTxnBrand();

        return $soi;
    }

    /**
     * Generate additional tax item field
     * @return \NetSuite\Classes\SalesOrderItem
     */
    private function getSubtotalItem()
    {
        $soi = new \NetSuite\Classes\SalesOrderItem();
        $soi->item = new \NetSuite\Classes\RecordRef();
        $soi->item->internalId = -2;
        $soi->customFieldList = new \NetSuite\Classes\CustomFieldList();
        $soi->customFieldList->customField[] = $this->getCsegTxnBrand();

        return $soi;
    }

    private function getCsegTxnBrand()
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = \OnitsukaTiger\NetSuite\Model\SuiteTalk::SCRIPT_ID_CSEG_TXN_BRAND;
        $field->value = \OnitsukaTiger\NetSuite\Model\SuiteTalk::SCRIPT_ID_CSEG_TXN_BRAND_VALUE;

        return $field;
    }

    private function getCustbodyInvAvnSaleschannel()
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_inv_avn_saleschannel";
        $field->value = "E-commerce";

        return $field;
    }

    private function getCustbodyAvnEinvBillingPhoneno($phone)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_einv_billing_phoneno";
        $field->value = $phone;

        return $field;
    }

    private function getCustbodyAvnEinvBillingEmail($email)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_einv_billing_email";
        $field->value = $email;

        return $field;
    }

    private function getCustbodyAvnEinvBillingName($name)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_einv_billing_name";
        $field->value = $name;

        return $field;
    }

    private function getCustbodyAvnEinvBillingAdd($address)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_einv_billing_add";
        $field->value = $address;

        return $field;
    }

    private function getCustbodyAvnEinvBillingVatno($taxId)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_einv_billing_vatno";
        $field->value = $taxId;

        return $field;
    }

    private function addVatInvoiceFieldPurchaserName($data)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_einv_purchaser_name";
        $field->value = $data['purchaser_name'] ?? '';

        return $field;
    }
    private function addVatInvoiceFieldCompanyName($data)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_einv_company_name";
        $field->value = $data['company_name'] ?? '';

        return $field;
    }

    private function addVatInvoiceFieldCustomerAddress($data)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_einv_customer_address";
        $field->value = $data['customer_address'] ?? '';

        return $field;
    }

    private function addVatInvoiceFieldCompanyTaxCode($data)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_einv_company_tax_code";
        $field->value = $data['company_tax_code'] ?? '';

        return $field;
    }

    private function addVatInvoiceFieldCompanyEmailAddress($data)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_company_email_address";
        $field->value = $data['company_email_address'] ?? '';

        return $field;
    }

    private function addVatInvoiceFieldCompanyEmailPhoneNumber($data)
    {
        $field = new \NetSuite\Classes\StringCustomFieldRef();
        $field->scriptId = "custbody_avn_einv_company_phone_number";
        $field->value = $data['company_phone_number'] ?? '';

        return $field;
    }

    /**
     * format number
     * @param $number
     * @return string
     */
    private function formatNumber($number)
    {
        return number_format(round($number, 2, PHP_ROUND_HALF_UP), 2, '.', '');
    }

    /**
     * Get total item discount
     * @param $data
     */
    private function getProductDiscountTotal($data)
    {
        $total = 0;
        foreach ($data['items'] as $item) {
            $dataItem = $item['dataItem'];
            $orgPrice = isset($dataItem['item:original_price']) ? $dataItem['item:original_price'] : 0;
            $price = isset($dataItem['item:price_incl_tax']) ? $dataItem['item:price_incl_tax'] : 0;
            $total += ($orgPrice - $price)*$dataItem['item:qty_ordered'];
        }
        return $total;
    }

    protected function getSimpleItemId($id)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('parent_item_id', $id)->create();

        $orderItems = $this->orderItemRepository->getList($searchCriteria)->getItems();
        foreach ($orderItems as $item) {
            return $item->getItemId();
        }
    }
}
