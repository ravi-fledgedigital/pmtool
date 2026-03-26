<?php
/** phpcs:ignoreFile */
namespace Seoulwebdesign\KakaoSync\Service;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\HTTP\ClientInterface;
use Seoulwebdesign\KakaoSync\Helper\ConfigHelper;
use Seoulwebdesign\KakaoSync\Helper\Logger;

class Kakao
{
    public const HOST_AUTH = 'https://kauth.kakao.com';
    public const HOST_API = 'https://kapi.kakao.com';
    public const TOKEN_PATH = '/oauth/token';
    public const USER_PATH = '/v2/user/me';
    public const UNLINK_PATH = '/v1/user/unlink';
    public const SCOPES_PATH = '/v2/user/scopes';
    public const REVOKE_SCOPE_PATH = '/v2/user/revoke/scopes';
    public const SHIPPING_ADDRESS_PATH = '/v1/user/shipping_address';
    public const GET_TERMS = '/v1/user/service/terms';
    public const LOGOUT_PATH = '/v1/user/logout';

    public const LOGOUT_URL = '/oauth/logout';
    public const LOG_FILE = 'Api';

    /**
     * @var ClientInterface
     */
    protected $curlClient;
    /**
     * @var ConfigHelper
     */
    protected $configHelper;
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Curl $client
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Curl $client,
        ConfigHelper $configHelper
    ) {
        $this->curlClient = $client;
        $this->configHelper = $configHelper;
        $this->logger = $this->configHelper->getLogger();
    }

    /**
     * Get the config helper
     *
     * @return ConfigHelper
     */
    public function getConfigHelper()
    {
        return $this->configHelper;
    }

    /**
     * Send request via curl
     *
     * @param string $url
     * @param array $field
     * @param string $type
     * @return false|mixed
     */
    public function sendCurl($url, $field, $type)
    {
        $logData['request']['type'] = $type;
        $logData['request']['url'] = $url;
        $logData['request']['data'] = $field;
        try {
            //$this->curlClient->setCredentials();
            //$this->curlClient->addHeader("Authorization", "KakaoAK " . $this->getAdminKey());
            $this->curlClient->addHeader("Content-Type", "application/x-www-form-urlencoded;charset=utf-8");
            switch (strtoupper($type)) {
                case "GET":
                    $this->curlClient->get($url);
                    break;
                case "POST":
                    $this->curlClient->post($url, $field);
                    break;
                case "DELETE":
                    $this->curlClient->delete($url);
                    break;
                case "PATCH":
                    $this->curlClient->patch($url, $field);
                    break;
                default:
                    return false;
            }
            $result = $this->curlClient->getBody();
            $result = json_decode($result, true);
            $logData['response'] = $result;
            $this->logger->logDebug($logData, self::LOG_FILE);
            return $result;
        } catch (\Exception $e) {
            $logData['error'] = $e->getMessage();
            $this->logger->logError($logData, self::LOG_FILE);
            return false;
        }
    }

    /**
     * Get token by auth code
     *
     * @param string $authCode
     * @return false|mixed
     * @throws \Exception
     */
    public function getToken($authCode)
    {
        $params['grant_type'] = 'authorization_code';
        $params['client_id'] = $this->configHelper->getRestApiKey();
        $params['redirect_uri'] = $this->configHelper->getRedirectUrl();
        $params['code'] = $authCode;
        $params['client_secret'] = $this->configHelper->getClientSecret();
        $url = self::HOST_AUTH . self::TOKEN_PATH;
        $result = $this->sendCurl($url, $params, 'POST');
        if (isset($result['error_code'])) {
            throw new LocalizedException(__($result['error_code'] . ' ' . $result['error_description']));
        }
        return $result;
    }

    /**
     * Refresh Token
     *
     * @param string $refreshToken
     * @return false|mixed
     */
    public function refreshToken($refreshToken)
    {
        $params['grant_type'] = 'refresh_token';
        $params['client_id'] = $this->configHelper->getRestApiKey();
        $params['refresh_token'] = $refreshToken;
//        $params['client_secret'] = '';
        $url = self::HOST_AUTH . self::TOKEN_PATH;
        return $this->sendCurl($url, $params, 'POST');
    }

    /**
     * Get user infomation
     *
     * @param string $accessToken
     * @return false|mixed
     */
    public function getUserInfomation($accessToken)
    {
        if ($accessToken) {
            $this->curlClient->addHeader("Authorization", "Bearer " . $accessToken);
            $params['secure_resource'] = 'refresh_token';
            $params['property_keys'] = '["kakao_account.email","kakao_account.gender"]';
            $url = self::HOST_API . self::USER_PATH;
            return $this->sendCurl($url, $params, 'GET');
        } else {
            return false;
        }
    }

    /**
     * Unlink connection
     *
     * @param string $accessToken
     * @return false|mixed
     */
    public function unlink($accessToken)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/unlink_customer_token.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);

        if ($accessToken) {
            $logger->info("============ Unlink Customer Token Start ==================");
            $logger->info("Customer Token: " . $accessToken);
            $this->curlClient->addHeader("Authorization", "Bearer " . $accessToken);
            $url = self::HOST_API . self::UNLINK_PATH;
            $logger->info("Customer Unlink Url: " . $url);
            $logger->info("============ Unlink Customer Token End ==================");
            return $this->sendCurl($url, [], 'POST');
        } else {
            $logger->info("============ Unlink Customer Token Error ==================");
            return false;
        }
    }

    /**
     * Get Consent Details
     *
     * @param string $accessToken
     * @return false|mixed
     */
    public function getConsentDetails($accessToken)
    {
        if ($accessToken) {
            $this->curlClient->addHeader("Authorization", "Bearer " . $accessToken);
            $url = self::HOST_API . self::SCOPES_PATH;
            return $this->sendCurl($url, [], 'GET');
        } else {
            return false;
        }
    }

    /**
     * Get the shipping address
     *
     * @param string $accessToken
     * @return array
     */
    public function getShippingAddress($accessToken)
    {
        if ($accessToken) {
            $this->curlClient->addHeader("Authorization", "Bearer " . $accessToken);
            $url = self::HOST_API . self::SHIPPING_ADDRESS_PATH;
            return $this->sendCurl($url, [], 'GET');
        } else {
            return [];
        }
    }

    /**
     * Get the terms
     *
     * @param string $accessToken
     * @return false|mixed
     */
    public function getTerms($accessToken)
    {
        if ($accessToken) {
            $this->curlClient->addHeader("Authorization", "Bearer " . $accessToken);
            $url = self::HOST_API . self::GET_TERMS . '?extra=app_service_terms';
            return $this->sendCurl($url, [], 'GET');
        } else {
            return false;
        }
    }

    /**
     * Logout user
     *
     * @param string $accessToken
     * @return false|mixed
     */
    public function logout($accessToken)
    {
        if ($accessToken) {
            $this->curlClient->addHeader("Authorization", "Bearer " . $accessToken);
            $url = self::HOST_API . self::LOGOUT_PATH;
            return $this->sendCurl($url, [], 'POST');
        } else {
            return false;
        }
    }

    /**
     * Get the logout url
     *
     * @param $redirectUrl
     * @param $state
     * @return string
     */
    public function getLogoutUrl($redirectUrl, $state)
    {
        $url = self::HOST_AUTH . self::LOGOUT_URL;
        $url .= '?client_id=' . $this->configHelper->getRestApiKey();
        $url .= '&logout_redirect_uri=' . urlencode($redirectUrl);
        $url .= '&state=' . $state;
        return $url;
    }
}
