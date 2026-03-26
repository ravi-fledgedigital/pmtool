<?php
/**
 * @author Aitoc Team
 * @copyright Copyright (c) 2022 Aitoc (https://www.aitoc.com)
 * @package Aitoc_SendGrid
 */


namespace Aitoc\SendGrid\Model;

class ApiWork
{
    const API_URL = 'https://api.sendgrid.com/v3';
    const WORK_WITH_CONTACTS = '/marketing/contacts';
    const WORK_WITH_CONTACT_LISTS = '/marketing/lists';
    const WORK_WITH_UNSUBSCRIBE_GROUPS = '/asm/groups';
    const SEARCH_CONTACTS = '/marketing/contacts/search';
    const GET_ALL_UNSUBSRIBE_CUSTOMERS = '/asm/suppressions';
    const SINGLESENDS = '/marketing/singlesends';
    const ALL_STATS = '/stats';
    const TEMPLATES = '/templates';

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    private $encryptor;

    /**
     * @var \Aitoc\SendGrid\Model\Http\Adapter\Curl
     */
    private $curl;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    public function __construct(
        \Aitoc\SendGrid\Model\ConfigProvider $configProvider,
        \Aitoc\SendGrid\Model\Http\Adapter\Curl $curl,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        $this->configProvider = $configProvider;
        $this->encryptor = $encryptor;
        $this->curl = $curl;
        $this->json = $json;
    }

    /**
     * @param $emails
     * @param $storeId
     */
    public function sendNewSubscriber($emails, $storeId)
    {
        $listId = $this->configProvider->getSubscribeListId($storeId);
        $customers = $this->getJsonContacts($emails);

        $requestBody = $listId && $this->isExistInList($listId, $storeId)
            ? '{"list_ids":["' . $listId . '"],"contacts":[' . $customers . ']}'
            : '{"contacts":[' . $customers . ']}';

        $this->sendRequest(\Zend_Http_Client::PUT, self::API_URL . self::WORK_WITH_CONTACTS, $storeId, $requestBody);
    }

    /**
     * @param $emails
     * @return string
     */
    private function getJsonContacts($emails)
    {
        if (is_array($emails)) {
            $customers = '';
            foreach ($emails as $key => $email) {
                $delimiter = $key >= count($emails) - 1 ? '' : ',';
                $customers .= '{"email": "' . $email . '"}' . $delimiter;
            }
        } else {
            $customers = '{"email": "' . $emails . '"}';
        }

        return $customers;
    }

    /**
     * @param $customers
     * @param $storeId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendCustomerWithoutSub($customers, $storeId)
    {
        $listId = $this->configProvider->getListForNewCustomer($storeId);
        $customers = $this->getJsonForNewContacts($customers);

        $requestBody = $listId && $this->isExistInList($listId, $storeId)
            ? '{"list_ids":["' . $listId . '"],"contacts":[' . $customers . ']}'
            : '{"contacts":[' . $customers . ']}';

        $this->sendRequest(\Zend_Http_Client::PUT, self::API_URL . self::WORK_WITH_CONTACTS, $storeId, $requestBody);
    }

    /**
     * @param $customersData
     * @return string
     */
    private function getJsonForNewContacts($customersData)
    {
        $customersJson = '';
        foreach ($customersData as $key => $customer) {
            $delimiter = $key >= count($customersData) - 1 ? '' : ',';
            $customersJson .= '{"email": "'
                . $customer->getEmail()
                . '", "first_name": "'
                . $customer->getFirstname()
                . '", "last_name": "'
                . $customer->getLastname()
                . '"}'
                . $delimiter;
        }

        return $customersJson;
    }

    /**
     * @param $listId
     * @param $storeId
     * @return bool
     */
    private function isExistInList($listId, $storeId)
    {
        $allLists = $this->getContactLists($storeId);

        $isExist = false;
        foreach ($allLists as $list) {
            if ($list['id'] == $listId) {
                $isExist = true;
                break;
            }
        }

        return $isExist;
    }

