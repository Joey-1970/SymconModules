<?
   // Klassendefinition
    class IPS2GPIO_RGB extends IPSModule 
    {
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
            $this->RegisterPropertyInteger("Pin_R", -1);
            $this->RegisterPropertyInteger("Pin_G", -1);
            $this->RegisterPropertyInteger("Pin_B", -1);
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
	        $this->RegisterVariableBoolean("Status", "Status", "~Switch", 10);
           	$this->EnableAction("Status");
           	$this->RegisterVariableInteger("Intensity_R", "Intensity Rot", "~Intensity.255",20);
           	$this->EnableAction("Intensity_R");
           	$this->RegisterVariableInteger("Intensity_G", "Intensity Grün", "~Intensity.255", 30);
           	$this->EnableAction("Intensity_G");
           	$this->RegisterVariableInteger("Intensity_B", "Intensity Blau", "~Intensity.255", 40);
           	$this->EnableAction("Intensity_B");
           	$this->RegisterVariableInteger("Color", "Farbe", "~HexColor", 50);
           	$this->EnableAction("Color");
           	If (($this->ReadPropertyInteger("Pin_R") >= 0) AND ($this->ReadPropertyInteger("Pin_G") >= 0) AND ($this->ReadPropertyInteger("Pin_B") >= 0)) {
           		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_pinupdate")));
           	}
        }
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Status":
	            $this->Set_Status($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueBoolean($this->GetIDForIdent($Ident), $Value);
	            break;
	        case "Intensity_R":
	            $this->Set_RGB($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            SetValueInteger($this->GetIDForIdent("Color"), $this->RGB2Hex(GetValue($this->GetIDForIdent(Intensity_R)), GetValue($this->GetIDForIdent(Intensity_G)), GetValue($this->GetIDForIdent(Intensity_B))));
	            break;
	        case "Intensity_G":
	            $this->Set_RGB($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            SetValueInteger($this->GetIDForIdent("Color"), $this->RGB2Hex(GetValue($this->GetIDForIdent(Intensity_R)), GetValue($this->GetIDForIdent(Intensity_G)), GetValue($this->GetIDForIdent(Intensity_B))));
	            break;
	        case "Intensity_B":
	            $this->Set_RGB($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            SetValueInteger($this->GetIDForIdent("Color"), $this->RGB2Hex(GetValue($this->GetIDForIdent(Intensity_R)), GetValue($this->GetIDForIdent(Intensity_G)), GetValue($this->GetIDForIdent(Intensity_B))));
	            break;
	        case "Color":
	            list($r, $g, $b) = $this->Hex2RGB($Value);
	            $this->Set_RGB($r, $g, $b);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            SetValueInteger($this->GetIDForIdent("Intensity_R"), intval($r));
	            SetValueInteger($this->GetIDForIdent("Intensity_G"), intval($g));
	            SetValueInteger($this->GetIDForIdent("Intensity_B"), intval($b));
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
		   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_R"), "Modus" => "W")));
		   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_G"), "Modus" => "W")));
		   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_B"), "Modus" => "W")));
		   	break;
		case "status":
			If (($data->Pin == $this->ReadPropertyInteger("Pin_R")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_G")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_B"))) {
			   	$this->SetStatus($data->Status);
			}
			break;
		case "freepin":
			// Funktion zum erstellen dynamischer Pulldown-Menüs
			break;
		case "result":
			If (($data->Pin == $this->ReadPropertyInteger("Pin_R")) AND (GetValueBoolean($this->GetIDForIdent("Status")) == true)){
			   	$this->SetValueInteger($this->GetIDForIdent("Intensity_R"), $data->Value);
			}
			ElseIf (($data->Pin == $this->ReadPropertyInteger("Pin_G")) AND (GetValueBoolean($this->GetIDForIdent("Status")) == true)){
			   	$this->SetValueInteger($this->GetIDForIdent("Intensity_G"), $data->Value);
			}
			If (($data->Pin == $this->ReadPropertyInteger("Pin_B")) AND (GetValueBoolean($this->GetIDForIdent("Status")) == true)){
			   	$this->SetValueInteger($this->GetIDForIdent("Intensity_B"), $data->Value);
			}
			break;
    		}
    	return;
	}
	
	// Beginn der Funktionen

	// Dimmt den gewaehlten Pin
	public function Set_RGB($R, $G, $B)
	{
 		$R = min(255, max(0, $R));
 		$G = min(255, max(0, $G));
 		$B = min(255, max(0, $B));
 		If (GetValueBoolean($this->GetIDForIdent("Status")) == true) { 
 			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => $R, "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => $G, "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => $B))); 
		}
		else {
			SetValueInteger($this->GetIDForIdent("Intensity_R"), $R);
			SetValueInteger($this->GetIDForIdent("Intensity_G"), $G);
			SetValueInteger($this->GetIDForIdent("Intensity_B"), $B);	
		}
	return;
	}
	
	public function Set_Status($value)
	{
		SetValue($this->GetIDForIdent("Status"), $value);
		
		If ($value == true) {
			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => GetValueInteger($this->GetIDForIdent("Intensity_R")), "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => GetValueInteger($this->GetIDForIdent("Intensity_G")), "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => GetValueInteger($this->GetIDForIdent("Intensity_B"))))); 
		}
		else {
			$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => 0, "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => 0, "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => 0))); 
		}
	return;
	}
	
	// Toggelt den Status
	public function Toggle_Status()
	{
		$this->Set_Status(!GetValueBoolean($this->GetIDForIdent("Status")));	
	return;
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
