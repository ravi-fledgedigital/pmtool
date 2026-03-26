<?php

namespace Seoulwebdesign\Base\Gateway\Command;

use Magento\Payment\Gateway\Command;
use Magento\Payment\Gateway\Command\CommandException;
use Magento\Payment\Gateway\CommandInterface;

/**
 * Class DoNothingCommand
 * @package Seoulwebdesign\Base\Gateway\Command
 */
class DoNothingCommand implements CommandInterface
{

    /**
     * Executes command basing on business object
     *
     * @param array $commandSubject
     * @return null|Command\ResultInterface
     * @throws CommandException
     */
    public function execute(array $commandSubject)
    {
    }
}
