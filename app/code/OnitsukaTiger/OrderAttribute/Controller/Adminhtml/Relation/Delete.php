<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Relation;

use Magento\Backend\App\Action;
use OnitsukaTiger\OrderAttribute\Api\RelationRepositoryInterface;

class Delete extends \OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Relation
{
    /**
     * @var RelationRepositoryInterface
     */
    private $repository;

    public function __construct(
        Action\Context $context,
        RelationRepositoryInterface $repository
    ) {
        parent::__construct($context);
        $this->repository = $repository;
    }

    public function execute()
    {
        if ($relationId = $this->getRequest()->getParam('relation_id')) {
            try {
                $this->repository->deleteById($relationId);
                $this->messageManager->addSuccessMessage(__('The Relation has been deleted.'));
            } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('This Relation does not exist.'));
            }
        }

        $this->_redirect('*/*/');
    }
}
