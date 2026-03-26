<?php
/** phpcs:ignoreFile */

namespace OnitsukaTiger\Rma\Observer\Rma;

use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Request\Email\EmailRequest;
use Amasty\Rma\Observer\RmaEventNames;
use Amasty\Rma\Utils\Email;
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

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * Constructs a new instance.
     *
     * @param      \Magento\Store\Model\App\Emulation                  $emulation       The emulation
     * @param      \Amasty\Rma\Model\ConfigProvider                    $configProvider  The configuration provider
     * @param      \Amasty\Rma\Model\Request\Email\EmailRequest        $emailProcessor  The email processor
     * @param      \Amasty\Rma\Utils\Email                             $emailSender     The email sender
     * @param      \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig     The scope configuration
     */
    public function __construct(
        \Magento\Store\Model\App\Emulation $emulation,
        ConfigProvider $configProvider,
        EmailRequest $emailProcessor,
        Email $emailSender,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->emulation = $emulation;
        $this->configProvider = $configProvider;
        $this->emailSender = $emailSender;
        $this->emailProcessor = $emailProcessor;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * { function_description }
     *
     * @param      \Magento\Framework\Event\Observer  $observer  The observer
     */
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
     * Sends a customer notification.
     *
     * @param      <type>  $request  The request
     */
    private function sendCustomerNotification($request)
    {
        $emailRequest = $this->emailProcessor->parseRequest($request);
        $storeId = $request->getStoreId();
        if (in_array($storeId, ['6', '7'])) {
            $this->emulation->startEnvironmentEmulation($request->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
            $this->emailSender->sendEmail(
                $emailRequest->getCustomerEmail(),
                $storeId,
                'amrma_email_user_template_customer',
                ['email_request' => $emailRequest],
                \Magento\Framework\App\Area::AREA_FRONTEND,
                $this->configProvider->getSender($storeId)
            );
            $this->emulation->stopEnvironmentEmulation();
        } else {
            $this->emailSender->sendEmail(
                $emailRequest->getCustomerEmail(),
                $storeId,
                $this->scopeConfig->getValue(
                    ConfigProvider::XPATH_USER_TEMPLATE,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                    $storeId
                ),
                ['email_request' => $emailRequest],
                \Magento\Framework\App\Area::AREA_FRONTEND,
                $this->configProvider->getSender($storeId)
            );
        }
    }

    /**
     * Sends an admin notification.
     *
     * @param      <type>  $request  The request
     */
    private function sendAdminNotification($request)
    {
        $emailRequest = $this->emailProcessor->parseRequest($request);
        $storeId = $request->getStoreId();
        $emails = $this->configProvider->getAdminEmails($request->getStoreId());
        if ($emails) {
            if (in_array($storeId, ['6', '7'])) {
                $this->emulation->startEnvironmentEmulation($request->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
                $this->emailSender->sendEmail(
                    $emails,
                    $storeId,
                    'amrma_email_user_template_warehouse',
                    ['email_request' => $emailRequest],
                    \Magento\Framework\App\Area::AREA_FRONTEND
                );
                $this->emulation->stopEnvironmentEmulation();
            } else {
                $this->emailSender->sendEmail(
                    $emails,
                    $storeId,
                    $this->scopeConfig->getValue(
                        ConfigProvider::XPATH_ADMIN_TEMPLATE,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                        $storeId
                    ),
                    ['email_request' => $emailRequest],
                    \Magento\Framework\App\Area::AREA_ADMINHTML
                );
            }
        }
    }
}
