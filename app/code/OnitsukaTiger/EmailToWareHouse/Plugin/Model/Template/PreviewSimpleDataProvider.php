<?php

namespace OnitsukaTiger\EmailToWareHouse\Plugin\Model\Template;

use Amasty\PDFCustom\Model\Template\PreviewSimpleDataProvider as Amastysubject;

class PreviewSimpleDataProvider
{
    /**
     * @var \OnitsukaTiger\EmailToWareHouse\Service\Email
     */
    protected $_service;

    /**
     * @param \OnitsukaTiger\EmailToWareHouse\Service\Email $service
     */
    public function __construct(\OnitsukaTiger\EmailToWareHouse\Service\Email $service) {
        $this->_service = $service;
    }

    /**
     * @param Amastysubject $subject
     * @param $result
     * @return mixed
     */
    public function afterGetVariablesData(Amastysubject $subject, $result)
    {
        $this->_service->setVariablesData($result);
        return $result;
    }

}