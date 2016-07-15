<?
    // Klassendefinition
    class IPS2GPIO_Dimmer extends IPSModule 
    {
        public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
        }

        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
            $this->SetStatus(101);
            $this->RegisterPropertyInteger("Pin", -1);
 	    $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    $this->SetStatus(104);
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
           $this->RegisterVariableInteger("Intensity", "Intensity", "~Intensity.255");
           $this->EnableAction("Intensity");
           If ($this->ReadPropertyInteger("Pin") >= 0) {
           	$this->Set_Mode();
           	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
           }	
        }
	
	public function RequestAction($Ident, $Value) 
	{
  		SetValueString(47271, $Ident." ".$Value);
  		switch($Ident) {
	        case "Status":
	            $this->Set_Status($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValue($this->GetIDForIdent($Ident), $Value);
	            break;
	        case "Intensity":
	            $this->Set_Intensity($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValue($this->GetIDForIdent($Ident), $Value);
	            break;
	        default:
	            throw new Exception("Invalid Ident");
	    }
	}
	
	public function ReceiveData($JSONString) 
	{
    	// Empfangene Daten vom Gateway/Splitter
    	$data = json_decode($JSONString);
    	//IPS_LogMessage("ReceiveData_Dimmer", utf8_decode($data->Buffer));
 	switch ($data->Function) {
		   case "get_usedpin":
		   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin"))));
		   	break;
		   case "status":
			If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   	$this->SetStatus($data->Status);
			}
			break;
		   case "freepin":
			   // Funktion zum erstellen dynamischer Pulldown-Menüs
			   break;
 	}

 	return;
 	}
	// Beginn der Funktionen
	
	// Setzt den gewaehlten Pin in den Output-Modus
	private function Set_Mode()
	{
   		$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_mode", "Pin" => $this->ReadPropertyInteger("Pin"), "Modus" => "W")));
   	return;
	}
	
	// Dimmt den gewaehlten Pin
	public function Set_Intensity($value)
	{
   		SetValueInteger($this->GetIDForIdent("Intensity"), $value);
		
		If (GetValueBoolean($this->GetIDForIdent("Status")) == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $value)));
 		}
	return;
	}
	
	// Schaltet den gewaehlten Pin
	public function Set_Status($value)
	{
		SetValueBoolean($this->GetIDForIdent("Status"), $value);
		
		If ($value == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => GetValueInteger($this->GetIDForIdent("Intensity")))));
		}
		else {
   			$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => 0)));
		}	
	}
	
	// Toggelt den Status
	public function Toggle_Status()
	{
		$this->Set_Status(!GetValueBoolean($this->GetIDForIdent("Status")));
	return;
	}

    }
?>
