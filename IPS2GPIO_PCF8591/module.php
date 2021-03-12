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
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("DeviceAddress", 72);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		for ($i = 0; $i <= 3; $i++) {
 	    		$this->RegisterPropertyBoolean("Ain".$i, true);
			$this->RegisterPropertyInteger("Function_".$i, 0);
			$this->RegisterPropertyFloat("PressureSensorMinVoltage_".$i, 0.5);
			$this->RegisterPropertyFloat("PressureSensorMaxVoltage_".$i, 4.5);
			$this->RegisterPropertyFloat("MaxPressure_".$i, 2);
		}
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GAD1_Measurement($_IPS["TARGET"]);');
		
		// Status-Variablen anlegen
		for ($i = 0; $i <= 3; $i++) {
			$this->RegisterVariableInteger("Channel_".$i, "Channel ".$i, "~Intensity.255", $i * 10 + 10);
		}
		
		$this->RegisterVariableInteger("Output", "Output", "~Intensity.255", 50);
          	$this->EnableAction("Output");
	}
	
        public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "I²C-Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
 			
		$arrayOptions = array();
		for ($i = 72; $i <= 79; $i++) {
		    	$arrayOptions[] = array("label" => $i." dez. / 0x".strtoupper(dechex($i))."h", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "caption" => "I²C-Bus (Default ist 1)");
		$arrayOptions = array();
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		foreach($DevicePorts AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "caption" => "Genutzte Eingänge");
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Standard", "value" => 0);
		$arrayOptions[] = array("label" => "Druck-Sensor", "value" => 1);

		
		for ($i = 0; $i <= 3; $i++) {
			$arrayElements[] = array("type" => "CheckBox", "name" => "Ain".$i, "caption" => "Ain".$i);
																					//  'IPS_RequestAction($id,"ChangeFunction",$Function);');
			$arrayElements[] = array("type" => "Select", "name" => "Function_".$i, "caption" => "Funktionsauswahl", "options" => $arrayOptions, "onChange" => 'IPS_RequestAction($id,"ChangeFunction_'.$i.'", $Function_'.$i.');');
			If ($this->ReadPropertyInteger("Function_".$i) == 0) {
				// Standard
				
				// unsichtbar
				$arrayElements[] = array("type" => "NumberSpinner", "name" => "PressureSensorMinVoltage_".$i, "caption" => "Mindest Spannung Messbereich", "minimum" => 0, "visible" => false);	
				$arrayElements[] = array("type" => "NumberSpinner", "name" => "PressureSensorMaxVoltage_".$i, "caption" => "Maximal Spannung Messbereich", "minimum" => 0, "visible" => false);	
				$arrayElements[] = array("type" => "NumberSpinner", "name" => "MaxPressure_".$i, "caption" => "Maximal Druck Messbereich", "minimum" => 1, "visible" => false);	

			}
			elseif ($this->ReadPropertyInteger("Function_".$i) == 1) {
				// Druck-Sensor
				
				// sichtbar
				$arrayElements[] = array("type" => "NumberSpinner", "name" => "PressureSensorMinVoltage_".$i, "caption" => "Mindest Spannung Messbereich", "minimum" => 0, "visible" => true);	
				$arrayElements[] = array("type" => "NumberSpinner", "name" => "PressureSensorMaxVoltage_".$i, "caption" => "Maximal Spannung Messbereich", "minimum" => 0, "visible" => true);	
				$arrayElements[] = array("type" => "NumberSpinner", "name" => "MaxPressure_".$i, "caption" => "Maximal Druck Messbereich", "minimum" => 1, "visible" => true);	
			}
			
		}
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Messzyklus", "caption" => "Wiederholungszyklus in Sekunden (0 -> aus) (optional)", "minimum" => 0);	

		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "caption" => "Hinweise:");
		$arrayElements[] = array("type" => "Label", "caption" => "- die Device Adresse lautet 72 dez (0x48h) bei A0 an GND");
		$arrayElements[] = array("type" => "Label", "caption" => "- die Device Adresse lautet 73 dez (0x49h) bei A0 an 5V");
		$arrayElements[] = array("type" => "Label", "caption" => "- die Device Adresse kann weitere Werte bei Beschaltung von A1 und A2 annehmen");
		$arrayElements[] = array("type" => "Label", "caption" => "- eine Abfrage des Ausgangs ist nicht direkt möglich, dazu muss er auf einen Eingang geschaltet werden");
		$arrayElements[] = array("type" => "Label", "caption" => "- die I2C-Nutzung muss in der Raspberry Pi-Konfiguration freigegeben werden (sudo raspi-config -> Advanced Options -> I2C Enable = true)");
		$arrayElements[] = array("type" => "Label", "caption" => "- die korrekte Nutzung der GPIO ist zwingend erforderlich (GPIO-Nr. 0/1 nur beim Raspberry Pi Model B Revision 1, alle anderen GPIO-Nr. 2/3)");
		$arrayElements[] = array("type" => "Label", "caption" => "- auf den korrekten Anschluss von SDA/SCL achten");
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 

		$arrayActions = array(); 
		$arrayActions[] = array("type" => "Label", "label" => "Test Center"); 
		$arrayActions[] = array("type" => "TestCenter", "name" => "TestCenter");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}   
   
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Summary setzen
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		$this->SetSummary("DA: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." DB: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]);

		// ReceiveData-Filter setzen
		$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
		$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
		$this->SetReceiveDataFilter($Filter);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {	
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					// Erste Messdaten einlesen
					$this->Measurement();
				}
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
			case "Output":
				$this->SetOutput($Value);
				break;

			case preg_match('/ChangeFunction.*/', $Ident) ? $Ident : !$Ident:
				$Input = intval(substr($Ident, -1)); // der Eingang der geändert werden soll
				$Function = $Value; // die Funktion die der Eingang haben soll
				$this->SendDebug("RequestAction", "ChangeFunction - Input: ".$Input." Funktion: ".$Function, 0);
				
				switch($Function) {
					case 0: // Standard
						$this->UpdateFormField('PressureSensorMinVoltage_'.$Input, 'visible', false);
						$this->UpdateFormField('PressureSensorMaxVoltage_'.$Input, 'visible', false);
						$this->UpdateFormField('MaxPressure_'.$Input, 'visible', false);
						break;
					case 1: // Druck-Sensor
						$this->UpdateFormField('PressureSensorMinVoltage_'.$Input, 'visible', true);
						$this->UpdateFormField('PressureSensorMaxVoltage_'.$Input, 'visible', true);
						$this->UpdateFormField('MaxPressure_'.$Input, 'visible', true);
						break;
				}
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
	 	}
 	}
	
	// Beginn der Funktionen
	// Führt eine Messung aus
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Measurement", "Ausfuehrung", 0);
			for ($i = 0; $i <= 3; $i++) {
				If ($this->ReadPropertyBoolean("Ain".$i) == true) {
					// Aktualisierung der Messerte anfordern
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8591_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("40")|($i & 3) )));
					If ($Result < 0) {
						$this->SendDebug("Measurement", "Einlesen der Werte fehlerhaft!", 0);
						$this->SetStatus(202);
						return;
					}

					// Messwerte einlesen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8591_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("40")|($i & 3) )));
					If ($Result < 0) {
						$this->SendDebug("Measurement", "Einlesen der Werte fehlerhaft!", 0);
						$this->SetStatus(202);
						return;
					}
					else {
						$this->SetStatus(102);
						If ($this->GetValue("Channel_".$i) <> $Result) {
							$this->SetValue("Channel_".$i, $Result);
						}
					}
				}
				else {
					If ($this->GetValue("Channel_".$i) <> 0) {
						$this->SetValue("Channel_".$i, 0);
					}
				}
			}
		}
	}
	
	public function SetOutput(Int $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetOutput", "Ausfuehrung", 0);
			$Value = min(255, max(0, $Value));
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8591_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("40"), "Value" => $Value)));
			If (!$Result) {
				$this->SendDebug("SetOutput", "Setzen des Ausgangs fehlerhaft!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				//Neuen Wert in die Statusvariable schreiben
	            		If ($this->GetValue("Output") <> $Value) {
					$this->SetValue("Output", $Value);
				}
			}
		}
	}
	
	private function Get_I2C_Ports()
	{
		If ($this->HasActiveParent() == true) {
			$I2C_Ports = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_get_ports")));
		}
		else {
			$DevicePorts = array();
			$DevicePorts[0] = "I²C-Bus 0";
			$DevicePorts[1] = "I²C-Bus 1";
			for ($i = 3; $i <= 10; $i++) {
				$DevicePorts[$i] = "MUX I²C-Bus ".($i - 3);
			}
			$I2C_Ports = serialize($DevicePorts);
		}
	return $I2C_Ports;
	}    
}
?>
