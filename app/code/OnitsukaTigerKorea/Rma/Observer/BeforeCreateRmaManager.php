<?php
declare(strict_types=1);
namespace OnitsukaTigerKorea\Rma\Observer;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\StatusRepositoryInterface;
use Magento\Framework\App\State;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use OnitsukaTigerKorea\Rma\Helper\Data;

class BeforeCreateRmaManager implements ObserverInterface {

    /**
     * @var StatusRepositoryInterface
     */
    private $statusRepository;

    /**
     * @var Data
     */
    public $helper;

    /**
     * @var State
     */
    public $state;

    /**
     * BeforeCreateRmaManager constructor.
     * @param StatusRepositoryInterface $statusRepository
     * @param Data $helper
     */
    public function __construct(
        \Amasty\Rma\Api\StatusRepositoryInterface $statusRepository,
        Data $helper,
        State $state
    )
    {
        $this->statusRepository = $statusRepository;
        $this->helper = $helper;
        $this->state = $state;
    }

    /**
     * @param Observer $observer
     * @return RequestInterface
     */
    public function execute(Observer $observer): RequestInterface
    {
        /** @var RequestInterface $request */
        $request = $observer->getData('request');
        if($request->getStoreId() == \OnitsukaTiger\Store\Model\Store::KO_KR) {
            $initStatusRmaKorea = $this->helper->getInitialStatusId($request->getStoreId());
            $request->setStatus($initStatusRmaKorea);

            if( $this->state->getAreaCode() == \Magento\Framework\App\Area::AREA_FRONTEND){
                $initResolutionRmaKorea = $this->helper->getInitialResolutionId($request->getStoreId());
                $requestItems = $request->getRequestItems();
                foreach($requestItems as $requestItem){
                    $requestItem->setResolutionId($initResolutionRmaKorea);
                }
            }
        }
        return $request;
    }
}
