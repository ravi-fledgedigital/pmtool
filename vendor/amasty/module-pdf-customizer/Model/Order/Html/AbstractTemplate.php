<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Order\Html;

use Magento\Sales\Model\Order;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

abstract class AbstractTemplate
{
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Amasty\PDFCustom\Model\Template\Factory
     */
    protected $templateFactory;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    protected $addressRenderer;

    /**
     * @var \Amasty\PDFCustom\Model\ConfigProvider
     */
    protected $configProvider;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository
     */
    protected $templateRepository;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    public function __construct(
        \Magento\Payment\Helper\Data $paymentHelper,
        \Amasty\PDFCustom\Model\Template\Factory $templateFactory,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Amasty\PDFCustom\Model\ConfigProvider $configProvider,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Amasty\PDFCustom\Model\ResourceModel\TemplateRepository $templateRepository,
        TimezoneInterface $timezone,
        ResolverInterface $localeResolver,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->templateFactory = $templateFactory;
        $this->addressRenderer = $addressRenderer;
        $this->configProvider = $configProvider;
        $this->orderRepository = $orderRepository;
        $this->templateRepository = $templateRepository;
        $this->localeResolver = $localeResolver;
        $this->timezone = $timezone;
        $this->eventManager = $eventManager;
    }

    /**
     * @param \Magento\Sales\Model\AbstractModel $saleObject
     *
     * @return string
     */
    abstract public function getHtml($saleObject);

    /**
     * Return payment info block as html
     *
     * @param \Magento\Sales\Model\Order $order
     * @return string
     */
    protected function getPaymentHtml(\Magento\Sales\Model\Order $order)
    {
        return $this->paymentHelper->getInfoBlockHtml(
            $order->getPayment(),
            $order->getStoreId()
        );
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string|null
     */
    protected function getFormattedShippingAddress($order)
    {
        return $order->getIsVirtual()
            ? null
            : $this->addressRenderer->format($order->getShippingAddress(), 'html');
    }

    protected function getFormattedDate(?string $date, ?int $storeId): string
    {
        return $this->timezone->formatDateTime(
            $date,
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            $this->localeResolver->emulate($storeId),
            $this->timezone->getConfigTimezone('store', $storeId),
            'yyyy-MM-dd HH:mm:ss'
        );
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return string|null
     */
    protected function getFormattedBillingAddress($order)
    {
        return $this->addressRenderer->format($order->getBillingAddress(), 'html');
    }

    protected function getFormattedOrderHistoryComments(Order $order): string
    {
        $result = '';

        foreach ($order->getAllStatusHistory() as $item) {
            $result .= $item->getComment() . '<br>';
        }

        return $result;
    }
}
