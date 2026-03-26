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

use Magento\Framework\Controller\ResultFactory;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\CatalogLabel\Controller\Adminhtml\Label;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassStatus extends Label
{
    public function execute()
    {
        $this->resultFactory->create(ResultFactory::TYPE_PAGE);

        $ids      = $this->getRequest()->getParam('selected');
        $excluded = $this->getRequest()->getParam('excluded');

        if (!is_array($ids) && $excluded !== 'false' && !is_array($excluded)) {
            $this->messageManager->addError((string)__('Please select label(s)'));
        } else {
            try {
                $labels = $this->labelRepository->getCollection();

                if (is_array($ids)) {
                    $labels->addFieldToFilter(LabelInterface::ID, ['in' => $ids]);
                } elseif (is_array($excluded)) {
                    $labels->addFieldToFilter(LabelInterface::ID, ['nin' => $excluded]);
                } else {
                    $labels = $this->filter->getCollection($labels);
                }

                $updated = 0;

                foreach ($labels as $label) {
                    $label->setIsActive((bool)$this->getRequest()->getParam('status'));

                    $this->labelRepository->save($label);

                    $updated++;
                }

                $this->messageManager->addSuccess(
                    (string)__('Total of %1 record(s) were successfully updated', $updated)
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
}
