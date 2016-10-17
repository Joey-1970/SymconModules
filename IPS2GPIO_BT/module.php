<?
    // Klassendefinition
    class IPS2GPIO_BT extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
	    $this->RegisterPropertyString("MAC0", "");
	    $this->RegisterPropertyBoolean("LoggingMAC0", false);
	    $this->RegisterPropertyString("MAC1", "");
	    $this->RegisterPropertyBoolean("LoggingMAC1", false);
	    $this->RegisterPropertyString("MAC2", "");
	    $this->RegisterPropertyBoolean("LoggingMAC2", false);
	    $this->RegisterPropertyString("MAC3", "");
	    $this->RegisterPropertyBoolean("LoggingMAC3", false);
	    $this->RegisterPropertyString("MAC4", "");
	    $this->RegisterPropertyBoolean("LoggingMAC4", false);
	    $this->RegisterTimer("Messzyklus", 0, 'I2GBT_Measurement($_IPS["TARGET"]);');
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
	         $this->RegisterVariableBoolean("MAC0", "MAC 1", "~Switch", 10);
		 $this->EnableAction("MAC0");
		 $this->RegisterVariableString("MAC0Name", "MAC 1 Name", "", 20);
                 $this->EnableAction("MAC0Name");
                 $this->RegisterVariableBoolean("MAC1", "MAC 2", "~Switch", 30);
		 $this->EnableAction("MAC1");
		 $this->RegisterVariableString("MAC1Name", "MAC 2 Name", "", 40);
                 $this->EnableAction("MAC1Name");
		 $this->RegisterVariableBoolean("MAC2", "MAC 3", "~Switch", 50);
		 $this->EnableAction("MAC2");
		 $this->RegisterVariableString("MAC2Name", "MAC 3 Name", "", 60);
                 $this->EnableAction("MAC2Name");
		 $this->RegisterVariableBoolean("MAC3", "MAC 4", "~Switch", 70);
		 $this->EnableAction("MAC3");
		 $this->RegisterVariableString("MAC3Name", "MAC 4 Name", "", 80);
                 $this->EnableAction("MAC3Name");
		 $this->RegisterVariableBoolean("MAC4", "MAC 5", "~Switch", 90);
		 $this->EnableAction("MAC4");
		 $this->RegisterVariableString("MAC4Name", "MAC 5 Name", "", 1000);
                 $this->EnableAction("MAC4Name");
                
		//ReceiveData-Filter setzen
                $Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);
		If (IPS_GetKernelRunlevel() == 10103) {
			If ($this->ReadPropertyInteger("Pin") >= 0) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
			}
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
			   case "get_usedpin":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
			   	break;
			   case "status":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
			  case "result":
				If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   		SetValueInteger($this->GetIDForIdent("Status"), $data->Value);
				}
			break;
			   case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
	 	}
	return;
 	}
	// Beginn der Funktionen
	

	// Führt eine Messung aus
	public function Measurement()
	{
		for ($i = 0; $i <= 4; $i++) {
			If ($this->ReadPropertyString("MAC".$i) <> "") {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_BT_connect", "MAC" => $this->ReadPropertyString("MAC".$i), "MAC_Number" => $i )));
			}
		}
	}
	
}
?>
