<?php



/**
 * Клас 'core_Pager' - Отговаря за странирането на резултати от заявка
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
class core_Pager extends core_BaseClass
{
    
    
    /**
     * Мениджърът, който го използва
     */
    var $mvc;
    
    
    /**
     * Колко са общо резултатите
     */
    var $itemsCount;
    
    
    /**
     * Колко общо страници с резултати има
     */
    var $pagesCount;
    
    
    /**
     * Пореден номер на първия резултат за текущата страница
     */
    var $rangeStart;
    
    
    /**
     * Пореден номер на последния резултат за текущата страница
     */
    var $rangeEnd;
    
    
    /**
     * Колко записа съдържа една страница
     */
    var $itemsPerPage;
    
    
    /**
     * Номера на текущата страница
     */
    var $page;
    
    
    /**
     * На колко страници отстояние от първата и последната да оставя по една междинна
     */
    var $minPagesForMid = 20;
    
    
    /**
     * До колко страници около текущата да показва?
     */
    var $pagesAround;
    

    /**
     * Брояч за текущия резултат
     */
    var $currentResult;


    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        setIfNot($this->itemsPerPage, 20);
        setIfNot($this->pageVar, 'P');
        if(Mode::is('screenMode', 'narrow')) {
            setIfNot($this->pagesAround, 1);
        } else {
            setIfNot($this->pagesAround, 2);
        }

    }
    
    
    /**
     * Изчислява индексите на първия и последния елемент от текущата страница и общия брой страници
     */
    function calc()
    {   
        setIfNot($this->page, Request::get($this->pageVar, 'int'), 1);
        $this->rangeStart = NULL;
        $this->rangeEnd = NULL;
        $this->pagesCount = NULL;
        
        if (!($this->itemsCount >= 0))
        $this->itemsPerPage = 0;
        
        $maxPages = max(1, round($this->itemsCount / $this->itemsPerPage));
        
        if ($this->page > $maxPages) {
            $this->page = $maxPages;
        }
        
        $this->pagesCount = round($this->itemsCount / $this->itemsPerPage);
        
        if ($this->itemsCount > 0 && $this->pagesCount == 0) {
            $this->pagesCount = 1;
        }
        $this->rangeStart = 0;
        $this->rangeEnd = $this->itemsCount;
        
        $this->rangeStart = $this->itemsPerPage * ($this->page - 1);
        $this->rangeEnd = $this->rangeStart + $this->itemsPerPage;
        
        if (isset($this->itemsCount)) {
            if ($this->page == $this->pagesCount) {
                $this->rangeEnd = $this->itemsCount;
            } else {
                $this->rangeEnd = min($this->rangeEnd, $this->itemsCount);
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getItemsCount()
    {
        return $this->itemsCount;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getPagesCount()
    {
        return $this->pagesCount;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getPage()
    {
        return $this->page;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getRangeStart()
    {
        return $this->rangeStart;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getRangeLength()
    {
        return $this->getRangeEnd() - $this->getRangeStart();
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getRangeEnd()
    {
        return $this->rangeEnd;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function setLimit(&$query)
    {
        $q = clone ($query);
        
        $wh = $q->getWhereAndHaving();
        $wh = $wh->w . ' ' . $wh->h;
        $this->itemsCount = PHP_INT_MAX;
        $this->calc();

        if((!Request::get('V') && !strpos($wh, "`doc_containers`.`search_keywords`)") && $this->rangeStart < 10000) || Request::get('V') == 1) {
            $qCnt = clone ($query);
            $qCnt->orderBy = array();

            $qCnt->show('id');
            $this->itemsCount = $qCnt->count();
            $this->calc();
            if (isset($this->rangeStart) && isset($this->rangeEnd)) {
                $q->limit($this->rangeEnd - $this->rangeStart);
                $q->startFrom($this->rangeStart);
                $q->show('id');
                $q->select();
                while($rec = $q->fetch()) {
                    $ids[] = $rec->id;
                }
            }

            if(count($ids)) {

                $ids = implode(',', $ids);

                $query->where("#id IN ($ids)");
            } else {
                $this->itemsCount = 0;
                $this->calc();
                $query->limit(0);
            } 
        } elseif((!Request::get('V')) || Request::get('V') == 2) {
            $q->show('id');
            $q->addOption('SQL_CALC_FOUND_ROWS');

            if (isset($this->rangeStart) && isset($this->rangeEnd)) {
                $q->limit(floor(1.5*($this->rangeEnd - $this->rangeStart) + 0.6));
                $q->startFrom($this->rangeStart); 
                $q->select();
                while($rec = $q->fetch()) {
                    $ids[] = $rec->id;
                }
            }
            
            if(count($ids)) {
                $dbRes = $q->mvc->db->query("SELECT FOUND_ROWS()");
                $cntArr = $q->mvc->db->fetchArray($dbRes);
                $this->itemsCount  = array_shift($cntArr);
                $this->calc();

                $ids = array_slice($ids, 0, $this->rangeEnd-$this->rangeStart);

                $ids = implode(',', $ids);

                $query->where("#id IN ($ids)");
            } else {
                $this->itemsCount = 0;
                $this->calc();
                $query->limit(0);
            }
        } elseif(Request::get('V') == 3) {
            $q = clone ($query);

            $this->itemsCount = 100000000;
            $this->calc();
            if (isset($this->rangeStart) && isset($this->rangeEnd)) {
                $q->limit(1000);
                $q->startFrom($this->rangeStart); 
                $cnt = $this->rangeStart + $q->select();
                $i = 0;
                while(($rec = $q->fetch()) && $i++ < ($this->rangeEnd-$this->rangeStart)) {
                    $ids[] = $rec->id;
                }
            }
            
            $this->itemsCount  = $cnt;  
            $this->calc();
            
            if(count($ids)) {
                $ids = implode(',', $ids);
                $query->where("#id IN ($ids)");
            } else {
                $query->limit(0);
            }
        }
    }


    /**
     * Връща линкове за предишна и следваща страница, спрямо текущата
     */
    function getPrevNext($nextTitle, $prevTitle)
    {
        $link = self::getUrl();

        $p = $this->getPage();
        $cnt = $this->getPagesCount();

        if($p > 1) {
            $link[$this->pageVar] = $p-1;
            $prev = "<a href=\"" . toUrlEsc($link) . "\" class=\"pager\">{$prevTitle}</a>";
        }

        if($p < $cnt) {
            $link[$this->pageVar] = $p+1;
            $next = "<a href=\"" . toUrlEsc($link) . "\" class=\"pager\">{$nextTitle}</a>";
        }

        return "<div class=\"small\" style='margin-bottom: 10px;'><div style='float:left;'>{$next}</div><div style='float:right;'>{$prev}</div><div class='clearfix21'></div> </div>";
    }
    
    
    /**
     * Рендира HTML кода на пейджъра
     */
    function getHtml($link = NULL)
    { 
        if ($this->url) {
            $link = $this->url;
        } else { 
            $link = toUrl(self::getUrl());
        }
        
        $start = $this->getPage() - $this->pagesAround;
        
        if ($start < 5) {
            $start = 1;
        }
        
        $end = $this->getPage() + $this->pagesAround;
        
        if (($end > $this->getPagesCount()) || ($this->getPagesCount() - $end) < 5) {
            $end = $this->getPagesCount();
        }
        
        $html = '';
        $pn = tr('Страница') . ' #';
        if ($start < $end) {
            //Ако имаме страници, които не се показват в посока към началото, показваме <
            if ($this->getPage() > 1) {
                if ($start > 1) {
                    $html .= "<a href=\"" . htmlspecialchars(Url::change($link, array($this->pageVar => 1)), ENT_QUOTES, "UTF-8") . "\" class=\"pager\" title=\"{$pn}1\">1</a>";
                    $mid = round($start / 2);
                    $html .= "<a href=\"" . htmlspecialchars(Url::change($link, array($this->pageVar => $mid)), ENT_QUOTES, "UTF-8") . "\" class=\"pager\" title='{$pn}{$mid}'>...</a>";
                   
                }
            }
            
            do {
                $sel = "class=\"pager\"";
                
                if ($start == $this->getPage()) {
                    $sel = "class='pager pagerSelected'";
                }
                $html .= "<a href=\"" . htmlspecialchars(Url::change($link, array($this->pageVar => $start)), ENT_QUOTES, "UTF-8") . "\"  $sel title='{$pn}{$start}'>{$start}</a> ";
            } while ($start++ < $end);
            
            //Ако имаме страници, които не се показват в посока към края, показваме >
            if ($this->getPage() < $this->getPagesCount()) {
                if ($end < $this->getPagesCount()) {
                    $mid = $this->getPagesCount() - $end;
                    $mid = round($mid / 2) + $end;
                    $html .= "<a href=\"" . htmlspecialchars(Url::change($link, array($this->pageVar => $mid)), ENT_QUOTES, "UTF-8") . "\" class=\"pager\" title='{$pn}{$mid}'>...</a>";
                    $last = $this->getPagesCount();
                    $html .= "<a href=\"" . htmlspecialchars(Url::change($link, array($this->pageVar => $this->getPagesCount())), ENT_QUOTES, "UTF-8") .
                    "\" class=\"pager\" title='{$pn}{$last}'>{$last}</a>";
                }
            }
        }
        
        $tpl = new ET($html ? "<div class='pages'>$html</div>" : "");
        
        return $tpl;
    }


    /**
     * Връща текущото URL
     */
    function getUrl()
    {  
        $url = getCurrentUrl(); 
        if(is_array($this->addToUrl)) { 
            $url = $url + $this->addToUrl; 
        }

        return $url;
    }


    /**
     * Проверява дали текущия резултат трябва да се показва
     */
    public function isOnPage()
    {
        if(!$this->rangeStart) {
            $this->calc();
        }

        if(!$this->currentResult) {
            $this->currentResult = 1;
        } else {
            $this->currentResult++;
        }
 
        if($this->currentResult <= $this->rangeStart || $this->currentResult > $this->rangeEnd) {
 
            return FALSE;
        }

        return TRUE;
    }


    /**
     * Задава стойността на контролната променлива за пейджъра
     */
    function setPageVar($masterClass = NULL, $id = NULL, $detailClass = NULL)
    {
        $this->pageVar =  self::getPageVar($masterClass, $id, $detailClass);
    }


    /**
     * Връща името на променливата използвана за отбелязване на текущата страница
     */
    static function getPageVar($masterClass = NULL, $id = NULL, $detailClass = NULL)
    {
        $pageVar = 'P';

        if($masterClass) {
            $pageVar .= "_{$masterClass}";
        }
        if($id) {
            $pageVar .= "_{$id}";
        }
        if($detailClass) {
            $pageVar .= "_{$detailClass}";
        }

        return $pageVar;
    }
}