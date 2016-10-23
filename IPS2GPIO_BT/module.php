<?
    // Klassendefinition
    class IPS2GPIO_BT extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
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
      }
 
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                 // Diese Zeile nicht löschen
                 parent::ApplyChanges();
                 //Connect to available splitter or create a new one
	         $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	   
	         //Status-Variablen anlegen
	         $this->RegisterVariableBoolean("MAC0Connect", "MAC 1", "~Switch", 10);
		 $this->EnableAction("MAC0Connect");
		 $this->RegisterVariableString("MAC0Name", "MAC 1 Name", "", 20);
                 $this->EnableAction("MAC0Name");
                 $this->RegisterVariableBoolean("MAC1Connect", "MAC 2", "~Switch", 30);
		 $this->EnableAction("MAC1Connect");
		 $this->RegisterVariableString("MAC1Name", "MAC 2 Name", "", 40);
                 $this->EnableAction("MAC1Name");
		 $this->RegisterVariableBoolean("MAC2Connect", "MAC 3", "~Switch", 50);
		 $this->EnableAction("MAC2Connect");
		 $this->RegisterVariableString("MAC2Name", "MAC 3 Name", "", 60);
                 $this->EnableAction("MAC2Name");
		 $this->RegisterVariableBoolean("MAC3Connect", "MAC 4", "~Switch", 70);
		 $this->EnableAction("MAC3Connect");
		 $this->RegisterVariableString("MAC3Name", "MAC 4 Name", "", 80);
                 $this->EnableAction("MAC3Name");
		 $this->RegisterVariableBoolean("MAC4Connect", "MAC 5", "~Switch", 90);
		 $this->EnableAction("MAC4Connect");
		 $this->RegisterVariableString("MAC4Name", "MAC 5 Name", "", 100);
                 $this->EnableAction("MAC4Name");
                
		If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			for ($i = 0; $i <= 4; $i++) {
				AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("MAC".$i."Connect"),  $this->ReadPropertyBoolean("LoggingMAC".$i)); 
			} 
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);


			//ReceiveData-Filter setzen
			//$Filter = '((.*"Function":"set_RPi_connect".*|.*"InstanceID":'.$this->InstanceID.'.*)|.*"Function":"get_start_trigger".*)';
			$Filter = '(.*"Function":"get_start_trigger".*|.*"InstanceID":'.$this->InstanceID.'.*)';
			$this->SetReceiveDataFilter($Filter);
			
			$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
			$this->Measurement();
			$this->SetStatus(102);
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
			   case "set_RPi_connect":
			   	//If ($data->InstanceID == $this->InstanceID) {
					SetValueString($this->GetIDForIdent("MAC".$data->CommandNumber."Name"), utf8_decode($data->Result));
					If (strlen($data->Result) > 0) {
						SetValueBoolean($this->GetIDForIdent("MAC".$data->CommandNumber."Connect"), true);
					}
					else {
						SetValueBoolean($this->GetIDForIdent("MAC".$data->CommandNumber."Connect"), false);
					}
				//}
			   	break;
			  case "get_start_trigger":
			   	$this->ApplyChanges();
				break;
	 	}
	return;
 	}
	// Beginn der Funktionen

	// Führt eine Messung aus
	public function Measurement()
	{
		/*
		$Command = "";
		$CommandArray = array();
		// Alle gültigen MAC-Adressen in einem Array erfassen
		for ($i = 0; $i <= 4; $i++) {
		    If (filter_var(trim($this->ReadPropertyString("MAC".$i)), FILTER_VALIDATE_MAC)) {
			$CommandArray[] = $MAC[$i]; 
		    }
		}
		// Die gültigen MAC-Adressen in einen Befehl zusammenfassen
		for ($i = 0; $i < Count($CommandArray); $i++) {
		    If ($i < (Count($CommandArray) - 1)) {
			$Command = $Command."hcitool name ".$CommandArray[$i]." && ";
		    }
		    else {
			$Command = $Command."hcitool name ".$CommandArray[$i];
		    }
		}
		// Befehl senden
		If (strlen($Command)) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => $Command, "CommandNumber" => 0 )));
		}
		*/
		$CommandArray = Array();
		for ($i = 0; $i <= 4; $i++) {
			If (filter_var(trim($this->ReadPropertyString("MAC".$i)), FILTER_VALIDATE_MAC)) {
				//IPS_LogMessage("IPS2GPIO SSH-Connect", "Sende MAC ".$i+1 );
				$CommandArray[$i] = "hcitool name ".$this->ReadPropertyString("MAC".$i);
			}
		}	
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray), "CommandNumber" => 0, "IsArray" => true )));
		
		
		/*
		for ($i = 0; $i <= 4; $i++) {
			If (filter_var(trim($this->ReadPropertyString("MAC".$i)), FILTER_VALIDATE_MAC)) {
				//IPS_LogMessage("IPS2GPIO SSH-Connect", "Sende MAC ".$i+1 );
				$Command = "hcitool name ".$this->ReadPropertyString("MAC".$i);
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => $Command, "CommandNumber" => $i, "IsArray" => false )));
			}
		}
		*/
	}
	
}
?>
