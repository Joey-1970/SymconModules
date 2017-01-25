<?
    // Klassendefinition
    class IPS2GPIO_Dimmer extends IPSModule 
    {
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
		 // Diese Zeile nicht löschen.
		 parent::Create();
		 $this->RegisterPropertyBoolean("Open", false);
		 $this->RegisterPropertyInteger("Pin", -1);
		 $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
        }
	
	/*
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
  		
		$arrayActions = array();
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}    
	*/
	    
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
		$Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
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
				If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				}
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
 	}
	// Beginn der Funktionen

	// Dimmt den gewaehlten Pin
	public function Set_Intensity(Int $value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$value = min(255, max(0, $value));
			If (GetValueBoolean($this->GetIDForIdent("Status")) == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $value)));
			}
			else {
				SetValueInteger($this->GetIDForIdent("Intensity"), $value);
			}
		}
	}
	
	// Schaltet den gewaehlten Pin
	public function Set_Status(Bool $value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			SetValueBoolean($this->GetIDForIdent("Status"), $value);

			If ($value == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => GetValueInteger($this->GetIDForIdent("Intensity")))));
			}
			else {
				$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => 0)));
			}
		}
	}
	
	// Toggelt den Status
	public function Toggle_Status()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->Set_Status(!GetValueBoolean($this->GetIDForIdent("Status")));
		}
	}

    }
?>
