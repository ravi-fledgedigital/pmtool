<?php
/**
 * Copyright © Firebear Studio, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Firebear\PlatformNetsuite\Model\Import\Source\Gateway;

use NetSuite\Classes\GetRequest;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;

/**
 * Netsuite abstract gateway
 */
class AbstractGateway
{
    const CACHE_TAG = 'config_scopes';
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var Search Id
     */
    protected $searchId;

    /**
     * @var \NetSuite\NetSuiteService
     */
    protected $service;

    /**
     * @var array
     */
    protected $countryMapping = [
        '_afghanistan' => 'AF',
        '_alandIslands' => 'AX',
        '_albania' => 'AL',
        '_algeria' => 'DZ',
        '_americanSamoa' => 'AS',
        '_andorra' => 'AD',
        '_angola' => 'AO',
        '_anguilla' => 'AI',
        '_antarctica' => 'AQ',
        '_antiguaAndBarbuda' => 'AG',
        '_argentina' => 'AR',
        '_armenia' => 'AM',
        '_aruba' => 'AW',
        '_australia' => 'AU',
        '_austria' => 'AT',
        '_azerbaijan' => 'AZ',
        '_bahamas' => 'BS',
        '_bahrain' => 'BH',
        '_bangladesh' => 'BD',
        '_barbados' => 'BB',
        '_belarus' => 'BY',
        '_belgium' => 'BE',
        '_belize' => 'BZ',
        '_benin' => 'BJ',
        '_bermuda' => 'BM',
        '_bhutan' => 'BT',
        '_bolivia' => 'BO',
        '_bonaireSaintEustatiusAndSaba' => 'BQ',
        '_bosniaAndHerzegovina' => 'BA',
        '_botswana' => 'BW',
        '_bouvetIsland' => 'BV',
        '_brazil' => 'BR',
        '_britishIndianOceanTerritory' => 'IO',
        '_bruneiDarussalam' => 'BN',
        '_bulgaria' => 'BG',
        '_burkinaFaso' => 'BF',
        '_burundi' => 'BI',
        '_cambodia' => 'KH',
        '_cameroon' => 'CM',
        '_canada' => 'CA',
        '_canaryIslands' => 'IC',
        '_capeVerde' => 'CV',
        '_caymanIslands' => 'KY',
        '_centralAfricanRepublic' => 'CF',
        '_ceutaAndMelilla' => 'EA',
        '_chad' => 'TD',
        '_chile' => 'CL',
        '_china' => 'CN',
        '_christmasIsland' => 'CX',
        '_cocosKeelingIslands' => 'CC',
        '_colombia' => 'CO',
        '_comoros' => 'KM',
        '_congoDemocraticPeoplesRepublic' => 'CD',
        '_congoRepublicOf' => 'CG',
        '_cookIslands' => 'CK',
        '_costaRica' => 'CR',
        '_coteDIvoire' => 'CI',
        '_croatiaHrvatska' => 'HR',
        '_cuba' => 'CU',
        '_curacao' => 'CW',
        '_cyprus' => 'CY',
        '_czechRepublic' => 'CZ',
        '_denmark' => 'DK',
        '_djibouti' => 'DJ',
        '_dominica' => 'DM',
        '_dominicanRepublic' => 'DO',
        '_eastTimor' => 'TP',
        '_ecuador' => 'EC',
        '_egypt' => 'EG',
        '_elSalvador' => 'SV',
        '_equatorialGuinea' => 'GQ',
        '_eritrea' => 'ER',
        '_estonia' => 'EE',
        '_ethiopia' => 'ET',
        '_falklandIslands' => 'FK',
        '_faroeIslands' => 'FO',
        '_fiji' => 'FJ',
        '_finland' => 'FI',
        '_france' => 'FR',
        '_frenchGuiana' => 'GF',
        '_frenchPolynesia' => 'PF',
        '_frenchSouthernTerritories' => 'TF',
        '_gabon' => 'GA',
        '_gambia' => 'GM',
        '_georgia' => 'GE',
        '_germany' => 'DE',
        '_ghana' => 'GH',
        '_gibraltar' => 'GI',
        '_greece' => 'GR',
        '_greenland' => 'GL',
        '_grenada' => 'GD',
        '_guadeloupe' => 'GP',
        '_guam' => 'GU',
        '_guatemala' => 'GT',
        '_guernsey' => 'GG',
        '_guinea' => 'GN',
        '_guineaBissau' => 'GW',
        '_guyana' => 'GY',
        '_haiti' => 'HT',
        '_heardAndMcDonaldIslands' => 'HM',
        '_holySeeCityVaticanState' => 'VA',
        '_honduras' => 'HN',
        '_hongKong' => 'HK',
        '_hungary' => 'HU',
        '_iceland' => 'IS',
        '_india' => 'IN',
        '_indonesia' => 'ID',
        '_iranIslamicRepublicOf' => 'IR',
        '_iraq' => 'IQ',
        '_ireland' => 'IE',
        '_isleOfMan' => 'IM',
        '_israel' => 'IL',
        '_italy' => 'IT',
        '_jamaica' => 'JM',
        '_japan' => 'JP',
        '_jersey' => 'JE',
        '_jordan' => 'JO',
        '_kazakhstan' => 'KZ',
        '_kenya' => 'KE',
        '_kiribati' => 'KI',
        '_koreaDemocraticPeoplesRepublic' => 'KP',
        '_koreaRepublicOf' => 'KR',
        '_kosovo' => 'XK',
        '_kuwait' => 'KW',
        '_kyrgyzstan' => 'KG',
        '_laoPeoplesDemocraticRepublic' => 'LA',
        '_latvia' => 'LV',
        '_lebanon' => 'LB',
        '_lesotho' => 'LS',
        '_liberia' => 'LR',
        '_libya' => 'LY',
        '_liechtenstein' => 'LI',
        '_lithuania' => 'LT',
        '_luxembourg' => 'LU',
        '_macau' => 'MO',
        '_macedonia' => 'MK',
        '_madagascar' => 'MG',
        '_malawi' => 'MW',
        '_malaysia' => 'MY',
        '_maldives' => 'MV',
        '_mali' => 'ML',
        '_malta' => 'MT',
        '_marshallIslands' => 'MH',
        '_martinique' => 'MQ',
        '_mauritania' => 'MR',
        '_mauritius' => 'MU',
        '_mayotte' => 'YT',
        '_mexico' => 'MX',
        '_micronesiaFederalStateOf' => 'FM',
        '_moldovaRepublicOf' => 'MD',
        '_monaco' => 'MC',
        '_mongolia' => 'MN',
        '_montenegro' => 'ME',
        '_montserrat' => 'MS',
        '_morocco' => 'MA',
        '_mozambique' => 'MZ',
        '_myanmar' => 'MM',
        '_namibia' => 'NA',
        '_nauru' => 'NR',
        '_nepal' => 'NP',
        '_netherlands' => 'NL',
        '_newCaledonia' => 'NC',
        '_newZealand' => 'NZ',
        '_nicaragua' => 'NI',
        '_niger' => 'NE',
        '_nigeria' => 'NG',
        '_niue' => 'NU',
        '_norfolkIsland' => 'NF',
        '_northernMarianaIslands' => 'MP',
        '_norway' => 'NO',
        '_oman' => 'OM',
        '_pakistan' => 'PK',
        '_palau' => 'PW',
        '_panama' => 'PA',
        '_papuaNewGuinea' => 'PG',
        '_paraguay' => 'PY',
        '_peru' => 'PE',
        '_philippines' => 'PH',
        '_pitcairnIsland' => 'PN',
        '_poland' => 'PL',
        '_portugal' => 'PT',
        '_puertoRico' => 'PR',
        '_qatar' => 'QA',
        '_reunionIsland' => 'RE',
        '_romania' => 'RO',
        '_russianFederation' => 'RU',
        '_rwanda' => 'RW',
        '_saintBarthelemy' => 'BL',
        '_saintHelena' => 'SH',
        '_saintKittsAndNevis' => 'KN',
        '_saintLucia' => 'LC',
        '_saintMartin' => 'MF',
        '_saintVincentAndTheGrenadines' => 'VC',
        '_samoa' => 'WS',
        '_sanMarino' => 'SM',
        '_saoTomeAndPrincipe' => 'ST',
        '_saudiArabia' => 'SA',
        '_senegal' => 'SN',
        '_serbia' => 'RS',
        '_seychelles' => 'SC',
        '_sierraLeone' => 'SL',
        '_singapore' => 'SG',
        '_sintMaarten' => 'SX',
        '_slovakRepublic' => 'SK',
        '_slovenia' => 'SI',
        '_solomonIslands' => 'SB',
        '_somalia' => 'SO',
        '_southAfrica' => 'ZA',
        '_southGeorgia' => 'GS',
        '_southSudan' => 'SS',
        '_spain' => 'ES',
        '_sriLanka' => 'LK',
        '_stateOfPalestine' => 'PS',
        '_stPierreAndMiquelon' => 'PM',
        '_sudan' => 'SD',
        '_suriname' => 'SR',
        '_svalbardAndJanMayenIslands' => 'SJ',
        '_swaziland' => 'SZ',
        '_sweden' => 'SE',
        '_switzerland' => 'CH',
        '_syrianArabRepublic' => 'SY',
        '_taiwan' => 'TW',
        '_tajikistan' => 'TJ',
        '_tanzania' => 'TZ',
        '_thailand' => 'TH',
        '_togo' => 'TG',
        '_tokelau' => 'TK',
        '_tonga' => 'TO',
        '_trinidadAndTobago' => 'TT',
        '_tunisia' => 'TN',
        '_turkey' => 'TR',
        '_turkmenistan' => 'TM',
        '_turksAndCaicosIslands' => 'TC',
        '_tuvalu' => 'TV',
        '_uganda' => 'UG',
        '_ukraine' => 'UA',
        '_unitedArabEmirates' => 'AE',
        '_unitedKingdom' => 'GB',
        '_unitedStates' => 'US',
        '_uruguay' => 'UY',
        '_uSMinorOutlyingIslands' => 'UM',
        '_uzbekistan' => 'UZ',
        '_vanuatu' => 'VU',
        '_venezuela' => 'VE',
        '_vietnam' => 'VN',
        '_virginIslandsBritish' => 'VG',
        '_virginIslandsUSA' => 'VI',
        '_wallisAndFutunaIslands' => 'WF',
        '_westernSahara' => 'EH',
        '_yemen' => 'YE',
        '_zambia' => 'ZM',
        '_zimbabwe' => 'ZW'
    ];

