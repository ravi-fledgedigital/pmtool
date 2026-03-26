<?php

/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */

declare(strict_types=1);

namespace Vaimo\OTScene7Integration\Api\Data;

interface Scene7AssetInterface
{
    public const URL_DATA_KEY = 'url';
    public const WIDTH_DATA_KEY = 'width';
    public const HEIGHT_DATA_KEY = 'height';
    public const IS_PLACEHOLDER_DATA_KEY = 'is_placeholder';

    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @param string $url
     */
    public function setUrl(string $url): void;

    /**
     * @return int|null
     */
    public function getWidth(): ?int;

    /**
     * @param int|null $width
     */
    public function setWidth(?int $width): void;

    /**
     * @return int|null
     */
    public function getHeight(): ?int;

    /**
     * @param int|null $height
     */
    public function setHeight(?int $height): void;

    /**
     * @return bool
     */
    public function isPlaceHolder(): bool;

    /**
     * @param bool $isPlaceholder
     * @return void
     */
    public function setIsPlaceHolder(bool $isPlaceholder): void;
}
