<?php
/** phpcs:ignoreFile */

namespace OnitsukaTiger\Rma\Observer\Rma;

use Amasty\Rma\Model\Request\Email\EmailRequest;
use Amasty\Rma\Utils\Email;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\Event\ObserverInterface;
use OnitsukaTiger\Rma\Helper\Data;

class StatusChanged implements ObserverInterface
{
    /**
     * @var \Amasty\Rma\Api\StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var \Amasty\Rma\Api\ChatRepositoryInterface
     */
    private $chatRepository;

    /**
     * @var EmailRequest
     */
    private $emailRequest;

    /**
     * @var Email
     */
    private $emailSender;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var Data
     */
    private $helperData;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $emulation;

    public function __construct(
        \Magento\Store\Model\App\Emulation $emulation,
        \Amasty\Rma\Api\StatusRepositoryInterface $statusRepository,
        \Amasty\Rma\Api\ChatRepositoryInterface $chatRepository,
        EmailRequest $emailRequest,
        Email $emailSender,
        ConfigProvider $configProvider,
        Data $helperData
    ) {
        $this->emulation = $emulation;
        $this->statusRepository = $statusRepository;
        $this->chatRepository = $chatRepository;
        $this->emailRequest = $emailRequest;
        $this->emailSender = $emailSender;
        $this->configProvider = $configProvider;
        $this->helperData = $helperData;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Amasty\Rma\Api\Data\RequestInterface $request */
        if (($newStatus = $observer->getData('new_status')) && $request = $observer->getData('request')) {
            $newStatus = $this->statusRepository->getById($newStatus, $request->getStoreId());
            $storeStatus = $newStatus->getStoreData();
            if ($storeStatus->isSendToChat() && !empty($chatMessage = $storeStatus->getChatMessage())) {
                $message = $this->chatRepository->getEmptyMessageModel();
                $message->setIsRead(false)
                    ->setIsSystem(true)
                    ->setRequestId($request->getRequestId())
                    ->setMessage($chatMessage);
                $this->chatRepository->save($message, false);
            }
            $emailRequest = $this->emailRequest->parseRequest($request);

            if ($storeStatus->isSendEmailToAdmin()) {
                switch ($storeStatus->getAdminEmailTemplate()) {
                    case 0:
                        $templateIdentifier = 'amrma_email_empty_backend';
                        break;
                    case 1:
                        if ($this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_approve', $request->getStoreId())) {
                            $templateIdentifier = $this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_approve', $request->getStoreId());
                        } else {
                            $templateIdentifier = 'amrma_email_user_template_approve';
                        }
                        break;
                    case 2:
                        if ($this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_rejected', $request->getStoreId())) {
                            $templateIdentifier = $this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_rejected', $request->getStoreId());
                        } else {
                            $templateIdentifier = 'amrma_email_user_template_rejected';
                        }
                        break;
                    case 3:
                        if ($this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_completed', $request->getStoreId())) {
                            $templateIdentifier = $this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_completed', $request->getStoreId());
                        } else {
                            $templateIdentifier = 'amrma_email_user_template_completed';
                        }
                        break;
                    case 4:
                        if ($this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_initial', $request->getStoreId())) {
                            $templateIdentifier = $this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_initial', $request->getStoreId());
                        } else {
                            $templateIdentifier = 'amrma_email_user_template_initial';
                        }
                        break;
                    default:
                        $templateIdentifier = $storeStatus->getAdminEmailTemplate();
                }
                if (in_array($request->getStoreId(), ['6','7'])) {
                    $templateIdentifier = 'amrma_email_user_template_warehouse';

                    $this->emulation->startEnvironmentEmulation($request->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);

                    $this->emailSender->sendEmail(
                        $this->configProvider->getAdminEmails($request->getStoreId()),
                        $request->getStoreId(),
                        $templateIdentifier,
                        ['email_request' => $emailRequest, 'custom_text' => $storeStatus->getAdminCustomText()],
                        \Magento\Framework\App\Area::AREA_FRONTEND
                    );

                    $this->emulation->stopEnvironmentEmulation();
                } else {
                    $this->emailSender->sendEmail(
                        $this->configProvider->getAdminEmails($request->getStoreId()),
                        $request->getStoreId(),
                        $templateIdentifier,
                        ['email_request' => $emailRequest, 'custom_text' => $storeStatus->getAdminCustomText()],
                        \Magento\Framework\App\Area::AREA_ADMINHTML
                    );
                }
            }

            if ($storeStatus->isSendEmailToCustomer()) {
                switch ($storeStatus->getCustomerEmailTemplate()) {
                    case 0:
                        $templateIdentifier = 'amrma_email_empty_frontend';
                        break;
                    case 1:
                        if ($this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_approve', $request->getStoreId())) {
                            $templateIdentifier = $this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_approve', $request->getStoreId());
                        } else {
                            $templateIdentifier = 'amrma_email_user_template_approve';
                        }
                        break;
                    case 2:
                        if ($this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_rejected', $request->getStoreId())) {
                            $templateIdentifier = $this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_rejected', $request->getStoreId());
                        } else {
                            $templateIdentifier = 'amrma_email_user_template_rejected';
                        }
                        break;
                    case 3:
                        if ($this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_completed', $request->getStoreId())) {
                            $templateIdentifier = $this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_completed', $request->getStoreId());
                        } else {
                            $templateIdentifier = 'amrma_email_user_template_completed';
                        }
                        break;
                    case 4:
                        if ($this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_initial', $request->getStoreId())) {
                            $templateIdentifier = $this->helperData->getRmaEmailTemplateConfig('amrma/email_user/template_initial', $request->getStoreId());
                        } else {
                            $templateIdentifier = 'amrma_email_user_template_initial';
                        }
                        break;
                    default:
                        $templateIdentifier = $storeStatus->getCustomerEmailTemplate();
                }
                /*if (in_array($request->getStoreId(), ['6','7'])) {
                    if ($observer->getData('new_status') != 4) {
                        $templateIdentifier = 'amrma_email_user_template_customer';
                    }
                }*/

                $this->emulation->startEnvironmentEmulation($request->getStoreId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
                $this->emailSender->sendEmail(
                    $emailRequest->getCustomerEmail(),
                    $request->getStoreId(),
                    $templateIdentifier,
                    ['email_request' => $emailRequest, 'custom_text' => $storeStatus->getCustomerCustomText()],
                    \Magento\Framework\App\Area::AREA_FRONTEND,
                    $this->configProvider->getSender($request->getStoreId())
                );
                $this->emulation->stopEnvironmentEmulation();
            }
        }
    }
}

