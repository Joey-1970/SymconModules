<?
    // Klassendefinition
    class IPS2GPIO_BH1750 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 35);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GBH_Measurement($_IPS["TARGET"]);');
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
	    		IPS_LogMessage("GPIO : ","I2C-Device Adresse in einem nicht definierten Bereich!");  
	    	}
	    	// Profil anlegen
		$this->RegisterProfileInteger("illuminance.lx", "Illuminance", "", " lx", 0, 1000000, 1);
	    	//Status-Variablen anlegen
	    	$this->RegisterVariableInteger("HardwareRev", "HardwareRev", "", 100);
          	$this->DisableAction("HardwareRev");
		IPS_SetHidden($this->GetIDForIdent("HardwareRev"), true);
		
          	$this->RegisterVariableInteger("Handle", "Handle", "", 110);
		$this->DisableAction("Handle");
		IPS_SetHidden($this->GetIDForIdent("Handle"), true);
             	
             	$this->RegisterVariableInteger("Illuminance", "Illuminance", "illuminance.lx", 10);
		$this->DisableAction("Illuminance");
		IPS_SetHidden($this->GetIDForIdent("Illuminance"), false);

             	If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
             		// Handle löschen
             		//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "close_handle_i2c", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")))));
             		SetValueInteger($this->GetIDForIdent("Handle"), -1);
             	}
            	// den Handle für dieses Gerät ermitteln
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"))));

            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
            	$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
            	If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
	            	// Setup
	            	$this->Setup();
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
			   case "set_i2c_handle":
			   	If ($data->Address == $this->ReadPropertyInteger("DeviceAddress")) {
			   		SetValueInteger($this->GetIDForIdent("Handle"), $data->Handle);
			   		SetValueInteger($this->GetIDForIdent("HardwareRev"), $data->HardwareRev);
			   	}
			   	break;
			   case "get_used_i2c":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "Value" => true)));
			   	break;
			   case "status":
			   	If (GetValueInteger($this->GetIDForIdent("HardwareRev")) <= 3) {
				   	If (($data->Pin == 0) OR ($data->Pin == 1)) {
				   		$this->SetStatus($data->Status);		
				   	}
			   	}
				else if (GetValueInteger($this->GetIDForIdent("HardwareRev")) > 3) {
					If (($data->Pin == 2) OR ($data->Pin == 3)) {
				   		$this->SetStatus($data->Status);
				   	}
				}
			   	break;
			  case "set_i2c_data":
			  	If ($data->Handle == GetValueInteger($this->GetIDForIdent("Handle"))) {
			  		// Daten der Messung
			  		If ($data->Register == $this->ReadPropertyInteger("DeviceAddress"))  {
			  			$Lux = (($data->Value & 0xff00)>>8) | (($data->Value & 0x00ff)<<8);
			  			$Lux = max(0, $Lux);
			  			SetValueInteger($this->GetIDForIdent("Illuminance"), $Lux);
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
		If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => $this->ReadPropertyInteger("DeviceAddress"))));
		}
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

	private function Setup()
	{
		If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
			// Einschalten
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte_onhandle", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Value" => hexdec("01"))));
			IPS_Sleep(100);
			// Messwerterfassung setzen
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte_onhandle", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Value" => hexdec("10"))));
		}
	return;
	}

}
?>
