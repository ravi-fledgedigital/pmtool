<?php
namespace OnitsukaTiger\Command\Console\Omise;

/**
 * Class CsvExport
 */
class CsvExport extends \OnitsukaTiger\Command\Console\Command
{
    const OPTION_DAYS_BEFORE = 'days';

    const CHARGE_PER_PAGE = 100;

    /**
     * @var \Omise\Payment\Model\Config\Config
     */
    protected $omiseConfig;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var mixed
     */
    protected $netSuiteAccount;
    /**
     * @var mixed
     */
    protected $netSuiteUrl;
    /**
     * @var mixed
     */
    protected $netSuiteDeployId;
    /**
     * @var mixed
     */
    protected $netSuiteScriptId;
    /**
     * @var mixed
     */
    protected $netSuiteConsumerKey;
    /**
     * @var mixed
     */
    protected $netSuiteConsumerSecret;
    /**
     * @var mixed
     */
    protected $netSuiteTokenId;
    /**
     * @var mixed
     */
    protected $netSuiteTokenSecret;

    protected $omise;

    /**
     * @param \OnitsukaTiger\Logger\Logger $logger
     * @param \Omise\Payment\Model\Omise $omise
     * @param \Omise\Payment\Model\Config\Config $omiseConfig
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \OnitsukaTiger\Logger\Logger $logger,
        \Omise\Payment\Model\Omise $omise,
        \Omise\Payment\Model\Config\Config $omiseConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $omise->defineApiKeys();
        $this->omiseConfig = $omiseConfig;
        $this->scopeConfig = $scopeConfig;
        $this->netSuiteAccount = $this->scopeConfig->getValue('firebear_importexport/netsuite/account');
        $this->netSuiteUrl = $this->scopeConfig->getValue('omise_netsuite/general/url');
        $this->netSuiteDeployId = $this->scopeConfig->getValue('omise_netsuite/general/deploy_id');
        $this->netSuiteScriptId = $this->scopeConfig->getValue('omise_netsuite/general/script_id');
        $this->netSuiteConsumerKey = $this->scopeConfig->getValue('omise_netsuite/general/consumer_key');
        $this->netSuiteConsumerSecret = $this->scopeConfig->getValue('omise_netsuite/general/consumer_secret');
        $this->netSuiteTokenId = $this->scopeConfig->getValue('omise_netsuite/general/token_id');
        $this->netSuiteTokenSecret = $this->scopeConfig->getValue('omise_netsuite/general/token_secret');

        parent::__construct($logger);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $options = [
            new \Symfony\Component\Console\Input\InputOption(
                self::OPTION_DAYS_BEFORE,
                'd',
                \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL,
                'N Days Before'
            )
        ];

        $this->setName('omise:csv:export');
        $this->setDescription('Omise CSV file export command');
        $this->setDefinition($options);
        parent::configure();
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return null|int
     */
    protected function execute(
        \Symfony\Component\Console\Input\InputInterface $input,
        \Symfony\Component\Console\Output\OutputInterface $output
    ) {
        // TODO you need to define API version if your environment is not use '2019-05-29' or newer version
//        define('OMISE_API_VERSION', '2019-05-29');

        // key
        $publicKey = $this->omiseConfig->getPublicKey();
        $secretKey = $this->omiseConfig->getSecretKey();

        $n = -1;
        if ($option = $input->getOption(self::OPTION_DAYS_BEFORE)) {
            $n = -$option;
        }

        // target date (default yesterday)
        $targetDate = new \DateTime($n . ' days', new \DateTimeZone('Asia/Bangkok'));

        $date = $targetDate->format("Y-m-d");
        $this->logger->info('target date : ' . $date);

        // receipt
        // Please use product environment if you want to test Receipt API
        $search = \OnitsukaTiger\Command\Console\Omise\OmiseReceipt::retrieve($date, $publicKey, $secretKey);

        $reciepts = $search['data'];
        $reciept = null;
        foreach ($reciepts as $data) {
            $reciept = $data;
        }

        // charge
        $search = \OmiseCharge::search('', $publicKey, $secretKey)->filter([
            'created' => sprintf('%s..%s', $date, $date)
        ])->per_page(self::CHARGE_PER_PAGE);
        // search
        $transactions = $this->getTransactions($search);
        $result = [];
        foreach ($transactions as $transaction) {
            $data = [];
            $data['transaction'] = $transaction['object'];
            $data['date'] = $transaction['paid_at'];
            $data['order_id'] = array_key_exists('metadata', $transaction) && array_key_exists('order_id', $transaction['metadata']) ? $transaction['metadata']['order_id'] : '';
            $data['amount'] = $transaction['amount'] / 100;
            $data['commission_amount'] = ($transaction['fee'] + $transaction['fee_vat']) / 100;
            $data['net_amount'] = $data['amount'] - $data['commission_amount'];
            $data['type'] = $transaction['card'] ? 'card' : $transaction['source']['type'];
            $data['status'] = $transaction['status'];

            if($data['status'] == 'failed') {
                $this->logger->alert('skip failed data : ' . json_encode($data));
                continue;
            }
            $result[] = $data;
        }

        // refund
        $search = \OmiseRefund::search('', $publicKey, $secretKey)->filter([
            'created' => sprintf('%s..%s', $date, $date)
        ]);
        $transactions = $this->getTransactions($search);
        foreach ($transactions as $transaction) {
            $data = [];
            $charge = \OmiseCharge::retrieve($transaction['charge'], $publicKey, $secretKey);
            if ($transaction['voided'] == 'true') {
                $data['transaction'] = $transaction['object'] . ' (voided)';
                $data['commission_amount'] = 0;
                $data['amount'] = ($transaction['amount'] / 100);
            } else {
                $data['transaction'] = $transaction['object'];
                $data['commission_amount'] = 0;
                $data['amount'] = ($transaction['amount'] / 100);
            }
            $data['date'] = $transaction['created_at'];
            $metadata = $charge->offsetExists('metadata') ? $charge['metadata'] : [];
            $data['net_amount'] = $data['amount'] - $data['commission_amount'];
            $data['order_id'] = array_key_exists('order_id', $metadata) ? $metadata['order_id'] : '';
            $data['type'] = '';
            $data['status'] = $transaction['status'];

            $result[] = $data;
        }
        // output
        $header = [
            'transaction',
            'date',
            'order id(Magento)',
            'amount',
            'commission amount',
            'net amount',
            'type',
            'invoice number',
            'invoice date',
            'status'
        ];
        $csv = implode(',', $header) . PHP_EOL;
        foreach ($result as $row) {
            $result = [
                $row['transaction'],
                $this->formatDateForNetSuite(!is_null($row['date']) ? $row['date'] : 'now'),
                $row['order_id'],
                $row['amount'],
                $row['commission_amount'],
                $row['net_amount'],
                $row['type'],
                $reciept['number'],
                $this->formatDateForNetSuite(!is_null($reciept['created_at']) ? $reciept['created_at'] : 'now'),
                $row['status']
            ];
            $csv .= implode(',', $result) . PHP_EOL;
        }

        return $this->post($csv, $targetDate);
    }

