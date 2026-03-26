<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Block\Adminhtml;

use \Magento\Backend\Block\Template;
use \Magento\Backend\Model\Auth\Session;
use Magento\Framework\UrlInterface;
use \Magento\Integration\Model\Oauth\TokenFactory;
use SendGrid\EmailDeliverySimplified\Model\GeneralSettings;
use SendGrid\EmailDeliverySimplified\Helper\Tools;

class Statistics extends Template
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var \Aitoc\SendGrid\Model\ApiWork
     */
    private $apiWork;

    public function __construct(
        Template\Context $context,
        \Aitoc\SendGrid\Model\ApiWork $apiWork,
        UrlInterface $urlBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->apiWork = $apiWork;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @return string
     */
    public function getStatsBaseUrl()
    {
        return $this->urlBuilder->getUrl('aitoc_sendgrid/statistics/statistics');
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSingleSends()
    {
        $categories = $this->apiWork->getSingleSends();
        $singleSends = [];
        if ($categories && isset($categories['result'])) {
            foreach ($categories['result'] as $compaign) {
                $singleSends[$compaign['updated_at']] = $compaign['name'];
            }
        }

        return $singleSends;
    }
}
