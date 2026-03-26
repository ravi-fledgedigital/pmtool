<?php
namespace OnitsukaTiger\Customer\Model\Metadata\Form;

use Magento\Framework\Api\ArrayObjectSearch;

class Date extends \Magento\Customer\Model\Metadata\Form\Date
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;


    /**
     * @var Data
     */
    private $helperStore;


    public function __construct(
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \OnitsukaTiger\Store\Helper\Data $helperStore,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Customer\Api\Data\AttributeMetadataInterface $attribute,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        $value,
        $entityTypeCode,
        $isAjax = false
    )
    {
        $this->storeManager = $storeManager;
        $this->helperStore = $helperStore;
        parent::__construct($localeDate, $logger, $attribute, $localeResolver, $value, $entityTypeCode, $isAjax);
    }


    /**
     * @inheritdoc
     */
    public function extractValue(\Magento\Framework\App\RequestInterface $request)
    {
        $value = $this->_getRequestValue($request);
        return $this->_applyInputFilter($value);
    }

    /**
     * Apply attribute input filter to value
     *
     * @param string $value
     * @return string|bool
     */
    protected function _applyInputFilter($value)
    {
        if ($value === false) {
            return false;
        }
        if ($this->getAttribute()->getAttributeCode() == 'dob') {
            if ($value) {
                $dateFormat = $this->helperStore->formatDateOfDob($this->storeManager->getStore()->getId());
                $valueDate = \DateTime::createFromFormat($dateFormat, $value);
                // Note: If the year is specified in a two-digit format, values between 0-69 are mapped to 2000-2069 and values between 70-100 are mapped to 1970-2000.
                if ($valueDate) {
                    if ($valueDate->format('Y') > date('Y')) {
                        $valueDate->modify('-100 year');
                    }
                    return $valueDate->format('m/d/Y');
                }
            }
        }
        $filter = $this->_getFormFilter();
        if ($filter) {
            $value = $filter->inputFilter($value);
        }

        return $value;
    }
}
