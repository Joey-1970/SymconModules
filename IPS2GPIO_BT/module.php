<?
    // Klassendefinition
    class IPS2GPIO_BT extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Messzyklus", 0);
	}
	
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
		// Diese Zeile nicht löschen.
		parent::Create();
		$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterPropertyString("MAC0", " ");
		$this->RegisterPropertyBoolean("LoggingMAC0", false);
		$this->RegisterPropertyString("MAC1", " ");
		$this->RegisterPropertyBoolean("LoggingMAC1", false);
		$this->RegisterPropertyString("MAC2", " ");
		$this->RegisterPropertyBoolean("LoggingMAC2", false);
		$this->RegisterPropertyString("MAC3", " ");
		$this->RegisterPropertyBoolean("LoggingMAC3", false);
		$this->RegisterPropertyString("MAC4", " ");
		$this->RegisterPropertyBoolean("LoggingMAC4", false);
		$this->RegisterTimer("Messzyklus", 0, 'I2GBT_Measurement($_IPS["TARGET"]);');
		
		 //Status-Variablen anlegen
	         $this->RegisterVariableBoolean("MAC0Connect", "MAC 1", "~Switch", 10);
		 $this->DisableAction("MAC0Connect");
		 $this->RegisterVariableString("MAC0Name", "MAC 1 Name", "", 20);
                 $this->DisableAction("MAC0Name");
                 $this->RegisterVariableBoolean("MAC1Connect", "MAC 2", "~Switch", 30);
		 $this->DisableAction("MAC1Connect");
		 $this->RegisterVariableString("MAC1Name", "MAC 2 Name", "", 40);
                 $this->DisableAction("MAC1Name");
		 $this->RegisterVariableBoolean("MAC2Connect", "MAC 3", "~Switch", 50);
		 $this->DisableAction("MAC2Connect");
		 $this->RegisterVariableString("MAC2Name", "MAC 3 Name", "", 60);
                 $this->DisableAction("MAC2Name");
		 $this->RegisterVariableBoolean("MAC3Connect", "MAC 4", "~Switch", 70);
		 $this->DisableAction("MAC3Connect");
		 $this->RegisterVariableString("MAC3Name", "MAC 4 Name", "", 80);
                 $this->DisableAction("MAC3Name");
		 $this->RegisterVariableBoolean("MAC4Connect", "MAC 5", "~Switch", 90);
		 $this->DisableAction("MAC4Connect");
		 $this->RegisterVariableString("MAC4Name", "MAC 5 Name", "", 100);
                 $this->DisableAction("MAC4Name");
                 $this->RegisterVariableBoolean("Summary", "Summary", "~Switch", 110);
		 $this->DisableAction("Summary");
		 $this->SetBuffer("Summary", false);
      	}
	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
				
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
 		
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der MAC-Adresse(n) (Format 00:00:00:00:00:00)");
		
		
		for ($i = 0; $i <= 4; $i++) {
		    	$arrayElements[] = array("type" => "ValidationTextBox", "name" => "MAC".$i, "caption" => "MAC-Adresse ".($i + 1)); 
			$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingMAC".$i, "caption" => "Logging aktivieren"); 
		}	
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 30 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
				
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	} 
	
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                 // Diese Zeile nicht löschen
                 parent::ApplyChanges();
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {	
			// Logging setzen
			for ($i = 0; $i <= 4; $i++) {
				AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("MAC".$i."Connect"),  $this->ReadPropertyBoolean("LoggingMAC".$i)); 
				SetValueString($this->GetIDForIdent("MAC".$i."Name"), "");
				SetValueBoolean($this->GetIDForIdent("MAC".$i."Connect"), false);
			} 
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);


			//ReceiveData-Filter setzen
			$Filter = '(.*"Function":"get_start_trigger".*|.*"InstanceID":'.$this->InstanceID.'.*)';
			$this->SetReceiveDataFilter($Filter);
			
			$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->Measurement();
				$this->SetStatus(102);
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				$this->SetStatus(104);
			}
		}
		else {
			$this->SetTimerInterval("Messzyklus", 0);
			$this->SetStatus(104);
		}
        }
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	       
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			  case "get_start_trigger":
			   	$this->ApplyChanges();
				break;
	 	}
 	}
	// Beginn der Funktionen

	// Führt eine Messung aus
	public function Measurement()
	{	
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Measurement", "Ausfuehrung", 0);
			$CommandArray = Array();
			for ($i = 0; $i <= 4; $i++) {
				If (filter_var(trim($this->ReadPropertyString("MAC".$i)), FILTER_VALIDATE_MAC)) {
					$CommandArray[$i] = "hcitool name ".$this->ReadPropertyString("MAC".$i);
				}
			}	
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray), "CommandNumber" => 0, "IsArray" => true )));
			
			If (is_array(unserialize($Result)) ) { 
				$ResultArray = unserialize($Result);
				$this->SetBuffer("Summary", 0);
				for ($i = 0; $i < Count($ResultArray); $i++) {
					//IPS_LogMessage("IPS2GPIO BT-Connect", $ResultArray[key($ResultArray)] );
					If (trim($ResultArray[key($ResultArray)]) <> trim("Device is not available.")) {
						SetValueString($this->GetIDForIdent("MAC".key($ResultArray)."Name"), trim($ResultArray[key($ResultArray)]));
						$this->SendDebug("Measurement", "Antwort -".trim($ResultArray[key($ResultArray)])."-", 0);
						if (strlen(trim($ResultArray[key($ResultArray)])) > 0) {
							SetValueBoolean($this->GetIDForIdent("MAC".key($ResultArray)."Connect"), true);
							$this->SetBuffer("Summary", 1);
						}
						else {
							SetValueBoolean($this->GetIDForIdent("MAC".key($ResultArray)."Connect"), false);
						}
						Next($ResultArray);
					}
					else {
						SetValueString($this->GetIDForIdent("MAC".key($ResultArray)."Name"), "");
						SetValueBoolean($this->GetIDForIdent("MAC".key($ResultArray)."Connect"), false);
					}

				}
				If (GetValueBoolean($this->GetIDForIdent("Summary")) <> boolval($this->GetBuffer("Summary"))) {
					SetValueBoolean($this->GetIDForIdent("Summary"), $this->GetBuffer("Summary"));
				}
			}
			else {
				$this->SendDebug("Measurement", "Fehler bei der Datenrueckgabe!", 0);
			}
			
		
		}
	}
	
	private function HasActiveParent()
    	{
		$Instance = @IPS_GetInstance($this->InstanceID);
		if ($Instance['ConnectionID'] > 0)
		{
			$Parent = IPS_GetInstance($Instance['ConnectionID']);
			if ($Parent['InstanceStatus'] == 102)
			return true;
		}
        return false;
    	}  
}
?>
