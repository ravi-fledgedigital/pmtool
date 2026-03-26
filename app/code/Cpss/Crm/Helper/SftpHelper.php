<?php

namespace Cpss\Crm\Helper;

use Cpss\Crm\Logger\Logger;
use Cpss\Pos\Logger\CsvLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Store\Model\ScopeInterface;

class SftpHelper extends AbstractHelper
{
    protected $sftp;
    protected $_scopeConfig;
    protected $directory;
    private $logger;
    protected $encryptor;
    protected $csvLogger;
    public $usedSftpServer = "";

    public function __construct(
        Context $context,
        Sftp $sftp,
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directory,
        Logger $logger,
        CsvLogger $csvLogger,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->sftp = $sftp;
        $this->_scopeConfig = $scopeConfig;
        $this->directory = $directory;
        $this->logger = $logger;
        $this->encryptor = $encryptor;
        $this->csvLogger = $csvLogger;
    }

    public function cpssConnect($storeId = null)
    {
        try {
            $auth = [
                'host' => $this->getHost('cpss', $storeId),
                'port' => $this->getPort('cpss', $storeId),
                'username' => $this->getUsername('cpss', $storeId)
            ];

            //Set sftp port (need to append to host using ':' )
            if (isset($auth['port']) && $auth['port'] != 22) {
                $auth['host'] .= ':' . $auth['port'];
            }

            $this->usedSftpServer = $auth["host"];
            $this->logger->info("SFTP AUTH", $auth);
            $methodAccess = $this->getMethodAccess("cpss");

            if ($methodAccess == "key") {
                $key = $this->encryptor->decrypt($this->getPrivateKey("cpss"));
                $rsa = new \phpseclib\Crypt\RSA($key);
                $rsa->loadKey($key);
                $auth['password'] = $rsa;
            } else {
                $auth['password'] = $this->getPassword('cpss', $storeId);
            }

            $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/sftpHelper.log');
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $logger->info('==========================SFTP Helpre Debugging Start============================');
            $logger->info('SFTP Helper Data: ' . print_r($auth, true));

            $this->sftp->open($auth);
            return true;
        } catch (\Exception $e) {
            $this->logger->critical($e);
            return false;
        }
    }

    public function fileTransfer($dstFile, $srcFile)
    {
        try {
            $data_to_send = '';
            if (!empty($srcFile)) {
                $data_to_send = @file_get_contents($srcFile);
            }

            $success = $this->sftp->write(
                $dstFile,
                $data_to_send
            );

            if ($success) {
                $this->logger->info("$srcFile transferred to SFTP server.");
                return true;
            } else {
                $this->logger->error('Failed to transfer ' . $srcFile);
            }

            return false;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    public function changeDirectory($dir)
    {
        $this->sftp->cd($dir);
    }

    public function workingDirectory()
    {
        return $this->sftp->pwd();
    }

    public function ls($grep = null)
    {
        return $this->sftp->ls($grep);
    }

    public function read($path)
    {
        return $this->sftp->read($path);
    }

    // move or rename [sftp to local]
    public function move($source, $destination)
    {
        $this->copy($source, $destination);
        $this->sftp->rm($source);
    }

    //sftp to sftp
    public function mv($source, $destination)
    {
        $this->sftp->mv($source, $destination);
    }

    public function write($filename, $source, $mode = null)
    {
        $this->sftp->write($filename, $source, $mode);
    }

    //copy sftp to local
    public function copy($source, $destination)
    {
        $destination = $destination . "/" . $this->filename($source);
        $this->sftp->read($source, $destination);
    }

    public function filename($source)
    {
        $arr = explode("/", $source);
        return end($arr);
    }

    public function upload($destination, $source)
    {
        $this->copy($destination, $source);
    }

    public function remove($source)
    {
        $this->sftp->rm($source);
    }

    public function getHost($data, $storeId)
    {
        return $this->_scopeConfig->getValue(
            'sftp/' . $data . '/host',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getUsername($data, $storeId)
    {
        return $this->_scopeConfig->getValue(
            'sftp/' . $data . '/username',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPassword($data, $storeId)
    {
        return $this->_scopeConfig->getValue(
            'sftp/' . $data . '/password',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getMethodAccess($data)
    {
        return $this->_scopeConfig->getValue('sftp/' . $data . '/method_access');
    }

    public function getPrivateKey($data)
    {
        return $this->_scopeConfig->getValue('sftp/' . $data . '/private_key');
    }

    public function getPort($data, $storeId)
    {
        return $this->_scopeConfig->getValue(
            'sftp/' . $data . '/port',
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function getPath($data, $path = null)
    {
        $arr = ['inbound' => 'in_', 'outbound' => 'out_'];
        $path = ($path === null) ? '' : $arr[$path];

        return  $this->_scopeConfig->getValue('sftp/' . $data . '/' . $path . 'path');
    }

    public function logout()
    {
        $this->sftp->close();
    }
}
