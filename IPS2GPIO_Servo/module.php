<?
    // Klassendefinition
    class IPS2GPIO_Servo extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin", -1);
		$this->RegisterPropertyInteger("most_anti_clockwise", 500);
		$this->RegisterPropertyInteger("midpoint", 1500);
		$this->RegisterPropertyInteger("most_clockwise", 2500);
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
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der Microsekunden bei 50 Hz:"); 
		$arrayElements[] = array("name" => "most_anti_clockwise", "type" => "NumberSpinner",  "caption" => "Max. Links (ms)"); 
		$arrayElements[] = array("name" => "midpoint", "type" => "NumberSpinner",  "caption" => "Mittelstellung (ms)"); 
		$arrayElements[] = array("name" => "most_clockwise", "type" => "NumberSpinner",  "caption" => "Max. Rechts (ms)");
		$arrayElements[] = array("type" => "Label", "label" => "ACHTUNG: Falsche Werte können zur Beschädigung des Servo führen!");
		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
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
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("Output", "Ausgang", "~Intensity.100", 10);
		$this->EnableAction("Output");	
            	
		//ReceiveData-Filter setzen
                $Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);
		If (IPS_GetKernelRunlevel() == 10103) {
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->Setup();
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
	        case "Output":
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
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
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
		$this->SendDebug("SetOutput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Left = $this->ReadPropertyInteger("most_anti_clockwise");
			$Right = $this->ReadPropertyInteger("most_clockwise");
			
			$Value = intval(($Value * ($Right - $Left) / 100) + $Left);
			$this->SendDebug("SetOutput", "Errechneter Zielwert: ".$Value, 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $Value)));
			If (!$Result) {
				$this->SendDebug("SetOutput", "Fehler beim Positionieren!", 0);
			}
			IPS_Sleep(500);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => 0)));
			If (!$Result) {
				$this->SendDebug("SetOutput", "Fehler beim Ausschalten!", 0);
			}
		}
	}
	    
	private function Setup()
	{
		$this->SendDebug("Setup", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $this->ReadPropertyInteger("midpoint"))));
			If (!$Result) {
				$this->SendDebug("Setup", "Fehler beim Stellen der Mittelstellung!", 0);
			}
			SetValueInteger($this->GetIDForIdent("Output"), 50);
			IPS_Sleep(500);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => 0)));
			If (!$Result) {
				$this->SendDebug("Setup", "Fehler beim Ausschalten!", 0);
			}
		}
	}
	    
}
?>
