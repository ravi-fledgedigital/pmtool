<?php
/**
 * @copyright: Copyright © 2019 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\PlatformNetsuite\Plugin\Model\Job;

use Magento\Framework\Exception\LocalizedException;

/**
 * Class Processor
 * @package Firebear\PlatformNetsuite\Plugin\Model\Job
 */
class Processor
{
    /**
     * @param \Firebear\ImportExport\Model\Job\Processor $model
     * @param $result
     * @return mixed
     */
    public function afterDataValidate(
        \Firebear\ImportExport\Model\Job\Processor $model,
        $result
    ) {
//        $importHistoryModel = $model->getImportHistoryModel();
//        $importHistoryModel->load(null);
//        $summary = $importHistoryModel->getSummary();
//        if ((strpos($summary, 'Error loading data from API') !== false) ||
//            (strpos($summary, 'The import was stopped on page') !== false)
//        ) {
//            throw new LocalizedException(__($summary));
//        }
//        return $result;
    }
}
