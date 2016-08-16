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
 	    	$this->RegisterPropertyBoolean("Ain1", true);
 	    	$this->RegisterPropertyBoolean("Ain2", true);
 	    	$this->RegisterPropertyBoolean("Ain3", true);
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
	    		IPS_LogMessage("GPIO : ","I2C-Device Adresse in einem nicht definierten Bereich!");  
	    	}
	    	//Status-Variablen anlegen
	    	$this->RegisterVariableInteger("HardwareRev", "HardwareRev", "", 100);
          	$this->DisableAction("HardwareRev");
		IPS_SetHidden($this->GetIDForIdent("HardwareRev"), true);
		
		$this->RegisterVariableBoolean("WriteProtection", "WriteProtection", "", 130);
		$this->DisableAction("WriteProtection");
		IPS_SetHidden($this->GetIDForIdent("WriteProtection"), true);

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
		
          	$this->RegisterVariableInteger("Handle", "Handle", "", 110);
		$this->DisableAction("Handle");
		IPS_SetHidden($this->GetIDForIdent("Handle"), true);
/*		
            	If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
             		// Handle löschen
             		//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "close_handle_i2c", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")))));
             		SetValueInteger($this->GetIDForIdent("Handle"), -1);
             	}
            	// den Handle für dieses Gerät ermitteln
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"))));
*/
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
            	$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
            	If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
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
			   case "set_i2c_handle":
			   	If ($data->Address == $this->ReadPropertyInteger("DeviceAddress")) {
			   		SetValueInteger($this->GetIDForIdent("Handle"), $data->Handle);
			   		SetValueInteger($this->GetIDForIdent("HardwareRev"), $data->HardwareRev);
			   	}
			   	break;
			   case "get_used_i2c":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "Value" => true, "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"))));
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
			  		If (GetValueBoolean($this->GetIDForIdent("WriteProtection")) == false) {
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
		If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
			for ($i = 0; $i <= 3; $i++) {
			    	If ($this->ReadPropertyBoolean("Ain".$i) == true) {
				    	SetValueBoolean($this->GetIDForIdent("WriteProtection"), true);
				    	// Aktualisierung der Messerte anfordern
				    	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Register" => hexdec("40")|($i & 3) )));
					SetValueBoolean($this->GetIDForIdent("WriteProtection"), false);
					// Messwerte einlesen
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Register" => hexdec("40")|($i & 3) )));
			    	}
			    	else {
			    		SetValueInteger($this->GetIDForIdent("Channel_".$i), 0);
			    	}
			    
			}
		}
	return;
	}
	
	public function Set_Output($Value)
	{
		If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
			$Value = min(255, max(0, $Value));
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Register" => hexdec("40"), "Value" => $Value)));
		}
	return;
	}
}
?>
