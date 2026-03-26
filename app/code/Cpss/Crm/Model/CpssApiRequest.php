<?php
//phpcs:ignoreFile
namespace Cpss\Crm\Model;

use Cpss\Crm\Helper\Data;

class CpssApiRequest
{
    const PARAMS_AUTHORITY= '&sid=otcrm-sgp&spw=otcrm-sgp&result=json';
    const LOG_PATH = '/var/log/cpss_api_request.log';
    private const ADD_MEMBER_API = 'cmmm/api/add_member.php';
    private const UPDATE_MEMBER_API = 'cmmm/api/update_member.php';
    private const GET_MEMBER_STATUS_API = 'cspm/api/get_member_status.php';
    private const GET_HISTORY_PAGE_API = 'cspm/api/get_history_page.php';
    private const GET_POINT_RATE_API = 'cspm/api/get_point_rate.php';
    // private const GET_GIVEN_POINT_API = 'cspm/api/get_given_point.php';
    private const GET_GIVEN_POINT_API = 'otcrm/api/get_given_point.php'; #TEMP
    private const GET_GIVEN_POINT_API_SG = 'otcrm-sgp/api/get_given_point.php'; #TEMP
    private const GET_GIVEN_POINT_API_MY = 'otcrm-mys/api/get_given_point.php'; #TEMP
    private const GET_GIVEN_POINT_API_TH = 'otcrm-tha/api/get_given_point.php'; #TEMP
    private const GET_GIVEN_POINT_API_KR = 'otcrm-kor/api/get_given_point.php'; #TEMP
    private const USE_POINT_API = 'cspm/api/use_point.php';
    private const ADD_POINT_API = 'cspm/api/add_point.php';
    private const GET_NEAREST_EXPIRES = 'cpbm/api/get_nearest_expires.php';
    private const GET_NEAREST_EXPIRES_API_SG = 'otcrm-sgp/api/get_nearest_expires.php';
    private const GET_NEAREST_EXPIRES_API_MY = 'otcrm-mys/api/get_nearest_expires.php';
    private const GET_NEAREST_EXPIRES_API_TH = 'otcrm-tha/api/get_nearest_expires.php';
    private const GET_NEAREST_EXPIRES_API_KR = 'otcrm-kor/api/get_nearest_expires.php';
    private const GET_HISTORY_PAGE_TOTAL = 'cpbm/api/get_history_page_total.php';

    protected $helperData;
    protected $logger;

    /**
     * @var \OnitsukaTigerCpss\Crm\Helper\HelperData
     */
    protected $cpssHelperData;

    /**
     * __construct
     *
     * @param Data
     *
     */
    public function __construct(
        Data $helperData,
        \OnitsukaTigerCpss\Crm\Helper\HelperData $cpssHelperData
    ) {
        $this->helperData = $helperData;
        $this->cpssHelperData = $cpssHelperData;
        $this->initLogger();
    }

    /**
     * add_member
     *
     * @param string $memberId
     * @param string $accountStatus
     * @return array
     */
    public function addMember($memberId, $accountStatus = 'REG')
    {
        $request = [
            'url' => self::ADD_MEMBER_API,
            'params' => "aid=$memberId&sts=$accountStatus"
        ];

        return $this->execQuery($request);
    }

    /**
     * update_member
     *
     * @param string $memberId
     * @param string $accountStatus
     * @return array
     */
    public function updateMember($memberId, $accountStatus = 'OWN')
    {
        $request = [
            'url' => self::UPDATE_MEMBER_API,
            'params' => "aid=$memberId&sts=$accountStatus"
        ];

        return $this->execQuery($request);
    }

    /**
     * get_member_status
     *
     * @param string $memberId
     * @param string $ptypes
     * @param string $expires
     * @return array
     */
    public function getMemberStatus($memberId, $ptypes = "point", $expires = 0, $storeId = '', $websiteId = '')
    {
        $request = [
            'url' => self::GET_MEMBER_STATUS_API,
            'params' => "aid=$memberId:USR&ptypes=$ptypes&expires=$expires"
        ];

        return $this->execQuery($request, $storeId, $websiteId);
    }

    /**
     * get_history_page
     *
     * @param string $memberId
     * @param string $ptypes (temporary default value)
     * @param string $lines
     * @param string $page
     * @return array
     */
    public function getPointHistory($memberId, $ptypes = "point", $lines = 10, $page = 1, $filters = "TMP", $storeId = '', $websiteId = '')
    {
        $request = [
            'url' => self::GET_HISTORY_PAGE_API,
            'params' => "aid=$memberId:USR&ptypes=$ptypes&lines=$lines&page=$page&filter=$filters"
        ];

        return $this->execQuery($request, $storeId, $websiteId);
    }

