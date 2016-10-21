
<?
    // Klassendefinition
    class IPS2GPIO_PCF8591 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 72);
 	    	$this->RegisterPropertyBoolean("Ain0", true);
 	    	$this->RegisterPropertyBoolean("LoggingAin0", false);
 	    	$this->RegisterPropertyBoolean("Ain1", true);
 	    	$this->RegisterPropertyBoolean("LoggingAin1", false);
 	    	$this->RegisterPropertyBoolean("Ain2", true);
 	    	$this->RegisterPropertyBoolean("LoggingAin2", false);
 	    	$this->RegisterPropertyBoolean("Ain3", true);
 	    	$this->RegisterPropertyBoolean("LoggingAin3", false);
 	    	$this->RegisterPropertyBoolean("LoggingOut", false);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GAD1_Measurement($_IPS["TARGET"]);');
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
	    		IPS_LogMessage("IPS2GPIO PCF8591","I2C-Device Adresse in einem nicht definierten Bereich!");  
	    	}
	    	//Status-Variablen anlegen
		$this->RegisterVariableInteger("Channel_0", "Channel 0", "~Intensity.255", 10);
          	$this->DisableAction("Channel_0");
		IPS_SetHidden($this->GetIDForIdent("Channel_0"), false);
		
		$this->RegisterVariableInteger("Channel_1", "Channel 1", "~Intensity.255", 20);
          	$this->DisableAction("Channel_1");
		IPS_SetHidden($this->GetIDForIdent("Channel_1"), false);
		
		$this->RegisterVariableInteger("Channel_2", "Channel 2", "~Intensity.255", 30);
          	$this->DisableAction("Channel_2");
		IPS_SetHidden($this->GetIDForIdent("Channel_2"), false);
		
		$this->RegisterVariableInteger("Channel_3", "Channel 3", "~Intensity.255", 40);
          	$this->DisableAction("Channel_3");
		IPS_SetHidden($this->GetIDForIdent("Channel_3"), false);
		
		$this->RegisterVariableInteger("Output", "Output", "~Intensity.255", 50);
          	$this->EnableAction("Output");
		IPS_SetHidden($this->GetIDForIdent("Output"), false);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			for ($i = 0; $i <= 3; $i++) {
				AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Channel_".$i), $this->ReadPropertyBoolean("LoggingAin".$i)); 
			} 
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Output"), $this->ReadPropertyBoolean("LoggingOut")); 
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);

			//ReceiveData-Filter setzen
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceAddress":'.$this->ReadPropertyInteger("DeviceAddress").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
		
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "InstanceID" => $this->InstanceID)));
			$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
			// Erste Messdaten einlesen
			$this->Measurement();
			$this->SetStatus(102);
		}
        }
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Output":
	            $this->Set_Output($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValue($this->GetIDForIdent($Ident), $Value);
	            break;
	        default:
	            throw new Exception("Invalid Ident");
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
			  		If ($this->GetBuffer("WriteProtection") == false) {
			  			If ($data->Register == hexdec("40")) {
				  			SetValueInteger($this->GetIDForIdent("Channel_0"), $data->Value);
				  		}
				   		If ($data->Register == hexdec("41")) {
				   			SetValueInteger($this->GetIDForIdent("Channel_1"), $data->Value);
				   		}	
				   		If ($data->Register == hexdec("42")) {
				   			SetValueInteger($this->GetIDForIdent("Channel_2"), $data->Value);
				   		}
				   		If ($data->Register == hexdec("43")) {
				   			SetValueInteger($this->GetIDForIdent("Channel_3"), $data->Value);
				   		}
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
		for ($i = 0; $i <= 3; $i++) {
		    	If ($this->ReadPropertyBoolean("Ain".$i) == true) {
			    	$this->SetBuffer("WriteProtection", true);
			    	// Aktualisierung der Messerte anfordern
			    	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Register" => hexdec("40")|($i & 3) )));
				$this->SetBuffer("WriteProtection", false);
				// Messwerte einlesen
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Register" => hexdec("40")|($i & 3) )));
		    	}
		    	else {
		    		SetValueInteger($this->GetIDForIdent("Channel_".$i), 0);
		    	}
		    
		}
	return;
	}
	
	public function Set_Output(Int $Value)
	{
		$Value = min(255, max(0, $Value));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Register" => hexdec("40"), "Value" => $Value)));
	return;
	}
}
?>