    /**
     * Order constructor.
     * @param \Magento\Framework\App\CacheInterface $cache
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache
    ) {
        $this->cache = $cache;
    }

    /**
     * @param null $config
     * @return array
     */
    public function uploadPartSource($config = null)
    {
        $page = $config['page'];
        $savedSearchId = $config['saved_search_id'];
        $items = [];
        $this->initService($config);
        if (isset($config['netsuite_internal_id']) && $config['netsuite_internal_id']) {
            $items = $this->entityUpdateRequestHandler($config['netsuite_entity_type'], $config['netsuite_internal_id']);
        } else {
            $cachedSearchId = $this->getSearchId();

            if ($cachedSearchId && $page > 1) {
                $request = new \NetSuite\Classes\SearchMoreWithIdRequest();
                $request->searchId = $cachedSearchId;
                $request->pageIndex = $page;
                $searchResponse = $this->service->searchMoreWithId($request);
                if ($searchResponse->searchResult->status->isSuccess) {
                    $items = $this->prepareResult($searchResponse->searchResult->searchRowList->searchRow);
                } else {
                    $this->setSearchId(null);
                }
            } else {
                $this->service->setSearchPreferences(false, 20);
                $search = new \NetSuite\Classes\TransactionSearchAdvanced();
                $search->savedSearchId = $savedSearchId;
                $request = new \NetSuite\Classes\SearchRequest();
                $request->searchRecord = $search;
                $searchResponse = $this->service->search($request);
                if ($searchResponse->searchResult->status->isSuccess) {
                    $searchId = $searchResponse->searchResult->searchId;
                    $this->setSearchId($searchId);
                    $items = $this->prepareResult($searchResponse->searchResult->searchRowList->searchRow);
                } else {
                    $this->setSearchId(null);
                }
            }
        }
        return $items;
    }

