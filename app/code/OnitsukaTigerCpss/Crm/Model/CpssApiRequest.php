<?php

namespace OnitsukaTigerCpss\Crm\Model;

class CpssApiRequest extends \Cpss\Crm\Model\CpssApiRequest
{
    public function initLogger()
    {
        try {
            $writer = new \Zend_Log_Writer_Stream(BP . self::LOG_PATH);
            $logger = new \Zend_Log();
            $logger->addWriter($writer);
            $this->logger = $logger;
        } catch (\Exception $exception) {
        }
    }

    /**
     * execQuery
     *
     * @param  array $cpssQuery
     * @return array
     */
    protected function execQuery($cpssQuery)
    {
        $strBodys = [];
        $ch = curl_init();
        $header = [
            'X-CPSS-RESPONSETYPE: JSON'
        ];

        $this->logger->debug($cpssQuery);

        //STG authentication
        if ($this->helperData->getEnv() == 'STG') {
            $header = array_merge([$this->helperData->getAuthBearer()], $header);
        }

        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $cpssQuery['params']);
        curl_setopt($ch, CURLOPT_URL, $this->helperData->getCpssApiBaseUrl() . DIRECTORY_SEPARATOR . $cpssQuery['url']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $strBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch)['http_code'];

        $strBodys = $this->checkHtmlData($strBody);

        if ($httpCode != 200) {
            $strBodys['X-CPSS-Result'] = "999-999-999";
        }
        $result['X-CPSS-Result'] = '000-000-000';
        $strBodys['http_code'] = $httpCode;
        $this->logger->debug($strBodys);

        curl_close($ch);
        return $strBodys;
    }

    private function checkHtmlData($data)
    {
        $datas = preg_split("/\r\n?/", $data);
        $datas[0] = preg_replace('(/)', ': ', $datas[0]);

        $checkedHtml = [];
        $checkedHtml['Body'] = [];
        $i = 0;

        foreach ($datas as $data) {
            if (!strlen($data)) {
                $ii = 1;
                for ($counter = 1; ($counter + $i) < count($datas) && strlen($datas[$counter + $i]); $counter++) {
                    $checkedHtml['Body'][$counter - 1] = preg_split("/\t/", mb_convert_encoding($datas[$counter + $i], "UTF-8", "Shift-JIS"));
                }
                break;
            } else {
                $line = preg_split("/: +/", $data);
                $checkedHtml[$line[0]] = $line[1];
                $i++;
            }
        }

        return $checkedHtml;
    }
}
