<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Observer\Rma;

use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Request\Email\EmailRequest;
use Amasty\Rma\Observer\RmaEventNames;
use Amasty\Rma\Utils\Email;
use Magento\Framework\App\Area;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class RmaCreated implements ObserverInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Email
     */
    private $emailSender;

    /**
     * @var EmailRequest
     */
    private $emailProcessor;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(
        ConfigProvider $configProvider,
        EmailRequest $emailProcessor,
        Email $emailSender,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->configProvider = $configProvider;
        $this->emailSender = $emailSender;
        $this->emailProcessor = $emailProcessor;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(Observer $observer)
    {
        /** @var \Amasty\Rma\Model\Request\Request $request */
        $request = $observer->getRequest();

        if ($this->configProvider->isNotifyCustomer($request->getStoreId())) {
            $this->sendCustomerNotification($request);
        }

        if ($this->configProvider->isNotifyAdmin($request->getStoreId())) {
            $this->sendAdminNotification($request);
        }
    }

    /**
     * @param \Amasty\Rma\Model\Request\Request $request
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendCustomerNotification($request)
    {
        $emailRequest = $this->emailProcessor->parseRequest($request);
        $storeId = $request->getStoreId();
        $this->emailSender->sendEmail(
            $emailRequest->getCustomerEmail(),
            $storeId,
            $this->scopeConfig->getValue(
                ConfigProvider::XPATH_USER_TEMPLATE,
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $storeId
            ),
            [
                'email_request' => $emailRequest,
                'order' => $emailRequest->getOrder()
            ],
            \Magento\Framework\App\Area::AREA_FRONTEND,
            $this->configProvider->getSender($storeId)
        );
    }

    /**
     * @param \Amasty\Rma\Model\Request\Request $request
     *
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendAdminNotification($request)
    {
        $emailRequest = $this->emailProcessor->parseRequest($request);
        $storeId = $request->getStoreId();
        $emails = $this->configProvider->getAdminEmails($request->getStoreId());
        if ($emails) {
            $this->emailSender->sendEmail(
                $emails,
                $storeId,
                $this->scopeConfig->getValue(
                    ConfigProvider::XPATH_ADMIN_TEMPLATE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                [
                    'email_request' => $emailRequest,
                    'order' => $emailRequest->getOrder()
                ],
                \Magento\Framework\App\Area::AREA_FRONTEND
            );
        }
    }
}
