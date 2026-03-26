<?php

namespace OnitsukaTigerKorea\RmaAddress\Plugin\DataProvider;

use Amasty\Rma\Api\Data\RequestInterface;
use Amasty\Rma\Api\RequestRepositoryInterface;
use OnitsukaTigerKorea\RmaAddress\Helper\Data;

class FormPlugin
{
    /**
     * @var RequestRepositoryInterface
     */
    private $requestRepository;

    /**
     * @var Data
     */
    private $addressHelper;


    /**
     * @var OnitsukaTigerKorea\RmaAddress\Helper\Data
     */
    private $dataHelper;

    public function __construct(
        RequestRepositoryInterface $requestRepository,
        \OnitsukaTigerKorea\RmaAddress\Helper\Data $dataHelper,
        Data $addressHelper
    ) {
        $this->requestRepository = $requestRepository;
        $this->dataHelper = $dataHelper;
        $this->addressHelper = $addressHelper;
    }

    public function afterGetData(
        \Amasty\Rma\Model\Request\DataProvider\Form $subject,
        $result
    ) {
        $request = $this->requestRepository->getById($result['items'][0][RequestInterface::REQUEST_ID]);
        if($this->dataHelper->enableShowAddressRMA($request->getStoreId())){
            $address = $this->addressHelper->rmaAddressToHtml($request);
            $result[$request->getRequestId()]['rma'] = [
                'address' => $address == '' ? '' : $address
            ];
        }

        return $result;
    }

}
