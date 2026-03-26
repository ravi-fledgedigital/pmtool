<?php
namespace OnitsukaTiger\Sales\Controller\Adminhtml\Order\Create;

use Magento\Sales\Controller\Adminhtml\Order\Create\Index as CreateIndex;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\ObjectManagerInterface;
/**
 * Order create index page controller.
 */
class Index
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected ObjectManagerInterface $_objectManager;

    public function __construct(ObjectManagerInterface $objectManager){
        $this->_objectManager = $objectManager;
    }

    /**
     * @param CreateIndex $subject
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundExecute(CreateIndex $subject, \Closure $proceed)
    {

        $storeId = $this->_getSession()->getStoreId();
        $subject->getRequest()->setParams(['store_id'=> $storeId]);

        return $proceed();
    }

    /**
     * @return Quote
     */
    protected function _getSession(): Quote
    {
        return $this->_objectManager->get(Quote::class);
    }

}
