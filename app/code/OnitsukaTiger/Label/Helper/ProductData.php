<?php

namespace OnitsukaTiger\Label\Helper;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\StockStateInterface;
use Magento\CatalogInventory\Model\Stock\ItemFactory;
use Magento\CatalogRule\Model\ResourceModel\Rule as RuleResource;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\ConfigurableFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Module\Manager;
use Magento\Framework\Pricing\Helper\Data as PricingDataHelper;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\CatalogLabel\Model\ConfigProvider;
use Psr\Log\LoggerInterface;

class ProductData extends \Mirasvit\CatalogLabel\Helper\ProductData
{

    protected $variableMapper = [
        'br'               => 'getNewLine',
        'price'            => 'getPrice',
        'final_price'      => 'getFinalPrice',
        'special_price'    => 'getSpecialPrice',
        'sku'              => 'getSku',
        'stock_qty'        => 'getStockQty',
        'salable_qty'      => 'getSalableQty',
        'discount_amount'  => 'getDiscountAmount',
        'discount_percent' => 'getDiscountPercent',
        'special_price_dl' => 'getDaysLeftForSpecialPrice',
        'new_days'         => 'getProductNewDays',
    ];
    /** 
     * @var PricingDataHelper
     */
    protected $pricingHelper;
    /**
     * @var ConfigProvider
     */
    protected $configProvider;
    /**
     * @var DateTimeFactory
     */
    protected $dateTimeFactory;
    /**
     * @var TimezoneInterface
     */
    protected $localDate;
    /**
     * @var DateTimeFormatterInterface
     */
    protected $dateTimeFormatter;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param RuleResource $ruleResource
     * @param PricingDataHelper $pricingHelper
     * @param RuleFactory $ruleFactoryModel
     * @param Manager $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param ItemFactory $stockItemFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ConfigurableFactory $productTypeConfigurableFactory
     * @param TimezoneInterface $localDate
     * @param StockStateInterface $stockState
     * @param ConfigProvider $configProvider
     * @param DateTimeFormatterInterface $dateTimeFormatter
     * @param DateTimeFactory $dateTimeFactory
     * @param LoggerInterface $logger
     * @param State $state
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        RuleResource $ruleResource,
        PricingDataHelper $pricingHelper,
        RuleFactory $ruleFactoryModel,
        Manager $moduleManager,
        StoreManagerInterface $storeManager,
        ItemFactory $stockItemFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ConfigurableFactory $productTypeConfigurableFactory,
        TimezoneInterface $localDate,
        StockStateInterface $stockState,
        ConfigProvider $configProvider,
        DateTimeFormatterInterface $dateTimeFormatter,
        DateTimeFactory $dateTimeFactory,
        LoggerInterface $logger,
        State $state,
        ProductRepositoryInterface $productRepository
    ) {
        $this->pricingHelper = $pricingHelper;
        $this->configProvider = $configProvider;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->localDate = $localDate;
        $this->dateTimeFormatter = $dateTimeFormatter;
        $this->logger = $logger;
        parent::__construct($ruleResource, $pricingHelper, $ruleFactoryModel, $moduleManager, $storeManager, $stockItemFactory, $searchCriteriaBuilder, $productTypeConfigurableFactory, $localDate, $stockState, $configProvider, $dateTimeFormatter, $dateTimeFactory, $logger, $state, $productRepository);
    }

    public function processVariables(string $text): string
    {
        foreach ($this->variableMapper as $code => $method) {
            if (str_contains($text, '[' . $code . ']')) {
                $text = str_replace(
                    '[' . $code . ']',
                    $this->formatOutput($code, (string)$this->{$method}()),
                    $text
                );
            }
        }

        $text = preg_replace_callback(
            '/\[attr\|(.*?)\]/s',
            [$this, 'replaceAttributeVariable'],
            $text
        );

        preg_match_all('/\[(\w*)\]/', $text, $matches);

        foreach ($matches[1] as $dataKey) {
            $value = $this->getProduct()->getData($dataKey);

            if (is_object($value)) {
                $value = '';
            }

            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $text = str_replace(
                '[' . $dataKey . ']',
                $this->formatOutput($dataKey, (string)$value),
                $text
            );
        }

        return $text;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function formatOutput(string $code, string $value): string
    {
        if (
            $code !== 'special_price_dl'
            && (strpos($code, 'price') !== false || $code === 'discount_amount')
        ) {
            return $this->pricingHelper->currency($value);
        }

        if ($code === 'discount_percent') {
            $value = $this->configProvider->getDiscountOutputPrecision()
                ? round((float)$value, $this->configProvider->getDiscountOutputPrecision())
                : floor((float)$value);

            return $value . '%';
        }

        if (in_array($code, ['datetime', 'date'])) {
            $dateFormat     = $this->configProvider->getDateFormat();
            $formattedValue = false;

            try {
                if (!empty($dateFormat)) {
                    $date = $this->dateTimeFactory->create($value);

                    if ($code === 'datetime') {
                        $date = $this->localDate->date($date);
                    }

                    $formattedValue = $this->dateTimeFormatter->formatObject($date, $dateFormat);

                    if ($code === 'date') {
                        $formattedValue = str_replace('00:00:00', '', $formattedValue);
                    }
                }
            } catch (Exception $exception) {
                $this->logger->error($exception->getMessage());
            }

            return $formattedValue ?: $value;
        }

        return (string)$value;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    public function replaceAttributeVariable(array $match): string
    {
        if (count($match) !== 2) {
            return '';
        }

        $attrCode = $match[1];

        $value = $this->getAttributeValue($attrCode);

        if (!$value || is_object($value)) {
            return '';
        }

        return is_array($value) ? implode(', ', $value) : (string)$value;
    }
}
