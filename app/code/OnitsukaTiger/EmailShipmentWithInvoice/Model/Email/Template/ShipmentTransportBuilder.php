<?php
namespace OnitsukaTiger\EmailShipmentWithInvoice\Model\Email\Template;

use Amasty\PDFCustom\Model\PdfProvider;
use Amasty\PDFCustom\Model\ResourceModel\TemplateRepository;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\EmailMessageInterface;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MailMessageInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use OnitsukaTiger\EmailToWareHouse\Model\Email;
use OnitsukaTiger\EmailShipmentWithInvoice\Helper\Data as HelperData;

class ShipmentTransportBuilder extends TransportBuilder
{
    /**#@+
     * supported template types
     */
    const TYPE_INVOICE = 'invoice';
    const TYPE_SHIPPING = 'shipment';
    const TYPE_CREDITMEMO = 'creditmemo';
    const TYPE_ORDER = 'order';
    /**#@-*/

    /**
     * @var \OnitsukaTiger\EmailShipmentWithInvoice\Model\MailMessageFactory
     */
    private $ammessageFactory;

    /**
     * @var PdfProvider
     */
    private $pdfProvider;

    /**
     * @var Email
     */
    private $modelEmail;

    /**
     * @var MimeMessageInterfaceFactory
     */
    private $mimeMessageInterfaceFactory;

    /**
     * @var EmailMessageInterfaceFactory
     */
    private $emailMessageInterfaceFactory;

    /**
     * @var AddressConverter
     */
    private $addressConverter;

    /**
     * @var array
     */
    private $messageData;

    /**
     * @var array
     */
    private $attachments = [];

