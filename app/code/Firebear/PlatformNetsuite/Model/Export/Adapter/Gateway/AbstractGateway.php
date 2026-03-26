<?php
/**
 * Copyright © Firebear Studio, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Firebear\PlatformNetsuite\Model\Export\Adapter\Gateway;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use NetSuite\Classes\RecordType;

/**
 * Netsuite abstract gateway
 */
class AbstractGateway
{
    /**
     * @var \NetSuite\NetSuiteService
     */
    protected $service;

    /**
     * @var array
     */
    protected $countryMapping = [
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
        'VN' => '_vietnam',
        'VG' => '_virginIslandsBritish',
        'VI' => '_virginIslandsUSA',
        'WF' => '_wallisAndFutunaIslands',
        'EH' => '_westernSahara',
        'YE' => '_yemen',
        'ZM' => '_zambia',
        'ZW' => '_zimbabwe'
    ];

    /**
     * @var array
     */
    protected $customFieldReplaceData;

    /**
     * @var array
     */
    protected $customFieldData;

    /**
     * @var array
     */
    protected $customFieldOptionData;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Config data
     */
    private $config;

    /**
     * @var array
     */
    private $behaviorData = [];

    /**
     * AbstractGateway constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $config
     */
    protected function initService()
    {
        $options = [
            'connection_timeout' => 12000,
            'keep_alive' => true
        ];

        $config = $this->getConfig();

        if (!empty($config['use_old_http_protocol_version'])) {
            $options['stream_context'] = stream_context_create(
                ['http' => ['protocol_version' => 1.0]]
            );
        }

        $this->service = new \NetSuite\NetSuiteService($config, $options);
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        if (empty($this->config)) {

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
                    )
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
                    "use_old_http_protocol_version" => \trim(
                        $this->scopeConfig->getValue(
                            'firebear_importexport/netsuite/use_old_http_protocol_version'
                        )
                    )
                ];
            }
        }
        return $this->config;
    }

    /**
     * Set behavior data
     *
     * @param $data
     *
     * @return $this
     */
    public function setBehaviorData($data)
    {
        $this->behaviorData = $data;

        return $this;
    }

    /**
     * Get behavior data
     *
     * @return array
     */
    public function getBehaviorData()
    {
        return $this->behaviorData;
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

            if (!empty($netsuiteCustomEntityMapping)) {
                $customFieldsData = $this->getNetsuiteCustomFieldData($customizationTypeName);
                foreach ($netsuiteCustomEntityMapping as $exportAttribute => $systemAttribute) {
                    if (isset($customFieldsData[$exportAttribute])) {
                        $detailCustomItemField = $this->getNetsuiteCustomFieldById($customFieldsData[$exportAttribute]);
                        if (isset($detailCustomItemField['selectRecordType'])) {
                            $listInternalId = $detailCustomItemField['selectRecordType']->internalId;
                            $this->initNetsuiteCustomFieldOptionData($exportAttribute, $listInternalId);
                        }
                    }
                }
            }
        }
        return $netsuiteCustomEntityMapping;
    }

    /**
     * @param $exportAttribute
     * @param $internalId
     */
    private function initNetsuiteCustomFieldOptionData($exportAttribute, $internalId)
    {
        $getRequest = new \NetSuite\Classes\GetRequest();
        $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
        $getRequest->baseRef->internalId = $internalId;
        $getRequest->baseRef->type = RecordType::customList;
        $getResponse = $this->service->get($getRequest);
        $record = $getResponse->readResponse->record;
        if ($record) {
            $customValueList = $record->customValueList;
            $options = $customValueList->customValue;
            foreach ($options as $option) {
                $this->customFieldOptionData[$exportAttribute][$option->value] = $option->valueId;
            }
        }
    }

    /**
     * @param $internalId
     * @return array
     */
    private function getNetsuiteCustomFieldById($internalId)
    {
        $getRequest = new \NetSuite\Classes\GetRequest();
        $getRequest->baseRef = new \NetSuite\Classes\RecordRef();
        $getRequest->baseRef->internalId = $internalId;
        $getRequest->baseRef->type = \NetSuite\Classes\GetCustomizationType::entityCustomField;
        $detailCustomItemField = (array)$this->service->get($getRequest)->readResponse->record;
        return $detailCustomItemField;
    }

    /**
     * @param $customizationTypeName
     * @return mixed
     */
    private function getNetsuiteCustomFieldData($customizationTypeName)
    {
        if (!isset($this->customFieldData[$customizationTypeName])) {
            $customFields = $this->getCustomFieldsByTypeName($customizationTypeName);
            foreach ($customFields as $customField) {
                $this->customFieldData[$customizationTypeName][$customField->scriptId] = $customField->internalId;
            }
        }
        return $this->customFieldData[$customizationTypeName];
    }

    /**
     * Get all item custom fields
     *
     * @param $customizationTypeName
     * @return array
     */
    private function getCustomFieldsByTypeName($customizationTypeName)
    {
        $getCustomizationIdRequest = new \NetSuite\Classes\GetCustomizationIdRequest();
        $customizationType = new \NetSuite\Classes\CustomizationType();
        $customizationType->getCustomizationType = $customizationTypeName;
        $getCustomizationIdRequest->customizationType = $customizationType;
        $getCustomizationIdRequest->includeInactives = false;
        try {
            $itemCustomFields = $this->service
                ->getCustomizationId($getCustomizationIdRequest)
                ->getCustomizationIdResult
                ->customizationRefList
                ->customizationRef;
        } catch (Exception $e) {
            return [];
        }
        return $itemCustomFields;
    }

}
