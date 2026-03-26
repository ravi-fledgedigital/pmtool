<?php

namespace OnitsukaTiger\NetSuiteStoreShipping\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\WebsiteRepository;

class Data extends AbstractHelper
{
    private WebsiteRepository $websiteRepository;

    /**
     * @param WebsiteRepository $websiteRepository
     * @param Context $context
     */
    public function __construct(
        WebsiteRepository $websiteRepository,
        Context $context
    ) {
        $this->websiteRepository = $websiteRepository;
        parent::__construct($context);
    }

    /**
     * @param $code
     * @return mixed|string|null
     * @throws NoSuchEntityException
     */
    public function getWebsiteName($code): mixed
    {
        $websiteRepository = $this->websiteRepository->get($code);
        return $websiteRepository->getName();
    }
}