    /**
     * @var TemplateRepository
     */
    private $templateRepository;
    /**
     * @var HelperData
     */
    private $helper;
    /**
     * @param PdfProvider $pdfProvider
     * @param Email $modelEmail
     * @param FactoryInterface $templateFactory
     * @param MessageInterface $message
     * @param SenderResolverInterface $senderResolver
     * @param ObjectManagerInterface $objectManager
     * @param TransportInterfaceFactory $mailTransportFactory
     * @param MessageInterfaceFactory $messageFactory
     * @param TemplateRepository $templateRepository
     * @param HelperData $helper
     */
    public function __construct(
        PdfProvider $pdfProvider,
        Email $modelEmail,
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        MessageInterfaceFactory $messageFactory,
        TemplateRepository $templateRepository,
        HelperData $helper
    ) {
        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory
        );
        /** @var \OnitsukaTiger\EmailShipmentWithInvoice\Model\MailMessage message */
        $this->message = $message;
        $this->ammessageFactory = $messageFactory;
        if (interface_exists(MailMessageInterface::class)) {
            $this->message = $objectManager->create(\OnitsukaTiger\EmailShipmentWithInvoice\Model\MailMessage::class);
            $this->ammessageFactory = $objectManager->create(\OnitsukaTiger\EmailShipmentWithInvoice\Model\MailMessageFactory::class);
        }
        $this->pdfProvider = $pdfProvider;
        $this->modelEmail = $modelEmail;
        if (interface_exists(EmailMessageInterface::class)) {
            $this->mimeMessageInterfaceFactory = $objectManager->create(MimeMessageInterfaceFactory::class);
            $this->emailMessageInterfaceFactory = $objectManager->create(EmailMessageInterfaceFactory::class);
            $this->addressConverter = $objectManager->create(AddressConverter::class);
        }
        $this->templateRepository = $templateRepository;
        $this->helper = $helper;
    }

    /**
     * @inheritDoc
     */
    public function getTransport()
    {
        try {
            $type = $this->getType();

            if ($type == static::TYPE_SHIPPING) {
                $this->createAttachmentByShipment();
            }

            $this->prepareMessage();

            $mailTransport = $this->mailTransportFactory->create(['message' => clone $this->message]);
        } finally {
            $this->reset();
        }

        return $mailTransport;
    }

    /**
     * @inheritDoc
     */
    protected function prepareMessage()
    {
        parent::prepareMessage();

        if ($this->mimeMessageInterfaceFactory !== null) {
            $parts = $this->message->getBody()->getParts();

            $this->messageData['body'] = $this->mimeMessageInterfaceFactory->create(
                ['parts' => array_merge($parts, $this->attachments)]
            );

            $this->messageData['subject'] = $this->message->getSubject();
            $this->message = $this->emailMessageInterfaceFactory->create($this->messageData);
        }

        return $this;
    }

    /**
     * add attachment to email message
     *
     * @param string $content
     * @param string $name
     * @param string $type
     *
     * @return $this
     */
    public function addAttachment($content, $name, $type = 'application/pdf')
    {
        $attachmentPart = $this->message->createAttachment(
            $content,
            $type,
            \Laminas\Mime\Mime::DISPOSITION_ATTACHMENT,
            \Laminas\Mime\Mime::ENCODING_BASE64,
            $name
        );

        $this->attachments[] = $attachmentPart;

        return $this;
    }

    /**
     * Render HTML invoice template, convert to PDF and attach to email
     */
    private function createAttachmentByShipment()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->getSaleObjectByType(static::TYPE_ORDER);
        $enableSendInvoice = $this->helper->isEnableSendWithInvoice($order->getStoreId());

        if ($order->hasInvoices() && $enableSendInvoice) {
            $invoice = $this->modelEmail->getInvoiceDataByOrderId($order->getId());
            $pdf = $this->pdfProvider->getInvoicePdf(reset($invoice));
            $this->addAttachment($pdf->render(), 'invoice' . '.pdf');
        }
    }

    /**
     * Return current sale template type
     *
     * @return string
     */
    private function getType()
    {
        if (isset($this->templateVars[static::TYPE_INVOICE])) {
            return static::TYPE_INVOICE;
        }

        if (isset($this->templateVars[static::TYPE_CREDITMEMO])) {
            return static::TYPE_CREDITMEMO;
        }

        if (isset($this->templateVars[static::TYPE_SHIPPING])) {
            return static::TYPE_SHIPPING;
        }

        // important: order check should be last, because any sales template contains the order variable
        if (isset($this->templateVars[static::TYPE_ORDER])) {
            return static::TYPE_ORDER;
        }

        return 'unsupported';
    }

    /**
     * @param string $type
     *
     * @return \Magento\Sales\Model\AbstractModel
     */
    protected function getSaleObjectByType($type)
    {
        return $this->templateVars[$type];
    }

    /**
     * Reset object state
     *
     * @return $this
     */
    protected function reset()
    {
        parent::reset();
        $this->message = $this->ammessageFactory->create();
        $this->messageData = [];
        $this->attachments = [];

        return $this;
    }

    /**
     * @return $this
     */
    public function clear()
    {
        $this->reset();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addTo($address, $name = '')
    {
        if ($this->mimeMessageInterfaceFactory !== null) {
            $this->addAddressByType('to', $address, $name);
            parent::addTo($address, $name);
        } else {
            $this->message->addTo($address);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setFrom($toAddress)
    {
        $toAddress = $this->_senderResolver->resolve($toAddress);

        if ($this->mimeMessageInterfaceFactory !== null) {
            $this->addAddressByType('from', $toAddress['email'], $toAddress['name']);
            parent::setFrom($toAddress);
        } else {
            $this->message->setFrom($toAddress['email'], $toAddress['name']);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setFromByScope($from, $scopeId = null)
    {
        $from = $this->_senderResolver->resolve($from, $scopeId);

        if ($this->mimeMessageInterfaceFactory !== null) {
            $this->addAddressByType('from', $from['email'], $from['name']);
            return parent::setFromByScope($from, $scopeId);
        } else {
            $this->message->setFrom($from['email'], $from['name']);
        }

        return $this;
    }

    /**
     * @param array|string $address
     */
    public function addBcc($address)
    {
        if ($this->addressConverter) {
            $this->addAddressByType('bcc', $address);
        } else {
            $this->message->addBcc($address);
        }

        return $this;
    }

    /**
     * @param string $addressType
     * @param array|string $email
     * @param null $name
     */
    private function addAddressByType($addressType, $email, $name = null)
    {
        if (is_string($email)) {
            $this->messageData[$addressType][] = $this->addressConverter->convert($email, $name);
            return;
        }
        $convertedAddressArray = $this->addressConverter->convertMany($email);
        if (isset($this->messageData[$addressType])) {
            $this->messageData[$addressType] = array_merge(
                $this->messageData[$addressType],
                $convertedAddressArray
            );
        }
    }
}
