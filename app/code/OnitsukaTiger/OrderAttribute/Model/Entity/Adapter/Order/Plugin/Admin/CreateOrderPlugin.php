<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/
declare(strict_types=1);

namespace OnitsukaTiger\OrderAttribute\Model\Entity\Adapter\Order\Plugin\Admin;

use OnitsukaTiger\OrderAttribute\Model\Entity\Adapter\Order\Admin\CreateProcessor;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\AdminOrder\Create;

class CreateOrderPlugin
{
    /**
     * @var CreateProcessor
     */
    private $createProcessor;

    public function __construct(
        CreateProcessor $createProcessor
    ) {
        $this->createProcessor = $createProcessor;
    }

    /**
     * @param Create $subject
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    public function beforeImportPostData(Create $subject, array $data): void
    {
        $this->createProcessor->processAttributesDataFromAdminForm(
            $subject->getQuote(),
            $subject->getSession()->getStore(),
            $data
        );
    }
}
