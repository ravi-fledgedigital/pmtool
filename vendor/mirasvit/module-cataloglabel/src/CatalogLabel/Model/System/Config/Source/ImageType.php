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

use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;

class ImageType implements \Magento\Framework\Option\ArrayInterface
{
    public function toOptionArray(): array
    {
        $array = [
            [
                'label' => 'Product View Image',
                'value' => DisplayInterface::TYPE_VIEW,
            ],
            [
                'label' => 'Product List Image',
                'value' => DisplayInterface::TYPE_LIST,
            ],
        ];

        return $array;
    }

    public function getLabel(string $value): ?string
    {
        foreach ($this->toOptionArray() as $item) {
            if ($item['value'] == $value) {
                return $item['label'];
            }
        }

        return null;
    }

    public function toArray(): array
    {
        return [DisplayInterface::TYPE_LIST, DisplayInterface::TYPE_VIEW];
    }
}
