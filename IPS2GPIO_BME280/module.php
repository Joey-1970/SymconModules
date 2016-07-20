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
	    $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_hardwarerev")));
	    $this->RegisterVariableInteger("Pin_SDA", "Pin_SDA");
	    $this->DisableAction("Pin_SDA");
	    IPS_SetHidden($this->GetIDForIdent("Pin_SDA"), true);
	    $this->RegisterVariableInteger("Pin_SCL", "Pin_SCL");
	    $this->DisableAction("Pin_SCL");
	    IPS_SetHidden($this->GetIDForIdent("Pin_SCL"), true);
	    

            If (($this->ReadPropertyInteger("Pin_D") >= 0) AND ($this->ReadPropertyInteger("Pin_D") >= 0)) {
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
            }
        }
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			   case "notify":
			   // leer
			   	break;
			   case "get_notifypin":
			   	//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_notifypin", "Pin" => $this->ReadPropertyInteger("Pin"), "GlitchFilter" => $this->ReadPropertyInteger("GlitchFilter"))));
			   	break;
			   case "get_usedpin":
			   	// Ist bei allen bisherigen Raspberry Pi GPIO 2&3, außer beim Modell 1 Revision 1, muss noch angepasst werden!
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => 2, "Modus" => "R")));
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => 3, "Modus" => "R")));

			   	break;
			   case "status":
			   	// Ist bei allen bisherigen Raspberry Pi GPIO 2&3, außer beim Modell 1 Revision 1, muss noch angepasst werden!
			   	If (($data->Pin == 2) OR ($data->Pin == 3)){
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
			   case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
	 	}
	return;
 	}
	// Beginn der Funktionen
	
	
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