    /**
     * @param \OmiseSearch $search
     * @return array
     */
    private function getTransactions(\OmiseSearch $search)
    {
        $transactions = [];
        for ($i = 1; $i <= $search['total_pages']; $i++) {
            $transactions = array_merge($transactions, $search->page($i)['data']);
        }

        return $transactions;
    }

    /**
     * Format date for NetSuite
     * @param $str
     * @return string
     * @throws \Exception
     */
    private function formatDateForNetSuite($str)
    {
        $t = new \DateTime($str);
        $t->setTimeZone(new \DateTimeZone('Asia/Bangkok'));
        return $t->format('Ymd');
    }

    /**
     * POST to NetSuite
     * @param $csv
     * @param \DateTime $csvDate
     * @return int
     */
    private function post($csv, \DateTime $csvDate)
    {
        $filename = sprintf('omise_%s.csv', $csvDate->format('Ymd'));
        $data = [
            'filename' => $filename,
            'filetype' => 'import_payment_gateway_gds_th',
            'filetext' => $csv
        ];
        $json = json_encode($data);
        $this->logger->info($json);

        /** @var \Laminas\Http\Headers $httpHeaders */
        $httpHeaders = new \Laminas\Http\Headers();
        $httpHeaders->addHeaders([
            'Authorization: ' . $this->getAuthHeader(),
            'Content-Type: application/json',
//            'Content-Length: ' . strlen($json)
        ]);
        $request = new \Laminas\Http\Request();
        $request->setHeaders($httpHeaders);
        $uri = sprintf('%s?script=%s&deploy=%s', $this->netSuiteUrl, $this->netSuiteScriptId, $this->netSuiteDeployId);
        $request->setUri($uri);
        $request->setMethod(\Laminas\Http\Request::METHOD_POST);

        $client = new \Laminas\Http\Client();
        $options = [
            'adapter'   => 'Laminas\Http\Client\Adapter\Curl',
            'curloptions' => [
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_POSTFIELDS => $json
            ],
            'maxredirects' => 0,
            'timeout' => 30
        ];
        $client->setOptions($options);

        /** @var \Laminas\Http\Response $response */
        $response = $client->send($request);

        if($response->getStatusCode() != 200) {
            $message = sprintf(
                'Failed to push data to NetSuite. status code [%s], message[%s]',
                $response->getStatusCode(),
                $response->getContent()
            );
            $this->logger->error($message);

            return self::FAILURE;
        }
        // get body
        $body = json_decode($response->getBody());
        if(!$body->success) {
            $message = sprintf(
                'Failed to process at the NetSuite. status [%s], message[%s]',
                $body->status,
                $body->message
            );
            $this->logger->error($message);

            return self::FAILURE;
        } else {
            $message = sprintf(
                'File uploaded to NetSuite. status [%s], message[%s]',
                $body->status,
                $body->message
            );
            $this->logger->info($message);
        }

        return self::SUCCESS;
    }

