<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\TrackingNumber;

use Amasty\Rma\Api\CustomerRequestRepositoryInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Remove extends \Magento\Framework\App\Action\Action
{
    /**
     * @var CustomerRequestRepositoryInterface
     */
    private $requestRepository;

    public function __construct(
        CustomerRequestRepositoryInterface $requestRepository,
        Context $context
    ) {
        parent::__construct($context);
        $this->requestRepository = $requestRepository;
    }

    public function execute()
    {
        /** @var Json $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $trackingId = $this->getRequest()->getParam('id');
        if (($hash = $this->getRequest()->getParam('hash')) && $trackingId) {
            try {
                $this->requestRepository->removeTracking($hash, $trackingId);
            } catch (\Exception $e) {
                return $response->setData([]);
            }

            return $response->setData(['success' => true]);
        }

        return $response->setData([]);
    }
}
