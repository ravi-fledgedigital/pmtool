<?php
namespace OnitsukaTiger\StaticBlocks\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class InstallData.
 * Sets the data on module install.
 *
 * @package Onisuka\StaticBlocks\Setup
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface $_config Config resource model.
     */
    protected $_config;
    /**
     * @var \OnitsukaTiger\Fixture\Model\Cms\Block $_cmsBlock Fixture cms block.
     */
    protected $_cmsBlock;
    /**
     * @var \OnitsukaTiger\Fixture\Model\Cms\Page $_cmsPage Fixture cms page.
     */
    protected $_cmsPage;
    /**
     * @var \Magento\Cms\Model\PageFactory $_cmsPageFactory Cms page factory.
     */
    protected $_cmsPageFactory;
    /**
     * @var \Magento\Cms\Api\PageRepositoryInterface $_cmsPageRepository Cms page repository.
     */
    protected $_cmsPageRepository;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteFactory $_directoryFactory Writable directory factory.
     */
    protected $_directoryFactory;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList DirectoryList Directory list.
     */
    protected $_directoryList;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface $_storeManager Store manager.
     */
    protected $_storeManager;
    /**
     * @var \Psr\Log\LoggerInterface $_logger Logger instance.
     */
    protected $_logger;

    /**
     * InstallData constructor.
     *
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config            Config resource model.
     * @param \OnitsukaTiger\Fixture\Model\Cms\Block                             $cmsBlock          Fixture cms block.
     * @param \OnitsukaTiger\Fixture\Model\Cms\Page                              $cmsPage           Fixture cms page.
     * @param \Magento\Cms\Model\PageFactory                               $cmsPageFactory    Cms page factory.
     * @param \Magento\Cms\Api\PageRepositoryInterface                     $cmsPageRepository Cms page repository.
     * @param \Magento\Framework\Filesystem\Directory\WriteFactory         $directoryFactory  Writable directory
     *                                                                                        factory.
     * @param \Magento\Framework\App\Filesystem\DirectoryList              $directoryList     Directory list.
     * @param \Magento\Store\Model\StoreManagerInterface                   $storeManager      Store manager.
     * @param \Psr\Log\LoggerInterface                                     $logger            Logger instance.
     */
    public function __construct(
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $config,
        \OnitsukaTiger\Fixture\Model\Cms\Block $cmsBlock,
        \OnitsukaTiger\Fixture\Model\Cms\Page $cmsPage,
        \Magento\Cms\Model\PageFactory $cmsPageFactory,
        \Magento\Cms\Api\PageRepositoryInterface $cmsPageRepository,
        \Magento\Framework\Filesystem\Directory\WriteFactory $directoryFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->_config = $config;
        $this->_cmsBlock = $cmsBlock;
        $this->_cmsPage = $cmsPage;
        $this->_cmsPageFactory = $cmsPageFactory;
        $this->_cmsPageRepository = $cmsPageRepository;
        $this->_directoryFactory = $directoryFactory;
        $this->_directoryList = $directoryList;
        $this->_storeManager = $storeManager;
        $this->_logger = $logger;
    }

    /**
     * Installs the data.
     *
     * @param ModuleDataSetupInterface $setup Setup model.
     * @param ModuleContextInterface $context Context model.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $this->_installCmsBlocks();
        $this->_deactivateStandardPages();

        $setup->endSetup();
    }

    /**
     * Installs cms blocks.
     *
     * @return void
     */
    private function _installCmsBlocks()
    {
        $this->_cmsBlock->install(
            [
                'OnitsukaTiger_StaticBlocks::fixtures/blocks/general/footer-flagship-stores.csv'
            ]
        );
    }
    /**
     * Deactivates standard cms pages.
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _deactivateStandardPages()
    {
        $pageIdentifiers = [
            'enable-cookies'
        ];

        foreach ($pageIdentifiers as $pageIdentifier) {
            /** @var \Magento\Cms\Model\Page $cmsPage */
            $cmsPage = $this->_cmsPageFactory->create();
            $cmsPage->load($pageIdentifier, \Magento\Cms\Model\Page::IDENTIFIER);

            if (!$cmsPage->getId()) {
                $this->_logger->notice(
                    sprintf('Cannot deactivate "%s" page.', $pageIdentifier)
                );
                continue;
            }

            $cmsPage->setIsActive(false);
            $this->_cmsPageRepository->save($cmsPage);
        }
    }
}
