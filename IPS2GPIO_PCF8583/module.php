<?
    // Klassendefinition
    class IPS2GPIO_PCF8583 extends IPSModule 
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
		$this->RegisterPropertyInteger("AlarmValue", 0);
		$this->RegisterPropertyInteger("CounterInterrupt", 0);
		$this->RegisterPropertyInteger("Pin", -1);
		$this->SetBuffer("PreviousPin", -1);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'I2GPCF8583_GetCounter($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("Interrupt", 0);
		$this->RegisterTimer("Interrupt", 0, 'I2GPCF8583_ResetInterrupt($_IPS["TARGET"]);');
		
		// Profile anlegen
		$this->RegisterProfileFloat("IPS2GPIO.PCF8583", "Intensity", "", " Imp./min", 0, 1000, 0.1, 1);
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("LastInterrupt", "Letzte Meldung", "~UnixTimestamp", 10);
		
		$this->RegisterVariableInteger("CounterValue", "Zählwert", "", 20);
		
		$this->RegisterVariableInteger("CounterDifference", "Zählwert-Differenz", "", 30);
		
		$this->RegisterVariableFloat("PulseMinute", "Impulse/Minute", "IPS2GPIO.PCF8583", 40);
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
		$arrayElements[] = array("type" => "Label", "caption" => "Wiederholungszyklus in Sekunden (0 -> aus) (optional)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");		
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "caption" => "Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt (optional)"); 
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
		$arrayElements[] = array("type" => "Label", "caption" => "Fix-Wert bei dem ein Interrupt ausgelöst werden soll (optional)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "AlarmValue",  "caption" => "Alarm Wert");
		$arrayElements[] = array("type" => "Label", "label" => "Interrupt-Auslösung nach Zählerwert (optional)"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Kein (Default)", "value" => 0); // Default
		$arrayOptions[] = array("label" => "Jeder 100", "value" => 1);
		$arrayOptions[] = array("label" => "Jeder 10.000", "value" => 2);
		$arrayOptions[] = array("label" => "Jeder 1.000.000", "value" => 3);
		$arrayOptions[] = array("label" => "Jeder 100.000.000", "value" => 4);
		$arrayElements[] = array("type" => "Select", "name" => "CounterInterrupt", "caption" => "Impulse", "options" => $arrayOptions );
		
				
		$arrayActions = array();
		If ($this->ReadPropertyBoolean("Open") == true) {
			$arrayActions[] = array("type" => "Button", "label" => "Zähler Reset", "onClick" => 'I2GPCF8583_SetCounter($id, 0, 0, 0);');
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
		
		If (intval($this->GetBuffer("PreviousPin")) <> $this->ReadPropertyInteger("Pin")) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin"), 0);
		}
			
		// Summary setzen
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		$this->SetSummary("DA: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." DB: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]." GPIO: ".$this->ReadPropertyInteger("Pin"));

		// ReceiveData-Filter setzen
		$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
		$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*))';
		$this->SetReceiveDataFilter($Filter);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
			$this->RegisterProfileInteger("IPS2GPIO.Pulse", "Network", "", "", 0, 1, 1);
			IPS_SetVariableProfileAssociation("IPS2GPIO.Pulse", 0, "", "Flag", -1);
			IPS_SetVariableProfileAssociation("IPS2GPIO.Pulse", 1, "Impuls", "Flag", 0xFF0000);
			
			$this->RegisterVariableInteger("Pulse", "Impuls", "IPS2GPIO.Pulse", 30);
			
			IPS_SetHidden($this->GetIDForIdent("Pulse"), false);
			
			$this->SetValue("Pulse", 0);
						
			If ($this->ReadPropertyBoolean("Open") == true) {
				If ($this->ReadPropertyInteger("Pin") >= 0) {
					$ResultPin = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
										  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 2)));	
				}
				else {
					$ResultPin = true;
				}
				$this->SetBuffer("PreviousPin", $this->ReadPropertyInteger("Pin"));
				
				$ResultI2C = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
								
				
				If (($ResultI2C == true) AND ($ResultPin == true)) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					
					// Zähler zurücksetzen
					$this->SetCounter(0, 0, 0);					
					
					// Erste Messung durchführen
					$StartTime = time();
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
			case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
					If (($data->Value == 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Interrupt", "Wert: ".(int)$data->Value." -> Counter auslesen", 0);
						SetValueInteger($this->GetIDForIdent("LastInterrupt"), time() );
						$this->GetCounterByInterrupt();
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Interrupt", "Wert: ".(int)$data->Value." -> keine Aktion", 0);
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
	public function GetCounter()
	{
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
								
				$CounterValue =  0;
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

							// Berechnung des Wertes Darstellung BCD
							$CounterValue = 0;
							$CounterValue = intval(sprintf("%02d", dechex($MeasurementData[3])).sprintf("%02d", dechex($MeasurementData[2])).sprintf("%02d", dechex($MeasurementData[1])));
							//$this->SendDebug("GetCounter", "BCD Ergebnis: ".$Test, 0);

							$this->SendDebug("GetCounter", "Ergebnis: ".$CounterValue, 0);									
							$this->SetValue("CounterValue", $CounterValue);

							// Zählerdifferenz berechnen
							$CounterOldValue = intval($this->GetBuffer("CounterOldValue"));
							$CounterDifference = $CounterValue - $CounterOldValue;
							$this->SetValue("CounterDifference", $CounterDifference);
							$this->SetBuffer("CounterOldValue", $CounterValue);

							// Zeitdifferenz berechnen und Impulse/Minute ausgeben
							$MeasurementTime = time();
							$CounterOldTime = intval($this->GetBuffer("CounterOldTime"));
							$TimeDifference = $MeasurementTime - $CounterOldTime;
							$PulseMinute = 0;
							If ($TimeDifference > 0) {
								$PulseMinute = 60 / $TimeDifference * $CounterDifference;
							}
							$this->SetValue("PulseMinute", $PulseMinute);
							$this->SetBuffer("CounterOldTime", $MeasurementTime);

							//$this->GetTimerValue();									

							break;
						}
					}
				}
			$tries--;
			} while ($tries);  
		}
	return $CounterValue;
	}    
	
	private function GetCounterByInterrupt()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$tries = 5;
			do {
				$this->SendDebug("GetCounterByInterrupt", "Ausfuehrung", 0);
				$CounterValue =  0;
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x01, "Count" => 3)));
				If ($Result < 0) {
					$this->SendDebug("GetCounterByInterrupt", "Fehler bei der Datenermittung", 0);
					$this->SetStatus(202);
				}
				else {
					If (is_array(unserialize($Result)) == true) {
						$this->SetStatus(102);
						$MeasurementData = array();
						$MeasurementData = unserialize($Result);
						
						// Berechnung des Wertes Darstellung BCD
						$CounterValue = 0;
						for ($i = 1; $i <= 3; $i++) {
							$CounterValue = $CounterValue + ($MeasurementData[$i] & 15) * pow(10, ($i + $i - 2));
							$CounterValue = $CounterValue + (($MeasurementData[$i] & 240) >> 4) * pow(10, ($i + $i - 1));
						}
						$this->SendDebug("GetCounter", "Ergebnis BCD: ".$CounterValue, 0);									
						SetValueInteger($this->GetIDForIdent("CounterValue"), $CounterValue);
						
						// Zählerdifferenz berechnen
						$CounterOldValue = intval($this->GetBuffer("CounterOldValue"));
						$CounterDifference = $CounterValue - $CounterOldValue;
						SetValueInteger($this->GetIDForIdent("CounterDifference"), $CounterDifference);
						$this->SetBuffer("CounterOldValue", $CounterValue);
						
						// Zeitdifferenz berechnen und Impulse/Minute ausgeben
						$MeasurementTime = time();
						$CounterOldTime = intval($this->GetBuffer("CounterOldTime"));
						$TimeDifference = $MeasurementTime - $CounterOldTime;
						$PulseMinute = 0;
						If ($TimeDifference > 0) {
							$PulseMinute = 60 / $TimeDifference * $CounterDifference;
						}
						SetValueFloat($this->GetIDForIdent("PulseMinute"), $PulseMinute);
						$this->SetBuffer("CounterOldTime", $MeasurementTime);
						
						// Interruptauslöser bestimmen
						$AlarmValue =  $this->ReadPropertyInteger("AlarmValue");
						
						If ($CounterValue == $AlarmValue) {
							$this->SendDebug("GetCounterByInterrupt", "Interruptausloeser - Alarmwert erreicht: ".$CounterValue, 0);
						}
						else {
							$this->SendDebug("GetCounterByInterrupt", "Interruptausloeser - Zaehlwert erreicht: ".$CounterValue, 0);
						}
						
						$this->SetTimerInterval("Interrupt", (1 * 1000));
						//SetValueBoolean($this->GetIDForIdent("Interrupt"), true);
						SetValueInteger($this->GetIDForIdent("Pulse"), 1);
						$this->ResetInterruptFlags();	
						$this->GetTimerValue();	
						
						break;
					}
				}
			$tries--;
			} while ($tries);  
		}
	}        
	    
	public function ResetInterrupt()
	{
		SetValueInteger($this->GetIDForIdent("Pulse"), 0);
		//SetValueBoolean($this->GetIDForIdent("Interrupt"), false);
		$this->SetTimerInterval("Interrupt", 0);
		
	}
	
	private function ResetInterruptFlags()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			$tries = 3;
			do {
				// PCF8583 zum Schreiben vorbereiten
				$Bitmask = 0x20;
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" =>0x00, "Value" => $Bitmask)));
				If (!$Result) {
					$this->SendDebug("ResetInterruptFlags", "Setzen der Config fehlerhaft!", 0);
					$this->SetStatus(202);
					$this->SetTimerInterval("Messzyklus", 0);
				}
				else {
					$this->SetStatus(102);
					// Counter Einstellungen beenden
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
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00, "Value" => $Bitmask)));
					If (!$Result) {
						$this->SendDebug("ResetInterruptFlags", "Setzen der Config fehlerhaft!", 0);
						$this->SetStatus(202);
						$this->SetTimerInterval("Messzyklus", 0);
					}
					else {
						$this->SetStatus(102);
						break;
					}
				}
			$tries--;
			} while ($tries);  
		}
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
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					$MeasurementData = array();
					$MeasurementData = unserialize($Result);
										
					// Berechnung des Wertes Darstellung BCD
					$AlarmValue = 0;
					for ($i = 1; $i <= 3; $i++) {
						$AlarmValue = $AlarmValue + ($MeasurementData[$i] & 15) * pow(10, ($i + $i - 2));
						$AlarmValue = $AlarmValue + (($MeasurementData[$i] & 240) >> 4) * pow(10, ($i + $i - 1));
					}
					$this->SendDebug("GetAlarmValue", "Ergebnis BCD: ".$AlarmValue, 0);		
				}
			}
		}
	}     
	
	private function GetTimerValue()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetTimerValue", "Ausfuehrung", 0);
			$TimerValue =  0;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8583_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("07"), "Count" => 1)));
			If ($Result < 0) {
				$this->SendDebug("GetTimerValue", "Fehler bei der Datenermittung", 0);
				$this->SetStatus(202);
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					$MeasurementData = array();
					$MeasurementData = unserialize($Result);
					$TimerValue = ($MeasurementData[1] & 15) + ((($MeasurementData[1] & 240) >> 4) * 10);
					$this->SendDebug("GetTimerValue", "Ergebnis: ".$TimerValue, 0);
				}
			}
			
		}
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
						$this->SetValue("CounterDifference", 0);
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
	
	protected function HasActiveParent()
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
