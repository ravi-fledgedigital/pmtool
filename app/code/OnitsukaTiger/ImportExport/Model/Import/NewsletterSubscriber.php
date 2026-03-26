<?php
namespace OnitsukaTiger\ImportExport\Model\Import;

use Firebear\ImportExport\Traits\Import\Entity as ImportTrait;

/**
 * Class NewsletterSubscriber
 * @package OnitsukaTiger\ImportExport\Model\Import
 */
class NewsletterSubscriber extends \Firebear\ImportExport\Model\Import\NewsletterSubscriber
{
    use ImportTrait;

    /**
     * Validate status string
     *
     * @param array $rowData
     * @param int $rowNumber
     * @return void
     */
    protected function validateStatus(array $rowData, $rowNumber)
    {
        if (!empty($rowData[self::COL_STATUS])) {
            if (!in_array($rowData[self::COL_STATUS], $this->status)) {
                $this->addRowError(self::ERROR_STATUS_VALUE, $rowNumber);
            }
        }
    }

    /**
     * Validate row data for update behaviour
     *
     * @param array $rowData
     * @param int $rowNumber
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function validateRowForUpdate(array $rowData, $rowNumber)
    {
        $this->validateRowForDelete($rowData, $rowNumber);
        $this->validateStore($rowData, $rowNumber);
        $this->validateStatus($rowData, $rowNumber);
    }

    /**
     * Validate store data
     *
     * @param array $rowData
     * @param int $rowNumber
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function validateStore(array $rowData, $rowNumber)
    {
        if (!$this->storeManager->isSingleStoreMode()) {
            if (empty($rowData[self::COL_STORE_ID])) {
                $this->addRowError(self::ERROR_STORE_ID_IS_EMPTY, $rowNumber);
            } else {
                $store = $this->storeManager->getStore($rowData[self::COL_STORE_ID]);
                if (!$store) {
                    $this->addRowError('Value of Store Id column is invalid', $rowNumber);
                }
            }
        }
    }
}
