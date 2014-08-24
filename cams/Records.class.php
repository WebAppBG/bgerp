<?php



/**
 * Път до директория, където ще се съхраняват записите от камерите
 */
defIfNot('CAMS_VIDEOS_PATH', EF_UPLOADS_PATH . "/cams/videos");


/**
 * Път до директория, където ще се записват jpeg превютата
 */
defIfNot('CAMS_IMAGES_PATH', EF_UPLOADS_PATH . "/cams/images");


/**
 * Директория за flv файловете
 */
defIfNot('SBF_CAMS_FLV_DIR', "_cams/flv");


/**
 * Път до директория, където ще се записват flv файловете
 */
defIfNot('SBF_CAMS_FLV_PATH', EF_SBF_PATH . '/' . SBF_CAMS_FLV_DIR);


/**
 * Колко е продължителността на конвертирането на един клип в секунди
 */
defIfNot('CAMS_CLIP_TO_FLV_DURATION', round(cams_CLIP_DURATION / 30));


/**
 * Клас 'cams_Records' -
 *
 *
 * @category  bgerp
 * @package   cams
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class cams_Records extends core_Master
{
    
    
    /**
     * Зареждане на използваните мениджъри
     */
    var $loadList = 'plg_RowTools, cams_Wrapper, Cameras=cams_Cameras';
    
    
    /**
     * Заглавие
     */
    var $title = 'Записи от камери';
    
    
    /**
     * Полетата, които ще се ползват
     */
    var $listFields = 'id, thumb, cameraId, startTime, duration, playedOn, marked';
    
    
    /**
     * Права за писане
     */
    var $canWrite = 'ceo,cams, admin';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'ceo,admin,cams';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'ceo,admin,cams';
    
    
    /**
     * Права за четене
     */
    var $canRead = 'ceo,cams, admin';
    
    
    /**
     * Права за маркиране
     */
    var $canMark = 'ceo,cams,admin';


    /**
     * Права за размаркиране
     */
    var $canUnmark = 'ceo,cams,admin';
    
    // Ръчно не могат да се добавят записи
    //var $canEdit = 'no_one';
    //var $canAdd = 'no_one';
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('cameraId', 'key(mvc=cams_Cameras,select=title)', 'caption=Камера, mandatory');
        $this->FLD('startTime', 'datetime', 'caption=Начало');
        $this->FLD('duration', 'int', 'caption=Продължителност');
        $this->FLD('playedOn', 'datetime', 'caption=Гледан на');
        $this->FLD('marked', 'enum(no,yes)', 'caption=Маркиран');
        $this->FLD('params', 'text', 'caption=Параметри, input=none');
    }
    
    
    /**
     * Връща пътища до медийните файлове за $id-тия запис
     */
    function getFilePaths($startTime, $cameraId)
    {
        $baseName = dt::mysql2verbal($startTime, "d-m-y_H-i") . '-' . $cameraId;
        
        $fp = new stdClass();
        // Видео MP4 файл - суров запис от камерата с добро качество
        $fp->videoFile = CAMS_VIDEOS_PATH . "/{$baseName}.mp4";
        
        // Картинка към началото на записа
        $fp->imageFile = CAMS_IMAGES_PATH . "/{$baseName}.jpg";
        
        // Умалена картинка към началото на записа
        $fp->thumbFile = CAMS_IMAGES_PATH . "/{$baseName}_t.jpg";
        
        // Flash Video File за записа
        $hash = substr(md5(EF_SALT . $baseName), 0, 6);
        
        $fp->flvFile = SBF_CAMS_FLV_PATH . "/{$baseName}_{$hash}.flv";
        
        // Ако директорията за flv файловете не съществува,
        // записва в лога 
        if(!is_dir(SBF_CAMS_FLV_PATH)) {
            $this->log("sbf директорията за flv файловете не съществува - преинсталирайте cams.");
        }
        
        $fp->flvUrl = sbf(SBF_CAMS_FLV_DIR . "/{$baseName}_{$hash}.flv", '');
        
        return $fp;
    }
    
    
    /**
     * Връща началната картинка за посочения запис
     * Ако параметъра от заявката thumb е сетнат - връща умалена картинка
     */
    function act_StartJpg()
    {
        requireRole('cams, admin');
        
        $id = Request::get('id', 'int');
        
        expect($rec = $this->fetch($id));
        
        // Подготвяме пътищата до различните медийни файлове
        $fp = $this->getFilePaths($rec->startTime, $rec->cameraId);
        
        if(Request::get('thumb')) {
            $img = imagecreatefromjpeg($fp->thumbFile);
        } else {
            $img = imagecreatefromjpeg($fp->imageFile);
        }
        
        // Кеширане в браузъра в рамките на 1 ч.
        $cacheTime = 60 * 60;
        
        session_cache_limiter('none');
        
        // Then send Cache-Control: max-age=number_of_seconds and
        // optionally equivalent Expires: header.
        header('Cache-control: max-age=' . $cacheTime);
        header('Expires: ' . gmdate(DATE_RFC1123, time() + $cacheTime));
        
        // To get best cacheability, send Last-Modified header and reply with 
        // status 304 and empty body if browser sends If-Modified-Since header.
        header('Last-Modified: ' . gmdate(DATE_RFC1123, filemtime($fp->imageFile)));
        
        // This is cheating a bit (doesn't verify the date), but is valid as 
        // long as you don't mind browsers keeping cached file forever:
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            header('HTTP/1.1 304 Not Modified');
            die();
        }
        
        // Set the content type header - in this case image/jpeg
        header('Content-type: image/jpeg');
        
        // Output the image
        imagejpeg($img);
        
        die();
    }
    
    
    /**
     * Плейва посоченото видео (id на записа идва от GET)
     */
    function act_Single()
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $id = Request::get('id', 'int');
        
        expect($rec = $this->fetch($id));
        
        // Подготвяме пътищата до различните медийни файлове
        $fp = $this->getFilePaths($rec->startTime, $rec->cameraId);
        
        $data = new stdClass();
        // Настройваме параметрите на плеъра
        $data->url = $fp->flvUrl;
        $data->image = toUrl(array($this, 'StartJpg', $id));
        $data->toolbar = cls::get('core_Toolbar');
        
        // Ако имаме предишен запис - поставяме бутон към него
        if($idPrev = $this->getPrevRec($id)) {
            $data->toolbar->addBtn('« Предишен', array($this, 'Single', $idPrev));
        }
        
        // Ако имаме следващ запис - поставяме бутон към него
        if($idNext = $this->getNextRec($id)) {
            $data->toolbar->addBtn('Следващ »', array($this, 'Single', $idNext));
        }
        
        // Ако записа е маркиран, поставяме бутон за от маркиране и обратното
        if($rec->marked == 'yes') {
            $data->toolbar->addBtn('От маркиране', array($this, 'Unmark', $id));
        } else {
            $data->toolbar->addBtn('Маркиране', array($this, 'Mark', $id));
        }
