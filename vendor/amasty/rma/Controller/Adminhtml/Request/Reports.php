<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\Request;

use Amasty\Rma\Model\OptionSource\Manager;
use Amasty\Rma\Model\OptionSource\State;
use Amasty\Rma\Model\Request\ResourceModel\Request;
use Amasty\Rma\Model\Request\ResourceModel\RequestItem;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Store\Model\StoreManagerInterface;

class Reports extends Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Amasty_Rma::manage';

    /**
     * @var Request
     */
    private $requestResource;

    /**
     * @var State
     */
    private $state;

    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var RequestItem
     */
    private $requestItemResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Request $requestResource,
        RequestItem $requestItemResource,
        State $state,
        Manager $manager,
        Action\Context $context,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->requestResource = $requestResource;
        $this->state = $state;
        $this->manager = $manager;
        $this->requestItemResource = $requestItemResource;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $totalsByState = [];
        $states = $this->state->toArray();
        foreach ($this->requestResource->getTotalByState() as $key => $value) {
            $totalsByState[] = [
                'name' => (string)(isset($states[$key]) ? $states[$key] : ''),
                'total' => (int)$value
            ];
        }

        $managersTotal = [];
        $managers = $this->manager->toArray();
        foreach ($this->requestResource->getManagerRequestsCount() as $key => $value) {
            $managersTotal[] = [
                'name' => (string)(isset($managers[$key]) ? $managers[$key] : ''),
                'total' => (int)$value
            ];
        }

        $data = [
            'totalByState' => $totalsByState,
            'managersTotal' => $managersTotal,
            'topReasons' => $this->requestItemResource->getTop5Reasons(),
            'itemsBasePrice' => $this->requestItemResource->getReturnItemsBasePrice(),
            'basePriceFormatted' => $this->storeManager->getStore()
                ->getBaseCurrency()
                ->formatTxt($this->requestItemResource->getReturnItemsBasePrice())
        ];

        return $result->setData($data);
    }
}
