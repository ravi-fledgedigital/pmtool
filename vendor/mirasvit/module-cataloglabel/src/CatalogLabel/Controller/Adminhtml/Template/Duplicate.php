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


use Magento\Framework\Controller\ResultFactory;
use Mirasvit\CatalogLabel\Api\Data\TemplateInterface;
use Mirasvit\CatalogLabel\Controller\Adminhtml\Template;

class Duplicate extends Template
{
    public function execute()
    {
        $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $id = $this->getRequest()->getParam('id');

        if ($id) {
            $template = $this->repository->get((int)$id);

            if (!$template || !$template->getId()) {
                $this->messageManager->addError((string)__('Can\'t duplicate template with ID %1. Template no longer exists', $id));
                $this->_redirect('*/*/');
            }

            $templateData = $template->getData();

            unset($templateData[TemplateInterface::ID]);

            $templateData['code'] .= '_copy';
            $templateData['name'] .= ' (copy)';

            $newTemplate = $this->repository->create()
                ->setData($templateData);

            try {
                $this->repository->save($newTemplate);

                $this->messageManager->addSuccess((string)__('Template was successfully duplicated'));
                $this->_redirect('*/*/');

                return;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->_redirect('*/*/');

                return;
            }
        }

        $this->messageManager->addError((string)__('Unable to find a template to duplicate'));
        $this->_redirect('*/*/');
    }
}
