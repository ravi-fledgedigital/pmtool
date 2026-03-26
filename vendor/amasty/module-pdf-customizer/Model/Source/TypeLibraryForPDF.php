<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TypeLibraryForPDF implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => "dompdf",
                'label' => __('Dompdf')
            ]
        ];
    }
}
