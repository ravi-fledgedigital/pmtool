<?php

namespace Cpss\Pos\Cron;

class CronSchedRunRecovery
{
    /**
     * @var \Cpss\Pos\Cron\UpdatePosDataRecovery
     */
    protected $updatePosData;

    /**
     * @var \Cpss\Pos\Helper\CreateCsv
     */
    protected $createCsv;

    /**
     * @var \Cpss\Pos\Cron\CpssTransferFiles
     */
    protected $cpssTransferFiles;

    /**
     * @var \Cpss\Pos\Logger\Logger
     */
    protected $posLogger;

    public function __construct(
        \Cpss\Pos\Cron\UpdatePosDataRecovery $updatePosData,
        \Cpss\Pos\Helper\CreateCsv $createCsv,
        \Cpss\Pos\Cron\CpssTransferFiles $cpssTransferFiles,
        \Cpss\Pos\Logger\Logger $posLogger
    ) {
        $this->updatePosData = $updatePosData;
        $this->createCsv = $createCsv;
        $this->cpssTransferFiles = $cpssTransferFiles;
        $this->posLogger = $posLogger;
    }

    public function execute($send = false)
    {
        $this->posLogger->info("START POS -> CPSS PROCESS");
        $this->posLogger->info("Get POS csv and merge..");
        $purchaseIds = $this->updatePosData->execute();
        $this->posLogger->info("Recovery returned purchaseIds: ". print_r($purchaseIds, true));
        $this->posLogger->info("Generate CPSS csv..");
        $this->createCsv->generateRealStoresData(true, $purchaseIds);
        if ($send == 1) {
            $this->posLogger->info("Transfer generated csv to CPSS SFTP server..");
            $this->cpssTransferFiles->executeRealStore(true);
        }
        
        $this->posLogger->info("END POS -> CPSS PROCESS");
        echo "POS Recovery is Finished. \n";
    }
}
