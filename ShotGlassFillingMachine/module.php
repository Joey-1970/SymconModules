<?
    // Klassendefinition
    class ShotGlassFillingMachine extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Shutdown", 0);
	}  
	    
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
			// Diese Zeile nicht löschen.
			parent::Create();
			$this->RegisterPropertyBoolean("Open", false);
			$this->RegisterPropertyInteger("Pin", -1);
			$this->SetBuffer("PreviousPin", -1);
			$this->RegisterPropertyInteger("most_anti_clockwise", 500);
			$this->RegisterPropertyInteger("midpoint", 1500);
			$this->RegisterPropertyInteger("most_clockwise", 2500);
			$this->RegisterPropertyInteger("Shutdown", 500);
			for ($i = 1; $i <= 5; $i++) {
				$this->RegisterPropertyInteger("Position_".$i, $i * 20);
			}
			$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
			$this->RegisterTimer("Shutdown", 0, 'ShotGlassFillingMachine_Shutdown($_IPS["TARGET"]);');

			// Profile erstellen
			$this->RegisterProfileInteger("ShotGlassFillingMachine.Position", "Information", "", "", 0, 3, 1);
			IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.Position", 0, "Ruheposition", "TurnLeft", 0x000000);
			for ($i = 1; $i <= 5; $i++) {
				IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.Position", $i, "Postion ".$i, "TurnRight", 0x000000);
			}
			
			// Status-Variablen anlegen
			$this->RegisterVariableInteger("Output", "Ausgang", "~Intensity.100", 10);
			$this->EnableAction("Output");
			$this->RegisterVariableInteger("Position", "Position", "ShotGlassFillingMachine.Position", 20);
			$this->EnableAction("Position");
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
 		$arrayElements[] = array("type" => "Label", "caption" => "Angabe der GPIO-Nummer (Broadcom-Number)"); 
  		
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
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "caption" => "Angabe der Microsekunden bei 50 Hz"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "most_anti_clockwise", "caption" => "Max. Links (ms)", "minimum" => 0); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "midpoint", "caption" => "Mittelstellung (ms)", "minimum" => 0); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "most_clockwise", "caption" => "Max. Rechts (ms)", "minimum" => 0);
		$arrayElements[] = array("type" => "Label", "caption" => "Zeit bis zur Abschaltung in Microsekunden (0 = keine automatische Abschaltung)"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Shutdown", "caption" => "Abschaltung (ms)", "minimum" => 0); 
		$arrayElements[] = array("type" => "Label", "caption" => "ACHTUNG: Falsche Werte können zur Beschädigung des Servo führen!");
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "caption" => "Angabe der Postitionen in Prozent"); 
		for ($i = 1; $i <= 5; $i++) {
			$arrayElements[] = array("type" => "NumberSpinner", "name" => "Position_".$i, "caption" => "Position ".$i, "minimum" => 0, "maximum" => 100); 
		}
		
		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$arrayActions = array(); 
			$arrayActions[] = array("type" => "Label", "label" => "Test Center"); 
			$arrayActions[] = array("type" => "TestCenter", "name" => "TestCenter");
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
		
		// Summary setzen
		$this->SetSummary("GPIO: ".$this->ReadPropertyInteger("Pin"));
            	
		//ReceiveData-Filter setzen
                $Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);
		
		$this->SetTimerInterval("Shutdown", 0);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {	
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin", $this->ReadPropertyInteger("Pin"));
				If ($Result == true) {
					$this->Setup();
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
				}
			}
			else {
				If ($this->GetStatus() <> 104) {
					$this->SetStatus(104);
				}
			}
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
		}
	}
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Output":
	            If ($this->ReadPropertyBoolean("Open") == true) {
		    		$this->SetOutput($Value);
		    	}
	            break;
			case "Position":
	            If ($this->ReadPropertyBoolean("Open") == true) {
		    		$this->SetPosition($Value);
		    	}
	            break;
	        default:
	            throw new Exception("Invalid Ident");
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
			break;
			   case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
	 	}
 	}
	// Beginn der Funktionen
	
	// Schaltet den gewaehlten Pin
	public function SetOutput(Int $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetOutput", "Ausfuehrung", 0);
			$Left = $this->ReadPropertyInteger("most_anti_clockwise");
			$Right = $this->ReadPropertyInteger("most_clockwise");
			$Shutdown = $this->ReadPropertyInteger("Shutdown");
			
			$Value = min(100, max(0, $Value));
			
			$Value = intval(($Value * ($Right - $Left) / 100) + $Left);
			$this->SendDebug("SetOutput", "Errechneter Zielwert: ".$Value, 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $Value)));
			If (!$Result) {
				$this->SendDebug("SetOutput", "Fehler beim Positionieren!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				//$Output = ($Value / ($Right - $Left)) * 100;
				$Output = (($Value - $Left)/ ($Right - $Left)) * 100;
				SetValue("Output", $Output);
				$this->GetOutput();
			}
			
			If ($Shutdown > 0) {
				$this->SetTimerInterval("Shutdown", $Shutdown);
			}
		}
	}

	public function SetPosition(Int $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetPosition", "Ausfuehrung", 0);
			$Left = $this->ReadPropertyInteger("most_anti_clockwise");
			$Right = $this->ReadPropertyInteger("most_clockwise");
			$Shutdown = $this->ReadPropertyInteger("Shutdown");
			If ($Value > 0) {
				$Position = $this->ReadPropertyInteger("Position_".$Value);
			}
			
			$Value = min(5, max(0, $Value));

			If ($Value == 0) {
				$Value = $Left;
			}
			else {
				$Value = intval(($Position * ($Right - $Left) / 100) + $Left);
			}
			
			$this->SendDebug("SetOutput", "Errechneter Zielwert: ".$Value, 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $Value)));
			If (!$Result) {
				$this->SendDebug("SetPosition", "Fehler beim Positionieren!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				
				$Output = (($Value - $Left)/ ($Right - $Left)) * 100;
				SetValue("Output", $Output);
				$this->GetOutput();
			}
			
			If ($Shutdown > 0) {
				$this->SetTimerInterval("Shutdown", $Shutdown);
			}
		}
	}
	  
	public function Shutdown()
	{
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => 0)));
			If (!$Result) {
				$this->SendDebug("Shutdown", "Fehler beim Ausschalten!", 0);
				$this->SetStatus(202);
			}
		$this->SetTimerInterval("Shutdown", 0);
	}
	    
	public function GetOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetOutput", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_servo", "Pin" => $this->ReadPropertyInteger("Pin") )));
			If ($Result < 0) {
				$this->SendDebug("GetOutput", "Fehler beim Lesen!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$this->SendDebug("GetOutput", "Wert: ".$Result, 0);
				$Left = $this->ReadPropertyInteger("most_anti_clockwise");
				$Right = $this->ReadPropertyInteger("most_clockwise");
				$Output = (($Result - $Left)/ ($Right - $Left)) * 100;
				SetValue("Output", $Output);
			}
		}
	}   
	
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $this->ReadPropertyInteger("midpoint"))));
			If (!$Result) {
				$this->SendDebug("Setup", "Fehler beim Stellen der Mittelstellung!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
			else {
				$this->SetStatus(102);
				SetValue("Output", 50);
				$this->GetOutput();
				IPS_Sleep(500);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => 0)));
				If (!$Result) {
					$this->SendDebug("Setup", "Fehler beim Ausschalten!", 0);
				}
			}
		}
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
		
	protected function HasActiveParent()
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
