<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Utils;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Validator\EmailAddress;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

class Email
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ScopeConfigInterface
     */
    private $configProvider;

    /**
     * @var EmailAddress
     */
    private $emailValidator;

    public function __construct(
        StoreManagerInterface $storeManager,
        TransportBuilder $transportBuilder,
        LoggerInterface $logger,
        ScopeConfigInterface $configProvider,
        ?EmailAddress $emailValidator = null //TODO move to not optional
    ) {
        $this->storeManager = $storeManager;
        $this->transportBuilder = $transportBuilder;
        $this->logger = $logger;
        $this->configProvider = $configProvider;
        // OM for backward compatibility
        $this->emailValidator = $emailValidator ?? ObjectManager::getInstance()->get(EmailAddress::class);
    }

    public function sendEmail(
        $emailTo = '',
        $storeId = 0,
        $templateIdentifier = '',
        $vars = [],
        $area = \Magento\Framework\App\Area::AREA_FRONTEND,
        $sendFrom = ''
    ) {
        try {
            /** @var \Magento\Store\Model\Store $store */
            $store = $this->storeManager->getStore($storeId);
            $data =  array_merge(
                [
                    'website_name' => $store->getWebsite()->getName(),
                    'group_name' => $store->getGroup()->getName(),
                    'store_name' => $store->getName(),
                ],
                $vars
            );

            if (empty($sendFrom)) {
                $sendFrom = 'general';
            }

            if (!is_array($emailTo)) {
                $emailTo = [$emailTo];
            }

            foreach ($emailTo as $reciever) {
                if (!$this->emailValidator->isValid($reciever)) {
                    continue;
                }

                $transportBuild = $this->transportBuilder->setTemplateIdentifier($templateIdentifier)
                    ->setTemplateOptions(['area' => $area, 'store' => $store->getId()])
                    ->setTemplateVars($data)
                    ->addTo($reciever);
                if (method_exists($transportBuild, 'setFromByScope')) {
                    $transportBuild->setFromByScope($sendFrom, $storeId);
                } else {
                    $transportBuild->setFrom($sendFrom);
                }

                $transportBuild->getTransport()->sendMessage();
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        }
    }
}
