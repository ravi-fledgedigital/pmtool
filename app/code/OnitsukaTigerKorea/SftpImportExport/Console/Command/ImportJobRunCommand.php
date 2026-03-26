<?php
//phpcs:ignoreFile
declare(strict_types=1);
namespace OnitsukaTigerKorea\SftpImportExport\Console\Command;

use Firebear\ImportExport\Api\JobRepositoryInterface;
use Firebear\ImportExport\Helper\Data;
use Firebear\ImportExport\Model\Email\Sender;
use Firebear\ImportExport\Model\Job\Processor;
use Firebear\ImportExport\Model\JobFactory;
use Magento\Framework\App\Area;
use \Magento\Framework\App\AreaList;
use Magento\Framework\App\State;
use Magento\Framework\Xml\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;
use OnitsukaTiger\Logger\Api\Logger as OnitsukaTigerLogger;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport\ImportShipping;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport\ImportRelease;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport\ImportComplete;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport\ImportReceiving;
use OnitsukaTigerKorea\SftpImportExport\Model\SftpImport\ImportReceipt;

class ImportJobRunCommand extends Command {

    const NAME = 'name';
    const FILE_PATH = 'path';
    const IMPORT_SHIPPING = 'Shipping';
    const IMPORT_RELEASE = 'Release';
    const IMPORT_COMPLETE = 'Complete';
    const IMPORT_RECEIVE = 'Receive';
    const IMPORT_RECEIPT = 'Receipt';
    const IMPORT_REPLENISH = 'Replenish';

    /**
     * @var State
     */
    protected $state;

    /**
     * @var Parser
     */
    protected $parser;

    /**
     * @var JobFactory
     */
    protected $factory;

    /**
     * @var JobRepositoryInterface
     */
    protected $repository;

    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var OnitsukaTigerLogger
     */
    protected $logger;

    protected $debugMode;

    /**
     * @var Data
     */
    protected $helper;

    protected $loggerRun;

    /**
     * Email sender
     *
     * @var Sender
     */
    protected $sender;

    /**
     * @var ShipmentRepositoryInterface
     */
    protected $shipmentRepository;

    /**
     * @var ImportShipping
     */
    protected $importShipping;

    /**
     * @var ImportRelease
     */
    protected $importRelease;

    /**
     * @var ImportComplete
     */
    protected $importComplete;

    /**
     * @var ImportReceiving
     */
    protected $importReceiving;

    /**
     * @var ImportReceipt
     */
    protected $importReceipt;

    /**
     * @var AreaList
     */
    private $areaList;

    /**
     * @var \OnitsukaTigerKorea\SftpImportExport\Helper\Data
     */
    protected $helperSftpKorea;

    /**
     * ImportJobRunCommand constructor.
     * @param \OnitsukaTigerKorea\SftpImportExport\Helper\Data $helperSftpKorea
     * @param AreaList $areaList
     * @param ShipmentRepositoryInterface $shipmentRepository
     * @param JobFactory $factory
     * @param JobRepositoryInterface $repository
     * @param OnitsukaTigerLogger $logger
     * @param Processor $importProcessor
     * @param Data $helper
     * @param State $state
     * @param Parser $parser
     * @param Sender $sender
     * @param ImportShipping $importShipping
     * @param ImportRelease $importRelease
     * @param ImportComplete $importComplete
     * @param ImportReceiving $importReceiving
     * @param ImportReceipt $importReceipt
     * @param string|null $name
     */
    public function __construct(
        \OnitsukaTigerKorea\SftpImportExport\Helper\Data $helperSftpKorea,
        AreaList $areaList,
        ShipmentRepositoryInterface $shipmentRepository,
        JobFactory $factory,
        JobRepositoryInterface $repository,
        OnitsukaTigerLogger $logger,
        Processor $importProcessor,
        Data $helper,
        State $state,
        Parser $parser,
        Sender $sender,
        ImportShipping $importShipping,
        ImportRelease $importRelease,
        ImportComplete $importComplete,
        ImportReceiving $importReceiving,
        ImportReceipt $importReceipt,
        string $name = null
    ) {
        $this->helperSftpKorea = $helperSftpKorea;
        $this->areaList = $areaList;
        $this->shipmentRepository = $shipmentRepository;
        $this->factory = $factory;
        $this->repository = $repository;
        $this->processor = $importProcessor;
        $this->state = $state;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->sender = $sender;
        $this->parser = $parser;
        $this->importShipping = $importShipping;
        $this->importRelease = $importRelease;
        $this->importComplete = $importComplete;
        $this->importReceiving = $importReceiving;
        $this->importReceipt = $importReceipt;
        parent::__construct($name);
    }

