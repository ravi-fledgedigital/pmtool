<?php
namespace OnitsukaTiger\CurrencyFormatter\Model;

use Magento\Framework\Locale\Bundle\DataBundle;

class Format extends \Magento\Framework\Locale\Format
{
    /**
     * @var string
     */
    private static $defaultNumberSet = 'latn';

    /**
     * Get Price Format
     *
     * @param mixed $localeCode
     * @param mixed $currencyCode
     * @return array
     */
    public function getPriceFormat($localeCode = null, $currencyCode = null)
    {
        $localeCode = $localeCode ?: $this->_localeResolver->getLocale();
        if ($currencyCode) {
            $currency = $this->currencyFactory->create()->load($currencyCode);
        } else {
            $currency = $this->_scopeResolver->getScope()->getCurrentCurrency();
        }

        $localeData = (new DataBundle())->get($localeCode);
        $defaultSet = $localeData['NumberElements']['default'] ?: self::$defaultNumberSet;

        $format = $localeData['NumberElements'][$defaultSet]['patterns']['currencyFormat']
            ?: ($localeData['NumberElements'][self::$defaultNumberSet]['patterns']['currencyFormat']
                ?: explode(';', $localeData['NumberPatterns'][1])[0]);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
        $storeCode  =  $storeManager->getStore()->getCode();
        $decimalSymbol = '.';
        $groupSymbol = ',';

        $pos = strpos($format, ';');
        if ($pos !== false) {
            $format = substr($format, 0, $pos);
        }
        $format = preg_replace("/[^0\#\.,]/", "", $format);
        $totalPrecision = 0;
        $decimalPoint = strpos($format, '.');
        if ($decimalPoint !== false) {
            $totalPrecision = strlen($format) - (strrpos($format, '.') + 1);
        } else {
            $decimalPoint = strlen($format);
        }
        $requiredPrecision = $totalPrecision;
        $t = substr($format, $decimalPoint);
        $pos = strpos($t, '#');
        if ($pos !== false) {
            $requiredPrecision = strlen($t) - $pos - $totalPrecision;
        }

        if (strrpos($format, ',') !== false) {
            $group = $decimalPoint - strrpos($format, ',') - 1;
        } else {
            $group = strrpos($format, '.');
        }
        $integerRequired = strpos($format, '.') - strpos($format, '0');

        $result = [
            //TODO: change interface
            'pattern' => $currency->getOutputFormat(),
            'precision' => $totalPrecision,
            'requiredPrecision' => $requiredPrecision,
            'decimalSymbol' => $decimalSymbol,
            'groupSymbol' => $groupSymbol,
            'groupLength' => $group,
            'integerRequired' => $integerRequired,
        ];

        if ($storeCode == 'web_kr_ko') {
            $result['precision'] = $result['requiredPrecision'] = 0;
        }

        if (in_array($storeCode, ['web_id_id', 'web_id_en'])) {
            $result['precision'] = $result['requiredPrecision'] = 0;
        }

        if (in_array($storeCode, ['web_vn_vi', 'web_vn_en'])) {
            $result['precision'] = 2;
            $result['requiredPrecision'] = 2;
            $result['decimalSymbol'] = ',';
            $result['groupSymbol'] = '.';
            $result['groupLength'] = $group;
        }

        return $result;
    }
}
