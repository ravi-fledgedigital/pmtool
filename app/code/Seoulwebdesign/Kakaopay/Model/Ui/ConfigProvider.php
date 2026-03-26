<?php
namespace Seoulwebdesign\Kakaopay\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Seoulwebdesign\Base\Helper\Data;
use Seoulwebdesign\Kakaopay\Helper\Constant;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'kakaopay';

    /**
     * @var Data
     */
    protected $baseHelper;

    public function __construct(
        Data $baseHelper
    ) {
        $this->baseHelper = $baseHelper;
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
                    'method'=>self::CODE,
                    'instructions'=>$this->baseHelper->getPaymentConfig('kakaopay/instructions'),
                    'redirectUrl'=>$this->baseHelper->getUrl(Constant::KAKAOPAY_REDIRECT_URL)
                ]
            ]
        ];
    }
}
