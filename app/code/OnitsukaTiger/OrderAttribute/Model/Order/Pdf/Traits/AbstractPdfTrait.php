<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Order\Pdf\Traits;

use OnitsukaTiger\OrderAttribute\Model\MagentoVersion;
use OnitsukaTiger\OrderAttribute\Model\ConfigProvider;
use OnitsukaTiger\OrderAttribute\Model\Entity\EntityResolver;
use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\FormFactory;
use Magento\Store\Model\App\Emulation;

trait AbstractPdfTrait
{
    /**
     * @var FormFactory
     */
    private $metadataFormFactory;

    /**
     * @var EntityResolver
     */
    private $entityResolver;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $currentOrder;

    /**
     * @var \Zend_Pdf_Page
     */
    private $lastPage;

    /**
     * @var bool
     */
    private $isFirstItemDrawn;

    /**
     * @var bool
     */
    private $newPageHeader = false;

    public function __construct(
        FormFactory $metadataFormFactory,
        EntityResolver $entityResolver,
        ConfigProvider $configProvider,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Sales\Model\Order\Pdf\Config $pdfConfig,
        \Magento\Sales\Model\Order\Pdf\Total\Factory $pdfTotalFactory,
        \Magento\Sales\Model\Order\Pdf\ItemsFactory $pdfItemsFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        Emulation $emulation,
        MagentoVersion $magentoVersion,
        array $data = []
    ) {
        $magentoVersionString = $magentoVersion->get();
        if (version_compare($magentoVersionString, '2.3.6', '>=')
            && $magentoVersionString !== '2.4.0'
        ) {
            $localeResolverOrEmulation = $emulation;
        } else {
            $localeResolverOrEmulation = $localeResolver;
        }

        parent::__construct(
            $paymentData,
            $string,
            $scopeConfig,
            $filesystem,
            $pdfConfig,
            $pdfTotalFactory,
            $pdfItemsFactory,
            $localeDate,
            $inlineTranslation,
            $addressRenderer,
            $storeManager,
            $localeResolverOrEmulation,
            $data
        );
        $this->metadataFormFactory = $metadataFormFactory;
        $this->entityResolver = $entityResolver;
        $this->configProvider = $configProvider;
    }

    /**
     * @inheritdoc
     */
    protected function insertOrder(&$page, $obj, $putOrderId = true)
    {
        if ($obj instanceof \Magento\Sales\Model\Order) {
            $shipment = null;
            $order = $obj;
        } elseif ($obj instanceof \Magento\Sales\Model\Order\Shipment) {
            $shipment = $obj;
            $order = $shipment->getOrder();
        }

        $this->currentOrder = $order;

        parent::insertOrder($page, $obj, $putOrderId);
    }

    /**
     * Draw Item process
     *
     * @param  \Magento\Framework\DataObject $item
     * @param  \Zend_Pdf_Page $page
     * @param  \Magento\Sales\Model\Order $order
     * @return \Zend_Pdf_Page
     */
    protected function _drawItem(
        \Magento\Framework\DataObject $item,
        \Zend_Pdf_Page $page,
        \Magento\Sales\Model\Order $order
    ) {
        if (!$this->isFirstItemDrawn) {
            $page = $this->lastPage;
            $this->isFirstItemDrawn = true;
        }

        return parent::_drawItem($item, $page, $order);
    }

    /**
     * @param  \Zend_Pdf_Page $page
     */
    protected function _drawHeader(\Zend_Pdf_Page $page)
    {
        if ($this->isPrintAttributesAllowed() && !$this->newPageHeader) {
            $this->isAlreadyDrawn = true;
            $orderAttributesData = [];
            $entity = $this->entityResolver->getEntityByOrder($this->currentOrder);
            $form = $this->createEntityForm($entity, $this->currentOrder->getStore());
            $outputData = $form->outputData(\Magento\Eav\Model\AttributeDataFactory::OUTPUT_FORMAT_HTML);
            foreach ($outputData as $attributeCode => $data) {
                if (!empty($data)) {
                    $orderAttributesData[] = [
                        'label' => $form->getAttribute($attributeCode)->getDefaultFrontendLabel(),
                        'value' => $this->resolveValue($data)
                    ];
                }
            }

            if (!empty($orderAttributesData)) {
                $this->drawOrderAttributesHeader($page);

                if ($lineBlocks = $this->createLinesBlockFromAttributes($page, $orderAttributesData)) {
                    foreach ($lineBlocks as $lineBlock) {
                        $page = $this->drawLineBlocks($page, [$lineBlock]);
                    }

                    $this->y -= 20;
                }

                if ($this->y < 80) {
                    $page = $this->newPage();
                }
            }
        }

        $this->lastPage = $page;

        parent::_drawHeader($page);
    }

