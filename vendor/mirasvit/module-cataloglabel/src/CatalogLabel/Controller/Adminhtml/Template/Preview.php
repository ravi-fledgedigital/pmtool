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


namespace Mirasvit\CatalogLabel\Controller\Adminhtml\Template;


use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockFactory;
use Magento\Ui\Component\MassAction\Filter;
use Mirasvit\CatalogLabel\Controller\Adminhtml\Template;
use Mirasvit\CatalogLabel\Block\Adminhtml\Template\Preview as PreviewBlock;
use Mirasvit\CatalogLabel\Repository\TemplateRepository;
use Mirasvit\Core\Service\SecureOutputService;
use Mirasvit\Core\Service\SerializeService;

class Preview extends Template
{
    private $blockFactory;

    public function __construct(
        BlockFactory $blockFactory,
        TemplateRepository $repository,
        Registry $registry,
        Context $context,
        Filter $filter
    ) {
        $this->blockFactory = $blockFactory;

        parent::__construct($repository, $registry, $context, $filter);
    }

    public function execute()
    {
        $data     = SecureOutputService::cleanupArray($this->getRequest()->getParams());
        $template = $this->repository->create();

        if (isset($data['preview'])) {
            unset($data['preview']);
        }

        try {
            $template->setData($data);

            /** @var PreviewBlock $block */
            $block = $this->blockFactory->createBlock(PreviewBlock::class)
                ->setTemplate('Mirasvit_CatalogLabel::template/template-preview.phtml');

            $block->setLabelTemplate($template);

            $this->getResponse()->representJson(SerializeService::encode([
                'success' => true,
                'html' => $block->getTemplateHtmlContent($data),
                'styles' => $block->getTemplateStyles()
            ]));
        } catch (\Exception $e) {
            $this->getResponse()->representJson(SerializeService::encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]));
        }
    }
}
