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
}    
$recCode=SQLSelectOne("SELECT * FROM `methods` WHERE `OBJECT_ID` ='$id' AND TITLE='Run'");
}

if ($this->mode=='update') { 
  $ok=1;
  if ($this->tab=='') {
    global $title;
    //delete old job
    SQLExec("DELETE FROM jobs WHERE title='Cron_".$rec['TITLE']."'"); 
    $rec['TITLE']=$title;
    global $description;
    $rec['DESCRIPTION']=$description;
    global $crontab;
    global $enable;
    global $code;
    $recCode['CODE']=$code;
    
    //check name object
    if ($title!="" && $crontab!="")
    {
        $recDublicate=SQLSelectOne("SELECT * FROM objects WHERE TITLE='".$title."'");
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
			$class = SQLSelectOne("select ID from classes where TITLE='".$this->nameClass."';");
			$rec['CLASS_ID']=$class['ID'];
			$rec['ID']=SQLInsert("objects", $rec); // adding new record
			$id=$rec['ID'];
		} 
		if ($recCode['ID']) {
			SQLUpdate("methods", $recCode);
		}
		else {
			//create methods
			$recCode['OBJECT_ID']=$id;
			$recCode['TITLE']="Run";
			$recCode['CALL_PARENT']=1;
			$recCode['ID']=SQLInsert("methods", $recCode); // adding new record			
		}
		sg($rec['TITLE'].".Crontab",$crontab);
		if ($enable==1)
			sg($rec['TITLE'].".Enable",1);
		else
			sg($rec['TITLE'].".Enable",0);	  
      $out['OK']=1;
    } else {
      $out['ERR']=1;
    }
	$recOut["ENABLE"] = $enable;
	$recOut["CRONTAB"] = $crontab;
	$recOut["TITLE"] = $title;
	$recOut["DESCRIPTION"] = $description;
  }
}
if ($rec['ID'])
    $recOut["CODE"] = $recCode['CODE'];
else
    $recOut["CODE"]="";

outHash($recOut, $out);
  
?>
