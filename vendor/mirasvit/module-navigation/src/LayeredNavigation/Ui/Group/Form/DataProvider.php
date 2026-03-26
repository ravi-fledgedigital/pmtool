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
 * @package   mirasvit/module-navigation
 * @version   2.9.34
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);


namespace Mirasvit\LayeredNavigation\Ui\Group\Form;


use Magento\Ui\DataProvider\AbstractDataProvider;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Model\ConfigProvider;
use Mirasvit\LayeredNavigation\Repository\GroupRepository;

class DataProvider extends AbstractDataProvider
{
    private $groupRepository;

    private $configProvider;

    public function __construct(
        GroupRepository $groupRepository,
        ConfigProvider $configProvider,
        string $name,
        string $primaryFieldName,
        string $requestFieldName,
        array $meta = [],
        array $data = []
    ) {
        $this->groupRepository = $groupRepository;
        $this->configProvider  = $configProvider;

        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    public function getData(): array
    {
        $result = [];

        /** @var GroupInterface $item */
        foreach ($this->groupRepository->getCollection() as $item) {
            $result[$item->getId()] = [
                GroupInterface::ID             => $item->getId(),
                GroupInterface::IS_ACTIVE      => (string)$item->getIsActive(),
                GroupInterface::POSITION       => $item->getPosition(),
                GroupInterface::CODE           => $item->getCode(),
                GroupInterface::SWATCH_TYPE    => (string)$item->getSwatchType(),
                GroupInterface::ATTRIBUTE_CODE => $item->getAttributeCode() ?: '0',
                'colors_filter' => $item->getSwatchType() == GroupInterface::SWATCH_TYPE_COLOR ? $item->getSwatchValue() : null,
            ];

            if ($item->getSwatchType() == GroupInterface::SWATCH_TYPE_IMAGE && $item->getSwatchValue()) {
                $image = $item->getSwatchValue();

                $imgPath = $this->configProvider->getMediaPath($image);
                $result[$item->getId()]['file'] = [
                    0 => [
                        'name' => $item->getImage(),
                        'url'  => $this->configProvider->getMediaUrl($image),
                        'size' => file_exists($imgPath) ? filesize($imgPath) : '',
                        'type' => 'image',
                    ],
                ];
            }
        }

        return $result;
    }

    public function addFilter(\Magento\Framework\Api\Filter $filter): void
    {
        return;
    }
}
