<?
    // Klassendefinition
    class IPS2GPIO_Vaillant extends IPSModule 
    {
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
		// Diese Zeile nicht löschen.
		parent::Create();
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin", -1);
		$this->RegisterPropertyInteger("OutdoorTemperature_ID", 0);
		$this->RegisterPropertyInteger("ReferenceTemperature_ID", 0);
		$this->RegisterPropertyInteger("FlowTemperature_ID", 0);
		$this->RegisterPropertyInteger("ReturnTemperature_ID", 0);
		$this->RegisterPropertyFloat("Steepness", 1);
		$this->RegisterPropertyInteger("ParallelShift", 15);
		$this->RegisterPropertyInteger("MinTemp", 35);
		$this->RegisterPropertyInteger("MaxTemp", 70);
		$this->RegisterPropertyInteger("SwitchTemp", 20);
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
 		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für die PWM Steuerung"); 
  		
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
		
		$arrayElements[] = array("type" => "Label", "label" => "Variable der Aussentemperatur");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "OutdoorTemperature_ID", "caption" => "Variablen ID");
		$arrayElements[] = array("type" => "Label", "label" => "Variable der Referenztemperatur");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "ReferenceTemperature_ID", "caption" => "Variablen ID");
		$arrayElements[] = array("type" => "Label", "label" => "Variable der Vorlauftemperatur (optional)");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "FlowTemperature_ID", "caption" => "Variablen ID");
		$arrayElements[] = array("type" => "Label", "label" => "Variable der Rücklauftemperatur (optional)");
		$arrayElements[] = array("type" => "SelectVariable", "name" => "ReturnTemperature_ID", "caption" => "Variablen ID");
		
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der Steilheit (Typische Werte: 0,1 - 3,5)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Steepness", "caption" => "Steilheit", "digits" => 1);
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der Parallelverschiebung (K)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "ParallelShift", "caption" => "Parallelverschiebung");
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der Minimaltemperatur (C°)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "MinTemp", "caption" => "Minimaltemperatur");
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der Maximaltemperatur (C°)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "MaxTemp", "caption" => "Maximaltemperatur");
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der Umschalttemperatur Sommer/Winter (C°)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "SwitchTemp", "caption" => "Umschalttemperatur");
		
		
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
		
		$this->RegisterProfileInteger("IPS2GPIO.HeatingStatus", "Information", "", "", 0, 2, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.HeatingStatus", 0, "unbekannt", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.HeatingStatus", 1, "Winterbetrieb", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.HeatingStatus", 2, "Sommerbetrieb", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.HeatingStatus", 3, "gestört", "Information", -1);
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("Status", "Status", "IPS2GPIO.HeatingStatus", 10);
	        $this->DisableAction("Status");
		
		$this->RegisterVariableFloat("SetTemperature", "Soll-Temperatur", "~Temperature", 20);
	        $this->DisableAction("SetTemperature");
	        
		$this->RegisterVariableFloat("Voltage", "Spannung", "~Volt", 30);
	        $this->DisableAction("Voltage");
           	
		// Registrierung für die Änderung der Aussen-Temperatur
		If ($this->ReadPropertyInteger("OutdoorTemperature_ID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("OutdoorTemperature_ID"), 10603);
		}
		// Registrierung für die Änderung der Referenz-Temperatur
		If ($this->ReadPropertyInteger("ReferenceTemperature_ID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("ReferenceTemperature_ID"), 10603);
		}
		// Registrierung für die Änderung der Vorlauf-Temperatur
		If ($this->ReadPropertyInteger("FlowTemperature_ID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("FlowTemperature_ID"), 10603);
		}
		// Registrierung für die Änderung der Rücklauf-Temperatur
		If ($this->ReadPropertyInteger("ReturnTemperature_ID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("ReturnTemperature_ID"), 10603);
		}
		
           	//ReceiveData-Filter setzen
		$Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);
		SetValueInteger($this->GetIDForIdent("Status"), 0);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				If ($Result == true) {
					$this->SetStatus(102);
					$this->Calculate();
				}
			}
			else {
				SetValueInteger($this->GetIDForIdent("Status"), 0);
				$this->SetStatus(104);
			}
		}
 	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Status":
	            $this->Set_Status($Value);
	            break;
	        case "Intensity":
	            $this->Set_Intensity($Value);
	            break;
	        default:
	            throw new Exception("Invalid Ident");
	    }
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case 10603:
				// Änderung der Aussentemperatur
				If ($SenderID == $this->ReadPropertyInteger("OutdoorTemperature_ID")) {
					$this->SendDebug("ReceiveData", "Ausloeser Aenderung Aussentemperatur", 0);
					$this->Calculate();
				}
				// Änderung der Referenztemperatur
				elseif ($SenderID == $this->ReadPropertyInteger("ReferenceTemperature_ID")) {
					$this->SendDebug("ReceiveData", "Ausloeser Aenderung Referenztemperatur", 0);
					$this->Calculate();
				}
				// Änderung der Vorlauftemperatur
				elseif ($SenderID == $this->ReadPropertyInteger("FlowTemperature_ID")) {
					$this->SendDebug("ReceiveData", "Ausloeser Aenderung Vorlauftemperatur", 0);
					$this->Calculate();
				}
				// Änderung der Rücklauftemperatur
				elseif ($SenderID == $this->ReadPropertyInteger("ReturnTemperature_ID")) {
					$this->SendDebug("ReceiveData", "Ausloeser Aenderung Rücklauftemperatur", 0);
					$this->Calculate();
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
		If (($this->ReadPropertyInteger("OutdoorTemperature_ID") > 0) AND ($this->ReadPropertyInteger("ReferenceTemperature_ID") > 0)) {
			$OutdoorTemperature = GetValueFloat($this->ReadPropertyInteger("OutdoorTemperature_ID"));
			$SwitchTemp = $this->ReadPropertyInteger("SwitchTemp");
			$Steepness = $this->ReadPropertyFloat("Steepness");
			$MinTemp = $this->ReadPropertyInteger("MinTemp");
			$MaxTemp = $this->ReadPropertyInteger("MaxTemp");
			$ParallelShift = $this->ReadPropertyInteger("ParallelShift");
			$ReferenceTemperature = GetValueFloat($this->ReadPropertyInteger("ReferenceTemperature_ID"));
			If (($this->ReadPropertyInteger("FlowTemperature_ID") > 0) AND ($this->ReadPropertyInteger("ReturnTemperature_ID") > 0)) {
				$FlowTemperature = GetValueFloat($this->ReadPropertyInteger("FlowTemperature_ID"));
				$ReturnTemperature = GetValueFloat($this->ReadPropertyInteger("ReturnTemperature_ID"));
				$TempDiff = $FlowTemperature - $ReturnTemperature;
				$ParallelShift = $ParallelShift + ($TempDiff - $ParallelShift);
			}
			
			If ($OutdoorTemperature < $SwitchTemp) {
				// Winterbetrieb
				If (GetValueInteger($this->GetIDForIdent("Status")) <> 1) {
					SetValueInteger($this->GetIDForIdent("Status"), 1);
				}
				$SetTemperature = min(max(round((0.55 * $Steepness * (pow($ReferenceTemperature,($OutdoorTemperature / (320 - $OutdoorTemperature * 4))))*((-$OutdoorTemperature + 20) * 2) + $ReferenceTemperature + $ParallelShift) * 1) / 1, $MinTemp), $MaxTemp);
				If (GetValueFloat($this->GetIDForIdent("SetTemperature")) <> $SetTemperature) {
					SetValueFloat($this->GetIDForIdent("SetTemperature"), $SetTemperature);
				}
				$Voltage = ((($SetTemperature - 40) / 10) + 11.9);	
			}
			If ($OutdoorTemperature >= $SwitchTemp) {			     
				If (GetValueInteger($this->GetIDForIdent("Status")) <> 2) {
					SetValueInteger($this->GetIDForIdent("Status"), 2);
				}
				If (GetValueFloat($this->GetIDForIdent("SetTemperature")) <> 0) {
					SetValueFloat($this->GetIDForIdent("SetTemperature"), 0);
				}
				$Voltage = 11.4;
			}
			If (GetValueFloat($this->GetIDForIdent("Voltage")) <> $Voltage) {
				SetValueFloat($this->GetIDForIdent("Voltage"), $Voltage);
			}
			$Intensity = intval($Voltage / 15 * 100 * 2.55);
			$this->SendDebug("Calculate", "Stellwert: ".$Intensity." Spannung: ".$Voltage."V", 0);
			$this->Set_Intensity($Intensity);
		}
			
	}
	    
	// PWM auf dem gewaehlten Pin
	public function Set_Intensity(Int $value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$value = min(255, max(0, $value));
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $value)));
			If (!$Result) {
				$this->SendDebug("Set_Intensity", "Fehler beim Schreiben des Wertes!", 0);
				If (GetValueInteger($this->GetIDForIdent("Status")) <> 3) {
					SetValueInteger($this->GetIDForIdent("Status"), 3);
				}
				return;
			}
			else {
				$this->SendDebug("Set_Intensity", "Ausfuehrung erfolgreich", 0);
			}
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
