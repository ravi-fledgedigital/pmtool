<?php
/**
 * Mirasvit
 *
 * This source file is subject to the Mirasvit Software License, which is available at https://mirasvit.com/license/.
 * Do not edit or add to this file if you wish to upgrade the to newer versions in the future.
 * If you wish to customize this module for your needs.
 * Please refer to http://www.magentocommerce.com for more information.
 *
 * @category  Mirasvit
 * @package   mirasvit/module-cataloglabel
 * @version   2.5.7
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\CatalogLabel\Model\System\Config\Source;


use Mirasvit\CatalogLabel\Api\Data\LabelInterface;

class LabelAppearenceSource implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray(): array
    {
        $array = [
            [
                'label' => 'Product List Label',
                'value' => LabelInterface::APPEARENCE_LIST,
            ],
            [
                'label' => 'Product View Label',
                'value' => LabelInterface::APPEARENCE_VIEW,
            ],
            [
                'label' => 'Same Label for List and View',
                'value' => LabelInterface::APPEARENCE_BOTH,
            ],
            [
                'label' => 'Sepparate Labels for List and View',
                'value' => LabelInterface::APPEARENCE_LIST . ',' . LabelInterface::APPEARENCE_VIEW,
            ],
        ];

        return $array;
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this->toOptionArray() as $item) {
            $result[$item['value']] = $item['label'];
        }

        return $result;
    }
}
