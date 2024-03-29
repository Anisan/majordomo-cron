<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
include_once(DIR_MODULES . 'cron/cron.class.php');
$cron_module = new cron();
$cron_module->getConfig();
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
$latest_check=0;
$checkEvery=60;
$cron_module->rebootJobs();
$cycleVarName='ThisComputer.'.str_replace('.php', '', basename(__FILE__)).'Run';
while (1)
{
  //setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
  if ($latest_check_cycle + 30 < time())
           {
       $latest_check_cycle = time();
       setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', $latest_check_cycle, 1);
           }
   if ((time()-$latest_check)>$checkEvery) {
    $latest_check=time();
    $cron_module->processCycle();
   }
   if (file_exists('./reboot') || IsSet($_GET['onetime']))
   {
      $db->Disconnect();
      exit;
   }
   sleep(1);
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
