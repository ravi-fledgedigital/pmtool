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


namespace Mirasvit\LayeredNavigation\Api\Data;


interface GroupInterface
{
    const TABLE_NAME = 'mst_navigation_grouped_option';

    const ID                  = 'group_id';
    const IS_ACTIVE           = 'is_active';
    const TITLE               = 'title';
    const CODE                = 'code';
    const SWATCH_TYPE         = 'swatch_type';
    const SWATCH_VALUE        = 'swatch_value';
    const POSITION            = 'position';
    const ATTRIBUTE_CODE      = 'attribute_code';
    const ATTRIBUTE_VALUE_IDS = 'attribute_value_ids';

    const SWATCH_TYPE_NONE  = 0;
    const SWATCH_TYPE_COLOR = 1;
    const SWATCH_TYPE_IMAGE = 2;

    public function getId(): ?int;

    public function getIsActive(): bool;

    public function setIsActive(bool $value): self;

    public function getTitle(): array;

    public function setTitle(array $value): self;

    public function getCode(): string;

    public function setCode(string $value): self;

    public function getSwatchType(): int;

    public function setSwatchType(int $value): self;

    public function getSwatchValue(): ?string;

    public function setSwatchValue(?string $value = null): self;

    public function getPosition(): int;

    public function setPosition(int $value): self;

    public function getAttributeCode(): string;

    public function setAttributeCode(string $value): self;

    public function getAttributeValueIds(): array;

    public function setattributeValueIds(array $value): self;
}
