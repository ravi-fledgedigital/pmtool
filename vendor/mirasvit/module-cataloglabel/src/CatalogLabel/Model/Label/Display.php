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


namespace Mirasvit\CatalogLabel\Model\Label;


use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Api\Data\TemplateInterface;
use Mirasvit\CatalogLabel\Model\ConfigProvider;
use Mirasvit\CatalogLabel\Repository\LabelRepository;
use Mirasvit\CatalogLabel\Repository\PlaceholderRepository;
use Mirasvit\CatalogLabel\Repository\TemplateRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Display extends AbstractModel implements DisplayInterface
{
    private $config;

    private $labelRepository;

    private $templateRepository;

    private $placeholderRepository;

    /** @var LabelInterface|null */
    private $label;

    /** @var PlaceholderInterface|null */
    private $placeholder;

    /** @var TemplateInterface|null */
    private $template;

    private $imageWidth;

    private $imageHeight;

    public function __construct(
        ConfigProvider $config,
        LabelRepository $labelRepository,
        TemplateRepository $templateRepository,
        PlaceholderRepository $placeholderRepository,
        Context $context,
        Registry $registry,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->config                = $config;
        $this->labelRepository       = $labelRepository;
        $this->templateRepository    = $templateRepository;
        $this->placeholderRepository = $placeholderRepository;

        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Mirasvit\CatalogLabel\Model\ResourceModel\Label\Display');
    }

    public function getType(): string
    {
        return $this->getData(self::TYPE);
    }

    public function setType(string $value): DisplayInterface
    {
        return $this->setData(self::TYPE, $value);
    }

    public function getLabelId(): int
    {
        return (int)$this->getData(self::LABEL_ID);
    }

    public function setLabelId(int $value): DisplayInterface
    {
        return $this->setData(self::LABEL_ID, $value);
    }

    public function getLabel(): LabelInterface
    {
        if (!$this->label) {
            $this->label = $this->labelRepository->get($this->getLabelId());
        }

        return $this->label;
    }

    public function getPlaceholderId(): ?int
    {
        return $this->getData(self::PLACEHOLDER_ID)
            ? (int)$this->getData(self::PLACEHOLDER_ID)
            : null;
    }

    public function setPlaceholderId(int $value): DisplayInterface
    {
        return $this->setData(self::PLACEHOLDER_ID, $value);
    }

    public function getPlaceholder(): ?PlaceholderInterface
    {
        if (!$this->placeholder && $this->getPlaceholderId()) {
            $this->placeholder = $this->placeholderRepository->get($this->getPlaceholderId());
        }

        return $this->placeholder;
    }

    public function getTemplateId(): ?int
    {
        return $this->getData(self::TEMPLATE_ID)
            ? (int)$this->getData(self::TEMPLATE_ID)
            : null;
    }

    public function setTemplateId(int $value): DisplayInterface
    {
        return $this->setData(self::PLACEHOLDER_ID, $value);
    }

    public function getTemplate(): ?TemplateInterface
    {
        if (!$this->template && $this->getTemplateId()) {
            $this->template = $this->templateRepository->get($this->getTemplateId());
        }

        return $this->template;
    }

    public function getTitle(): string
    {
        return (string)$this->getData(self::TITLE);
    }

    public function setTitle(string $value): DisplayInterface
    {
        return $this->setData(self::TITLE, $value);
    }

    public function getDescription(): string
    {
        return (string)$this->getData(self::DESCRIPTION);
    }

    public function setDescription(string $value): DisplayInterface
    {
        return $this->setData(self::DESCRIPTION, $value);
    }

    public function getImagePath(): ?string
    {
        return $this->getData(self::IMAGE_PATH) ?: null;
    }

    public function setImagePath(string $value): DisplayInterface
    {
        return $this->setData(self::IMAGE_PATH, $value);
    }

    public function getImageUrl(): string
    {
        if ($path = $this->getImagePath()) {
            return $this->config->getBaseMediaUrl() . '/' . $path;
        }

        return '';
    }

    private function imageExist(): bool
    {
        return $this->getImagePath()
            && file_exists($this->config->getBaseMediaPath() . '/' . $this->getImagePath());
    }

    private function ensureImageSize(): void
    {
        if (!$this->imageExist()) {
            return;
        }

        if ($this->imageWidth && $this->imageHeight) {
            return;
        }

        $width  = 50;
        $height = 50;

        $imagePath = $this->config->getBaseMediaPath() . '/' . $this->getImagePath();

        set_error_handler(function ($errno, $errstr) {}, E_WARNING);
        if (file_get_contents($this->config->getBaseMediaPath() . '/' . $this->getImagePath())) {
            if (file_get_contents($imagePath)) {
                list($imageW, $imageH) = getimagesize($imagePath);

                if ($imageW) {
                    $width = $imageW;
                }

                if ($imageH) {
                    $height = $imageH;
                }
            }
        }
        restore_error_handler();

        $this->imageWidth  = $width;
        $this->imageHeight = $height;
    }

    public function getImageWidth(): string
    {
        $this->ensureImageSize();

        return (string)$this->imageWidth;
    }

    public function getImageHeight(): string
    {
        $this->ensureImageSize();

        return (string)$this->imageHeight;
    }

    public function getUrl(): ?string
    {
        return $this->getData(self::URL) ?: null;
    }

    public function setUrl(string $value): DisplayInterface
    {
        return $this->setData(self::URL, $value);
    }

    public function getStyle(): ?string
    {
        $style = (string)$this->getData(self::STYLE);

        try {
            $processor = new \Less_Parser;
            $processor->parse($style);

            return $style;
        } catch (\Exception $e) {
            return '/* ' . $e->getMessage() . ' */';
        }
    }

    public function setStyle(string $value): DisplayInterface
    {
        return $this->setData(self::STYLE, $value);
    }

    public function getAttributeOptionId(): string
    {
        return (string)$this->getData(self::ATTR_OPTION_ID);
    }

    public function setAttributeOptionId(string $value): DisplayInterface
    {
        return $this->setData(self::ATTR_OPTION_ID, $value);
    }
}
