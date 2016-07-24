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
             	If (GetValueInteger($this->GetIDForIdent("Handle")) == 0) {
             		// den Handle für dieses Gerät ermitteln
             		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_i2c", "DeviceAddress" => $this->ReadPropertyString("DeviceAddress"))));
             	}
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
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
	 	}
	return;
 	}
	// Beginn der Funktionen

	// Führt eine Messung aus
	public function Measurement()
	{
		// Temperatur
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_read_byte", "Handle" => $this->ReadPropertyInteger("Handle"), "Register" => "0xFA")));
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
