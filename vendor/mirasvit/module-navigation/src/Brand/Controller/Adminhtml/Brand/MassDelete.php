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

namespace Mirasvit\Brand\Controller\Adminhtml\Brand;

use Magento\Backend\App\Action;
use Magento\Ui\Component\MassAction\Filter;
use Mirasvit\Brand\Model\ResourceModel\BrandPage\CollectionFactory;

class MassDelete extends Action
{
    private $filter;
    private $collectionFactory;

    public function __construct(
        Action\Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deletedCount = 0;
        foreach ($collection as $brandPage) {
            $brandPage->delete();
            $deletedCount++;
        }

        if ($deletedCount) {
            $this->messageManager->addSuccessMessage(
                __('A total of %1 record(s) have been deleted.', $deletedCount)
            );
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/index');
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Mirasvit_Brand::brand_delete');
    }
}
