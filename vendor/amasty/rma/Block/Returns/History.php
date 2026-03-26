<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Block\Returns;

use Amasty\Rma\Api\Data\MessageInterface;
use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\Data\RequestItemInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Amasty\Rma\Controller\RegistryConstants;
use Amasty\Rma\Model\Chat\ResourceModel\Message;
use Amasty\Rma\Model\ConfigProvider;
use Amasty\Rma\Model\Order\OrderItemImage;
use Amasty\Rma\Model\Request\ResourceModel\Collection;
use Amasty\Rma\Model\Request\ResourceModel\CollectionFactory;
use Amasty\Rma\Model\Request\ResourceModel\RequestItem;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;

class History extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Amasty_Rma::account/returns/index.phtml';

    /**
     * @var bool
     */
    private $isGuest;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var OrderItemCollectionFactory
     */
    private $orderItemCollectionFactory;

    /**
     * @var OrderItemImage
     */
    private $orderItemImage;

    public function __construct(
        CollectionFactory $collectionFactory,
        Session $customerSession,
        ProductRepositoryInterface $productRepository,
        ConfigProvider $configProvider,
        Registry $registry,
        StatusRepositoryInterface $statusRepository,
        OrderItemImage $orderItemImage,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->isGuest = !empty($data['isGuest']);
        $this->collectionFactory = $collectionFactory;
        $this->customerSession = $customerSession;
        $this->registry = $registry;
        $this->productRepository = $productRepository;
        $this->statusRepository = $statusRepository;
        $this->configProvider = $configProvider;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->orderItemImage = $orderItemImage;
    }

    /**
     * Return Pager html for all pages
     *
     * @return string
     */
    public function getPagerHtml()
    {
        $pagerBlock = $this->getChildBlock('amasty_rma_pager');

        if ($pagerBlock instanceof \Magento\Framework\DataObject) {

            $pagerBlock->setUseContainer(
                false
            )->setFrameLength(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setJump(
                $this->_scopeConfig->getValue(
                    'design/pagination/pagination_frame_skip',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                )
            )->setLimit(
                $this->getLimit()
            )->setCollection(
                $this->getCollection()
            );

            return $pagerBlock->toHtml();
        }

        return '';
    }

    public function getCollection()
    {
        if (!$this->collection) {
            $this->collection = $this->collectionFactory->create();

            $this->collection->join(
                'sales_order',
                'main_table.' . RequestInterface::ORDER_ID . ' = sales_order.' . OrderInterface::ENTITY_ID,
                [OrderInterface::INCREMENT_ID]
            );
            if ($this->isGuest) {
                $this->collection->addFieldToFilter(
                    'sales_order.' . OrderInterface::CUSTOMER_EMAIL,
                    $this->registry->registry(RegistryConstants::GUEST_DATA)['email']
                )->join(
                    'sales_order_address',
                    'main_table.order_id = sales_order_address.parent_id'
                    . ' and sales_order_address.address_type = \'billing\'',
                    []
                )->addFieldToFilter(
                    'sales_order_address.' . OrderAddressInterface::LASTNAME,
                    $this->registry->registry(RegistryConstants::GUEST_DATA)['lastname']
                );
                if ($this->configProvider->isRmaRestrictedByStore()) {
                    $this->collection->addFieldToFilter(
                        'sales_order.' . OrderInterface::STORE_ID,
                        $this->_storeManager->getStore()->getId()
                    );
                }
            } else {
                $this->collection->addFieldToFilter(
                    'main_table.' . RequestInterface::CUSTOMER_ID,
                    (int)$this->customerSession->getCustomerId()
                );
            }
            $this->collection->join(
                RequestItem::TABLE_NAME,
                'main_table.' . RequestInterface::REQUEST_ID
                . ' = ' . RequestItem::TABLE_NAME . '.' . RequestItemInterface::REQUEST_ID,
                [new \Zend_Db_Expr(
                    'sum(' . RequestItem::TABLE_NAME . '.' . RequestItemInterface::QTY . ') as qty'
                )]
            )->join(
                'sales_order_item',
                RequestItem::TABLE_NAME . '.' . RequestItemInterface::ORDER_ITEM_ID
                . '=' . 'sales_order_item.' . OrderItemInterface::ITEM_ID,
                ['sales_order_item.item_id']
            );

            $this->collection->getSelect()->group('main_table.' . RequestInterface::REQUEST_ID);

            $this->collection->getSelect()->columns([
                new \Zend_Db_Expr(
                    '(select count(*) from ' . $this->collection->getTable(Message::TABLE_NAME) .' as `mess`'
                        .' where `mess`.`' . MessageInterface::REQUEST_ID
                        . '` = `main_table`.`' . RequestInterface::REQUEST_ID . '`'
                        . ' and `mess`.`' . MessageInterface::IS_READ . '` = 0'
                        . ' and (`mess`.`' . MessageInterface::IS_MANAGER .'` = 1'
                        . ' or `mess`.`' . MessageInterface::IS_SYSTEM . '` = 1)) as `new_message`'
                )
            ]);
//            $this->collection->getSelect()->joinLeft(
//                ['is_read' => $this->collection->getTable(Message::TABLE_NAME)],
//                '`main_table`.`' . RequestInterface::REQUEST_ID
//                    . '` = `is_read`.`' . MessageInterface::REQUEST_ID . '`'
//                    . ' and `is_read`.`is_read` = 1'
//                    . ' and (`is_read`.`' . MessageInterface::IS_MANAGER . '` = 1'
//                    . ' or `is_read`.`' . MessageInterface::IS_SYSTEM . '` = 1)',
//                MessageInterface::MESSAGE_ID
//            );
            $this->collection->setOrder('main_table.' . RequestInterface::REQUEST_ID, 'DESC');
            if ($this->getLimit()) {
                $curPage = (int)$this->getRequest()->getParam('p', 1);
                $this->collection->setCurPage($curPage);
                $this->collection->setPageSize($this->getLimit());
            }
        }

        return $this->collection;
    }

    public function getLimit()
    {
        return (int)$this->getRequest()->getParam('limit', 10);
    }

    public function isChatEnabled()
    {
        return $this->configProvider->isChatEnabled();
    }

    public function getProceedData()
    {
        $data = $this->getCollection()->getData();
        $statuses = $this->statusRepository->getStatusesByStoreId(
            $this->_storeManager->getStore()->getId(),
            false,
            true
        );
        foreach ($data as &$item) {
            $item['product_url'] = $this->orderItemImage->getUrl($item['item_id'], 'product_base_image');
            $item['view_url'] = ($this->isGuest)
                ? $this->_urlBuilder->getUrl(
                    $this->configProvider->getUrlPrefix() .'/guest/view',
                    ['request' => $item[RequestInterface::URL_HASH]]
                )
                : $this->_urlBuilder->getUrl(
                    $this->configProvider->getUrlPrefix() . '/account/view',
                    ['request' => $item[RequestInterface::REQUEST_ID]]
                );
            if (!empty($statuses[$item[RequestInterface::STATUS]])) {
                $item['status_label'] = $statuses[$item[RequestInterface::STATUS]]->getLabel();
                if ($statuses[$item[RequestInterface::STATUS]]->getColor() &&
                    preg_match(
                        '/^#[0-9a-fA-F]{3,6}$/',
                        $statuses[$item[RequestInterface::STATUS]]->getColor()
                    )
                ) {
                    $item['status_color'] = $statuses[$item[RequestInterface::STATUS]]->getColor();
                }
            }
        }

        return $data;
    }
}
