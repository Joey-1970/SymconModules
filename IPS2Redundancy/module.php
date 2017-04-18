<?
class Redundancy extends IPSModule
{
    	
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		
		$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyString("IPAddress_primary", "127.0.0.1");
		$this->RegisterPropertyString("IPS_User_primary", "IPS-Benutzername");
		$this->RegisterPropertyString("IPS_Password_primary", "IPS-Passwort");
		$this->RegisterPropertyString("IPAddress_secondary", "127.0.0.1");
		$this->RegisterPropertyString("IPS_User_secondary", "IPS-Benutzername");
		$this->RegisterPropertyString("IPS_Password_secondary", "IPS-Passwort");
		$this->RegisterPropertyString("MAC_primary", "");
		$this->RegisterPropertyInteger("Pruefzyklus", 60);
		$this->RegisterTimer("Pruefzyklus", 0, 'Redundancy_GetSystemStatus($_IPS["TARGET"]);');
		
	}
	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Pruefzyklus", "caption" => "Prüfzyklus (sek)");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Daten des Primärsystems:");
		$arrayElements[] = array("name" => "IPAddress_primary", "type" => "ValidationTextBox",  "caption" => "IP bzw. DynDNS inkl. Port");
		$arrayElements[] = array("type" => "Label", "label" => "Daten des IP-Symcon Fernzugriffs:");
		$arrayElements[] = array("name" => "IPS_User_primary", "type" => "ValidationTextBox",  "caption" => "IP-Symcon Benutzername");
		$arrayElements[] = array("name" => "IPS_Password_primary", "type" => "PasswordTextBox",  "caption" => "IP-Symcon Password");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Daten des Sekundärsystems:");
		$arrayElements[] = array("name" => "IPAddress_secondary", "type" => "ValidationTextBox",  "caption" => "IP bzw. DynDNS inkl. Port");
		$arrayElements[] = array("type" => "Label", "label" => "Daten des IP-Symcon Fernzugriffs:");
		$arrayElements[] = array("name" => "IPS_User_secondary", "type" => "ValidationTextBox",  "caption" => "IP-Symcon Benutzername");
		$arrayElements[] = array("name" => "IPS_Password_secondary", "type" => "PasswordTextBox",  "caption" => "IP-Symcon Password");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Die MAC dieses Systems lautet: ".$this->GetMAC());
		$arrayElements[] = array("type" => "Label", "label" => "Soll diese MAC-Adresse das Primärsystem markieren?");
		$arrayElements[] = array("type" => "Button", "label" => "Primärsystem setzen", "onClick" => 'Redundancy_SetMAC($id);');	
		$arrayActions = array();
		
		$arrayActions[] = array("type" => "Label", "label" => "Aktuell sind keine Funktionen definiert");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}    
	
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		//Status-Variablen anlegen
		$this->RegisterVariableBoolean("SystemFunction", "System Funktion", "", 10);
          	$this->DisableAction("SystemFunction");
		IPS_SetHidden($this->GetIDForIdent("SystemFunction"), false);

		$this->RegisterVariableBoolean("SystemStatus", "System Status", "~Alert.Reversed", 20);
          	$this->DisableAction("SystemStatus");
		IPS_SetHidden($this->GetIDForIdent("SystemStatus"), false);
		
		$this->RegisterVariableString("PrimaryMAC", "MAC Primärsystem", "", 30);
          	$this->DisableAction("PrimaryMAC");
		IPS_SetHidden($this->GetIDForIdent("PrimaryMAC"), false);
		
		$this->RegisterVariableString("ThisMAC", "MAC dieses Systems", "", 40);
          	$this->DisableAction("ThisMAC");
		IPS_SetHidden($this->GetIDForIdent("ThisMAC"), false);
		
		// Logging setzen
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SetTimerInterval("Pruefzyklus", ($this->ReadPropertyInteger("Pruefzyklus") * 1000));
			$this->GetSystemStatus();
			SetValueString($this->GetIDForIdent("PrimaryMAC"), $this->ReadPropertyString("MAC_primary"));
			SetValueString($this->GetIDForIdent("ThisMAC"), $this->GetMAC());
			If (GetValueString($this->GetIDForIdent("PrimaryMAC")) == GetValueString($this->GetIDForIdent("ThisMAC"))) {
				SetValueBoolean($this->GetIDForIdent("SystemFunction"), false);
			}
			else {
				SetValueBoolean($this->GetIDForIdent("SystemFunction"), true);
			}
			$this->SetStatus(102);
		}
		else {
			$this->SetTimerInterval("Pruefzyklus", 0);
			$this->SetStatus(104);
		}
		
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "SetpointTemperature":
	            	
	            	break;
	        
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case 10803:
				
				break;
			
		}
    	}
		

	public function GetSystemStatus()
	{	
		If (GetValueBoolean($this->GetIDForIdent("SystemFunction")) == false) {
			$User = $this->ReadPropertyString("IPS_User_secondary");
			$Password = $this->ReadPropertyString("IPS_Password_secondary");
			$IP = $this->ReadPropertyString("IPAddress_secondary");
		}
		else {
			$User = $this->ReadPropertyString("IPS_User_primary");
			$Password = $this->ReadPropertyString("IPS_Password_primary");
			$IP = $this->ReadPropertyString("IPAddress_primary");
		}
		$rpc = new JSONRPC("http://".$User.":".$Password."@".$IP."/api/");
		$Status = $rpc->IPS_GetKernelRunlevel();
		If ($Status == 10103) {
			SetValueBoolean($this->GetIDForIdent("SystemStatus"), true);
		}
		else {
			SetValueBoolean($this->GetIDForIdent("SystemStatus"), false);
		}
			
	}

	public function SetMAC()
	{
		IPS_LogMessage("Redundancy", $this->GetMAC());
		IPS_SetProperty($this->InstanceID, "MAC_primary", $this->GetMAC());
		IPS_ApplyChanges($this->InstanceID);
	}
	
	private function GetMAC()
	{
		$MAC = exec("cat /sys/class/net/eth0/address");
	return $MAC;
	}


}
?>