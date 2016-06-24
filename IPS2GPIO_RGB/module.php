<?
   // Klassendefinition
    class IPS2GPIO_RGB extends IPSModule 
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
           
            $this->RegisterPropertyInteger("Pin_R", 2);
            $this->RegisterPropertyInteger("Pin_G", 3);
            $this->RegisterPropertyInteger("Pin_B", 4);
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
           	$this->RegisterVariableInteger("Intensity_R", "Intensity Rot", "~Intensity.255");
           	$this->EnableAction("Intensity_R");
           	$this->RegisterVariableInteger("Intensity_G", "Intensity Grün", "~Intensity.255");
           	$this->EnableAction("Intensity_G");
           	$this->RegisterVariableInteger("Intensity_B", "Intensity Blau", "~Intensity.255");
           	$this->EnableAction("Intensity_B");
           	$this->RegisterVariableInteger("Color", "Farbe", "~HexColor");
           	$this->EnableAction("Color");
           	$this->Set_Mode_RGB();
        }
	// Beginn der Funktionen
	
	// Setzt den gewaehlten Pins in den Output-Modus
	private function Set_Mode_RGB()
	{
   		$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_mode", "Pin" => $this->ReadPropertyInteger("Pin_R"), "Modus" => "W"))); 
 		$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_mode", "Pin" => $this->ReadPropertyInteger("Pin_G"), "Modus" => "W")));
 		$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_mode", "Pin" => $this->ReadPropertyInteger("Pin_B"), "Modus" => "W"))); 
	return $result;
	}
	
	// Dimmt den gewaehlten Pin
	public function Set_RGB($R, $G, $B)
	{
 		SetValue($this->GetIDForIdent("Intensity_R"), $R);
		SetValue($this->GetIDForIdent("Intensity_G"), $G);
		SetValue($this->GetIDForIdent("Intensity_B"), $B);
 		 
 		If (GetValueBoolean($this->GetIDForIdent("Status")) == true) { 
 			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_R"), "Value" => $value))); 
   			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_G"), "Value" => $value)));
   			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_B"), "Value" => $value)));
	return $result;
	}
	
	public function Set_Status($value)
	{
		SetValue($this->GetIDForIdent("Status"), $value);
		
		If ($value == true)
		{
			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_R"), "Value" => GetValueInteger($this->GetIDForIdent("Intensity_R"))))); 
   			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_G"), "Value" => GetValueInteger($this->GetIDForIdent("Intensity_G")))));
   			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_B"), "Value" => GetValueInteger($this->GetIDForIdent("Intensity_B")))));	
		}
		else
		{
			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_R"), "Value" => 0))); 
   			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_G"), "Value" => 0)));
   			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_B"), "Value" => 0)));	
		}
	}
	
	
	private function Hex2RGB($Hex)
	{
		$r = (($Hex >> 16) & 0xFF);
		$g = (($Hex >> 8) & 0xFF);
		$b = (($Hex >> 0) & 0xFF);	
	return array($r, $g, $b);
	}
	
	private function RGB2Hex($r, $g, $b)
	{
		$Hex = hexdec(str_pad(dechex($r), 2,'0', STR_PAD_LEFT).str_pad(dechex($g), 2,'0', STR_PAD_LEFT).str_pad(dechex($b), 2,'0', STR_PAD_LEFT));
	
	return $Hex;
	}
	


    }
?>
