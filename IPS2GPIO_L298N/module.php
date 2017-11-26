<?
    // Klassendefinition
    class IPS2GPIO_L298N extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin_1L", -1);
		$this->SetBuffer("PreviousPin_1L", -1);
		$this->RegisterPropertyInteger("Pin_1R", -1);
		$this->SetBuffer("PreviousPin_1R", -1);
		$this->RegisterPropertyInteger("Pin_2L", -1);
		$this->SetBuffer("PreviousPin_2L", -1);
		$this->RegisterPropertyInteger("Pin_2R", -1);
		$this->SetBuffer("PreviousPin_2R", -1);
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
 		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number)"); 
  		
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		$arrayElements[] = array("type" => "Label", "label" => "Motor Ausgang 1, Linkslauf"); 
		If ($this->ReadPropertyInteger("Pin_1L") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_1L")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_1L")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_1L", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Motor Ausgang 1, Rechtslauf"); 
		If ($this->ReadPropertyInteger("Pin_1R") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_1R")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_1R")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_1R", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Motor Ausgang 2, Linkslauf"); 
		If ($this->ReadPropertyInteger("Pin_2L") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_2L")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_2L")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_2L", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "Motor Ausgang 2, Rechtslauf"); 
		If ($this->ReadPropertyInteger("Pin_2R") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_2R")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_2R")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_2R", "caption" => "GPIO-Nr.", "options" => $arrayOptions );

		
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		
		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			//$arrayActions[] = array("type" => "Button", "label" => "On", "onClick" => 'I2GOUT_Set_Status($id, true);');
			//$arrayActions[] = array("type" => "Button", "label" => "Off", "onClick" => 'I2GOUT_Set_Status($id, false);');
			//$arrayActions[] = array("type" => "Button", "label" => "Toggle Output", "onClick" => 'I2GOUT_Toggle_Status($id);');
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
		If ((intval($this->GetBuffer("PreviousPin_1L")) <> $this->ReadPropertyInteger("Pin_1L")) OR
			(intval($this->GetBuffer("PreviousPin_1R")) <> $this->ReadPropertyInteger("Pin_1R")) OR
			(intval($this->GetBuffer("PreviousPin_2L")) <> $this->ReadPropertyInteger("Pin_2L")) OR
			(intval($this->GetBuffer("PreviousPin_2R")) <> $this->ReadPropertyInteger("Pin_2R"))) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_1L")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_1L"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_1R")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_1R"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_2L")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_2L"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_2R")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_2R"), 0);
		}

		//Status-Variablen anlegen
		$this->RegisterVariableBoolean("Motor_1L", "Motor 1 Links", "~Switch", 10);
		$this->EnableAction("Motor_1L");
		$this->RegisterVariableBoolean("Motor_1S", "Motor 1 Stop", "~Switch", 20);
		$this->EnableAction("Motor_1S");
		$this->RegisterVariableBoolean("Motor_1R", "Motor 1 Rechts", "~Switch", 30);
		$this->EnableAction("Motor_1R");
		
		$this->RegisterVariableBoolean("Motor_2L", "Motor 2 Links", "~Switch", 40);
		$this->EnableAction("Motor_2L");
		$this->RegisterVariableBoolean("Motor_2S", "Motor 2 Stop", "~Switch", 50);
		$this->EnableAction("Motor_2S");
		$this->RegisterVariableBoolean("Motor_2R", "Motor 2 Rechts", "~Switch", 60);
		$this->EnableAction("Motor_2R");
            	
             	//ReceiveData-Filter setzen
                $Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);

		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {	
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_1L"), "PreviousPin" => $this->GetBuffer("PreviousPin_1L"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_1L", $this->ReadPropertyInteger("Pin_1L"));
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_1R"), "PreviousPin" => $this->GetBuffer("PreviousPin_1R"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_1R", $this->ReadPropertyInteger("Pin_1R"));
				
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_2L"), "PreviousPin" => $this->GetBuffer("PreviousPin_2L"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_2L", $this->ReadPropertyInteger("Pin_2L"));
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_2R"), "PreviousPin" => $this->GetBuffer("PreviousPin_2R"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_2R", $this->ReadPropertyInteger("Pin_2R"));
				
				If ($Result == true) {
					//$this->Get_Status();
					
					$this->SetStatus(102);
				}
			}
			else {
				$this->SetStatus(104);
			}
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

	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			   case "get_usedpin":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
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
	
	// Toggelt den Status
	public function Toggle_Status()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Toggle_Status", "Ausfuehrung", 0);
			$this->Set_Status(!GetValueBoolean($this->GetIDForIdent("Status")));
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
