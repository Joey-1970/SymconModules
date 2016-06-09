<?
class IPS2GPIO extends IPSModule
{
  public function __construct($InstanceID)
  {
    //Never delete this line!
    parent::__construct($InstanceID);
    //These lines are parsed on Symcon Startup or Instance creation
    //You cannot use variables here. Just static values.
    $this->RegisterPropertyString("IP", "");
    $this->RegisterPropertyBoolean("Open", "");
    $this->RegisterPropertyString("Model", "");
  }
  
  public function ApplyChanges()
  {
    //Never delete this line!
    parent::ApplyChanges();
    //$this->RegisterVariableBoolean("Status", "Status", "~Switch");
    //$this->RegisterEventCyclic("UpdateTimer", "Automatische Aktualisierung", 15);
  }
 
  //*************************************************************************************************************
  // Prüft ob die Box eingeschaltet ist
  public function PowerstateStatus()
  {
     $result = false;
  	$IP = $this->ReadPropertyString("IP");
  	$xml = simplexml_load_file("http://$IP/web/powerstate?.xml");
  	$wert = $xml->e2instandby;
  
  	if(strpos($wert,"false")!== false)
  		{
  		$result = true; // Bei "false" ist die Box eingeschaltet
  		}
  	else
  		{
  		$result = false;
  		}
  
  return $result;
  }
  
  
  
  
  
    //*************************************************************************************************************
  // Testfunktion
  public function Test()
  {
   $result = false;
   if ((Boolean) Sys_Ping($IP, 1000))
   	{
    	$result = true;
   	}
  return $result;
  }
  
  
  
  
 
 
  
  private function CreateCategoryByIdent($id, $ident, $name)
  {
    $cid = @IPS_GetObjectIDByIdent($ident, $id);
    if($cid === false)
    {
      $cid = IPS_CreateCategory();
      IPS_SetParent($cid, $id);
      IPS_SetName($cid, $name);
      IPS_SetIdent($cid, $ident);
    }
    return $cid;
  }
  
  private function CreateVariableByIdent($id, $ident, $name, $type, $profile = "")
  {
    $vid = @IPS_GetObjectIDByIdent($ident, $id);
    if($vid === false)
    {
      $vid = IPS_CreateVariable($type);
      IPS_SetParent($vid, $id);
      IPS_SetName($vid, $name);
      IPS_SetIdent($vid, $ident);
      if($profile != "")
      IPS_SetVariableCustomProfile($vid, $profile);
    }
    return $vid;
  }
  
  private function CreateInstanceByIdent($id, $ident, $name, $moduleid = "{485D0419-BE97-4548-AA9C-C083EB82E61E}")
  {
    $iid = @IPS_GetObjectIDByIdent($ident, $id);
    if($iid === false)
    {
      $iid = IPS_CreateInstance($moduleid);
      IPS_SetParent($iid, $id);
      IPS_SetName($iid, $name);
      IPS_SetIdent($iid, $ident);
    }
    return $iid;
  }
}
?>

