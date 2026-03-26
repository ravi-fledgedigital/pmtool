<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Controller\Rating;

use Amasty\Rma\Model\Request\CustomerRequestRepository;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Rate extends \Magento\Framework\App\Action\Action
{
    /**
     * @var CustomerRequestRepository
     */
    private $customerRequestRepository;

    public function __construct(
        CustomerRequestRepository $customerRequestRepository,
        Context $context
    ) {
        parent::__construct($context);
        $this->customerRequestRepository = $customerRequestRepository;
    }

    public function execute()
    {
        /** @var Json $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $rating = (int)$this->getRequest()->getParam('rating');
        if (($hash = $this->getRequest()->getParam('hash'))
            && $rating && $rating > 0 && $rating < 6
        ) {
            $ratingComment = $this->getRequest()->getParam('rating_comment');

            try {
                $this->customerRequestRepository->saveRating($hash, (int)$rating, $ratingComment);
            } catch (\Exception $e) {
                return $response->setData([]);
            }

            return $response->setData(['success' => true]);
        }

        return $response->setData([]);
    }
}
