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
class Datetime extends \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\Date
{
    /**
     * Return Data Form Input/Output Filter
     *
     * @return \Magento\Framework\Data\Form\Filter\FilterInterface|false
     */
    protected function _getFormFilter()
    {
        return new \OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form\Filter\DateWithTime(
            $this->_dateFilterFormat(),
            $this->_localeResolver
        );
    }

    /**
     * Get/Set/Reset date filter format
     *
     * @param string|null|false $format
     *
     * @return $this|string
     */
    protected function _dateFilterFormat($format = null)
    {
        if ($format === null) {
            // get format
            return $this->configProvider->getDateFormatJs() . ' ' . $this->configProvider->getTimeFormatJs();
        } elseif ($format === false) {
            // reset value
            $this->_dateFilterFormat = null;

            return $this;
        }

        $this->_dateFilterFormat = $format;

        return $this;
    }
}
