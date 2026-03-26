<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\SalesArchive\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\SalesArchive\Model\Config;
use Magento\SalesArchive\Model\ResourceModel\Archive;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ArchiveGridAsyncUpdateObserver implements ObserverInterface
{

    /**
     * Archival entity names
     */
    private const STATUS = 'status';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Archive
     */
    private $archive;

    /**
     * Global configuration storage.
     *
     * @var ScopeConfigInterface
     */
    private $globalConfig;

    /**
     * @param config $config
     * @param Archive $archive
     * @param ScopeConfigInterface $globalConfig
     */
    public function __construct(
        Config $config,
        Archive $archive,
        ScopeConfigInterface $globalConfig
    ) {
        $this->config = $config;
        $this->archive = $archive;
        $this->globalConfig = $globalConfig;
    }

    /**
     * Handles asynchronous update of the updated entity
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->config->isArchiveActive()) {
            return $this;
        }
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();
        if ($order->getId() && $this->globalConfig->isSetFlag('dev/grid/async_indexing')) {
            $connection = $this->archive->getConnection();
            $connection->update(
                $this->archive->getArchiveEntityTable('order'),
                [self::STATUS => $order->getStatus()],
                $connection->quoteInto($order->getIdFieldName() . '= ?', $order->getId())
            );
        }
    }
}
