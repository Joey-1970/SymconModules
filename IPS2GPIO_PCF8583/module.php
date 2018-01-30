<?
    // Klassendefinition
    class IPS2GPIO_PCF8583 extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Messzyklus", 0);
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	//http://www.raspberry-pi-geek.de/Magazin/2015/02/Der-Uhrenbaustein-PCF8583-am-I-2-C-Bus-des-Raspberry-Pi/(offset)/2
		
		// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 80);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		$this->RegisterPropertyInteger("AlarmValue", 0);
		$this->RegisterPropertyInteger("PulseScalar", 1);	
		$this->RegisterPropertyInteger("Pin", -1);
		$this->SetBuffer("PreviousPin", -1);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'I2GPCF8583_GetCounter($_IPS["TARGET"]);');
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("LastInterrupt", "Letzte Meldung", "~UnixTimestamp", 10);
		$this->DisableAction("LastInterrupt");
		IPS_SetHidden($this->GetIDForIdent("LastInterrupt"), false);
		
		$this->RegisterVariableInteger("CounterValue", "Zählwert", "", 20);
		$this->DisableAction("CounterValue");
		IPS_SetHidden($this->GetIDForIdent("CounterValue"), false);
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
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "80 dez. / 0x50h", "value" => 80);
		$arrayOptions[] = array("label" => "81 dez. / 0x51h", "value" => 81);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		
		$arrayOptions = array();
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		foreach($DevicePorts AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt (optional)"); 
		
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		
		$arrayElements[] = array("type" => "Select", "name" => "Pin", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "Wert bei dem ein Interrupt ausgelöst werden soll");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "AlarmValue",  "caption" => "Alarm Wert");
		$arrayElements[] = array("type" => "Label", "label" => "Wertigkeit der Zählimpulse");
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "jeder Impuls", "value" => 1);
		$arrayOptions[] = array("label" => "jeder Hundertste", "value" => 2);
		$arrayOptions[] = array("label" => "jeder Zehntausendste", "value" => 3);
		$arrayOptions[] = array("label" => "jeder Millionste", "value" => 4);
		$arrayElements[] = array("type" => "Select", "name" => "PulseScalar", "caption" => "Wertigkeit", "options" => $arrayOptions );

		
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If (intval($this->GetBuffer("PreviousPin")) <> $this->ReadPropertyInteger("Pin")) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin"), 0);
		}
		
		//ReceiveData-Filter setzen
		$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
		$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*))';
		$this->SetReceiveDataFilter($Filter);
		
			
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
			If ($this->ReadPropertyBoolean("Open") == true) {
				If ($this->ReadPropertyInteger("Pin") >= 0) {
					$ResultPin = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
										  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 0)));	
				}
				else {
					$ResultPin = true;
				}
				$this->SetBuffer("PreviousPin", $this->ReadPropertyInteger("Pin"));
				
				$ResultI2C = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
								
				
				If (($ResultI2C == true) AND ($ResultPin == true)) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					$this->Setup();
					// Erste Messdaten einlesen
					$this->GetCounter();	
					$this->SetStatus(102);
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				$this->SetStatus(104);
			}	
		}
		else {
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
					If (($data->Value == 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						SetValueInteger($this->GetIDForIdent("LastInterrupt"), time() );
						$this->GetCounter();
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						//$this->GetCounter();
					}
			   	}
			   	break; 
			
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
	

	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			// Kontroll und Status Register an Adresse x00 setzen
			If ($this->ReadPropertyInteger("Pin") >= 0) {
				// Interrupt setzen
				$Interrupt = 1 << 2;
			}
			else {
				$Interrupt = 0 << 2;
			}
			
			$CounterMode = 2 << 4;
			$Bitmask = $CounterMode | $Interrupt;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("00"), "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("Setup", "Setzen der Config fehlerhaft!", 0);
				$this->SetStatus(202);
				return;
			}
			else {
				$this->SetStatus(102);
			}
			
			// Alarm Kontrolle an Andresse x08 setzen
			$Units = $this->ReadPropertyInteger("PulseScalar");
			If ($this->ReadPropertyInteger("Pin") >= 0) {
				// Interrupt setzen
				$Interrupt = 1 << 7;
			}
			else {
				$Interrupt = 0 << 7;
			}
			
			$Bitmask = $Units | $Interrupt;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("08"), "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("Setup", "Setzen der Config fehlerhaft!", 0);
				$this->SetStatus(202);
				return;
			}
			else {
				$this->SetStatus(102);
			}
			$this->SetAlarmValue();
		}
	}    
	
	public function GetCounter()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetCounter", "Ausfuehrung", 0);
			$CounterValue =  0;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("01"), "Count" => 3)));
			If ($Result < 0) {
				$this->SendDebug("GetCounter", "Fehler bei der Datenermittung", 0);
				$this->SetStatus(202);
				return 0;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					$this->SendDebug("GetCounter", "Ergebnis: ".$Result, 0);
					$MeasurementData = array();
					$MeasurementData = unserialize($Result);
					$CounterValue = (($MeasurementData[3] << 16) | ($MeasurementData[2] << 8) | $MeasurementData[1]);
					SetValueInteger($this->GetIDForIdent("CounterValue"), $CounterValue );
				}
			}
			
		}
	return $CounterValue;
	}    
	
	private function GetAlarmValue()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetAlarmValue", "Ausfuehrung", 0);
			$AlarmValue =  0;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("09"), "Count" => 3)));
			If ($Result < 0) {
				$this->SendDebug("GetAlarmValue", "Fehler bei der Datenermittung", 0);
				$this->SetStatus(202);
				return 0;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					//$this->SendDebug("GetAlarmValue", "Ergebnis: ".$Result, 0);
					$MeasurementData = array();
					$MeasurementData = unserialize($Result);
					$AlarmValue = (($MeasurementData[3] << 16) | ($MeasurementData[2] << 8) | $MeasurementData[1]);
					$this->SendDebug("GetAlarmValue", "Ergebnis: ".$AlarmValue, 0);
				}
			}
			
		}
	return $AlarmValue;
	} 
	    
	private function SetAlarmValue()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetAlarmValue", "Ausfuehrung", 0);
			$AlarmValue =  $this->ReadPropertyInteger("AlarmValue");
			$AlarmValue = min(pow(2, 47), max(0, $AlarmValue));
			$AlarmValueArray = array();
			$AlarmValueArray = unpack("C*", pack("Q", $AlarmValue));
			unset($AlarmValueArray[7]);
			unset($AlarmValueArray[8]);
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_write_array", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("09"), 
											  "Parameter" => serialize($AlarmValueArray) )));	
			If (!$Result) {
				$this->SendDebug("SetAlarmValue", "Setzen des Alarmwertes fehlerhaft!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				$this->GetAlarmValue();
			}
		}
	}      
	    
	private function GetTimer()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetTimer", "Ausfuehrung", 0);
			$TimerValue =  0;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("07"), "Count" => 1)));
			If ($Result < 0) {
				$this->SendDebug("GetTimer", "Fehler bei der Datenermittung", 0);
				$this->SetStatus(202);
				return 0;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					$this->SendDebug("GetTimer", "Ergebnis: ".$Result, 0);
					$MeasurementData = array();
					$MeasurementData = unserialize($Result);
					$TimerValue = $MeasurementData[1];
				}
			}
			
		}
	}            
	
	private function SetTimer()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetTimer", "Ausfuehrung", 0);
			$TimerValue =  0;
			//$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("07"), "Count" => 1)));
			If (!$Result) {
				$this->SendDebug("SetTimer", "Fehler bei der Datenermittung", 0);
				$this->SetStatus(202);
				return 0;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					$this->SendDebug("SetTimer", "Ergebnis: ".$Result, 0);
					$MeasurementData = array();
					$MeasurementData = unserialize($Result);
					$TimerValue = $MeasurementData[1];
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
	
	private function Get_GPIO()
	{
		If ($this->HasActiveParent() == true) {
			$GPIO = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_GPIO")));
		}
		else {
			$AllGPIO = array();
			$AllGPIO[-1] = "undefiniert";
			for ($i = 2; $i <= 27; $i++) {
				$AllGPIO[$i] = "GPIO".(sprintf("%'.02d", $i));
			}
			$GPIO = serialize($AllGPIO);
		}
	return $GPIO;
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
	
	private function HasActiveParent()
    	{
		$Instance = @IPS_GetInstance($this->InstanceID);
		if ($Instance['ConnectionID'] > 0)
		{
			$Parent = IPS_GetInstance($Instance['ConnectionID']);
			if ($Parent['InstanceStatus'] == 102)
			return true;
		}
        return false;
    	}  
}
?>
