<?
    // Klassendefinition
    class IPS2GPIO_BME680 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// https://github.com/BoschSensortec/BME680_driver
		// https://os.mbed.com/users/yangcq88517/code/BME680/file/85088a918342/BME680.h
		
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
		$this->RegisterPropertyInteger("IIR_Filter", 0);
 		$this->RegisterPropertyInteger("Mode", 0); 	    	
		$this->RegisterPropertyInteger("Altitude", 0);
		$this->RegisterPropertyInteger("Temperature_ID", 0);
		$this->RegisterPropertyInteger("Humidity_ID", 0);
		$this->RegisterPropertyInteger("HeaterProfileSetpoint", 0);
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
       		$arrayElements[] = array("type" => "Label", "label" => "Mode (Default: Sleep Mode)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Sleep Mode (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "Forced Mode", "value" => 1);
		$arrayElements[] = array("type" => "Select", "name" => "Mode", "caption" => "Mode", "options" => $arrayOptions );
      		$arrayElements[] = array("type" => "Label", "label" => "IIR-Filter (Default: 0->aus)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "0 (aus)", "value" => 0);
		$arrayOptions[] = array("label" => "1", "value" => 1);
		$arrayOptions[] = array("label" => "3", "value" => 2);
		$arrayOptions[] = array("label" => "7", "value" => 3);
		$arrayOptions[] = array("label" => "15", "value" => 4);
		$arrayOptions[] = array("label" => "31", "value" => 5);
		$arrayOptions[] = array("label" => "63", "value" => 6);
		$arrayOptions[] = array("label" => "127", "value" => 7);
		$arrayElements[] = array("type" => "Select", "name" => "IIR_Filter", "caption" => "IIR_Filter", "options" => $arrayOptions );
        	
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "0", "value" => 0);
		$arrayOptions[] = array("label" => "1", "value" => 1);
		$arrayOptions[] = array("label" => "2", "value" => 2);
		$arrayOptions[] = array("label" => "3", "value" => 3);
		$arrayOptions[] = array("label" => "4", "value" => 4);
		$arrayOptions[] = array("label" => "5", "value" => 5);
		$arrayOptions[] = array("label" => "6", "value" => 6);
		$arrayOptions[] = array("label" => "7", "value" => 7);
		$arrayOptions[] = array("label" => "8", "value" => 8);
		$arrayOptions[] = array("label" => "9", "value" => 9);
		$arrayElements[] = array("type" => "Select", "name" => "HeaterProfileSetpoint", "caption" => "Heater Profile Setpoint", "options" => $arrayOptions );
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
		$this->RegisterProfileFloat("IPS2GPIO.ohm", "Electricity", "", " Ohm", 0, 100000, 0.1, 1);
		
		$this->RegisterProfileInteger("IPS2GPIO.AirQuality", "Information", "", "", 0, 6, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 0, "unbekannt", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 1, "gut", "Information", 0x58FA58);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 2, "durchschnittlich", "Information", 0xF7FE2E);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 3, "etwas schlecht", "Information", 0xFE9A2E);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 4, "schlecht", "Information", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 5, "schlechter", "Information", 0x61380B);
		IPS_SetVariableProfileAssociation("IPS2GPIO.AirQuality", 6, "sehr schlecht", "Information", 0x000000);
		
		//Status-Variablen anlegen
             	$this->RegisterVariableInteger("ChipID", "Chip ID", "", 5);
		$this->DisableAction("ChipID");
		IPS_SetHidden($this->GetIDForIdent("ChipID"), false);
		
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
		
		$this->RegisterVariableFloat("GasResistance", "Gas Resistance", "IPS2GPIO.ohm", 120);
		$this->DisableAction("GasResistance");
		IPS_SetHidden($this->GetIDForIdent("GasResistance"), false);
		
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
					// SoftReset
					$this->SoftReset();
					// Parameterdaten zum Baustein senden
					$this->Setup();
					// Kalibrierungsdaten einlesen
					$this->ReadCalibrateData();
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
			
			$this->read_field_data;
			
			return;
			
			// Messwerte aktualisieren
			$CalibrateData = array();
			If (is_array(unserialize($this->GetBuffer("CalibrateData"))) == false) {
				$this->SendDebug("Measurement", "Kalibrirungsdaten nicht korrekt!", 0);
				$this->ReadCalibrateData();
			}
			$CalibrateData = unserialize($this->GetBuffer("CalibrateData"));
			$this->SendDebug("Measurement", "CalibrateData: ".count($CalibrateData), 0);
			$this->SendDebug("Measurement", "CalibrateData: ".$this->GetBuffer("CalibrateData"), 0);
			If (count($CalibrateData) == 46)  {
				$this->ReadData();
				// Kalibrierungsdatan aufbereiten
				$par_t1 = (($CalibrateData[34] << 8) | $CalibrateData[33]);
				$par_t2 = (($CalibrateData[2] << 8) | $CalibrateData[1]);
				$par_t3 = $CalibrateData[3];

				$par_p1 = (($CalibrateData[6] << 8) | $CalibrateData[5]);
				$par_p2 = (($CalibrateData[8] << 8) | $CalibrateData[7]);
				$par_p3 = $CalibrateData[9];
				$par_p4 = (($CalibrateData[12] << 8) | $CalibrateData[11]);
				$par_p5 = (($CalibrateData[14] << 8) | $CalibrateData[13]);
				$par_p6 = $CalibrateData[16];
				$par_p7 = ($CalibrateData[15]);
				$par_p8 = (($CalibrateData[20] << 8) | $CalibrateData[19]);
				$par_p9 = (($CalibrateData[22] << 8) | $CalibrateData[21]);
				$par_p10 = $CalibrateData[23];

				$par_h1 = (($CalibrateData[27] << 4) | $CalibrateData[26] & hexdec("0F"));
				$par_h2 = (($CalibrateData[26] << 4) | $CalibrateData[25] >> 4);
				$par_h3 = $CalibrateData[28];
				$par_h4 = $CalibrateData[29];
				$par_h5 = $CalibrateData[30];
				$par_h6 = $CalibrateData[31];
				$par_h7 = $CalibrateData[32];

				$par_gh1 = $CalibrateData[37];
				$par_gh2 = (($CalibrateData[36] << 8) | $CalibrateData[35]);
				$par_gh3 = $CalibrateData[38];
				
				$res_heat_val = $CalibrateData[39];
				
				$res_heat_range = ($CalibrateData[41] >> 4) & hexdec("03");
				
				$range_switching_error = ($CalibrateData[43] & hexdec("F0")) >> 4;

				// Messwerte aufbereiten
				$MeasurementData = array();
				$MeasurementData = unserialize($this->GetBuffer("MeasurementData"));
				$this->SendDebug("Measurement", "MeasurementData: ".count($MeasurementData), 0);
				$this->SendDebug("Measurement", "MeasurementData: ".$this->GetBuffer("MeasurementData"), 0);
				If (count($MeasurementData) == 15) {
					$status = $MeasurementData[1] & hexdec("80"); // Flag New_Data_0
					$gas_status = $MeasurementData[1] & hexdec("0F");
					$maes_index = $MeasurementData[2];
					
					$adc_pres = ($MeasurementData[3] * 4096) | ($MeasurementData[4] * 16) | ($MeasurementData[5] / 16);
					$adc_temp = ($MeasurementData[6] * 4096) | ($MeasurementData[7] * 16) | ($MeasurementData[8] / 16);
					$adc_hum = ($MeasurementData[9] * 256) | $MeasurementData[10];
					$adc_gas_res = ($MeasurementData[14] * 4) | ($MeasurementData[15] / 64);
					$gas_range = $MeasurementData[15] & hexdec("0F");
					
					$status = $status | ($MeasurementData[15] & hexdec("20")); // Flag GASM_VALID_R
					$status = $status | ($MeasurementData[15] & hexdec("10")); // Flag HEAT_STAB_R
					
					If (!$status) {
						$this->SendDebug("Measurement", "New Data Flag: ".($MeasurementData[1] & hexdec("80")), 0);
						$this->SendDebug("Measurement", "Flag GASM_VALID_R:".($MeasurementData[15] & hexdec("20")), 0);
						$this->SendDebug("Measurement", "Flag HEAT_STAB_R:".($MeasurementData[15] & hexdec("10")), 0);
						$this->SendDebug("Measurement", "Keine auswertbaren Daten!", 0);
						//return;
					}
					
					// Temperatur
					$var1 = ($adc_temp / 8) - ($par_t1 * 2);
					$var2 = ($var1 * $par_t2) / 2048;
					$var3 = (($var1 / 2) * ($var1 / 2)) / 4096;
					$var3 = (($var3) * ($par_t3 * 16)) / 16384;
					$t_fine = ($var2 + $var3);
					$Temp = ((($t_fine * 5) + 128) / 256);
					SetValueFloat($this->GetIDForIdent("Temperature"), round($Temp, 2));
					
					// Luftdruck
					$var1 = (($t_fine) / 2) - 64000;
					$var2 = (($var1 / 4) * ($var1 / 4)) / 2048;
					$var2 = (($var2) * $par_p6) / 4;
					$var2 = $var2 + (($var1 * $par_p5) * 2);
					$var2 = ($var2 / 4) + ($par_p4 * 65536);
					$var1 = (($var1 / 4) * ($var1 / 4)) / 8192;
					$var1 = ((($var1) * ($par_p3 * 32)) / 8) + (($par_p2 * $var1) / 2);
					$var1 = $var1 / 262144;
					$var1 = ((32768 + $var1) * $par_p1) / 32768;
					$Pressure = (1048576 - $adc_pres);
					$Pressure = (($Pressure - ($var2 / 4096)) * (3125));
					$Pressure = (($Pressure / $var1) * 2);
					$var1 = ($par_p9 * ((($Pressure / 8) * ($Pressure / 8)) / 8192)) / 4096;
					$var2 = (($Pressure / 4) * $par_p8) / 8192;
					$var3 = (($Pressure / 256) * ($Pressure / 256) * ($Pressure / 256) * $par_p10) / 131072;
					$Pressure = ($Pressure) + (($var1 + $var2 + $var3 + ($par_p7 * 128)) / 16);
					SetValueFloat($this->GetIDForIdent("Pressure"), round($Pressure / 100, 2));
					
					// Luftfeuchtigkeit
					$temp_scaled = (($t_fine * 5) + 128) / 256;
					$var1 = ($adc_hum - (($par_h1 * 16))) - ((($temp_scaled * $par_h3) / (100)) / 2);
					$var2 = ($par_h2 * ((($temp_scaled * $par_h4) / (100)) + ((($temp_scaled * (($temp_scaled * $par_h5) / (100))) / 64) / (100)) + (1 * 16384))) / 1024;
					$var3 = $var1 * $var2;
					$var4 = $par_h6 * 128;
					$var4 = (($var4) + (($temp_scaled *$par_h7) / (100))) / 16;
					$var5 = (($var3 / 16384) * ($var3 / 16384)) / 1024;
					$var6 = ($var4 * $var5) / 2;
					$Hum = ((($var3 + $var6) / 1024) * (1000)) / 4096;
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
					
					// Relativen Luftdruck
					$Altitude = $this->ReadPropertyInteger("Altitude");
					If ($this->ReadPropertyInteger("Temperature_ID") > 0) {
						// Wert der Variablen zur Berechnung nutzen
						$Temperature = GetValueInteger($this->ReadPropertyInteger("Temperature_ID"));
					}
					else {
						// Wert dieses BME280 verwenden
						$Temperature = $Temp;
					}
					If ($this->ReadPropertyInteger("Humidity_ID") > 0) {
						// Wert der Variablen zur Berechnung nutzen
						$Humidity = GetValueInteger($this->ReadPropertyInteger("Humidity_ID"));
					}
					else {
						// Wert dieses BME280 verwenden
						$Humidity = $Hum;
					}
					$g_n = 9.80665; // Erdbeschleunigung (m/s^2)
					$gam = 0.0065; // Temperaturabnahme in K pro geopotentiellen Metern (K/gpm)
					$R = 287.06; // Gaskonstante für trockene Luft (R = R_0 / M)
					$M = 0.0289644; // Molare Masse trockener Luft (J/kgK)
					$R_0 = 8.314472; // allgemeine Gaskonstante (J/molK)
					$T_0 = 273.15; // Umrechnung von °C in K
					$C = 0.11; // DWD-Beiwert für die Berücksichtigung der Luftfeuchte
					$E_0 = 6.11213; // (hPa)
					$f_rel = $Humidity / 100; // relative Luftfeuchte (0-1.0)
					// momentaner Stationsdampfdruck (hPa)
					$e_d = $f_rel * $E_0 * exp((17.5043 * $Temperature) / (241.2 + $Temperature));
        				$PressureRel = $Pressure * exp(($g_n * $Altitude) / ($R * ($Temperature + $T_0 + $C * $e_d + (($gam * $Altitude) / 2))));
					SetValueFloat($this->GetIDForIdent("PressureRel"), round($PressureRel / 100, 2));
					
					// Luftdruck Trends
					If ($this->ReadPropertyBoolean("LoggingPres") == true) {
						SetValueFloat($this->GetIDForIdent("PressureTrend1h"), $this->PressureTrend(1));
						SetValueFloat($this->GetIDForIdent("PressureTrend3h"), $this->PressureTrend(3));
						SetValueFloat($this->GetIDForIdent("PressureTrend12h"), $this->PressureTrend(12));
						SetValueFloat($this->GetIDForIdent("PressureTrend24h"), $this->PressureTrend(24));
					}
					
					// Look up table for the possible gas range values
					$lookupTable1 = array(2147483647, 2147483647, 2147483647, 2147483647, 2147483647, 2126008810, 2147483647, 2130303777,2147483647, 
							      2147483647, 2143188679, 2136746228, 2147483647, 2126008810, 2147483647, 2147483647);
					// Look up table for the possible gas range values
					$lookupTable2 = array(4096000000, 2048000000, 1024000000, 512000000, 255744255, 127110228, 64000000, 32258064, 16016016, 
							      8000000, 4000000, 2000000, 1000000, 500000, 250000, 125000);

					
					// Gas Widerstand
					$var1 = ((1340 + (5 * $range_switching_error)) * ($lookupTable1[$gas_range])) / 65536;
					$var2 = ((($adc_gas_res * 32768) - (16777216)) + $var1);
					$var3 = (($lookupTable2[$gas_range] * $var1) / 512);
					$calc_gas_res = (($var3 + ($var2 / 2)) / $var2);
					SetValueFloat($this->GetIDForIdent("GasResistance"), $calc_gas_res);

				}
				
			}
		}
	}	
	
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			$osrs_t = $this->ReadPropertyInteger("OSRS_T"); // Oversampling Measure temperature x1, x2, x4, x8, x16 (dec: 0 (off), 1, 2, 3, 4)
			$osrs_p = $this->ReadPropertyInteger("OSRS_P"); // Oversampling Measure pressure x1, x2, x4, x8, x16 (dec: 0 (off), 1, 2, 3, 4)
			$osrs_h = $this->ReadPropertyInteger("OSRS_H"); // Oversampling Measure humidity x1, x2, x4, x8, x16 (dec: 0 (off), 1, 2, 3, 4)
			$mode = $this->ReadPropertyInteger("Mode"); // 0 = Power Off (Sleep Mode), x01 und x10 Force Mode
			$filter = $this->ReadPropertyInteger("IIR_Filter"); // IIR-Filter 0-> off - 2, 4, 8, 16 (dec: 0 (off) - 4)
			$HeaterProfileSetpoint = $this->ReadPropertyInteger("HeaterProfileSetpoint");
			$run_gas = 1;
			
			$spi3w_en = 0;
			$ctrl_meas_reg = (($osrs_t << 5)|($osrs_p << 2)|$mode);
			$config_reg = (($filter << 2)|$spi3w_en);
			$ctrl_hum_reg = $osrs_h;
			$crtl_gas_0 = hexdec("00"); // Heater enable - Heater disable = hexdec("08")
			$crtl_gas_1 = ($run_gas << 4)|$HeaterProfileSetpoint;
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("72"), "Value" => $ctrl_hum_reg)));
			If (!$Result) {
				$this->SendDebug("Setup", "ctrl_hum_reg setzen fehlerhaft!", 0);
				return;
			}
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME2680_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("74"), "Value" => $ctrl_meas_reg)));
			If (!$Result) {
				$this->SendDebug("Setup", "ctrl_meas_reg setzen fehlerhaft!", 0);
				return;
			}
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("75"), "Value" => $config_reg)));
			If (!$Result) {
				$this->SendDebug("Setup", "config_reg setzen fehlerhaft!", 0);
				return;
			}
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME2680_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("70"), "Value" => $crtl_gas_0)));
			If (!$Result) {
				$this->SendDebug("Setup", "crtl_gas_0 setzen fehlerhaft!", 0);
				return;
			}
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("71"), "Value" => $crtl_gas_1)));
			If (!$Result) {
				$this->SendDebug("Setup", "crtl_gas_1 setzen fehlerhaft!", 0);
				return;
			}
			// ********************************
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("64"), "Value" => hexdec("59") )));
			If (!$Result) {
				$this->SendDebug("Setup", "gas_wait_0 setzen fehlerhaft!", 0);
				return;
			}
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("5A"), "Value" => hexdec("59") )));
			If (!$Result) {
				$this->SendDebug("Setup", "res_heat_0 setzen fehlerhaft!", 0);
				return;
			}
			// **********************************
			// Lesen der ChipID
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("D0"))));
				If ($Result < 0) {
					$this->SendDebug("Setup", "Fehler beim Einlesen der Chip ID", 0);
					return;
				}
				else {
					SetValueInteger($this->GetIDForIdent("ChipID"), $Result);
				}
		
		}
	}

	/*
	private function set_gas_config()
	{
		If ($this->ReadPropertyInteger("Mode") == 1) {
			$reg_addr[0] = hexdec("5A");
			$reg_data[0] = $this->calc_heater_res(dev->gas_sett.heatr_temp, dev);
			$reg_addr[1] = hexdec("64");
			$reg_data[1] = $this->calc_heater_dur(dev->gas_sett.heatr_dur);
			dev->gas_sett.nb_conv = 0;
		}
		else {
			$Result = 1;
		}
		$this->bme680_set_regs(reg_addr, reg_data, 2, dev);
	
	}
	*/    
	  

	    
	    
	private function ReadCalibrateData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			/*
			* @note Registers 89h  to A1h for calibration data 1 to 24
			*        from bit 0 to 7
			* @note Registers E1h to F0h for calibration data 25 to 40
			*        from bit 0 to 7
			*/
			
			// Kalibrierungsdaten neu einlesen
			$this->SendDebug("ReadCalibrateData", "Ausfuehrung", 0);
			$CalibrateData = array();
			for ($i = hexdec("89"); $i < (hexdec("89") + 25); $i++) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $i)));
				If ($Result < 0) {
					$this->SendDebug("ReadCalibrateData", "Fehler beim Einlesen der Kalibrierungsdaten bei Byte ".$i, 0);
					return;
				}
				else {
					//$CalibrateData[$i] = $Result;
					$CalibrateData[] = $Result;
				}
			}
			
			for ($i = hexdec("E1"); $i < (hexdec("E1") + 16); $i++) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $i)));
				If ($Result < 0) {
					$this->SendDebug("ReadCalibrateData", "Fehler beim Einlesen der Kalibrierungsdaten bei Byte ".$i, 0);
					return;
				}
				else {
					//$CalibrateData[$i] = $Result;
					$CalibrateData[] = $Result;
				}
			}
			
			for ($i = hexdec("00"); $i < (hexdec("00") + 5); $i++) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $i)));
				If ($Result < 0) {
					$this->SendDebug("ReadCalibrateData", "Fehler beim Einlesen der Kalibrierungsdaten bei Byte ".$i, 0);
					return;
				}
				else {
					//$CalibrateData[$i] = $Result;
					$CalibrateData[] = $Result;
				}
			}
			$this->SetBuffer("CalibrateData", serialize($CalibrateData));
			
			
		}
	}
	    
	private function ReadData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Liest die Messdaten ein
			$this->SendDebug("ReadData", "Ausfuehrung", 0);
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_read_block", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("1D"), "Count" => 15)));
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
	    
	private function calc_temperature($adc_temp)
	{
		$CalibrateData = array();
		$CalibrateData = unserialize($this->GetBuffer("CalibrateData"));
		// Kalibrierungsdatan aufbereiten
		$par_t1 = (($CalibrateData[34] << 8) | $CalibrateData[33]);
		$par_t2 = (($CalibrateData[2] << 8) | $CalibrateData[1]);
		$par_t3 = $CalibrateData[3];
		
		// Temperatur
		$var1 = ($adc_temp / 8) - ($par_t1 * 2);
		$var2 = ($var1 * $par_t2) / 2048;
		$var3 = (($var1 / 2) * ($var1 / 2)) / 4096;
		$var3 = (($var3) * ($par_t3 * 16)) / 16384;
		$t_fine = ($var2 + $var3);
		$this->SetBuffer("t_fine", $t_fine);
		$Temp = ((($t_fine * 5) + 128) / 256);
		SetValueFloat($this->GetIDForIdent("Temperature"), round($Temp, 2));
	return $Temp;
	}
	
	private function calc_pressure($adc_pres)
	{
		$CalibrateData = array();
		$CalibrateData = unserialize($this->GetBuffer("CalibrateData"));
		// Kalibrierungsdatan aufbereiten
		$par_p1 = (($CalibrateData[6] << 8) | $CalibrateData[5]);
		$par_p2 = (($CalibrateData[8] << 8) | $CalibrateData[7]);
		$par_p3 = $CalibrateData[9];
		$par_p4 = (($CalibrateData[12] << 8) | $CalibrateData[11]);
		$par_p5 = (($CalibrateData[14] << 8) | $CalibrateData[13]);
		$par_p6 = $CalibrateData[16];
		$par_p7 = ($CalibrateData[15]);
		$par_p8 = (($CalibrateData[20] << 8) | $CalibrateData[19]);
		$par_p9 = (($CalibrateData[22] << 8) | $CalibrateData[21]);
		$par_p10 = $CalibrateData[23];
		$t_fine = $this->GetBuffer("t_fine");
		
		// Luftdruck
		$var1 = (($t_fine) / 2) - 64000;
		$var2 = (($var1 / 4) * ($var1 / 4)) / 2048;
		$var2 = (($var2) * $par_p6) / 4;
		$var2 = $var2 + (($var1 * $par_p5) * 2);
		$var2 = ($var2 / 4) + ($par_p4 * 65536);
		$var1 = (($var1 / 4) * ($var1 / 4)) / 8192;
		$var1 = ((($var1) * ($par_p3 * 32)) / 8) + (($par_p2 * $var1) / 2);
		$var1 = $var1 / 262144;
		$var1 = ((32768 + $var1) * $par_p1) / 32768;
		$Pressure = (1048576 - $adc_pres);
		$Pressure = (($Pressure - ($var2 / 4096)) * (3125));
		$Pressure = (($Pressure / $var1) * 2);
		$var1 = ($par_p9 * ((($Pressure / 8) * ($Pressure / 8)) / 8192)) / 4096;
		$var2 = (($Pressure / 4) * $par_p8) / 8192;
		$var3 = (($Pressure / 256) * ($Pressure / 256) * ($Pressure / 256) * $par_p10) / 131072;
		$Pressure = ($Pressure) + (($var1 + $var2 + $var3 + ($par_p7 * 128)) / 16);
		SetValueFloat($this->GetIDForIdent("Pressure"), round($Pressure / 100, 2));
	return $Pressure;
	}
	
	private function calc_humidity($adc_hum)
	{
		$CalibrateData = array();
		$CalibrateData = unserialize($this->GetBuffer("CalibrateData"));
		// Kalibrierungsdatan aufbereiten
		$par_h1 = (($CalibrateData[27] << 4) | $CalibrateData[26] & hexdec("0F"));
		$par_h2 = (($CalibrateData[26] << 4) | $CalibrateData[25] >> 4);
		$par_h3 = $CalibrateData[28];
		$par_h4 = $CalibrateData[29];
		$par_h5 = $CalibrateData[30];
		$par_h6 = $CalibrateData[31];
		$par_h7 = $CalibrateData[32];
		$t_fine = $this->GetBuffer("t_fine");
		
		// Luftfeuchtigkeit
		$temp_scaled = (($t_fine * 5) + 128) / 256;
		$var1 = ($adc_hum - (($par_h1 * 16))) - ((($temp_scaled * $par_h3) / (100)) / 2);
		$var2 = ($par_h2 * ((($temp_scaled * $par_h4) / (100)) + ((($temp_scaled * (($temp_scaled * $par_h5) / (100))) / 64) / (100)) + (1 * 16384))) / 1024;
		$var3 = $var1 * $var2;
		$var4 = $par_h6 * 128;
		$var4 = (($var4) + (($temp_scaled *$par_h7) / (100))) / 16;
		$var5 = (($var3 / 16384) * ($var3 / 16384)) / 1024;
		$var6 = ($var4 * $var5) / 2;
		$Hum = ((($var3 + $var6) / 1024) * (1000)) / 4096;
		If ($Hum > 100) {
			$Hum = 100;
		}
		elseif ($Hum < 0) {
			$Hum = 0;
		}
		SetValueFloat($this->GetIDForIdent("Humidity"), round($Hum, 2));
	return $Hum;
	}
	
	private function calc_gas_resistance($adc_gas_res, $gas_range)
	{
		$this->SendDebug("calc_gas_resistance", "Ausfuehrung", 0);
		$CalibrateData = array();
		$CalibrateData = unserialize($this->GetBuffer("CalibrateData"));
		// Kalibrierungsdatan aufbereiten
		$par_gh1 = $CalibrateData[37];
		$par_gh2 = (($CalibrateData[36] << 8) | $CalibrateData[35]);
		$par_gh3 = $CalibrateData[38];
		$range_switching_error = ($CalibrateData[43] & hexdec("F0")) >> 4;
		
		// Look up table for the possible gas range values
		$lookupTable1 = array(2147483647, 2147483647, 2147483647, 2147483647, 2147483647, 2126008810, 2147483647, 2130303777,2147483647, 
				      2147483647, 2143188679, 2136746228, 2147483647, 2126008810, 2147483647, 2147483647);
		// Look up table for the possible gas range values
		$lookupTable2 = array(4096000000, 2048000000, 1024000000, 512000000, 255744255, 127110228, 64000000, 32258064, 16016016, 
				      8000000, 4000000, 2000000, 1000000, 500000, 250000, 125000);

		// Gas Widerstand
		$var1 = ((1340 + (5 * $range_switching_error)) * ($lookupTable1[$gas_range])) / 65536;
		$var2 = ((($adc_gas_res * 32768) - (16777216)) + $var1);
		$var3 = (($lookupTable2[$gas_range] * $var1) / 512);
		$GasResistance = (($var3 + ($var2 / 2)) / $var2);
		SetValueFloat($this->GetIDForIdent("GasResistance"), $GasResistance);
	return $GasResistant;
	}
	
	private function read_field_data()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Liest die Messdaten ein
			$this->SendDebug("read_field_data", "Ausfuehrung", 0);
			$tries = 10;
			do {
			    	$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_read_block", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("1D"), "Count" => 15)));
				If ($Result < 0) {
					$MeasurementData = array();
					$this->SetBuffer("read_field_data", serialize($MeasurementData));
					$this->SendDebug("read_field_data", "Fehler bei der Datenermittung", 0);
					return;
				}
				else {
					If (is_array(unserialize($Result)) == true) {
						$MeasurementData = array();
						$MeasurementData = unserialize($Result);
						
						$status = $MeasurementData[1] & hexdec("80"); // Flag New_Data_0
						$gas_status = $MeasurementData[1] & hexdec("0F");
						$maes_index = $MeasurementData[2];

						$adc_pres = ($MeasurementData[3] * 4096) | ($MeasurementData[4] * 16) | ($MeasurementData[5] / 16);
						$adc_temp = ($MeasurementData[6] * 4096) | ($MeasurementData[7] * 16) | ($MeasurementData[8] / 16);
						$adc_hum = ($MeasurementData[9] * 256) | $MeasurementData[10];
						$adc_gas_res = ($MeasurementData[14] * 4) | ($MeasurementData[15] / 64);
						$gas_range = $MeasurementData[15] & hexdec("0F");

						$status = $status | ($MeasurementData[15] & hexdec("20")); // Flag GASM_VALID_R
						$status = $status | ($MeasurementData[15] & hexdec("10")); // Flag HEAT_STAB_R
						
						if ($status & hexdec("80")) {
							$this->SetBuffer("Temperature", $this->calc_temperature($adc_temp));
							$this->SetBuffer("Pressure", $this->calc_pressure($adc_pres));
							$this->SetBuffer("Humidity", $this->calc_humidity($adc_hum));
							$this->SetBuffer("GasResistance"), $this->calc_gas_resistance($adc_gas_res, $gas_range));
							break;
						} else {
							IPS_Sleep(10);
						}
						
						//$this->SetBuffer("MeasurementData", $Result);
					}
				}
				$tries--;
			} while ($tries);  
			
			If (!$tries) {
				$Result = 2;
			}

		return $Result;
		}	
	}
	
	private function SoftReset()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Führt einen SoftReset aus
			$this->SendDebug("SoftReset", "Ausfuehrung", 0);
			$reg_addr = hexdec("E0");
			$soft_rst_cmd = hexdec("B6");

			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME680_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $reg_addr, "Value" => $soft_rst_cmd)));
			If (!$Result) {
				$this->SendDebug("SoftReset", "SoftReset fehlerhaft!", 0);
				return;
			}
			IPS_Sleep(5); 
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
