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
	        // da kommt einiges
            
                //ReceiveData-Filter setzen
		$Filter = '(.*"Function":"get_usedpin".*|.*"Pin":"4".*)';
		$this->SetReceiveDataFilter($Filter);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => 4, "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->Setup();
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
				for ($i = 0; $i < Count($ResultArray); $i++) {
					IPS_LogMessage("IPS2GPIO 1-Wire: ","Sensor ".$ResultArray[$i]);
					$this->RegisterVariableFloat("Sensor_".$ResultArray[$i], "Sensor_".$ResultArray[$i], "~Temperature", ($i + 1) *10);
					$this->DisableAction("Sensor_".$ResultArray[$i]);
				}
			   	break;
	 	}
 	}
	// Beginn der Funktionen
	private function Setup()
	{
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_1wire_devices", "InstanceID" => $this->InstanceID )));
	}
	    
	
}
?>
