<?
    // Klassendefinition
    class IPS2GPIO_Dimmer extends IPSModule 
    {
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
            $this->RegisterPropertyInteger("Pin", -1);
 	    $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
        }

        // Überschreibt die interne IPS_Destroy($id) Funktion
        public function Destroy() {
            // Diese Zeile nicht löschen.
            parent::Destroy();
            $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "gpio_destroy", "Pin" => $this->ReadPropertyInteger("Pin"))));
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
	        // Diese Zeile nicht löschen
	        parent::ApplyChanges();
	        //Connect to available splitter or create a new one
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		   
		//Status-Variablen anlegen
		$this->RegisterVariableBoolean("Status", "Status", "~Switch", 10);
	        $this->EnableAction("Status");
	        $this->RegisterVariableInteger("Intensity", "Intensity", "~Intensity.255", 20);
	        $this->EnableAction("Intensity");
           
           	//ReceiveData-Filter setzen
		$Filter = '.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*';
		$this->SetReceiveDataFilter($Filter);
		
	        If ($this->ReadPropertyInteger("Pin") >= 0) {
	        $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
	        }	
        }
	
	public function RequestAction($Ident, $Value) 
	{
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
		   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin"), "Modus" => 1)));
		   	break;
		case "status":
			If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   	$this->SetStatus($data->Status);
			}
			break;
		case "freepin":
			   // Funktion zum erstellen dynamischer Pulldown-Menüs
			   break;
		case "result":
			If (($data->Pin == $this->ReadPropertyInteger("Pin")) AND (GetValueBoolean($this->GetIDForIdent("Status")) == true)){
			   	SetValueInteger($this->GetIDForIdent("Intensity"), $data->Value);
			}
			break;
 	}

 	return;
 	}
	// Beginn der Funktionen

	// Dimmt den gewaehlten Pin
	public function Set_Intensity($value)
	{
		$value = min(255, max(0, $value));
		If (GetValueBoolean($this->GetIDForIdent("Status")) == true) {
			$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $value)));
 		}
 		else {
 			SetValueInteger($this->GetIDForIdent("Intensity"), $value);
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
