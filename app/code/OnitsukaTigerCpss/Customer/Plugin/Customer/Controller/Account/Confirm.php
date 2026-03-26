<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace OnitsukaTigerCpss\Customer\Plugin\Customer\Controller\Account;

use Magento\Customer\Controller\Account\Confirm as AccountConfirm;
use Magento\Customer\Model\Session;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use OnitsukaTigerCpss\Crm\Helper\Data;

/**
 * Class Confirm *  class is responsible for account confirmation flow
 */
class Confirm
{

    /**
     * @var Data
     */
    private Data $helperData;
    /**
     * @var Session
     */

    /**
     * @var MessageManagerInterface
     */
    protected MessageManagerInterface $messageManager;
    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;
    /**
     * @var Session
     */
    protected Session $session;

    /**
     * @param Data $helperData
     * @param Session $customerSession
     * @param MessageManagerInterface $messageManager
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Data $helperData,
        Session $customerSession,
        MessageManagerInterface  $messageManager,
        StoreManagerInterface $storeManager
    ) {
        $this->helperData = $helperData;
        $this->session = $customerSession;
        $this->messageManager = $messageManager;
        $this->storeManager = $storeManager;
    }

    /**
     * @param AccountConfirm $subject
     * @param $result
     * @return mixed
     */
    public function beforeExecute(AccountConfirm $subject): mixed
    {
        try {
            $params = $subject->getRequest()->getParams();
            if (empty($params['back_url'])) {
                return [$subject];
            }
            $backUrlWithParams = explode('&', $params['back_url']);
            $request = $this->joinParams($backUrlWithParams);
            if (empty($request['registeredfromapp'])) {
                return [$subject];
            }
            $this->session->setRegisteredFromApp(true);
            if (empty($this->session->getAppLoginRequest())) {
                $this->session->setAppLoginRequest($request);
            }
            $this->messageManager->addSuccessMessage(__('Thank you for registering with %1.', $this->storeManager->getStore()->getFrontendName()));
            return [$subject];
        } catch (\Exception $e) {
            $this->helperData->logCritical($e->getMessage());
        }
        return [$subject];
    }
    private function joinParams($params = [])
    {
        $result = [];
        foreach ($params as $param) {
            $list = explode('=', $param);
            if (empty($list[1])) {
                continue;
            }
            $result[$list[0]] = $list[1];
        }
        return $result;
    }
}
