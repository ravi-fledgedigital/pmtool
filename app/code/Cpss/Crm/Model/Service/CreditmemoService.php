<?php

namespace Cpss\Crm\Model\Service;

/**
 * Class CreditmemoService
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreditmemoService extends \Magento\Sales\Model\Service\CreditmemoService
{
    /**
     * @var \Magento\Sales\Api\CreditmemoRepositoryInterface
     */
    protected $creditmemoRepository;

    /**
     * @var \Magento\Sales\Api\CreditmemoCommentRepositoryInterface
     */
    protected $commentRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $filterBuilder;

    /**
     * @var \Magento\Sales\Model\Order\CreditmemoNotifier
     */
    protected $creditmemoNotifier;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var \Magento\Sales\Model\Order\RefundAdapterInterface
     */
    private $refundAdapter;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var \Magento\Sales\Api\InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @param \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository
     * @param \Magento\Sales\Api\CreditmemoCommentRepositoryInterface $creditmemoCommentRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Magento\Sales\Model\Order\CreditmemoNotifier $creditmemoNotifier
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \Magento\Sales\Api\CreditmemoRepositoryInterface $creditmemoRepository,
        \Magento\Sales\Api\CreditmemoCommentRepositoryInterface $creditmemoCommentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Sales\Model\Order\CreditmemoNotifier $creditmemoNotifier,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->creditmemoRepository = $creditmemoRepository;
        $this->commentRepository = $creditmemoCommentRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->filterBuilder = $filterBuilder;
        $this->creditmemoNotifier = $creditmemoNotifier;
        $this->priceCurrency = $priceCurrency;
        $this->eventManager = $eventManager;
        parent::__construct($creditmemoRepository, $creditmemoCommentRepository, $searchCriteriaBuilder, $filterBuilder, $creditmemoNotifier, $priceCurrency, $eventManager);
    }

    /**
     * Validates if credit memo is available for refund.
     *
     * @param \Magento\Sales\Api\Data\CreditmemoInterface $creditmemo
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function validateForRefund(\Magento\Sales\Api\Data\CreditmemoInterface $creditmemo)
    {
        if ($creditmemo->getId() && $creditmemo->getState() != \Magento\Sales\Model\Order\Creditmemo::STATE_OPEN) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('We cannot register an existing credit memo.')
            );
        }

        $baseOrderRefund = $this->priceCurrency->round(
            $creditmemo->getOrder()->getBaseTotalRefunded() + $creditmemo->getBaseGrandTotal()
        );
        
        if (round($baseOrderRefund) > round($this->priceCurrency->round($creditmemo->getOrder()->getBaseTotalPaid()))) {
            $baseAvailableRefund = $creditmemo->getOrder()->getBaseTotalPaid()
                - $creditmemo->getOrder()->getBaseTotalRefunded();

            throw new \Magento\Framework\Exception\LocalizedException(
                __(
                    'The most money available to refund is %1.',
                    $creditmemo->getOrder()->getBaseCurrency()->formatTxt($baseAvailableRefund)
                )
            );
        }
        return true;
    }
}
