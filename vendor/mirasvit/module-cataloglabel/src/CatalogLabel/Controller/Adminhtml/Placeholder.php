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

namespace Mirasvit\CatalogLabel\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Magento\Framework\Registry;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Mirasvit\CatalogLabel\Repository\PlaceholderRepository;

abstract class Placeholder extends Action
{
    protected $repository;

    protected $registry;

    protected $context;

    protected $backendSession;

    protected $resultFactory;

    protected $filter;

    public function __construct(
        PlaceholderRepository $repository,
        Registry $registry,
        Context $context,
        Filter $filter
    ) {
        $this->repository         = $repository;
        $this->registry           = $registry;
        $this->context            = $context;
        $this->backendSession     = $context->getSession();
        $this->resultFactory      = $context->getResultFactory();
        $this->filter             = $filter;

        parent::__construct($context);
    }

    /**
     * @param RequestInterface $request
     * @return ResponseInterface
     */
    public function dispatch(RequestInterface $request)
    {
        return parent::dispatch($request);
    }

    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Mirasvit_CatalogLabel::cataloglabel');

        $resultPage->getConfig()->getTitle()->prepend((string)__('Product Labels'));
        $resultPage->getConfig()->getTitle()->prepend((string)__('Manage Placeholders'));

        return $resultPage;
    }

    protected function getModel(): PlaceholderInterface
    {
        $model = $this->repository->create();
        if ($id = $this->getRequest()->getParam('id')) {
            $model = $this->repository->get((int)$id);
        }

        $this->registry->register('current_model', $model);

        return $model;
    }

    protected function _isAllowed(): bool
    {
        return $this->context->getAuthorization()
            ->isAllowed('Mirasvit_CatalogLabel::cataloglabel_placeholders');
    }
}
