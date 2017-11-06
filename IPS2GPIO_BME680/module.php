<?
    // Klassendefinition
    class IPS2GPIO_BME680 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("DeviceAddress", 118);
		$this->RegisterPropertyInteger("DeviceBus", 1);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
 	    	$this->RegisterPropertyBoolean("LoggingTemp", false);
 	    	$this->RegisterPropertyBoolean("LoggingHum", false);
 	    	$this->RegisterPropertyBoolean("LoggingPres", false);
 	    	$this->RegisterPropertyInteger("OSRS_T", 1);
 	    	$this->RegisterPropertyInteger("OSRS_H", 1);
 	    	$this->RegisterPropertyInteger("OSRS_P", 1);
 	    	$this->RegisterPropertyInteger("Mode", 3);
 	    	$this->RegisterPropertyInteger("SB_T", 5);
 	    	$this->RegisterPropertyInteger("IIR_Filter", 0);
		$this->RegisterPropertyInteger("Altitude", 0);
		$this->RegisterPropertyInteger("Temperature_ID", 0);
		$this->RegisterPropertyInteger("Humidity_ID", 0);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GBME680_Measurement($_IPS["TARGET"]);');
        }
	
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
		$arrayOptions[] = array("label" => "118 dez. / 0x76h", "value" => 118);
		$arrayOptions[] = array("label" => "119 dez. / 0x77h", "value" => 119);
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		$arrayOptions = array();
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		foreach($DevicePorts AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Korrektur des Luftdrucks nach Hohenangabe");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Altitude", "caption" => "Höhe über NN (m)");
		$arrayElements[] = array("type" => "Label", "label" => "Optionale Angabe von Quellen");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "Temperature_ID", "caption" => "Temperatur (extern)");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "Humidity_ID", "caption" => "Luftfeuchtigkeit (extern)");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingTemp", "caption" => "Logging Temperatur aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingHum", "caption" => "Logging Luftfeuchtigkeit aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingPres", "caption" => "Logging Luftdruck aktivieren");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "An den folgenden Werten muss in der Regel nichts verändert werden");
		
		$arrayElements[] = array("type" => "Label", "label" => "Oversampling Temperatur (Default: x1)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "0 (aus)", "value" => 0);
		$arrayOptions[] = array("label" => "x1 (Default)", "value" => 1);
		$arrayOptions[] = array("label" => "x2", "value" => 2);
		$arrayOptions[] = array("label" => "x4", "value" => 3);
		$arrayOptions[] = array("label" => "x8", "value" => 4);
		$arrayOptions[] = array("label" => "x16", "value" => 5);
		$arrayElements[] = array("type" => "Select", "name" => "OSRS_T", "caption" => "Oversampling", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Oversampling Luftfeuchtigkeit (Default: x1)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "0 (aus)", "value" => 0);
		$arrayOptions[] = array("label" => "x1 (Default)", "value" => 1);
		$arrayOptions[] = array("label" => "x2", "value" => 2);
		$arrayOptions[] = array("label" => "x4", "value" => 3);
		$arrayOptions[] = array("label" => "x8", "value" => 4);
		$arrayOptions[] = array("label" => "x16", "value" => 5);
		$arrayElements[] = array("type" => "Select", "name" => "OSRS_H", "caption" => "Oversampling", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "Oversampling Luftdruck (Default: x1)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "0 (aus)", "value" => 0);
		$arrayOptions[] = array("label" => "x1 (Default)", "value" => 1);
		$arrayOptions[] = array("label" => "x2", "value" => 2);
		$arrayOptions[] = array("label" => "x4", "value" => 3);
		$arrayOptions[] = array("label" => "x8", "value" => 4);
		$arrayOptions[] = array("label" => "x16", "value" => 5);
		$arrayElements[] = array("type" => "Select", "name" => "OSRS_P", "caption" => "Oversampling", "options" => $arrayOptions );
       		$arrayElements[] = array("type" => "Label", "label" => "Mode (Default: Normal Mode)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Sleep Mode", "value" => 0);
		$arrayOptions[] = array("label" => "Forced Mode", "value" => 1);
		$arrayOptions[] = array("label" => "Normal Mode (Default)", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "Mode", "caption" => "Mode", "options" => $arrayOptions );
      		$arrayElements[] = array("type" => "Label", "label" => "IIR-Filter (Default: 0->aus)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "0 (aus)", "value" => 0);
		$arrayOptions[] = array("label" => "2", "value" => 1);
		$arrayOptions[] = array("label" => "4", "value" => 2);
		$arrayOptions[] = array("label" => "8", "value" => 3);
		$arrayOptions[] = array("label" => "16", "value" => 4);
		$arrayElements[] = array("type" => "Select", "name" => "IIR_Filter", "caption" => "IIR_Filter", "options" => $arrayOptions );
        	$arrayElements[] = array("type" => "Label", "label" => "StandBy Zeit (Default: 1000ms)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "0.5", "value" => 0);
		$arrayOptions[] = array("label" => "62.5", "value" => 1);
		$arrayOptions[] = array("label" => "125", "value" => 2);
		$arrayOptions[] = array("label" => "250", "value" => 3);
		$arrayOptions[] = array("label" => "500", "value" => 4);
		$arrayOptions[] = array("label" => "1000 (Default)", "value" => 5);
		$arrayOptions[] = array("label" => "10", "value" => 6);
		$arrayOptions[] = array("label" => "20", "value" => 7);
		$arrayElements[] = array("type" => "Select", "name" => "SB_T", "caption" => "StandBy Zeit (ms)", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Hinweise:");
		$arrayElements[] = array("type" => "Label", "label" => "- die Device Adresse lautet 118 dez (0x76h) bei SDO an GND");
		$arrayElements[] = array("type" => "Label", "label" => "- die Device Adresse lautet 119 dez (0x77h) als Default");
		$arrayElements[] = array("type" => "Label", "label" => "- die I2C-Nutzung muss in der Raspberry Pi-Konfiguration freigegeben werden (sudo raspi-config -> Advanced Options -> I2C Enable = true)");
		$arrayElements[] = array("type" => "Label", "label" => "- die korrekte Nutzung der GPIO ist zwingend erforderlich (GPIO-Nr. 0/1 nur beim Raspberry Pi Model B Revision 1, alle anderen GPIO-Nr. 2/3)");
		$arrayElements[] = array("type" => "Label", "label" => "- auf den korrekten Anschluss von SDA/SCL achten");	
		
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}  
	    
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
  
		// Device Adresse prüfen
	    	If (($this->ReadPropertyInteger("DeviceAddress") < 0) OR ($this->ReadPropertyInteger("DeviceAddress") > 128)) {
	    		IPS_LogMessage("IPS2GPIO BME280","I2C-Device Adresse in einem nicht definierten Bereich!");  
	    	}
	    	
		// Profil anlegen
		$this->RegisterProfileFloat("IPS2GPIO.gm3", "Drops", "", " g/m³", 0, 1000, 0.1, 1);
		
		$this->RegisterProfileInteger("IPS2GPIO.AirQuality", "Information", "", "", 0, 6, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 0, "unbekannt", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 1, "gut", "Information", 0x58FA58);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 2, "durchschnittlich", "Information", 0xF7FE2E);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 3, "etwas schlecht", "Information", 0xFE9A2E);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 4, "schlecht", "Information", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 5, "schlechter", "Information", 0x61380B);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 6, "sehr schlecht", "Information", 0x000000);
		
		//Status-Variablen anlegen
             	$this->RegisterVariableFloat("Temperature", "Temperature", "~Temperature", 10);
		$this->DisableAction("Temperature");
		IPS_SetHidden($this->GetIDForIdent("Temperature"), false);
		
		$this->RegisterVariableFloat("Pressure", "Pressure (abs)", "~AirPressure.F", 20);
		$this->DisableAction("Pressure");
		IPS_SetHidden($this->GetIDForIdent("Pressure"), false);
		
		$this->RegisterVariableFloat("PressureRel", "Pressure (rel)", "~AirPressure.F", 30);
		$this->DisableAction("PressureRel");
		IPS_SetHidden($this->GetIDForIdent("PressureRel"), false);
		
		$this->RegisterVariableFloat("HumidityAbs", "Humidity (abs)", "IPS2GPIO.gm3", 40);
		$this->DisableAction("HumidityAbs");
		IPS_SetHidden($this->GetIDForIdent("HumidityAbs"), false);
		
		$this->RegisterVariableFloat("Humidity", "Humidity (rel)", "~Humidity.F", 50);
		$this->DisableAction("Humidity");
		IPS_SetHidden($this->GetIDForIdent("Humidity"), false);
		
		$this->RegisterVariableFloat("DewPointTemperature", "Dew Point Temperature", "~Temperature", 60);
		$this->DisableAction("DewPointTemperature");
		IPS_SetHidden($this->GetIDForIdent("DewPointTemperature"), false);
		
		$this->RegisterVariableFloat("PressureTrend1h", "Pressure trend 1h", "~AirPressure.F", 70);
		$this->DisableAction("PressureTrend1h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend1h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend1h"), 0);
		
		$this->RegisterVariableFloat("PressureTrend3h", "Pressure trend 3h", "~AirPressure.F", 80);
		$this->DisableAction("PressureTrend3h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend3h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend3h"), 0);
		
		$this->RegisterVariableFloat("PressureTrend12h", "Pressure trend 12h", "~AirPressure.F", 90);
		$this->DisableAction("PressureTrend12h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend12h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend12h"), 0);
		
		$this->RegisterVariableFloat("PressureTrend24h", "Pressure trend 24h", "~AirPressure.F", 100);
		$this->DisableAction("PressureTrend24h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend24h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend24h"), 0);
		
		$this->RegisterVariableInteger("AirQuality", "AirQuality", "IPS2GPIO.AirQuality", 110);
		$this->DisableAction("AirQuality");
		IPS_SetHidden($this->GetIDForIdent("AirQuality"), false);
		SetValueInteger($this->GetIDForIdent("AirQuality"), 0);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// Logging setzen
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Temperature"), $this->ReadPropertyBoolean("LoggingTemp"));
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Pressure"), $this->ReadPropertyBoolean("LoggingPres"));
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Humidity"), $this->ReadPropertyBoolean("LoggingHum"));
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					// Parameterdaten zum Baustein senden
					$this->Setup();
					// Erste Messdaten einlesen
					$this->Measurement();
					$this->SetStatus(102);
				}
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
			// Messwerte aktualisieren
			$this->ReadData();
			$MeasurementData = array();
			$MeasurementData = unserialize($this->GetBuffer("MeasurementData"));
			$this->SendDebug("Measurement", "MeasurementData: ".count($MeasurementData), 0);
			$this->SendDebug("Measurement", "MeasurementData: ".$this->GetBuffer("MeasurementData"), 0);
			// Byte 1 (29): eas_status_0
			// Byte 2 (30): unwichtig
			// Byte 3 (31): press_msb
			// Byte 4 (32): press_lsb
			// Byte 5 (33): press_xlsb
			// Byte 6 (34): temp_msb
			// Byte 7 (35): temp_lsb
			// Byte 8 (36): temp_xlsb
			// Byte 9 (37): hum_msb
			// Byte 10 (38): hum_lsb
			// Byte 11 (39): unwichtig
			// Byte 12 (40): unwichtig
			// Byte 13 (41): unwichtig
			// Byte 14 (42): gas_r_msb
			// Byte 15 (43): gas_r_msb
		}
	}	
	
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			/*
			$osrs_t = $this->ReadPropertyInteger("OSRS_T"); // Oversampling Measure temperature x1, x2, x4, x8, x16 (dec: 0 (off), 1, 2, 3, 4)
			$osrs_p = $this->ReadPropertyInteger("OSRS_P"); // Oversampling Measure pressure x1, x2, x4, x8, x16 (dec: 0 (off), 1, 2, 3, 4)
			$osrs_h = $this->ReadPropertyInteger("OSRS_H"); // Oversampling Measure humidity x1, x2, x4, x8, x16 (dec: 0 (off), 1, 2, 3, 4)
			$mode = $this->ReadPropertyInteger("Mode"); // 0 = Power Off (Sleep Mode), x01 und x10 Force Mode, 11 Normal Mode
			$t_sb = $this->ReadPropertyInteger("SB_T"); // StandBy Time: dec: 0 (0.5ms) - 5 (1000ms), 6 (10ms), 7 (20ms)
			$filter = $this->ReadPropertyInteger("IIR_Filter"); // IIR-Filter 0-> off - 2, 4, 8, 16 (dec: 0 (off) - 4)
			$spi3w_en = 0;
			$ctrl_meas_reg = (($osrs_t << 5)|($osrs_p << 2)|$mode);
			$config_reg = (($t_sb << 5)|($filter << 2)|$spi3w_en);
			$ctrl_hum_reg = $osrs_h;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME280_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F2"), "Value" => $ctrl_hum_reg)));
			If (!$Result) {
				$this->SendDebug("Setup", "ctrl_hum_reg setzen fehlerhaft!", 0);
				return;
			}
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME280_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F4"), "Value" => $ctrl_meas_reg)));
			If (!$Result) {
				$this->SendDebug("Setup", "ctrl_meas_reg setzen fehlerhaft!", 0);
				return;
			}
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME280_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F5"), "Value" => $config_reg)));
			If (!$Result) {
				$this->SendDebug("Setup", "config_reg setzen fehlerhaft!", 0);
				return;
			}
			*/
		}
	}
		
	private function ReadData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Liest die Messdaten ein
			$this->SendDebug("ReadData", "Ausfuehrung", 0);
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_read_block", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("1D"), "Count" => 14)));
			If ($Result < 0) {
				$MeasurementData = array();
				$this->SetBuffer("MeasurementData", serialize($MeasurementData));
				$this->SendDebug("ReadData", "Fehler bei der Datenermittung", 0);
				return;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetBuffer("MeasurementData", $Result);
				}
			}
		}	
	}
	
	private function PressureTrend(int $interval)
	{
		$Result = 0;
		$LoggingArray = AC_GetLoggedValues(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Pressure"), time()- (3600 * $interval), time(), 0); 
		$Result = @($LoggingArray[0]['Value'] - end($LoggingArray)['Value']); 
	return $Result;
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
				$DevicePorts[$i] = "MUX I²C-Bus ".($i -3);
			}
			$I2C_Ports = serialize($DevicePorts);
		}
	return $I2C_Ports;
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
