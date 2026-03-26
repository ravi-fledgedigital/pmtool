<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace OnitsukaTiger\EmailToWareHouse\Block\Order\Email\Invoice;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class Address
 * @package OnitsukaTiger\EmailToWareHouse\Block\Order\Email\Invoice
 */
class Address extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Directory\Model\Country
     */
    protected $countryModel;

    /**
     * @var \OnitsukaTiger\EmailToWareHouse\Service\Email
     */
    protected $_service;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @param Template\Context $context
     * @param \Magento\Directory\Model\Country $countryModel
     * @param \OnitsukaTiger\EmailToWareHouse\Service\Email $service
     * @param OrderRepositoryInterface $orderRepository
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Directory\Model\Country $countryModel,
        \OnitsukaTiger\EmailToWareHouse\Service\Email $service,
        OrderRepositoryInterface $orderRepository,
        array $data = []
    ) {
        $this->countryModel = $countryModel;
        $this->_service = $service;
        $this->orderRepository = $orderRepository;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve current order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        $order = $this->getData('order');
        $orderId = (int)$this->getData('order_id');
        if ($orderId) {
            $order = $this->orderRepository->get($orderId);
            $this->setData('order', $order);
        }elseif ($order == null) {
            $order = $this->_service->getVariablesData()['order'];
        }
        return $order;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $address
     * @return string
     */
    public function getName($address)
    {
        return $address->getFirstname() . ' ' . $address->getLastname();
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $address
     * @return mixed
     */
    public function getStreet($address)
    {
        return $this->escapeHtml($address->getStreet());
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $address
     * @return string
     */
    public function getProvinceCity($address)
    {
        $provinceCity = '';
        if (!empty($address->getRegion())) {
            $provinceCity .= $address->getRegion() . ', ';
        }
        if (!empty($address->getCity())) {
            $provinceCity .= $address->getCity();
        }
        return $provinceCity;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $address
     * @return string
     */
    public function getCountry($address)
    {
        $country = '';
        if (!empty($address->getCountryId())) {
            $country .= $this->countryModel->load($address->getCountryId())->getName();
        }
        return $country;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $address
     * @return string
     */
    public function getPostCode($address)
    {
        $postCode = '';
        if (!empty($address->getPostCode())) {
            $postCode .= $address->getPostCode();
        }
        return $postCode;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $address
     * @return string
     */
    public function getTelephone($address)
    {
        $telephone = '';
        if (!empty($address->getTelephone())) {
            $telephone .= substr($address->getTelephone(), 0, 2) . '-' . substr($address->getTelephone(), 2);
        }
        return $telephone;
    }

    /**
     * @param \Magento\Sales\Model\Order\Address $address
     * @return string
     */
    public function getEmail($address)
    {
        $email = '';
        if (!empty($address->getEmail())) {
            $email .= $address->getEmail();
        }
        return $email;
    }
}