    /**
     * Configures the current command.
     *
     * @throws \Symfony\Component\Console\Exception\InvalidArgumentException
     */
    protected function configure()
    {
        $this->setName('sftp:import:file')
            ->setDescription('Import data from file SFTP on Korea site')
            ->addArgument(
                self::NAME,
                InputArgument::REQUIRED,
                'The name Node of the IF to be started: Option: Shipping, Release, Complete, Receiving, Receipt, Replenish'
            )
            ->addOption(
                self::FILE_PATH,
                'p',
                InputOption::VALUE_OPTIONAL,
                'File Path'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $time = explode(" ", microtime());
        $startTime = $time[0] + $time[1];

        $isAreaCode = 0;
        try {
            $isAreaCode = $this->state->getAreaCode() ? 1 : 0;
        } catch (\Exception $e) {
            $isAreaCode = 0;
        }
        if (!$isAreaCode) {
            $this->state->setAreaCode(Area::AREA_GLOBAL);
        }

        $area = $this->areaList->getArea($this->state->getAreaCode());
        $area->load(Area::PART_TRANSLATE);

        $filePath = $input->getOption(self::FILE_PATH);
        $result = $this->parser->load($filePath)->xmlToArray();

        $nodeObject = array_keys($result['root']);
        $iFObject = $input->getArgument(self::NAME);

        if($iFObject != $nodeObject[0]) {
            $this->addLogComment('Input Object Argument different with Object in file '. $filePath . ' import.', $output, 'info');
            $this->addLogComment('Stop process.', $output, 'info');
            $this->logger->debug('Input Object Argument different with Object in file '. $filePath . ' import.');
            return false;
        }

        $this->addLogComment('Start file path: '. $filePath . ' begin', $output, 'info');
        $this->logger->debug('Start file path: '. $filePath . ' begin');
        switch ($iFObject) {
            case self::IMPORT_RELEASE:
                $release = $result['root'][self::IMPORT_RELEASE];
                if (array_key_exists('order_no', $release)) {
                    $result = $this->importRelease->execute([$release]);
                }else if (array_key_exists('order_no', $release[0])){
                    $result = $this->importRelease->execute($release);
                }
                break;
            case self::IMPORT_SHIPPING:
                $shipping = $result['root'][self::IMPORT_SHIPPING];
                if (array_key_exists('order_no', $shipping)) {
                    $result = $this->importShipping->execute([$shipping]);
                }else if (array_key_exists('order_no', $shipping[0])){
                    $result = $this->importShipping->execute($shipping);
                }
                break;
            case self::IMPORT_COMPLETE:
                $complete = $result['root'][self::IMPORT_COMPLETE];
                if (array_key_exists('order_no', $complete)) {
                    $result = $this->importComplete->execute([$complete]);
                }else if (array_key_exists('order_no', $complete[0])){
                    $result = $this->importComplete->execute($complete);
                }
                break;
            case self::IMPORT_RECEIVE:
                $receiving = $result['root'][self::IMPORT_RECEIVE];
                if (array_key_exists('rtn_order_no', $receiving)) {
                    $result = $this->importReceiving->execute([$receiving]);
                }else if (array_key_exists('rtn_order_no', $receiving[0])){
                    $result = $this->importReceiving->execute($receiving);
                }
                break;
            case self::IMPORT_RECEIPT:
                $receipt = $result['root'][self::IMPORT_RECEIPT];
                if (array_key_exists('rtn_order_no', $receipt)) {
                    $result = $this->importReceipt->execute([$receipt]);
                }else if (array_key_exists('rtn_order_no', $receipt[0])){
                    $result = $this->importReceipt->execute($receipt);
                }
                break;
            case self::IMPORT_REPLENISH:
                break;
        }

        $time = explode(" ", microtime());
        $endTime = $time[0] + $time[1];
        $totalTime = $endTime - $startTime;
        $totalTime = round($totalTime, 5);

        $flag = array_keys($result);
        if (empty($flag) && !is_null($flag)) {
            $this->addLogComment('Nothing data import', $output, 'info');
            $this->logger->debug('Nothing data import');
        }

        $this->addLogComment("Running time: " . $totalTime, $output, 'info');
        $this->logger->debug('Stop file path: ' . $filePath . ' end');
        $this->addLogComment('Stop file path: ' . $filePath . ' end', $output, 'info');

        return Command::SUCCESS;
    }

    /**
     * @param $debugData
     * @param OutputInterface|null $output
     * @param null $type
     * @return $this
     */
    public function addLogComment($debugData, OutputInterface $output = null, $type = null)
    {

        if ($this->debugMode) {
            $this->logger->debug($debugData);
        }

        if ($output) {
            switch ($type) {
                case 'error':
                    $debugData = '<error>' . $debugData . '</error>';
                    break;
                case 'info':
                    $debugData = '<info>' . $debugData . '</info>';
                    break;
                default:
                    $debugData = '<comment>' . $debugData . '</comment>';
                    break;
            }

            $output->writeln($debugData);
        }

        return $this;
    }
}
