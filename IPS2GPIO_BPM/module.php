<?
    // Klassendefinition
    class IPS2GPIO_BPM extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin", -1);
		$this->SetBuffer("PreviousPin", -1);
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		$this->RegisterPropertyInteger("BPMUpThreshold", 160);
		$this->RegisterPropertyInteger("BPMDownThreshold", 50);
		$BPMArray = array();
		$this->SetBuffer("BPMArray", serialize($BPMArray));
		
	        //Status-Variablen anlegen
                $this->RegisterVariableBoolean("Trigger", "Trigger", "~Switch", 10);
                $this->DisableAction("Trigger");
		IPS_SetHidden($this->GetIDForIdent("Trigger"), false);
        }
	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "GPIO-Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv");
 		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number)"); 
  		
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");

		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
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
		If (intval($this->GetBuffer("PreviousPin")) <> $this->ReadPropertyInteger("Pin")) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin"), 0);
		}
		
		// Profil anlegen
		$this->RegisterProfileInteger("IPS2GPIO.BPM", "Clock", "", " BPM", 0, 200, 1);
		
		$this->RegisterVariableInteger("BPM", "BPM", "IPS2GPIO.BPM", 20);
		$this->EnableAction("BPM");
		IPS_SetHidden($this->GetIDForIdent("BPM"), false);
		
		$BPMArray = array();
		$this->SetBuffer("BPMArray", serialize($BPMArray));
		$this->SetBuffer("OldTimestamp", 0);
		
  	   
                //ReceiveData-Filter setzen
		$Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 0, "Resistance" => 0)));
				$this->SetBuffer("PreviousPin", $this->ReadPropertyInteger("Pin"));
				If ($Result == true) {
					$this->SetStatus(102);
				}
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
			   		$this->SendDebug("Notify", "Ausfuehrung", 0);
					// Trigger kurzzeitig setzen
			   		If (intval($data->Value) == true) {
			   			$OldTimestamp = intval($this->GetBuffer("OldTimestamp"));
						$BPMUpThreshold = $this->ReadPropertyInteger("BPMUpThreshold");
						$BPMDownThreshold = $this->ReadPropertyInteger("BPMDownThreshold");
						$this->SendDebug("Notify", "Trigger setzen mit Wert: ".intval($data->Value)." Zeitstempel:".$data->Timestamp, 0);
						SetValueBoolean($this->GetIDForIdent("Trigger"), true);
			   			SetValueBoolean($this->GetIDForIdent("Trigger"), false);
						If ($OldTimestamp == 0) {
							$this->SetBuffer("OldTimestamp", intval($data->Timestamp) );
						}
						else {
							// Zeitdifferenz in Microsekunden
							$TimeDiff = abs(intval($data->Timestamp) - $OldTimestamp);
							// Zeitdifferenz in Millisekunden
							$TimeDiff = intval($TimeDiff / 1000);
							$BPM = round(60000 / $TimeDiff, 0);
							
							$this->SendDebug("Notify", "Zeitdifferenz: ".$TimeDiff."ms BPM: ".$BPM, 0);
							$BPMArray = array();
							$BPMArray = unserialize($this->GetBuffer("BPMArray"));
							
							If (count($BPMArray) < 5) {
								If ($BPM < $BPMDownThreshold) {
									$this->SetBuffer("OldTimestamp", intval($data->Timestamp) );
								}
								elseif ($BPM > $BPMUpThreshold) {
									// nichts machen
								}
								else {
									$BPMArray[] = $BPM;
									$this->SetBuffer("BPMArray", serialize($BPMArray));
									$this->SendDebug("Notify", "Array < 5: ".serialize($BPMArray), 0);
									$this->SetBuffer("OldTimestamp", intval($data->Timestamp) );
								}
							}
							else {
								If ($BPM < $BPMDownThreshold) {
									$this->SetBuffer("OldTimestamp", intval($data->Timestamp) );
								}
								elseif ($BPM > $BPMUpThreshold) {
									// nichts machen
								}
								else {
									// Erstes (ältestes) Element entfernen
									$FirstValue = array_shift($BPMArray);
									// Neustes Element hinzufügen
									$BPMArray[] = $BPM;

									// Höchsten und niedrigsten Wert löschen
									$MaxValue = max($BPMArray);
									$MaxIndex = array_search($MaxValue, $BPMArray);
									$MinValue = min($BPMArray);
									$MinIndex = array_search($MinValue, $BPMArray);
									unset($BPMArray[$MinIndex], $BPMArray[$MaxIndex]);
									// Array sichern
									$this->SetBuffer("BPMArray", serialize($BPMArray));
									$this->SendDebug("Notify", "Array > 5: ".serialize($BPMArray), 0);
									$BPM = array_sum($BPMArray) / count($BPMArray);
									SetValueInteger($this->GetIDForIdent("BPM"), $BPM);
									$this->SetBuffer("OldTimestamp", intval($data->Timestamp) );
								}
							}
						}
			   		}		   		
			   	}
			   	break;
			   case "get_usedpin":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->ApplyChanges();
				}
				break;
			   case "status":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
	 	}
 	}
	
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);    
	}    
	
	private function Get_GPIO()
	{
		If ($this->HasActiveParent() == true) {
			$GPIO = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_GPIO")));
		}
		else {
			$AllGPIO = array();
			$AllGPIO[-1] = "undefiniert";
			for ($i = 2; $i <= 27; $i++) {
				$AllGPIO[$i] = "GPIO".(sprintf("%'.02d", $i));
			}
			$GPIO = serialize($AllGPIO);
		}
	return $GPIO;
	}
	    
	private function HasActiveParent()
    	{
		$Instance = @IPS_GetInstance($this->InstanceID);
		if ($Instance['ConnectionID'] > 0)
		{
			$Parent = IPS_GetInstance($Instance['ConnectionID']);
			if ($Parent['InstanceStatus'] == 102)
			return true;
		}
        return false;
    	}  
	
}
?>
