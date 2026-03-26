<?php

namespace Cpss\Crm\Model\Export;

class MetadataProvider extends \Magento\Ui\Model\Export\MetadataProvider
{

    /**
     * Returns row data
     *
     * @param \Magento\Framework\Api\Search\DocumentInterface $document
     * @param array $fields
     * @param array $options
     *
     * @return string[]
     */
    public function getRowData(\Magento\Framework\Api\Search\DocumentInterface $document, $fields, $options): array
    {
        $row = [];
        foreach ($fields as $column) {
            if (isset($options[$column])) {
                $key = $document->getCustomAttribute($column)->getValue();

                if (isset($options[$column][$key])) {
                    $row[] = $options[$column][$key];
                } else {
                    $row[] = $key;
                }
            } else {
                if ($column == "purchase_date") {
                    $purchaseDate = $document->getCustomAttribute($column)->getValue();
                    if (!$purchaseDate) {
                        $row[] = "";
                    } else {
                        $date = $this->localeDate->date(new \DateTime($purchaseDate));
                        $row[] = $date->format('Y-m-d H:i:s');
                    }
                } elseif ($column == "return_date") {
                    $returnDate = $document->getCustomAttribute($column)->getValue();
                    if (!$returnDate) {
                        $row[] = "";
                    } else {
                        $date = $this->localeDate->date(new \DateTime($returnDate));
                        $row[] = $date->format('Y-m-d 00:00:00');
                    }
                } else {
                    $row[] = $document->getCustomAttribute($column)->getValue();
                }
            }
        }

        return $row;
    }
}
