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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Registry;
use Magento\Ui\Component\MassAction\Filter;
use Mirasvit\CatalogLabel\Controller\Adminhtml\Template;
use Mirasvit\CatalogLabel\Repository\TemplateRepository;

class Save extends Template
{
    private $filterManager;

    public function __construct(
        FilterManager $filterManager,
        TemplateRepository $repository,
        Registry $registry,
        Context $context,
        Filter $filter
    ) {
        $this->filterManager = $filterManager;

        parent::__construct($repository, $registry, $context, $filter);
    }

    public function execute()
    {
        $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        if ($data = $this->getRequest()->getParams()) {
            $model = $this->getModel();
            $data  = $this->prepareData($data);

            $model->addData($data);

            $idx   = 0;
            $code  = $model->getCode();
            $exist = $this->repository->getByCode($code);

            while ($exist && $exist->getId() != $model->getId()) {
                $idx++;
                $model->setCode($code . '_' . $idx);
                $exist = $this->repository->getByCode($model->getCode());
            }

            try {
                $this->repository->save($model);

                $processor = new \Less_Parser;
                $processor->parse($model->getStyle());

                $this->messageManager->addSuccess((string)__('Template was successfully saved'));
                $this->backendSession->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['id' => $model->getId()]);

                    return;
                }
                $this->_redirect('*/*/');

                return;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->backendSession->setFormData($data);
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);

                return;
            }
        }

        $this->messageManager->addError((string)__('Unable to find a template to save'));
        $this->_redirect('*/*/');
    }

    private function prepareData(array $data): array
    {
        $code = isset($data['code']) && $data['code']
            ? $data['code']
            : $data['name'];

        $data['code'] = $this->filterManager->translitUrl($code);

        return $data;
    }
}