    public function newPage(array $settings = [])
    {
        $this->newPageHeader = !empty($settings['table_header']);

        return parent::newPage($settings);
    }

    /**
     * Paste block title to PDF
     *
     * @param \Zend_Pdf_Page $page
     */
    protected function drawOrderAttributesHeader($page)
    {
        $page->setFillColor(new \Zend_Pdf_Color_RGB(0.93, 0.92, 0.92));
        $page->setLineColor(new \Zend_Pdf_Color_GrayScale(0.5));
        $page->setLineWidth(0.5);
        $page->drawRectangle(25, $this->y, 570, $this->y - 20);
        $this->y -= 15;
        $page->setFillColor(new \Zend_Pdf_Color_RGB(0, 0, 0));

        //columns headers
        $lines[0][] = ['text' => __('Additional Information'), 'feed' => 35, 'font' => 'bold', 'font_size' => 12];

        $lineBlock = ['lines' => $lines, 'height' => 15];

        $this->drawLineBlocks($page, [$lineBlock], ['table_header' => true]);
        $page->setFillColor(new \Zend_Pdf_Color_GrayScale(0));
        $this->y -= 20;
    }

    /**
     * @param \Zend_Pdf_Page $page
     * @param array $attributesData
     *
     * @return array
     */
    protected function createLinesBlockFromAttributes($page, $attributesData)
    {
        $lineBlocks = [];
        $labelMaxWidth = 80;
        $valueMaxWidth = 390;
        $font = $page->getFont();

        foreach ($attributesData as $attributeData) {
            $valueLines = $this->getLines($font, $attributeData['value'], $valueMaxWidth);
            $labelLines = $this->getLines($font, $attributeData['label'], $labelMaxWidth);
            $maxLines = max(count($valueLines), count($labelLines));
            for ($i = 0; $i < $maxLines; $i++) {
                $lineBlocks[] =  [
                    'lines' => [
                        [
                            [
                                'text' => !empty($labelLines[$i]) ? $labelLines[$i] : '',
                                'feed' => 30,
                                'font_size' => 10,
                                'font' => 'bold'
                            ],
                            [
                                'text' => !empty($valueLines[$i]) ? $valueLines[$i] : '',
                                'feed' => 150,
                                'font_size' => 10,
                                'font' => 'regular'
                            ]
                        ]
                    ],
                    'height' => 13
                ];
            }
            $lineBlocks[count($lineBlocks) - 1]['height'] = 19;
        }

        return $lineBlocks;
    }

    /**
     * @param \Zend_Pdf_Resource_Font $font
     * @param string $value
     * @param int $maxSize
     *
     * @return array
     */
    private function getLines($font, $value, $maxSize)
    {
        $lines = [];
        while (!empty($value)) {
            $currentChar = 0;
            $valuePart = '';
            do {
                $valuePart .= mb_substr($value, $currentChar, 1);
            } while ($currentChar++ < mb_strlen($value)
                && $maxSize > $this->widthForStringUsingFontSize($valuePart, $font, 10)
            );
            $value = mb_substr($value, $currentChar);
            $lines[] = $valuePart;
        }

        return $lines;
    }

    /**
     * Return Checkout Form instance
     *
     * @param \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData $entity
     * @param \Magento\Store\Model\Store                $store
     *
     * @return \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form
     */
    protected function createEntityForm($entity, $store)
    {
        /** @var \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form $formProcessor */
        $formProcessor = $this->metadataFormFactory->create();
        $formProcessor->setFormCode('adminhtml_order_print')
            ->setEntity($entity)
            ->setStore($store);

        return $formProcessor;
    }

    /**
     * @param array|string $attributeValue
     * @return string
     */
    private function resolveValue($attributeValue)
    {
        if (is_array($attributeValue) && isset($attributeValue['name'])) {
            $attributeValue = $attributeValue['name'];
        }

        return $attributeValue;
    }
}
