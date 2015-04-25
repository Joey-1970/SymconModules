<?
class Enigma2 extends IPSModule
{
  public function __construct($InstanceID)
  {
    //Never delete this line!
    parent::__construct($InstanceID);
    //These lines are parsed on Symcon Startup or Instance creation
    //You cannot use variables here. Just static values.
    $this->RegisterPropertyString("IP", "");
    $this->RegisterPropertyBoolean("Open", "");
  }
  
  public function ApplyChanges()
  {
    //Never delete this line!
    parent::ApplyChanges();
    $this->RegisterVariableBoolean("Status", "Status", "~Switch");
    //$this->RegisterEventCyclic("UpdateTimer", "Automatische Aktualisierung", 15);
  }
  
  public function RequestInfo()
	{
		$IP = $this->ReadPropertyString("IP");
		SetValue($this->GetIDForIdent("Status"), PowerstateStatus($IP));
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
  // Toggelt den Standby-Modus
  public function ToggleStandby()
  {
    $powerstate = 0;
    $result = -1;
    $IP = $this->ReadPropertyString("IP");
   
   if ((Boolean) Sys_Ping($IP, 1000))
    {
       $xmlResult = new SimpleXMLElement(file_get_contents("http://$IP/web/powerstate?newstate=$powerstate"));
        if ($xmlResult->e2instandby == 'true')
        {
           $result = 1;
        }
        else
        {
            $result = 0;
        }
   }

  return $result;
  }
  
  //*************************************************************************************************************
  // Setzt das Gerät in DeepStandby
  public function DeepStandby()
  {
   $powerstate = 1;
   $result = false;
   $IP = $this->ReadPropertyString("IP");

   if ((Boolean) Sys_Ping($IP, 1000))
   	{
      $xmlResult = new SimpleXMLElement(file_get_contents("http://$IP/web/powerstate?newstate=$powerstate"));
      $result = (Boolean)$xmlResult->e2instandby;
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
  
  
  //*************************************************************************************************************
  // Setzt das Gerät in den Standby-Modus
  public function Standby()
  {
    $powerstate = 5;
    $result = -1;
    $IP = $this->ReadPropertyString("IP");

   if ((Boolean) Sys_Ping($IP, 1000))
    {
       $xmlResult = new SimpleXMLElement(file_get_contents("http://$IP/web/powerstate?newstate=$powerstate"));
        if ($xmlResult->e2instandby == 'true')
        {
           $result = 1;
        }
        else
        {
            $result = 0;
        }
   }
  return $result;
  }
  
  //*************************************************************************************************************
  // Weckt das Gerät aus dem Standby-Modus
  public function WakeUpStandby()
  {
   $powerstate = 4;
   $result = false;
   $IP = $this->ReadPropertyString("IP");

   if ((Boolean) Sys_Ping($IP, 1000))
    	{
		$xmlResult = new SimpleXMLElement(file_get_contents("http://$IP/web/powerstate?newstate=$powerstate"));
      		$result = (Boolean)$xmlResult->e2instandby;
   	}
  return $result;
  }
  
  //*************************************************************************************************************
  // Rebootes das Gerät
  public function Reboot()
  {
   $powerstate = 2;
   $result = false;
   $IP = $this->ReadPropertyString("IP");

   if ((Boolean) Sys_Ping($IP, 1000))
    	{
      $xmlResult = new SimpleXMLElement(file_get_contents("http://$IP/web/powerstate?newstate=$powerstate"));
      $result = (Boolean)$xmlResult->e2instandby;
   	}
  return $result;
  }
  
  //*************************************************************************************************************
  // Rebootet das Gerät
  public function Restart()
 {
   $powerstate = 3;
   $result = false;
   $IP = $this->ReadPropertyString("IP");

   if ((Boolean) Sys_Ping($IP, 1000))
    	{
      $xmlResult = new SimpleXMLElement(file_get_contents("http://$IP/web/powerstate?newstate=$powerstate"));
      $result = (Boolean)$xmlResult->e2instandby;
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
