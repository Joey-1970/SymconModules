<?
    // Klassendefinition
    class IPS2GPIO_HCSR04 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		// Pin Echo
		$this->RegisterPropertyInteger("Pin_I", -1);
		// Pin Trigger
		$this->RegisterPropertyInteger("Pin_O", -1);
		$this->RegisterPropertyInteger("PUL", 0);
		$this->RegisterPropertyInteger("Messzyklus", 5);
		$this->RegisterPropertyBoolean("Logging", false);
		$this->RegisterTimer("Messzyklus", 0, 'I2GSR4_Measurement($_IPS["TARGET"]);');
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	}

	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!"); 
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
 		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number)"); 
  		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "ungesetzt", "value" => -1);
		for ($i = 0; $i <= 27; $i++) {
			$arrayOptions[] = array("label" => $i, "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_I", "caption" => "GPIO-Nr. Echo", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Select", "name" => "Pin_O", "caption" => "GPIO-Nr. Trigger", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Setzen der internen Pull Up/Down Widerstände"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Kein", "value" => 0);
		$arrayOptions[] = array("label" => "Pull-Down", "value" => 1);
		$arrayOptions[] = array("label" => "Pull-Up", "value" => 2);		
		$arrayElements[] = array("type" => "Select", "name" => "PUL", "caption" => "Widerstand setzen", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "Logging", "caption" => "Logging aktivieren"); 
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Hinweise:"); 
		$arrayElements[] = array("type" => "Label", "label" => "- ein Distanz Wert von 999,99cm wird als Maximum-Ergebnis angezeigt");
		$arrayElements[] = array("type" => "Label", "label" => "- VCC an Pin 2 (5 Volt)");
		$arrayElements[] = array("type" => "Label", "label" => "- GND an Pin 6");
		$arrayElements[] = array("type" => "Label", "label" => "- Trigger an freien GPIO-Pin nach Wahl");
		$arrayElements[] = array("type" => "Label", "label" => "- Echo über Spannungsteiler an freien GPIO-Pin nach Wahl"); 
		
		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin_I") >= 0) AND ($this->ReadPropertyInteger("Pin_O") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
					}
		else {
			$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		}
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}    
	    
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
	            // Diese Zeile nicht löschen
	            parent::ApplyChanges();

		    // Profil anlegen
		    $this->RegisterProfileFloat("length.cm", "Distance", "", " cm", 0, 1000, 0.1, 1);
		    //Status-Variablen anlegen
		    $this->RegisterVariableFloat("Distance", "Distance", "length.cm", 10);
	            $this->DisableAction("Distance");
	            IPS_SetHidden($this->GetIDForIdent("Distance"), false);

	            // Logging setzen
		    AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Distance"), $this->ReadPropertyBoolean("Logging"));
		    IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);

	            //ReceiveData-Filter setzen
		    $Filter = '((.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin_I").'.*)|.*"Pin":'.$this->ReadPropertyInteger("Pin_O").'.*)';
		    $this->SetReceiveDataFilter($Filter);
			
		    If (IPS_GetKernelRunlevel() == 10103) {
				If (($this->ReadPropertyInteger("Pin_I") >= 0) AND ($this->ReadPropertyInteger("Pin_O") >= 0) AND ($this->ReadPropertyBoolean("Open") == true) ) {
					  $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
										    "Pin" => $this->ReadPropertyInteger("Pin_I"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 0, "Resistance" => $this->ReadPropertyInteger("PUL"))));
					  $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
										    "Pin" => $this->ReadPropertyInteger("Pin_O"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
					  $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $this->ReadPropertyInteger("Pin_O"), "Value" => 0)));

					  $this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					  // Erste Messung durchführen
					  $this->Measurement();
					  $this->SetStatus(102);
				}
				else {
					 $this->SetTimerInterval("Messzyklus", 0);
					$this->SetStatus(104);
				}
		    }
		    else {
			    $this->SetTimerInterval("Messzyklus", 0);
		    }
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			   case "notify":
			   	If (($data->Pin == $this->ReadPropertyInteger("Pin_I")) AND ($data->Value == false)) {
			   		$TimeDiff = $data->Timestamp - intval($this->GetBuffer("Timestamp"));
			   		$TimeDiff = abs($TimeDiff/1000000);
   					$Distance = round(($TimeDiff * 34300 / 2), 1);
   					SetValueFloat($this->GetIDForIdent("Distance"), min($Distance, 999.99));
			   	}
			   	elseif (($data->Pin == $this->ReadPropertyInteger("Pin_I")) AND ($data->Value == true)) {
			   		$this->SetBuffer("Timestamp", $data->Timestamp);	
			   	}
			   	break;
			   case "get_usedpin":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
										  "Pin" => $this->ReadPropertyInteger("Pin_I"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 0, "Resistance" => $this->ReadPropertyInteger("PUL"))));
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_O"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
					$this->ApplyChanges();
				}
				break;
			   case "status":
			   	If (($data->Pin == $this->ReadPropertyInteger("Pin_I")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_O"))) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
			   case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
	 	}
 	}
	// Beginn der Funktionen
	
	// Führt eine Messung aus
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_trigger", "Pin" => $this->ReadPropertyInteger("Pin_O"), "Time" => 10)));
		}
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
