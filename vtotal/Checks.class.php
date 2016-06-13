<?php


class vtotal_Checks extends core_Master
{


    /**
     * Заглавие
     */
    public $title = "Проверки във VirusTotal";


    /**
     * Кой има право да чете?
     */
    var $canRead = 'debug, admin';


    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    var $canList = 'debug, admin';


    /**
     * Кой може да разглежда сингъла на документите?
     */
    var $canSingle = 'debug, admin';


    /**
     * Кой може да го види?
     */
    var $canView = 'debug, admin';


    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'debug, admin';


    /**
     * @var Url нужно на VirusTotal за Api-то
     */
    private static $VTScanUrl = "https://www.virustotal.com/vtapi/v2/file/report";


    /**
     * Плъгини за зареждане
     */
    public $loadList = "plg_Created";


    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('firstCheck', 'datetime', 'caption=Последно от вирус тотал');
        $this->FLD('lastCheck', 'datetime', 'caption=Последно проверяване от системата');
        $this->FLD('filemanDataId', 'key(mvc=fileman_Files,select=id)', 'caption=Файл');
        $this->FLD('md5', 'varchar', 'caption=Хеш на съответния файл');
        $this->FLD('timesScanеd', 'int', 'caption=Пъти сканиран този файл, notNull ,value=0');
        $this->setDbUnique('filemanDataId');
    }


    /**
     * @param $VTResult обект от тип stdClass VirusTotalRespone
     * @return type_Percent колко опасен е съответния файл
     */
    public function getDangerRate($VTResult)
    {
        return $VTResult->positives/$VTResult->total;
    }


    /**
     * @param $md5Hash Хеш за проверка на файл през VirusTotal MD5
     * @return mixed
     * При неуспешно повикване връща int respone_code
     * При успешно повикване връща stdClass Обект от VirusTotal отговор
     */
    public static function VTGetReport($md5Hash)
    {
        $post = array(
            "resource" => $md5Hash,
            "apikey" => vtotal_Setup::get("API_KEY"),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, self::$VTScanUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $responce = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == '429') {
            return array(
                'response_code' => -3
            );
        } elseif ($httpCode == '403') {
            return array(
                'response_code' => -1
            );
        } else
            return json_decode($responce);
    }


    /**
     * Функция по крон, която проверява по 4 файла взети от fileman_Files и
     * ги поставя в този модел за инспекция
     */
    public function cron_MoveFilesFromFilemanLog()
    {
        $dangerExtensions = array(
            //Executable
            'EXE', 'PIF', 'APPLICATION', 'GADGET',
            'MSI', 'MSP', 'COM', 'SCR', 'HTA', 'CPL',
            'MSC', 'JAR', 'BAT', 'CMD', 'VB', 'VBS',
            'JS', 'JSE', 'WS', 'WSH', 'WSC', 'WSF',
            'PS1', 'PS1XML', 'PS2', 'PS2XML', 'PSC1',
            'PSC2', 'SCF', 'LNK', 'INF',
            //Macro
            'REG', 'DOC', 'XLS', 'PPT', 'DOCM',
            'DOTM', 'XLSM', 'XLTM', 'XLAM',
            'PPTM', 'POTM', 'PPAM', 'PPSM' , 'SLDM',
        );

        $query = fileman_Files::getQuery();
        $query->where("#dangerRate IS NULL");
        $query->orderBy("#createdOn", "DESC");

        $counter = 0;

        while($rec = $query->fetch()) {
            if($counter == vtotal_Setup::get("NUMBER_OF_ITEMS_TO_SCAN_BY_VIRUSTOTAL"))break;

            $extension = pathinfo($rec->name, PATHINFO_EXTENSION);

            if (!in_array(strtoupper($extension), $dangerExtensions)) {

                $cRec = $this->fetch("#filemanDataId = {$rec->dataId}");

                if($cRec) {
                    $rec->dangerRate = $cRec->dangerRate;
                    fileman_Files::save($rec, "dangerRate");
                } else {
                    $rec->dangerRate = 0;

                    $fQuery = fileman_Files::getQuery();
                    $fQuery->where("#dataId = {$rec->dataId}");

                    while ($fRec = $fQuery->fetch()) {
                        $extensionFRec = pathinfo($fRec->name, PATHINFO_EXTENSION);
                        if (!isset($fRec->dangerRate) && !in_array(strtoupper($extensionFRec), $dangerExtensions)) {
                            $fRec->dangerRate = 0;
                            fileman_Files::save($fRec, "dangerRate");
                        }
                    }
                }
            }
            elseif ($rec->dangerRate == NULL) {

                $vtotalFilemanDataObject = fileman_Data::fetch($rec->dataId);
                $checkFile = (object)array('filemanDataId' => $rec->dataId,
                    'firstCheck' => NULL, 'lastCheck' => NULL, 'md5'=> $vtotalFilemanDataObject->md5, 'timesScand' => 1);
                $result = $this->save($checkFile, NULL, "IGNORE");

                if(!$result) {
                    $cRec = $this->fetch("#filemanDataId = {$rec->dataId}");
                    $rec->dangerRate = $cRec->dangerRate;
                    fileman_Files::save($rec, "dangerRate");
                } else {
                    $counter++;
                }
            }

        }
    }


    /**
     * Функция по крон, която врема запосите от този модел и
     * прави определени функции според техния вид
     */
    public function cron_VTCheck()
    {
        $now = dt::now();
        $query = self::getQuery();
        $query->where("#lastCheck IS NULL");
        $query->orWhere("ADDDATE(#lastCheck, INTERVAL " . vtotal_Setup::get("BETWEEN_TIME_SCANS") . " SECOND) < '{$now}'");
        $query->orderBy("#createdOn", "DESC");
        $query->limit(vtotal_Setup::get("NUMBER_OF_ITEMS_TO_SCAN_BY_VIRUSTOTAL"));


        while($rec = $query->fetch())
        {
            $result = self::VTGetReport($rec->md5);

            if($rec->timesScanеd >= 2)
            {
                $fQuery = fileman_Files::getQuery();
                $fQuery->where("#dataId = {$rec->filemanDataId}");

                while($fRec = $fQuery->fetch())
                {
                    $fRec->dangerRate = -1;
                    fileman_Files::save($fRec);
                }
            }
            else{
                if($result == -1 || $result == -3 || $result->response_code == 0)
                {
                    $rec->timesScanеd = $rec->timesScanеd + 1;
                    $rec->lastCheck = $now;
                    $this->save($rec);
                }
                elseif ($result->response_code == 1)
                {
                    $dangerRate = $this->getDangerRate($result);
                    $rec->firstCheck = $result->scan_date;
                    $rec->lastCheck = $now;
                    $this->save($rec, 'firstCheck, lastCheck');

                    $query = fileman_Files::getQuery();
                    $query->where("#dataId = {$rec->filemanDataId}");

                    while($fRec = $query->fetch())
                    {
                        $fRec->dangerRate = $dangerRate;
                        fileman_Files::save($fRec);
                    }
                }
            }
        }
    }
}