    /**
     * get NetSuite Auth Header
     * @return string
     */
    function getAuthHeader()
    {
        $oauth_nonce = md5(mt_rand());
        $oauth_timestamp = time();
        $oauth_signature_method = 'HMAC-SHA256';
        $oauth_version = "1.0";

        $base =
            "POST&" . urlencode($this->netSuiteUrl) . "&" .
            urlencode(
                "deploy=" . $this->netSuiteDeployId
                . "&oauth_consumer_key=" . $this->netSuiteConsumerKey
                . "&oauth_nonce=" . $oauth_nonce
                . "&oauth_signature_method=" . $oauth_signature_method
                . "&oauth_timestamp=" . $oauth_timestamp
                . "&oauth_token=" . $this->netSuiteTokenId
                . "&oauth_version=" . $oauth_version
                . "&script=" . $this->netSuiteScriptId
            );
        $sig = urlencode($this->netSuiteConsumerSecret) . '&' . urlencode($this->netSuiteTokenSecret);
        $signature = base64_encode(hash_hmac("sha256", $base, $sig, true));

        $header = sprintf('OAuth oauth_signature="%s",', rawurlencode($signature));
        $header .= sprintf('oauth_version="%s",', rawurlencode($oauth_version));
        $header .= sprintf('oauth_nonce="%s",', rawurlencode($oauth_nonce));
        $header .= sprintf('oauth_signature_method="%s",', rawurlencode($oauth_signature_method));
        $header .= sprintf('oauth_consumer_key="%s",', rawurlencode($this->netSuiteConsumerKey));
        $header .= sprintf('oauth_token="%s",', rawurlencode($this->netSuiteTokenId));
        $header .= sprintf('oauth_timestamp="%s",', rawurlencode($oauth_timestamp));
        $header .= sprintf('realm="%s"', rawurlencode($this->netSuiteAccount));

        return $header;
    }
}
