<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Gdx\CustomLogging\Model\Source\Logger;

/**
 * Class Formatter
 *
 * @package Gdx\CustomLogging\Model\Source\Logger
 */
class Formatter implements \Magento\Framework\Option\ArrayInterface
{
    const LINE_FORMATTER = 1;
    const JSON_FORMATTER = 2;

    /**
     * List formatter for logger
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            array('value' => self::LINE_FORMATTER, 'label' => __('Line Formatter')),
            array('value' => self::JSON_FORMATTER, 'label' => __('Json Formatter')),
        ];
    }
}
