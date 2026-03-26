<?php

namespace Cpss\Pos\Cron;

class CronSchedRun
{
    /**
     * @var \Cpss\Pos\Cron\UpdatePosData
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
        \Cpss\Pos\Cron\UpdatePosData $updatePosData,
        \Cpss\Pos\Helper\CreateCsv $createCsv,
        \Cpss\Pos\Cron\CpssTransferFiles $cpssTransferFiles,
        \Cpss\Pos\Logger\Logger $posLogger
    ) {
        $this->updatePosData = $updatePosData;
        $this->createCsv = $createCsv;
        $this->cpssTransferFiles = $cpssTransferFiles;
        $this->posLogger = $posLogger;
    }

    public function execute()
    {
        $this->posLogger->info("START POS -> CPSS PROCESS");
        $this->posLogger->info("Get POS csv and merge..");
        $this->updatePosData->execute();
        $this->posLogger->info("Generate CPSS csv..");
        $this->createCsv->generateRealStoresData();
        $this->posLogger->info("Transfer generated csv to CPSS SFTP server..");
        $this->cpssTransferFiles->executeRealStore();
        $this->posLogger->info("END POS -> CPSS PROCESS");
    }
}
