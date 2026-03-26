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

namespace Mirasvit\LandingPage\Model\Page;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractModel;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Api\Data\PageStoreInterface;
use Mirasvit\LandingPage\Model\ResourceModel\Page\Store as Resource;

class Store extends AbstractModel implements IdentityInterface, PageStoreInterface
{

    const CACHE_TAG = 'mst_landing_page_store';

    protected $_cacheTag    = 'mst_landing_page_store';

    protected $_eventPrefix = 'mst_landing_page_store';

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getPageId(): int
    {
        return (int)$this->getData(self::PAGE_ID);
    }

    public function setPageId(int $value): self
    {
        return $this->setData(self::PAGE_ID, $value);
    }

    public function getName(): string
    {
        return (string)$this->getData(PageInterface::NAME);
    }

    public function setName(string $value): self
    {
        return $this->setData(PageInterface::NAME, $value);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(PageInterface::IS_ACTIVE);
    }

    public function setIsActive(bool $value): self
    {
        return $this->setData(PageInterface::IS_ACTIVE, $value);
    }

    public function getRedirect(): bool
    {
        return (bool)$this->getData(PageInterface::REDIRECT);
    }

    public function setRedirect(bool $value): self
    {
        return $this->setData(PageInterface::REDIRECT, $value);
    }

    public function getUrlKey(): string
    {
        return (string)$this->getData(PageInterface::URL_KEY);
    }

    public function setUrlKey(string $value): self
    {
        return $this->setData(PageInterface::URL_KEY, $value);
    }

    public function getPageTitle(): string
    {
        return (string)$this->getData(PageInterface::PAGE_TITLE);
    }

    public function setPageTitle(string $value): self
    {
        return $this->setData(PageInterface::PAGE_TITLE, $value);
    }

    public function getMetaTitle(): string
    {
        return (string)$this->getData(PageInterface::META_TITLE);
    }

    public function setMetaTitle(string $value): self
    {
        return $this->setData(PageInterface::META_TITLE, $value);
    }

    public function getMetaTags(): string
    {
        return (string)$this->getData(PageInterface::META_TAGS);
    }

    public function setMetaTags(string $value): self
    {
        return $this->setData(PageInterface::META_TAGS, $value);
    }

    public function getMetaDescription(): string
    {
        return (string)$this->getData(PageInterface::META_DESCRIPTION);
    }

    public function setMetaDescription(string $value): self
    {
        return $this->setData(PageInterface::META_DESCRIPTION, $value);
    }

    public function getDescription(): string
    {
        return (string)$this->getData(PageInterface::DESCRIPTION);
    }

    public function setDescription(string $value): self
    {
        return $this->setData(PageInterface::DESCRIPTION, $value);
    }

    public function getTopBlock(): int
    {
        return (int)$this->getData(PageInterface::TOP_BLOCK);
    }

    public function setTopBlock(int $value): self
    {
        return $this->setData(PageInterface::TOP_BLOCK, $value);
    }

    public function getBottomBlock(): int
    {
        return (int)$this->getData(PageInterface::BOTTOM_BLOCK);
    }

    public function setBottomBlock(int $value): self
    {
        return $this->setData(PageInterface::BOTTOM_BLOCK, $value);
    }

    public function getLayoutUpdate(): string
    {
        return (string)$this->getData(PageInterface::LAYOUT_UPDATE);
    }

    public function setLayoutUpdate(string $value): self
    {
        return $this->setData(PageInterface::LAYOUT_UPDATE, $value);
    }

    public function getCategories(): string
    {
        return (string)$this->getData(PageInterface::CATEGORIES);
    }

    public function setCategories(string $value): self
    {
        return $this->setData(PageInterface::CATEGORIES, $value);
    }

    public function getSearchTerm(): string
    {
        return (string)$this->getData(PageInterface::SEARCH_TERM);
    }

    public function setSearchTerm(string $value): self
    {
        return $this->setData(PageInterface::SEARCH_TERM, $value);
    }

    protected function _construct()
    {
        $this->_init(Resource::class);
    }

    public function getStoreId(): string
    {
        return (string)$this->getData(PageStoreInterface::STORE_ID);
    }

    public function setStoreId(int $value): self
    {
        return $this->setData(PageStoreInterface::STORE_ID, $value);
    }


}
