<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\EmailTemplate\Plugin\Model\Mail;

use Magento\Framework\Mail\TransportInterface;
use Aitoc\Smtp\Model\Config;
use Aitoc\Smtp\Controller\RegistryConstants;
use Laminas\Mail\Message as ZendMessage;
use Laminas\Mail\Address;
use Aitoc\Smtp\Model\LogFactory;
use Aitoc\Smtp\Model\Config\Options\Status;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Registry;

/**
 * Plugin over \Magento\Framework\Mail\TransportInterface
 *
 * It disables email sending depending on the system configuration settings
 */
class TransportInterfacePlugin
{
    /**
     * @var Config
     */
    private $aitConfig;

    /**
     * @var LogFactory
     */
    private $logFactory;

    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @param Config $aitConfig
     * @param LogFactory $logFactory
     * @param Registry $registry
     */
    public function __construct(
        Config $aitConfig,
        LogFactory $logFactory,
        Registry $registry
    ) {
        $this->aitConfig = $aitConfig;
        $this->logFactory = $logFactory;
        $this->registry = $registry;
    }

    public function aroundSendMessage(
        TransportInterface $subject,
        \Closure $proceed
    ) {
        $logDisabled = $this->registry->registry(RegistryConstants::CURRENT_RULE) ? true : false;

        try {
            $this->message = $this->aitConfig->prepareMessageToSend($subject->getMessage());
            $logMessage = $this->message;
            $errorData = [];

            if ($this->aitConfig->isBlockedDelivery()) {
                $modifiedRecipient = $this->modifyTo();

                if (!$modifiedRecipient) {
                    $errorData = [
                        'status' => Status::STATUS_BLOCKED,
                        'status_message' => 'Debug mode'
                    ];
                }
            }

            if (!$logDisabled) {
                $this->getLoger()->log($logMessage, $errorData);
            }
        } catch (\Exception $e) {
            $errorData = [
                'status' => Status::STATUS_FAILED,
                'status_message' => $e->getMessage()
            ];
            $logMessage = $this->message;

            if (!$logDisabled) {
                $this->getLoger()->log($logMessage, $errorData);
            }
        }

        return $proceed();

    }

    /**
     * @return \Aitoc\Smtp\Model\Log
     */
    public function getLoger()
    {
        return $this->logFactory->create();
    }

    /**
     * @return bool|ZendMessage
     */
    public function modifyTo()
    {
        if ($this->aitConfig->isNewSender(RegistryConstants::VERSION_COMPARISON_OLD_MAIL)) {
            $message = ZendMessage::fromString($this->message->getRawMessage());
        } else {
            $message = $this->message;
        }

        $toEmails = $message->getTo();

        $newEmails = [];

        if ($toEmails) {
            foreach ($toEmails as $email) {
                $name = '';
                if ($email instanceof Address) {
                    $name = $email->getName();
                    $email = $email->getEmail();
                }

                if ($this->aitConfig->needToBlockEmail($email)) {
                    continue;
                }

                $newEmails[] = [
                    'email' => $email,
                    'name' => $name
                ];
            }
        }

        if (!$newEmails) {
            return false;
        }

        $addressList = $this->aitConfig->getAddressList($newEmails);
        $message->setTo($addressList);

        return $message;
    }
}
