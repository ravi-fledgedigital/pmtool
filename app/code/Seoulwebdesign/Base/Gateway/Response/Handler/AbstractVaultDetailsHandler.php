<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Seoulwebdesign\Base\Gateway\Response\Handler;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Gateway\Helper;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Model\Order\Payment;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractVaultDetailsHandler implements HandlerInterface
{
    /**
     * @var PaymentTokenFactoryInterface
     */
    protected $paymentTokenFactory;

    /**
     * @var OrderPaymentExtensionInterfaceFactory
     */
    protected $paymentExtensionFactory;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * VaultDetailsHandler constructor.
     *
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory
     * @param Json|null $serializer
     */
    public function __construct(
        PaymentTokenFactoryInterface $paymentTokenFactory,
        OrderPaymentExtensionInterfaceFactory $paymentExtensionFactory,
        Json $serializer = null
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentExtensionFactory = $paymentExtensionFactory;
        $this->serializer = $serializer ?: ObjectManager::getInstance()
            ->get(Json::class);
    }

    /**
     * @inheritdoc
     */
    public function handle(array $handlingSubject, array $response)
    {
        $paymentDO = Helper\SubjectReader::readPayment($handlingSubject);
        /** @var Payment $payment */
        //$payment = $handlingSubject['payment']->getPayment();
        $payment = $paymentDO->getPayment();
        if ($payment->getMethod()== $this->getConfigProviderCode()) {
            // add vault payment token entity to extension attributes
            $paymentToken = $this->getVaultPaymentToken($payment);
            if (null !== $paymentToken) {
                $extensionAttributes = $this->getExtensionAttributes($payment);
                $extensionAttributes->setVaultPaymentToken($paymentToken);
            }
        }
    }

    /**
     * @return string
     */
    abstract protected function getConfigProviderCode();

    /**
     * Get vault payment token entity
     *
     * @param Payment $payment
     * @return PaymentTokenInterface|null
     * @throws Exception
     */
    protected function getVaultPaymentToken($payment)
    {
        $paymentToken = $this->paymentTokenFactory->create(PaymentTokenFactoryInterface::TOKEN_TYPE_CREDIT_CARD);
        $paymentToken->setGatewayToken($this->getGatewayToken($payment));
        $paymentToken->setExpiresAt($this->getExpireAt($payment));

        $paymentToken->setTokenDetails($this->convertDetailsToJSON([
            'type' => $this->getCardType($payment),
            'maskedCC' => $this->getLastCc($payment),
            'expirationDate' => $this->getExpirationDate($payment),
            'iconUrl' => $this->getIconUrl($payment)
        ]));

        return $paymentToken;
    }

    /**
     * @param Payment $payment
     * @return string
     */
    abstract protected function getGatewayToken($payment): string;

    /**
     * @param Payment $payment
     * @return string
     */
    abstract protected function getExpireAt($payment): string;


    /**
     * @param Payment $payment
     * @return string
     */
    abstract protected function getCardType($payment): string;


    /**
     * @param Payment $payment
     * @return string
     */
    abstract protected function getLastCc($payment): string;


    /**
     * @param Payment $payment
     * @return string
     */
    abstract protected function getExpirationDate($payment): string;

    /**
     * @param Payment $payment
     * @return string
     */
    abstract protected function getIconUrl($payment): string;

    /**
     * Convert payment token details to JSON
     * @param array $details
     * @return string
     */
    private function convertDetailsToJSON(array $details): string
    {
        $json = $this->serializer->serialize($details);
        return $json ?: '{}';
    }

    /**
     * Get payment extension attributes
     *
     * @param InfoInterface $payment
     * @return OrderPaymentExtensionInterface
     */
    private function getExtensionAttributes(InfoInterface $payment): OrderPaymentExtensionInterface
    {
        $extensionAttributes = $payment->getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->paymentExtensionFactory->create();
            $payment->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }
}
