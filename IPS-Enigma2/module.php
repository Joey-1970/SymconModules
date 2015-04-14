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
    $this->RegisterEventCyclic("UpdateTimer", "Automatische Aktualisierung", 15);
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
