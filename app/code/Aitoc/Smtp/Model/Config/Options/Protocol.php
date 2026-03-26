<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_Smtp
 */


namespace Aitoc\Smtp\Model\Config\Options;

use Magento\Framework\Option\ArrayInterface;

class Protocol implements ArrayInterface
{
    const PROTOCOL_NONE = 0;
    const PROTOCOL_SSL = 1;
    const PROTOCOL_TLS = 2;

    const PROTOCOL_SSL_VALUE = 'ssl';
    const PROTOCOL_TLS_VALUE = 'tls';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::PROTOCOL_NONE,
                'label' => __('None')
            ],
            [
                'value' => self::PROTOCOL_SSL,
                'label' => __('SSL')
            ],
            [
                'value' => self::PROTOCOL_TLS,
                'label' => __('TLS')
            ]
        ];
    }

    /**
     * @param $id
     * @return string
     */
    public function getOptionById($id)
    {
        $value = '';
        switch ($id) {
            case 1:
                $value = self::PROTOCOL_SSL_VALUE;
                break;
            case 2:
                $value = self::PROTOCOL_TLS_VALUE;
                break;
        }

        return $value;
    }
}
