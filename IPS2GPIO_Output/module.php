<?
    // Klassendefinition
    class IPS2GPIO_Output extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin", -1);
		$this->SetBuffer("PreviousPin", -1);
		$this->RegisterPropertyBoolean("Invert", false);
		$this->RegisterPropertyBoolean("Logging", false);
		$this->RegisterPropertyInteger("Startoption", 2);
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		
		// Status-Variablen anlegen
		$this->RegisterVariableBoolean("Status", "Status", "~Switch", 10);
		$this->EnableAction("Status");
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
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
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
			$arrayActions[] = array("type" => "Button", "label" => "On", "onClick" => 'I2GOUT_Set_Status($id, true);');
			$arrayActions[] = array("type" => "Button", "label" => "Off", "onClick" => 'I2GOUT_Set_Status($id, false);');
			$arrayActions[] = array("type" => "Button", "label" => "Toggle Output", "onClick" => 'I2GOUT_Toggle_Status($id);');
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
		    	$this->SetOutput($Value);
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
	 	}
 	}
	// Beginn der Funktionen
	
	// Schaltet den gewaehlten Pin
	public function Set_Status(Bool $Value)
	{
		// Übergangsfunktion
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SetOutput($Value);
		}
	}
	    
	public function SetOutput(Bool $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Value = min(1, max(0, $Value));
			$this->SendDebug("SetOutput", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => ($Value ^ $this->ReadPropertyBoolean("Invert")) )));
			$this->SendDebug("SetOutput", "Ergebnis: ".(int)$Result, 0);
			IF (!$Result) {
				$this->SendDebug("SetOutput", "Fehler beim Setzen des Status!", 0);
				$this->SetStatus(202);
				return;
			}
			else {
				$this->SetStatus(102);
				SetValueBoolean($this->GetIDForIdent("Status"), ($Value ^ $this->ReadPropertyBoolean("Invert")));
				$this->Get_Status();
			}
		}
	}
	
	// Toggelt den Status
	public function Toggle_Status()
	{
		// Übergangsfunktion
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->ToggleOutput();
		}
	}
	    
	public function ToggleOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("ToggleOutput", "Ausfuehrung", 0);
			$this->Set_Status(!GetValueBoolean($this->GetIDForIdent("Status")));
		}
	}
	
	// Ermittelt den Status
	public function Get_Status()
	{
		// Übergangsfunktion
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->GetOutput();
		}
	}
	    
	public function GetOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Get_Status", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_value", "Pin" => $this->ReadPropertyInteger("Pin") )));
			If ($Result < 0) {
				$this->SendDebug("GetOutput", "Fehler beim Lesen des Status!", 0);
				$this->SetStatus(202);
				return;
			}
			else {
				$this->SetStatus(102);
				$this->SendDebug("GetOutput", "Ergebnis: ".(int)$Result, 0);
				SetValueBoolean($this->GetIDForIdent("Status"), ($Result ^ $this->ReadPropertyBoolean("Invert")));
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
