<?
    // Klassendefinition
    class IPS2GPIO_BME280 extends IPSModule 
    {
	public function __construct($InstanceID) {
            	// Diese Zeile nicht löschen
            	parent::__construct($InstanceID);
        }

	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyString("DeviceAddress", "");
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GBME_Measurement($_IPS["TARGET"]);');
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
            	//Connect to available splitter or create a new one
	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	    	//Status-Variablen anlegen
	    	$this->RegisterVariableInteger("HardwareRev", "HardwareRev", "", 100);
          	$this->DisableAction("HardwareRev");
		IPS_SetHidden($this->GetIDForIdent("HardwareRev"), true);
		
          	$this->RegisterVariableInteger("Handle", "Handle", "", 110);
		$this->DisableAction("Handle");
		IPS_SetHidden($this->GetIDForIdent("Handle"), true);
             	
             	$this->RegisterVariableString("CalibrateData", "CalibrateData", "", 120);
		$this->DisableAction("CalibrateData");
		IPS_SetHidden($this->GetIDForIdent("CalibrateData"), true);
             	
             	$this->RegisterVariableString("MeasurementData", "MeasurementData", "", 130);
		$this->DisableAction("MeasurementData");
		IPS_SetHidden($this->GetIDForIdent("MeasurementData"), true);
		
		// Test!
		$this->RegisterVariableString("tmpMeasurementData", "tmpMeasurementData", "", 130);
		$this->DisableAction("tmpMeasurementData");
		IPS_SetHidden($this->GetIDForIdent("tmpMeasurementData"), true);

             	$this->RegisterVariableFloat("Temperature", "Temperature", "~Temperature", 10);
		$this->DisableAction("Temperature");
		IPS_SetHidden($this->GetIDForIdent("Temperature"), false);
		
		$this->RegisterVariableFloat("Pressure", "Pressure", "~AirPressure", 20);
		$this->DisableAction("Pressure");
		IPS_SetHidden($this->GetIDForIdent("Pressure"), false);
		
		$this->RegisterVariableFloat("Humidity", "Humidity", "~Humidity", 30);
		$this->DisableAction("Humidity");
		IPS_SetHidden($this->GetIDForIdent("Humidity"), false);
             	
             	If (GetValueInteger($this->GetIDForIdent("Handle")) > 0) {
             		// Handle löschen
             		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "close_handle_i2c", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")))));
             		SetValueInteger($this->GetIDForIdent("Handle"), 0);
             	}
            	// den Handle für dieses Gerät ermitteln
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_i2c", "DeviceAddress" => $this->ReadPropertyString("DeviceAddress"))));

            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
            	$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
            	$this->Setup();
            	$this->ReadCalibrateData();
        }
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			   case "notify":
			   // leer
			   	break;
			   case "set_i2c_handle":
			   	If ($data->Address == $this->ReadPropertyString("DeviceAddress")) {
			   		SetValueInteger($this->GetIDForIdent("Handle"), $data->Handle);
			   		SetValueInteger($this->GetIDForIdent("HardwareRev"), $data->HardwareRev);
			   	}
			   	break;
			   case "get_used_i2c":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "Value" => true)));
			   	break;
			   case "status":
			   	If (GetValueInteger($this->GetIDForIdent("HardwareRev")) <= 3) {
				   	If (($data->Pin == 0) OR ($data->Pin == 1)) {
				   		$this->SetStatus($data->Status);		
				   	}
			   	}
				else if (GetValueInteger($this->GetIDForIdent("HardwareRev")) > 3) {
					If (($data->Pin == 2) OR ($data->Pin == 3)) {
				   		$this->SetStatus($data->Status);
				   	}
				}
			   	break;
			  case "set_i2c_data":
			  	If ($data->Handle == GetValueInteger($this->GetIDForIdent("Handle"))) {
			  		// Daten zur Kalibrierung
			  		If (($data->Register >= hexdec("88")) AND ($data->Register < hexdec("E8"))) {
			  			$CalibrateData = unserialize(GetValueString($this->GetIDForIdent("CalibrateData")));
			  			$CalibrateData[$data->Register] = $data->Value;
			  			SetValueString($this->GetIDForIdent("CalibrateData"), serialize($CalibrateData));
			  		}
			  		// Daten der Messung
			  		If (($data->Register >= hexdec("F7")) AND ($data->Register < hexdec("FF"))) {
			  			$MeasurementData = unserialize(GetValueString($this->GetIDForIdent("MeasurementData")));
			  			$MeasurementData[$data->Register] = $data->Value;
			  			SetValueString($this->GetIDForIdent("MeasurementData"), serialize($MeasurementData));
			  		}
			  	}
			  	break;
	 	}
	return;
 	}
	// Beginn der Funktionen

	// Führt eine Messung aus
	public function Measurement()
	{
		// Messwerte aktualisieren
		$this->ReadData();
		// Kalibrierungsdatan aufbereiten
		$CalibrateData = unserialize(GetValueString($this->GetIDForIdent("CalibrateData")));
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
		
		$Dig_H[0] = $CalibrateData[160];
		$Dig_H[1] = (($CalibrateData[162] << 8) | $CalibrateData[161]);
		$Dig_H[2] = $CalibrateData[163];
		$Dig_H[3] = (($CalibrateData[164] << 4) | (hexdec("0F") & $CalibrateData[165]));
		$Dig_H[4] = (($CalibrateData[166] << 4) | (($CalibrateData[165] >> 4) & hexdec("0F")));
		$Dig_H[5] = $CalibrateData[167];
		// Messwerte aufbereiten
		$MeasurementData = unserialize(GetValueString($this->GetIDForIdent("MeasurementData")));
		$Pres_raw = (($MeasurementData[247] << 12) | ($MeasurementData[248] << 4) | ($MeasurementData[249] << 4));
		$Temp_raw = (($MeasurementData[250] << 12) | ($MeasurementData[251] << 4) | ($MeasurementData[252] << 4));
		$Hum_raw =  (($MeasurementData[253] << 8) | $MeasurementData[254]);
		
		$FineCalibrate = 0;
		
		// Temperatur
		$V1 = ($Temp_raw / 16384 - $Dig_T[0] / 1024) * $Dig_T[1];
		$V2 = ($Temp_raw / 131072 - $Dig_T[0] / 8192) * ($Temp_raw / 131072 - $Dig_T[0] / 8192) * $Dig_T[2];
		$FineCalibrate = $V1 + $V2;
		SetValueFloat($this->GetIDForIdent("Temperature"), $FineCalibrate / 5120);
		
		// Luftdruck
		$V1 = ($FineCalibrate / 2) - 64000;
		$V2 = ((($V1 / 4) * ($V1 / 4)) / 2048) * $Dig_P[5];
		$V2 = $V2 + (($V1 * $Dig_P[4]) * 2);
		$V2 = ($V2 / 4) + ($Dig_P[3] * 65536);
		$V1 = ((($Dig_P[2] * ((($V1 / 4) * ($V1 / 4)) / 8192)) / 8) + (($Dig_P[1] * $V1) / 2)) / 262144;
		$V1 = ((32768 + $V1) * $Dig_P[0]]) / 32768;
		
		If ($V1 == 0) {
			SetValueFloat($this->GetIDForIdent("Pressure"), "0");
		}
		$Pressure = ((1048576 - $Pres_raw) - ($V2 / 4096)) * 3125;
		
		If ($Pressure < 0x80000000) {
			$Pressure = ($Pressure * 2) * $V1;
		}
		else {
			$Pressure = ($Pressure / $V1) * 2;
		}
		$V1 = ($Dig_P[8] * ((($Pressure / 8) * ($Pressure / 8)) / 8192)) / 4096;
		$V2 = (($Pressure / 4) * $Dig_P[7]) / 8192;
		$Pressure = $Pressure + (($V1 + $V2 + $Dig_P[6]) / 16);
		
		SetValueFloat($this->GetIDForIdent("Pressure"), $Pressure);
		
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
		
		SetValueFloat($this->GetIDForIdent("Humidity"), $Hum);
	return;
	}	
	
	private function Setup()
	{
		$osrs_t = 1;
		$osrs_p = 1;
		$osrs_h = 1;
		$mode = 3;
		$t_sb = 5;
		$filter = 0;
		$spi3w_en = 0;
		
		$ctrl_meas_reg = (($osrs_t << 5)|($osrs_p << 2)|$mode);
		$config_reg = (($t_sb << 5)|($filter << 2)|$spi3w_en);
		$ctrl_hum_reg = $osrs_h;
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => hexdec("F2"), "Value" => $ctrl_hum_reg)));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => hexdec("F4"), "Value" => $ctrl_meas_reg)));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => hexdec("F5"), "Value" => $config_reg)));
	return;
	}
	
	private function ReadCalibrateData()
	{
		SetValueString($this->GetIDForIdent("CalibrateData"), "");
		
		for ($i = hexdec("88"); $i < (hexdec("88") + 24); $i++) {
    			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => $i, "Value" => $i)));
		}

		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => hexdec("A1"), "Value" => $i)));

		for ($i = hexdec("E1"); $i < (hexdec("E1") + 7); $i++) {
    			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => $i, "Value" => $i)));
		}
	return;	
	}
	
	private function ReadData()
	{
		SetValueString($this->GetIDForIdent("MeasurementData"), "");
		for ($i = hexdec("F7"); $i < (hexdec("F7") + 8); $i++) {
    			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => $i)));
		}
		// Test!
		SetValueString($this->GetIDForIdent("tmpMeasurementData"), "");
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_block_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => hexdec("F7"), "Count" => 8)));
	return;
	}

}
?>
