<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace OnitsukaTiger\Customer\Block\Widget;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Framework\Api\ArrayObjectSearch;

/**
 * Class Dob
 *
 * @SuppressWarnings(PHPMD.DepthOfInheritance)
 */
class Dob extends \Magento\Customer\Block\Widget\Dob
{
    /**
     * Constants for borders of date-type customer attributes
     */
    const MIN_DATE_RANGE_KEY = 'date_range_min';

    const MAX_DATE_RANGE_KEY = 'date_range_max';

    /**
     * Date inputs
     *
     * @var array
     */
    protected $_dateInputs = [];

    /**
     * @var \Magento\Framework\View\Element\Html\Date
     */
    protected $dateElement;

    /**
     * @var \Magento\Framework\Data\Form\FilterFactory
     */
    protected $filterFactory;

    /**
     * @var \OnitsukaTigerKorea\Customer\Helper\Data
     */
    private $dataHelper;

    /**
     * @var \OnitsukaTiger\Store\Helper\Data
     */
    private $helperStore;


    /**
     * @var Element\Html\Date
     */
    private $dateOfBirthElement;

    /**
     * Dob constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param \OnitsukaTigerKorea\Customer\Helper\Data $dataHelper
     * @param \OnitsukaTiger\Store\Helper\Data $helperStore
     * @param CustomerMetadataInterface $customerMetadata
     * @param \Magento\Framework\View\Element\Html\Date $dateElement
     * @param Element\Html\Date $dateofBirthElement
     * @param \Magento\Framework\Data\Form\FilterFactory $filterFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Address $addressHelper,
        \OnitsukaTigerKorea\Customer\Helper\Data $dataHelper,
        \OnitsukaTiger\Store\Helper\Data $helperStore,
        CustomerMetadataInterface $customerMetadata,
        \Magento\Framework\View\Element\Html\Date $dateElement,
        \OnitsukaTiger\Customer\Block\Widget\Element\Html\Date $dateofBirthElement,
        \Magento\Framework\Data\Form\FilterFactory $filterFactory,
        array $data = []
    )
    {
        $this->dataHelper = $dataHelper;
        $this->dateElement = $dateElement;
        $this->dateOfBirthElement = $dateofBirthElement;
        $this->filterFactory = $filterFactory;
        $this->helperStore = $helperStore;
        parent::__construct($context, $addressHelper, $customerMetadata, $dateElement, $filterFactory, $data);
    }


    /**
     * Create correct date field
     *
     * @return string
     */
    public function getFieldHtml()
    {
        $paramsDob = [
            'extra_params' => $this->getHtmlExtraParams(),
            'name' => $this->getHtmlId(),
            'id' => $this->getHtmlId(),
            'class' => $this->getHtmlClass(),
            'value' => $this->getValue(),
            'image' => $this->getViewFileUrl('Magento_Theme::calendar.png'),
            'years_range' => '-120y:c+nn',
            'change_month' => 'true',
            'change_year' => 'true',
            'show_on' => 'both',
            'first_day' => $this->getFirstDay()
        ];
        if ($this->dataHelper->isKoreanThemeEnable($this->_storeManager->getStore()->getId())) {
            $paramsDob['max_date'] = '-14y';
            $paramsDob['date_format'] = $this->getDateFormat();
            $this->dateElement->setData($paramsDob);
            return $this->dateElement->getHtml();
        } else {
            $paramsDob['max_date'] = '-18y';
            $paramsDob['date_format'] = $this->convertDateFormat($this->getDateFormat());
            $this->dateOfBirthElement->setData($paramsDob);
            return $this->dateOfBirthElement->getHtml();
        }
    }

    /**
     * @return mixed
     */
    public function getDob()
    {
        return $this->getValue();
    }

    public function convertDateFormat($dateLocale)
    {
        $SYMBOLS_MATCHING = array(
            'd' => 'dd',
            'D' => 'D',
            'j' => 'd',
            'l' => 'DD',
            'z' => 'o',
            'F' => 'MM',
            'm' => 'mm',
            'M' => 'M',
            'n' => 'm',
            'Y' => 'yy',
            'y' => 'y',
        );
        $dateFormat = "";
        $escaping = false;
        for ($i = 0; $i < strlen($dateLocale); $i++) {
            $char = $dateLocale[$i];
            if ($char === '\\')
            {
                $i++;
                if ($escaping) {
                    $dateFormat .= $dateLocale[$i];
                } else {
                    $dateFormat .= '\'' . $dateLocale[$i];
                }
                $escaping = true;
            } else {
                if ($escaping) {
                    $dateFormat .= "'";
                    $escaping = false;
                }

                if (isset($SYMBOLS_MATCHING[$char])) {
                    $dateFormat .= $SYMBOLS_MATCHING[$char];
                } else {
                    $dateFormat .= $char;
                }
            }
        }
        return $dateFormat;
    }

    /**
     * Set date
     *
     * @param string $date
     * @return \Magento\Customer\Block\Widget\Dob
     */
    public function setDate($date)
    {
        $this->setValue('');
        if ($date) {
            $this->setTime($this->filterTime($date));
            $dateFormat = $this->helperStore->formatDateOfDob($this->_storeManager->getStore()->getId());
            $valueDate = \DateTime::createFromFormat('Y-m-d', $date);
            if($valueDate) {
                $dateLocaleData = $valueDate->format($dateFormat);
                $this->setValue($dateLocaleData);
            }
        }

        return $this;
    }

    /**
     * Sanitizes time
     *
     * @param mixed $value
     * @return bool|int
     */
    private function filterTime($value)
    {
        $time = false;
        if ($value) {
            if ($value instanceof \DateTimeInterface) {
                $time = $value->getTimestamp();
            } elseif (is_numeric($value)) {
                $time = $value;
            } elseif (is_string($value)) {
                $time = strtotime($value);
                $time = $time === false ? $this->_localeDate->date($value, null, false, false)->getTimestamp() : $time;
            }
        }

        return $time;
    }

    /**
     * Return data-validate rules
     *
     * @return string
     */
    public function getHtmlExtraParams()
    {
        $validators = [];
        if ($this->isRequired()) {
            $validators['required'] = true;
        }
        $validators['validate-date-birth'] = [
            'dateFormat' => $this->getDateFormat()
        ];
        $validators['validate-dob-custom'] = true;

        return 'readonly="readonly" data-validate="' . $this->_escaper->escapeHtml(json_encode($validators)) . '"';
    }
}
