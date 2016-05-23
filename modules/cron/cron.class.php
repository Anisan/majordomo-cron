<?php
/**
* Cron 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 21:05:35 [May 19, 2016])
*/
//
//
class cron extends module {
/**
* Cron
*
* Module class constructor
*
* @access private
*/
private $nameClass = "Cron";

function cron() {
  $this->name="cron";
  $this->title="Cron";
  $this->module_category="<#LANG_SECTION_SYSTEM#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}
/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
    if ($this->data_source=='telegram' || $this->data_source=='') {
        if ($this->view_mode=='' || $this->view_mode=='search_cron') {
           $this->search_cron($out);
        } 
        if ($this->view_mode=='user_edit') {
            $this->edit_user($out, $this->id);
        }
        if ($this->view_mode=='user_delete') {
          $this->delete_user($this->id);
          $this->redirect("?");
        } 
    }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}


 function search_cron(&$out) {
    $sql = "SELECT *, (select value from pvalues where PROPERTY_NAME= CONCAT(title,'.enable')) as ENABLE, ".
        " (select value from pvalues where PROPERTY_NAME= CONCAT(title,'.lastRun')) as LAST_RUN, ".
        " (select value from pvalues where PROPERTY_NAME= CONCAT(title,'.crontab')) as crontab, ".
        " (select runtime from jobs where jobs.TITLE = CONCAT('Cron_',`objects`.title)) as NEXT_RUN ".
        " FROM `objects` WHERE `CLASS_ID`=(select ID from classes where TITLE='Cron')";
    //echo $sql;
    $jobs=SQLSelect($sql);
    if ($jobs[0]['ID']) {
        $out['JOBS']=$jobs;
        //print_r ($jobs);
    }

 }
 
 function log($text)
 {
	echo  date("Y-m-d H:i:s ").$text."\n";
 }
 
function processCycle() {
	$this->log("Update jobs...");
	setGlobal('runCronUpdater',date('H:i')); 
	$this->updateJobs();
} 

function updateJobs()
{ 
	$objects=getObjectsByClass($this->nameClass);
	foreach($objects as $obj) {
		if (getGlobal($obj['TITLE'].".enable")=="1")
		{ 
			if ($this->jobExists($this->nameClass."_".$obj['TITLE'])==0)
			{
				$timestamp = $this->parse(getGlobal($obj['TITLE'].".crontab"));
				$date_time_array = getdate($timestamp);
				$hours = $date_time_array['hours'];
				$minutes = $date_time_array['minutes'];
				$month = $date_time_array['mon'];
				$day = $date_time_array['mday'];
				$year = $date_time_array['year'];
				$timestamp = mktime($hours,$minutes,0,$month,$day,$year);
				$this->log("Create job ".$this->nameClass."_".$obj['TITLE']);
				AddScheduledJob($this->nameClass."_".$obj['TITLE'],"callMethod('".$obj['TITLE'].".Run');",$timestamp,60);
			}
		}
	}
}

function jobExists($title) {
  $job=SQLSelectOne("SELECT ID FROM jobs WHERE TITLE LIKE '".DBSafe($title)."'");
  return (int)$job['ID'];
}
    
function parse($_cron_string,$_after_timestamp=null){
        $cron   = preg_split("/[\s]+/i",trim($_cron_string));
        $start  = empty($_after_timestamp)?time():$_after_timestamp;
        $date   = array(    'minutes'   =>$this->_parseCronNumbers($cron[0],0,59),
                            'hours'     =>$this->_parseCronNumbers($cron[1],0,23),
                            'dom'       =>$this->_parseCronNumbers($cron[2],1,31),
                            'month'     =>$this->_parseCronNumbers($cron[3],1,12),
                            'dow'       =>$this->_parseCronNumbers($cron[4],0,6),
                        );
                        //echo $date  ;
        // limited to time()+366 - no need to check more than 1year ahead
        for($i=0;$i<=60*60*24*366;$i+=60){
            if( in_array(intval(date('j',$start+$i)),$date['dom']) &&
                in_array(intval(date('n',$start+$i)),$date['month']) &&
                in_array(intval(date('w',$start+$i)),$date['dow']) &&
                in_array(intval(date('G',$start+$i)),$date['hours']) &&
                in_array(intval(date('i',$start+$i)),$date['minutes'])

                ){
                    return $start+$i;
            }
        }
        return null;
    }

function _parseCronNumbers($s,$min,$max){
        $result = array();

        $v = explode(',',$s);
        foreach($v as $vv){
            $vvv  = explode('/',$vv);
            $step = empty($vvv[1])?1:$vvv[1];
            $vvvv = explode('-',$vvv[0]);
            $_min = count($vvvv)==2?$vvvv[0]:($vvv[0]=='*'?$min:$vvv[0]);
            $_max = count($vvvv)==2?$vvvv[1]:($vvv[0]=='*'?$max:$vvv[0]);

            for($i=$_min;$i<=$_max;$i+=$step){
                $result[$i]=intval($i);
            }
        }
        ksort($result);
        return $result;
    } 
 

/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {

	//class
    $rec = SQLSelectOne("SELECT ID FROM classes WHERE TITLE LIKE '" . DBSafe($this->nameClass) . "'");
      
    if (!$rec['ID'])
    {
        $rec = array();
        $rec['TITLE'] = $this->nameClass;
        $rec['DESCRIPTION'] = 'Cron scheduler';
        $rec['ID'] = SQLInsert('classes', $rec);
    }
	//properties
	 
	//methods 
	 
	parent::install();
}

public function uninstall()
   {
      //SQLExec("delete from classes where title = '".$this->nameClass."'");
      
      parent::uninstall();
   }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgTWF5IDE5LCAyMDE2IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
