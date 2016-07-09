<?
    // Klassendefinition
    class IPS2GPIO_Input extends IPSModule 
    {
 
        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) 
        {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
 
            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
           
            $this->RegisterPropertyInteger("Pin", 2);
            $this->RegisterPropertyInteger("GlitchFilter", 10);
 	    $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");	
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
            
            //Connect to available splitter or create a new one
	    $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	   
	          //Status-Variablen anlegen
	    $this->RegisterVariableBoolean("Status", "Status", "~Switch", 1);
            $this->EnableAction("Status");
            $this->RegisterVariableBoolean("Toggle", "Toggle", "~Switch", 1);
            $this->EnableAction("Toggle");
           //$this->SetStatus(101);
           //$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "pin_possible", "DataID" => $this->DataID, "InstanzID" => $this->InstanzID, "Pin" => $this->ReadPropertyInteger("Pin"))));
           
            $this->Set_Mode();
        }
	
	
	
	public function ReceiveData($JSONString) 
	{
    	// Empfangene Daten vom Gateway/Splitter
    	$data = json_decode($JSONString);
    	IPS_LogMessage("ReceiveData_Input", utf8_decode($data->Buffer));
 	switch ($data->Function) {
		    case "pin_possible":
		        If ($data->InstanzID == $this->InstanzID) {
		        	If ($data->Result) {
		        		//$this->SetStatus(102);
		        		IPS_LogMessage("GPIO Auswahl: ","erfolgreich");
		        	}
		        	else {
		        		//$this->SetStatus(200);
		        		IPS_LogMessage("GPIO Auswahl: ","nicht erfolgreich!");
		        	}
		        				
		        	
		        }
		        break;
 	}
    	// Datenverarbeitung und schreiben der Werte in die Statusvariablen
    	//SetValue($this->GetIDForIdent("Value"), $data->Buffer);
	
 	return;
 	}
	// Beginn der Funktionen
	
	// Setzt den gewaehlten Pin in den Output-Modus
	private function Set_Mode()
	{
   		$this->SendDataToParent(json_encode(Array("DataID"=> "{EBFBEE2B-0F1A-470D-8B5B-97F977D605CA}", "Function" => "set_mode", "Pin" => $this->ReadPropertyInteger("Pin"), "Modus" => "R")));
   	return;
	}

	// Setzt den gewaehlten Pin in den Output-Modus
	private function Set_GlitchFilter()
	{
   		$this->SendDataToParent(json_encode(Array("DataID"=> "{EBFBEE2B-0F1A-470D-8B5B-97F977D605CA}", "Function" => "set_glitchfilter", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $this->ReadPropertyInteger("GlitchFilter"))));
   	return;
	}	

	

	
}
?>
