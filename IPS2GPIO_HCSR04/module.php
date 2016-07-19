<?
    // Klassendefinition
    class IPS2GPIO_HCSR04 extends IPSModule 
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
            $this->RegisterPropertyInteger("Pin_I", -1);
            $this->RegisterPropertyInteger("Pin_O", -1);
            $this->RegisterPropertyInteger("Messzyklus", 1000);
            $this->RegisterTimer("Messzyklus", 0, 'I2GSR4_Measurement($_IPS["TARGET"]);');
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
	    $this->RegisterVariableFloat("Distanz", "Distanz", "", 0);
            $this->DisableAction("Distanz");
            $this->RegisterVariableInteger("TimestampTrigger", "TimestampTrigger", "", 0);
            $this->DisableAction("TimestampTrigger");
            IPS_SetHidden(GetIDForIdent("TimestampTrigger"), true);
            $this->SetTimerInterval("Messzyklus", $this->ReadPropertyInteger("Messzyklus"));
            
            If (($this->ReadPropertyInteger("Pin_I") >= 0) AND ($this->ReadPropertyInteger("Pin_O")) >= 0) {
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Value" => 0)));
            }
        }
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			   case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin_I")) {
			   		$TimeDiff = $data->Timestamp - GetValueInteger(GetIDForIdent("TimestampTrigger"));
   					SetValueFloat(GetIDForIdent("Distanz"), $TimeDiff * 34300) / 2);
			   	}
			   	elseif ($data->Pin == $this->ReadPropertyInteger("Pin_O")) {
			   		SetValueInteger(GetIDForIdent("TimestampTrigger"), $data->Timestamp);	
			   	}
			   	break;
			   case "get_notifypin":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_notifypin", "Pin" => $this->ReadPropertyInteger("Pin_I"), "GlitchFilter" => 0)));
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_notifypin", "Pin" => $this->ReadPropertyInteger("Pin_O"), "GlitchFilter" => 0)));
			   	break;
			   case "get_usedpin":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_I"), "Modus" => "R")));
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_O"), "Modus" => "W")));

			   	break;
			   case "status":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin_I")) {
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
	
	// Toggelt den Status
	public function Measurement()
	{
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_trigger", "Pin" => $this->ReadPropertyInteger("Pin_O"), "Time" => 10)));
	return;
	}
	
}
?>
