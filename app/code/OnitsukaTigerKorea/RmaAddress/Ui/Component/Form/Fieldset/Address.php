<?php
declare(strict_types=1);

namespace OnitsukaTigerKorea\RmaAddress\Ui\Component\Form\Fieldset;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\StoreRepository;

/**
 *
 * @package OnitsukaTigerKorea\Customer\Ui\Component\Form\Field
 */
class Address extends \Magento\Ui\Component\Form\Fieldset
{
    /**
     * Address Helper
     *
     * @var Data
     */
    private $dataHelper;


    /**
     * @var StoreRepository
     */
    private $storeRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RequestInterface
     */
    private  $request;

    /**
     * NewAddress constructor.
     * @param ContextInterface $context
     * @param RequestInterface $request
     * @param \OnitsukaTigerKorea\RmaAddress\Helper\Data $dataHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param StoreRepository $StoreRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        RequestInterface $request,
        \OnitsukaTigerKorea\RmaAddress\Helper\Data $dataHelper,
        OrderRepositoryInterface $orderRepository,
        StoreRepository $StoreRepository,
        array $components = [],
        array $data = []
    )
    {
        $this->request = $request;
        $this->dataHelper = $dataHelper;
        $this->orderRepository = $orderRepository;
        $this->storeRepository = $StoreRepository;
        parent::__construct($context,  $components, $data);
    }

    /**
     * Prepare component configuration
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepare()
    {
        parent::prepare();
        $orderId = $this->request->getParam('order_id');

        if ($orderId) {
            $order = $this->orderRepository->get($orderId);
            if(!$this->dataHelper->enableShowAddressRMA($order->getStoreId())){
                $currentConfig = $this->getData('config');
                $currentConfig['visible'] = false;

                $this->setData('config', $currentConfig);
            }
        }
    }

}
