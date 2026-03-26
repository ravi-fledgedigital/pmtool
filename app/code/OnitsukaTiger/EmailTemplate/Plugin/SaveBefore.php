<?php
namespace OnitsukaTiger\EmailTemplate\Plugin;

/**
 * Class SaveBefore
 * @package OnitsukaTiger\EmailTemplate\Plugin
 */
class SaveBefore
{
    /**
     * @param \Magento\Email\Model\BackendTemplate $subject
     * @return mixed
     */
    public function beforeSave(\Magento\Email\Model\BackendTemplate $subject)
    {
        // If don't set "is_legacy = 1" $object = null (ex : $order or $invoice or $shipment) because "is_legacy = 0" only array or scalar variables are passed
        // Need to set "is_legacy = 1" to object variable is passed to blocks in created backend template
        $subject->setData('is_legacy', 1);
        return $subject;
    }
}
