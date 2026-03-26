<?php
/**
* @author OnitsukaTiger Team
* @copyright Copyright (c) 2022 OnitsukaTiger (https://www.onitsukatiger.com)
* @package Custom Checkout Fields for Magento 2
*/

namespace OnitsukaTiger\OrderAttribute\Block\Order;

use OnitsukaTiger\OrderAttribute\Model\Value\Metadata\Form;

class PrintOrder extends Attributes
{
    /**
     * Return Checkout Form instance
     *
     * @param \OnitsukaTiger\OrderAttribute\Model\Entity\EntityData $entity
     * @return Form
     */
    protected function createEntityForm($entity)
    {
        /** @var Form $formProcessor */
        $formProcessor = $this->metadataFormFactory->create();
        $formProcessor->setFormCode('frontend_order_print')
            ->setEntity($entity)
            ->setStore($this->getOrder()->getStore());

        return $formProcessor;
    }
}
