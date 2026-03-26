<?php

namespace Seoulwebdesign\Toast\Model\MessageField;

use Seoulwebdesign\Toast\Model\Message;
use Seoulwebdesign\Toast\Model\MessageFieldAbstract;

class Customer extends MessageFieldAbstract
{

    /**
     * @inheritDoc
     */
    public function getAvailableVariables()
    {
        return [
            ['id' => 'var_customer_name', 'label' => 'Customer Name']
        ];
    }

    /**
     * @inheritDoc
     */
    public function getRefFieldList()
    {
        return [
            Message::CUSTOMER_REGISTERED,
        ];
    }
}
