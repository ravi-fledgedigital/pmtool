<?php

namespace Cpss\Pos\Helper;

use Magento\Framework\App\Area;
use \Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use \Magento\Framework\Mail\Template\TransportBuilder;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Framework\Translate\Inline\StateInterface;

class Mail
{

    const POS_MAILER_ENABLED = 'crm/pos_mailer/active';
    const POS_MAILER_EMAIL_IDENTITY = 'crm/pos_mailer/pos_email_identity';
    const POS_MAILER_EMAIL_TEMPLATE = 'crm/pos_mailer/pos_email_template';
    const POS_MAILER_RECIPIENT_EMAIL = 'crm/pos_mailer/pos_recipient_email';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfigInterface;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Cpss\Pos\Logger\Logger
     */
    protected $logger;

    public function __construct(
        ScopeConfigInterface $scopeConfigInterface,
        TransportBuilder $transportBuilder,
        StateInterface $translation,
        StoreManagerInterface $storeManager,
        \Cpss\Pos\Logger\Logger $logger
    ) {
        $this->scopeConfigInterface = $scopeConfigInterface;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $translation;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
    }

    public function sendEmail($data, $emailType = null, $errorMessage = null)
    {
        try {
            if (!$this->getConfigValue(self::POS_MAILER_ENABLED)) {
                $this->logger->info("POS MAILER is disabled. Will not send email.");
                return;
            }

            $storeId = $this->storeManager->getStore()->getId();
            // template variables pass here
            $tempData["data"] = $data;
            $tempData["emailType"] = $emailType;
            $tempData["errMessage"] = $errorMessage;
            $templateVars = [
                "posData" => $tempData
            ];

            $templateOptions = [
                'area' => Area::AREA_ADMINHTML,
                'store' => $storeId
            ];

            $this->inlineTranslation->suspend();
            $transport = $this->transportBuilder
                ->setTemplateIdentifier(
                    $this->getConfigValue(self::POS_MAILER_EMAIL_TEMPLATE),
                    ScopeInterface::SCOPE_STORE
                )->setTemplateOptions(
                    $templateOptions
                )->setTemplateVars(
                    $templateVars
                )->setFrom(
                    $this->getConfigValue(self::POS_MAILER_EMAIL_IDENTITY)
                )->addTo(
                    $this->getConfigValue(self::POS_MAILER_RECIPIENT_EMAIL)
                )->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }

    public function getConfigValue($path)
    {
        return $this->scopeConfigInterface->getValue($path, ScopeInterface::SCOPE_STORE);
    }
}
