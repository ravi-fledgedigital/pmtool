<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\Razer\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use OnitsukaTiger\Razer\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'molpay_seamless';

    protected $method;

    protected $channel;

    /**
     * Payment ConfigProvider constructor.
     * @param \Magento\Payment\Helper\Data $paymentHelper
     */
    public function __construct(
        \OnitsukaTiger\Razer\Helper\Data $paymentHelper,
        \OnitsukaTiger\Razer\Model\Source\Channel $channelList
    ) {
        $this->method = $paymentHelper;
        $this->channel = $channelList;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success')
                    ],
		    'channels_payment' => $this->getActiveChannels(),
		    'sandbox_environment' => $this->isSandBoxEnvironment()
                ]
            ]
        ];
    }


    //Get activated channel
    protected function getActiveChannels()
    {
        $activeChannel = [];
        if( empty($this->method->getActiveChannels()) ){
            return $activeChannel;
        }
        $activeConfigChannels = explode(",",$this->method->getActiveChannels());
        $allChannel = $this->channel->toArray();


        foreach( $allChannel as $k => $v ){
            if( in_array( $k, $activeConfigChannels ) ){
                $activeChannel[] = [ "value" => $k, "label" =>$v ];
            }
        }
        return $activeChannel;
    }

    //Get Sandbox Environment
    protected function isSandBoxEnvironment()
    {
       return $this->method->getSandboxEnvironment();
    }
}
