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
	         $this->RegisterVariableBoolean("MAC0Connect", "MAC 1", "~Switch", 10);
		 $this->EnableAction("MAC0Connect");
		 $this->RegisterVariableString("MAC0Name", "MAC 1 Name", "", 20);
                 $this->EnableAction("MAC0Name");
                 $this->RegisterVariableBoolean("MAC1Connect", "MAC 2", "~Switch", 30);
		 $this->EnableAction("MAC1Connect");
		 $this->RegisterVariableString("MAC1Name", "MAC 2 Name", "", 40);
                 $this->EnableAction("MAC1Name");
		 $this->RegisterVariableBoolean("MAC2Connect", "MAC 3", "~Switch", 50);
		 $this->EnableAction("MAC2Connect");
		 $this->RegisterVariableString("MAC2Name", "MAC 3 Name", "", 60);
                 $this->EnableAction("MAC2Name");
		 $this->RegisterVariableBoolean("MAC3Connect", "MAC 4", "~Switch", 70);
		 $this->EnableAction("MAC3Connect");
		 $this->RegisterVariableString("MAC3Name", "MAC 4 Name", "", 80);
                 $this->EnableAction("MAC3Name");
		 $this->RegisterVariableBoolean("MAC4Connect", "MAC 5", "~Switch", 90);
		 $this->EnableAction("MAC4Connect");
		 $this->RegisterVariableString("MAC4Name", "MAC 5 Name", "", 1000);
                 $this->EnableAction("MAC4Name");
                
		//ReceiveData-Filter setzen
                $Filter = '(.*"Function":"set_BT_connect".*)';
		$this->SetReceiveDataFilter($Filter);
		If (IPS_GetKernelRunlevel() == 10103) {
			$this->Measurement();
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
			   	SetValueString($this->GetIDForIdent("MAC".$data->MAC_Number."Name"), utf8_decode($data->Result));
				If (strlen($data->Result) > 0) {
					SetValueBoolean($this->GetIDForIdent("MAC".$data->MAC_Number."Connect"), true);
				}
				else {
					SetValueBoolean($this->GetIDForIdent("MAC".$data->MAC_Number."Connect"), false);
				}
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
