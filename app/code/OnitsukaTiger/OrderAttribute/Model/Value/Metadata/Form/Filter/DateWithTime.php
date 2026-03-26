<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

/**
 * Form Input/Output Date with time Filter
 */
namespace OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\Filter;

use Magento\Framework\Stdlib\DateTime;

class DateWithTime implements \Magento\Framework\Data\Form\Filter\FilterInterface
{
    /**
     * Sometimes Magento is not returning seconds - remove seconds from pattern before validate
     */
    public const DATETIME_INTERNAL_VALIDATION_FORMAT = 'yyyy-MM-dd HH:mm';

    /**
     * @var string
     */
    protected $dateFormat;

    /**
     * Local
     *
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localeResolver;

    public function __construct(
        $format = null,
        \Magento\Framework\Locale\ResolverInterface $localeResolver = null
    ) {
        if ($format === null) {
            $format = DateTime::DATETIME_INTERNAL_FORMAT;
        }
        $this->dateFormat = $format;
        $this->localeResolver = $localeResolver;
    }

    /**
     * Returns the result of filtering $value
     *
     * @param string $value date in format $this->_dateFormat
     *
     * @return string          date in format DateTime::DATETIME_INTERNAL_FORMAT
     */
    public function inputFilter($value)
    {
        if (!$this->validateInputDate($value)) {
            return $value;
        }
        $options = [
            'date_format' => $this->dateFormat,
            'locale'      => $this->localeResolver->getLocale()
        ];
        $filterInternal = new \Zend_Filter_NormalizedToLocalized(
            ['date_format' => DateTime::DATETIME_INTERNAL_FORMAT, 'locale' => $this->localeResolver->getLocale()]
        );

        //parse date
        $value = \Zend_Locale_Format::getDate($value, $options);
        $value = $filterInternal->filter($value);

        return $value;
    }

    /**
     * Returns the result of filtering $value
     *
     * @param string $value date in format DateTime::DATETIME_INTERNAL_FORMAT
     *
     * @return string         date in format $this->_dateFormat
     */
    public function outputFilter($value)
    {
        if (!$this->validateOutputDate($value)) {
            return $value;
        }
        $options = [
            'date_format' => DateTime::DATETIME_INTERNAL_FORMAT,
            'locale'      => $this->localeResolver->getLocale()
        ];
        $filterInternal = new \Zend_Filter_NormalizedToLocalized(
            ['date_format' => $this->dateFormat, 'locale' => $this->localeResolver->getLocale()]
        );

        //parse date
        $value = \Zend_Locale_Format::getDate($value, $options);
        $value = $filterInternal->filter($value);

        return $value;
    }

    /**
     * Sometimes Magento is not returning seconds - remove seconds from pattern before validate
     * if in date pattern will be seconds, it will not passed
     *
     * @param string $value
     *
     * @return bool
     */
    public function validateInputDate($value)
    {
        $options = [
            'date_format' => str_replace('s', '', $this->dateFormat),
            'locale'      => $this->localeResolver->getLocale()
        ];

        return \Zend_Locale_Format::checkDateFormat($value, $options);
    }

    /**
     * Sometimes Magento is not returning seconds - remove seconds from pattern before validate
     * if in date pattern will be seconds, it will not passed
     *
     * @param string $value
     *
     * @return bool
     */
    public function validateOutputDate($value)
    {
        $options = [
            'date_format' => self::DATETIME_INTERNAL_VALIDATION_FORMAT,
            'locale'      => $this->localeResolver->getLocale()
        ];

        return \Zend_Locale_Format::checkDateFormat($value, $options);
    }
}
