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


namespace Mirasvit\CatalogLabel\Api\Data;


interface PlaceholderInterface
{
    const CACHE_TAG = 'cataloglabel_placeholder';

    const TABLE_NAME       = 'mst_productlabel_placeholder';
    const ID               = 'placeholder_id';
    const NAME             = 'name';
    const CODE             = 'code';
    const IS_ACTIVE        = 'is_active';
    const POSITION         = 'position';
    const LABELS_LIMIT     = 'labels_limit';
    const LABELS_DIRECTION = 'labels_direction';
    const CREATED_AT       = 'created_at';
    const UPDATED_AT       = 'updated_at';

    public function getName(): string;

    public function setName(string $value): self;

    public function getCode(): string;

    public function setCode(string $value): self;

    public function getIsActive(): bool;

    public function setIsActive(bool $value): self;

    public function getPosition(): string;

    public function setPosition(string $value): self;

    public function getLabelsLimit(): int;

    public function setLabelsLimit(int $value): self;

    public function getLabelsDirection(): string;

    public function setLabelsDirection(string $value): self;
}
