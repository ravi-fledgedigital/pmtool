<?php

namespace WeltPixel\GA4\Model;

/**
 * Class \WeltPixel\GA4\Model\JsonGenerator
 */
class IpAddressResolver extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\RequestInterface $request
     *
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request
    )
    {
        parent::__construct($context, $registry);
        $this->request = $request;
    }


    /**
     * @return false|string
     */
    public function getIpAddress()
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            $ip = $this->request->getServer($header);
            if (!$ip) {
                continue;
            }

            if (strpos($ip, ',') !== false) {
                $ips = array_map('trim', explode(',', $ip));
                $ip = $ips[0];
            }
            return  trim($ip);
        }

        return false;
    }


}
