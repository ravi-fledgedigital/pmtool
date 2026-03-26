<?php
namespace OnitsukaTiger\Checkout\Observer;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager;
use Magento\PageCache\Model\Config;

/**
 * Customer logged in observer
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class CustomerLoggedInObserver implements ObserverInterface
{
    private \Magento\Quote\Api\CartRepositoryInterface $quoteRepository;
    private CheckoutSession $session;
    private \Magento\Quote\Model\Quote\ItemFactory $quoteItem;
    private GroupRepositoryInterface $groupRepository;
    private Session $customerSession;
    private Manager $moduleManager;
    private Config $cacheConfig;

    /**
     * @param GroupRepositoryInterface $groupRepository
     * @param Session $customerSession
     * @param Manager $moduleManager
     * @param Config $cacheConfig
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param CheckoutSession $session
     * @param \Magento\Quote\Model\Quote\ItemFactory $quoteItem
     */
    public function __construct(
        GroupRepositoryInterface $groupRepository,
        Session $customerSession,
        Manager $moduleManager,
        Config $cacheConfig,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        CheckoutSession $session,
        \Magento\Quote\Model\Quote\ItemFactory $quoteItem

    ) {
        $this->quoteRepository = $quoteRepository;
        $this->session = $session;
        $this->quoteItem = $quoteItem;
        $this->groupRepository = $groupRepository;
        $this->customerSession = $customerSession;
        $this->moduleManager = $moduleManager;
        $this->cacheConfig = $cacheConfig;
    }

    /**
     * Execute.
     *
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute(Observer $observer)
    {
        if($this->session->getQuote()->getId()) {
            $quote = $this->quoteRepository->get($this->session->getQuote()->getId());
            $quoteAllItems = $quote->getAllItems();
            $quoteItemsAvailable = $quote->getItems();
            $itemIdsAvailable = [];
            foreach ($quoteItemsAvailable as $itemAvail) {
                $itemIdsAvailable[] = $itemAvail->getId();
            }
            foreach ($quoteAllItems as $item) {
                $arrayDiff = array_diff([$item->getParentItemId()],$itemIdsAvailable);
                if($item->getParentItemId() != null && $arrayDiff ) {
                    $quoteItem = $this->quoteItem->create()->load($item->getParentItemId());
                    $quoteItem->delete();
                    $quote->collectTotals();
                }
            }
            $currentQuote= $this->session->getQuote();
            $shippingAddress = $currentQuote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
            $currentQuote->setTriggerRecollect(1);
            $currentQuote->collectTotals();
            $currentQuote->save($currentQuote);
        }
    }
}
