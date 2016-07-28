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
		$this->ReadData();
		
		$CalibrateData = unserialize(GetValueString($this->GetIDForIdent("CalibrateData")));
		$Dig_T1 = $CalibrateData[137] << 8 | $CalibrateData[136];
		$Dig_T2 = $CalibrateData[139] << 8 | $CalibrateData[138];
		$Dig_T3 = $CalibrateData[141] << 8 | $CalibrateData[140];
		
		$Dig_P1 = $CalibrateData[143] << 8 | $CalibrateData[142];
		$Dig_P2 = $CalibrateData[145] << 8 | $CalibrateData[144];
		$Dig_P3 = $CalibrateData[147] << 8 | $CalibrateData[146];
		$Dig_P4 = $CalibrateData[149] << 8 | $CalibrateData[148];
		$Dig_P5 = $CalibrateData[151] << 8 | $CalibrateData[150];
		$Dig_P6 = $CalibrateData[153] << 8 | $CalibrateData[152];
		$Dig_P7 = $CalibrateData[155] << 8 | $CalibrateData[154];
		$Dig_P8 = $CalibrateData[157] << 8 | $CalibrateData[156];
		$Dig_P9 = $CalibrateData[159] << 8 | $CalibrateData[158];
		
		$Dig_H1 = $CalibrateData[160];
		$Dig_H2 = $CalibrateData[162] << 8 | $CalibrateData[161];
		$Dig_H3 = $CalibrateData[163];
		$Dig_H4 = $CalibrateData[164] << 4 | (hexdec("0F") & $CalibrateData[165]);
		$Dig_H5 = $CalibrateData[166] << 4 | (($CalibrateData[165] >> 4) & hexdec("0F"));
		$Dig_H6 = $CalibrateData[167];
		
		$MeasurementData = unserialize(GetValueString($this->GetIDForIdent("MeasurementData")));
		
		
		
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
    			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => $i, "Value" => $i)));
		}
	return;
	}
	
	private function CalibrateTemp()
	{
		$CalibrateData = unserialize(GetValueString($this->GetIDForIdent("CalibrateData")));
		$Dig_T1 = $CalibrateData[137] << 8 | $CalibrateData[136];
		$Dig_T2 = $CalibrateData[139] << 8 | $CalibrateData[138];
		$Dig_T3 = $CalibrateData[141] << 8 | $CalibrateData[140];
		
	return;
	}

}
?>
