<?
    // Klassendefinition
    class IPS2GPIO_RPi extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
            $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
            $this->RegisterPropertyInteger("Messzyklus1", 60);
            $this->RegisterTimer("Messzyklus1", 0, 'I2GRPi_Measurement($_IPS["TARGET"]);');
            $this->RegisterPropertyInteger("Messzyklus2", 60);
            $this->RegisterTimer("Messzyklus2", 0, 'I2GRPi_Measurement($_IPS["TARGET"]);');
        }
 
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                 // Diese Zeile nicht löschen
                 parent::ApplyChanges();
                 //Connect to available splitter or create a new one
	         $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	   
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("RPiData0", "Temperatur CPU", "~Temperature", 10);
		$this->DisableAction("RPiData0");
		$this->RegisterVariableInteger("RPiData1", "Temperatur GPU", "~Temperature", 20);
		$this->DisableAction("RPiData1");
		 
                
		// Logging setzen
		/*
		for ($i = 0; $i <= 4; $i++) {
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("MAC".$i), $this->ReadPropertyBoolean("LoggingMAC".$i)); 
		} 
		IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
		*/
		
		//ReceiveData-Filter setzen
                $Filter = '(.*"Function":"set_BT_connect".*|.*"InstanceID":'.$this->InstanceID.'.*))';
		$this->SetReceiveDataFilter($Filter);
		If (IPS_GetKernelRunlevel() == 10103) {
			$this->SetTimerInterval("Messzyklus1", ($this->ReadPropertyInteger("Messzyklus1") * 1000));
			$this->SetTimerInterval("Messzyklus2", ($this->ReadPropertyInteger("Messzyklus2") * 1000));
			$this->Measurement();
			$this->SetStatus(102);
		}
        }
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	       
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			   case "set_BT_connect":
			   	
	 	}
	return;
 	}
	// Beginn der Funktionen
	// Führt eine Messung aus
	public function Measurement()
	{
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_BT_connect", "InstanceID" => $this->InstanceID,  "MAC" => $this->ReadPropertyString("MAC".$i), "MAC_Number" => $i )));
		
	}

	
}
?>
