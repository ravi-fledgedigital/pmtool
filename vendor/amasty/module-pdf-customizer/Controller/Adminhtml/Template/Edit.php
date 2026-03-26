<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Adminhtml\Template;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Layout\Builder;

class Edit extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Amasty_PDFCustom::template';

    /**
     * @var \Amasty\PDFCustom\Model\TemplateFactory
     */
    private $templateFactory;

    /**
     * @var Builder
     */
    private $builder;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Amasty\PDFCustom\Model\TemplateFactory $templateFactory,
        Builder $builder
    ) {
        parent::__construct($context);
        $this->templateFactory = $templateFactory;
        $this->builder = $builder;
    }

    /**
     * Edit PDF template action
     *
     * @return void
     */
    public function execute()
    {
        $this->builder->build();
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $id = (int)$this->getRequest()->getParam('id');
        /** @var \Amasty\PDFCustom\Model\Template $template */
        $template = $this->templateFactory->create();
        if ($id) {
            $template->load($id);
        }
        $resultPage->setActiveMenu('Amasty_PDFCustom::template');

        if ($this->getRequest()->getParam('id')) {
            $resultPage->addBreadcrumb(__('Edit Template'), __('Edit System Template'));
        } else {
            $resultPage->addBreadcrumb(__('New Template'), __('New System Template'));
        }
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('PDF Templates'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $template->getId() ? $template->getTemplateCode() : __('New Template')
        );

        $resultPage->addContent(
            $this->_view->getLayout()->createBlock(
                \Amasty\PDFCustom\Block\Adminhtml\Template\Edit::class,
                'template_edit',
                [
                    'data' => [
                        'email_template' => $template
                    ]
                ]
            )->setEditMode(
                (bool)$this->getRequest()->getParam('id')
            )
        );
        $resultPage->renderResult($this->_response);
    }
}
