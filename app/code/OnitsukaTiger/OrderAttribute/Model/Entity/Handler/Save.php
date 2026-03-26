<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Model\Entity\Handler;

use OnitsukaTiger\OrderAttribute\Model\ResourceModel\Entity\Entity as EntityResource;
use OnitsukaTiger\OrderAttribute\Api\Data\EntityDataInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

class Save
{
    /**
     * @var EntityResource
     */
    private $entityResource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(EntityResource $entityResource, LoggerInterface $logger)
    {
        $this->entityResource = $entityResource;
        $this->logger = $logger;
    }

    /**
     * @param EntityDataInterface|\OnitsukaTiger\OrderAttribute\Model\Entity\EntityData $entityData
     *
     * @return EntityDataInterface
     * @throws CouldNotSaveException
     */
    public function execute(EntityDataInterface $entityData)
    {
        try {
            if (!$entityData->getEntityId()) {
                $entityData->setEntityId($this->entityResource->reserveEntityId());
            }

            $this->entityResource->save($entityData);
        } catch (\Exception $e) {
            $this->logger->critical('Unable to save OnitsukaTiger Order Attributes', ['exception' => $e->getMessage()]);
            throw new CouldNotSaveException(__('Unable to save Order Attributes. Error: %1', $e->getMessage()));
        }

        return $entityData;
    }
}
