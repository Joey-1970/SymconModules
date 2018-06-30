<?
    // Klassendefinition
    class IPS2GPIO_BMP180 extends IPSModule 
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
            	// https://github.com/BoschSensortec/BMP180_driver
		
		// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("DeviceAddress", 119);
		$this->RegisterPropertyInteger("DeviceBus", 1);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
 	    	$this->RegisterPropertyBoolean("LoggingTemp", false);
 	    	$this->RegisterPropertyBoolean("LoggingPres", false);
 	    	$this->RegisterPropertyInteger("OSRS_P", 0);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GBMP180_Measurement($_IPS["TARGET"]);');
		$CalibrateData = array();
		$this->SetBuffer("CalibrateData", serialize($CalibrateData));
		
		//Status-Variablen anlegen
             	$this->RegisterVariableInteger("ChipID", "Chip ID", "", 5);
		$this->DisableAction("ChipID");
		IPS_SetHidden($this->GetIDForIdent("ChipID"), true);
		
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 10);
		$this->DisableAction("Temperature");
		
		$this->RegisterVariableFloat("Pressure", "Luftdruck (abs)", "~AirPressure.F", 20);
		$this->DisableAction("Pressure");
		
		$this->RegisterVariableFloat("PressureTrend1h", "Luftdruck 1h-Trend", "~AirPressure.F", 70);
		$this->DisableAction("PressureTrend1h");
		
		$this->RegisterVariableFloat("PressureTrend3h", "Luftdruck 3h-Trend", "~AirPressure.F", 80);
		$this->DisableAction("PressureTrend3h");
		
		$this->RegisterVariableFloat("PressureTrend12h", "Luftdruck 12h-Trend", "~AirPressure.F", 90);
		$this->DisableAction("PressureTrend12h");
		
		$this->RegisterVariableFloat("PressureTrend24h", "Luftdruck 24h-Trend", "~AirPressure.F", 100);
		$this->DisableAction("PressureTrend24h");
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
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingTemp", "caption" => "Logging Temperatur aktivieren");
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingPres", "caption" => "Logging Luftdruck aktivieren");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");	
		$arrayElements[] = array("type" => "Label", "label" => "Oversampling Luftdruck (Default: x1)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "x1 (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "x2", "value" => 1);
		$arrayOptions[] = array("label" => "x4", "value" => 2);
		$arrayOptions[] = array("label" => "x8", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "OSRS_P", "caption" => "Oversampling", "options" => $arrayOptions );       	
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Hinweise:");
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
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// Logging setzen
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Temperature"), $this->ReadPropertyBoolean("LoggingTemp"));
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Pressure"), $this->ReadPropertyBoolean("LoggingPres"));
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
			
			// Summary setzen
			$DevicePorts = array();
			$DevicePorts = unserialize($this->Get_I2C_Ports());
			$this->SetSummary("Adresse: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." Bus: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]);

			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					// SoftReset
					$this->SoftReset();
					// Parameterdaten zum Baustein senden
					$this->Setup();
					// Kalibrierungsdaten einlesen
					$this->ReadCalibrateData();
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
			$CalibrateData = array();
			If (is_array(unserialize($this->GetBuffer("CalibrateData"))) == false) {
				$this->SendDebug("Measurement", "Kalibrirungsdaten nicht korrekt!", 0);
				$this->ReadCalibrateData();
			}
			$CalibrateData = unserialize($this->GetBuffer("CalibrateData"));
			$this->SendDebug("Measurement", "CalibrateData: ".count($CalibrateData), 0);
			If (count($CalibrateData) == 22)  {
				// Kalibrierungsdatan aufbereiten
				$AC1 =  $this->bin16dec(($CalibrateData[170] << 8) | $CalibrateData[171]);
				$AC2 =  $this->bin16dec(($CalibrateData[172] << 8) | $CalibrateData[173]);
				$AC3 =  $this->bin16dec(($CalibrateData[174] << 8) | $CalibrateData[175]);
				$AC4 =  ($CalibrateData[176] << 8) | $CalibrateData[177];
				$AC5 =  ($CalibrateData[178] << 8) | $CalibrateData[179];
				$AC6 =  ($CalibrateData[180] << 8) | $CalibrateData[181];
				
				$B1 =  $this->bin16dec(($CalibrateData[182] << 8) | $CalibrateData[183]);
				$B2 =  $this->bin16dec(($CalibrateData[184] << 8) | $CalibrateData[185]);
				
				$MB =  $this->bin16dec(($CalibrateData[186] << 8) | $CalibrateData[187]);
				$MC =  $this->bin16dec(($CalibrateData[188] << 8) | $CalibrateData[189]);
				$MD =  $this->bin16dec(($CalibrateData[190] << 8) | $CalibrateData[191]);
				
				
				// Messwerte aufbereiten
				
				// Roh-Temperatur einlesen
				$Temp_raw = $this->ReadTemperatureData();

				// Roh-Luftdruck einlesen
				$Pres_raw = $this->ReadPressureData();

				// Temperatur
				$Temp = 0;
				$X1 = ($Temp_raw - $AC6) * $AC5 / pow(2, 15);
				$X2 = $MC * pow(2, 11) / ($X1 + $MD);
				$B5 = $X1 + $X2;
				$Temp = ($B5 + 8) / pow(2, 4);
				SetValueFloat($this->GetIDForIdent("Temperature"), round(($Temp / 10), 2));

				// Luftdruck
				$Pressure = 0;
				$osrs_p = $this->ReadPropertyInteger("OSRS_P");
				$B6 = $B5 - 4000;
				$X1 = ($B2 * ($B6 * $B6 / pow(2, 12) )) / pow(2, 11);
				$X2 = $AC2 * $B6 / pow(2, 11);
				$X3 = $X1 + $X2;
				$B3 = ((($AC1 * 4 + $X3) << $osrs_p) + 2) / 4;
				$X1 = $AC3 * $B6 / pow(2, 13);
				$X2 = ($B1 * ($B6 * $B6 / pow(2, 12) )) / pow(2, 15);
				$X3 = (($X1 + $X2) + 2) / pow(2, 2);
				$B4 = $AC4 * abs($X3 + 32768) / pow(2, 15);
				$B7 = (abs($Pres_raw - $B3)) * (50000 >> $osrs_p);
				If ($B7 < hexdec("80000000")) {
					$p = ($B7 * 2) / $B4;
				}
				else {
					$p = ($B7 / $B4) * 2;
				}
				$X1 = ($p / pow(2, 8)) * ($p / pow(2, 8));
				$X1 = ($X1 * 3038) / pow(2, 15);
				$X2 = (-7357 * $p) / pow(2, 15);
				$Pressure = $p + ($X1 + $X2 + 3791) / pow(2, 4);
				SetValueFloat($this->GetIDForIdent("Pressure"), round($Pressure / 100, 2));

				// Luftdruck Trends
				If ($this->ReadPropertyBoolean("LoggingPres") == true) {
					SetValueFloat($this->GetIDForIdent("PressureTrend1h"), $this->PressureTrend(1));
					SetValueFloat($this->GetIDForIdent("PressureTrend3h"), $this->PressureTrend(3));
					SetValueFloat($this->GetIDForIdent("PressureTrend12h"), $this->PressureTrend(12));
					SetValueFloat($this->GetIDForIdent("PressureTrend24h"), $this->PressureTrend(24));
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
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			
			// Lesen der ChipID
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME280_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("D0"))));
				If ($Result < 0) {
					$this->SendDebug("Setup", "Fehler beim Einlesen der Chip ID", 0);
					$this->SetStatus(202);
					return;
				}
				else {
					$this->SetStatus(102);
					SetValueInteger($this->GetIDForIdent("ChipID"), $Result);
					If ($Result <> 85) {
						$this->SendDebug("Setup", "Laut Chip ID ist es kein BMP180!", 0);
					}
				}
		}
	}
	
	private function ReadCalibrateData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Kalibrierungsdaten neu einlesen
			$this->SendDebug("ReadCalibrateData", "Ausfuehrung", 0);
			$CalibrateData = array();
			for ($i = hexdec("AA"); $i < (hexdec("AA") + 22); $i++) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BMP180_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $i)));
				If ($Result < 0) {
					$this->SendDebug("ReadCalibrateData", "Fehler beim Einlesen der Kalibrierungsdaten bei Byte ".$i, 0);
					$this->SetStatus(202);
					return;
				}
				else {
					$this->SetStatus(102);
					$CalibrateData[$i] = $Result;
				}
			}
			$this->SetBuffer("CalibrateData", serialize($CalibrateData));
		}
	}
	
	private function ReadTemperatureData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Liest die Messdaten ein
			$this->SendDebug("ReadTemperaturData", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME280_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F4"), "Value" => hexdec("2E"))));
			If (!$Result) {
				$this->SendDebug("ReadTemperatureData", "Abfrage der Roh-Temperatur fehlerhaft", 0);
				$this->SetStatus(202);
				return 0;
			}
			IPS_Sleep(5);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BMP180_read_block", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F6"), "Count" => 2)));
			If ($Result < 0) {
				$this->SendDebug("ReadTemperatureData", "Fehler bei der Datenermittung", 0);
				$this->SetStatus(202);
				return 0;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					$this->SendDebug("ReadTemperaturData", "Ergebnis: ".$Result, 0);
					$MeasurementData = array();
					$MeasurementData = unserialize($Result);
					$Temp_raw =  ($MeasurementData[1] << 8) | $MeasurementData[2];
					$this->SendDebug("ReadTemperatureData", "Roh-Temperatur: ".$Temp_raw, 0);
					return $Temp_raw;
				}
			}
		}	
	}
	
	private function ReadPressureData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Liest die Messdaten ein
			$osrs_p = $this->ReadPropertyInteger("OSRS_P");
			$this->SendDebug("ReadPressureData", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME280_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F4"), "Value" => (hexdec("34") << $osrs_p) )));
			If (!$Result) {
				$this->SendDebug("ReadPressureData", "Abfrage des Roh-Luftdrucks fehlerhaft", 0);
				$this->SetStatus(202);
				return 0;
			}
			$WaitTime = array(5, 8, 14, 26);
			IPS_Sleep($WaitTime[$osrs_p]);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BMP180_read_block", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F6"), "Count" => 3)));
			If ($Result < 0) {
				$this->SendDebug("ReadPressureData", "Fehler bei der Datenermittung", 0);
				$this->SetStatus(202);
				return 0;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					$this->SendDebug("ReadPressureData", "Ergebnis: ".$Result, 0);
					$MeasurementData = array();
					$MeasurementData = unserialize($Result);
					$Pres_raw = (($MeasurementData[1] << 16) | ($MeasurementData[2] << 8) | $MeasurementData[3]) >> (8 - $osrs_p);
					$this->SendDebug("ReadPressureData", "Roh-Luftdruck: ".$Pres_raw, 0);
					return $Pres_raw;
				}
			}
		}	
	}			       
					       
	private function SoftReset()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Führt einen SoftReset aus
			$this->SendDebug("SoftReset", "Ausfuehrung", 0);
			$reg_addr = hexdec("E0");
			$soft_rst_cmd = hexdec("B6");
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BMP180_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $reg_addr, "Value" => $soft_rst_cmd)));
			If (!$Result) {
				$this->SendDebug("SoftReset", "SoftReset fehlerhaft!", 0);
				$this->SetStatus(202);
				return;
			}
			IPS_Sleep(5); 
		}
	}    
	    
	private function bin16dec($dec) 
	{
	    	// converts 8bit binary number string to integer using two's complement
	    	$BinString = decbin($dec);
		$DecNumber = bindec($BinString) & 0xFFFF; // only use bottom 16 bits
	    	If (0x8000 & $DecNumber) {
			$DecNumber = - (0x010000 - $DecNumber);
	    	}
	return $DecNumber;
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
				$DevicePorts[$i] = "MUX I²C-Bus ".($i - 3);
			}
			$I2C_Ports = serialize($DevicePorts);
		}
	return $I2C_Ports;
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
