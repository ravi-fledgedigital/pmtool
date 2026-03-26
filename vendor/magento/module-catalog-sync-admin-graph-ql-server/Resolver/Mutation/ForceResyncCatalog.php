<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogSyncAdminGraphQlServer\Resolver\Mutation;

use Magento\Cron\Model\Schedule;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Psr\Log\LoggerInterface;
use Magento\Cron\Model\ScheduleFactory;

/**
 * Resolver for mutation forceResyncCatalog
 */
class ForceResyncCatalog implements ResolverInterface
{
    private const JOB_CODE = 'force_resync_catalog';
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /**
     * @var ScheduleFactory
     */
    private $scheduleFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * @param ScheduleFactory $scheduleFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScheduleFactory $scheduleFactory,
        LoggerInterface $logger
    ) {
        $this->scheduleFactory = $scheduleFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        try {
            $currentTime = new \DateTime();
            $createdAt = $currentTime->format(self::DATE_FORMAT);
            $scheduleAt = $currentTime->modify('+1 minutes')->format(self::DATE_FORMAT);
            $this->scheduleFactory->create()
                ->setJobCode(self::JOB_CODE)
                ->setStatus(Schedule::STATUS_PENDING)
                ->setCreatedAt($createdAt)
                ->setScheduledAt($scheduleAt)
                ->setCronExp("* * * * *")
                ->save();

            $result = ['message' => "Catalog data re-sync requested"];
        } catch (\Exception $ex) {
            $errorMessage ='An error occurred during catalog data re-sync';
            $this->logger->error($errorMessage . ": ". $ex->getMessage());
            $result = ['message' => $errorMessage];
        }
        return $result;
    }
}
