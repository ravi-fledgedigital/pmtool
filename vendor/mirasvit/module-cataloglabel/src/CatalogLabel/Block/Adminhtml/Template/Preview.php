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


namespace Mirasvit\CatalogLabel\Block\Adminhtml\Template;


use Magento\Framework\DataObject;
use Magento\Framework\View\Element\Template;
use Mirasvit\CatalogLabel\Api\Data\TemplateInterface;
use Mirasvit\CatalogLabel\Repository\TemplateRepository;
use Mirasvit\CatalogLabel\Service\ContentService;

class Preview extends Template
{
    /**
     * @var null|TemplateInterface
     */
    private $labelTemplate = null;

    private $templateRepository;

    private $contentService;

    private $labelData = [];

    public function __construct(
        TemplateRepository $repository,
        ContentService $contentService,
        Template\Context $context,
        array $data = []
    ) {
        $this->templateRepository = $repository;
        $this->contentService     = $contentService;

        parent::__construct($context, $data);
    }

    public function setLabelTemplate(TemplateInterface $template): self
    {
        $this->labelTemplate = $template;

        return $this;
    }

    public function getLabelTemplate(): ?TemplateInterface
    {
       return $this->labelTemplate;
    }

    public function getTemplateHtmlContent(array $data = []): string
    {
        $this->ensureTemplate();

        if (!empty($data)) {
            $this->labelData = $data;
        }

        return $this->labelTemplate
            ? $this->contentService->processHtmlContent($this->labelTemplate->getHtmlTemplate(), $this->prepareLabelData($this->labelData))
            : '';
    }

    public function getTemplateStyles(): string
    {
        $this->ensureTemplate();

        if (!$this->labelTemplate) {
            return '';
        }

        $css = '.preview_content .htmlcontent { ' . $this->labelTemplate->getStyle() . ' }';

        $processor = new \Less_Parser;
        $processor->parse($css);

        $css = $processor->getCss();

        return '<style>' . $css . '</style>';
    }

    public function getJsConfig(): array
    {
        return ['previewUrl' => $this->_urlBuilder->getUrl('*/*/preview')];
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     *
     * @return DataObject[]
     */
    private function prepareLabelData(array $labelData = []): array
    {
        $title       = isset($labelData['test_title']) && $labelData['test_title']
            ? $labelData['test_title']
            : '';

        $description = isset($labelData['test_description']) && $labelData['test_description']
            ? $labelData['test_description']
            : '';

        $imageUrl    = isset($labelData['test_image_url']) && $labelData['test_image_url']
            ? $labelData['test_image_url']
            : $this->getViewFileUrl('Mirasvit_CatalogLabel::images/mirasvit-logo-85.png');

        $w = 50;
        $h = 50;

        set_error_handler(function ($errno, $errstr) {}, E_WARNING);
        if (file_get_contents($imageUrl)) {
            list($imageW, $imageH) = getimagesize($imageUrl);

            if ($imageW) {
                $w = $imageW;
            }

            if ($imageH) {
                $h = $imageH;
            }
        }
        restore_error_handler();

        $imageTemplateStyles = 'background:url(' . $imageUrl
            . '); background-repeat: no-repeat; width: ' . $w . 'px; height: ' . $h . 'px; '
            . 'display: flex; justify-content: center; align-items: center; text-align: center';

        $imageTemplate = '<div class="label-image" style="' . $imageTemplateStyles . '">'
            . '<span class="label-title">' . $title . '</span>'
            . '</div>';

        $data = [
            'title'       => $title,
            'description' => $description,
            'image_url'   => $imageUrl,
            'image'       => $imageTemplate
        ];

        return ['label' => new DataObject($data)];
    }

    private function ensureTemplate(): void
    {
        if (!$this->labelTemplate && ($id = $this->getRequest()->getParam('id'))) {
            $this->labelTemplate = $this->templateRepository->get((int)$id);
        }
    }

    public function setLabelData(array $data): self
    {
        $this->labelData = $data;

        return $this;
    }

    public function isFormPage(): bool
    {
        return $this->getRequest()->getFullActionName() == 'cataloglabel_template_edit';
    }
}
