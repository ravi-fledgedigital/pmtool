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

namespace Mirasvit\LandingPage\Model;

use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Mirasvit\LandingPage\Api\Data\PageInterface;

class Page extends AbstractModel implements IdentityInterface, PageInterface
{

    const CACHE_TAG = 'mst_landing_page';

    protected $_cacheTag    = 'mst_landing_page';

    protected $_eventPrefix = 'mst_landing_page';

    private $imageUploader;

    private $filesystem;

    public function __construct(
        ImageUploader     $imageUploader,
        Filesystem        $filesystem,
        Context           $context,
        Registry          $registry,
        ?AbstractResource $resource = null,
        ?AbstractDb       $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->imageUploader = $imageUploader;
        $this->filesystem    = $filesystem;
    }

    public function afterSave(): self
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $path     = $this->imageUploader->getBasePath();

        if ($this->getImage() && !$mediaDir->isExist($this->imageUploader->getFilePath($path, $this->getImage()))) {
            $this->imageUploader->moveFileFromTmp($this->getImage(), true);
        }

        return parent::afterSave();
    }

    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    public function getPageId(): int
    {
        return (int)$this->getData(PageInterface::PAGE_ID);
    }

    public function getName(): string
    {
        return (string)$this->getData(PageInterface::NAME);
    }

    public function setName(string $value): PageInterface
    {
        return $this->setData(PageInterface::NAME, $value);
    }

    public function getStoreIds(): string
    {
        return (string)$this->getData(PageInterface::STORE_IDS);
    }

    public function setStoreIds(string $value): PageInterface
    {
        return $this->setData(PageInterface::STORE_IDS, $value);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(PageInterface::IS_ACTIVE);
    }

    public function setIsActive(bool $value): PageInterface
    {
        return $this->setData(PageInterface::IS_ACTIVE, $value);
    }

    public function getRedirect(): bool
    {
        return (bool)$this->getData(PageInterface::REDIRECT);
    }

    public function setRedirect(bool $value): PageInterface
    {
        return $this->setData(PageInterface::REDIRECT, $value);
    }

    public function getUrlKey(): string
    {
        return (string)$this->getData(PageInterface::URL_KEY);
    }

    public function setUrlKey(string $value): PageInterface
    {
        return $this->setData(PageInterface::URL_KEY, $value);
    }

    public function getPageTitle(): string
    {
        return (string)$this->getData(PageInterface::PAGE_TITLE);
    }

    public function setPageTitle(string $value): PageInterface
    {
        return $this->setData(PageInterface::PAGE_TITLE, $value);
    }

    public function getMetaTitle(): string
    {
        return (string)$this->getData(PageInterface::META_TITLE);
    }

    public function setMetaTitle(string $value): PageInterface
    {
        return $this->setData(PageInterface::META_TITLE, $value);
    }

    public function getMetaTags(): string
    {
        return (string)$this->getData(PageInterface::META_TAGS);
    }

    public function setMetaTags(string $value): PageInterface
    {
        return $this->setData(PageInterface::META_TAGS, $value);
    }

    public function getMetaDescription(): string
    {
        return (string)$this->getData(PageInterface::META_DESCRIPTION);
    }

    public function setMetaDescription(string $value): PageInterface
    {
        return $this->setData(PageInterface::META_DESCRIPTION, $value);
    }

    public function getDescription(): string
    {
        return (string)$this->getData(PageInterface::DESCRIPTION);
    }

    public function setDescription(string $value): PageInterface
    {
        return $this->setData(PageInterface::DESCRIPTION, $value);
    }

    public function getTopBlock(): int
    {
        return (int)$this->getData(PageInterface::TOP_BLOCK);
    }

    public function setTopBlock(int $value): PageInterface
    {
        return $this->setData(PageInterface::TOP_BLOCK, $value);
    }

    public function getBottomBlock(): int
    {
        return (int)$this->getData(PageInterface::BOTTOM_BLOCK);
    }

    public function setBottomBlock(int $value): PageInterface
    {
        return $this->setData(PageInterface::BOTTOM_BLOCK, $value);
    }

    public function getLayoutUpdate(): string
    {
        return (string)$this->getData(PageInterface::LAYOUT_UPDATE);
    }

    public function setLayoutUpdate(string $value): PageInterface
    {
        return $this->setData(PageInterface::LAYOUT_UPDATE, $value);
    }

    public function getCategories(): string
    {
        return (string)$this->getData(PageInterface::CATEGORIES);
    }

    public function setCategories(string $value): PageInterface
    {
        return $this->setData(PageInterface::CATEGORIES, $value);
    }

    public function getSearchTerm(): string
    {
        return (string)$this->getData(PageInterface::SEARCH_TERM);
    }

    public function setSearchTerm(string $value): PageInterface
    {
        return $this->setData(PageInterface::SEARCH_TERM, $value);
    }

    protected function _construct()
    {
        $this->_init('Mirasvit\LandingPage\Model\ResourceModel\Page');
    }

    public function getStoreId(): string
    {
        return (string)$this->getData(PageInterface::STORE_ID);
    }

    public function setStoreId(int $value): PageInterface
    {
        return $this->setData(PageInterface::STORE_ID, $value);
    }

    public function getUseDefault(): array
    {
        $useDefault = $this->getData(self::DEFAULT);

        return is_array($useDefault) ? $useDefault : [];
    }

    public function setUseDefault(array $value): PageInterface
    {
        return $this->setData(self::DEFAULT, $value);
    }

    public function getImage(): ?string
    {
        return (string)$this->getData(self::IMAGE);
    }

    public function setImage(?string $value): PageInterface
    {
        return $this->setData(self::IMAGE, $value);
    }
}
