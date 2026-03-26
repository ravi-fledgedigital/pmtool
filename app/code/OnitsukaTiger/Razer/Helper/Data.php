<?php

namespace OnitsukaTiger\Razer\Helper;


use Magento\Framework\App\Helper\Context;
use OnitsukaTiger\Logger\Razer\Logger;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MER_GATE_ID = 'payment/molpay_seamless/merchant_gateway_id';
    const MER_GATE_KEY = 'payment/molpay_seamless/merchant_gateway_key';
    const MER_GATE_SECRETKEY ='payment/molpay_seamless/merchant_gateway_secretkey';
    const MOLPAY_CHANNELS ='payment/molpay_seamless/channels_payment';
    const MOLPAY_ENV = "payment/molpay_seamless/sandbox_environment";
    const ID_CHANNEL_API = 'payment/molpay_channel_list_api/id';
    const KEY_CHANNEL_API = 'payment/molpay_channel_list_api/key';
    const CHANNEL_CREDIT_CART = 'CC';
    const CHANNEL_ENABLE = 1;


    /**
     * @var Logger
     */
    public $logger;
    public function __construct(Context $context, Logger $logger)
    {
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function getMerchantID()
    {
        return $this->scopeConfig->getValue(
            self::MER_GATE_ID,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getVerifyKey()
    {
        return $this->scopeConfig->getValue(
            self::MER_GATE_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getSecretKey()
    {
        return $this->scopeConfig->getValue(
            self::MER_GATE_SECRETKEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getActiveChannels(){
        return $this->scopeConfig->getValue(
            self::MOLPAY_CHANNELS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

     public function getSandboxEnvironment(){
        return $this->scopeConfig->getValue(
            self::MOLPAY_ENV,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getIdForChannelApi()
    {
        return $this->scopeConfig->getValue(
            self::ID_CHANNEL_API,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getKeyForChannelApi()
    {
        return $this->scopeConfig->getValue(
            self::KEY_CHANNEL_API,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getActiveChannelsFromApi(): array
    {
        $activeChannel = [];
        $merchantId = $this->getIdForChannelApi();
        $vkey = $this->getKeyForChannelApi();
        $datetime = date('YmdHis');
        $skey = hash_hmac('sha256', $datetime . $merchantId, $vkey);
        $url = 'https://pay.merchant.razer.com/RMS/API/chkstat/channel_status.php';

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => array('merchantID' => $merchantId, 'datetime' => $datetime, 'skey' => $skey),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $result = json_decode($response, true);
        if (!$result['status']) {
            $msg = sprintf("Error API Get Channel List with error code [%s]. Message: [%s]", $result['error_code'], $result['error_desc']);
            $this->logger->error($msg);
            return $activeChannel;
        }

        $allActiveChannels = $result['result'];
        foreach ($allActiveChannels as $channel) {
            if ($channel['status'] = self::CHANNEL_ENABLE) {
                if($channel['channel_type'] == self::CHANNEL_CREDIT_CART) {
                    $activeChannel[] = ["value" => $channel['channel_map']['seamlesspayment']['request'], "label" => $channel['title']];
                }else {
                    $channelValue = '';
                    if(isset($channel['channel_map']) && !empty($channel['channel_map']) &&
                        isset($channel['channel_map']['seamless']) && !empty($channel['channel_map']['seamless']) &&
                        isset($channel['channel_map']['seamless']['request']) && !empty($channel['channel_map']['seamless']['request'])
                    ) {
                        $channelValue = $channel['channel_map']['seamless']['request'];
                    }
                    $activeChannel[] = ["value" => $channelValue, "label" => $channel['title']];
                }
            }
        }

        return $activeChannel;
    }
}
