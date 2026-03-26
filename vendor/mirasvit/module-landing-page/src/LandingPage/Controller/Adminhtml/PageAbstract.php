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

namespace Mirasvit\LandingPage\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Mirasvit\LandingPage\Api\Data\PageInterface;
use Mirasvit\LandingPage\Repository\PageRepository;

abstract class PageAbstract extends Action
{
    protected $pageRepository;

    protected $resultForwardFactory;

    private   $session;

    private   $registry;

    private   $context;

    public function __construct(
        PageRepository $pageRepository,
        Registry       $registry,
        ForwardFactory $resultForwardFactory,
        Context        $context
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        $this->registry             = $registry;
        $this->pageRepository       = $pageRepository;
        $this->context              = $context;
        $this->session              = $context->getSession();
        parent::__construct($context);
    }

    protected function initPage(ResultInterface $resultPage): ResultInterface
    {
        $resultPage->setActiveMenu('Mirasvit_LandingPage::landing');
        $resultPage->getConfig()->getTitle()->prepend((string)__('Landing Pages'));

        return $resultPage;
    }

    protected function initModel(): ?PageInterface
    {
        $model = $this->pageRepository->create();

        if ($id = $this->getRequest()->getParam(PageInterface::PAGE_ID)) {
            $model = $this->pageRepository->get((int)$id, (int)$this->getRequest()->getParam(PageInterface::STORE_ID));
            $this->registry->register('page_data', $model);
        }

        return $model;
    }

    protected function _isAllowed(): bool
    {
        return $this->context->getAuthorization()->isAllowed('Mirasvit_LandingPage::landing');
    }
}
