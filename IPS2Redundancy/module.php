<?
class IPS2Redundancy extends IPSModule
{
    	
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("System", 0);
	    	$this->RegisterPropertyString("IPAddress_primary", "127.0.0.1");
		$this->RegisterPropertyString("IPS_User_primary", "IPS-Benutzername");
		$this->RegisterPropertyString("IPS_Password_primary", "IPS-Passwort");
		$this->RegisterPropertyString("IPAddress_secondary", "127.0.0.1");
		$this->RegisterPropertyString("IPS_User_secondary", "IPS-Benutzername");
		$this->RegisterPropertyString("IPS_Password_secondary", "IPS-Passwort");
		
	}
	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv");
		
		$arrayOptionsSystem = array();
		$arrayOptionsSystem[] = array("label" => "Primärsystem", "value" => 0);
		$arrayOptionsSystem[] = array("label" => "Sekundärsystem", "value" => 1);
		
		$arrayElements[] = array("type" => "Select", "name" => "System", "caption" => "Systembestimmung", "options" => $arrayOptionsSystem );
		If ($this->ReadPropertyInteger("System") == 0) {
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
		}
  				
		$arrayActions = array();
		
		$arrayActions[] = array("type" => "Label", "label" => "Aktuell sind keine Funktionen definiert");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}    
	
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		//Status-Variablen anlegen
		$this->RegisterVariableBoolean("SystemStatus", "System Status", "~Alert.Reversed", 10);
          	$this->DisableAction("SystemStatus");
		IPS_SetHidden($this->GetIDForIdent("SystemStatus"), false);
		
		// Logging setzen
				
		// Registrierung für Nachrichten
	
		//$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->GetSystemStatus();
			$this->SetStatus(102);
		}
		else {
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
		

	private function GetSystemStatus()
	{
		If ($this->ReadPropertyBoolean("System") == false) {
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

	private function ConfigFile()
	{
		$Filepath = "/var/lib/symcon-redundancy";

		If(file_exists($Filepath."/symcon-redundancy.config")) {
			$content = file_get_contents($Filepath."/symcon-redundancy.config");
			echo $content;
		}
		else {
			If (is_dir($Filepath) == false) {
				mkdir($Filepath);
				$handle = fopen($Filepath."/symcon-redundancy.config", "w");
				fwrite ($handle, (boolval($this->ReadPropertyInteger("System")) ? 'true' : 'false'));
				fclose ($handle);
			}
		}
	}

}
?>
