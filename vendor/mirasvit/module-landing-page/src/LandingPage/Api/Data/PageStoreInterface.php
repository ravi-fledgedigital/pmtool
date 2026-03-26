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
 * @package   mirasvit/module-landing-page
 * @version   1.1.0
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\LandingPage\Api\Data;

interface PageStoreInterface
{
    const TABLE_NAME = 'mst_landing_page_store';

    const ID = 'entity_id';

    const PAGE_ID = 'page_id';

    const STORE_ID = 'store_id';

    public function getPageId(): int;

    public function setPageId(int $value): self;

    public function getName(): string;

    public function setName(string $value): self;

    public function getIsActive(): bool;

    public function setIsActive(bool $value): self;

    public function getUrlKey(): string;

    public function setUrlKey(string $value): self;

    public function getStoreId(): string;

    public function setStoreId(int $value): self;

    public function getPageTitle(): string;

    public function setPageTitle(string $value): self;

    public function getMetaTitle(): string;

    public function setMetaTitle(string $value): self;

    public function getMetaTags(): string;

    public function setMetaTags(string $value): self;

    public function getMetaDescription(): string;

    public function setMetaDescription(string $value): self;

    public function getDescription(): string;

    public function setDescription(string $value): self;

    public function getTopBlock(): int;

    public function setTopBlock(int $value): self;

    public function getBottomBlock(): int;

    public function setBottomBlock(int $value): self;

    public function getLayoutUpdate(): string;

    public function setLayoutUpdate(string $value): self;

    public function getSearchTerm(): string;

    public function setSearchTerm(string $value): self;
}
