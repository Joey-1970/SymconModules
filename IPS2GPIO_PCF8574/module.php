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
 	    	$this->RegisterPropertyBoolean("LoggingP0", false);
 	    	$this->RegisterPropertyBoolean("LoggingP1", false);
 	    	$this->RegisterPropertyBoolean("LoggingP2", false);
 	    	$this->RegisterPropertyBoolean("LoggingP3", false);
 	    	$this->RegisterPropertyBoolean("LoggingP4", false);
 	    	$this->RegisterPropertyBoolean("LoggingP5", false);
 	    	$this->RegisterPropertyBoolean("LoggingP6", false);
 	    	$this->RegisterPropertyBoolean("LoggingP7", false);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GIO1_Read_Status($_IPS["TARGET"]);');
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
	    		IPS_LogMessage("IPS2GPIO PCF8574","I2C-Device Adresse in einem nicht definierten Bereich!");  
	    	}
	    	//Status-Variablen anlegen
		$this->RegisterVariableBoolean("P0", "P0", "~Switch", 10);
          	IPS_SetHidden($this->GetIDForIdent("P0"), false);
		
		$this->RegisterVariableBoolean("P1", "P1", "~Switch", 20);
          	IPS_SetHidden($this->GetIDForIdent("P1"), false);
		
		$this->RegisterVariableBoolean("P2", "P2", "~Switch", 30);
          	IPS_SetHidden($this->GetIDForIdent("P2"), false);
		
		$this->RegisterVariableBoolean("P3", "P3", "~Switch", 40);
          	IPS_SetHidden($this->GetIDForIdent("P3"), false);
		
		$this->RegisterVariableBoolean("P4", "P4", "~Switch", 50);
          	IPS_SetHidden($this->GetIDForIdent("P4"), false);
		
		$this->RegisterVariableBoolean("P5", "P5", "~Switch", 60);
          	IPS_SetHidden($this->GetIDForIdent("P5"), false);
		
		$this->RegisterVariableBoolean("P6", "P6", "~Switch", 70);
          	IPS_SetHidden($this->GetIDForIdent("P6"), false);
		
		$this->RegisterVariableBoolean("P7", "P7", "~Switch", 80);
          	IPS_SetHidden($this->GetIDForIdent("P7"), false);
          	
          	$this->RegisterVariableInteger("Value", "Value", "", 90);
          	IPS_SetHidden($this->GetIDForIdent("Value"), false);
		
		for ($i = 0; $i <= 7; $i++) {
			If ($this->ReadPropertyBoolean("P".$i) == true) {
				// wenn true dann Eingang, dann disable
				$this->DisableAction("P".$i);
			}
			else {
				// Ausgang muss manipulierbar sein
				$this->EnableAction("P".$i);	
			}
		}

		// Logging setzen
		for ($i = 0; $i <= 7; $i++) {
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("P".$i), $this->ReadPropertyBoolean("LoggingP".$i)); 
		} 
		IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);

		//ReceiveData-Filter setzen
		$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceAddress":'.$this->ReadPropertyInteger("DeviceAddress").'.*)|.*"Function":"status".*)';
		$this->SetReceiveDataFilter($Filter);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "InstanceID" => $this->InstanceID)));
			$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
			$this->Setup();
			// Erste Messdaten einlesen
			$this->Read_Status();
			$this->SetStatus(102);
		}
        }
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "P0":
	            $this->SetPinOutput(0, $Value);
	            break;
	        case "P1":
	            $this->SetPinOutput(1, $Value);
	            break;
	        case "P2":
	            $this->SetPinOutput(2, $Value);
	            break;
	        case "P3":
	            $this->SetPinOutput(3, $Value);
	            break;
	        case "P4":
	            $this->SetPinOutput(4, $Value);
	            break;
	        case "P5":
	            $this->SetPinOutput(5, $Value);
	            break;    
	        case "P6":
	            $this->SetPinOutput(6, $Value);
	            break;
	        case "P7":
	            $this->SetPinOutput(7, $Value);
	            break;
	        case "Value":
	            $this->SetOutput($Value);
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
			  		SetValueInteger($this->GetIDForIdent("Value"), $data->Value);
			  		$result = str_pad (decbin($data->Value), 8, '0', STR_PAD_LEFT );
			  		for ($i = 0; $i <= 7; $i++) {
						SetValueBoolean($this->GetIDForIdent("P".$i), substr ($result , 7-$i, 1));
			  		}
			  	}
			  	break;
	 	}
	return;
 	}
	// Beginn der Funktionen
	// Führt eine Messung aus
	public function Read_Status()
	{
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte_onhandle", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"))));
	return;
	}
	
	private function Setup()
	{
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte_onhandle", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Value" => 0)));
	return;	
	}
	
	public function SetPinOutput($Pin, $Value)
	{
		// Setzt einen bestimmten Pin auf den vorgegebenen Wert
		$Pin = min(7, max(0, $Pin));
		$Value = boolval($Value);
		// Aktuellen Status abfragen
		$this->Read_Status();
		// Bitmaske erstellen
		$Bitmask = GetValueInteger($this->GetIDForIdent("Value"));
		If ($Value == true) {
			$Bitmask = $this->setBit($Bitmask, $Pin);
		}
		else {
			$Bitmask = $this->unsetBit($Bitmask, $Pin);
		}
		$Bitmask = min(255, max(0, $Bitmask));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte_onhandle", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Value" => $Bitmask)));
		$this->Read_Status();
	return;
	}
	
	public function SetOutput($Value)
	{
		// Setzt alle Ausgänge
		$Value = min(255, max(0, $Value));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte_onhandle", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "Value" => $Value)));
		$this->Read_Status();
	return;
	}
	
	private function setBit($byte, $significance) { 
 		// ein bestimmtes Bit auf 1 setzen
 		return $byte | 1<<$significance;   
 	} 

	private function unsetBit($byte, $significance) {
	    // ein bestimmtes Bit auf 0 setzen
	    return $byte & ~(1<<$significance);
	}

}
?>
