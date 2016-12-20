<?
    // Klassendefinition
    class IPS2GPIO_Input extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin", -1);
            	$this->RegisterPropertyBoolean("ActionValue", true);
            	$this->RegisterPropertyInteger("GlitchFilter", 10);
	    	$this->RegisterPropertyString("PUL", "o");
            	$this->RegisterPropertyInteger("TriggerScript", 0);
            	$this->RegisterPropertyInteger("ToggleScript", 0);
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
	        $this->RegisterVariableBoolean("Status", "Status", "~Switch", 10);
                $this->DisableAction("Status");
                $this->RegisterVariableBoolean("Toggle", "Toggle", "~Switch", 20);
                $this->DisableAction("Toggle");
                $this->RegisterVariableBoolean("Trigger", "Trigger", "~Switch", 30);
                $this->DisableAction("Trigger");
            
                //ReceiveData-Filter setzen
		$Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => $this->ReadPropertyInteger("GlitchFilter"), "Resistance" => $this->ReadPropertyString("PUL"))));
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
			   case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   		// Trigger kurzzeitig setzen
			   		If ($data->Value == $this->ReadPropertyBoolean("ActionValue") ) {
			   			SetValueBoolean($this->GetIDForIdent("Trigger"), true);
			   			If ($this->ReadPropertyInteger("TriggerScript") > 0) {
			   				IPS_RunScript($this->ReadPropertyInteger("TriggerScript"));
			   			}
			   			SetValueBoolean($this->GetIDForIdent("Trigger"), false);
			   		}
			   		// Toggle-Variable
			   		If ((GetValueBoolean($this->GetIDForIdent("Status")) == false) and ($data->Value == true)) {
			   			SetValueBoolean($this->GetIDForIdent("Toggle"), !GetValueBoolean($this->GetIDForIdent("Toggle")));
			   			If ($this->ReadPropertyInteger("ToggleScript") > 0) {
			   				IPS_RunScript($this->ReadPropertyInteger("ToggleScript"));
			   			}
			   		}
			   		// Status setzen
			   		SetValueBoolean($this->GetIDForIdent("Status"), $data->Value);
			   	}
			   	break;
			   case "get_usedpin":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => $this->ReadPropertyInteger("GlitchFilter"), "Resistance" => $this->ReadPropertyString("PUL"))));
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
 	}
	// Beginn der Funktionen
	

}
?>
