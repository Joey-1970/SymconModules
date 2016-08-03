<?
    // Klassendefinition
    class IPS2GPIO_BH1750 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", "");
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GBH_Measurement($_IPS["TARGET"]);');
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

             	$this->RegisterVariableFloat("Brightness", "Brightness", "", 10);
		$this->DisableAction("Brightness");
		IPS_SetHidden($this->GetIDForIdent("Brightness"), false);

             	If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
             		// Handle löschen
             		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "close_handle_i2c", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")))));
             		SetValueInteger($this->GetIDForIdent("Handle"), -1);
             	}
            	// den Handle für dieses Gerät ermitteln
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"))));

            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
            	$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
            	If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
	            	// Parameterdaten zum Baustein senden
	            	//$this->Setup();
	            	// Kalibrirungsdaten einlesen
	            	//$this->ReadCalibrateData();
	            	// Erste Messdaten einlesen
	            	//$this->Measurement();
	            	$this->SetStatus(102);
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
			   case "set_i2c_handle":
			   	If ($data->Address == $this->ReadPropertyInteger("DeviceAddress")) {
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
			  case "set_i2c_byte_block":
			   	If ($data->Handle == GetValueInteger($this->GetIDForIdent("Handle"))) {
			   		SetValueString($this->GetIDForIdent("MeasurementData"), $data->ByteArray);
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
		
	return;
	}	
	
	private function Setup()
	{
	
	return;
	}
	
	private function ReadCalibrateData()
	{
		
	return;	
	}
	
	private function ReadData()
	{
			
	return;
	}

}
?>
