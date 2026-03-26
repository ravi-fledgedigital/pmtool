<?php
namespace OnitsukaTiger\Aitoc\Smtp\Model;

use Aitoc\Smtp\Api\Data\LogInterface;
use Aitoc\Smtp\Controller\RegistryConstants;
use Aitoc\Smtp\Model\Config;
use Aitoc\Smtp\Model\LogFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Store\Model\Store;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\MailException;
use Aitoc\Smtp\Model\Config\Options\Status;
use OnitsukaTiger\EmailToWareHouse\Model\Email;
use OnitsukaTiger\EmailToWareHouse\Model\Email\Template\TransportBuilder;

class Sender extends \Aitoc\Smtp\Model\Sender
{
    /**
     * @var LogFactory
     */
    private $logFactory;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Sender constructor.
     * @param LogFactory $logFactory
     * @param TransportBuilder $transportBuilder
     * @param Config $config
     * @param Filesystem $filesystem
     */
    public function __construct(
        LogFactory $logFactory,
        TransportBuilder $transportBuilder,
        Config $config,
        Filesystem $filesystem
    ) {
        $this->logFactory = $logFactory;
        $this->transportBuilder = $transportBuilder;
        $this->config = $config;
        $this->filesystem = $filesystem;
    }

    /**
     * @param $logId
     * @return bool
     */
    public function sendByLogId($logId)
    {
        $log = $this->getCurrentLog($logId);

        if (!$log->getId()) {
            return false;
        }

        $data = $log->getData();
        $data[LogInterface::EMAIL_BODY] = htmlspecialchars_decode($data[LogInterface::EMAIL_BODY]);
        $vars = [];

        if (!$data[LogInterface::EMAIL_BODY]
            || !$data[LogInterface::RECIPIENT_EMAIL]
            || !$data[LogInterface::SENDER_EMAIL]
            || !$data[LogInterface::SUBJECT]
        ) {
            return false;
        }

        $vars[LogInterface::EMAIL_BODY] = $data[LogInterface::EMAIL_BODY];
        $vars[LogInterface::SUBJECT] = $data[LogInterface::SUBJECT];

        $this->transportBuilder
            ->addTo($this->prepareEmailsData($data[LogInterface::RECIPIENT_EMAIL]))
            ->setFrom($this->prepareEmailsData($data[LogInterface::SENDER_EMAIL], true));

        if ($data[LogInterface::BCC]) {
            $this->transportBuilder->addBcc($this->prepareEmailsData($data[LogInterface::BCC]));
        }

        if ($data[LogInterface::CC]) {
            $this->transportBuilder->addCc($this->prepareEmailsData($data[LogInterface::CC]));
        }

        try {
            $this->transportBuilder
                ->setTemplateIdentifier(RegistryConstants::RESEND_EMAIL_TEMPLATE_ID)
                ->setTemplateOptions(['store' => Store::DEFAULT_STORE_ID, 'area' => Area::AREA_FRONTEND])
                ->setTemplateVars($vars);

            if ($data[Log::INVOICE_PDF_CONTENT] != '') {
                $reader = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
                $invoicePath = $reader->getAbsolutePath() . $data[Log::INVOICE_PDF_CONTENT];
                $invoicePdfContent = file_get_contents($invoicePath);
                $this->transportBuilder->addAttachment($invoicePdfContent, Email::INVOICE_PDF_NAME, 'application/pdf');
            }

            if ($data[Log::DISPATCH_PDF_CONTENT] != '') {
                $reader = $this->filesystem->getDirectoryRead(DirectoryList::ROOT);
                $invoicePath = $reader->getAbsolutePath() . $data[Log::DISPATCH_PDF_CONTENT];
                $dispatchPdfContent = file_get_contents($invoicePath);
                $this->transportBuilder->addAttachment($dispatchPdfContent, Email::DISPATCH_PDF_NAME, 'application/pdf');
            }

            $this->transportBuilder->getTransport()->sendMessage();

            $log->setData(LogInterface::STATUS, Status::STATUS_SUCCESS)
                ->setData(LogInterface::STATUS_MESSAGE, '')
                ->save();
        } catch (MailException $e) {
            $log->setData(LogInterface::STATUS, Status::STATUS_FAILED)
                ->setData(LogInterface::STATUS_MESSAGE, $e->getMessage())
                ->save();

            return false;
        }

        return true;
    }

    /**
     * @param $emails
     * @param bool $from
     * @return array|\Laminas\Mail\AddressList
     */
    private function prepareEmailsData($emails, $from = false)
    {
        $emailsConverted = [];
        $emails = explode(',', $emails);
        foreach ($emails as $email) {
            $emailData = explode('>', substr($email, 1));

            if ($from) {
                return [
                    'name' => ($emailData[0] == 'Unknown' ? null : $emailData[0]),
                    'email' => $emailData[1],
                ];
            } else {
                $emailsConverted[] = $emailData[1];
            }
        }

        return $emailsConverted;
    }

    /**
     * @return mixed
     */
    public function getCurrentLog($logId)
    {
        return $this->logFactory->create()->getLogById($logId);
    }
}
