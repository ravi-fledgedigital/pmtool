<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package RMA Base for Magento 2
 */

namespace Amasty\Rma\Model;

use Amasty\Base\Model\Serializer;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigProvider extends \Amasty\Base\Model\ConfigProviderAbstract
{
    /**
     * @var string
     */
    protected $pathPrefix = 'amrma/';

    /**
     * @var Serializer
     */
    private $serializer;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        Serializer $serializer
    ) {
        parent::__construct($scopeConfig);
        $this->serializer = $serializer;
    }

    public const XPATH_ENABLED = 'general/enabled';
    public const URL_PREFIX = 'general/route';
    public const IS_GUEST_RMA_ALLOWED = 'general/guest';
    public const STORE_RESTRICTION = 'general/store_restriction';
    public const IS_HIDE_BILLING = 'general/hide_billing';
    public const ORDER_STATUSES = 'general/allowed_statuses';
    public const RMA_INFO_PRODUCT = 'general/show_return_period_product_page';
    public const RMA_INFO_CART = 'general/show_return_period_cart';
    public const IS_ENABLE_FEEDBACK = 'general/enable_feedback';
    public const MAX_FILE_SIZE = 'general/max_file_size';

    public const IS_ENABLE_RETURN_POLICY = 'rma_policy/policy_enable';
    public const RETURN_POLICY_PAGE = 'rma_policy/policy_page';

    public const CARRIERS = 'shipping/carriers';

    public const NOTIFY_CUSTOMER = 'email/notify_customer';
    public const SENDER = 'email/sender';
    public const NOTIFY_ADMIN = 'email/notify_admin';
    public const NOTIFY_ADMIN_NEW_CUSTOMER_MESSAGE = 'email/notify_admin_new_customer_message';
    public const NOTIFY_ADMIN_GAP_TIME = 'email/notify_admin_gap_time';
    public const SEND_TO = 'email/send_to';
    public const NOTIFY_CUSTOMER_NEW_ADMIN_MESSAGE = 'email/notify_customer_new_admin_message';
    public const NOTIFY_CUSTOMER_NEW_ADMIN_MESSAGE_TEMPLATE = 'email/new_message_template';
    public const CHAT_SENDER = 'email/chat_sender';

    public const XPATH_USER_TEMPLATE = 'amrma/email/user_template';
    public const XPATH_ADMIN_TEMPLATE = 'amrma/email/admin_template';
    public const XPATH_NEW_MESSAGE_TEMPLATE = 'amrma/email/new_message_template';
    public const XPATH_NEW_MESSAGE_ADMIN_TEMPLATE = 'email/new_message_admin_template';

    public const CUSTOM_FIELDS_LABEL = 'extra/title';
    public const CUSTOM_FIELDS = 'extra/custom_fields';

    public const IS_CHAT_ENABLED = 'chat/enabled';
    public const QUICK_REPLIES = 'chat/quick_replies';

    public const IS_SHOW_ADMINISTRATOR_CONTACT = 'return/is_show_administrator_contact';
    public const ADMINISTRATOR_EMAIL = 'return/administrator_email';
    public const ADMINISTRATOR_PHONE = 'return/administrator_phone';

    public const SHOW_CONFIRMATION_MESSAGE = 'general/show_confirmation_message';
    public const CONFIRMATION_MESSAGE = 'general/confirmation_message';

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool)$this->isSetFlag(self::XPATH_ENABLED);
    }

    /**
     * @param int $storeId
     *
     * @return string
     */
    public function getUrlPrefix($storeId = null)
    {
        return (string)$this->getValue(self::URL_PREFIX, $storeId);
    }

    public function isGuestRmaAllowed()
    {
        return (bool)$this->isSetFlag(self::IS_GUEST_RMA_ALLOWED);
    }

    public function isRmaRestrictedByStore(): bool
    {
        return (bool)$this->isSetFlag(self::STORE_RESTRICTION);
    }

    public function isHideBilling(): bool
    {
        return (bool)$this->isSetFlag(self::IS_HIDE_BILLING);
    }

    /**
     * @param int|null $storeId
     *
     * @return string
     */
    public function getCustomFieldsLabel($storeId = null)
    {
        return (string)$this->getValue(self::CUSTOM_FIELDS_LABEL, $storeId);
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCustomFields($storeId = null)
    {
        $result = [];
        if ($customFields = $this->getValue(self::CUSTOM_FIELDS, $storeId)) {
            $customFields = $this->serializer->unserialize($customFields);
            foreach ($customFields as $customField) {
                if (!empty($customField['code']) && !empty($customField['label'])) {
                    $result[$customField['code']] = $customField['label'];
                }
            }
        }

        return $result;
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getCarriers($storeId = null, $toArray = false)
    {
        $result = [];
        if ($carriers = $this->getValue(self::CARRIERS, $storeId)) {
            $carriers = $this->serializer->unserialize($carriers);
            foreach ($carriers as $carrier) {
                if (!empty($carrier['carrier_code']) && !empty($carrier['carrier_label'])) {
                    if ($toArray) {
                        $result[$carrier['carrier_code']] = $carrier['carrier_label'];
                    } else {
                        $result[] = [
                            'code' => $carrier['carrier_code'],
                            'label' => $carrier['carrier_label'],
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isNotifyCustomer($storeId = null)
    {
        return (bool)$this->isSetFlag(self::NOTIFY_CUSTOMER, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getSender($storeId = null)
    {
        return $this->getValue(self::SENDER, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getChatSender($storeId = null)
    {
        return $this->getValue(self::CHAT_SENDER, $storeId);
    }

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isNotifyAdmin($storeId = null)
    {
        return (bool)$this->isSetFlag(self::NOTIFY_ADMIN, $storeId);
    }

    public function isNotifyCustomerAboutNewMessage($storeId = null)
    {
        return $this->isSetFlag(self::NOTIFY_CUSTOMER_NEW_ADMIN_MESSAGE, $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return array|bool
     */
    public function getAdminEmails($storeId = null)
    {
        $emails = trim($this->getValue(self::SEND_TO, $storeId) ?? '');

        return $emails ? preg_split('/\n|\r\n?/', $emails) : false;
    }

    /**
     * @param int|null $storeId
     *
     * @return array
     */
    public function getQuickReplies($storeId = null)
    {
        $result = [];
        if ($quickReplies = $this->getValue(self::QUICK_REPLIES, $storeId)) {
            $quickReplies = $this->serializer->unserialize($quickReplies);
            foreach ($quickReplies as $quickReply) {
                if (!empty($quickReply['reply'])) {
                    $result[$quickReply['label']] = $quickReply['reply'];
                }
            }
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getMaxFileSize()
    {
        return (int)$this->getValue(self::MAX_FILE_SIZE);
    }

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isEnableFeedback($storeId = null)
    {
        return (bool)$this->isSetFlag(self::IS_ENABLE_FEEDBACK, $storeId);
    }

    /**
     * @param null|int $storeId
     *
     * @return array
     */
    public function getAllowedOrderStatuses($storeId = null)
    {
        $orderStatuses = $this->getValue(self::ORDER_STATUSES, $storeId);
        if (empty($orderStatuses)) {
            return [];
        }

        return array_map('trim', explode(',', $orderStatuses));
    }

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isShowRmaInfoProductPage($storeId = null)
    {
        return (bool)$this->isSetFlag(self::RMA_INFO_PRODUCT, $storeId);
    }

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isShowRmaInfoCart($storeId = null)
    {
        return (bool)$this->isSetFlag(self::RMA_INFO_CART, $storeId);
    }

    /**
     * @return bool
     */
    public function isShowAdministratorContact()
    {
        return (bool)$this->isSetFlag(self::IS_SHOW_ADMINISTRATOR_CONTACT);
    }

    /**
     * @return string
     */
    public function getAdministratorPhoneNumber()
    {
        return (string)$this->getValue(self::ADMINISTRATOR_PHONE);
    }

    /**
     * @return string
     */
    public function getAdministratorEmail()
    {
        return (string)$this->getValue(self::ADMINISTRATOR_EMAIL);
    }

    /**
     * @return bool
     */
    public function isReturnPolicyEnabled()
    {
        return (bool)$this->isSetFlag(self::IS_ENABLE_RETURN_POLICY);
    }

    /**
     * @return int
     */
    public function getReturnPolicyPage()
    {
        return (int)$this->getValue(self::RETURN_POLICY_PAGE);
    }

    /**
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isChatEnabled($storeId = null)
    {
        return (bool)$this->isSetFlag(self::IS_CHAT_ENABLED, $storeId);
    }

    public function getEmailTemplateForNewAdminMessage(?int $storeId = null): int
    {
        return (int)$this->getValue(self::NOTIFY_CUSTOMER_NEW_ADMIN_MESSAGE_TEMPLATE, $storeId);
    }

    public function isNotifyAdminAboutNewMessage(?int $storeId = null): bool
    {
        return $this->isSetFlag(self::NOTIFY_ADMIN_NEW_CUSTOMER_MESSAGE, $storeId);
    }

    public function getNotifyAdminGapTime(?int $storeId = null): int
    {
        return (int)$this->getValue(self::NOTIFY_ADMIN_GAP_TIME, $storeId);
    }

    public function getNewMessageAdminTemplate(?int $storeId = null): string
    {
        return (string)$this->getValue(self::XPATH_NEW_MESSAGE_ADMIN_TEMPLATE, $storeId);
    }

    public function isShowConfirmationMessage(?int $storeId = null): bool
    {
        return $this->isSetFlag(self::SHOW_CONFIRMATION_MESSAGE, $storeId);
    }

    public function getConfirmationMessage(?int $storeId = null): string
    {
        return (string)$this->getValue(self::CONFIRMATION_MESSAGE, $storeId);
    }
}
