<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Attribute\InputType\FrontendCaster;

use OnitsukaTiger\OrderAttribute\Api\Data\CheckoutAttributeInterface;
use OnitsukaTiger\OrderAttribute\Model\Config\Source\DateFormat;
use OnitsukaTiger\OrderAttribute\Model\ConfigProvider;

class Datetime implements SpecificationProcessorInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(
        ConfigProvider $configProvider
    ) {
        $this->configProvider = $configProvider;
    }

    /**
     * @param string[] $element
     * @param CheckoutAttributeInterface $attribute
     */
    public function processSpecificationByAttribute(array &$element, CheckoutAttributeInterface $attribute): void
    {
        $validationRules = $attribute->getValidationRules();
        $format = DateFormat::$formats[$this->configProvider->getDateFormat()]['format'];

        if (!isset($element['additionalClasses'])) {
            $element['additionalClasses'] = '';
        }
        $element['additionalClasses'] .= ' date';
        $element['dataType'] = $element['formElement'] = 'date';
        $element['options'] = [
            'dateFormat' => $this->configProvider->getDateFormatJs(),
            'showsTime'  => true,
            'timeFormat' =>  $this->configProvider->getTimeFormatJs(),
            'showOn' => 'both'
        ];

        if (!empty($element['value'])) {
            $element['value'] = date(
                $format . ' ' . $this->configProvider->getTimeFormat(),
                strtotime($element['value'])
            );
        }

        if (!empty($validationRules['date_range_min'])) {
            $element['options']['minDate'] = date($format, $validationRules['date_range_min']);
        }

        if (!empty($validationRules['date_range_max'])) {
            $element['options']['maxDate'] = date($format, $validationRules['date_range_max']);
        }
    }
}
