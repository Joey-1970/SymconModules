<?
    // Klassendefinition
    class IPS2GPIO_Dimmer extends IPSModule 
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
           $this->RegisterVariableInteger("Intensity", "Intensity", "~Intensity.255");
           $this->EnableAction("Intensity");
           $this->Set_Mode();
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
    	IPS_LogMessage("ReceiveData", utf8_decode($data->Buffer));
 
    	// Datenverarbeitung und schreiben der Werte in die Statusvariablen
    	//SetValue($this->GetIDForIdent("Value"), $data->Buffer);
	}
	// Beginn der Funktionen
	
	// Setzt den gewaehlten Pin in den Output-Modus
	private function Set_Mode()
	{
   		$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_mode", "Pin" => $this->ReadPropertyInteger("Pin"), "Modus" => "W")));
   	return;
	}
	
	// Dimmt den gewaehlten Pin
	public function Set_Intensity($value)
	{
   		SetValueInteger($this->GetIDForIdent("Intensity"), $value);
		
		If (GetValueBoolean($this->GetIDForIdent("Status")) == true) {
			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $value)));
 		}
	return;
	}
	
	// Schaltet den gewaehlten Pin
	public function Set_Status($value)
	{
		SetValueBoolean($this->GetIDForIdent("Status"), $value);
		
		If ($value == true) {
			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => GetValueInteger($this->GetIDForIdent("Intensity")))));
		}
		else {
   			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => 0)));
		}	
	}
	

    }
?>
