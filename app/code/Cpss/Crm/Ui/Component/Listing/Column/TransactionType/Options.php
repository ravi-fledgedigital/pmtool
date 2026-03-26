<?php
namespace Cpss\Crm\Ui\Component\Listing\Column\TransactionType;

use Magento\Framework\Data\OptionSourceInterface;
use Cpss\Crm\Model\Shop\Config\Param;

class Options implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $transactionType = Param::TRANSACTION_TYPE_VALUES;
        foreach($transactionType as $value => $label) {
            $label = ($value == 1) ? 'Complete' : 'Closed';
            $data['value'] = $value;
            $data['label'] = __( $label);
            $options[] = $data;
        }

        return $options;
    }
}