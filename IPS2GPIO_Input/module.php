<?
    // Klassendefinition
    class IPS2GPIO_Input extends IPSModule 
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
            $this->RegisterPropertyInteger("Pin", -1);
            $this->RegisterPropertyInteger("GlitchFilter", 10);
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
	    $this->RegisterVariableBoolean("Status", "Status", "~Switch", 1);
            $this->DisableAction("Status");
            $this->RegisterVariableBoolean("Toggle", "Toggle", "~Switch", 1);
            $this->DisableAction("Toggle");
            $this->RegisterVariableBoolean("Trigger", "Trigger", "~Switch", 1);
            $this->DisableAction("Trigger");
            If ($this->ReadPropertyInteger("Pin") >= 0) {
            	//$this->Set_Mode();
            	//$this->Set_GlitchFilter();
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
            }
        }
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	    	//IPS_LogMessage("ReceiveData_Input", utf8_decode($data->Buffer));
	 	switch ($data->Function) {
			   case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   		// Trigger kurzzeitig setzen
			   		If ($data->Value == true) {
			   			SetValueBoolean($this->GetIDForIdent("Trigger"), true);
			   			SetValueBoolean($this->GetIDForIdent("Trigger"), false);
			   		}
			   		// Toggle-Variable
			   		If ((GetValueBoolean($this->GetIDForIdent("Status")) == false) and ($data->Value == true)) {
			   			SetValueBoolean($this->GetIDForIdent("Toggle"), !GetValueBoolean($this->GetIDForIdent("Toggle")));
			   		}
			   		// Status setzen
			   		SetValueBoolean($this->GetIDForIdent("Status"), $data->Value);
			   	}
			   	break;
			   case "get_notifypin":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_notifypin", "Pin" => $this->ReadPropertyInteger("Pin"), "GlitchFilter" => $this->ReadPropertyInteger("GlitchFilter"))));
			   	break;
			   case "get_usedpin":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin"), "Modus" => "R")));
			   	break;
			   case "status":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
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
	
	// Setzt den gewaehlten Pin in den Output-Modus
	private function Set_Mode()
	{
   		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_mode", "Pin" => $this->ReadPropertyInteger("Pin"), "Modus" => "R")));
   	return;
	}

	// Setzt den gewaehlten Pin in den Output-Modus
	private function Set_GlitchFilter()
	{
   		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_glitchfilter", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $this->ReadPropertyInteger("GlitchFilter"))));
   	return;
	}	

}
?>
