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

namespace Mirasvit\CatalogLabel\Controller\Adminhtml\Placeholder;

use Magento\Framework\Controller\ResultFactory;
use Mirasvit\CatalogLabel\Api\Data\PlaceholderInterface;
use Mirasvit\CatalogLabel\Controller\Adminhtml\Placeholder;

class MassDelete extends Placeholder
{
    public function execute()
    {
        $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $ids      = $this->getRequest()->getParam('selected');
        $excluded = $this->getRequest()->getParam('excluded');

        if (!is_array($ids) && $excluded !== 'false' && !is_array($excluded)) {
            $this->messageManager->addError((string)__('Please select placeholder(s)'));
        } else {
            try {
                $placeholders = $this->repository->getCollection();

                if (is_array($ids)) {
                    $placeholders->addFieldToFilter(PlaceholderInterface::ID, ['in' => $ids]);
                } elseif (is_array($excluded)) {
                    $placeholders->addFieldToFilter(PlaceholderInterface::ID, ['nin' => $excluded]);
                } else {
                    $placeholders = $this->filter->getCollection($placeholders);
                }

                $deleted = 0;

                foreach ($placeholders as $placeholder) {
                    $this->repository->delete($placeholder);
                    $deleted++;
                }

                $this->messageManager->addSuccess(
                    (string)__('Total of %1 record(s) were successfully deleted', $deleted)
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }
}
