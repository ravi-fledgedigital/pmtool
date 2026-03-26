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


use Magento\Framework\App\Request\Http;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Repository\PlaceholderRepository;

class PositionSource implements \Magento\Framework\Option\ArrayInterface
{
    private $placeholderRepository;

    private $request;

    public function __construct(
        PlaceholderRepository $placeholderRepository,
        Http $request
    ) {
        $this->placeholderRepository = $placeholderRepository;
        $this->request               = $request;
    }

    private $positions = [
        'TL' => 'Top Left',
        'TC' => 'Top Center',
        'TR' => 'Top Right',
        'ML' => 'Middle Left',
        'MC' => 'Middle Center',
        'MR' => 'Middle Right',
        'BL' => 'Bottom Left',
        'BC' => 'Bottom Center',
        'BR' => 'Bottom Right'
    ];

    public function toOptionArray(): array
    {
        $optionsArray = [];

        foreach ($this->positions as $value => $label) {
            $existPlaceholder = $this->placeholderRepository->getCollection()
                ->addFieldToFilter(PlaceholderInterface::POSITION, $value)
                ->getFirstItem();

            $isDisabled = $existPlaceholder && $existPlaceholder->getId()
                && (!$this->request->getParam('id') || $existPlaceholder->getId() != $this->request->getParam('id'));

            $optionsArray[] = [
                'label'    => (string)__($label),
                'value'    => $value,
                'disabled' => $isDisabled,
            ];
        }

        $optionsArray[] = [
            'label'    => (string)__('Manual'),
            'value'    => 'MANUAL',
            'disabled' => false
        ];

        return $optionsArray;
    }

    public function getPositionsArray(): array
    {
        return $this->positions;
    }
}
