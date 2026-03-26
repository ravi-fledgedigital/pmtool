<?php

namespace Firebear\PlatformNetsuite\Model\Import;

use Firebear\ImportExport\Helper\Data;
use Firebear\ImportExport\Model\Email\Sender;
use Magento\Catalog\Model\Product as ProductCollection;
use Firebear\ImportExport\Model\Job\Processor;
use Firebear\ImportExport\Model\JobRepository;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use NetSuite\Classes\RecordType;
use Psr\Log\LoggerInterface;


/**
 * Class UpdateEntities
 *
 * @package Firebear\PlatformNetsuite\Model\Import
 */
class UpdateEntities implements \Firebear\PlatformNetsuite\Api\UpdateEntitiesInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var JobRepository
     */
    protected $jobRepository;

    /**
     * Email sender
     *
     * @var Sender
     */
    protected $sender;

    /**
     * @var array
     */
    protected $netsuiteEventScriptEntities = [
        'salesOrder' => RecordType::salesOrder,
        'inventoryItem' => RecordType::inventoryItem,
        'customer' => RecordType::customer
    ];

    /**
     * @var ProductCollection
     */
    private $productCollection;

    /**
     * UpdateEntities constructor.
     * @param LoggerInterface $logger
     * @param ProductCollection $productCollection
     * @param Processor $importProcessor
     * @param Data $helper
     * @param JobRepository $jobRepository
     * @param Sender $sender
     */
    public function __construct(
        LoggerInterface $logger,
        ProductCollection $productCollection,
        Processor $importProcessor,
        Data $helper,
        JobRepository $jobRepository,
        Sender $sender
    )
    {
        $this->productCollection = $productCollection;
        $this->helper = $helper;
        $this->processor = $importProcessor;
        $this->logger = $logger;
        $this->jobRepository = $jobRepository;
        $this->sender = $sender;
    }

    /**
     * @inheritDoc
     */
    public function updateEntity($entityType, $netsuiteInternalId, $jobId)
    {
        if ($jobId && $netsuiteInternalId) {
            $netsuiteEntityType = (isset($this->netsuiteEventScriptEntities[$entityType])) ?
                $this->netsuiteEventScriptEntities[$entityType] : null;
            if (!$netsuiteEntityType) {
                return 'Unsupported entity type';
            }
            $noProblems = 0;
            $result = false;
            $file = $this->helper->beforeRun($jobId);
            try {
                $history = $this->helper->createHistory($jobId, $file, 'console');
                $this->processor->debugMode = $this->debugMode = $this->helper->getDebugMode();
                $this->processor->inConsole = 1;
                $this->processor->setLogger($this->helper->getLogger());
                $job = $this->jobRepository->getById($jobId);
                $jobData = $job->getBehaviorData();
                $jobData['netsuite_internal_id'] = $netsuiteInternalId;
                $jobData['netsuite_entity_type'] = $netsuiteEntityType;
                $job->setBehaviorData($jobData);
                $this->jobRepository->save($job);
                $this->processor->processScope($jobId, $file);
                $counter = $this->helper->countData($file, $jobId);
                $error = 0;
                for ($i = 0; $i < $counter; $i++) {
                    list($count, $result) = $this->helper->processImport($file, $jobId, $i, $error, 0);
                    $error += $count;
                    if (!$result) {
                        $noProblems = 1;
                        break;
                    }
                }
                if (!$noProblems && $this->processor->reindex) {
                    $this->processor->processReindex($file, $jobId);
                }
                $this->processor->showErrors();
                $this->processor->getImportModel()->getErrorAggregator()->clear();
                $this->processor->getImportModel()->setNullEntityAdapter();
                $jobData['netsuite_internal_id'] = '';
                $job->setBehaviorData($jobData);
                $this->jobRepository->save($job);
                $this->helper->saveFinishHistory($history);
                return true;
            } catch (\Exception $exception) {
                return 'Please Check the import job id';
            }
        }
        return false;
    }
}
