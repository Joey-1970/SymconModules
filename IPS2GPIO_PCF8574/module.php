<?
    // Klassendefinition
    class IPS2GPIO_PCF8574 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 32);
 	    	$this->RegisterPropertyBoolean("P0", false);
 	    	$this->RegisterPropertyBoolean("P1", false);
 	    	$this->RegisterPropertyBoolean("P2", false);
 	    	$this->RegisterPropertyBoolean("P3", false);
 	    	$this->RegisterPropertyBoolean("P4", false);
 	    	$this->RegisterPropertyBoolean("P5", false);
 	    	$this->RegisterPropertyBoolean("P6", false);
 	    	$this->RegisterPropertyBoolean("P7", false);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GIO1_Measurement($_IPS["TARGET"]);');
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

		$this->RegisterVariableString("MeasurementData", "MeasurementData", "", 140);
		$this->DisableAction("MeasurementData");
		IPS_SetHidden($this->GetIDForIdent("MeasurementData"), true);
		
		$this->RegisterVariableBoolean("P0", "P0", "~Switch", 10);
          	$this->EnableAction(P0");
		IPS_SetHidden($this->GetIDForIdent("P0"), false);
		
		$this->RegisterVariableBoolean("P1", "P1", "~Switch", 20);
          	$this->EnableAction(P1");
		IPS_SetHidden($this->GetIDForIdent("P1"), false);
		
		$this->RegisterVariableBoolean("P2", "P2", "~Switch", 30);
          	$this->EnableAction(P0");
		IPS_SetHidden($this->GetIDForIdent("P2"), false);
		
		$this->RegisterVariableBoolean("P3", "P3", "~Switch", 40);
          	$this->EnableAction(P3");
		IPS_SetHidden($this->GetIDForIdent("P3"), false);
		
		$this->RegisterVariableBoolean("P4", "P4", "~Switch", 50);
          	$this->EnableAction(P0");
		IPS_SetHidden($this->GetIDForIdent("P4"), false);
		
		$this->RegisterVariableBoolean("P5", "P5", "~Switch", 60);
          	$this->EnableAction(P5");
		IPS_SetHidden($this->GetIDForIdent("P5"), false);
		
		$this->RegisterVariableBoolean("P6", "P6", "~Switch", 70);
          	$this->EnableAction(P6");
		IPS_SetHidden($this->GetIDForIdent("P6"), false);
		
		$this->RegisterVariableBoolean("P7", "P7", "~Switch", 10);
          	$this->EnableAction(P7");
		IPS_SetHidden($this->GetIDForIdent("P7"), false);
		
          	$this->RegisterVariableInteger("Handle", "Handle", "", 110);
		$this->DisableAction("Handle");
		IPS_SetHidden($this->GetIDForIdent("Handle"), true);
             	
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
			  case "set_i2c_byte_block":
			   	If ($data->Handle == GetValueInteger($this->GetIDForIdent("Handle"))) {
			   		SetValueString($this->GetIDForIdent("MeasurementData"), $data->ByteArray);
			   		$MeasurementData = unserialize(GetValueString($this->GetIDForIdent("MeasurementData")));
			   		If ((count($MeasurementData) == 4) AND (GetValueBoolean($this->GetIDForIdent("WriteProtection")) == false)) {
				   		SetValueInteger($this->GetIDForIdent("Channel_0"), $MeasurementData[1]);
				   		SetValueInteger($this->GetIDForIdent("Channel_1"), $MeasurementData[2]);
				   		SetValueInteger($this->GetIDForIdent("Channel_2"), $MeasurementData[3]);
				   		SetValueInteger($this->GetIDForIdent("Channel_3"), $MeasurementData[4]);
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
			// Aktualisierung der Messerte anfordern
/*
			SetValueBoolean($this->GetIDForIdent("WriteProtection"), true);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_block_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => hexdec("40"), "Count" => 4)));
			SetValueBoolean($this->GetIDForIdent("WriteProtection"), false);
			// Messwerte einlesen
			IPS_Sleep(400);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_block_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => hexdec("40"), "Count" => 4)));
*/
			
			for ($i = 0; $i <= 3; $i++) {
			    	SetValueBoolean($this->GetIDForIdent("WriteProtection"), true);
			    	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => hexdec("40")|($i & 3) )));
				SetValueBoolean($this->GetIDForIdent("WriteProtection"), false);
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => hexdec("40")|($i & 3) )));
			}
		}
	return;
	}
	
	public function Set_Output($Value)
	{
		$Value = min(255, max(0, $Value));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => hexdec("40"), "Value" => $Value))); 
	return;
	}
	
}
?>
