<?
    // Klassendefinition
    class IPS2GPIO_Circulation extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin", -1);
		$this->SetBuffer("PreviousPin", -1);
		$this->RegisterPropertyInteger("FlowTemperature_ID", 0);
		$this->RegisterPropertyInteger("ReturnTemperature_ID", 0);
		$this->RegisterPropertyInteger("Amplification", 10);
		$this->RegisterPropertyInteger("PitchThreshold", 2);
		$this->RegisterPropertyInteger("MinRuntime", 120);
		$this->RegisterPropertyInteger("ParallelShift", 15);
		$this->RegisterPropertyBoolean("Invert", false);
		$this->RegisterPropertyBoolean("Logging", false);
		$this->RegisterPropertyInteger("Startoption", 2);
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
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für die Ansteuerung der Zirkulationspumpe"); 
  		
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
		$arrayElements[] = array("type" => "Label", "label" => "Variable der Vorlauftemperatur");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "FlowTemperature_ID", "caption" => "Variablen ID");
		$arrayElements[] = array("type" => "Label", "label" => "Variable der Rücklauftemperatur");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "ReturnTemperature_ID", "caption" => "Variablen ID");
		$arrayElements[] = array("type" => "Label", "label" => "Verstärkungsfaktor der Temperaturdifferenz");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Amplification", "caption" => "Faktor");
		$arrayElements[] = array("type" => "Label", "label" => "Schwellwert der Steigung");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "PitchThreshold", "caption" => "Schwellwert", "digits" => 1);
		$arrayElements[] = array("type" => "Label", "label" => "Minimale Laufzeit der Zirkulationspumpe");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "MinRuntime", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "Temperaturdifferenz Vor- zu Rücklauf als Abschaltbedingung (K)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "ParallelShift", "caption" => "Temperaturdifferenz");
		
		$arrayElements[] = array("name" => "Invert", "type" => "CheckBox",  "caption" => "Invertiere Anzeige");
		$arrayElements[] = array("name" => "Logging", "type" => "CheckBox",  "caption" => "Logging aktivieren");
		$arrayElements[] = array("type" => "Label", "label" => "Status des Ausgangs nach Neustart");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Aus", "value" => 0);
		$arrayOptions[] = array("label" => "An", "value" => 1);
		$arrayOptions[] = array("label" => "undefiniert", "value" => 2);
		$arrayElements[] = array("type" => "Select", "name" => "Startoption", "caption" => "Startoption", "options" => $arrayOptions );
		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$arrayActions[] = array("type" => "Button", "label" => "On", "onClick" => 'IPS2Cn_Set_Status($id, true);');
			$arrayActions[] = array("type" => "Button", "label" => "Off", "onClick" => 'IPS2Cn_Set_Status($id, false);');
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
		// Anlegen des Wochenplans
		$this->RegisterEvent("Wochenplan", "IPS2Cn_Event_".$this->InstanceID, 2, $this->InstanceID, 20);
		
		//Status-Variablen anlegen
		$this->RegisterVariableBoolean("Status", "Status", "~Switch", 10);
		$this->EnableAction("Status");
            	
		// Anlegen der Daten für den Wochenplan
		for ($i = 0; $i <= 6; $i++) {
			IPS_SetEventScheduleGroup($this->GetIDForIdent("IPS2Cn_Event_".$this->InstanceID), $i, pow(2, $i));
		}
		
		$this->RegisterScheduleAction($this->GetIDForIdent("IPS2Cn_Event_".$this->InstanceID), 0, "An", 0x40FF00, "IPS2Cn_SetPumpState(\$_IPS['TARGET'], 1);");
		$this->RegisterScheduleAction($this->GetIDForIdent("IPS2Cn_Event_".$this->InstanceID), 1, "Aus", 0xFF0040, "IPS2Cn_SetPumpState(\$_IPS['TARGET'], 0);");
		
		
		// Registrierung für die Änderung der Vorlauf-Temperatur
		If ($this->ReadPropertyInteger("FlowTemperature_ID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("FlowTemperature_ID"), 10603);
		}
		// Registrierung für die Änderung der Return-Temperatur
		If ($this->ReadPropertyInteger("ReturnTemperature_ID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("ReturnTemperature_ID"), 10603);
		}
		// Registrierung für den Wochenplan
		$this->RegisterMessage($this->GetIDForIdent("IPS2Cn_Event_".$this->InstanceID), 10803);
	
		
		
		// Logging setzen
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Status"), $this->ReadPropertyBoolean("Logging"));
		IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
             	//ReceiveData-Filter setzen
                $Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {	
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin", $this->ReadPropertyInteger("Pin"));
				If ($Result == true) {
					$this->Get_Status();
					If ($this->ReadPropertyInteger("Startoption") == 0) {
						$this->Set_Status(false);
					}
					elseif ($this->ReadPropertyInteger("Startoption") == 1) {
						$this->Set_Status(true);
					}
					$this->SetStatus(102);
				}
			}
			else {
				$this->SetStatus(104);
			}
		}
		else {
			$this->SetStatus(104);
		}
	}
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Status":
	            If ($this->ReadPropertyBoolean("Open") == true) {
		    	$this->Set_Status($Value);
		    }
	            break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case 10803:
				$this->SendDebug("ReceiveData", "Ausloeser Wochenplan", 0);
				break;
			case 10603:
				// Änderung der Vorlauf-Temperatur
				If ($SenderID == $this->ReadPropertyInteger("FlowTemperature_ID")) {
					$this->SendDebug("ReceiveData", "Ausloeser Aenderung Vorlauf-Temperatur", 0);
					$this->Calculate();
				}
				//Änderung der Rücklauf-Temperatur
				elseif ($SenderID == $this->ReadPropertyInteger("ReturnTemperature_ID")) {
					$this->SendDebug("ReceiveData", "Ausloeser Aenderung Ruecklauf-Temperatur", 0);
					$this->SwitchOff();
				}
				break;
		}
    	}    
	    
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
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
	
	// Beginn der Funktionen
	public function Calculate()
	{
		// Prüfen, ob die Zirkulationspumpe aufgrund einer Warmwasseranforderung eingeschaltet werden soll
		If (($this->ReadPropertyInteger("FlowTemperature_ID") > 0) AND ($this->ReadPropertyInteger("ReturnTemperature_ID"))) {
			$FlowTemperature = GetValueFloat($this->ReadPropertyInteger("FlowTemperature_ID"));
			$TempDiff = $FlowTemperature - $this->GetBuffer("LastFlowTemperature");
			$TimeDiff = time() -  $this->GetBuffer("LastCalculate");
			$Amplification = $this->ReadPropertyInteger("Amplification");
			$PumpState = GetValueBoolean($this->GetIDForIdent("Status"));
			$PitchThreshold = $this->ReadPropertyFloat("PitchThreshold");
			
			If ($TimeDiff > 0) {
				$Pitch = ($TempDiff * $Amplification) / $TimeDiff;
				$this->SendDebug("Calculate", "Steigung: ".round($Pitch, 2)." Temperaturdifferenz: ".$TempDiff." °C Zeitdifferenz: ".round($TimeDiff, 2), 0);
				If (($Pitch > $PitchThreshold) And ($TimeDiff > 1) And ($PumpState == false)) {
					// Pumpe einschalten
					$this->Set_Status(true);
					$this->SendDebug("Calculate", "Die Zirkulationspumpe wird wegen der Warmwasseranforderung eingeschaltet", 0);
					$this->SetBuffer("LastSwitchOn", time());
				}
			}
			
			$this->SetBuffer("LastCalculate", time());
			$this->SetBuffer("LastFlowTemperature", $FlowTemperature);
		}
			
	}
	
	public function SetPumpState(string $State)
	{
		$this->SendDebug("SetPumpState", "Aufruf aus dem Wochenplan", 0);
	}
	    
	private function SwitchOff()
	{
		If (($this->ReadPropertyInteger("FlowTemperature_ID") > 0) AND ($this->ReadPropertyInteger("ReturnTemperature_ID"))) {
			$FlowTemperature = GetValueFloat($this->ReadPropertyInteger("FlowTemperature_ID"));
			$ReturnTemperature = GetValueFloat($this->ReadPropertyInteger("ReturnTemperature_ID"));
			$TempDiff = $FlowTemperature - $ReturnTemperature;
			$TimeDiff = time() -  $this->GetBuffer("LastSwitchOn");
			$MinRuntime = $this->ReadPropertyInteger("MinRuntime");
			$ParallelShift = $this->ReadPropertyInteger("ParallelShift");
			$PumpState = GetValueBoolean($this->GetIDForIdent("Status"));
			
			If (($TimeDiff > $MinRuntime) AND (($ReturnTemperature - $ParallelShift) > $FlowTemperature) And ($PumpState == true)) {
				// Pumpe ausschalten
				$this->Set_Status(false);
				$this->SendDebug("Calculate", "Die Zirkulationspumpe wird ausgeschaltet da der Schwellwert der Rücklauftemperatur erreicht wurde", 0);
			}
		}
	}
	    
	// Schaltet den gewaehlten Pin
	public function Set_Status(Bool $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Set_Status", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => ($Value ^ $this->ReadPropertyBoolean("Invert")) )));
			$this->SendDebug("Set_Status", "Ergebnis: ".(int)$Result, 0);
			IF (!$Result) {
				$this->SendDebug("Set_Status", "Fehler beim Setzen des Status!", 0);
				return;
			}
			else {
				SetValueBoolean($this->GetIDForIdent("Status"), ($Value ^ $this->ReadPropertyBoolean("Invert")));
				$this->Get_Status();
			}
		}
	}
	
	// Ermittelt den Status
	public function Get_Status()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Get_Status", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_value", "Pin" => $this->ReadPropertyInteger("Pin") )));
			If ($Result < 0) {
				$this->SendDebug("Set_Status", "Fehler beim Lesen des Status!", 0);
				return;
			}
			else {
				$this->SendDebug("Get_Status", "Ergebnis: ".(int)$Result, 0);
				SetValueBoolean($this->GetIDForIdent("Status"), ($Result ^ $this->ReadPropertyBoolean("Invert")));
			}
		}
	}
	
	private function GetEventActionID($EventID, $EventType, $Days, $Hour, $Minute)
	{
		$EventValue = IPS_GetEvent($EventID);
		$Result = false;
		// Prüfen um welche Art von Event es sich handelt
		If ($EventValue['EventType'] == $EventType) {
			$ScheduleGroups = $EventValue['ScheduleGroups'];
			// Anzahl der ScheduleGroups ermitteln	
			$ScheduleGroupsCount = count($ScheduleGroups);
			If ($ScheduleGroupsCount > 0) {
				for ($i = 0; $i <= $ScheduleGroupsCount - 1; $i++) {	
					If ($ScheduleGroups[$i]['Days'] == $Days) {
						$ScheduleGroupDay = $ScheduleGroups[$i];
						$ScheduleGroupsDayCount = count($ScheduleGroupDay['Points']);
						If ($ScheduleGroupsDayCount == 0) {
							IPS_LogMessage("IPS2SingleRoomControl", "Keine Schaltpunkte definiert!"); 	
						}
						elseif ($ScheduleGroupsDayCount == 1) {
							$Result = $ScheduleGroupDay['Points'][0]['ActionID'];
						}
						elseif ($ScheduleGroupsDayCount > 1) {
							for ($j = 0; $j <= $ScheduleGroupsDayCount - 1; $j++) {
								$TimestampScheduleStart = mktime($ScheduleGroupDay['Points'][$j]['Start']['Hour'], $ScheduleGroupDay['Points'][$j]['Start']['Minute'], 0, 0, 0, 0);
								If ($j < $ScheduleGroupsDayCount - 1) {
									$TimestampScheduleEnd = mktime($ScheduleGroupDay['Points'][$j + 1]['Start']['Hour'], $ScheduleGroupDay['Points'][$j + 1]['Start']['Minute'], 0, 0, 0, 0);
								}
								else {
									$TimestampScheduleEnd = mktime(24, 0, 0, 0, 0, 0);
								}
								$Timestamp = mktime($Hour, $Minute, 0, 0, 0, 0);
								If (($Timestamp >= $TimestampScheduleStart) AND ($Timestamp < $TimestampScheduleEnd)) {
									$Result = ($ScheduleGroupDay['Points'][$j]['ActionID']) + 1;
								} 
							}
						}
					}
				}
			}
			else {
				IPS_LogMessage("IPS2SingleRoomControl", "Es sind keine Aktionen eingerichtet!");
			}
		  }
	return $Result;
	}
	
	private function RegisterEvent($Name, $Ident, $Typ, $Parent, $Position)
	{
		$eid = @$this->GetIDForIdent($Ident);
		if($eid === false) {
		    	$eid = 0;
		} elseif(IPS_GetEvent($eid)['EventType'] <> $Typ) {
		    	IPS_DeleteEvent($eid);
		    	$eid = 0;
		}
		//we need to create one
		if ($eid == 0) {
			$EventID = IPS_CreateEvent($Typ);
		    	IPS_SetParent($EventID, $Parent);
		    	IPS_SetIdent($EventID, $Ident);
		    	IPS_SetName($EventID, $Name);
		    	IPS_SetPosition($EventID, $Position);
		    	IPS_SetEventActive($EventID, true);  
		}
	}  
	
	private function RegisterScheduleAction($EventID, $ActionID, $Name, $Color, $Script)
	{
		IPS_SetEventScheduleAction($EventID, $ActionID, $Name, $Color, $Script);
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
