<?php

namespace OnitsukaTiger\Logger;

class Logger extends \Gdx\CustomLogging\Logger\Monolog
{
    public function debug($message, array $context = []): void
    {
        parent::debug($message, $this->addContext($context));
    }

    public function info($message, array $context = []): void
    {
        parent::info($message, $this->addContext($context));
    }

    public function notice($message, array $context = []): void
    {
        parent::notice($message, $this->addContext($context));
    }

    public function warn($message, array $context = []): void
    {
        parent::warning($message, $this->addContext($context));
    }

    public function warning($message, array $context = []): void
    {
        parent::warning($message, $this->addContext($context));
    }

    public function err($message, array $context = []): void
    {
        parent::error($message, $this->addContext($context));
    }

    public function error($message, array $context = []): void
    {
        parent::error($message, $this->addContext($context));
    }

    public function crit($message, array $context = []): void
    {
        parent::critical($message, $this->addContext($context));
    }

    public function critical($message, array $context = []): void
    {
        parent::critical($message, $this->addContext($context));
    }

    public function alert($message, array $context = []): void
    {
        parent::alert($message, $this->addContext($context));
    }

    public function emerg($message, array $context = []): void
    {
        parent::emergency($message, $this->addContext($context));
    }

    public function emergency($message, array $context = []): void
    {
        parent::emergency($message, $this->addContext($context));
    }

    private function addContext($context)
    {
        $dbt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($dbt[1]) ? $dbt[1] : null;
        if ($caller) {
            $context['file'] = $caller['file'];
            $context['line'] = $caller['line'];
        }
        return $context;
    }
}
