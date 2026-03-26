<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Reward\Test\Unit\Model;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\CurrencyInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Registry;
use Magento\Reward\Helper\Customer;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\ActionFactory;
use Magento\Reward\Model\Reward;
use Magento\Reward\Model\Reward\HistoryFactory;
use Magento\Reward\Model\Reward\RateFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.Superglobals)
 */
class RewardTest extends TestCase
{
    /**
     * @var Context|Context&MockObject|MockObject
     */
    private Context $context;
    /**
     * @var Registry|Registry&MockObject|MockObject
     */
    private Registry $registry;
    /**
     * @var Customer|Customer&MockObject|MockObject
     */
    private Customer $rewardCustomer;
    /**
     * @var Data|Data&MockObject|MockObject
     */
    private Data $rewardData;
    /**
     * @var StoreManagerInterface|StoreManagerInterface&MockObject|MockObject
     */
    private StoreManagerInterface $storeManager;
    /**
     * @var CurrencyInterface|CurrencyInterface&MockObject|MockObject
     */
    private CurrencyInterface $localeCurrency;
    /**
     * @var ActionFactory|ActionFactory&MockObject|MockObject
     */
    private ActionFactory $actionFactory;
    /**
     * @var HistoryFactory|HistoryFactory&MockObject|MockObject
     */
    private HistoryFactory $historyFactory;
    /**
     * @var RateFactory|RateFactory&MockObject|MockObject
     */
    private RateFactory $rateFactory;
    /**
     * @var TransportBuilder|TransportBuilder&MockObject|MockObject
     */
    private TransportBuilder $transportBuilder;
    /**
     * @var ScopeConfigInterface|ScopeConfigInterface&MockObject|MockObject
     */
    private ScopeConfigInterface $scopeConfig;
    /**
     * @var CustomerRepositoryInterface|CustomerRepositoryInterface&MockObject|MockObject
     */
    private CustomerRepositoryInterface $customerRepository;
    /**
     * @var Reward
     */
    private Reward $reward;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->createMock(Context::class);
        $this->registry = $this->createMock(Registry::class);
        $this->rewardCustomer = $this->createMock(Customer::class);
        $this->rewardData = $this->createMock(Data::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->localeCurrency = $this->createMock(CurrencyInterface::class);
        $this->actionFactory = $this->createMock(ActionFactory::class);
        $this->historyFactory = $this->createMock(HistoryFactory::class);
        $this->rateFactory = $this->createMock(RateFactory::class);
        $this->transportBuilder = $this->createMock(TransportBuilder::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
    }

    /**
     * @return void
     */
    public function testSendBalanceUpdateNotificationFrom(): void
    {
        $history = $this->getMockBuilder(\Magento\Reward\Model\Reward\History::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCurrencyDelta', 'getComment'])
            ->onlyMethods(['getMessage'])
            ->getMock();
        $history->expects($this->once())->method('getMessage')->willReturn('');

        $this->transportBuilder->expects($this->once())->method('setTemplateIdentifier')
            ->willReturn($this->transportBuilder);
        $this->transportBuilder->expects($this->once())->method('setTemplateOptions')
            ->willReturn($this->transportBuilder);
        $this->transportBuilder->expects($this->once())->method('setTemplateVars')
            ->willReturn($this->transportBuilder);
        $this->transportBuilder->expects($this->once())->method('setFromByScope')
            ->willReturn($this->transportBuilder);
        $this->transportBuilder->expects($this->once())->method('getTransport')
            ->willReturn($this->getMockForAbstractClass(TransportInterface::class));

        $store = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->addMethods(['getStoreId'])
            ->getMockForAbstractClass();
        $store->expects($this->any())->method('getId')
            ->willReturn(1);

        $this->storeManager->expects($this->once())->method('getStore')->willReturn($store);
        $this->rewardData->expects($this->any())->method('formatAmount')->willReturn(0);
        $resourceModel = $this->createMock(AbstractDb::class);
        $resourceModel->expects($this->any())->method('getIdFieldName')->willReturn('id');
        $customer = $this->createMock(\Magento\Customer\Model\Customer::class);
        $customer->expects($this->once())
            ->method('getData')
            ->with('reward_update_notification')
            ->willReturn(true);

        $this->reward = new Reward(
            $this->context,
            $this->registry,
            $this->rewardCustomer,
            $this->rewardData,
            $this->storeManager,
            $this->localeCurrency,
            $this->actionFactory,
            $this->historyFactory,
            $this->rateFactory,
            $this->transportBuilder,
            $this->scopeConfig,
            $this->customerRepository,
            $resourceModel,
            null,
            [
                'points_delta' => 1,
                'currency_amount' => 1,
                'history' => $history,
                'message' => ''
            ]
        );
        $this->reward->setCustomer($customer);
        $this->reward->sendBalanceUpdateNotification();
    }
}
