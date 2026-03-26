<?php


namespace OnitsukaTiger\Ninja\Model\ResourceModel;


class AccessToken extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const MAIN_TABLE = 'ninja_access_token';
    const ID_FIELD_NAME = 'token_id';
    const COUNTRY_CODE_FIELD_NAME = 'country_code';

    protected function _construct()
    {
        $this->_init(self::MAIN_TABLE, self::ID_FIELD_NAME);
    }

    public function loadByCountryCode($model, $countryCode)
    {
        return $this->load($model, $countryCode, self::COUNTRY_CODE_FIELD_NAME);
    }
}
