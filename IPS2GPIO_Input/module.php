<?
    // Klassendefinition
    class IPS2GPIO_Input extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
            $this->RegisterPropertyInteger("Pin", -1);
            $this->RegisterPropertyBoolean("ActionValue", true);
            $this->RegisterPropertyInteger("GlitchFilter", 10);
            $this->RegisterPropertyInteger("TriggerScript", 0);
            $this->RegisterPropertyInteger("ToggleScript", 0);
 	    $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
        }

        // Überschreibt die interne IPS_Destroy($id) Funktion
        public function Destroy() {
            // Diese Zeile nicht löschen.
            parent::Destroy();
            $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "gpio_destroy", "Pin" => $this->ReadPropertyInteger("Pin"))));
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
		$Filter = '.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*|.*"Function":"get_notifypin".*';
		$this->SendDebug("IPS2GPIO", $Filter, 0);
		$this->SetReceiveDataFilter($Filter);

                If ($this->ReadPropertyInteger("Pin") >= 0) {
            		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
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
			   case "get_notifypin":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_notifypin", "Pin" => $this->ReadPropertyInteger("Pin"), "GlitchFilter" => $this->ReadPropertyInteger("GlitchFilter"))));
			   	break;
			   case "get_usedpin":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin"), "Modus" => 0)));
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
	

}
?>