    /**
     * @param $emails
     * @param $storeId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendUnsubscribe($emails, $storeId)
    {
        $listId = $this->configProvider->getUnsubscribeListId($storeId);
        if (is_array($emails)) {
            $customers = '';
            foreach ($emails as $key => $email) {
                $delimiter = $key >= count($emails) - 1 ? '' : ',';
                $customers .= '"' . $email . '"' . $delimiter;
            }
        } else {
            $customers = '"' . $emails . '"';
        }
        $requestBody = '{"recipient_emails":[' . $customers . ']}';
        $url = self::API_URL . self::WORK_WITH_UNSUBSCRIBE_GROUPS . '/' . $listId . '/suppressions';
        $this->sendRequest(\Zend_Http_Client::POST, $url, $storeId, $requestBody);
    }

    /**
     * @param $storeId
     * @return array|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getContactLists($storeId = null)
    {
        $response = $this->sendRequest(\Zend_Http_Client::GET, self::API_URL . self::WORK_WITH_CONTACT_LISTS, $storeId);

        return $response['result'] ?? [];
    }

    /**
     * @return array
     */
    public function getUnsubscribeLists()
    {
        $response = $this->sendRequest(\Zend_Http_Client::GET, self::API_URL . self::WORK_WITH_UNSUBSCRIBE_GROUPS);

        return $response ?? [];
    }

    /**
     * @param $ids
     * @param $storeId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteCustomers($ids, $storeId)
    {
        $url = self::API_URL . self::WORK_WITH_CONTACTS . '?ids=' . implode(',', $ids);
        $this->sendRequest(\Zend_Http_Client::DELETE, $url, $storeId);
    }

    /**
     * @param $email
     * @param $storeId
     * @return array|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function findContactsByEmails($email, $storeId)
    {
        $requestBody = '{"query":"email LIKE \'' . $email . '\'"}';
        $url = self::API_URL . self::SEARCH_CONTACTS;
        $response = $this->sendRequest(\Zend_Http_Client::POST, $url, $storeId, $requestBody);

        return $response['result'] ?? [];
    }

    /**
     * @return array|mixed
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAllUnsubscribe()
    {
        $url = self::API_URL . self::GET_ALL_UNSUBSRIBE_CUSTOMERS;

        return $this->sendRequest(\Zend_Http_Client::GET, $url);
    }

    /**
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSingleSends()
    {
        $url = self::API_URL . self::SINGLESENDS;

        return $this->sendRequest(\Zend_Http_Client::GET, $url);
    }

    /**
     * @param $id
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSingleSendById($id)
    {
        $url = self::API_URL . self::SINGLESENDS . '/' . $id;

        return $this->sendRequest(\Zend_Http_Client::GET, $url);
    }

    /**
     * @param $id
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteSingleSendById($id)
    {
        $url = self::API_URL . self::SINGLESENDS . '/' . $id;
        $this->sendRequest(\Zend_Http_Client::DELETE, $url);
    }

    /**
     * @param $start
     * @param $end
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStatistics($start, $end)
    {
        $url = self::API_URL . self::ALL_STATS
            . '?aggregated_by=day&start_date=' . $start . '&end_date=' . $end;

        return $this->sendRequest(\Zend_Http_Client::GET, $url);
    }

    /**
     * @param $data
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function createSingleSend($data)
    {
        $url = self::API_URL . self::SINGLESENDS;

        return $this->sendRequest(\Zend_Http_Client::POST, $url, null, $this->json->serialize($data));
    }

    public function getTemplateById($id)
    {
        $url = self::API_URL . self::TEMPLATES . '/' . $id;

        return $this->sendRequest(\Zend_Http_Client::GET, $url);
    }

    public function getTemplateByIdAndVersion($id, $version)
    {
        $url = self::API_URL . self::TEMPLATES . '/' . $id . '/versions/' . $version;

        return $this->sendRequest(\Zend_Http_Client::GET, $url);
    }

    /**
     * @param $method
     * @param $url
     * @param null $storeId
     * @param string $requestBody
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendRequest($method, $url, $storeId = null, $requestBody = '{}')
    {
        $this->curl->write(
            $method,
            $url,
            CURL_HTTP_VERSION_1_1,
            [
                'Authorization: Bearer ' . $this->encryptor->decrypt($this->configProvider->getApiKey($storeId)),
                'Content-type: application/json'
            ],
            $requestBody
        );

        $this->curl->setOptions([
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = $this->curl->read();
        $this->curl->close();
        $json = \Zend_Http_Response::extractBody($response);
        $body = [];
        if ($json) {
            $body = $this->json->unserialize(\Zend_Http_Response::extractBody($response));
        }

        return isset($body['errors']) ? [] : $body;
    }
}
