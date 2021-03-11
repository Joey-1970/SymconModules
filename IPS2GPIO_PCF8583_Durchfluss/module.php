<?
    // Klassendefinition
    class IPS2GPIO_PCF8583_Durchfluss extends IPSModule 
    {
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
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterPropertyInteger("PulseLiterManuel", 1000);
		$this->RegisterTimer("Messzyklus", 0, 'I2GPCF8583Durchfluss_GetCounter($_IPS["TARGET"]);');
		
		// Profile anlegen
		$this->RegisterProfileFloat("IPS2GPIO.Pulse_Durchfluss", "Intensity", "", " Imp./min", 0, 10000, 0.1, 1);
		$this->RegisterProfileFloat("IPS2GPIO.Durchfluss", "Intensity", "", " l/min", 0, 10, 0.1, 1);		
		
		//Status-Variablen anlegen		
		$this->RegisterVariableInteger("CounterValue", "Zählwert", "", 10);
		
		$this->RegisterVariableInteger("CounterDifference", "Zählwert-Differenz", "", 20);
		
		$this->RegisterVariableFloat("PulseMinute", "Impulse/Minute", "IPS2GPIO.Pulse_EltakoWS", 30);
		
		$this->RegisterVariableFloat("LiterMinute", "Liter/Minute", "IPS2GPIO.Durchfluss", 40); 	
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
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Messzyklus", "caption" => "Wiederholungszyklus in Sekunden (0 -> aus) (optional)", "minimum" => 0);	
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "PulseLiterManuel", "caption" => "Impulse pro Liter laut Datenblatt", "minimum" => 1);	
		$arrayActions = array();
		If ($this->ReadPropertyBoolean("Open") == true) {
			$arrayActions[] = array("type" => "Button", "label" => "Zähler Reset", "onClick" => 'I2GPCF8583Durchfluss_SetCounter($id, 0, 0, 0);');
		}
		else {
			$arrayActions[] = array("type" => "Label", "caption" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		}		
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
		$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*))';
		$this->SetReceiveDataFilter($Filter);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {							
			If ($this->ReadPropertyBoolean("Open") == true) {
				$ResultI2C = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				
				If ($ResultI2C == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					
					// Zähler zurücksetzen
					$this->SetCounter(0, 0, 0);					
					
					// Erste Messung durchführen
					$StartTime = microtime(true);
					$this->SetBuffer("CounterOldTime", $StartTime);
					$this->GetCounter();	
					
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
			case "CounterReset":
			   	$this->SetCounter(0, 0, 0);
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
	public function GetCounter()
	{
		$CounterValue =  0;
		If ($this->ReadPropertyBoolean("Open") == true) {
			$tries = 5;
			do {
				$this->SendDebug("GetCounter", "Ausfuehrung", 0);
				
				$Bitmask = 0xE0;
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00, "Value" => $Bitmask)));
				If (!$Result) {
					$this->SendDebug("Setup", "Setzen der Config fehlerhaft!", 0);
					$this->SetStatus(202);
					$this->SetTimerInterval("Messzyklus", 0);
					break;
				}
				else {
					$this->SetStatus(102);
				}				
								
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x01, "Count" => 3)));
				If ($Result < 0) {
					$this->SendDebug("GetCounter", "Fehler bei der Datenermittung", 0);
					$this->SetStatus(202);
				}
				else {
					If (is_array(unserialize($Result)) == true) {
						
						$Bitmask = 0x24;
						$Result_2 = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00, "Value" => $Bitmask)));
						If (!$Result_2) {
							$this->SendDebug("GetCounter", "Setzen der Config fehlerhaft!", 0);
							$this->SetStatus(202);
							$this->SetTimerInterval("Messzyklus", 0);
							break;
						}
						else {
							$this->SetStatus(102);
							$MeasurementData = array();
							$MeasurementData = unserialize($Result);

							//$this->SendDebug("GetCounter", "Rohergebnis: ".$MeasurementData[3]." ".$MeasurementData[2]." ".$MeasurementData[1], 0);
							
							// Zeitdifferenz berechnen und Impulse/Minute ausgeben
							$MeasurementTime = microtime(true);
							$CounterOldTime = floatval($this->GetBuffer("CounterOldTime"));
							$TimeDifference = $MeasurementTime - $CounterOldTime;
							
							// Berechnung des Wertes Darstellung BCD
							$CounterValue = 0;
							$CounterValue = intval(sprintf("%02d", dechex($MeasurementData[3])).sprintf("%02d", dechex($MeasurementData[2])).sprintf("%02d", dechex($MeasurementData[1])));
							//$this->SendDebug("GetCounter", "BCD Ergebnis: ".$Test, 0);

							$this->SendDebug("GetCounter", "Ergebnis: ".$CounterValue, 0);									
							If ($this->GetValue("CounterValue") <> $CounterValue) {
								$this->SetValue("CounterValue", $CounterValue);
							}

							// Zählerdifferenz berechnen
							$CounterOldValue = intval($this->GetBuffer("CounterOldValue"));
							$CounterDifference = $CounterValue - $CounterOldValue;
							$CounterDifference = max($CounterDifference, 0); 
							If ($this->GetValue("CounterDifference") <> $CounterDifference) {
								$this->SetValue("CounterDifference", $CounterDifference);
							}
							$this->SetBuffer("CounterOldValue", $CounterValue);

							$PulseSecond = 0;
							If ($TimeDifference > 0) {
								$PulseSecond = $CounterDifference / $TimeDifference;
							}
							If ($this->GetValue("PulseMinute") <> $PulseSecond * 60) {
								$this->SetValue("PulseMinute", $PulseSecond * 60);
							}
							$this->SetBuffer("CounterOldTime", $MeasurementTime);
							
							$PulseLiterManuel = $this->ReadPropertyBoolean("PulseLiterManuel");
							$LiterMinute = $CounterDifference / $PulseLiterManuel;
							If ($this->GetValue("LiterMinute") <> $LiterMinute) {
								$this->SetValue("LiterMinute", $LiterMinute);
							}

							If ($CounterValue > 999900) {
								$this->SendDebug("GetCounter", "Zaehlerwert > 999900, Zaehler wird zurueckgesetzt", 0);
								// Zähler zurücksetzen
								$this->SetCounter(0, 0, 0);	
							}
							
							break;
						}
					}
				}
			$tries--;
			} while ($tries);  
		}
	return $CounterValue;
	}    

	public function SetCounter(int $Value01, int $Value02, int $Value03)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetCounter", "Ausfuehrung", 0);
			$Bitmask = 0xE0;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00, "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("SetCounter", "Einleitung des Reset fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				// Zähler zurücksetzen
				$CounterValueArray = array();
				$CounterValueArray = array($Value01, $Value02, $Value03);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_write_array", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 0x01, 
					"Parameter" => serialize($CounterValueArray) )));	
				
				If (!$Result) {
					$this->SendDebug("Setup", "Setzen des Counterwertes fehlerhaft!", 0);
					$this->SetStatus(202);
					$this->SetTimerInterval("Messzyklus", 0);
					return;
				}
				else {
					$Bitmask = 0xE0;
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00, "Value" => $Bitmask)));
					
					If (!$Result) {
						$this->SendDebug("SetCounter", "Abschluss des Reset fehlerhaft!", 0);
						$this->SetStatus(202);
						$this->SetTimerInterval("Messzyklus", 0);
						return;
					}
					else {
						$this->SendDebug("SetCounter", "Reset erfolgreich!", 0);
						$this->SetStatus(102);
						$this->SetBuffer("CounterOldValue", 0);
						If ($this->GetValue("CounterDifference") <> 0) {
							$this->SetValue("CounterDifference", 0);
						}
						$this->GetCounter();
					}
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
	 	    
	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 2);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 2)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	        IPS_SetVariableProfileDigits($Name, $Digits);
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