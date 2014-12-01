<?php

// Дали знака '@' преди функция да предизвиква подтискане на грешките в нея?
defIfNot('CORE_ENABLE_SUPRESS_ERRORS', TRUE);

// Кои грешки да се показват?
defIfNot('CORE_ERROR_REPORTING_LEVEL', E_ERROR | E_PARSE | E_CORE_ERROR | E_STRICT | E_COMPILE_ERROR | E_WARNING);


/**
 * Клас 'core_Debug' ['Debug'] - Функции за дебъг и настройка на приложения
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Debug
{
	static $startMicroTime;

	static $lastMicroTime;

    static $debugTime = array();

    static $timers = array();
    
    /**
     * Дали дебъгера да записва
     * Това е един начин, да се изключат логовете на дебъгера
     */
    static $isLogging = TRUE;


    /**
     * Функция - флаг, че обектите от този клас са Singleton
     */
    function _Singleton() {}
    
 
    /**
     * Инициализираме таймерите
     */
    static function init()
    {
        if (!self::$startMicroTime) {
            self::$startMicroTime = core_Datetime::getMicrotime();
            self::$lastMicroTime = 0;
        	self::$debugTime[] = (object) array('start' => 0, 'name' => 'Начало');
        }
    }
    
    
    /**
     * Пускаме хронометъра за посоченото име
     */
    static function startTimer($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug()) return;
        
        self::init();
        
        if(!isset(self::$timers[$name])){
        	self::$timers[$name] = new stdClass();
        }
        
        self::$timers[$name]->start = core_Datetime::getMicrotime();
    }
    
    
    /**
     * Спираме хронометъра за посоченото име
     */
    static function stopTimer($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug()) return;
        
        self::init();
  
        if (self::$timers[$name]->start) {
            $workingTime = core_Datetime::getMicrotime() - self::$timers[$name]->start;
            self::$timers[$name]->workingTime += $workingTime;
            self::$timers[$name]->start = NULL;
        }
    }
    
    
    /**
     * Лог записи за текущия хит
     */
    static function log($name)
    {
        // Функцията работи само в режим DEBUG
        if(!isDebug() || !core_Debug::$isLogging) return;

        self::init();
        
        $rec = new stdClass();
        $rec->start = core_Datetime::getMicrotime() - self::$startMicroTime;
        $rec->name  = $name;

        self::$debugTime[] = $rec;
    }
    
    
    /**
     * Колко време е записано на това име?
     */
    static function getExecutionTime()
    {
        self::init();
        return number_format((core_Datetime::getMicrotime() - self::$startMicroTime), 5);
    }


    /**
     * Връща watch point лога
     */
    private static function getWpLog()
    {
        self::init();
        
        $html = '';

        if (count(self::$debugTime)) {
            self::log('End');

            $html .= "\n<div style='padding:5px; margin:10px; border:solid 1px #777; background-color:#FFFF99; display:table;color:black;'>" .
            "\n<div style='background-color:#FFFF33; padding:5px; color:black;'>Debug log</div><ul>";
 
            foreach (self::$debugTime as $rec) {
                $html .= "\n<li style='padding:15px 0px 15px 0px;border-top:solid 1px #cc3;'>" .  number_format(($rec->start ), 5) . ": " . htmlentities($rec->name, ENT_QUOTES, 'UTF-8');
            }
            
            $html .= "\n</ul></div>";
        }

        return $html;
    }


    /**
     * Връща измерванията на таймерите
     */
    private static function getTimers()
    {
        $html = '';

        if (count(self::$timers)) {
            $html .= "\n<div style='padding:5px; margin:10px; border:solid 1px #777; background-color:#FFFF99; display:table;color:black;'>" .
            "\n<div style='background-color:#FFFF33; padding:5px;color:black;'>Timers info</div><ol>";
            
            foreach (self::$timers as $name => $t) {
                $html .= "\n<li> '{$name}' => " . number_format($t->workingTime, 5) . ' sec.';
            }
            
            $html .= "\n</ol></div>";
        }
        
        return $html;
    }
    
    
    /**
     * Връща лога за текущия хит
     */
    static function getLog()
    {
        $html = self::getWpLog() . self::getTimers();

        return $html;
    }



    /**
     * Показва страница с дебъг информация
     */
    public static function getInfoPage($html, $stack, $type = 'Прекъсване')
    {
        // Ако сме в работен, а не тестов режим, не показваме прекъсването
        if (!isDebug()) {
            error_log("Breakpoint on line $breakLine in $breakFile");
            return;
        }
 
        $errHtml = self::getErrorHtml($html, $stack, $type);
        
        $errHtml .= core_Debug::getLog();
        
        if (!file_exists(EF_TEMP_PATH) && !is_dir(EF_TEMP_PATH)) {
    		mkdir(EF_TEMP_PATH, 0777, TRUE);    
		}
        
        // Поставяме обвивка - html документ
        $page = core_Html::wrapMixedToHtml($errHtml, TRUE);
        
        // Записваме за всеки случай и като файл
        file_put_contents(EF_TEMP_PATH . '/err.log.html', $page . "\n\n");

        return  $page;
    }



    public static function getTraceAsHtml($trace)
    {
        $trace = self::prepareTrace($trace);

        $result = '';

        foreach ($trace as $row) {
            if($i++ % 2) {
                $bgk = '#ffd';
            } else {
                $bgk = '#e8e8ff';
            }
            $result .= "\n<tr style='background-color:{$bgk}'>";
            foreach ($row as $cell) {
                $result .= '<td>' . $cell . '</td>';
            }
            $result .= '</tr>';
        }

        $result = '<div><table border="0" style="border-collapse: collapse;" cellpadding="5">'. $result . '</table></div>';

        return $result;
    }


    /**
     * Подготвя за показване данни от подаден масив от данни отговарящи на работен стек
     */
    private static function prepareTrace($trace)
    {
        $rtn = array();

        foreach ($trace as $count => $frame) {
            $file = 'unknown';
            if (!empty($frame['file'])) { 
                $line = self::getEditLink($frame['file'], $frame['line']);
                $file = self::getEditLink($frame['file']);
                $file =  $file . ' : ' . $line;
                if($rUrl = self::getGithubSourceUrl($frame['file'], $frame['line'])) {
                    $githubLink = sprintf('<a target="_blank" class="octocat" href="%s" title="Отвори в GitHub"><img valign="middle" src=%s /></a>&nbsp;', $rUrl, sbf('img/16/github.png'));
                } 
            } else {
                $githubLink = '';
            }
            $args = "";
            if (isset($frame['args'])) {
                $args = array();
                foreach ($frame['args'] as $arg) {
                    $args[] = self::formatValue($arg);
                }
                $args = join(", ", $args);
            }

            $rtn[] = array(
                $file,
                $githubLink,
                sprintf("%s(%s)",
                    isset($frame['class']) ?
                        $frame['class'].$frame['type'].$frame['function'] :
                        $frame['function'],
                     $args
                ),
            );
        }

        return $rtn;
    }


    /**
     * URL на сорс-код файл в централизирано репозитори
     *
     * @param string $file
     * @param int $line
     * @return string|boolean FALSE при проблем, иначе пълно URL
     */
    private static function getGithubSourceUrl($file, $line)
    {
        $file = str_replace(array("\\", EF_APP_PATH), array('/', ''), $file);

        if(defined('BGERP_GIT_BRANCH')) {
            $branch = BGERP_GIT_BRANCH;
        } else {
            $branch = 'dev';
        }

        $url = "https://github.com/bgerp/bgerp/blob/{$branch}{$file}#L{$line}";

        return $url;
    }


    private static function formatValue($v)
    {
        $result = '';

        if (is_string($v)) {
            $result = "'" . htmlentities($v, ENT_COMPAT | ENT_IGNORE, 'UTF-8') . "'";
        } elseif (is_array($v)) {
            $result = self::arrayToString($v);
        } elseif (is_null($v)) {
            $result = 'NULL';
        } elseif (is_bool($v)) {
            $result = ($v) ? "TRUE" : "FALSE";
        } elseif (is_object($v)) {
            $result = get_class($v);
        } elseif (is_resource($v)) {
            $result = get_resource_type($v);
        } else {
            $result = $v;
        }

        return $result;
    }

    private static function arrayToString($arr)
    {
        foreach ($arr as $i=>$v) {
            $arr[$i] = self::formatValue($v);
        }

        return '[' . implode(', ', $arr) . ']';
    }



    /**
     * Връща кода от php файла, около посочената линия
     * Прави базово форматиране
     *
     * @param string $file Името на файла, съдържащ PHP код
     * @param int    $line Линията, около която търсим 
     */
    public static function getCodeAround($file, $line, $range = 4)
    {
        $source = file_get_contents($file);

        $lines = explode("\n", $source);

        $from = max($line - $range-1, 0);
        $to   = min($line + $range, count($lines));
        $code = "";
        $padding = strlen($to);
        for($i = $from; $i < $to; $i++) {
            $l = str_pad($i+1, $padding, " ", STR_PAD_LEFT);
            $style = '';
            if($i+1 == $line) {
                $style = " style='background-color:#ff9;'";
            }
            $l = "<span{$style}><span style='border-right:solid 1px #999;padding-right:5px;'>$l</span> ". str_replace('<', '&lt;', rtrim($lines[$i])) . "</span>\n";
            $code .= $l;
        }
         
        return $code;
    }


    /**
     * Анализира стека и премахва тази, част от него, която е създадена след прекъсването
     *
     * @param array $stack
     *
     * @return array [$stack, $breakFile, $breakLine]
     */
    private static function analyzeStack($stack)
    {
        // Вътрешни функции, чрез които може да се генерира прекъсване
        $intFunc = array(
            'bp:debug',
            'errorhandler:core_debug',
            'bp:',
            'trigger:core_error',
            'error:',
            'expect:'
        );

        $breakpointPos = $breakFile = $breakLine = NULL;

        foreach ($stack as $i => $f) {
            if (in_array(strtolower($f['function'] . ':' . (isset($f['class']) ? $f['class'] : '')), $intFunc)) {
                $breakpointPos = $i;
            }
        }

        if (isset($breakpointPos)) {
            $breakLine = $stack[$breakpointPos]['line'];
            $breakFile = $stack[$breakpointPos]['file'];
            $stack = array_slice($stack, $breakpointPos+1);
        }

        return array($stack, $breakFile, $breakLine);
    }


    private static function renderStack($stack)
    {
        $result = '';

        foreach ($stack as $f) {
            $hash = md5($f['file']. ':' . $f['line']);
            $result .= "<hr><br><div id=\"{$hash}\">";
            $result .= core_Html::mixedToHtml($f);
            $result .= "</div>";
        }

        return $result;
    }


    /**
     * Подготвя HTML страница с дебъг информация за съответното състояние
     */
    public static function getDebugPage($state)
    {
        $data['tabContent'] = $data['tabNav'] = '';
        
        // Дъмп
        if(!empty($state['dump'])) {
            $data['tabNav'] .= ' <li><a href="#">Дъмп</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . core_Html::arrayToHtml($state['dump']) . '</div>';
        }

        // Подготовка на стека
        if(isset($state['stack'])) {
            list($stack, $breakFile, $breakLine) = self::analyzeStack($state['stack']);
            $data['tabNav'] .= ' <li><a href="#">Стек</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . self::getTraceAsHtml($stack) . '</div>';
        }

        // Подготовка на кода
        if(!isset($breakFile) && isset($state['breakFile'])) {
            $breakFile = $state['breakFile'];
        }
        if(!isset($breakLine) && isset($state['breakLine'])) {
            $breakLine = $state['breakLine'];
        }

        if(isset($breakFile) && isset($breakLine)) {
            $data['code'] = self::getCodeAround($breakFile, $breakLine);
        }
        
        // Контекст
        if(isset($state['contex'])) {
            $data['tabNav'] .= ' <li><a href="#">Контекст</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . core_Html::mixedToHtml($state['contex']) . '</div>';
        }
        
        // Лог
        if($wpLog = self::getwpLog()) {
            $data['tabNav'] .= ' <li><a href="#">Лог</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . $wpLog . '</div>';
        }
        
        // Времена
        if($timers = self::getTimers()) {
            $data['tabNav'] .= ' <li><a href="#">Времена</a></li>';
            $data['tabContent'] .= '<div class="simpleTabsContent">' . $timers . '</div>';
        }
        
        $data['httpStatusCode'] = $state['httpStatusCode'];
        $data['httpStatusMsg'] = $state['httpStatusMsg'];
        $data['background'] = $state['background'];

        if(isset($state['errTitle']) && $state['errTitle'][0] == '@') {
            $state['errTitle'] = substr($state['errTitle'], 1);
        }

        if(isset($state['errTitle'])) {
            $data['errTitle'] = $state['errTitle'];
        }

        $lineHtml = self::getEditLink($breakFile, $breakLine);
        $fileHtml = self::getEditLink($breakFile);
        
        if(isset($state['header'])) {
            $data['header'] = $state['header'];
        } else {
            $data['header'] = $state['errType'];
            if($breakLine) {
                $data['header'] .= " на линия <i>{$lineHtml}</i>";
            }
            if($breakFile) {
                $data['header'] .= " в <i>{$fileHtml}</i>";
            }
        }

        if(!empty($state['update'])) {
            $data['update'] = ht::createLink('Обновяване на системата', $state['update']);
        }


        $tpl = new core_NT(getFileContent('core/tpl/Debug.shtml'));

        $res = $tpl->render($data);

        return $res;        
    }
    

    /**
     * Рендира страница за грешка
     */
    private  static function getErrorPage($state)
    { 
        $tpl = new core_NT(getFileContent('core/tpl/Error.shtml'));
        if(isset($state['errTitle']) && $state['errTitle'][0] == '@') {
            $state['errTitle'] = $state['httpStatusMsgBg'];
        }

        if(!empty($state['update'])) {
            $state['update'] = ht::createLink('Обновяване', $state['update']);
        }

        $state['forward'] = ht::createLink('Към сайта', array('Index'));

        $page = $tpl->render($state); 
 
        return $page;        
    }



    /**
     * Показва съобщението за грешка и евентуално дебъг информация
     *
     * @param $errType   string Тип на грешката ('E_STRICT', 'E_WARNING', 'Несъответствие', 'Изключение', 'Грешка', ...)
     * @param $errMsg    string Съобщение за грешка. Ако започва с число - то се приема за httpStatusCode
     * @param $errDetail string Детайла информация за грешката. Показва се само в дебъг режим
     * @param $dump      array  Масив с данни, които да се покажат в дебъг режим
     * @param $stack     array  Стека на изпълнение на програмата
     * @param $contex    array  Данни, които показват текущото състояние на машината
     * @param $breakFile string Файл, където е възникнало прекъсването
     * @param $breakLine int    Линия на която е възникнало прекъсването
     */
    public static  function displayState($errType, $errTitle, $errDetail, $dump, $stack, $contex, $breakFile, $breakLine, $update = NULL)
    {
        $state = array( 'errType'   => $errType, 
                        'errTitle'  => $errTitle, 
                        'errDetail' => $errDetail, 
                        'dump'      => $dump, 
                        'stack'     => $stack, 
                        'contex'    => $contex, 
                        'breakFile' => $breakFile, 
                        'breakLine' => $breakLine,
                        'update'    => $update,
                        
            );


        // Изваждаме от титлата httpStatusCode, ако е наличен
        if($state['httpStatusCode'] = (int) $errTitle) {
            $pos = strpos($errTitle, $state['httpStatusCode']);
            $pos += strlen($state['httpStatusCode']);
            $state['errTitle'] = trim(substr($errTitle, $pos));
        } else {
            $state['httpStatusCode'] = 500;
        }

        list($state['httpStatusMsg'], $state['httpStatusMsgBg'], $state['background']) = self::getHttpStatusMsg($state['httpStatusCode']);
        
        if(isDebug() || defined('EF_DEBUG_LOG_PATH')) {
            $debugPage = core_Debug::getDebugPage($state);
        }

        if(!headers_sent()) { 
            header($_SERVER["SERVER_PROTOCOL"]. " " . $state['httpStatusCode'] . " " . $state['httpStatusMsg']);
            header('Content-Type: text/html; charset=UTF-8');

            echo isDebug() ? $debugPage : self::getErrorPage($state); 
        }
        
        // Ако е необходимо записваме дебъг информацията
        if(defined('EF_DEBUG_LOG_PATH')) {
            @file_put_contents(EF_DEBUG_LOG_PATH . '/err.log',  $debugPage, FILE_APPEND);
        }
    }

 
    /**
     * Прихваща състоянията на грешка и завършването на програмата (в т.ч. и аварийно)
     */
    static function setErrorWaching()
    {   
        // От тук нататък спираме показването на грешки
        ini_set('display_errors', '0');

        // рапортуваме само тези, които са зададени в конфиг. константа
        set_error_handler(array('core_Debug', 'errorHandler'));

        register_shutdown_function(array('core_Debug', 'shutdownHandler'));
    }


    /**
     * Функция - обработвач на състоянията на грешки
     */
    static function errorHandler($errno, $errstr, $breakFile, $breakLine, $errcontext)
    {   
        if(!($errno & CORE_ERROR_REPORTING_LEVEL)) return;

        if(CORE_ENABLE_SUPRESS_ERRORS && error_reporting() == 0) return;

        $errType = self::getErrorLevel($errno);

        self::displayState($errType, '500 @' . $errstr, $errstr, NULL, debug_backtrace(), $errcontext, $breakFile, $breakLine);

        die;
    }


    /**
     * Извиква се преди спиране на програмата. Ако има грешка - показва я.
     */
    static function shutdownHandler()
    {
 
        if ($error = error_get_last()) {
            
            if(!($error['type'] & CORE_ERROR_REPORTING_LEVEL) ) return;

            if(CORE_ENABLE_SUPRESS_ERRORS && error_reporting() == 0)  return;

            $errType = self::getErrorLevel($error['type']);
            
            self::displayState($errType, '500 @' . $error['message'], $error['message'], NULL, NULL, $_SERVER, $error['file'], $error['line']);

            die;
        }
    }


    /**
     * Връща новото на грешката
     */
    private static function getErrorLevel($errorCode)
    {
        switch($errorCode){
                case E_ERROR:
                    $name = 'E_ERROR';
                    break;
                case E_WARNING:
                    $name = 'E_WARNING';
                    break;
                case E_PARSE:
                    $name = 'E_PARSE ERROR';
                    break;
                case E_NOTICE:
                    $name = 'E_NOTICE';
                    break;
                case E_CORE_ERROR:
                    $name = 'E_CORE_WARNING';
                    break;
                case E_CORE_WARNING:
                    $name = 'E_CORE_WARNING';
                    break;
                case E_COMPILE_ERROR:
                    $name = 'E_COMPILE_ERROR';
                    break;
                case E_USER_ERROR:
                    $name = 'E_USER_ERROR';
                    break;
                case E_USER_WARNING:
                    $name = 'E_USER_WARNING';
                    break;
                case E_STRICT:
                    $name = 'E_STRICT';
                    break;
                case E_USER_NOTICE:
                    $name = 'E_USER_NOTICE';
                    break;
                case E_RECOVERABLE_ERROR:
                    $name = 'E_RECOVERABLE_ERROR';
                    break;
                case E_DEPRECATED:
                    $name = 'E_DEPRECATED';
                    break;
                case E_USER_DEPRECATED:
                    $name = 'E_USER_DEPRECATED';
                    break;
                default:
                    $name = "ERROR №{$errorCode}";
        }
 
        return $name;
    }
  
    /**
     * Връща вербалния http статус на ексепшъна
     */
    private static function getHttpStatusMsg($httpStatusCode)
    {
        switch($httpStatusCode) {
            case 400: 
                $httpStatusMsg = 'Bad Request';
                $httpStatusMsgBg = 'Грешна заявка';
                $background      = '#c00';
                break;
            case 401: 
                $httpStatusMsg   = 'Unauthorized';
                $httpStatusMsgBg = 'Недостатъчни права';
                $background      = '#c60';
                break;
            case 403: 
                $httpStatusMsg = 'Forbidden';
                $httpStatusMsgBg = 'Забранен достъп';
                $background      = '#c06';
                break;
            case 404: 
                $httpStatusMsg = 'Not Found';
                $httpStatusMsgBg = 'Липсваща страница';
                $background      = '#c33';
                break;
            default:
                $httpStatusMsg = 'Internal Server Error';
                $httpStatusMsgBg = 'Грешка в сървъра';
                $background      = '#d22';
                break;
        }
        
        return array($httpStatusMsg, $httpStatusMsgBg, $background);
    }


    /**
     * Връща, ако може линк за редактиране на файла
     */
    private static function getEditLink($file, $line = NULL, $title = NULL)
    {  
        if(!$title) {
            if(!$line) {
                //$line = 1;
                $title = $file;
            } else {
                $title = $line;
            }
        }

        if(defined('EF_DEBUG_EDIT_URL')) {
            $fromTo = array('FILE' => urlencode($file));
            if($line) {
                $fromTo['LINE'] = urlencode($line);
            }
            $tpl = new core_NT(EF_DEBUG_EDIT_URL);
            $editUrl =  $tpl->render($fromTo);
        }
        
        if($editUrl) {
            $title = "<a href='edit:{$editUrl}'>{$title}</a>";
        }

        return $title;
    }


}