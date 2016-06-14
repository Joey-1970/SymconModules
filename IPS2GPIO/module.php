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
  
  private function ConnectionTest()
  {
      If (Sys_Ping($this->ReadPropertyInteger("IPAddress"), 2000)) {
	Echo "PC erreichbar";
	$status = @fsockopen($this->ReadPropertyInteger("IPAddress"), 8888, $errno, $errstr, 10);
	if (!$status) {
		echo "Port geschlossen";
   		}
   	else {
   		fclose($status);
		echo "Port offen";
   		}
	}
	else {
		Echo "PC nicht erreichbar";
		}
  }
?>
