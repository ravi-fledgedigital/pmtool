<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Relation;

use OnitsukaTiger\OrderAttribute\Api\RelationRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Framework\App\Request\DataPersistorInterface;
use Psr\Log\LoggerInterface;
use OnitsukaTiger\OrderAttribute\Model\Attribute\Relation\RelationFactory;

class Save extends \OnitsukaTiger\OrderAttribute\Controller\Adminhtml\Relation
{
    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    /**
     * @var RelationRepositoryInterface
     */
    private $repository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var RelationFactory
     */
    private $relationFactory;

    public function __construct(
        Action\Context $context,
        RelationRepositoryInterface $repository,
        RelationFactory $relationFactory,
        DataPersistorInterface $dataPersistor,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->dataPersistor = $dataPersistor;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->relationFactory = $relationFactory;
    }

    /**
     * Save Action
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getPostValue()) {

            /** @var \OnitsukaTiger\OrderAttribute\Model\Attribute\Relation\Relation $model */
            $model = $this->relationFactory->create();
            $relationId = $this->getRequest()->getParam('relation_id');

            try {
                if ($relationId) {
                    $model = $this->repository->get($relationId);
                }

                $model->loadPost($data);

                $this->repository->save($model);

                $this->messageManager->addSuccessMessage(__('The Relation has been saved.'));

                if ($this->getRequest()->getParam('back')) {
                    $this->redirectToEdit($model->getId());
                    return;
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->redirectToEdit($relationId, $data);
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('The Relation has not been saved. Please review the error log for the details.')
                );
                $this->logger->critical($e);
                $this->redirectToEdit($relationId, $data);
                return;
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Redirect to Edit or New and save $data to session
     *
     * @param null|int $relationId
     * @param null|array $data
     */
    private function redirectToEdit($relationId = null, $data = null)
    {
        if ($data) {
            $this->dataPersistor->set('onitsukatiger_order_attribute_relation', $data);
        }
        if ($relationId) {
            $this->_redirect('*/*/edit', ['relation_id' => $relationId]);
            return;
        }
        $this->_redirect('*/*/new');
    }
}
