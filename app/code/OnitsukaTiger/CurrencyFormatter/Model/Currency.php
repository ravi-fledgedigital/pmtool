<?php
namespace OnitsukaTiger\CurrencyFormatter\Model;

use Magento\Directory\Model\CurrencyConfig;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Locale\Currency as LocaleCurrency;
use Magento\Framework\Locale\ResolverInterface as LocalResolverInterface;
use Magento\Framework\NumberFormatterFactory;
use Magento\Framework\Serialize\Serializer\Json;

class Currency extends \Magento\Directory\Model\Currency
{
    private $currencyConfig;

    /**
     * @var LocalResolverInterface
     */
    private $localeResolver;

    /**
     * @var NumberFormatterFactory
     */
    private $numberFormatterFactory;

    /**
     * @var \Magento\Framework\NumberFormatter
     */
    private $numberFormatter;

    /**
     * @var array
     */
    private $numberFormatterCache;

    /**
     * @var Json
     */
    private $serializer;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Directory\Helper\Data $directoryHelper,
        \Magento\Directory\Model\Currency\FilterFactory $currencyFilterFactory,
        \Magento\Framework\Locale\CurrencyInterface $localeCurrency,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = [],
        CurrencyConfig $currencyConfig = null,
        LocalResolverInterface $localeResolver = null,
        \Magento\Framework\NumberFormatterFactory $numberFormatterFactory = null,
        Json $serializer = null
    ) {
        parent::__construct(
            $context,
            $registry,
            $localeFormat,
            $storeManager,
            $directoryHelper,
            $currencyFilterFactory,
            $localeCurrency,
            $resource,
            $resourceCollection,
            $data,
            $currencyConfig,
            $localeResolver,
            $numberFormatterFactory,
            $serializer
        );
    }

    protected function _construct()
    {
        $this->_init(\Magento\Directory\Model\ResourceModel\Currency::class);
    }
    public function getCode()
    {
        return $this->_getData('currency_code');
    }
    public function getCurrencyCode()
    {
        return $this->_getData('currency_code');
    }
    public function getRates()
    {
        return $this->_rates;
    }
    public function setRates(array $rates)
    {
        $this->_rates = $rates;
        return $this;
    }
    public function load($id, $field = null)
    {
        $this->unsRate();
        $this->setData('currency_code', $id);
        return $this;
    }
    public function getRate($toCurrency)
    {
        $code = $this->getCurrencyCodeFromToCurrency($toCurrency);
        $rates = $this->getRates();
        if (!isset($rates[$code])) {
            $rates[$code] = $this->_getResource()->getRate($this->getCode(), $toCurrency);
            $this->setRates($rates);
        }
        return $rates[$code];
    }
    public function getAnyRate($toCurrency)
    {
        $code = $this->getCurrencyCodeFromToCurrency($toCurrency);
        $rates = $this->getRates();
        if (!isset($rates[$code])) {
            $rates[$code] = $this->_getResource()->getAnyRate($this->getCode(), $toCurrency);
            $this->setRates($rates);
        }
        return $rates[$code];
    }
    public function convert($price, $toCurrency = null)
    {
        if ($toCurrency === null) {
            return $price;
        } elseif ($rate = $this->getRate($toCurrency)) {
            return (float)$price * (float)$rate;
        }

        throw new \Exception(__(
            'Undefined rate from "%1-%2".',
            $this->getCode(),
            $this->getCurrencyCodeFromToCurrency($toCurrency)
        ));
    }
    private function getCurrencyCodeFromToCurrency($toCurrency)
    {
        if (is_string($toCurrency)) {
            $code = $toCurrency;
        } elseif ($toCurrency instanceof \Magento\Directory\Model\Currency) {
            $code = $toCurrency->getCurrencyCode();
        } else {
            throw new InputException(__('Please correct the target currency.'));
        }
        return $code;
    }
    public function getFilter()
    {
        if (!$this->_filter) {
            $this->_filter = $this->_currencyFilterFactory->create(['code' => $this->getCode()]);
        }
        return $this->_filter;
    }
    public function format($price, $options = [], $includeContainer = true, $addBrackets = false)
    {
        return $this->formatPrecision($price, 2, $options, $includeContainer, $addBrackets);
    }
    public function formatPrecision(
        $price,
        $precision,
        $options = [],
        $includeContainer = true,
        $addBrackets = false
    ) {
        if (!isset($options['precision'])) {
            $options['precision'] = $precision;
        }
        if ($includeContainer) {
            return '<span class="price">' . ($addBrackets ? '[' : '') . $this->formatTxt(
                $price,
                $options
            ) . ($addBrackets ? ']' : '') . '</span>';
        }
        return $this->formatTxt($price, $options);
    }
    public function formatTxt($price, $options = [])
    {
        if (!is_numeric($price)) {
            $price = $this->_localeFormat->getNumber($price);
        }

        $price = sprintf("%F", $price);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);

        $storeCode  =  $storeManager->getStore()->getCode();

        if ($storeCode == 'web_kr_ko') {
            $options['format'] = "¤#,##0";
            $options['precision'] = 0;
        }

        if (in_array($storeCode, ['web_id_id', 'web_id_en'])) {
            $options['locale'] = 'en_US';
            $options['precision'] = 0;
            $options['requiredPrecision'] = 0;
        }

        if (in_array($storeCode, ['web_vn_vi', 'web_vn_en'])) {
            $options['locale'] = 'vi_VN';
            $options['precision'] = 2;
            $options['requiredPrecision'] = 2;
            $options['format'] = "#.##0,00 ¤";
        }

        if ($storeCode == 'web_my_en') {
            $formatedPriceWithCurrency = $this->_localeCurrency->getCurrency($this->getCode())->toCurrency($price, $options);
            return str_replace(' ', '', $formatedPriceWithCurrency);
        }

        return $this->_localeCurrency->getCurrency($this->getCode())->toCurrency($price, $options);
    }
    private function canUseNumberFormatter(array $options): bool
    {
        $allowedOptions = [
            'precision',
            LocaleCurrency::CURRENCY_OPTION_DISPLAY,
            LocaleCurrency::CURRENCY_OPTION_SYMBOL
        ];

        if (!empty(array_diff(array_keys($options), $allowedOptions))) {
            return false;
        }

        if (array_key_exists('display', $options)
            && $options['display'] !== \Magento\Framework\Currency::NO_SYMBOL
            && $options['display'] !== \Magento\Framework\Currency::USE_SYMBOL
        ) {
            return false;
        }

        return true;
    }
    private function formatCurrency(string $price, array $options): string
    {
        $customerOptions = new \Magento\Framework\DataObject([]);

        $this->_eventManager->dispatch(
            'currency_display_options_forming',
            ['currency_options' => $customerOptions, 'base_code' => $this->getCode()]
        );
        $options += $customerOptions->toArray();

        $this->numberFormatter = $this->getNumberFormatter($options);

        $formattedCurrency = $this->numberFormatter->formatCurrency(
            $price,
            $this->getCode() ?? $this->numberFormatter->getTextAttribute(\NumberFormatter::CURRENCY_CODE)
        );

        if (array_key_exists(LocaleCurrency::CURRENCY_OPTION_SYMBOL, $options)) {
            // remove only one non-breaking space from custom currency symbol to allow custom NBSP in currency symbol
            $formattedCurrency = preg_replace('/ /u', '', $formattedCurrency, 1);
        }

        if ((array_key_exists(LocaleCurrency::CURRENCY_OPTION_DISPLAY, $options)
                && $options[LocaleCurrency::CURRENCY_OPTION_DISPLAY] === \Magento\Framework\Currency::NO_SYMBOL)) {
            $formattedCurrency = str_replace(' ', '', $formattedCurrency);
        }

        return preg_replace('/^\s+|\s+$/u', '', $formattedCurrency);
    }
    private function getNumberFormatter(array $options): \Magento\Framework\NumberFormatter
    {
        $key = 'currency_' . md5($this->localeResolver->getLocale() . $this->serializer->serialize($options));
        if (!isset($this->numberFormatterCache[$key])) {
            $this->numberFormatter = $this->numberFormatterFactory->create(
                ['locale' => $this->localeResolver->getLocale(), 'style' => \NumberFormatter::CURRENCY]
            );

            $this->setOptions($options);
            $this->numberFormatterCache[$key] = $this->numberFormatter;
        }

        return $this->numberFormatterCache[$key];
    }
    private function setOptions(array $options): void
    {
        if (array_key_exists(LocaleCurrency::CURRENCY_OPTION_SYMBOL, $options)) {
            $this->numberFormatter->setSymbol(
                \NumberFormatter::CURRENCY_SYMBOL,
                $options[LocaleCurrency::CURRENCY_OPTION_SYMBOL]
            );
        }
        if (array_key_exists(LocaleCurrency::CURRENCY_OPTION_DISPLAY, $options)
            && $options[LocaleCurrency::CURRENCY_OPTION_DISPLAY] === \Magento\Framework\Currency::NO_SYMBOL) {
            $this->numberFormatter->setSymbol(\NumberFormatter::CURRENCY_SYMBOL, '');
        }
        if (array_key_exists('precision', $options)) {
            $this->numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $options['precision']);
        }
    }
    public function getCurrencySymbol()
    {
        return $this->_localeCurrency->getCurrency($this->getCode())->getSymbol();
    }
    public function getOutputFormat()
    {
        $formatted = $this->formatTxt(0);
        $number = $this->formatTxt(0, ['display' => \Magento\Framework\Currency::NO_SYMBOL]);
        return str_replace($this->trimUnicodeDirectionMark($number), '%s', $formatted);
    }
    public function getCurrencyRates($currency, $toCurrencies = null)
    {
        if ($currency instanceof \Magento\Directory\Model\Currency) {
            $currency = $currency->getCode();
        }
        $data = $this->_getResource()->getCurrencyRates($currency, $toCurrencies);
        return $data;
    }
    public function saveRates($rates)
    {
        $this->_getResource()->saveRates($rates);
        return $this;
    }
    private function trimUnicodeDirectionMark($string)
    {
        if (preg_match('/^(\x{200E}|\x{200F})/u', $string, $match)) {
            $string = preg_replace('/^' . $match[1] . '/u', '', $string);
        }
        return $string;
    }
}
