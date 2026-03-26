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
 * @package   mirasvit/module-sorting
 * @version   1.4.5
 * @copyright Copyright (C) 2026 Mirasvit (https://mirasvit.com/)
 */


declare(strict_types=1);

namespace Mirasvit\Sorting\Controller\Adminhtml\Criterion;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Mirasvit\Sorting\Api\Data\CriterionInterface;
use Mirasvit\Sorting\Api\Data\RankingFactorInterface;
use Mirasvit\Sorting\Controller\Adminhtml\CriterionAbstract;
use Mirasvit\Sorting\Model\ConfigProvider;
use Mirasvit\Sorting\Repository\CriterionRepository;
use Magento\Framework\FlagManager;
use Mirasvit\Sorting\Model\Indexer;
use Mirasvit\Sorting\Model\Criterion\ConditionCluster;

class Save extends CriterionAbstract
{
    private $flagManager;

    public function __construct(
        FlagManager $flagManager,
        CriterionRepository $criterionRepository,
        ConfigProvider $configProvider,
        ForwardFactory $resultForwardFactory,
        Context $context
    ) {
        $this->flagManager = $flagManager;

        parent::__construct($criterionRepository, $configProvider, $resultForwardFactory, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $id = $this->getRequest()->getParam(RankingFactorInterface::ID);

        $model = $this->initModel();

        $data = $this->getRequest()->getParams();

        $data = $this->filter($data);

        if ($data) {
            if (!$model->getId() && $id) {
                $this->messageManager->addErrorMessage((string)__('This criteria no longer exists.'));

                return $resultRedirect->setPath('*/*/');
            }

            $model->setName($data[CriterionInterface::NAME])
                ->setIsActive($data[CriterionInterface::IS_ACTIVE])
                ->setIsDefault($data[CriterionInterface::IS_DEFAULT])
                ->setIsSearchDefault($data[CriterionInterface::IS_SEARCH_DEFAULT])
                ->setCode($data[CriterionInterface::CODE])
                ->setPosition($data[CriterionInterface::POSITION])
                ->setConditionCluster($data[CriterionInterface::CONDITIONS]);

            try {
                $this->criterionRepository->save($model);

                $this->messageManager->addSuccessMessage((string)__('You have saved the criteria.'));

                $this->flagManager->saveFlag(Indexer::INDEXER_SHOULD_REINDEX, true);

                $this->esWarning();

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', [CriterionInterface::ID => $model->getId()]);
                }

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $resultRedirect->setPath('*/*/edit', [CriterionInterface::ID => $model->getId()]);
            }
        } else {
            $resultRedirect->setPath('*/*/');
            $this->messageManager->addErrorMessage((string)__('No data to save.'));

            return $resultRedirect;
        }
    }

    private function filter(array $data): array
    {
        if (!$data[CriterionInterface::POSITION]) {
            $data[CriterionInterface::POSITION] = 1;
        }

        if (!isset($data[CriterionInterface::CONDITIONS])) {
            $data[CriterionInterface::CONDITIONS] = [];
        }

        $conditionCluster = new ConditionCluster();
        $conditionCluster->loadArray($data[CriterionInterface::CONDITIONS]);

        $data[CriterionInterface::CONDITIONS] = $conditionCluster;

        return $data;
    }

    private function esWarning()
    {
        if ($this->configProvider->isElasticSearch()) {
            $this->messageManager->addWarningMessage('Elasticsearch engine is used. You should also run search reindex to apply sorting. `bin/magento indexer:reindex catalogsearch_fulltext`');
        }
    }
}
