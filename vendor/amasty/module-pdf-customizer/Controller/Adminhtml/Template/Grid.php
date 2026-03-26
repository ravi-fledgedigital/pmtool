<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Controller\Adminhtml\Template;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Layout\Builder;

class Grid extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Amasty_PDFCustom::template';

    /**
     * @var Builder
     */
    private $builder;

    public function __construct(
        Context $context,
        Builder $builder
    ) {
        parent::__construct($context);
        $this->builder = $builder;
    }

    /**
     * Grid action
     *
     * @return void
     */
    public function execute()
    {
        $this->builder->build();
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->renderResult($this->_response);
    }
}