/*        
        // Вземаме записа за камерата и подготвяме драйвера
        $camRec = $this->Cameras->fetch($rec->cameraId);
        $driver = cls::getInterface('cams_DriverIntf', $camRec->driver, $camRec->params);
*/
        // Подготвяме параметрите на записа        
        $params = json_decode($rec->params);
        
        $data->width = $params->width;
        $data->height = $params->height;
        
        // След колко секунди, очакваме клипа да бъде конвертиран?
        if(isset($rec->playedOn)) {
            $secondsToEnd = dt::mysql2timestamp($rec->playedOn) +
            $conf->CAMS_CLIP_TO_FLV_DURATION - time();
            
            // Времето може да бъде само положително
            $secondsToEnd = $secondsToEnd > 0 ? $secondsToEnd : 0;
        } else {
            $secondsToEnd = NULL;
        }
        
        if(!file_exists($fp->flvFile)) {
            if(!$secondsToEnd) {
                // Стартираме конвертирането на видеото към flv, ако това все още не е направено
                $this->convertToFlv($fp->videoFile, $fp->flvFile, $params);
                $this->log('Конвертиране към FLV', $rec->id);
                $secondsToEnd = $conf->CAMS_CLIP_TO_FLV_DURATION;
            }
            
            if($secondsToEnd === NULL) {
                $this->log('Правенo е конвертиране, но FLV файлът не се е появил', $rec->id);
                $secondsToEnd = $conf->CAMS_CLIP_TO_FLV_DURATION;
            }
        } else {
            if($secondsToEnd === NULL) {
                $this->log('Има FLV файл, без да е конвертиран', $rec->id);
                $secondsToEnd = $conf->CAMS_CLIP_TO_FLV_DURATION;
            }
        }
        
	    $data->startDelay = round($secondsToEnd * (filesize($fp->videoFile) / 100000000));
        
        $row = $this->recToVerbal($rec);
        
        // Получаваме класа на надписа
        $data->captionClass = $this->getCaptionClassByRec($rec);
        
        $camera = cams_Cameras::getTitleById($rec->cameraId);
        
        $data->caption = "{$camera}: $row->startTime";
        
        // Записваме, кога клипът е пуснат за разглеждане първи път
        if(empty($rec->playedOn)) {
            $rec->playedOn = dt::verbal2mysql();
            $this->save($rec, 'playedOn');
        } else {
            $data->caption .= ", видян на $row->playedOn";
        }
        
        if($rec->marked == 'yes') {
            $data->caption .= ", маркиран";
        }
        
        $data->duration = $conf->CAMS_CLIP_DURATION;
        
        // Рендираме плеъра
        $tpl = $this->renderSingle($data);
        
        $this->log("Single", $rec->id);
        
        return $this->renderWrapping($tpl);
    }
    
    
    /**
     * Рендиране на плеъра
     */
    function renderSingle_($data, $tpl = NULL)
    {
        
        $data->playerTpl = flvplayer_Embedder::render($data->url,
            $data->width,
            $data->height,
            $data->image,
            array('startDelay'=>$data->startDelay)
        );
        $tpl = new ET ('
            <div id=toolbar style="margin-bottom:10px;">[#toolbar#]</div>
            <div class="video-rec" style="display:table">
                <div class="[#captionClass#]" style="padding:5px;font-size:0.95em;">[#caption#]</div>
                [#playerTpl#]
            </div>
        ');
        
        // Какво ще показваме, докато плеъра се зареди
        setIfNot($data->content, "<img src='{$data->image}' style='width:{$data->width}px;height:{$data->height}px'>");
        
        $data->toolbar = $data->toolbar->renderHtml();
        
        // Поставяме стойностите на плейсхолдърите
        $tpl->placeObject($data);
        
        return $tpl;
    }
    
    
    /**
     * Конвертира указания файл (записан от този драйвер) към flv файл
     */
    function convertToFlv($mp4Path, $flvFile, $params)
    {

        $cmd = "ffmpeg -i $mp4Path -ar 44100 -ab 96 -qmax {$params->FPS} -f flv $flvFile < /dev/null > /dev/null 2>&1 &";
        
        $out = exec($cmd);
        
        debug::log("cmd = {$cmd}");
        debug::log("out = {$out}");
        
        return $out;
    }
    
    
    /**
     * Конвертира указания файл (записан от този драйвер) към flv файл
     */
    function convertToOgv($mp4Path, $ogvFile)
    {
        $cmd = "ffmpeg -i $mp4Path -ar 44100 -vcodec libtheora -acodec libvorbis -ab 96 -qmax 10 -f ogv $ogvFile < /dev/null > /dev/null 2>&1 &";
        
        $out = exec($cmd);
        debug::log("cmd = {$cmd}");
        debug::log("out = {$out}");
        
        return $out;
    }
    
    
    /**
     * Маркира посочения в id запис
     */
    function act_Mark()
    {
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('mark', $rec);
        
        $rec->marked = 'yes';
        
        $this->save($rec, 'marked');
        
        return new Redirect(array($this, 'Single', $id));
    }
    
    
    /**
     * От маркира посочения в id запис
     */
    function act_Unmark()
    {
        expect($id = Request::get('id', 'int'));
        
        expect($rec = $this->fetch($id));
        
        $this->requireRightFor('unmark', $rec);
        
        $fp = $this->getFilePaths($rec->startTime, $rec->cameraId);
        
        unlink($fp->flvFile);
        
        $rec->marked = 'no';
        
        $this->save($rec, 'marked');
        
        return new Redirect(array($this, 'Single', $id));
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function act_RecordVideo()
    {
        expect(isDebug());
        
        return $this->cron_RecordVideo();
    }
    
    
    /**
     * Стартира се периодично на всеки 5 минути и прави записи
     * на всички активни камери
     */
    function cron_RecordVideo()
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $camsQuery = $this->Cameras->getQuery();
        
        $camsQuery->where("#state = 'active'");
        
        $startTime = dt::timestamp2Mysql(round(time() / $conf->CAMS_CLIP_DURATION) * $conf->CAMS_CLIP_DURATION);
        
        $images = $clips = 0;
        
        while($camRec = $camsQuery->fetch()) {
            
            $fp = $this->getFilePaths($startTime, $camRec->id);
            
            $driver = cls::getInterface('cams_DriverIntf', $camRec->driver, $camRec->params);
            
            if(!$driver->isActive()) continue;
			
            $driver->captureVideo($fp->videoFile, $conf->CAMS_CLIP_DURATION + 7);

            if($imageStr = $driver->getPicture()) {
                
                imagejpeg($imageStr, $fp->imageFile);
                
                // Отложено ресайзване
                $toThumb[$fp->imageFile] = $fp->thumbFile;
                
                $shots++;
            }
            
            // Подготвяме и записваме записа;
            $rec = new stdClass();
            $rec->cameraId = $camRec->id;
            $rec->startTime = $startTime;
            $rec->duration = $conf->CAMS_CLIP_DURATION;
            $rec->marked = 'no';
            $rec->params = json_encode(array("FPS"=>$driver->getFPS(), "width"=>$driver->getWidth(), "height"=>$driver->getHeight()));
            
            $this->save($rec);
            
            $clips++;
        }
        
        // Преоразмеряваме големите картинки
        if(count($toThumb)) {
            foreach($toThumb as $src => $dest) {
                $thumb = thumbnail_Thumbnail::makeThumbnail($src, array(280, 210));
                imagejpeg($thumb, $dest, 85);
            }
        }
        
        return "Записани са {$clips} клипа.";
    }
    
    
    /**
     * Изпълнява се след подготовката на филтъра за листовия изглед
     * Обикновено тук се въвеждат филтриращите променливи от Request
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $camOpt = $mvc->getCameraOpts();
        
        $data->listFilter->setOptions('cameraId', $camOpt);
        
        $data->listFilter->FNC('select', 'enum(all=Всички,marked=Маркираните)');
        
        $data->listFilter->showFields = 'cameraId,startTime,select';
        
        $data->listFilter->toolbar->addSbBtn('Покажи');
        
        $data->listFilter->view = 'horizontal';
        
        // 1. Трябва да определим коя камера да се показва
        // 2. Трябва да определим от кое време нататък да се показва
        // 3. Дали само маркираните или всички
        $data->listFilter->input('cameraId,select', 'silent');
        
        $fRec = $data->listFilter->rec;
        
        // Ако не е указано, селектират се всички записи
        setIfNot($fRec->select, 'all');
        
        // Ако не е указанa, залагаме последно използваната камера
        setIfNot($fRec->cameraId, Mode::get('monLastUsedCameraId'));
        
        //Ако имаме cameraId
        if (isset($fRec->cameraId) && (!$mvc->Cameras->fetch($fRec->cameraId))) {
            $fRec->cameraId = NULL;
            Mode::setPermanent('monLastUsedCameraId', NULL);
        }
        
        // Ако няма последно използвана камера, вземаме първата активна от списъка
        if(!isset($fRec->cameraId)) {
            $fRec->cameraId = $mvc->Cameras->fetchField("#state = 'active'", 'id');
        }
        
        // Ако няма активна камера, вземаме първата
        if(!isset($fRec->cameraId)) {
            $fRec->cameraId = $mvc->Cameras->fetchField("1=1", 'id');
        }
        
        // Ако няма никаква камера, редиректваме към камерите, 
        // със съобщение за въведат поне една камера
        if(!isset($fRec->cameraId)) {
            core_Message::redirect("Моля въведете поне една камера", 'page_Error', NULL, array('cams_Cameras'));
        }
        
        // Задаваме, така получената камера, като последно използвана
        Mode::setPermanent('monLastUsedCameraId', $fRec->cameraId);
        
        if($fRec->select == 'marked') {
            $data->query->where("#marked = 'yes'");
        }
        
        $pageOpts = $mvc->getPageOpts($data->query, $fRec->cameraId, $firstPage);
        
        $data->listFilter->setOptions('startTime', $pageOpts);
        
        $data->listFilter->input('startTime', 'silent');
        
        setIfNot($fRec->startTime, dt::verbal2mysql($firstPage));
        
        $camTitle = $camOpt[$fRec->cameraId]->title;
        
        $startPage = dt::mysql2verbal($fRec->startTime);
        
        $startPageStamp = dt::mysql2timestamp($fRec->startTime);
        
        $startPageEndStamp = $startPageStamp + $mvc->getPageDuration();
        
        $data->startPageStamp = $startPageStamp;
        
        $startPageEnd = dt::mysql2verbal(dt::timestamp2mysql($startPageEndStamp));
        
        $camUrl = toUrl(array('cams_Cameras', 'Single', $fRec->cameraId));
        
        $data->title = "Записи на камера|* <a href='{$camUrl}'>{$camTitle}</a> |от" .
        "|* <span class=\"green\">{$startPage}</span> |до|* <span class=\"green\">{$startPageEnd}</span>";
        
        $startPageMysql = dt::verbal2mysql($startPage);
        
        $startPageEndMysql = dt::verbal2mysql($startPageEnd);
        
        $data->query->where("#startTime >=  '{$startPageMysql}' && #startTime < '{$startPageEndMysql}'");
        
        $data->query->where("#cameraId = {$fRec->cameraId}");
    }
    
    
    /**
     * Връща масива с опции за страници с видео-записи
     */
    function getPageOpts($query, $cameraId, &$firstPage)
    {
        $query = clone($query);
        
        $query->show('startTime,cameraId');
        
        $query->orderBy('#startTime', 'DESC');
        
        $pageOpts = $pageState = array();
        while($rec = $query->fetch()) {
            $page = $this->getPageByTime($rec->startTime);
            $pageOpts[$page] = $page;
            
            if($cameraId == $rec->cameraId) {
                $pageState[$page] = TRUE;
            }
        }
        
        $page = $this->getPageByTime(dt::verbal2mysql());
        $pageOpts[$page] = $page;
        
        arsort($pageOpts);
        
        $pageOptsVerbal = array();
        foreach($pageOpts as $page) {
            $pageVerbal = dt::mysql2verbal($page);
            
            $pageOptsVerbal[$page] = new stdClass();
            //            $pageOptsVerbal[$pageVerbal]->title = $pageVerbal;
            $pageOptsVerbal[$page]->title = $pageVerbal;
            
            if(!$pageState[$page]) {
            	$pageOptsVerbal[$pageVerbal] = new stdClass();
                $pageOptsVerbal[$pageVerbal]->attr = array('style' => 'color:#666');
            } else {
                if(!$firstPage) {
                    $firstPage = $pageVerbal;
                }
            }
        }
        
        return $pageOptsVerbal;
    }
    
    
    /**
     * Връща страницата според началното време на записа
     */
    function getPageByTime($startTime)
    {
        $begin = dt::mysql2timestamp('2000-01-01 00:00:00');
        $pageDuration = $this->getPageDuration();
        $startTimestamp = dt::mysql2timestamp($startTime);
        
        $page = dt::timestamp2Mysql($begin +
            floor(($startTimestamp - $begin) / $pageDuration) * $pageDuration);
        
        return $page;
    }
    
    
    /**
     * Връща опциите за камерите, като тези, които не записват са посивени
     */
    function getCameraOpts()
    {
        $camQuery = $this->Cameras->getQuery();
        
        while($camRec = $camQuery->fetch()) {
            
            $obj = new stdClass();
            
            $obj->title = $this->Cameras->getVerbal($camRec, 'title');
            
            if($camRec->state != 'active') {
                $obj->attr = array('style' => 'color:#666');
            }
            $cameraOpts[$camRec->id] = $obj;
        }
        
        return $cameraOpts;
    }
    
    
    /**
     * Връща id на предходния запис за същата камера
     */
    function getPrevRec($id)
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $rec = $this->fetch($id);
        $startStamp = dt::mysql2timestamp($rec->startTime);
        $prevStamp = $startStamp -$conf->CAMS_CLIP_DURATION;
        $prevTime = dt::timestamp2mysql($prevStamp);
        
        if($prevRec = $this->fetch("#startTime = '{$prevTime}' AND #cameraId = {$rec->cameraId}")) {
            return $prevRec->id;
        }
    }
    
    
    /**
     * Връща id на следващия запис за същата камера
     */
    function getNextRec($id)
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $rec = $this->fetch($id);
        $startStamp = dt::mysql2timestamp($rec->startTime);
        $nextStamp = $startStamp + $conf->CAMS_CLIP_DURATION;
        $nextTime = dt::timestamp2mysql($nextStamp);
        
        if($nextRec = $this->fetch("#startTime = '{$nextTime}' AND #cameraId = {$rec->cameraId}")) {
            return $nextRec->id;
        }
    }
    
    
    /**
     * Връща броя на клиповете, които се показват на една страница
     */
    function getClipsPerPage()
    {
    	$conf = core_Packs::getConfig('cams');
    	
        return Mode::is('screenMode', 'narrow') ?
        $conf->CAMS_CLIPS_PER_NARROW_PAGE :
        $conf->CAMS_CLIPS_PER_WIDE_PAGE;
    }
    
    
    /**
     * Връща броя на клиповете, които се показват на един ред
     */
    function getClipsPerRow()
    {
    	$conf = core_Packs::getConfig('cams');
    	
        return Mode::is('screenMode', 'narrow') ?
        $conf->CAMS_CLIPS_PER_NARROW_ROW :
        $conf->CAMS_CLIPS_PER_WIDE_ROW;
    }
    
    
    /**
     * Връща периода който обхваща една страница със записи в секунди
     */
    function getPageDuration()
    {
    	$conf = core_Packs::getConfig('cams');
    	
        return $conf->CAMS_CLIP_DURATION * $this->getClipsPerPage();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function prepareListRecs_(&$data)
    {
    	$conf = core_Packs::getConfig('cams');
    	
        while($rec = $data->query->fetch())
        {
            $startTimeTimestamp = dt::mysql2timestamp($rec->startTime);
            $number = ($startTimeTimestamp - $data->startPageStamp) / $conf->CAMS_CLIP_DURATION;
            $row = floor($number / $this->getClipsPerRow());
            $column = $number % $this->getClipsPerRow();
            
            $data->listRecs[$row][$column] = $rec;
            $data->listRows[$row][$column] = $this->recToVerbal($rec);
        }
    }
    
    
    /**
     * Рендира съдържанието - таблицата с превютата
     */
    function renderListTable_($data)
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $cols = $this->getClipsPerRow();
        $rows = $this->getClipsPerPage() / $this->getClipsPerRow();
        
        $html .= '<table cellspacing="3" bgcolor="white" class="video-rec">';
        
        for($r = 0; $r < $rows; $r++) {
            
            $html .= "<tr>";
            
            for($c = 0; $c < $cols; $c++) {
                
                if(isset($data->listRecs[$r][$c]->id)) {
                    $content = $data->listRows[$r][$c]->thumb;
                    $content = ht::createLink($content, array($this, 'Single', $data->listRecs[$r][$c]->id));
                } else {
                    $content = '';
                }
                
                if(!$data->listRows[$r][$c]->startTime) {
                    $startStamp = $data->startPageStamp + ($r * $cols + $c) * $conf->CAMS_CLIP_DURATION;
                    $startTime = dt::timestamp2mysql($startStamp);
                    $startVerbalTime = dt::mysql2verbal($startTime);
                } else {
                    $startVerbalTime = $data->listRows[$r][$c]->startTime;
                }
                
                $class = $this->getCaptionClassByRec($data->listRecs[$r][$c]);
                
                $date = "<div class='{$class}' style='border-bottom:solid 1px #ccc;'>" . $startVerbalTime . "</div>";
                
                $html .= "<td width=240 height=211 align=center valign=top bgcolor='#e8e8e8'>{$date}{$content}</td>";
            }
            
            $html .= "</tr>";
        }
        
        $html .= "</table>";
        
        return $html;
    }
    
    
    /**
     * Връща стила на надписа за съответния запис
     */
    function getCaptionClassByRec($rec)
    {
        if($rec->marked == 'yes') {
            $class = 'marked';
        } elseif($rec->playedOn) {
            $class = 'played';
        } else {
            $class = 'normal';
        }
        
        return $class;
    }
    
    
    /**
     * Изпълнява се след конвертирането към вербални стойности
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $attr['src'] = toUrl(array($mvc, 'StartJpg', $rec->id, 'thumb' => 'yes'));
        
        $row->thumb = ht::createElement('img', $attr);
    }
    
    
    /**
     * Изтрива стари записи, ако дисковото пространство е под лимита
     */
    function cron_DeleteOldRecords()
    {
    	$conf = core_Packs::getConfig('cams');
    	
        $freeSpace = disk_free_space(CAMS_VIDEOS_PATH);
        
        if($freeSpace < $conf->CAMS_MIN_DISK_SPACE) {
            
            $query = $this->getQuery();
            
            $query->orderBy('startTime');
            
            // Тези, които са под 1 ден не ги закачаме
            $before1day = dt::addDays(-1);
            
            $query->where("#startTime < '{$before1day}' AND #marked != 'yes'");
            
            $deleted = $delFiels = 0;
            
            while(disk_free_space(CAMS_VIDEOS_PATH) < $conf->CAMS_MIN_DISK_SPACE && ($rec = $query->fetch())) {
                
                if($rec->id) {
                    $this->delete($rec->id);
                    
                    $fPaths = $this->getFilePaths($rec->startTime, $rec->cameraId);
                    
                    if(@unlink($fPaths->videoFile)) $delFils++;
                    
                    if(@unlink($fPaths->imageFile)) $delFils++;
                    
                    if(@unlink($fPaths->thumbFile)) $delFils++;
                    
                    if(@unlink($fPaths->flvFile)) $delFils++;
                    
                    $deleted++;
                }
            }
            
            return "Изтрити са {$deleted} записа в базата и {$delFils} файла";
        }
        
        return "Не са изтрити записи от камерите, място все още има";
    }
    
    
    /**
     * Изпълнява се след начално установяване(настройка) на модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
    	$conf = core_Packs::getConfig('cams');
    	    	
        $dirs = array(
            CAMS_VIDEOS_PATH => "за съхраняване на записите",
            CAMS_IMAGES_PATH => "за съхраняване на JPG",
            SBF_CAMS_FLV_PATH => "за FLV за плейване",
        );
        
        foreach($dirs as $d => $caption) {
            
            if(!is_dir($d)) {
                if(mkdir($d, 0777, TRUE)) {
                    $msg = "<li style='color:green;'> Директорията <b>{$d}</b> е създадена ({$caption})";
                } else {
                    $msg = "<li style='color:red;'> Директорията <b>{$d}</b> не може да бъде създадена ({$caption})";
                }
            } else {
                $msg = "<li> Директорията <b>{$d}</b> съществува от преди ({$caption})";
            }
            
            $res .= $msg;
        }
        
        // Наглася Cron да стартира записването на камерите
        $rec = new stdClass();
        $rec->systemId = "record_video";
        $rec->description = "Правят се записи от камерите";
        $rec->controller = "cams_Records";
        $rec->action = "RecordVideo";
        $rec->period = (int) $conf->CAMS_CLIP_DURATION / 60;
        $rec->offset = 0;
        $res .= core_Cron::addOnce($rec);

        
        $rec = new stdClass();
        $rec->systemId = "delete_old_video";
        $rec->description = "Изтриване на старите записи от камерите";
        $rec->controller = "cams_Records";
        $rec->action = "DeleteOldRecords";
        $rec->period = (int) 2 * $conf->CAMS_CLIP_DURATION / 60;
        $rec->offset = 0;
        $res .= core_Cron::addOnce($rec);
    }
    
    
    /**
     * Метод за Cron за почистване на таблицата
     */
    function cron_RefreshRecords()
    {
        return $this->refrefRecords();
    }
    
}