<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTigerKorea\Newsletter\Helper;


use Magento\Store\Model\StoreManagerInterface;

/**
 * Newsletter Data Helper
 *
 * @api
 * @since 100.0.2
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @param $email
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getUnsubscribeUrl($email)
    {
        return $this->storeManager->getStore()->getBaseUrl() . 'subscriber/newsletter/remove/email/'.urlencode($email);
    }
}
