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


use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Model\ResourceModel\Placeholder\CollectionFactory;
use Magento\Framework\Option\ArrayInterface;


class PlaceholderSource implements ArrayInterface
{
    private $collectionFactory;

    private $positionSource;

    public function __construct(
        CollectionFactory $collectionFactory,
        PositionSource $positionSource
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->positionSource    = $positionSource;
    }

    public function toOptionArray(): array
    {
        // product image placeholders
        $collection = $this->collectionFactory->create();

        /** @var PlaceholderInterface $item */
        foreach ($this->positionSource->getPositionsArray() as $code => $label) {
            $disabled = true;
            $value    = $code;
            $label    = (string)__('not configured');

            foreach ($collection as $item) {
                if ($item->getPosition() == $code) {
                    $disabled = false;
                    $label    = $item->getName();
                    $value    = $item->getId();
                    break;
                }
            }

            $result[] = [
                'value'    => $value,
                'label'    => $label,
                'disabled' => $disabled
            ];
        }

        // manual placeholders

        $manual = $this->collectionFactory->create()
            ->addFieldToFilter(PlaceholderInterface::POSITION, 'MANUAL');

        foreach ($manual as $item) {
            $result[] = [
                'value'    => $item->getId(),
                'label'    => $item->getName() . ' (manual)',
                'disabled' => false
            ];
        }

        return $result;
    }

    public function toArray(): array
    {
        $values = [];

        foreach ($this->toOptionArray() as $item) {
            $values[$item['value']] = $item['label'];
        }

        return $values;
    }
}
