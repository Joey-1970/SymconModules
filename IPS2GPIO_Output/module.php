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
		$this->RegisterPropertyBoolean("Logging", false);
		$this->RegisterPropertyBoolean("Startoption", false);
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
		$arrayOptions[] = array("label" => "ungesetzt", "value" => -1);
		for ($i = 0; $i <= 27; $i++) {
			$arrayOptions[] = array("label" => $i, "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("name" => "Logging", "type" => "CheckBox",  "caption" => "Logging aktivieren");
		$arrayElements[] = array("type" => "Label", "label" => "Status des Ausgangs nach Neustart");
		$arrayElements[] = array("name" => "Startoption", "type" => "CheckBox",  "caption" => "Status");
		
		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
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

		//Status-Variablen anlegen
		$this->RegisterVariableBoolean("Status", "Status", "~Switch", 10);
		$this->EnableAction("Status");
            	
		// Logging setzen
		AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Status"), $this->ReadPropertyBoolean("Logging"));
		IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);

             	//ReceiveData-Filter setzen
                $Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);

		If (IPS_GetKernelRunlevel() == 10103) {
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				
				$this->Set_Status($this->ReadPropertyBoolean("Startoption"));
				$this->SetStatus(102);
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
	            	//Neuen Wert in die Statusvariable schreiben
	            	//SetValue($this->GetIDForIdent($Ident), $Value);
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
			  case "result":
				If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   		//SetValueBoolean($this->GetIDForIdent("Status"), $data->Value);
				}
			break;
			   case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
	 	}
 	}
	// Beginn der Funktionen
	
	// Schaltet den gewaehlten Pin
	public function Set_Status(Bool $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $Value)));
			$this->Get_Status();
		}
	}
	
	// Toggelt den Status
	public function Toggle_Status()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->Set_Status(!GetValueBoolean($this->GetIDForIdent("Status")));
		}
	}
	    
	private function Get_Status()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_value", "Pin" => $this->ReadPropertyInteger("Pin") )));
		}
	}
}
?>
