<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Observer\Rma;

use Amasty\Rma\Model\Request\Email\EmailRequest;
use Amasty\Rma\Utils\Email;
use Amasty\Rma\Model\ConfigProvider;
use Magento\Framework\Event\ObserverInterface;

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

    public function __construct(
        \Amasty\Rma\Api\StatusRepositoryInterface $statusRepository,
        \Amasty\Rma\Api\ChatRepositoryInterface $chatRepository,
        EmailRequest $emailRequest,
        Email $emailSender,
        ConfigProvider $configProvider
    ) {
        $this->statusRepository = $statusRepository;
        $this->chatRepository = $chatRepository;
        $this->emailRequest = $emailRequest;
        $this->emailSender = $emailSender;
        $this->configProvider = $configProvider;
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
                if ($storeStatus->getAdminEmailTemplate() === 0) {
                    $templateIdentifier = 'amrma_email_empty_backend';
                } else {
                    $templateIdentifier = $storeStatus->getAdminEmailTemplate();
                }
                $emails = $this->configProvider->getAdminEmails($request->getStoreId());
                if ($emails) {
                    $this->emailSender->sendEmail(
                        $emails,
                        $request->getStoreId(),
                        $templateIdentifier,
                        [
                            'email_request' => $emailRequest,
                            'order' => $emailRequest->getOrder(),
                            'custom_text' => $storeStatus->getAdminCustomText()
                        ],
                        \Magento\Framework\App\Area::AREA_ADMINHTML
                    );
                }
            }

            if ($storeStatus->isSendEmailToCustomer()) {
                if ($storeStatus->getCustomerEmailTemplate() === 0) {
                    $templateIdentifier = 'amrma_email_empty_frontend';
                } else {
                    $templateIdentifier = $storeStatus->getCustomerEmailTemplate();
                }
                $this->emailSender->sendEmail(
                    $emailRequest->getCustomerEmail(),
                    $request->getStoreId(),
                    $templateIdentifier,
                    [
                        'email_request' => $emailRequest,
                        'order' => $emailRequest->getOrder(),
                        'custom_text' => $storeStatus->getCustomerCustomText()
                    ],
                    \Magento\Framework\App\Area::AREA_FRONTEND,
                    $this->configProvider->getSender($request->getStoreId())
                );
            }
        }
    }
}
