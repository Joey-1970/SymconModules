<?
    // Klassendefinition
    class IPS2GPIO_iAQ extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("DeviceAddress", 90);
		$this->RegisterPropertyInteger("DeviceBus", 1);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
 	    	$this->RegisterPropertyBoolean("LoggingCO2", false);
		$this->RegisterPropertyBoolean("LoggingTVOC", false);
          	$this->RegisterTimer("Messzyklus", 0, 'I2GiAQ_Measurement($_IPS["TARGET"]);');
	}
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
            	//Connect to available splitter or create a new one
	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	    	// Device Adresse prüfen
	    	If (($this->ReadPropertyInteger("DeviceAddress") < 0) OR ($this->ReadPropertyInteger("DeviceAddress") > 128)) {
	    		IPS_LogMessage("IPS2GPIO iAQ-Core","I2C-Device Adresse in einem nicht definierten Bereich!");  
	    	}
	    	// Profil anlegen
		$this->RegisterProfileInteger("CO2.ppm", "Gauge", "", " ppm", 450, 2000, 1);
		$this->RegisterProfileInteger("TVOC.ppb", "Gauge", "", " ppb", 125, 600, 1);
		$this->RegisterProfileInteger("resistance.ohm", "Gauge", "", " Ohm", 0, 1000000, 1);
	    	
		//Status-Variablen anlegen
             	$this->RegisterVariableInteger("CO2", "CO2", "CO2.ppm", 10);
		$this->DisableAction("CO2");
		IPS_SetHidden($this->GetIDForIdent("CO2"), false);
		$this->RegisterVariableInteger("TVOC", "TVOC", "TVOC.ppb", 20);
		$this->DisableAction("TVOC");
		IPS_SetHidden($this->GetIDForIdent("TVOC"), false);
		$this->RegisterVariableInteger("Resistance", "Resistance", "resistance.ohm", 30);
		$this->DisableAction("Resistance");
		IPS_SetHidden($this->GetIDForIdent("Resistance"), false);
		$this->RegisterVariableString("Status", "Status", "", 40);
		$this->DisableAction("Status");
		IPS_SetHidden($this->GetIDForIdent("Status"), false);
		
		
		If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("CO2"), $this->ReadPropertyBoolean("LoggingCO2"));
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("TVOC"), $this->ReadPropertyBoolean("LoggingTVOC"));

			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			//$this->SendDebug("IPS2GPIO", $Filter, 0);
			$this->SetReceiveDataFilter($Filter);
		
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
			$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
			If ($this->ReadPropertyBoolean("Open") == true) {
				// Erste Messdaten einlesen
				$this->Measurement();
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}
		}       
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "get_used_i2c":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
			   	$this->ApplyChanges();
				break;
			case "status":
			   	If ($data->HardwareRev <= 3) {
				   	If (($data->Pin == 0) OR ($data->Pin == 1)) {
				   		$this->SetStatus($data->Status);		
				   	}
			   	}
				else if ($data->HardwareRev > 3) {
					If (($data->Pin == 2) OR ($data->Pin == 3)) {
				   		$this->SetStatus($data->Status);
				   	}
				}
			   	break;
			case "set_i2c_byte_block":
				 If ($data->DeviceIdent == $this->GetBuffer("DeviceIdent")) {
			   		// Daten der Messung
			  		IPS_LogMessage("IPS2GPIO GPIO iAQ", $data->ByteArray);
					$MeasurementArray = unserialize($data->ByteArray);
					// CO2 berechnen
					SetValueInteger($this->GetIDForIdent("CO2"), ($MeasurementArray[1] << 8) + $MeasurementArray[2]);
					// Status
					$StatusArray = Array(0 => "OK", 1 => "BUSY", 16 => "RUNNIN", 128 => "ERROR");
					SetValueString($this->GetIDForIdent("Status"), $StatusArray[$MeasurementArray[3]]);
					// Widerstand ausgeben
					SetValueInteger($this->GetIDForIdent("Resistance"), ($MeasurementArray[5] << 16) + ($MeasurementArray[6] << 8) + $MeasurementArray[7]);
					// TVOC berechnen
					SetValueInteger($this->GetIDForIdent("TVOC"), ($MeasurementArray[8] << 8) + $MeasurementArray[9]);
			   	}
			   	break;
	 	}
 	}
	
	    // Beginn der Funktionen
	// Führt eine Messung aus
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Daten anfordern
			//IPS_LogMessage("IPS2GPIO GPIO iAQ", "Daten sind angefordert");
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_bytes", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("5A"), "Count" => 9)));
		}
	}	
	
	    private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);        
	}

}
?>
