<?
    // Klassendefinition
    class IPS2GPIO_HCSR04 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
            // Pin Echo
            $this->RegisterPropertyInteger("Pin_I", -1);
            // Pin Trigger
            $this->RegisterPropertyInteger("Pin_O", -1);
            $this->RegisterPropertyInteger("Messzyklus", 5);
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
		    // Profil anlegen
		    $this->RegisterProfileFloat("length.cm", "Distance", "", " cm", 0, 1000, 0.1, 1);
		   
		    //Status-Variablen anlegen
		    $this->RegisterVariableFloat("Distance", "Distance", "length.cm", 10);
	            $this->DisableAction("Distance");
	            IPS_SetHidden($this->GetIDForIdent("Distance"), false);
	            $this->RegisterVariableInteger("Timestamp", "Timestamp", "", 20);
	            $this->DisableAction("Timestamp");
	            IPS_SetHidden($this->GetIDForIdent("Timestamp"), true);
	            
	            
	            If (($this->ReadPropertyInteger("Pin_I") >= 0) AND ($this->ReadPropertyInteger("Pin_O")) >= 0) {
	            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
	            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $this->ReadPropertyInteger("Pin_O"), "Value" => 0)));
	            }
	            $this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
        }
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			   case "notify":
			   	If (($data->Pin == $this->ReadPropertyInteger("Pin_I")) AND ($data->Value == false)) {
			   		$TimeDiff = $data->Timestamp - GetValueInteger($this->GetIDForIdent("Timestamp"));
			   		$TimeDiff = abs($TimeDiff/1000000);
   					$Distance = round(($TimeDiff * 34300 / 2), 1);
   					SetValueFloat($this->GetIDForIdent("Distance"), min($Distance, 999.99));
			   	}
			   	elseif (($data->Pin == $this->ReadPropertyInteger("Pin_I")) AND ($data->Value == true)) {
			   		SetValueInteger($this->GetIDForIdent("Timestamp"), $data->Timestamp);	
			   	}
			   	break;
			   case "get_notifypin":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_notifypin", "Pin" => $this->ReadPropertyInteger("Pin_I"), "GlitchFilter" => 0)));
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
	
	// Führt eine Messung aus
	public function Measurement()
	{
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_trigger", "Pin" => $this->ReadPropertyInteger("Pin_O"), "Time" => 10)));
	return;
	}
	
	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 2);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 2)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	        IPS_SetVariableProfileDigits($Name, $Digits);
	}
	
}
?>
