<?
    // Klassendefinition
    class IPS2GPIO_BME280 extends IPSModule 
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
 	    	//$this->RegisterPropertyBoolean("CalibrateData", true);
 	    	$this->RegisterPropertyInteger("OSRS_T", 1);
 	    	$this->RegisterPropertyInteger("OSRS_H", 1);
 	    	$this->RegisterPropertyInteger("OSRS_P", 1);
 	    	$this->RegisterPropertyInteger("Mode", 3);
 	    	$this->RegisterPropertyInteger("SB_T", 5);
 	    	$this->RegisterPropertyInteger("IIR_Filter", 0);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GBME_Measurement($_IPS["TARGET"]);');
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
		$this->RegisterProfileFloat("humidity.gm3", "Drops", "", " g/m³", 0, 1000, 0.1, 1);
		
		//Status-Variablen anlegen
             	$this->RegisterVariableFloat("Temperature", "Temperature", "~Temperature", 10);
		$this->DisableAction("Temperature");
		IPS_SetHidden($this->GetIDForIdent("Temperature"), false);
		
		$this->RegisterVariableFloat("Pressure", "Pressure", "~AirPressure.F", 20);
		$this->DisableAction("Pressure");
		IPS_SetHidden($this->GetIDForIdent("Pressure"), false);
		
		$this->RegisterVariableFloat("Humidity", "Humidity (rel)", "~Humidity.F", 30);
		$this->DisableAction("Humidity");
		IPS_SetHidden($this->GetIDForIdent("Humidity"), false);
		
		$this->RegisterVariableFloat("DewPointTemperature", "Dew Point Temperature", "~Temperature", 40);
		$this->DisableAction("DewPointTemperature");
		IPS_SetHidden($this->GetIDForIdent("DewPointTemperature"), false);
		
		$this->RegisterVariableFloat("HumidityAbs", "Humidity (abs)", "humidity.gm3", 50);
		$this->DisableAction("HumidityAbs");
		IPS_SetHidden($this->GetIDForIdent("HumidityAbs"), false);
		
		$this->RegisterVariableFloat("PressureTrend1h", "Pressure trend 1h", "~AirPressure.F", 60);
		$this->DisableAction("PressureTrend1h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend1h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend1h"), 0);
		
		$this->RegisterVariableFloat("PressureTrend3h", "Pressure trend 3h", "~AirPressure.F", 70);
		$this->DisableAction("PressureTrend3h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend3h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend3h"), 0);
		
		$this->RegisterVariableFloat("PressureTrend12h", "Pressure trend 12h", "~AirPressure.F", 80);
		$this->DisableAction("PressureTrend12h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend12h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend12h"), 0);
		
		$this->RegisterVariableFloat("PressureTrend24h", "Pressure trend 24h", "~AirPressure.F", 90);
		$this->DisableAction("PressureTrend24h");
		IPS_SetHidden($this->GetIDForIdent("PressureTrend24h"), false);
		SetValueFloat($this->GetIDForIdent("PressureTrend24h"), 0);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Temperature"), $this->ReadPropertyBoolean("LoggingTemp"));
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Pressure"), $this->ReadPropertyBoolean("LoggingPres"));
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Humidity"), $this->ReadPropertyBoolean("LoggingHum"));
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);

			//ReceiveData-Filter setzen
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
				// Parameterdaten zum Baustein senden
				$this->Setup();
				// Kalibrierungsdaten einlesen
				$this->ReadCalibrateData();
				// Erste Messdaten einlesen
				$this->Measurement();
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
			  		// Daten zur Kalibrierung
			  		If (($data->Register >= hexdec("88")) AND ($data->Register < hexdec("E8"))) {
			  			$CalibrateData = unserialize($this->GetBuffer("CalibrateData"));
			  			$CalibrateData[$data->Register] = $data->Value;
			  			$this->SetBuffer("CalibrateData", serialize($CalibrateData));
			  		}
			  	}
			  	break;
			  case "set_i2c_byte_block":
			   	If ($data->DeviceIdent == $this->GetBuffer("DeviceIdent")) {
			   		$this->SetBuffer("MeasurementData", $data->ByteArray);
			   	}
			   	break;
	 	}
 	}
	// Beginn der Funktionen

	// Führt eine Messung aus
	public function Measurement()
	{
		//IPS_LogMessage("IPS2GPIO BME280", "Messung!");
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Measurement", "Messung ausfuehren", 0);
			// Messwerte aktualisieren
			$CalibrateData = unserialize($this->GetBuffer("CalibrateData"));
			$this->SendDebug("Measurement", "CalibrateData: ".count($CalibrateData), 0);
			If (count($CalibrateData) == 32)  {
				$this->ReadData();
				// Kalibrierungsdatan aufbereiten
				$Dig_T[0] = (($CalibrateData[137] << 8) | $CalibrateData[136]);
				$Dig_T[1] = (($CalibrateData[139] << 8) | $CalibrateData[138]);
				$Dig_T[2] = (($CalibrateData[141] << 8) | $CalibrateData[140]);

				$Dig_P[0] = (($CalibrateData[143] << 8) | $CalibrateData[142]);
				$Dig_P[1] = (($CalibrateData[145] << 8) | $CalibrateData[144]);
				$Dig_P[2] = (($CalibrateData[147] << 8) | $CalibrateData[146]);
				$Dig_P[3] = (($CalibrateData[149] << 8) | $CalibrateData[148]);
				$Dig_P[4] = (($CalibrateData[151] << 8) | $CalibrateData[150]);
				$Dig_P[5] = (($CalibrateData[153] << 8) | $CalibrateData[152]);
				$Dig_P[6] = (($CalibrateData[155] << 8) | $CalibrateData[154]);
				$Dig_P[7] = (($CalibrateData[157] << 8) | $CalibrateData[156]);
				$Dig_P[8] = (($CalibrateData[159] << 8) | $CalibrateData[158]);

				$Dig_H[0] = $CalibrateData[161];
				$Dig_H[1] = (($CalibrateData[226] << 8) | $CalibrateData[225]);
				$Dig_H[2] = $CalibrateData[227];
				$Dig_H[3] = (($CalibrateData[228] << 4) | (hexdec("0F") & $CalibrateData[229]));
				$Dig_H[4] = (($CalibrateData[230] << 4) | (($CalibrateData[229] >> 4) & hexdec("0F")));
				$Dig_H[5] = $CalibrateData[231];

				for ($i = 1; $i <= 2; $i++) {
					If ($Dig_T[$i] & hexdec("8000")) {
						$Dig_T[$i] = (-$Dig_T[$i] ^ hexdec("FFFF")) + 1;
					}
				}
			
				for ($i = 1; $i <= 8; $i++) {
					If ($Dig_P[$i] & hexdec("8000")) {
						$Dig_P[$i] = (-$Dig_P[$i] ^ hexdec("FFFF")) + 1;
					}
				}

				for ($i = 0; $i <= 5; $i++) {
					If ($Dig_H[$i] & hexdec("8000")) {
						$Dig_H[$i] = (-$Dig_H[$i] ^ hexdec("FFFF")) + 1;
					}
				}

				// Messwerte aufbereiten
				$MeasurementData = unserialize($this->GetBuffer("MeasurementData"));
				$this->SendDebug("Measurement", "MeasurementData: ".count($MeasurementData), 0);
				If (count($MeasurementData) == 8) {
					$Pres_raw = (($MeasurementData[1] << 12) | ($MeasurementData[2] << 4) | ($MeasurementData[3] >> 4));
					$Temp_raw = (($MeasurementData[4] << 12) | ($MeasurementData[5] << 4) | ($MeasurementData[6] >> 4));
					$Hum_raw =  (($MeasurementData[7] << 8) | $MeasurementData[8]);

					$FineCalibrate = 0;

					// Temperatur
					$V1 = ($Temp_raw / 16384 - $Dig_T[0] / 1024) * $Dig_T[1];
					$V2 = ($Temp_raw / 131072 - $Dig_T[0] / 8192) * ($Temp_raw / 131072 - $Dig_T[0] / 8192) * $Dig_T[2];
					$FineCalibrate = $V1 + $V2;
					$Temp = $FineCalibrate / 5120;
					SetValueFloat($this->GetIDForIdent("Temperature"), round($Temp, 2));
				
					// Luftdruck
					$Pressure = 0;
					$V1 = ($FineCalibrate / 2) - 64000;
					$V2 = ((($V1 / 4) * ($V1 / 4)) / 2048) * $Dig_P[5];
					$V2 = $V2 + (($V1 * $Dig_P[4]) * 2);
					$V2 = ($V2 / 4) + ($Dig_P[3] * 65536);
					$V1 = ((($Dig_P[2] * ((($V1 / 4) * ($V1 / 4)) / 8192)) / 8) + (($Dig_P[1] * $V1) / 2)) / 262144;
					$V1 = ((32768 + $V1) * $Dig_P[0]) / 32768;

					If ($V1 == 0) {
						SetValueFloat($this->GetIDForIdent("Pressure"), "0");
					}
					$Pressure = ((1048576 - $Pres_raw) - ($V2 / 4096)) * 3125;

					If ($Pressure < hexdec("80000000")) {
						$Pressure = ($Pressure * 2) / $V1;
					}
					else {
						$Pressure = ($Pressure / $V1) * 2;
					}
					$V1 = ($Dig_P[8] * ((($Pressure / 8) * ($Pressure / 8)) / 8192)) / 4096;
					$V2 = (($Pressure / 4) * $Dig_P[7]) / 8192;
					$Pressure = $Pressure + (($V1 + $V2 + $Dig_P[6]) / 16);

					SetValueFloat($this->GetIDForIdent("Pressure"), round($Pressure / 100, 2));
				
					// Luftfeuchtigkeit
					$Hum = $FineCalibrate - 76800;
					If ($Hum <> 0) {
						$Hum = ($Hum_raw - ($Dig_H[3] * 64 + $Dig_H[4] / 16384 * $Hum)) * ($Dig_H[1]  / 65536 * (1 + $Dig_H[5] / 67108864 * $Hum * (1 + $Dig_H[2] / 67108864 * $Hum)));
					}
					else {
						SetValueFloat($this->GetIDForIdent("Humidity"), 0);
					}
					$Hum = $Hum * (1 - $Dig_H[0] * $Hum / 524288);
					If ($Hum > 100) {
						$Hum = 100;
					}
					elseif ($Hum < 0) {
						$Hum = 0;
					}

					SetValueFloat($this->GetIDForIdent("Humidity"), round($Hum, 2));

					// Berechnung von Taupunkt und absoluter Luftfeuchtigkeit
					if ($Temp < 0) {
						$a = 7.6; 
						$b = 240.7;
					}  
					elseif ($Temp >= 0) {
						$a = 7.5;
						$b = 237.3;
					}

					$sdd = 6.1078 * pow(10.0, (($a * $Temp) / ($b + $Temp)));
					$dd = $Hum/100.0 * $sdd;
					$v = log10($dd/6.1078);
					$td = $b * $v / ($a - $v);
					$af = pow(10,5) * 18.016 / 8314.3 * $dd / ($Temp + 273.15);
				
					// Taupunkttemperatur
					SetValueFloat($this->GetIDForIdent("DewPointTemperature"), round($td, 2));

					// Absolute Feuchtigkeit
					SetValueFloat($this->GetIDForIdent("HumidityAbs"), round($af, 2));

					// Luftdruck Trends
					If ($this->ReadPropertyBoolean("LoggingPres") == true) {
						SetValueFloat($this->GetIDForIdent("PressureTrend1h"), $this->PressureTrend(1));
						SetValueFloat($this->GetIDForIdent("PressureTrend3h"), $this->PressureTrend(3));
						SetValueFloat($this->GetIDForIdent("PressureTrend12h"), $this->PressureTrend(12));
						SetValueFloat($this->GetIDForIdent("PressureTrend24h"), $this->PressureTrend(24));
					}
				}
				
			}
			else {
				$this->Setup();
				$this->ReadCalibrateData();
			}
		}
	}	
	
	private function Setup()
	{
		$this->SendDebug("Setup", "Setup setzen", 0);
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
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F2"), "Value" => $ctrl_hum_reg)));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F4"), "Value" => $ctrl_meas_reg)));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F5"), "Value" => $config_reg)));
	}
	
	private function ReadCalibrateData()
	{
		// Kalibrierungsdaten neu einlesen
		$this->SendDebug("ReadCalibrateData", "Aktuelle Kalibrierungsdaten einlesen", 0);
		$CalibrateData = array();
		$this->SetBuffer("CalibrateData", serialize($CalibrateData));

		for ($i = hexdec("88"); $i < (hexdec("88") + 24); $i++) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $i, "Value" => $i)));
		}

		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("A1"), "Value" => $i)));

		for ($i = hexdec("E1"); $i < (hexdec("E1") + 7); $i++) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $i, "Value" => $i)));
		}
	}
	
	private function ReadData()
	{
		// Liest die Messdaten ein
		$this->SendDebug("ReadData", "Aktuelle Messdaten einlesen", 0);
		$MeasurementData = array();
		$this->SetBuffer("MeasurementData", serialize($MeasurementData));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_block_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F7"), "Count" => 8)));
	}
	
	private function PressureTrend(int $interval)
	{
		$Result = 0;
		$LoggingArray = AC_GetLoggedValues(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Pressure"), time()- (3600 * $interval), time(), 0); 
		//IPS_LogMessage("IPS2GPIO BME280",print_r($LoggingArray));
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

}
?>