    /**
     * @param $items
     * @return array
     */
    protected function prepareResult($items)
    {
        $result = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                $basicRow = $item->basic;
                $data = $this->convertResultToArray($basicRow);
                if (isset($data['internalId']) && isset($data['internalId']['internalId'])) {
                    $data['internalId'] = $data['internalId']['internalId'];
                }
                $result[] = $data;
            }
        }
        return $result;
    }

    /**
     * @param $data
     * @return array
     */
    protected function convertResultToArray($data)
    {
        $result = [];
        if (is_object($data)) {
            $objectProperties = $data::$paramtypesmap;
            foreach ($objectProperties as $objectProperty => $type) {
                if ($objectProperty == 'searchValue') {
                    $result = $this->convertResultToArray($data->$objectProperty);
                } else {
                    $result[$objectProperty] = $this->convertResultToArray($data->$objectProperty);
                }
            }
        } elseif (is_array($data)) {
            foreach ($data as $item) {
                $result = $this->convertResultToArray($item);
            }
        } else {
            $result = $data;
        }
        return $result;
    }

    /**
     * @return string
     */
    protected function getSearchId()
    {
        $searchId = $this->cache->load('netsuite_search_id');
        if ($searchId) {
            $this->searchId = $searchId;
        }
        return $this->searchId;
    }

    /**
     * @param $searchId
     */
    protected function setSearchId($searchId)
    {
        $this->cache->save($searchId, 'netsuite_search_id', [self::CACHE_TAG]);
        $this->searchId = $searchId;
    }

    /**
     * @param $config
     */
    protected function initService($config)
    {
        $config = [
            "endpoint" => $config['endpoint'],
            "host"     => $config['host'],
            "account"  => $config['account'],
            "consumerKey" => $config['consumerKey'],
            "consumerSecret" => $config['consumerSecret'],
            "token" => $config['token'],
            "tokenSecret" => $config['tokenSecret'],
            "use_old_http_protocol_version" => $config['use_old_http_protocol_version']
        ];

        $options = [
            'connection_timeout' => 6000,
            'keep_alive' => true
        ];

        if (!empty($config['use_old_http_protocol_version'])) {
            $options['stream_context'] = stream_context_create(
                ['http' => ['protocol_version' => 1.0]]
            );
        }

        $this->service = new \NetSuite\NetSuiteService($config, $options);
    }

    /**
     * @param $inventoryLocationName
     * @return string
     */
    protected function getSourceCode($inventoryLocationName) {
        $sourceCode = preg_replace("/[[:punct:]]+/", '', $inventoryLocationName);
        $sourceCode = strtolower(preg_replace('/\s+/', '_', $sourceCode));
        return $sourceCode;
    }

    /**
     * @param $type
     * @param $netsuiteInternalId
     * @return array
     */
    protected function entityUpdateRequestHandler($type, $netsuiteInternalId) {
        $this->setSearchId(null);
        $getRequest = new GetRequest();
        $itemRef = new RecordRef();
        $itemRef->internalId = $netsuiteInternalId;
        $itemRef->type = $type;
        $getRequest->baseRef = $itemRef;
        $response = $this->service->get($getRequest);
        $items = [];
        if ($response->readResponse->status->isSuccess) {
            $items[] = $this->convertResultToArray($response->readResponse->record);
        }
        return $items;
    }
}