    /**
     * getPointRate
     *
     * @param mixed $memberId
     * @return int
     */
    public function getPointRate($memberId)
    {
        try {
            $shopId = $this->helperData->getCpssShopId();
            $request = [
                'url' => self::GET_POINT_RATE_API,
                'params' => "aid={$memberId}&shopid={$shopId}&odd=true"
            ];
            $response = $this->execQuery($request);
            $bodyContent = json_decode($response['Body'][0][0], true);
            $percentage = $bodyContent['result']['rate'];
            $oddValue = $bodyContent['result']['odd'];

            // return $this->execQuery($request);
            return [
                'rate' => $percentage,
                'odd' => $oddValue
            ];
        } catch (\Exception $e) {
            return [
                'rate' => 0,
                'odd' => 0
            ];
        }
    }

    /**
     * usePoint
     *
     * @param string $orderId
     * @param string $memberId
     * @param int $point
     * @param string $ptypes
     * @param string $action
     * @param string $scode
     * @return array
     */
    public function usePoint($orderId, $memberId, $point, $ptypes = "point", $action = "BUY", $scode = "G2000001")
    {
        $this->logger->debug("ORDER INCREMENT_ID: {$orderId}");
        if (!is_numeric($point)) {
            $this->logger->debug("Point must be numeric " . $point);
            return ['X-CPSS-Result' => "999-999-999"];
        }

        $shopId = $this->helperData->getCpssShopId();
        $hid = $orderId . "_SUB";
        $request = [
            'url' => self::USE_POINT_API,
            'params' => "aid={$memberId}:USR&point={$point}&shopid={$shopId}&ptypes={$ptypes}&hid={$hid}&action={$action}&scode={$scode}"
        ];

        return $this->execQuery($request);
    }

    /**
     * addPoint
     * For CreditMemo/Refund
     *
     * @param string $orderId
     * @param string $memberId
     * @param int $point
     * @param int $refundTimes
     * @param string $effective
     * @param string $expire
     * @param string $ptypes
     * @param string $action
     * @param string $scode
     * @return array
     */
    public function addPoint($orderId, $memberId, $point, int $refundTimes = 0, $effective = null, $expire = null, $ptypes = "point", $action = "ADD", $scode = "G100001")
    {
        $order = $this->cpssHelperData->getOrderByIncrementId($orderId);
        $storeId = '';
        if ($order && $order->getId()) {
            $storeId = $order->getStoreId();
            $shopId = $this->helperData->getCpssShopId($storeId);
        } elseif ($scode = "G100002") {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $originalOrder = $objectManager->create(\Cpss\Pos\Model\PosData::class)->loadShopByExchPurchaseId($orderId);
            if ($originalOrder && $originalOrder->getId()) {
                $helperData = $objectManager->create(\OnitsukaTigerCpss\Crm\Helper\HelperData::class);
                $storeIds = $helperData->getStoreIds();
                $storeId = (isset($storeIds[$originalOrder->getStoreCode()])) ? $storeIds[$originalOrder->getStoreCode()] : '';
                $shopId = $originalOrder->getShopId();
            }
        } else {
            $shopId = $this->helperData->getCpssShopId();
        }

        $hid = $orderId . "_RA_RTN";
        if ($refundTimes > 0) {
            $hid = $orderId . "_R" . $refundTimes . "_RTN";
        }

        $request = [
            'url' => self::ADD_POINT_API,
            'params' => "aid={$memberId}:USR&point={$point}&shopid={$shopId}&ptypes={$ptypes}&hid={$hid}&action={$action}&scode={$scode}&receipt={$orderId}"
        ];

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/realStoreCSV.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info('==========================Create Real Store CSV Add Point API Start============================');
        $logger->info('Request Params: ' . print_r($request, true));
        $logger->info('==========================Create Real Store CSV Add Point API End============================');

        return $this->execQuery($request, $storeId);
    }

    /**
     * get_given_point
     *
     * @param string $memberId
     * @param string $hid
     * @return array
     */
    public function getGivenPoint($memberId, $hid, $storeCode = '')
    {
        $url = self::GET_GIVEN_POINT_API;
        if (!empty($storeCode)) {
            if ($storeCode == 'sg') {
                $url = self::GET_GIVEN_POINT_API_SG;
            } elseif ($storeCode == 'my') {
                $url = self::GET_GIVEN_POINT_API_MY;
            } elseif ($storeCode == 'th') {
                $url = self::GET_GIVEN_POINT_API_TH;
            } elseif ($storeCode == 'kr') {
                $url = self::GET_GIVEN_POINT_API_KR;
            }
        }

        $storeIds = $this->cpssHelperData->getStoreIds();

        $storeId = (isset($storeIds[$storeCode])) ? $storeIds[$storeCode] : 1;

        $request = [
            'url' => $url,
            'params' => "aid={$memberId}:USR&hid={$hid}"
        ];

        return $this->execQuery($request, $storeId);
    }

