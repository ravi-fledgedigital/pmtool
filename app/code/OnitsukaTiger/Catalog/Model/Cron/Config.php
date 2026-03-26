<?php
namespace OnitsukaTiger\Catalog\Model\Cron;

use Exception;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ValueFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use OnitsukaTiger\Logger\Logger;

class Config extends \Magento\Framework\App\Config\Value
{
    public const CRON_STRING_PATH = 'crontab/default/jobs/bestsellers_product_update/schedule/cron_expr';
    public const CRON_MODEL_PATH = 'crontab/default/jobs/bestsellers_product_update/run/model';
    /**
     * @var ValueFactory
     */
    protected ValueFactory $configValueFactory;

    /**
     * @var mixed|string
     */
    protected mixed $runModelPath;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * @param Logger $logger
     * @param ScopeConfigInterface $scopeConfig
     * @param Context $context
     * @param Registry $registry
     * @param ScopeConfigInterface $config
     * @param TypeListInterface $cacheTypeList
     * @param ValueFactory $configValueFactory
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param string $runModelPath
     * @param array $data
     */
    public function __construct(
        Logger               $logger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Context              $context,
        Registry             $registry,
        ScopeConfigInterface $config,
        TypeListInterface    $cacheTypeList,
        ValueFactory         $configValueFactory,
        AbstractResource     $resource = null,
        AbstractDb           $resourceCollection = null,
        string               $runModelPath = '',
        array                $data = [],
    ) {
        $this->runModelPath = $runModelPath;
        $this->configValueFactory = $configValueFactory;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
    }

    /**
     * @return Config
     * @throws Exception
     */
    public function afterSave(): Config
    {
        $scheduled = $this->getData('groups/cronScheduled/fields/scheduled/value');

        try {
            $this->configValueFactory->create()->load(
                self::CRON_STRING_PATH,
                'path'
            )->setValue(
                $scheduled
            )->setPath(
                self::CRON_STRING_PATH
            )->save();
            $this->configValueFactory->create()->load(
                self::CRON_MODEL_PATH,
                'path'
            )->setValue(
                $this->runModelPath
            )->setPath(
                self::CRON_MODEL_PATH
            )->save();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            throw new Exception(__('We can\'t save the cron expression.'));
        }
        return parent::afterSave();
    }
}
