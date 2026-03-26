<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form;

/**
 * EAV Entity Attribute Date with time Data Model
 */
class Date extends \Magento\Eav\Model\Attribute\Data\Date
{
    /**
     * @var \OnitsukaTiger\OrderAttribute\Model\ConfigProvider
     */
    protected $configProvider;

    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \OnitsukaTiger\OrderAttribute\Model\ConfigProvider $configProvider
    ) {
        parent::__construct($localeDate, $logger, $localeResolver);
        $this->configProvider = $configProvider;
    }

    /**
     * Return Data Form Input/Output Filter
     *
     * @return \Magento\Framework\Data\Form\Filter\FilterInterface|false
     */
    protected function _getFormFilter()
    {
        return new \Magento\Framework\Data\Form\Filter\Date($this->_dateFilterFormat(), $this->_localeResolver);
    }

    /**
     * Get/Set/Reset date filter format
     *
     * @param string|null|false $format
     * @return $this|string
     */
    protected function _dateFilterFormat($format = null)
    {
        if ($format === null) {
            // get format
            return $this->configProvider->getDateFormatJs();
        } elseif ($format === false) {
            // reset value
            $this->_dateFilterFormat = null;
            return $this;
        }

        $this->_dateFilterFormat = $format;
        return $this;
    }

    /**
     * Export attribute value to entity model
     *
     * @param array|string $value
     * @return $this
     */
    public function compactValue($value)
    {
        if ($value !== false) {
            if (empty($value)) {
                $value = null;
            }
            /*
             * avoid snake_case to CamelCale and vice versa convertation
             * because underscore in attribute_code can be lost
             */
            $this->getEntity()->setData($this->getAttribute()->getAttributeCode(), $value);
        }
        return $this;
    }
}
