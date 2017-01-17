<?
    // Klassendefinition
    class IPS2GPIO_1Wire extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Messzyklus", 5);
		$this->RegisterTimer("Messzyklus", 0, 'I2G1W_Measurement($_IPS["TARGET"]);');
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
	        $this->RegisterVariableString("SensorArray", "SensorArray", "", 5);
		$this->DisableAction("SensorArray");
		IPS_SetHidden($this->GetIDForIdent("SensorArray"), true);
            
                //ReceiveData-Filter setzen
		$Filter = '((.*"Function":"get_usedpin".*|.*"Pin":"4".*)|.*"InstanceID":'.$this->InstanceID.'.*)';
		//$Filter = '(.*"Function":"get_usedpin".*|.*"Pin":"4".*)';
		$this->SetReceiveDataFilter($Filter);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => 4, "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->Setup();
				$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
				// Erste Messung durchführen
				$this->Measurement();
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			  
			case "get_usedpin":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => 4, "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				}
				break;
			case "status":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
			case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
			case "set_1wire_devices":
			   	$ResultArray = unserialize(utf8_decode($data->Result));
				SetValueString($this->GetIDForIdent("SensorArray"), utf8_decode($data->Result));
				for ($i = 0; $i < Count($ResultArray); $i++) {
					//IPS_LogMessage("IPS2GPIO 1-Wire: ","Sensor ".$ResultArray[$i]);
					$Ident = "Sensor_".str_replace("-", "", $ResultArray[$i]);
					$this->RegisterVariableFloat($Ident, "Sensor_".$ResultArray[$i], "~Temperature", ($i + 1) *10);
					$this->DisableAction($Ident);
				}
			   	break;
			case "set_1wire_data":
			   	$ResultArray = unserialize(utf8_decode($data->Result));
				
				for ($i = 0; $i < Count($ResultArray); $i++) {
					IPS_LogMessage("IPS2GPIO 1-Wire: ","Sensorantwort: ".$ResultArray[$i]);
				}
			   	break;
	 	}
 	}
	// Beginn der Funktionen
	private function Setup()
	{
		// Ermittlung der angeschlossenen Sensoren
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_1wire_devices", "InstanceID" => $this->InstanceID )));
	}
	    
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$CommandArray = Array();
			// Zusammenstellung der Sensoren
			$SensorArray = unserialize(GetValueString($this->GetIDForIdent("SensorArray")));
			for ($i = 0; $i < Count($SensorArray); $i++) {
				$CommandArray[$i] = "cat /sys/bus/w1/devices/".$SensorArray[$i]."/w1_slave";
				IPS_LogMessage("IPS2GPIO 1-Wire: ","Sensoranfrage: ".$CommandArray[$i]);
			}
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_1W_data", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray) )));
		}
	}
}
?>
