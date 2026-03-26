<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ResamplingModes implements OptionSourceInterface
{
    /**
     * @return string[][]
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => null,
                'label' => \__('-- Use Scene7 Defaults --'),
            ],
            [
                'value' => 'bilin',
                'label' => \__('bi-linear interpolation'),
            ],
            [
                'value' => 'bicub',
                'label' => \__('bi-cubic interpolation'),
            ],
            [
                'value' => 'sharp2',
                'label' => \__('Sharp2 interpolation'),
            ],
            [
                'value' => 'bisharp',
                'label' => \__('Bicubic sharper interpolation'),
            ],
        ];
    }
}
