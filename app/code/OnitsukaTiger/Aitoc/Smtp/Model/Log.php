<?php
namespace OnitsukaTiger\Aitoc\Smtp\Model;

use Aitoc\Core\Model\Helpers\Date;
use Aitoc\Smtp\Api\Data\LogInterface;
use Aitoc\Smtp\Controller\RegistryConstants;
use Aitoc\Smtp\Model\Config;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class Log
 * @package OnitsukaTiger\Aitoc\Smtp\Model
 */
class Log extends \Aitoc\Smtp\Model\Log
{
    const INVOICE_PDF_CONTENT = 'invoice_pdf_content';
    const DISPATCH_PDF_CONTENT = 'dispatch_pdf_content';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Date
     */
    private $date;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Aitoc\Smtp\Model\ResourceModel\Log $resource,
        \Aitoc\Smtp\Model\ResourceModel\Log\Collection $resourceCollection,
        Config $config,
        Date $date,
        Filesystem $filesystem,
        ObjectManagerInterface $objectManager,
        array $data = [],
    )
    {
        $this->config = $config;
        $this->date = $date;
        $this->filesystem = $filesystem;
        $this->objectManager = $objectManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $config, $date, $data);
    }

    /**
     * Note : override this method because need to override getLogData (private method)
     * @param $message
     * @param array $errorData
     * @return \Aitoc\Smtp\Model\Log
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function log($message, $errorData = [])
    {
        if ($this->config->logEnabled()) {
            $logData = $this->getLogData($message);
            $logData[LogInterface::CREATED_AT] = $this->date->getCurrentDate();
            $logData[LogInterface::STATUS] = $errorData ? $errorData[LogInterface::STATUS] : 0;
            $logData[LogInterface::STATUS_MESSAGE] = $errorData ? $errorData[LogInterface::STATUS_MESSAGE] : '';

            $this->setData($logData);
            $this->_resource->save($this);
        }

        return $this;
    }

    /**
     * @param $message
     * @return array
     */
    private function getLogData($message)
    {
        $result = [];

        if ($this->config->isNewSender(RegistryConstants::VERSION_COMPARISON_OLD_MAIL)) {
            $result[LogInterface::SUBJECT] = $message->getSubject() ?: '';
            $result[LogInterface::SENDER_EMAIL] = $this->getEmailsFromAddressList($message->getFrom());
            $result[LogInterface::RECIPIENT_EMAIL] = $this->getEmailsFromAddressList($message->getTo());
            $result[LogInterface::BCC] = $this->getEmailsFromAddressList($message->getBcc());
            $result[LogInterface::CC] = $this->getEmailsFromAddressList($message->getCc());
            foreach ($message->getBody()->getParts() as $part) {
                if ($part->getType() == 'application/pdf') {
                    if (strtolower($part->getFilename()) == 'invoice.pdf') {
                        $result[self::INVOICE_PDF_CONTENT] = $this->savePdfFile('invoice', $part->getRawContent());
                    } elseif (strtolower($part->getFilename()) == 'dispatch.pdf') {
                        $result[self::DISPATCH_PDF_CONTENT] = $this->savePdfFile('dispatch', $part->getRawContent());
                    }
                } else {
                    $result[LogInterface::EMAIL_BODY] = htmlspecialchars($part->getRawContent());
                }
            }
        } else {
            $headers = $message->getHeaders();
            $result[LogInterface::SUBJECT] = isset($headers['Subject'][0]) ? $headers['Subject'][0] : '';
            $result[LogInterface::SENDER_EMAIL] = isset($headers['From'][0]) ? $headers['From'][0] : '';

            if (isset($headers['To'])) {
                $recipient = $headers['To'];
                if (isset($recipient['append'])) {
                    unset($recipient['append']);
                }

                $result[LogInterface::RECIPIENT_EMAIL] = $this->getEmailsFromAddressList($recipient);
            }

            if (isset($headers['Cc'])) {
                $cc = $headers['Cc'];
                if (isset($cc['append'])) {
                    unset($cc['append']);
                }

                $result[LogInterface::CC] = $this->getEmailsFromAddressList($cc);
            }

            if (isset($headers['Bcc'])) {
                $bcc = $headers['Bcc'];
                if (isset($bcc['append'])) {
                    unset($bcc['append']);
                }

                $result[LogInterface::BCC] = $this->getEmailsFromAddressList($bcc);
            }

            $emailBody = $message->getBodyHtml();

            if (is_object($emailBody)) {
                $result[LogInterface::EMAIL_BODY] = htmlspecialchars($emailBody->getRawContent());
            } else {
                $result[LogInterface::EMAIL_BODY] = htmlspecialchars($message->getBody()->getRawContent());
            }
        }

        return $result;
    }

    /**
     * @param $emails
     * @return string
     */
    private function getEmailsFromAddressList($emails)
    {
        $result = [];

        if (count($emails)) {
            foreach ($emails as $email) {
                $name = 'Unknown';

                if ($email->getName()) {
                    $name = $email->getName();
                }

                $result[] = "<" . $name . ">" . $email->getEmail();
            }
        }

        return implode(',', $result);
    }

    /**
     * @param $fileName
     * @param $content
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    private function savePdfFile($fileName, $content)
    {
        $date = $this->objectManager->get(
            \Magento\Framework\Stdlib\DateTime\DateTime::class
        )->date('Y-m-d_H-i-s');

        $dir = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $dir->create('/pdf');
        $dir->writeFile('/pdf/' . $fileName . $date . '.pdf', $content);

        return DirectoryList::VAR_DIR . '/pdf/' . $fileName . $date . '.pdf';
    }
}
