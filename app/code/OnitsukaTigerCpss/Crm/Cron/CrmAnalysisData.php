<?php

namespace OnitsukaTigerCpss\Crm\Cron;

class CrmAnalysisData
{
    const ORDER_STATUSES_SQL_QUERY = 'SELECT status FROM sales_order_status';
    const ORDER_STATUSES_RETURN_QUERY = '"closed", "canceled", "partial_refund","rejected"';

    protected $resource;
    protected $logger;

    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Cpss\Crm\Logger\Logger $logger
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    public function __destruct()
    {
        $this->logger->info("Destruct Connection!");
        if ($this->resource) {
            $this->resource->closeConnection();
        }
    }

    public function execute()
    {
        try {
            $connection = $this->resource->getConnection();
            $orderStatuses = $connection->fetchCol(self::ORDER_STATUSES_SQL_QUERY);
            $crmAnalysisDataQuery = $this->crmAnalysisDataQuery($orderStatuses);
            $connection->query($crmAnalysisDataQuery);
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }
    }

    public function crmAnalysisDataQuery($orderStatuses) {
        $orderStatuses = '"' . implode('","', $orderStatuses) . '"';

        return 'INSERT INTO crm_analysis_data (
            member_id,
            first_purchase_day,
            last_purchase_day,
            total_purchase_times,
            total_return_times,
            total_purchase_amounts,
            total_return_amounts
        ) SELECT * FROM (
            SELECT
            member_id,
            MIN(first_purchase_day) as first_purchase_day,
            MAX(last_purchase_day) as last_purchase_day,
            SUM(total_purchase_times) as total_purchase_times,
            SUM(total_return_times) as total_return_times,
            SUM(total_purchase_amounts) as total_purchase_amounts,
            SUM(total_return_amounts) as total_return_amounts
         FROM ((
            SELECT member_id,
                MIN(purchase_date) AS first_purchase_day,
                MAX(purchase_date) AS last_purchase_day,
                COUNT(CASE WHEN transaction_type = 1 THEN 1 ELSE NULL END ) AS total_purchase_times,
                COUNT(CASE WHEN transaction_type = 2 THEN 1 ELSE NULL END ) AS total_return_times,
                SUM(CASE WHEN transaction_type = 1 THEN total_amount ELSE 0 END) AS total_purchase_amounts,
                SUM(CASE WHEN transaction_type = 2 THEN total_amount ELSE 0 END) AS total_return_amounts
            FROM sales_real_store_order AS sr WHERE sr.member_id IS NOT NULL GROUP BY sr.member_id
        ) UNION ALL (
            SELECT customer_id AS member_id,
                MIN(created_at) AS first_purchase_day,
                MAX(created_at) AS last_purchase_day,
                COUNT(CASE WHEN STATUS IN(' . $orderStatuses . ') THEN 1 ELSE NULL END ) AS total_purchase_times,
                COUNT(CASE WHEN STATUS IN('.self::ORDER_STATUSES_RETURN_QUERY.') THEN 1 ELSE NULL END) AS total_return_times,
                SUM(CASE WHEN STATUS IN(' . $orderStatuses . ') THEN base_grand_total ELSE 0 END) AS total_purchase_amounts,
                SUM(CASE WHEN STATUS IN('.self::ORDER_STATUSES_RETURN_QUERY.') THEN CASE WHEN STATUS = "partial_refund" THEN base_total_refunded ELSE base_grand_total END ELSE 0 END) AS total_return_amounts
            FROM sales_order AS so
            WHERE so.customer_id IS NOT NULL AND so.status IN(' . $orderStatuses . ')
            GROUP BY so.customer_id HAVING total_purchase_times > 0 OR total_return_times > 0
        )) mergedCrmDataTableTemp GROUP BY member_id ORDER BY member_id ASC
        ) mergedCrmDataTable
        ON DUPLICATE KEY
        UPDATE
            crm_analysis_data.first_purchase_day = mergedCrmDataTable.first_purchase_day,
            crm_analysis_data.last_purchase_day = mergedCrmDataTable.last_purchase_day,
            crm_analysis_data.total_purchase_times = mergedCrmDataTable.total_purchase_times,
            crm_analysis_data.total_return_times = mergedCrmDataTable.total_return_times,
            crm_analysis_data.total_purchase_amounts = mergedCrmDataTable.total_purchase_amounts,
            crm_analysis_data.total_return_amounts = mergedCrmDataTable.total_return_amounts';
    }
}
