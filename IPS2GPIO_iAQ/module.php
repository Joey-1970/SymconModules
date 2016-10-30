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
 	    	$this->RegisterPropertyInteger("DeviceAddress", 90);
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
	    		IPS_LogMessage("IPS2GPIO BH1750","I2C-Device Adresse in einem nicht definierten Bereich!");  
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
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceAddress":'.$this->ReadPropertyInteger("DeviceAddress").'.*)|.*"Function":"status".*)';
			//$this->SendDebug("IPS2GPIO", $Filter, 0);
			$this->SetReceiveDataFilter($Filter);
		
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "InstanceID" => $this->InstanceID)));
			$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
			// Erste Messdaten einlesen
			$this->Measurement();
			$this->SetStatus(102);
		}
        }
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			   case "get_used_i2c":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "InstanceID" => $this->InstanceID)));
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
			  case "set_i2c_data":
			  	If ($data->DeviceAddress == $this->ReadPropertyInteger("DeviceAddress")) {
			  		// Daten der Messung
			  		IPS_LogMessage("IPS2GPIO GPIO iAQ", "Daten sind angekommen");
					If ($data->Register == $this->ReadPropertyInteger("DeviceAddress"))  {
			  			
			  			SetValueString($this->GetIDForIdent("Status"), "Test: ".$data->ByteArray);
					}
					
			  		
			  		
			  	}
			  	break;
	 	}
	return;
 	}
	// Beginn der Funktionen
	// Führt eine Messung aus
	public function Measurement()
	{
		// Daten anfordern
		IPS_LogMessage("IPS2GPIO GPIO iAQ", "Daten sind angefordert");
		//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte_onhandle", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Value" => hexdec("5B"))));
	
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_block_byte", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Register" => hexdec("5A"), "Count" => 7)));

		//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Register" => hexdec("5B") )));
		//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Register" => hexdec("5B"), "Value" => $Value)));
		//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Register" => $this->ReadPropertyInteger("DeviceAddress"))));
	return;
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
