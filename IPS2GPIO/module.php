<?
class IPS2GPIO_IO extends IPSModule
{
  // Der Konstruktor des Moduls
  // Überschreibt den Standard Kontruktor von IPS
  public function __construct($InstanceID) 
  {
  // Diese Zeile nicht löschen
  parent::__construct($InstanceID);
 
            // Selbsterstellter Code
  }
  
  public function Create() 
  {
    // Diese Zeile nicht entfernen
    parent::Create();
 
    // Modul-Eigenschaftserstellung
    $this->RegisterPropertyString("IPAddress", "127.0.0.1");
    $this->RegisterPropertyBoolean("Open", false);
    $this->RegisterPropertyInteger("Model", 0);
  }
  
  public function ApplyChanges()
  {
    //Never delete this line!
    parent::ApplyChanges();
    //$this->RegisterVariableBoolean("Status", "Status", "~Switch");
    //$this->RegisterEventCyclic("UpdateTimer", "Automatische Aktualisierung", 15);
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

