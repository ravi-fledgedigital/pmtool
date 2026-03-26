<?php

namespace OnitsukaTiger\Omise\Model\Method;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\ManagerInterface;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;
use Omise\Payment\Model\Config\Cc as Config;
use Psr\Log\LoggerInterface;
use Omise\Payment\Model\Omise;

/**
 * Class Refund
 * @package OnitsukaTiger\Omise\Model\Method
 */
class Refund extends Adapter
{
    const EXPONENT = 2;

    const OMISE_STATUS = 'closed';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Logger for exception details
     *
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Omise\Payment\Model\Omise
     */
    protected $omise;

    /**
     * Refund constructor.
     * @param Omise $omise
     * @param Config $config
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param ValueHandlerPoolInterface $valueHandlerPool
     * @param PaymentDataObjectFactory $paymentDataObjectFactory
     * @param string $code
     * @param string $formBlockType
     * @param string $infoBlockType
     * @param CommandPoolInterface|null $commandPool
     * @param ValidatorPoolInterface|null $validatorPool
     * @param CommandManagerInterface|null $commandExecutor
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Omise $omise,
        Config $config,
        ScopeConfigInterface $scopeConfig,
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        $code,
        $formBlockType,
        $infoBlockType,
        CommandPoolInterface $commandPool = null,
        ValidatorPoolInterface $validatorPool = null,
        CommandManagerInterface $commandExecutor = null,
        LoggerInterface $logger = null
    )
    {
        $omise->defineApiKeys();
        $this->config = $config;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger ?: ObjectManager::getInstance()->get(LoggerInterface::class);
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $code,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger
        );
    }

    /**
     * @return bool
     */
    public function canRefund()
    {
        $paymentMethod = $this->getCode();
        if ($paymentMethod == Config::CODE) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    public function canRefundPartialPerInvoice()
    {
        $paymentMethod = $this->getCode();
        if ($paymentMethod == Config::CODE) {
            return true;
        }
        return false;
    }

    /**
     * @param InfoInterface $payment
     * @param float $amount
     * @return $this|Adapter|\Magento\Payment\Model\MethodInterface|Refund
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function refund(InfoInterface $payment, $amount)
    {
        if (!$this->isEnabled()) {
            return $this;
        }

        $amount = $this->_amountAsInt($amount);
        if ($payment->getOrder()) {
            try {
                $additionalInfo = $payment->getAdditionalInformation();
                if (!empty($additionalInfo) && $additionalInfo['charge_id']) {
                    $charge = \OmiseCharge::retrieve($additionalInfo['charge_id'], $this->config->getPublicKey(), $this->config->getSecretKey());
                    $refund = $charge->refunds()->create(array('amount' => $amount));
                    if ($refund->offsetGet('status') === self::OMISE_STATUS) {
                        return $this;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error(sprintf('Request refund via Omise fail: %s', $e->getMessage()));
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Request refund via Omise fail: %1', $e->getMessage())
                );
            }
        }
        $this->logger->error('No matching order found in Omise to refund. Please visit your Omise and refund the order manually.');
        throw new \Magento\Framework\Exception\LocalizedException(
            __('No matching order found in Omise to refund. Please visit your Omise and refund the order manually.')
        );
    }

    protected function _amountAsInt($amount)
    {
        return round($amount, self::EXPONENT, PHP_ROUND_HALF_EVEN) * pow(10, self::EXPONENT);
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        if ($this->scopeConfig->getValue('refund_online/general/omise_refund_online_enabled')) {
            return true;
        }
        return false;
    }
}
