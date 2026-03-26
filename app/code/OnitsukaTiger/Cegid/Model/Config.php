<?php

namespace OnitsukaTiger\Cegid\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    const PATH_RETURN_STATUS_REMAINING_QTY_IS_0 = 'cegid/cegid_return/approve_by_admin_status';
    const PATH_RETURN_STATUS_RESPONSE_EMPTY = 'cegid/cegid_return/reject_by_admin_status';
    const PATH_RETURN_STATUS_SEND_TO_CEGID = 'cegid/cegid_return/new_request_status';
    const PATH_RETURN_USER_NAME = 'cegid/cegid_return/username';
    const PATH_RETURN_PASSWORD = 'cegid/cegid_return/password';
    const PATH_RETURN_DATABASE_ID = 'cegid/cegid_return/database_id';
    const PATH_RETURN_ENDPOINT = 'cegid/cegid_return/endpoint';
    const PATH_CHECK_ENABLE_GET_INVOICE = 'cegid/cegid_get_pdf/enable';
    const PATH_CHECK_ENABLE_GET_AWB = 'cegid/cegid_get_awb_pdf/enable';
    const PATH_CEGID_SOURCE_MAPPING = 'cegid/cegid_return/cegid_source_mapping';
    const PATH_UPDATE_STATUS_URL = 'cegid/general/cegid_update_status_url';

    private ScopeConfigInterface $scopeConfig;
    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
    ) {
        $this->scopeConfig = $scopeConfig;
    }


    /**
     * @return mixed
     */
    public function getReturnStatusReceived(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_RETURN_STATUS_REMAINING_QTY_IS_0
        );
    }

    /**
     * @return mixed
     */
    public function getReturnStatusRejected(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_RETURN_STATUS_RESPONSE_EMPTY
        );
    }

    /**
     * @return mixed
     */
    public function getReturnStatusSendToCegid(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_RETURN_STATUS_SEND_TO_CEGID
        );
    }

    /**
     * @return mixed
     */
    public function getReturnUserName(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_RETURN_USER_NAME
        );
    }

    /**
     * @return mixed
     */
    public function getReturnPassword(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_RETURN_PASSWORD
        );
    }

    /**
     * @return mixed
     */
    public function getReturnDatabaseId(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_RETURN_DATABASE_ID
        );
    }

    /**
     * @return mixed
     */
    public function getReturnEndpoint(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_RETURN_ENDPOINT
        );
    }

    /**
     * @param mixed|null $storeId
     * @return mixed
     */
    public function isEnableGetInvoicePdf(mixed $storeId = null): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_CHECK_ENABLE_GET_INVOICE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param mixed|null $storeId
     * @return mixed
     */
    public function isEnableGetAWBPdf(mixed $storeId = null): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_CHECK_ENABLE_GET_AWB,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
    /**
     * Get value config source mapping data
     * @return mixed
     */
    public function getCegidSourceMapping(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_CEGID_SOURCE_MAPPING,
        );
    }

    /**
     * Get value config source mapping data
     * @return mixed
     */
    public function getUpdateStatusUrl($storeId): mixed
    {
        return $this->scopeConfig->getValue(
            self::PATH_UPDATE_STATUS_URL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

}