    /**
     * get_nearest_expires
     *
     * @param string $memberId
     * @param string $ptypes
     * @param string $sts
     * @return array
     */
    public function getNearestExpires($memberId, $storeId = '', $websiteId = '', $ptypes = "point", $sts = "REG")
    {
        $url = self::GET_NEAREST_EXPIRES;
        if (empty($storeId)) {
            $storeId = $this->cpssHelperData->getCurrentStoreId();
        }

        if ($storeId == 1) {
            $url = self::GET_NEAREST_EXPIRES_API_SG;
        } elseif ($storeId == 2) {
            $url = self::GET_NEAREST_EXPIRES_API_MY;
        } elseif ($storeId == 3 || $storeId == 4) {
            $url = self::GET_NEAREST_EXPIRES_API_TH;
        } elseif ($storeId == 5) {
            $url = self::GET_NEAREST_EXPIRES_API_KR;
        }

        $request = [
            'url' => $url,
            'params' => "aid=$memberId:USR&ptypes=$ptypes&sts=$sts"
        ];

        return $this->execQuery($request, $storeId, $websiteId);
    }

    /**
     * get_history_page_total
     *
     * @param string $memberId
     * @param string $ptypes
     * @param int $lines
     * @param string $filters
     * @return array
     */
    public function getHistoryPageTotal($memberId, $ptypes = "point", $lines = 50, $filters = "TMP", $storeId = '', $websiteId = '')
    {
        $request = [
            'url' => self::GET_HISTORY_PAGE_TOTAL,
            'params' => "aid=$memberId:USR&ptypes=$ptypes&lines=$lines&filters=$filters"
        ];

        return $this->execQuery($request, $storeId, $websiteId);
    }

    /**
     * execQuery
     *
     * @param array $cpssQuery
     * @return array
     */
    private function execQuery($cpssQuery, $storeId = '', $websiteId = '')
    {
        if (!empty($websiteId)) {
            $siteId = $this->cpssHelperData->getSiteId($storeId, $websiteId);
            $sitePassword = $this->cpssHelperData->getSitePassword($storeId, $websiteId);
        } else {
            $siteId = $this->cpssHelperData->getSiteId($storeId);
            $sitePassword = $this->cpssHelperData->getSitePassword($storeId);
        }

        $authorityParams = self::PARAMS_AUTHORITY;

        if (!empty($siteId) && !empty($sitePassword)) {
            $authorityParams = '&sid=' . $siteId . '&spw=' . $sitePassword . '&result=json';
        }

        $strBodys = [];
        $ch = curl_init();
        $header = [
            'X-CPSS-RESPONSETYPE: JSON'
        ];

        $this->logger->debug($cpssQuery);

        //STG authentication
        if ($this->helperData->getEnv() == 'STG') {
            $header = array_merge([$this->helperData->getAuthBearer()], $header);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $cpssQuery['params'] . $authorityParams);
        curl_setopt($ch, CURLOPT_URL, $this->helperData->getCpssApiBaseUrl() . $cpssQuery['url']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $strBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch)['http_code'];

        $strBodys = $this->checkHtmlData($strBody);

        if ($httpCode != 200) {
            $strBodys['X-CPSS-Result'] = "999-999-999";
        }
        $strBodys['http_code'] = $httpCode;

        curl_close($ch);
        return $strBodys;
    }

    private function checkHtmlData($data)
    {
        $datas = preg_split("/\r\n?/", $data);
        $datas[0] = preg_replace('(/)', ': ', $datas[0]);

        $checkedHtml = [];
        $checkedHtml['Body'] = [];
        $i = 0;

        foreach ($datas as $data) {
            if (!strlen($data)) {
                $ii = 1;
                for ($counter = 1; ($counter + $i) < count($datas) && strlen($datas[$counter + $i]); $counter++) {
                    $checkedHtml['Body'][$counter - 1] = preg_split("/\t/", mb_convert_encoding($datas[$counter + $i], "UTF-8", "Shift-JIS"));
                }
                break;
            } else {
                $line = preg_split("/: +/", $data);
                $checkedHtml[$line[0]] = $line[1];
                $i++;
            }
        }
        return $this->reformatCpssResult($checkedHtml);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function reformatCpssResult($data)
    {
        if (!empty($data['x-cpss-result'])) {
            $data['X-CPSS-Result'] = $data['x-cpss-result'];
        }
        return $data;
    }

    public function initLogger()
    {
        $writer = new \Zend_Log_Writer_Stream(BP . self::LOG_PATH);
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $this->logger = $logger;
    }
}
