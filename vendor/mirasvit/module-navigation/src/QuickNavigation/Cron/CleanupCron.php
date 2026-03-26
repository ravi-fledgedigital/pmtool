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


namespace Mirasvit\QuickNavigation\Cron;


use Mirasvit\QuickNavigation\Api\Data\SequenceInterface;
use Mirasvit\QuickNavigation\Model\ConfigProvider;
use Mirasvit\QuickNavigation\Repository\SequenceRepository;

class CleanupCron
{
    const UNPOPULAR_SEQUENCES_LIFETIME = 60;

    private $configProvider;

    private $sequenceRepository;

    public function __construct(
        ConfigProvider $configProvider,
        SequenceRepository $sequenceRepository
    ) {
        $this->configProvider     = $configProvider;
        $this->sequenceRepository = $sequenceRepository;
    }

    public function execute()
    {
        if (!$this->configProvider->isEnabled()) {
            return;
        }

        $collection = $this->sequenceRepository->getCollection();

        $resource   = $collection->getResource();
        $connection = $resource->getConnection();

        // delete unpopular sequences added more than 60 days ago
        $connection->delete(
            $resource->getTable(SequenceInterface::TABLE_NAME),
            SequenceInterface::POPULARITY . ' = 1 AND DATEDIFF(CURDATE(), updated_at) >= ' . self::UNPOPULAR_SEQUENCES_LIFETIME
        );

        // delete sequences that weren't updated for the last 180 days
        $connection->delete(
            $resource->getTable(SequenceInterface::TABLE_NAME),
            SequenceInterface::POPULARITY . ' > 1 AND DATEDIFF(CURDATE(), updated_at) >= ' . (3 * self::UNPOPULAR_SEQUENCES_LIFETIME)
        );
    }
}
