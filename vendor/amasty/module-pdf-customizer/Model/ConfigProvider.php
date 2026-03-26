<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model;

use Amasty\PDFCustom\Model\ResourceModel\TemplateRepository;
use Amasty\PDFCustom\Model\Source\PlaceForUse;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigProvider extends \Amasty\Base\Model\ConfigProviderAbstract
{
    public const MODULE_SECTION = 'ampdf/';
    public const XPATH_ENABLED = 'general/enabled';
    public const XPATH_USED_LIBRARY = 'general/used_library';
    /** Invoice Group */
    public const XPATH_INVOICE_ATTACH = 'invoice/email_attach';
    public const XPATH_INVOICE_LINK_TYPE = 'invoice/link_type';
    /** Order Group */
    public const XPATH_ORDER_ATTACH = 'order/email_attach';
    public const XPATH_ORDER_LINK_TYPE = 'order/link_type';
    public const XPATH_ORDER_LINK_LABEL = 'order/link_label';
    /** Shipment Group */
    public const XPATH_SHIPMENT_ATTACH = 'shipment/email_attach';
    public const XPATH_SHIPMENT_LINK_TYPE = 'shipment/link_type';
    /** Creditmemo Group */
    public const XPATH_CREDITMEMO_ATTACH = 'creditmemo/email_attach';
    public const XPATH_CREDITMEMO_LINK_TYPE = 'creditmemo/link_type';

    /**
     * xpath prefix of module (section)
     * @var string '{section}/'
     */
    protected $pathPrefix = self::MODULE_SECTION;

    /**
     * @var TemplateRepository
     */
    private $templateRepository;

    /**
     * @var ComponentChecker
     */
    private $componentChecker;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        TemplateRepository $templateRepository,
        ComponentChecker $componentChecker
    ) {
        parent::__construct($scopeConfig);
        $this->templateRepository = $templateRepository;
        $this->componentChecker = $componentChecker;
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return $this->isSetFlag(static::XPATH_ENABLED, $storeId) && $this->componentChecker->isComponentsExist();
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     * @param int $customerGroupId
     *
     * @return int
     * @deprecated WARNING! This method works incorrect with default params!
     * @see \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    public function getInvoiceTemplateId($storeId = null, $customerGroupId = 0)
    {
        if ($storeId instanceof \Magento\Store\Model\ScopeInterface) {
            $storeId = $storeId->getId();
        }
        return $this->templateRepository->getInvoiceTemplateId(
            $storeId,
            $customerGroupId
        );
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return bool
     */
    public function isAttachInvoice($storeId = null)
    {
        return $this->isSetFlag(static::XPATH_INVOICE_ATTACH, $storeId);
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return int
     */
    public function getInvoiceLinkType($storeId = null)
    {
        return $this->getValue(static::XPATH_INVOICE_LINK_TYPE, $storeId);
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     * @param int $customerGroupId
     *
     * @return int
     * @deprecated WARNING! This method works incorrect with default params!
     * @see \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    public function getOrderTemplateId($storeId = null, $customerGroupId = 0)
    {
        if ($storeId instanceof \Magento\Store\Model\ScopeInterface) {
            $storeId = $storeId->getId();
        }
        return $this->templateRepository->getOrderTemplateId(
            $storeId,
            $customerGroupId
        );
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return bool
     */
    public function isAttachOrder($storeId = null)
    {
        return $this->isSetFlag(static::XPATH_ORDER_ATTACH, $storeId);
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderLinkType($storeId = null)
    {
        return $this->getValue(static::XPATH_ORDER_LINK_TYPE, $storeId);
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return string
     */
    public function getOrderLinkLabel($storeId = null)
    {
        return $this->getValue(static::XPATH_ORDER_LINK_LABEL, $storeId);
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     * @param int $customerGroupId
     *
     * @return int
     * @deprecated WARNING! This method works incorrect with default params!
     * @see \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    public function getShipmentTemplateId($storeId = null, $customerGroupId = 0)
    {
        if ($storeId instanceof \Magento\Store\Model\ScopeInterface) {
            $storeId = $storeId->getId();
        }
        return $this->templateRepository->getShipmentTemplateId(
            $storeId,
            $customerGroupId
        );
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return bool
     */
    public function isAttachShipment($storeId = null)
    {
        return $this->isSetFlag(static::XPATH_SHIPMENT_ATTACH, $storeId);
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return int
     */
    public function getShipmentLinkType($storeId = null)
    {
        return $this->getValue(static::XPATH_SHIPMENT_LINK_TYPE, $storeId);
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     * @param int $customerGroupId
     *
     * @return int
     * @deprecated WARNING! This method works incorrect with default params!
     * @see \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    public function getCreditmemoTemplateId($storeId = null, $customerGroupId = 0)
    {
        if ($storeId instanceof \Magento\Store\Model\ScopeInterface) {
            $storeId = $storeId->getId();
        }
        return $this->templateRepository->getCreditmemoTemplateId(
            $storeId,
            $customerGroupId
        );
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return bool
     */
    public function isAttachCreditmemo($storeId = null)
    {
        return $this->isSetFlag(static::XPATH_CREDITMEMO_ATTACH, $storeId);
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return int
     */
    public function getCreditmemoLinkType($storeId = null)
    {
        return $this->getValue(static::XPATH_CREDITMEMO_LINK_TYPE, $storeId);
    }

    /**
     * @param int|\Magento\Store\Model\ScopeInterface|null $storeId
     *
     * @return string
     */
    public function getLibraryCode($storeId = null): string
    {
        return (string) $this->getValue(static::XPATH_USED_LIBRARY, $storeId);
    }
}
