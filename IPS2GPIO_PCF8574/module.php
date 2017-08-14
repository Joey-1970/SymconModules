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
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("DeviceAddress", 32);
		$this->RegisterPropertyInteger("DeviceBus", 1);
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
		$this->RegisterPropertyInteger("Startoption", 0);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GIO1_Read_Status($_IPS["TARGET"]);');
	}
	/*
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!"); 
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
 			
		$arrayOptions = array();
		for ($i = 32; $i <= 39; $i++) {
		    	$arrayOptions[] = array("label" => $i." dez. / 0x".strtoupper(dechex($i))."h", "value" => $i);
		}
		for ($i = 56; $i <= 63; $i++) {
		    	$arrayOptions[] = array("label" => $i." dez. / 0x".strtoupper(dechex($i))."h", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "I²C-Bus 0", "value" => 0);
		$arrayOptions[] = array("label" => "I²C-Bus 1", "value" => 1);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 0", "value" => 3);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 1", "value" => 4);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 2", "value" => 5);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 3", "value" => 6);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 4", "value" => 7);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 5", "value" => 8);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 6", "value" => 9);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 7", "value" => 10);
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Definition der Ein- und Ausgänge (aktiviert => Eingang)");
		for ($i = 0; $i <= 7; $i++) {
			$arrayElements[] = array("type" => "CheckBox", "name" => "P".$i, "caption" => "P".$i);
			$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingP".$i, "caption" => "Logging P".$i." aktivieren");
		}
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Eingänge High, Ausgänge Low", "value" => 0);
		$arrayOptions[] = array("label" => "Eingänge High, Ausgänge High", "value" => 1);		
		$arrayElements[] = array("type" => "Select", "name" => "Startoption", "caption" => "Startoptionen", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Hinweise:");
		$arrayElements[] = array("type" => "Label", "label" => "- die Device Adresse lautet 32 bis 39 dez (0x20h - 0x27h) bei einem PCF8574");
		$arrayElements[] = array("type" => "Label", "label" => "- - die Device Adresse lautet 56 bis 63 dez (0x38h - 0x3Fh) bei einem PCF8574A");
		$arrayElements[] = array("type" => "Label", "label" => "- die I2C-Nutzung muss in der Raspberry Pi-Konfiguration freigegeben werden (sudo raspi-config -> Advanced Options -> I2C Enable = true)");
		$arrayElements[] = array("type" => "Label", "label" => "- die korrekte Nutzung der GPIO ist zwingend erforderlich (GPIO-Nr. 0/1 nur beim Raspberry Pi Model B Revision 1, alle anderen GPIO-Nr. 2/3)");
		$arrayElements[] = array("type" => "Label", "label" => "- auf den korrekten Anschluss von SDA/SCL achten");			
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}   
	*/    
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
 
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
		
		$SetTimer = false;
		for ($i = 0; $i <= 7; $i++) {
			If ($this->ReadPropertyBoolean("P".$i) == true) {
				// wenn true dann Eingang, dann disable		
 				$this->DisableAction("P".$i);
				$SetTimer = true;
 			}		
 			else {		
 				// Ausgang muss manipulierbar sein		
 				$this->EnableAction("P".$i);			
 			}
		}
		
		If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			for ($i = 0; $i <= 7; $i++) {
				AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("P".$i), $this->ReadPropertyBoolean("LoggingP".$i)); 
			} 
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);

			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
		
		
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
			
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				If ($SetTimer == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
				}
				else {
					$this->SetTimerInterval("Messzyklus", 0);
				}
				$this->Setup();
				// Erste Messdaten einlesen
				$this->Read_Status();
				$this->SetStatus(102);
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				$this->SetStatus(104);
			}
		}
		else {
			$this->SetTimerInterval("Messzyklus", 0);
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
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
					$this->ApplyChanges();
				}
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
			  	If ($data->DeviceIdent == $this->GetBuffer("DeviceIdent")) {
			  		// Daten der Messung
			  		SetValueInteger($this->GetIDForIdent("Value"), $data->Value);
			  		$result = str_pad (decbin($data->Value), 8, '0', STR_PAD_LEFT );
			  		for ($i = 0; $i <= 7; $i++) {
						SetValueBoolean($this->GetIDForIdent("P".$i), substr ($result , 7-$i, 1));
			  		}
			  	}
			  	break;
	 	}
 	}
	
	// Beginn der Funktionen
	// Führt eine Messung aus
	public function Read_Status()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte_onhandle", "DeviceIdent" => $this->GetBuffer("DeviceIdent"))));
		}
	}
	
	private function Setup()
	{
		If ($this->ReadPropertyInteger("Startoption") == 0) {
			$Bitmask = 0;
			for ($i = 0; $i <= 7; $i++) {
				If ($this->ReadPropertyBoolean("P".$i) == true) {
					// wenn true dann Eingang		
					$Bitmask = $Bitmask + pow(2, $i);
				}		
			}
			$this->SetOutput($Bitmask);
		}
		elseif ($this->ReadPropertyInteger("Startoption") == 1) {
			$this->SetOutput(255);
		}
	}
	
	public function SetPinOutput(Int $Pin, Bool $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
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
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte_onhandle", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Value" => $Bitmask)));
			$this->Read_Status();
		}
	}
	
	public function SetOutput(Int $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Setzt alle Ausgänge
			$Value = min(255, max(0, $Value));
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte_onhandle", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Value" => $Value)));
			$this->Read_Status();
		}
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
