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

namespace Mirasvit\Brand\Model;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Filesystem;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Mirasvit\Brand\Api\Data\BrandPageInterface;
use Mirasvit\Brand\Api\Data\BrandPageStoreInterface;
use Mirasvit\Brand\Model\Config\BrandPageConfig;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class BrandPage extends AbstractModel implements BrandPageInterface
{
    private $imageUploader;

    private $filesystem;

    private $storeManager;

    private bool $preventStoreReload = false;

    public function __construct(
        StoreManagerInterface $storeManager,
        Filesystem $filesystem,
        Context $context,
        Registry $registry,
        ImageUploader $imageUploader,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $resource,
            $resourceCollection,
            $data
        );

        $this->storeManager  = $storeManager;
        $this->imageUploader = $imageUploader;
        $this->filesystem    = $filesystem;
    }

    public function getId(): ?int
    {
        return $this->getData(self::ID) ? (int)$this->getData(self::ID) : null;
    }

    public function getAttributeOptionId(): int
    {
        return (int)$this->getData(self::ATTRIBUTE_OPTION_ID);
    }

    public function setAttributeOptionId(int $value): BrandPageInterface
    {
        return $this->setData(self::ATTRIBUTE_OPTION_ID, $value);
    }

    public function getAttributeId(): int
    {
        return (int)$this->getData(self::ATTRIBUTE_ID);
    }

    public function setAttributeId(int $value): BrandPageInterface
    {
        return $this->setData(self::ATTRIBUTE_ID, $value);
    }

    public function getIsActive(): bool
    {
        return (bool)$this->getData(self::IS_ACTIVE);
    }

    public function setIsActive(bool $value): BrandPageInterface
    {
        return $this->setData(self::IS_ACTIVE, $value);
    }

    public function getLogo(): string
    {
        return (string)$this->getData(self::LOGO);
    }

    public function setLogo(string $value): BrandPageInterface
    {
        return $this->setData(self::LOGO, $value);
    }

    public function getBrandTitle(): string
    {
        return (string)$this->getStoreValue(self::BRAND_TITLE);
    }

    public function setBrandTitle(string $value): BrandPageInterface
    {
        return $this->setData(self::BRAND_TITLE, $value);
    }

    public function getUrlKey(): string
    {
        return (string) ($this->getStoreValue(self::URL_KEY) ?: $this->getData(self::URL_KEY));
    }

    public function setUrlKey(string $value): BrandPageInterface
    {
        return $this->setData(self::URL_KEY, $value);
    }

    public function getBrandDescription(): string
    {
        return (string)$this->getStoreValue(self::BRAND_DESCRIPTION);
    }

    public function setBrandDescription(string $value): BrandPageInterface
    {
        return $this->setData(self::BRAND_DESCRIPTION, $value);
    }

    public function getMetaTitle(): string
    {
        return (string)$this->getStoreValue(self::META_TITLE);
    }

    public function setMetaTitle(string $value): BrandPageInterface
    {
        return $this->setData(self::META_TITLE, $value);
    }

    public function getKeyword(): string
    {
        return (string)$this->getStoreValue(self::KEYWORD);
    }

    public function setKeyword(string $value): BrandPageInterface
    {
        return $this->setData(self::KEYWORD, $value);
    }

    public function getMetaDescription(): string
    {
        return (string)$this->getStoreValue(self::META_DESCRIPTION);
    }

    public function setMetaDescription(string $value): BrandPageInterface
    {
        return $this->setData(self::META_DESCRIPTION, $value);
    }

    public function getSeoDescription(): string
    {
        return (string)$this->getStoreValue(self::SEO_DESCRIPTION);
    }

    public function setSeoDescription(string $value): BrandPageInterface
    {
        return $this->setData(self::SEO_DESCRIPTION, $value);
    }

    public function getSeoPosition(): string
    {
        $seoPosition = (string)$this->getStoreValue(self::SEO_POSITION);

        return $seoPosition ? (string)$seoPosition : BrandPageConfig::FROM_DEFAULT_POSITION;
    }

    public function setSeoPosition(string $value): BrandPageInterface
    {
        return $this->setData(self::SEO_POSITION, $value);
    }

    public function getRobots(): string
    {
        $robots = (string)$this->getStoreValue(self::ROBOTS);

        return $robots ? (string)$robots : BrandPageConfig::INDEX_FOLLOW;
    }

    public function setRobots(string $value): BrandPageInterface
    {
        return $this->setData(self::ROBOTS, $value);
    }

    public function getCanonical(): string
    {
        return (string)$this->getStoreValue(self::CANONICAL);
    }

    public function setCanonical(string $value): BrandPageInterface
    {
        return $this->setData(self::CANONICAL, $value);
    }

    public function getAttributeCode(): string
    {
        return (string)$this->getData(self::ATTRIBUTE_CODE);
    }

    public function getBrandName(): string
    {
        return (string)$this->getData(self::BRAND_NAME);
    }

    public function setBrandName(string $value): BrandPageInterface
    {
        return $this->setData(self::BRAND_NAME, $value);
    }

    public function getBannerAlt(): string
    {
        return (string)$this->getStoreValue(self::BANNER_ALT);
    }

    public function setBannerAlt(string $value): BrandPageInterface
    {
        return $this->setData(self::BANNER_ALT, $value);
    }

    public function getBannerTitle(): string
    {
        return (string)$this->getStoreValue(self::BANNER_TITLE);
    }

    public function setBannerTitle(string $value): BrandPageInterface
    {
        return $this->setData(self::BANNER_TITLE, $value);
    }

    public function getBanner(): string
    {
        return (string)$this->getStoreValue(self::BANNER);
    }

    public function setBanner(string $value): BrandPageInterface
    {
        return $this->setData(self::BANNER, $value);
    }

    public function getBannerPosition(): string
    {
        return (string)$this->getStoreValue(self::BANNER_POSITION);
    }

    public function setBannerPosition(string $value): BrandPageInterface
    {
        return $this->setData(self::BANNER_POSITION, $value);
    }

    public function getBrandShortDescription(): string
    {
        return (string)$this->getStoreValue(self::BRAND_SHORT_DESCRIPTION);
    }

    public function setBrandShortDescription(string $value): BrandPageInterface
    {
        return $this->setData(self::BRAND_SHORT_DESCRIPTION, $value);
    }

    public function afterSave(): self
    {
        $logo = $this->getLogo();
        $this->moveFileFromTmp($logo);
        $this->moveBannerFromTmp();

        return parent::afterSave();
    }

    public function moveBannerFromTmp(): void
    {
        $banner = (string)$this->getData(self::BANNER);
        if ($banner) {
            $this->moveFileFromTmp($banner);
        }
    }

    public function getBrandDisplayMode(): string
    {
        $displayMode = $this->getStoreValue(BrandPageStoreInterface::BRAND_DISPLAY_MODE);

        return $displayMode ? (string)$displayMode : Category::DM_PRODUCT;
    }

    public function getBrandCmsBlock(): ?string
    {
        if ($this->getBrandDisplayMode() == Category::DM_PRODUCT) {
            return null;
        }

        return $this->getStoreValue(BrandPageStoreInterface::BRAND_CMS_BLOCK)
            ? (string)$this->getStoreValue(BrandPageStoreInterface::BRAND_CMS_BLOCK)
            : null;
    }

    protected function _construct(): void
    {
        $this->_init(ResourceModel\BrandPage::class);
    }

    private function moveFileFromTmp(string $image): void
    {
        $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        if (
            $image && !$mediaDir->isExist($this->imageUploader->getFilePath($this->imageUploader->getBasePath(), $image))
        ) {
            $this->imageUploader->moveFileFromTmp($image, true);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function getStoreValue(?string $key = null, ?int $storeId = null): string
    {
        $fieldData = '';
        $store     = is_null($storeId) ? $this->storeManager->getStore()->getId() : $storeId;

        if (!$this->getData(BrandPageStoreInterface::STORES) && $this->getId() && !$this->getPreventStoreReload()) {
            $this->load($this->getId());
        }

        $fieldDataArray = $this->getData(BrandPageStoreInterface::STORES);

        if (!$fieldDataArray || !$key) {
            return $fieldData;
        }

        if (
            $store
            && isset($fieldDataArray[$store])
            && isset($fieldDataArray[$store][$key])
            && trim($fieldDataArray[$store][$key])
            && (trim($fieldDataArray[$store][$key]) != BrandPageConfig::FROM_DEFAULT_POSITION)
        ) {
            $fieldData = trim($fieldDataArray[$store][$key]);
        } elseif (isset($fieldDataArray[0]) && isset($fieldDataArray[0][$key])) {
            $fieldData = trim($fieldDataArray[0][$key]);
        }

        if ($fieldData == BrandPageConfig::DISABLED_POSITION) {
            $fieldData = '';
        }

        return $fieldData;
    }

    public function getUseDefault(): array
    {
        $useDefault = $this->getData(self::DEFAULT);

        return is_array($useDefault) ? $useDefault : [];
    }

    public function setUseDefault(array $value): self
    {
        return $this->setData(self::DEFAULT, $value);
    }

    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    public function setStoreId(int $value): self
    {
        return $this->setData(self::STORE_ID, $value);
    }

    public function getStoreFields(): array
    {
        return array_unique(array_merge(self::STORE_FIELDS, BrandPageStoreInterface::STORE_FIELDS));
    }

    public function setPreventStoreReload(bool $flag = true): self
    {
        $this->preventStoreReload = $flag;
        return $this;
    }

    public function getPreventStoreReload(): bool
    {
        return $this->preventStoreReload;
    }
}
