<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package PDF Customizer for Magento 2
 */

namespace Amasty\PDFCustom\Model\Template;

use Magento\OfflinePayments\Model\Checkmo;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

class PreviewSimpleDataProvider
{
    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    private $addressRenderer;

    /**
     * @var \Magento\Payment\Helper\Data
     */
    private $paymentHelper;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var \Magento\Sales\Model\Order\InvoiceFactory
     */
    private $invoiceFactory;

    /**
     * @var \Magento\Sales\Model\Order\ItemFactory
     */
    private $itemFactory;

    /**
     * @var \Magento\Sales\Model\Order\AddressFactory
     */
    private $addressFactory;

    /**
     * @var \Magento\Sales\Model\Order\PaymentFactory
     */
    private $paymentFactory;

    /**
     * @var \Magento\Sales\Model\Convert\Order
     */
    private $orderConvert;

    /**
     * @var \Magento\Sales\Model\Order
     */
    private $order = null;

    /**
     * @var \Magento\Sales\Model\Order\Item
     */
    private $orderItem = null;

    /**
     * @var \Magento\Sales\Model\Order\Payment
     */
    private $payment = null;

    /**
     * @var \Magento\Sales\Model\Order\Invoice
     */
    private $invoice = null;

    /**
     * @var \Magento\Sales\Model\Order\Creditmemo
     */
    private $creditmemo = null;

    /**
     * @var \Magento\Sales\Model\Order\Shipment
     */
    private $shipment = null;

    public function __construct(
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Sales\Model\Order\InvoiceFactory $invoiceFactory,
        \Magento\Sales\Model\Order\ItemFactory $itemFactory,
        \Magento\Sales\Model\Order\AddressFactory $addressFactory,
        \Magento\Sales\Model\Order\PaymentFactory $paymentFactory,
        \Magento\Sales\Model\Convert\Order $orderConvert
    ) {
        $this->addressRenderer = $addressRenderer;
        $this->paymentHelper = $paymentHelper;
        $this->orderFactory = $orderFactory;
        $this->invoiceFactory = $invoiceFactory;
        $this->itemFactory = $itemFactory;
        $this->addressFactory = $addressFactory;
        $this->paymentFactory = $paymentFactory;
        $this->orderConvert = $orderConvert;
    }

    public function getVariablesData()
    {
        $order = $this->getOrder();
        return [
            'order' => $order,
            'order_id' => $order->getId(),
            'invoice' => $this->getInvoice(),
            'invoice_id' => $this->getInvoice()->getId(),
            'creditmemo' => $this->getCreditmemo(),
            'creditmemo_id' => $this->getCreditmemo()->getId(),
            'shipment' => $this->getShipment(),
            'billing' => $order->getBillingAddress(),
            'payment_html' => $this->paymentHelper->getInfoBlock($order->getPayment())
                ->setArea(\Magento\Framework\App\Area::AREA_FRONTEND)
                ->setIsSecureMode(true)
                ->toHtml(),
            'formattedShippingAddress' => $this->addressRenderer->format($order->getShippingAddress(), 'html'),
            'formattedBillingAddress' => $this->addressRenderer->format($order->getBillingAddress(), 'html'),
        ];
    }

    /**
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if ($this->order === null) {
            /** @var \Magento\Sales\Model\Order $order */
            $this->order = $this->orderFactory->create(['data' => [
                OrderInterface::INCREMENT_ID => '000000000',
                OrderInterface::CUSTOMER_FIRSTNAME => 'Some',
                OrderInterface::CUSTOMER_LASTNAME => 'Body',
                OrderInterface::BASE_GRAND_TOTAL => 199.99,
                OrderInterface::GRAND_TOTAL => 199.99,
                OrderInterface::SUBTOTAL => 199.99,
                OrderInterface::BASE_SUBTOTAL => 199.99,
                OrderInterface::SUBTOTAL_REFUNDED => 199.99,
                OrderInterface::BASE_SUBTOTAL_REFUNDED => 199.99,
                OrderInterface::TOTAL_REFUNDED => 199.99,
                OrderInterface::BASE_TOTAL_REFUNDED => 199.99,
                OrderInterface::SHIPPING_DESCRIPTION => 'Carrier - Method'
            ]]);
            $item = $this->getOrderItem();
            $item->setOrder($this->order);

