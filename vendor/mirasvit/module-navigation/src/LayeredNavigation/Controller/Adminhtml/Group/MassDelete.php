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


namespace Mirasvit\LayeredNavigation\Controller\Adminhtml\Group;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use Mirasvit\LayeredNavigation\Repository\GroupRepository;

class MassDelete extends Action
{
    private $filter;

    private $groupRepository;

    public function __construct(
        GroupRepository $groupRepository,
        Filter $filter,
        Context $context
    ) {
        $this->filter          = $filter;
        $this->groupRepository = $groupRepository;

        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->groupRepository->getCollection());

        if (!$collection->getSize()) {
            $this->messageManager->addErrorMessage((string)__('Please select groups(s)'));
        } else {
            try {
                foreach ($collection as $event) {
                    $this->groupRepository->delete($event);
                }

                $this->messageManager->addSuccessMessage(
                    (string)__('Total of %1 record(s) were deleted', $collection->getSize())
                );
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        return $resultRedirect->setPath('*/*/');
    }
}
