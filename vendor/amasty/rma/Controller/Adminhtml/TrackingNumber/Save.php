<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Adminhtml\TrackingNumber;

use Amasty\Rma\Api\RequestRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    public function __construct(
        RequestRepositoryInterface $requestRepository,
        Context $context
    ) {
        parent::__construct($context);
        $this->requestRepository = $requestRepository;
    }

    public function execute()
    {
        /** @var Json $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $number = $this->getRequest()->getParam('number');
        $code = $this->getRequest()->getParam('code');
        if (($hash = $this->getRequest()->getParam('hash')) && $number && $code) {
            $tracking = $this->requestRepository->getEmptyTrackingModel();

            $tracking->setTrackingCode($code)
                ->setTrackingNumber($number)
                ->setIsCustomer(false)
                ->setRequestId($this->requestRepository->getByHash($hash)->getRequestId());
            try {
                $this->requestRepository->saveTracking($tracking);
            } catch (\Exception $e) {
                return $response->setData([]);
            }

            return $response->setData(['success' => true, 'id' => $tracking->getTrackingId()]);
        }

        return $response->setData([]);
    }
}
