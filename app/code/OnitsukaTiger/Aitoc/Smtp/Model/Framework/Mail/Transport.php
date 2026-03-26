<?php


namespace OnitsukaTiger\Aitoc\Smtp\Model\Framework\Mail;


use Aitoc\Smtp\Controller\RegistryConstants;
use Aitoc\Smtp\Model\Config;
use Aitoc\Smtp\Model\Config\Options\Status;
use Aitoc\Smtp\Model\LogFactory;
use Aitoc\Smtp\Model\Resolver\From;
use Magento\Email\Model\Template\SenderResolver;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Laminas\Mail\Address;
use Laminas\Mail\Message as ZendMessage;
use Laminas\Mail\Transport\Smtp;
use Laminas\Mail\Transport\SmtpOptions;

class Transport extends \Aitoc\Smtp\Model\Framework\Mail\Transport
{
    const DEFAULT_LOCAL_CLIENT_HOSTNAME = 'localhost';
    const TEST_MESSAGE_SUBJECT = 'Aitoc SMTP Test';
    const TEST_MESSAGE_BODY =
        "Now, your store uses an Aitoc SMTP. Please, hit ‘Save Config’ to use this connection.";

    /**
     * @var Sendmail
     */
    private $zendTransport;

    /**
     * @var MessageInterface
     */
    private $message;

    /**
     * @var array
     */
    private $config;

    /**
     * @var Config
     */
    private $aitConfig;

    /**
     * @var SenderResolver
     */
    private $senderResolver;

    /**
     * @var From
     */
    private $fromResolver;

    /**
     * @var LogFactory
     */
    private $logFactory;

    /**
     * @var Registry
     */
    private $registry;

    public function __construct(
        $message,
        SenderResolver $senderResolver,
        Config $aitConfig,
        From $from,
        LogFactory $logFactory,
        Registry $registry,
        array $config = []
    ) {
        $this->config = $config;
        $this->aitConfig = $aitConfig;
        $this->senderResolver = $senderResolver;
        $this->fromResolver = $from;
        $this->logFactory = $logFactory;
        $this->registry = $registry;

        if ($this->aitConfig->isNewSender(RegistryConstants::VERSION_COMPARISON_OLD_MAIL)) {
            $this->zendTransport = new Smtp($this->prepareOptions($config));
        } else {
            $this->zendTransport = new Zend_Mail_Transport_Smtp($config['host'], $config);
        }

        $this->message = $message;
        $this->setFrom();
    }

    /**
     * @return \Aitoc\Smtp\Model\Framework\Mail\Transport
     */
    public function setFrom()
    {
        $fromData = $this->fromResolver->getFrom();
        $message = ZendMessage::fromString($this->message->getRawMessage());

        if ($fromData) {
            if (($message instanceof ZendMessage && !$message->getFrom()->count())
                || ((is_array($message->getHeaders()) && !array_key_exists("From", $message->getHeaders())))
            ) {
                $this->message->setFrom($this->aitConfig->getNewAddress($fromData));
            }
        }

        return $this;
    }

    /**
     * @param $config
     * @return SmtpOptions
     */
    private function prepareOptions($config)
    {
        if (!isset($config['name']) || !$config['name']) {
            $config['name'] = self::DEFAULT_LOCAL_CLIENT_HOSTNAME;
        }

        $options = new SmtpOptions([
            'name' => isset($config['name']) ? $config['name'] : '',
            'host' => isset($config['host']) ? $config['host'] : '',
            'port' => isset($config['port']) ? $config['port'] : 465,
        ]);

        $connectionConfig = [];


        if (isset($config['auth']) && $config['auth'] != '') {
            $options->setConnectionClass($config['auth']);
            $connectionConfig = [
                'username' => isset($config['username']) ? $config['username'] : '',
                'password' => isset($config['password']) ? $config['password'] : ''
            ];
        }

        if (isset($config['ssl']) && $config['ssl']) {
            $connectionConfig['ssl'] = $config['ssl'];
        }

        if (!empty($connectionConfig)) {
            $options->setConnectionConfig($connectionConfig);
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function sendMessage()
    {
        $logDisabled = $this->registry->registry(RegistryConstants::CURRENT_RULE) ? true : false;

        try {
            $this->message = $this->aitConfig->prepareMessageToSend($this->getMessage());

            if ($this->aitConfig->isNewSender(RegistryConstants::VERSION_COMPARISON_OLD_MAIL)) {
                $message = ZendMessage::fromString($this->message->getRawMessage());
                // add for OT_DEV-1115 start
                foreach($message->getHeaders()->toArray() as $headerName => $headerValue) {
                    try {
                        $message->getHeaders()->get($headerName);
                    } catch (\Laminas\Mail\Header\Exception\InvalidArgumentException $e) { // catches only if Header is wrongly structured
                        $message->getHeaders()->removeHeader($headerName);
                        $header = new \Laminas\Mail\Header\GenericHeader();
                        $header->setEncoding('UTF-8');
                        $header->setFieldName($headerName);
                        $header->setFieldValue($headerValue);
                        $message->getHeaders()->addHeader($header);
                    }
                }
                $message->getHeaders()->setEncoding('UTF-8');
                // add for OT_DEV-1115 end
            } else {
                $message = $this->message;
            }

            // add save log for resend email (backlog ticket 1084)
            $logMessage = $this->message;

            if ($this->aitConfig->isBlockedDelivery()) {
                $modifiedRecipient = $this->modifyTo();

                if (!$modifiedRecipient) {
                    $errorData = [
                        'status' => Status::STATUS_BLOCKED,
                        'status_message' => 'Debug mode'
                    ];

                    if (!$logDisabled) {
                        $this->getLoger()->log($logMessage, $errorData);
                        return;
                    }
                }

                $message = $modifiedRecipient;
            }

            $this->zendTransport->send($message);

            if (!$logDisabled) {
                // add save log for resend email (backlog ticket 1084)
                $this->getLoger()->log($logMessage);
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
            throw new MailException(new Phrase($e->getMessage()), $e);
        }
    }

    /**
     * @return \Aitoc\Smtp\Model\Log
     */
    private function getLoger()
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

    /**
     * @param $to
     * @return bool
     * @throws MailException
     */
    public function testSend($to)
    {
        try {
            $result = false;
            $this->message = $this->aitConfig->prepareMessageToSend($this->getMessage(), true);
            $this->message
                ->addTo($to)
                ->setSubject(self::TEST_MESSAGE_SUBJECT)
                ->setBodyText(__(self::TEST_MESSAGE_BODY));

            if ($this->aitConfig->isNewSender(RegistryConstants::VERSION_COMPARISON_OLD_MAIL)) {
                $message = ZendMessage::fromString($this->message->getRawMessage());
            } else {
                $message = $this->message;
            }

            $this->zendTransport->send($message);

            return $result;
        } catch (\Exception $e) {
            throw new MailException(new Phrase($e->getMessage()), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getMessage()
    {
        return $this->message;
    }
}
