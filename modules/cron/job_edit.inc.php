<?php

if ($this->mode=='setvalue') {
   global $prop_id;
   global $new_value;
   global $id;
   $this->setProperty($prop_id, $new_value, 1);   
   $this->redirect("?id=".$id."&view_mode=".$this->view_mode."&edit_mode=".$this->edit_mode."&tab=".$this->tab);
} 

if ($this->mode=='cmd') {
    global $data;
    $this->cmd($data);
}
  
if ($this->owner->name=='panel') {
  $out['CONTROLPANEL']=1;
}

$rec = SQLSelectOne("SELECT * FROM classes WHERE TITLE LIKE '" . DBSafe($this->nameClass) . "'");
$recCategory = SQLSelectOne("SELECT * FROM properties WHERE CLASS_ID = ".$rec['ID']." and TITLE LIKE 'Category'");
    
$sql = "select value from pvalues where PROPERTY_ID=".$recCategory["ID"];
$recCats=SQLSelect($sql);
$cats = array_count_values(array_map(function($element) {  return $element['value'];}, $recCats));
$categories = array();
foreach ($cats as $key => $value)
{
    $categories[] = array('NAME'=> $key, 'TITLE'=>$key, "TOTAL"=> $value);
}
$out['CATEGORIES']=$categories;

$rec=SQLSelectOne("SELECT * FROM objects WHERE ID='$id'");
$recOut = $rec;
if ($rec['ID']){
$recProperties=SQLSelect("SELECT *, (select TITLE from properties where properties.ID=pvalues.Property_id) as TITLE FROM pvalues where `OBJECT_ID`= '$id'");
foreach($recProperties as $property)
{
	if ($property['TITLE'] == "Enable") 
		$recOut["ENABLE"] = $property['VALUE'];
	if ($property['TITLE'] == "Crontab") 
		$recOut["CRONTAB"] = $property['VALUE'];
	if ($property['TITLE'] == "Category") 
		$recOut["CATEGORY"] = $property['VALUE'];
}    
$recCode=SQLSelectOne("SELECT * FROM `methods` WHERE `OBJECT_ID` ='$id' AND TITLE='Run'");
}

if(defined('SETTINGS_CODEEDITOR_TURNONSETTINGS')) {
	$out['SETTINGS_CODEEDITOR_TURNONSETTINGS'] = SETTINGS_CODEEDITOR_TURNONSETTINGS;
	$out['SETTINGS_CODEEDITOR_UPTOLINE'] = SETTINGS_CODEEDITOR_UPTOLINE;
	$out['SETTINGS_CODEEDITOR_SHOWERROR'] = SETTINGS_CODEEDITOR_SHOWERROR;
}

if ($this->mode=='update') {
  $ok=1;
  if ($this->tab=='') {
    global $title;
    //delete old job
    SQLExec("DELETE FROM jobs WHERE title='Cron_".DBSafe($rec['TITLE'])."'"); 
    $rec['TITLE']=$title;
    global $description;
    $rec['DESCRIPTION']=$description;
    global $crontab;
    global $enable;
    global $code;

	$old_code=$recCode['CODE'];
	$recCode['CODE'] = $code;
	
    global $category;
    
    //check name object
    if ($title!="" && $crontab!="")
    {
        $recDublicate=SQLSelectOne("SELECT * FROM objects WHERE TITLE='".DBSafe($title)."'");
        if ($recDublicate['ID']){
            if ($rec['ID']!=$recDublicate['ID'])
            {
                $ok=0;
                $out['ERR_MESSAGE']="Object name '".$title."' already exists!";
            }
        }
    }
    else{
        $ok=0;
        $out['ERR_MESSAGE']="<#LANG_FILLOUT_REQURED#>";    
    }
    //UPDATING RECORD
    if ($ok) {
		if ($rec['ID']) {
			SQLUpdate("objects", $rec); // update
		} 
		else {
			$class = SQLSelectOne("select ID from classes where TITLE='".DBSafe($this->nameClass)."';");
			$rec['CLASS_ID']=$class['ID'];
			$rec['ID']=SQLInsert("objects", $rec); // adding new record
			$id=$rec['ID'];
		} 
		if ($recCode['ID']) {
			if ($code != '') {
				$errors = php_syntax_error($code);
			
				if ($errors) {
					$out['ERR_LINE'] = preg_replace('/[^0-9]/', '', substr(stristr($errors, 'php on line '), 0, 18))-2;
					$errorStr = explode('Parse error: ', htmlspecialchars(strip_tags(nl2br($errors))));
					$errorStr = explode('Errors parsing', $errorStr[1]);
					$errorStr = explode(' in ', $errorStr[0]);
					$out['ERRORS'] = $errorStr[0];
					$out['ERR_FULL'] = $errorStr[0].' '.$errorStr[1];
					$out['ERR_OLD_CODE'] = $old_code;
					$error_code=1;
					$out['ERR']=1;
				} else {
					$error_code=0;
					SQLUpdate("methods", $recCode);
				}
			} else {
				$error_code=0;
				SQLUpdate("methods", $recCode);
			}
		}
		else {
			//create methods
			$recCode['OBJECT_ID']=$id;
			$recCode['TITLE']="Run";
			$recCode['CALL_PARENT']=1;
			$recCode['ID']=SQLInsert("methods", $recCode); // adding new record			
		}
    sg($rec['TITLE'].".Crontab",$crontab);
    if ($enable=='on')
			sg($rec['TITLE'].".Enable",1);
		else
			sg($rec['TITLE'].".Enable",0);	  
        sg($rec['TITLE'].".Category",$category);
		
      if($error_code == 0) $out['OK']=1;
    } else {
      $out['ERR']=1;
    }
	$recOut["ENABLE"] = $enable == 'on' ? 1 : 0;
	$recOut["CRONTAB"] = $crontab;
	$recOut["TITLE"] = $title;
	$recOut["DESCRIPTION"] = $description;
	$recOut["CATEGORY"] = $category;
  }
}
if ($rec['ID'])
    $recOut["CODE"] = $recCode['CODE'];
else
    $recOut["CODE"]="";

outHash($recOut, $out);
  
?>
