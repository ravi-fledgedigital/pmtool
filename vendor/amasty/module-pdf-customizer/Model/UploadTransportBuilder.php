<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model;

use Amasty\Base\Model\MagentoVersion;
use Amasty\Base\Utils\Email\MultipartMimeMessageFactory;
use Amasty\Base\Utils\Email\TransportBuilder;
use Amasty\PDFCustom\Model\ResourceModel\TemplateRepository;
use Magento\Framework\Mail\AddressConverter;
use Magento\Framework\Mail\EmailMessageInterfaceFactory;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\MimeMessageInterfaceFactory;
use Magento\Framework\Mail\MimePartInterfaceFactory;
use Magento\Framework\Mail\Template\FactoryInterface;
use Magento\Framework\Mail\Template\SenderResolverInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;

class UploadTransportBuilder extends TransportBuilder
{
    /**#@+
     * supported template types
     */
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_SHIPPING = 'shipment';
    public const TYPE_CREDITMEMO = 'creditmemo';
    public const TYPE_ORDER = 'order';
    /**#@-*/

    /**
     * @var PdfProvider
     */
    private $pdfProvider;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    public function __construct(
        PdfProvider $pdfProvider,
        ConfigProvider $configProvider,
        FactoryInterface $templateFactory,
        MessageInterface $message,
        SenderResolverInterface $senderResolver,
        ObjectManagerInterface $objectManager,
        TransportInterfaceFactory $mailTransportFactory,
        MessageInterfaceFactory $messageFactory,
        TemplateRepository $templateRepository,
        AddressConverter $addressConverter,
        MultipartMimeMessageFactory $multipartMimeMessageFactory,
        MimeMessageInterfaceFactory $mimeMessageInterfaceFactory,
        EmailMessageInterfaceFactory $emailMessageInterfaceFactory,
        MimePartInterfaceFactory $mimePartFactory,
        MagentoVersion $magentoVersion
    ) {
        parent::__construct(
            $templateFactory,
            $message,
            $senderResolver,
            $objectManager,
            $mailTransportFactory,
            $messageFactory,
            $addressConverter,
            $multipartMimeMessageFactory,
            $mimeMessageInterfaceFactory,
            $emailMessageInterfaceFactory,
            $mimePartFactory,
            $magentoVersion
        );
        $this->configProvider = $configProvider;
        $this->pdfProvider = $pdfProvider;
        $this->templateRepository = $templateRepository;
    }

    /**
     * @inheritDoc
     */
    public function getTransport()
    {
        try {
            $type = $this->getType();

            if ($this->isAttachmentAllowed($type)) {
                $this->createAttachmentByType($type);
            }

            $this->prepareMessage();

            $mailTransport = $this->mailTransportFactory->create(['message' => clone $this->message]);
        } finally {
            $this->reset();
        }

        return $mailTransport;
    }

    /**
     * Render HTML template, convert to PDF and attach to email
     *
     * @param string $type
     */
    private function createAttachmentByType($type)
    {
        switch ($type) {
            case static::TYPE_INVOICE:
                /** @var \Magento\Sales\Model\Order\Invoice $saleObject */
                $saleObject = $this->getSaleObjectByType($type);
                $pdf = $this->pdfProvider->getInvoicePdf($saleObject);
                $this->addAttachment($pdf->render(), 'invoice' . $saleObject->getIncrementId() . '.pdf');
                break;
            case static::TYPE_SHIPPING:
                /** @var \Magento\Sales\Model\Order\Shipment $saleObject */
                $saleObject = $this->getSaleObjectByType($type);
                $pdf = $this->pdfProvider->getShipmentPdf($saleObject);
                $this->addAttachment($pdf->render(), 'shipment' . $saleObject->getIncrementId() . '.pdf');
                break;
            case static::TYPE_CREDITMEMO:
                /** @var \Magento\Sales\Model\Order\Creditmemo $saleObject */
                $saleObject = $this->getSaleObjectByType($type);
                $pdf = $this->pdfProvider->getCreditmemoPdf($saleObject);
                $this->addAttachment($pdf->render(), 'creditmemo' . $saleObject->getIncrementId() . '.pdf');
                break;
            case static::TYPE_ORDER:
                /** @var \Magento\Sales\Model\Order $saleObject */
                $saleObject = $this->getSaleObjectByType($type);
                $pdf = $this->pdfProvider->getOrderPdf($saleObject);
                $this->addAttachment($pdf->render(), 'order' . $saleObject->getIncrementId() . '.pdf');
                break;
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
     * is current type allowed to render HTML PDF and add to email as attachment
     *
     * @param string $type
     *
     * @return bool
     */
    private function isAttachmentAllowed($type)
    {
        if (!$this->configProvider->isEnabled()) {
            return false;
        }
        switch ($type) {
            case static::TYPE_INVOICE:
                $saleObject = $this->getSaleObjectByType($type);
                $storeId = $saleObject->getStoreId();
                $customerGroupId = $saleObject->getOrder()->getCustomerGroupId();

                return $this->templateRepository->getInvoiceTemplateId($storeId, $customerGroupId)
                    && $this->configProvider->isAttachInvoice($storeId);
            case static::TYPE_SHIPPING:
                $saleObject = $this->getSaleObjectByType($type);
                $storeId = $saleObject->getStoreId();
                $customerGroupId = $saleObject->getOrder()->getCustomerGroupId();

                return $this->templateRepository->getShipmentTemplateId($storeId, $customerGroupId)
                    && $this->configProvider->isAttachShipment($storeId);
            case static::TYPE_CREDITMEMO:
                $saleObject = $this->getSaleObjectByType($type);
                $storeId = $saleObject->getStoreId();
                $customerGroupId = $saleObject->getOrder()->getCustomerGroupId();

                return $this->templateRepository->getCreditmemoTemplateId($storeId, $customerGroupId)
                    && $this->configProvider->isAttachCreditmemo($storeId);
            case static::TYPE_ORDER:
                $saleObject = $this->getSaleObjectByType($type);
                $storeId = $saleObject->getStoreId();
                $customerGroupId = $saleObject->getCustomerGroupId();

                return $this->templateRepository->getOrderTemplateId($storeId, $customerGroupId)
                    && $this->configProvider->isAttachOrder($storeId);
        }

        return false;
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
     * @return $this
     */
    public function clear()
    {
        $this->reset();

        return $this;
    }
}
