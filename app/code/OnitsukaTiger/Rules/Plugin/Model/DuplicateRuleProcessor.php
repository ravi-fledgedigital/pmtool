<?php

namespace OnitsukaTiger\Rules\Plugin\Model;

use Amasty\Rgrid\Model\DuplicateRuleProcessor as DuplicateRuleProcessorSubject;

class DuplicateRuleProcessor
{
    /**
     * @param DuplicateRuleProcessorSubject $subject
     * @param \Closure $proceed
     * @param int $ruleId
     * @return mixed
     */
    public function aroundExecute(DuplicateRuleProcessorSubject $subject,\Closure $proceed,int $ruleId )
    {
        $result = $proceed($ruleId);
        $result->setRuleId($ruleId);
        return $result;
    }
}
