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
	    	$this->RegisterVariableInteger("HardwareRev", "HardwareRev");
          	$this->DisableAction("HardwareRev");
		IPS_SetHidden($this->GetIDForIdent("HardwareRev"), true);
		
          	$this->RegisterVariableInteger("Handle", "Handle");
		$this->DisableAction("Handle");
		IPS_SetHidden($this->GetIDForIdent("Handle"), true);
             	
             	$this->RegisterVariableString("CalibrateData", "CalibrateData");
		$this->DisableAction("CalibrateData");
		IPS_SetHidden($this->GetIDForIdent("CalibrateData"), true);
             	
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
            	$tihs->Get_CalibrateData();
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
			  case "add_calibrate_data":
			  	If ($data->Handle == GetValueInteger($this->GetIDForIdent("Handle"))) {
			  		$CalibrateData = unserialize(GetValueString($this->GetIDForIdent("CalibrateData")));
			  		$CalibrateData[$data->Register] = $data->Value;
			  		SetValueString($this->GetIDForIdent("CalibrateData"), serialize($CalibrateData));
			  	}
			  	break;
	 	}
	return;
 	}
	// Beginn der Funktionen

	// Führt eine Messung aus
	public function Measurement()
	{
		// Temperatur
		//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "250", "Value" => "4")));

		//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "250")));
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
	
	private function Get_CalibrateData()
	{
		for ($i = hexdec("88"); $i < (hexdec("88") + 24); $i++) {
    			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_exchange_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => $i, "Value" => $i)));
		}

/*		
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "242", "Value" => "1")));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "244", "Value" => "1")));

		
		// T1 0x88
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_exchange_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "136", "Value" => "136")));
		// T2 0x8A
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_exchange_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "138", "Value" => "138")));
		// T3 0x8C
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_exchange_byte", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "140", "Value" => "140")));

		// P1 0xBE
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "142")));
		// P2 0x90
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "144")));
		// P3 0x92
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "146")));
		// P4 0x94
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "148")));
		// P5 0x96
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "150")));
		// P6 0x98
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "152")));
		// P7 0x9A
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "154")));
		// P8 0x9C
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "156")));
		// P9 0x9E
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_word", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Register" => "158")));
*/
	return;	
	}
	
	// pi.i2c_open(0, YL_40, 0) => I2CO 54 bus device 4 uint32_t flags 
	// BME280_I2CADDR = 0x77
	// Rückgabe des Handle
	
	// I2CWB 62 handle register 4 uint32_t byte 
	// BME280_REGISTER_CONTROL_HUM = 0xF2 
	// 67 BME280_REGISTER_CONTROL = 0xF4 
	// 68 BME280_REGISTER_CONFIG = 0xF5 
	// 69 BME280_REGISTER_PRESSURE_DATA = 0xF7 
	// 70 BME280_REGISTER_TEMP_DATA = 0xFA 
	// 71 BME280_REGISTER_HUMIDITY_DATA = 0xFD 
	
	// I2CRB 61 handle register 0 - 


}
?>
