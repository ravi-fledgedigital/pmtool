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


namespace Mirasvit\LayeredNavigation\Model;


use Magento\Framework\Model\AbstractModel;
use Mirasvit\Core\Service\SerializeService;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;

class Group extends AbstractModel implements GroupInterface
{

    public function getId(): ?int
    {
        return $this->getData(self::ID) ? (int)$this->getData(self::ID) : null;
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $value): GroupInterface
    {
        return $this->setData(self::IS_ACTIVE, $value);
    }

    public function getTitle(): array
    {
        $titles = $this->getData(self::TITLE);

        return (array)SerializeService::decode($titles);
    }

    public function setTitle(array $value): GroupInterface
    {
        return $this->setData(self::TITLE, (string)SerializeService::encode($value));
    }

    public function getCode(): string
    {
        return (string)$this->getData(self::CODE);
    }

    public function setCode(string $value): GroupInterface
    {
        return $this->setData(self::CODE, $value);
    }

    public function getSwatchType(): int
    {
        return (int)$this->getData(self::SWATCH_TYPE);
    }

    public function setSwatchType(int $value): GroupInterface
    {
        return $this->setData(self::SWATCH_TYPE, $value);
    }

    public function getPosition(): int
    {
        return (int)$this->getData(self::POSITION);
    }

    public function setPosition(int $value): GroupInterface
    {
        return $this->setData(self::POSITION, $value);
    }

    public function getAttributeCode(): string
    {
        return (string)$this->getData(self::ATTRIBUTE_CODE);
    }

    public function setAttributeCode(string $value): GroupInterface
    {
        return $this->setData(self::ATTRIBUTE_CODE, $value);
    }

    public function getAttributeValueIds(): array
    {
        return explode(',', $this->getData(self::ATTRIBUTE_VALUE_IDS));
    }

    public function setattributeValueIds(array $value): GroupInterface
    {
        return $this->setData(self::ATTRIBUTE_VALUE_IDS, implode(',', $value));
    }

    public function getSwatchValue(): ?string
    {
        return $this->getData(self::SWATCH_VALUE)
            ? (string)$this->getData(self::SWATCH_VALUE)
            : null;
    }

    public function setSwatchValue(?string $value = null): GroupInterface
    {
        return $this->setData(self::SWATCH_VALUE, $value);
    }

    public function getLabelByStoreId(int $storeId): string
    {
        $titles = $this->getTitle();

        foreach ($titles as $title) {
            if ((int)$title['store_id'] === $storeId) {
                return $title['label'];
            }
        }

        return $this->getLabelByStoreId(0);
    }
}
