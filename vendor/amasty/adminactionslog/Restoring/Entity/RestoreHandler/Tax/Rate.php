<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Admin Actions Log for Magento 2
 */

namespace Amasty\AdminActionsLog\Restoring\Entity\RestoreHandler\Tax;

use Amasty\AdminActionsLog\Api\Data\LogDetailInterface;
use Amasty\AdminActionsLog\Api\Data\LogEntryInterface;
use Amasty\AdminActionsLog\Api\Logging\ObjectDataStorageInterface;
use Amasty\AdminActionsLog\Restoring\Entity\RestoreHandler\AbstractHandler;
use Amasty\Base\Model\Serializer;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Api\Data\TaxRateTitleInterface;
use Magento\Tax\Api\Data\TaxRateTitleInterfaceFactory;
use Magento\Tax\Api\TaxRateRepositoryInterface;

class Rate extends AbstractHandler
{
    public function __construct(
        ObjectManagerInterface $objectManager,
        ObjectDataStorageInterface $dataStorage,
        StoreManagerInterface $storeManager,
        private readonly Serializer $serializer,
        private readonly TaxRateTitleInterfaceFactory $taxRateTitleDataObjectFactory,
        private readonly TaxRateRepositoryInterface $taxRateRepository
    ) {
        parent::__construct($objectManager, $dataStorage, $storeManager);
    }

    public function restore(
        LogEntryInterface $logEntry,
        array $logDetails
    ): void {
        if (empty($logDetails)) {
            return;
        }

        $taxRate = $this->getModelObject($logEntry, current($logDetails));

        /** @var LogDetailInterface $logDetail */
        foreach ($logDetails as $logDetail) {
            $oldValue = $logDetail->getOldValue();
            $elementKey = $logDetail->getName();

            if ($elementKey === 'titles') {
                $oldValue = $this->serializer->unserialize($oldValue) ?: [];

                if ($oldValue) {
                    $oldValue = $this->prepareTitles($oldValue);
                } else {
                    $taxRate->saveTitles($oldValue);
                }
            }

            $taxRate->setData($elementKey, $oldValue);
        }

        $this->setRestoreActionFlag($taxRate);
        $this->taxRateRepository->save($taxRate);
    }

    /**
     * @param string[] $titlesData
     * @return TaxRateTitleInterface[]
     */
    private function prepareTitles(array $titlesData): array
    {
        $titles = [];
        foreach ($titlesData as $storeId => $value) {
            $titles[] = $this->taxRateTitleDataObjectFactory->create()
                ->setStoreId($storeId)
                ->setValue($value);
        }

        return $titles;
    }
}
