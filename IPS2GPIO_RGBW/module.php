<?
   // Klassendefinition
    class IPS2GPIO_RGBW extends IPSModule 
    {
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin_R", -1);
            	$this->RegisterPropertyInteger("Pin_G", -1);
            	$this->RegisterPropertyInteger("Pin_B", -1);
		$this->RegisterPropertyInteger("Pin_W", -1);
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
		$arrayElements[] = array("type" => "Select", "name" => "Pin_R", "caption" => "GPIO-Nr. Rot", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Select", "name" => "Pin_G", "caption" => "GPIO-Nr. Grün", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Select", "name" => "Pin_B", "caption" => "GPIO-Nr. Blau", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Select", "name" => "Pin_W", "caption" => "GPIO-Nr. Blau", "options" => $arrayOptions );
		
		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin_R") >= 0) AND ($this->ReadPropertyInteger("Pin_G") >= 0) AND ($this->ReadPropertyInteger("Pin_B") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$arrayActions[] = array("type" => "Button", "label" => "On", "onClick" => 'I2GRGB_Set_Status($id, true);');
			$arrayActions[] = array("type" => "Button", "label" => "Off", "onClick" => 'I2GRGB_Set_Status($id, false);');
			$arrayActions[] = array("type" => "Button", "label" => "Toggle", "onClick" => 'I2GRGB_Toggle_Status($id);');
			$arrayActions[] = array("type" => "Label", "label" => "Rot");
			$arrayActions[] = array("type" => "HorizontalSlider", "name" => "SliderR", "minimum" => 0,  "maximum" => 255, "onChange" => 'I2GRGB_Set_RGB($id, $SliderR, $SliderG, $SliderB);');
			$arrayActions[] = array("type" => "Label", "label" => "Grün");
			$arrayActions[] = array("type" => "HorizontalSlider", "name" => "SliderG", "minimum" => 0,  "maximum" => 255, "onChange" => 'I2GRGB_Set_RGB($id, $SliderR, $SliderG, $SliderB);');
			$arrayActions[] = array("type" => "Label", "label" => "Blau");
			$arrayActions[] = array("type" => "HorizontalSlider", "name" => "SliderB", "minimum" => 0,  "maximum" => 255, "onChange" => 'I2GRGB_Set_RGB($id, $SliderR, $SliderG, $SliderB);');
			$arrayActions[] = array("type" => "Label", "label" => "Weiß");
			$arrayActions[] = array("type" => "HorizontalSlider", "name" => "SliderW", "minimum" => 0,  "maximum" => 255, "onChange" => 'I2GRGB_Set_White($id, $SliderW);');
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
           	$this->RegisterVariableInteger("Intensity_R", "Intensity Rot", "~Intensity.255",20);
           	$this->EnableAction("Intensity_R");
           	$this->RegisterVariableInteger("Intensity_G", "Intensity Grün", "~Intensity.255", 30);
           	$this->EnableAction("Intensity_G");
           	$this->RegisterVariableInteger("Intensity_B", "Intensity Blau", "~Intensity.255", 40);
           	$this->EnableAction("Intensity_B");
		$this->RegisterVariableInteger("Intensity_W", "Intensity Weiß", "~Intensity.255", 50);
           	$this->EnableAction("Intensity_B");
           	$this->RegisterVariableInteger("Color", "Farbe", "~HexColor", 60);
           	$this->EnableAction("Color");
           	
          	//ReceiveData-Filter setzen
          	$Filter = '((.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin_R").'.*)|(.*"Pin":'.$this->ReadPropertyInteger("Pin_G").'.*|.*"Pin":'.$this->ReadPropertyInteger("Pin_B").'.*))';
		$this->SetReceiveDataFilter($Filter);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			If (($this->ReadPropertyInteger("Pin_R") >= 0) AND ($this->ReadPropertyInteger("Pin_G") >= 0) AND ($this->ReadPropertyInteger("Pin_B") >= 0) AND ($this->ReadPropertyInteger("Pin_W") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_R"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_G"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_B"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_W"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
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
	            SetValueBoolean($this->GetIDForIdent($Ident), $Value);
	            break;
	        case "Intensity_R":
	            $this->Set_RGB($Value, GetValueInteger($this->GetIDForIdent("Intensity_G")), GetValueInteger($this->GetIDForIdent("Intensity_B")));
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            SetValueInteger($this->GetIDForIdent("Color"), $this->RGB2Hex(GetValueInteger($this->GetIDForIdent("Intensity_R")), GetValueInteger($this->GetIDForIdent("Intensity_G")), GetValueInteger($this->GetIDForIdent("Intensity_B"))));
	            break;
	        case "Intensity_G":
	            $this->Set_RGB(GetValueInteger($this->GetIDForIdent("Intensity_R")), $Value, GetValueInteger($this->GetIDForIdent("Intensity_B")));
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            SetValueInteger($this->GetIDForIdent("Color"), $this->RGB2Hex(GetValueInteger($this->GetIDForIdent("Intensity_R")), GetValueInteger($this->GetIDForIdent("Intensity_G")), GetValueInteger($this->GetIDForIdent("Intensity_B"))));
	            break;
	        case "Intensity_B":
	            $this->Set_RGB(GetValueInteger($this->GetIDForIdent("Intensity_R")), GetValueInteger($this->GetIDForIdent("Intensity_G")), $Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            SetValueInteger($this->GetIDForIdent("Color"), $this->RGB2Hex(GetValueInteger($this->GetIDForIdent("Intensity_R")), GetValueInteger($this->GetIDForIdent("Intensity_G")), GetValueInteger($this->GetIDForIdent("Intensity_B"))));
	            break;
		case "Intensity_W":
	            $this->Set_White($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
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
		   	If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_R"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_G"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_B"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
			}
			break;
		case "status":
			If (($data->Pin == $this->ReadPropertyInteger("Pin_R")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_G")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_B")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_W")) ) {
			   	$this->SetStatus($data->Status);
			}
			break;
		case "freepin":
			// Funktion zum erstellen dynamischer Pulldown-Menüs
			break;
		case "result":
			If (($data->Pin == $this->ReadPropertyInteger("Pin_R")) AND (GetValueBoolean($this->GetIDForIdent("Status")) == true)){
			   	SetValueInteger($this->GetIDForIdent("Intensity_R"), $data->Value);
			}
			ElseIf (($data->Pin == $this->ReadPropertyInteger("Pin_G")) AND (GetValueBoolean($this->GetIDForIdent("Status")) == true)){
			   	SetValueInteger($this->GetIDForIdent("Intensity_G"), $data->Value);
			}
			If (($data->Pin == $this->ReadPropertyInteger("Pin_B")) AND (GetValueBoolean($this->GetIDForIdent("Status")) == true)){
			   	SetValueInteger($this->GetIDForIdent("Intensity_B"), $data->Value);
			}
			If (($data->Pin == $this->ReadPropertyInteger("Pin_W")) AND (GetValueBoolean($this->GetIDForIdent("Status")) == true)){
				SetValueInteger($this->GetIDForIdent("Intensity_W"), $data->Value);
			}
			break;
    		}
	}
	
	// Beginn der Funktionen
	// Dimmt den gewaehlten Pin
	public function Set_RGB(Int $R, Int $G, Int $B)
	{
 		If ($this->ReadPropertyBoolean("Open") == true) {
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
		}
	}
	
	public function Set_White(Int $value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$value = min(255, max(0, $value));
			If (GetValueBoolean($this->GetIDForIdent("Status")) == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $value)));
			}
			else {
				SetValueInteger($this->GetIDForIdent("Intensity_W"), $value);
			}
		}
	}    
	    
	public function Set_Status(Bool $value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			SetValue($this->GetIDForIdent("Status"), $value);
			If ($value == true) {
				$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => GetValueInteger($this->GetIDForIdent("Intensity_R")), "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => GetValueInteger($this->GetIDForIdent("Intensity_G")), "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => GetValueInteger($this->GetIDForIdent("Intensity_B"))))); 
			}
			else {
				$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => 0, "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => 0, "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => 0))); 
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
