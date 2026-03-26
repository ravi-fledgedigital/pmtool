<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Config\Source;

class DateFormat implements \Magento\Framework\Option\ArrayInterface
{
    public static $formats = [
        'yyyy-MM-dd' => [
            'label' => 'yyyy-mm-dd',
            'format' => 'Y-m-d'
        ],
        'MM/dd/yyyy' => [
            'label' => 'mm/dd/yyyy',
            'format' => 'm/d/Y'
        ],
        'dd/MM/yyyy' => [
            'label' => 'dd/mm/yyyy',
            'format' => 'd/m/Y'
        ],
        'd/M/yy' => [
            'label' => 'd/m/yy',
            'format' => 'j/n/y'
        ],
        'd/M/yyyy' => [
            'label' => 'd/m/yyyy',
            'format' => 'j/n/Y'
        ],
        'dd.MM.yyyy' => [
            'label' => 'dd.mm.yyyy',
            'format' => 'd.m.Y'
        ],
        'dd.MM.yy' => [
            'label' => 'dd.mm.yy',
            'format' => 'd.m.y'
        ],
        'd.M.yy' => [
            'label' => 'd.m.yy',
            'format' => 'j.n.y'
        ],
        'd.M.yyyy' => [
            'label' => 'd.m.yyyy',
            'format' => 'j.n.Y'
        ],
        'dd-MM-yy' => [
            'label' => 'dd-mm-yy',
            'format' => 'd-m-y'
        ],
        'yyyy.MM.dd' => [
            'label' => 'yyyy.mm.dd',
            'format' => 'Y.m.d'
        ],
        'dd-MM-yyyy' => [
            'label' => 'dd-mm-yyyy',
            'format' => 'd-m-Y'
        ],
        'yyyy/MM/dd' => [
            'label' => 'yyyy/mm/dd',
            'format' => 'Y/m/d'
        ],
        'yy/MM/dd' => [
            'label' => 'yy/mm/dd',
            'format' => 'y/m/d'
        ],
        'dd/MM/yy' => [
            'label' => 'dd/mm/yy',
            'format' => 'd/m/y'
        ],
        'MM/dd/yy' => [
            'label' => 'mm/dd/yy',
            'format' => 'm/d/y'
        ],
        'dd/MM yyyy' => [
            'label' => 'dd/mm yyyy',
            'format' => 'd/m Y'
        ],
        'yyyy MM dd' => [
            'label' => 'yyyy mm dd',
            'format' => 'Y m d'
        ],
    ];

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $result = [];

        foreach (self::$formats as $value => $options) {
            $result[] = [
                'value' => $value,
                'label' => $options['label'].' (' . date($options['format']) . ')'
            ];
        }

        return $result;
    }
}
