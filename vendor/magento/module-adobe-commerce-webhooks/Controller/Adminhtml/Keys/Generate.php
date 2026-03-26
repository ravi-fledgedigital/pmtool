<?php
/************************************************************************
 *
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2024 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 * ************************************************************************
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceWebhooks\Controller\Adminhtml\Keys;

use Magento\AdobeCommerceWebhooks\Model\Config\System\Config;
use Magento\Framework\App\Cache\Type\Config as CacheConfig;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Oauth\Exception;
use phpseclib3\Crypt\RSA;
use Psr\Log\LoggerInterface;

/**
 * Controller for generating digital signature keys
 */
class Generate extends Action implements HttpGetActionInterface
{
    /**
     * Authorization level
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_AdobeCommerceWebhooks::digital_signature_generate_keys';

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Context $context,
        private EncryptorInterface $encryptor,
        private WriterInterface $configWriter,
        private TypeListInterface $cacheTypeList,
        private LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Generates a new pair of keys for webhooks digital signature
     *
     * @return ResultInterface
     * @throws Exception
     */
    public function execute(): ResultInterface
    {
        $rsaPrivateKey = RSA::createKey();

        $publicKey = $rsaPrivateKey->getPublicKey()->toString($rsaPrivateKey->getLoadedFormat());
        $privateKey = $rsaPrivateKey->toString($rsaPrivateKey->getLoadedFormat());

        $this->configWriter->save(Config::CONFIG_PATH_SIGNATURE_ENABLED, true);
        $this->configWriter->save(Config::CONFIG_PATH_SIGNATURE_PUBLIC_KEY, $this->encryptor->encrypt($publicKey));
        $this->configWriter->save(Config::CONFIG_PATH_SIGNATURE_PRIVATE_KEY, $this->encryptor->encrypt($privateKey));

        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData([
            'status' => 200
        ]);
        $this->logger->info('The new pair of keys for webhooks digital signature have been successfully generated');
        $this->messageManager->addSuccessMessage(__('The new pair of keys have been successfully generated'));

        $this->cacheTypeList->cleanType(CacheConfig::TYPE_IDENTIFIER);

        return $result;
    }
}
