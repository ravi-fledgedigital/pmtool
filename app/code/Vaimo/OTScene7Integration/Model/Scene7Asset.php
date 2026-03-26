<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Model;

use Magento\Framework\DataObject;
use Vaimo\OTScene7Integration\Api\Data\Scene7AssetInterface;

class Scene7Asset extends DataObject implements Scene7AssetInterface
{
    public function getUrl(): string
    {
        return $this->getData(self::URL_DATA_KEY);
    }

    public function setUrl(string $url): void
    {
        $this->setData(self::URL_DATA_KEY, $url);
    }

    public function getWidth(): ?int
    {
        return $this->getData(self::WIDTH_DATA_KEY);
    }

    public function setWidth(?int $width): void
    {
        $this->setData(self::WIDTH_DATA_KEY, $width);
    }

    public function getHeight(): ?int
    {
        return $this->getData(self::HEIGHT_DATA_KEY);
    }

    public function setHeight(?int $height): void
    {
        $this->setData(self::HEIGHT_DATA_KEY, $height);
    }

    public function isPlaceHolder(): bool
    {
        return (bool) $this->getData(self::IS_PLACEHOLDER_DATA_KEY);
    }

    public function setIsPlaceHolder(bool $isPlaceholder): void
    {
        $this->setData(self::IS_PLACEHOLDER_DATA_KEY, $isPlaceholder);
    }
}