            $this->order->setItems([$item]);
            $this->order->addAddress($this->createAddress('billing'));
            $this->order->addAddress($this->createAddress('shipping'));
            $this->order->setPayment($this->getPayment());
        }

        return $this->order;
    }

    /**
     * @return \Magento\Sales\Model\Order\Invoice
     */
    public function getInvoice()
    {
        if ($this->invoice === null) {
            $this->invoice = $this->orderConvert->toInvoice($this->getOrder())
                ->addData([
                    InvoiceInterface::INCREMENT_ID => '000000000',
                    InvoiceInterface::BASE_GRAND_TOTAL => 199.99,
                    InvoiceInterface::GRAND_TOTAL => 199.99,
                    InvoiceInterface::SUBTOTAL => 199.99,
                    InvoiceInterface::BASE_SUBTOTAL => 199.99,
                    InvoiceInterface::BASE_TOTAL_REFUNDED => 199.99,
                    InvoiceInterface::SUBTOTAL_INCL_TAX => 199.99,
                ]);
            $this->invoice->addItem($this->orderConvert->itemToInvoiceItem($this->getOrderItem())->setQty(1));
        }

        return $this->invoice;
    }

    /**
     * @return \Magento\Sales\Model\Order\Creditmemo
     */
    public function getCreditmemo()
    {
        if ($this->creditmemo === null) {
            $this->creditmemo = $this->orderConvert->toCreditmemo($this->getOrder())
                ->addData([
                    CreditmemoInterface::INCREMENT_ID => '000000000',
                    CreditmemoInterface::BASE_GRAND_TOTAL => 199.99,
                    CreditmemoInterface::GRAND_TOTAL => 199.99,
                    CreditmemoInterface::SUBTOTAL => 199.99,
                    CreditmemoInterface::BASE_SUBTOTAL => 199.99,
                    CreditmemoInterface::SUBTOTAL_INCL_TAX => 199.99,
                ]);
            $this->creditmemo->addItem(
                $this->orderConvert->itemToCreditmemoItem($this->getOrderItem())
                    ->setQty(1)
            );
        }

        return $this->creditmemo;
    }

    /**
     * @return \Magento\Sales\Model\Order\Shipment
     */
    public function getShipment()
    {
        if ($this->shipment === null) {
            $this->shipment = $this->orderConvert->toShipment($this->getOrder())
                ->addData([
                    ShipmentInterface::INCREMENT_ID => '000000000',
                ]);
            $this->shipment->addItem(
                $this->orderConvert->itemToShipmentItem($this->getOrderItem())
                    ->setQty(1)
            );
        }

        return $this->shipment;
    }

    /**
     * @return \Magento\Sales\Model\Order\Item
     */
    protected function getOrderItem()
    {
        if ($this->orderItem === null) {
            $this->orderItem = $this->itemFactory->create(
                [
                    'data' => [
                        OrderItemInterface::NAME => 'Test item',
                        OrderItemInterface::SKU => 'SKU_00',
                        OrderItemInterface::DESCRIPTION => 'test product description',
                        OrderItemInterface::BASE_ROW_TOTAL => 199.99,
                        OrderItemInterface::ROW_TOTAL => 199.99,
                        OrderItemInterface::PRICE => 199.99,
                        OrderItemInterface::BASE_PRICE => 199.99,
                        OrderItemInterface::BASE_COST => 199.99,
                        OrderItemInterface::QTY_ORDERED => 1,
                        OrderItemInterface::QTY_INVOICED => 1,
                        OrderItemInterface::QTY_SHIPPED => 1,
                        OrderItemInterface::QTY_REFUNDED => 1,
                    ]
                ]
            );
        }

        return $this->orderItem;
    }

    /**
     * @param string $type
     *
     * @return \Magento\Sales\Model\Order\Address
     */
    protected function createAddress($type = 'billing')
    {
        return $this->addressFactory->create(['data' => [
            OrderAddressInterface::ADDRESS_TYPE => $type,
            OrderAddressInterface::CITY => 'City Value',
            OrderAddressInterface::COMPANY => 'Company Value',
            OrderAddressInterface::EMAIL => 'email@example.com',
            OrderAddressInterface::FAX => '123456',
            OrderAddressInterface::FIRSTNAME => 'Some',
            OrderAddressInterface::LASTNAME => 'Body',
            OrderAddressInterface::POSTCODE => 'POSTCODE',
            OrderAddressInterface::STREET => 'street value ' . $type,
            OrderAddressInterface::TELEPHONE => '12345',
            OrderAddressInterface::COUNTRY_ID => 'US',
            OrderAddressInterface::REGION => 'Region value',
            OrderAddressInterface::REGION_ID => '12'
        ]]);
    }

    /**
     * @return \Magento\Sales\Model\Order\Payment
     */
    protected function getPayment()
    {
        if ($this->payment === null) {
            $this->payment = $this->paymentFactory->create(
                [
                    'data' => [
                        OrderPaymentInterface::AMOUNT_ORDERED => 199.99,
                        OrderPaymentInterface::AMOUNT_AUTHORIZED => 199.99,
                        OrderPaymentInterface::AMOUNT_PAID => 199.99,
                        OrderPaymentInterface::AMOUNT_REFUNDED => 199.99,
                        OrderPaymentInterface::BASE_AMOUNT_ORDERED => 199.99,
                        OrderPaymentInterface::BASE_AMOUNT_AUTHORIZED => 199.99,
                        OrderPaymentInterface::BASE_AMOUNT_PAID => 199.99,
                        OrderPaymentInterface::BASE_AMOUNT_REFUNDED => 199.99,
                        OrderPaymentInterface::METHOD => Checkmo::PAYMENT_METHOD_CHECKMO_CODE,
                    ]
                ]
            );
        }

        return $this->payment;
    }
}
