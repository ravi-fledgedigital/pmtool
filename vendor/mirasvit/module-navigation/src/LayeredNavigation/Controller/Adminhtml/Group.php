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


namespace Mirasvit\LayeredNavigation\Controller\Adminhtml;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Backend\Model\View\Result\Page\Interceptor;
use Mirasvit\LayeredNavigation\Api\Data\GroupInterface;
use Mirasvit\LayeredNavigation\Repository\GroupRepository;

abstract class Group extends Action
{
    protected $repository;

    protected $context;

    public function __construct(
        GroupRepository $repository,
        Context $context
    ) {
        $this->repository = $repository;
        $this->context    = $context;

        parent::__construct($context);
    }

    public function initModel(): GroupInterface
    {
        $model = $this->repository->create();
        $id    = (int)$this->getRequest()->getParam(GroupInterface::ID);

        if ($id) {
            $model = $this->repository->get($id);
        }

        return $model;
    }

    protected function initPage(Page $resultPage): Interceptor
    {
        $resultPage->setActiveMenu('Mirasvit_LayeredNavigation::navigation');
        $resultPage->getConfig()->getTitle()->prepend((string)__('Mirasvit Layered Navigation'));
        $resultPage->getConfig()->getTitle()->prepend((string)__('Grouped Options'));

        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->context->getAuthorization()->isAllowed('Mirasvit_LayeredNavigation::layered_navigation_group');
    }
}
