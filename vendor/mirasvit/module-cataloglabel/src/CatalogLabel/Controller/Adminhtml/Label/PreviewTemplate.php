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


namespace Mirasvit\CatalogLabel\Controller\Adminhtml\Label;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Element\BlockFactory;
use Mirasvit\CatalogLabel\Block\Adminhtml\Template\Preview as PreviewBlock;
use Mirasvit\CatalogLabel\Repository\TemplateRepository;
use Mirasvit\Core\Service\SecureOutputService;
use Mirasvit\Core\Service\SerializeService;


class PreviewTemplate extends Action
{
    private $templateRepository;

    private $blockFactory;

    public function __construct(
        BlockFactory $blockFactory,
        TemplateRepository $templateRepository,
        Context $context
    ) {
        $this->templateRepository = $templateRepository;
        $this->blockFactory       = $blockFactory;

        parent::__construct($context);
    }

    public function execute()
    {
        $data          = SecureOutputService::cleanupArray($this->getRequest()->getParams());
        $displayStyles = $data['style'];
        $wrapperClass  = $data['class'] ?? '';
        $previewData   = [];

        try {

            foreach ($this->templateRepository->getCollection() as $template) {
                $previewBlock = $this->blockFactory->createBlock(PreviewBlock::class);

                $templateData = [];

                foreach ($data as $key => $value) {
                    $templateData['test_' . $key] = $value;
                }

                if (!isset($templateData['test_title']) || !$templateData['test_title']) {
                    $templateData['test_title'] = (string)__('Example text');
                }

                $previewBlock->setLabelTemplate($template);

                $previewData[$template->getId()] = '<div class="template-preview">'
                    . $previewBlock->getTemplateHtmlContent($templateData)
                    . $this->prepareStyles($template->getStyle(), $displayStyles, $wrapperClass)
                    . '</div>';
            }

            $response = [
                'success' => true,
                'preview' => $previewData
            ];
        } catch (\Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        $this->getResponse()->representJson(SerializeService::encode($response));
    }

    private function prepareStyles(string $templateStyle = '', string $displayStyle = '', string $wrapperClass = ''): string
    {
        if (!$templateStyle && !$displayStyle) {
            return '';
        }

        $styles = $wrapperClass . ' .template-preview { ' . $templateStyle . $displayStyle . ' }';

        $processor = new \Less_Parser;
        $processor->parse($styles);

        $css = $processor->getCss();

        return '<style>' . $css . '</style>';
    }
}
