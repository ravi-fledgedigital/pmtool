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


namespace Mirasvit\CatalogLabel\Repository;


use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Model\AbstractModel;
use Mirasvit\CatalogLabel\Api\Data\IndexInterface;
use Mirasvit\CatalogLabel\Api\Data\LabelInterface;
use Mirasvit\CatalogLabel\Api\Data\DisplayInterface;
use Mirasvit\CatalogLabel\Helper\ArrayHelper;
use Mirasvit\CatalogLabel\Model\Indexer;
use Mirasvit\CatalogLabel\Model\LabelFactory;
use Mirasvit\CatalogLabel\Model\Label\DisplayFactory;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\Collection;
use Mirasvit\CatalogLabel\Model\ResourceModel\Label\CollectionFactory;
use Mirasvit\Core\Service\SecureOutputService;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LabelRepository
{
    private $factory;

    private $collectionFactory;

    private $entityManager;

    private $indexer;

    private $indexerRegistry;

    private $displayRepository;

    public function __construct(
        LabelFactory $factory,
        DisplayRepository $displayRepository,
        CollectionFactory $collectionFactory,
        Indexer $indexer,
        IndexerRegistry $indexerRegistry,
        EntityManager $entityManager
    ) {
        $this->factory           = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->indexer           = $indexer;
        $this->indexerRegistry   = $indexerRegistry;
        $this->entityManager     = $entityManager;
        $this->displayRepository = $displayRepository;
    }

    public function create(): LabelInterface
    {
        return $this->factory->create();
    }

    public function getCollection(): Collection
    {
        return $this->collectionFactory->create();
    }

    public function get(int $id): ?LabelInterface
    {
        $model = $this->create();

        $this->entityManager->load($model, $id);

        return $model->getId() ? $model : null;
    }

    public function save(LabelInterface $model): LabelInterface
    {
        $model = $this->entityManager->save($model);

        $this->afterSave($model);

        return $model;
    }

    public function delete(LabelInterface $model): bool
    {
        $this->beforeDelete($model);

        return $this->entityManager->delete($model);
    }

    private function afterSave(LabelInterface $model): void
    {
        if ($displayData = $model->getData('display')) {
            switch ($model->getType()) {
                case LabelInterface::TYPE_ATTRIBUTE:

                    foreach ($displayData as $attrOption => $data) {
                        $this->saveDisplays(
                            ArrayHelper::filterArrayRecursive($data),
                            $model,
                            (string)$attrOption
                        );
                    }

                    break;
                case LabelInterface::TYPE_RULE:
                    $this->saveDisplays(ArrayHelper::filterArrayRecursive($displayData), $model);
                    break;
            }
        }

        $idxr = $this->indexerRegistry->get(Indexer::INDEXER_ID);

        if (!$idxr->isScheduled()) {
            $this->indexer->reindexLabel((int)$model->getId());
        }
    }

    private function beforeDelete(LabelInterface $model): void
    {
        $resource   = $model->getResource();
        $connection = $resource->getConnection();

        $connection->delete(
            $resource->getTable(IndexInterface::TABLE_NAME),
            'label_id = ' . $model->getId()
        );
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function saveDisplays(array $data, LabelInterface $model, ?string $attrOption = null): void
    {
        $keys = [
            DisplayInterface::TITLE,
            DisplayInterface::DESCRIPTION,
            DisplayInterface::STYLE,
            DisplayInterface::URL
        ];

        foreach ($data as $type => $displayData) {
            $displayData = $displayData['display_data'];

            if (!isset($displayData[DisplayInterface::PLACEHOLDER_ID])) {
                throw new LocalizedException(__('Placeholder is not set. Part of the label data won\'t be saved'));
            }

            if (isset($displayData[DisplayInterface::TITLE])) {
                $displayData[DisplayInterface::TITLE] = SecureOutputService::cleanupOne($displayData[DisplayInterface::TITLE]);
            }

            $display = $this->displayRepository->create();

            if (isset($displayData[DisplayInterface::ID])) {
                $display = $this->displayRepository->get((int)$displayData[DisplayInterface::ID]);

                if (isset($displayData['delete'])) {
                    $this->displayRepository->delete($display);
                    continue;
                }

                if (strpos($model->getAppearence(), $type) === false) { // cleanup displays on label appearence change
                    $this->displayRepository->delete($display);
                    continue;
                }
            }

            // handle image updates
            if (isset($displayData['image'])) {
                $displayData[DisplayInterface::IMAGE_PATH] = $displayData['image'][0][DisplayInterface::IMAGE_PATH];
                unset($displayData['image']);
            } else {
                $displayData[DisplayInterface::IMAGE_PATH] = null;
            }

            $displayData[DisplayInterface::TYPE]           = $type;
            $displayData[DisplayInterface::LABEL_ID]       = $model->getId();
            $displayData[DisplayInterface::ATTR_OPTION_ID] = $attrOption;

            if (!isset($displayData[DisplayInterface::TEMPLATE_ID]) || $displayData[DisplayInterface::TEMPLATE_ID] == 0) {
                $displayData[DisplayInterface::TEMPLATE_ID] = null;
            }

            // restore fields after ArrayHelper
            foreach ($keys as $key) {
                if (!isset($displayData[$key])) {
                    $displayData[$key] = null;
                }
            }

            $display->setData($displayData);
            $this->displayRepository->save($display);
        }
    }
}
