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
		$this->SetBuffer("PreviousPin_R", -1);
            	$this->RegisterPropertyInteger("Pin_G", -1);
		$this->SetBuffer("PreviousPin_G", -1);
            	$this->RegisterPropertyInteger("Pin_B", -1);
		$this->SetBuffer("PreviousPin_B", -1);
		$this->RegisterPropertyInteger("Pin_W", -1);
		$this->SetBuffer("PreviousPin_W", -1);
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		$this->RegisterPropertyInteger("FadeIn", 0);
		$this->RegisterPropertyInteger("FadeOut", 0);
		$this->RegisterPropertyInteger("FadeScalar", 4);
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
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_R") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_R")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_R")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_R", "caption" => "GPIO-Nr. Rot", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_G") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_G")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_G")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_G", "caption" => "GPIO-Nr. Grün", "options" => $arrayOptions );

		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_B") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_B")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_B")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_B", "caption" => "GPIO-Nr.Blau", "options" => $arrayOptions );

		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_W") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_W")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_W")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_W", "caption" => "GPIO-Nr. Weiß", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Optional: Angabe von Fade-In/-Out-Zeit in Sekunden (0 => aus, max. 10 Sek)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeIn",  "caption" => "Fade-In-Zeit"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeOut",  "caption" => "Fade-Out-Zeit");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");


		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin_R") >= 0) AND ($this->ReadPropertyInteger("Pin_G") >= 0) AND ($this->ReadPropertyInteger("Pin_B") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$arrayActions[] = array("type" => "Button", "label" => "On", "onClick" => 'I2GRGBW_Set_Status($id, true);');
			$arrayActions[] = array("type" => "Button", "label" => "Off", "onClick" => 'I2GRGBW_Set_Status($id, false);');
			$arrayActions[] = array("type" => "Button", "label" => "Toggle", "onClick" => 'I2GRGBW_Toggle_Status($id);');
			$arrayActions[] = array("type" => "Label", "label" => "Rot");
			$arrayActions[] = array("type" => "HorizontalSlider", "name" => "SliderR", "minimum" => 0,  "maximum" => 255, "onChange" => 'I2GRGBW_Set_RGB($id, $SliderR, $SliderG, $SliderB);');
			$arrayActions[] = array("type" => "Label", "label" => "Grün");
			$arrayActions[] = array("type" => "HorizontalSlider", "name" => "SliderG", "minimum" => 0,  "maximum" => 255, "onChange" => 'I2GRGBW_Set_RGB($id, $SliderR, $SliderG, $SliderB);');
			$arrayActions[] = array("type" => "Label", "label" => "Blau");
			$arrayActions[] = array("type" => "HorizontalSlider", "name" => "SliderB", "minimum" => 0,  "maximum" => 255, "onChange" => 'I2GRGBW_Set_RGB($id, $SliderR, $SliderG, $SliderB);');
			$arrayActions[] = array("type" => "Label", "label" => "Weiß");
			$arrayActions[] = array("type" => "HorizontalSlider", "name" => "SliderW", "minimum" => 0,  "maximum" => 255, "onChange" => 'I2GRGBW_Set_White($id, $SliderW);');
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
		If ((intval($this->GetBuffer("PreviousPin_R")) <> $this->ReadPropertyInteger("Pin_R")) OR (intval($this->GetBuffer("PreviousPin_G")) <> $this->ReadPropertyInteger("Pin_G")) OR (intval($this->GetBuffer("PreviousPin_B")) <> $this->ReadPropertyInteger("Pin_B")) OR (intval($this->GetBuffer("PreviousPin_W")) <> $this->ReadPropertyInteger("Pin_W")) ) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel R - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_R")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_R"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel G - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_G")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_G"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel B - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_B")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_B"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel W - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_W")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_W"), 0);
		}
  	   
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
           	$this->EnableAction("Intensity_W");
           	$this->RegisterVariableInteger("Color", "Farbe", "~HexColor", 60);
           	$this->EnableAction("Color");
           	
          	//ReceiveData-Filter setzen
          	$Filter = '((.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin_R").'.*)|(.*"Pin":'.$this->ReadPropertyInteger("Pin_G").'.*|.*"Pin":'.$this->ReadPropertyInteger("Pin_B").'.*)|(.*"Pin":'.$this->ReadPropertyInteger("Pin_W").'.*))';
		$this->SetReceiveDataFilter($Filter);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {	
			If (($this->ReadPropertyInteger("Pin_R") >= 0) AND ($this->ReadPropertyInteger("Pin_G") >= 0) AND ($this->ReadPropertyInteger("Pin_B") >= 0) AND ($this->ReadPropertyInteger("Pin_W") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_R"), "PreviousPin" => $this->GetBuffer("PreviousPin_R"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_G"), "PreviousPin" => $this->GetBuffer("PreviousPin_G"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_B"), "PreviousPin" => $this->GetBuffer("PreviousPin_B"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => $this->ReadPropertyInteger("Pin_W"), "PreviousPin" => $this->GetBuffer("PreviousPin_W"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_R", $this->ReadPropertyInteger("Pin_R"));
				$this->SetBuffer("PreviousPin_G", $this->ReadPropertyInteger("Pin_G"));
				$this->SetBuffer("PreviousPin_B", $this->ReadPropertyInteger("Pin_B"));
				$this->SetBuffer("PreviousPin_W", $this->ReadPropertyInteger("Pin_W"));
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
				$this->ApplyChanges();			}
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
			If ($this->GetBuffer("Fade") == 0) {
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
			}
			break;
    		}
	}
	
	// Beginn der Funktionen
	// Dimmt den gewaehlten Pin
	public function Set_RGB(Int $R, Int $G, Int $B)
	{
 		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Set_RGB", "Ausfuehrung", 0);
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
			$this->SendDebug("Set_White", "Ausfuehrung", 0);
			$value = min(255, max(0, $value));
			If (GetValueBoolean($this->GetIDForIdent("Status")) == true) {
				$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_W"), "Value" => $value)));
			}
			else {
				SetValueInteger($this->GetIDForIdent("Intensity_W"), $value);
			}
		}
	}    
	
	private function FadeIn()
	{
		// RGBW beim Einschalten Faden
		$this->SendDebug("FadeIn", "Ausfuehrung", 0);
		$this->SetBuffer("Fade", 1);
		$Fadetime = $this->ReadPropertyInteger("FadeIn");
		$Fadetime = min(10, max(0, $Fadetime));
		
		If ($Fadetime > 0) {
			// Zielwert RGB bestimmen
			$Value_R = GetValueInteger($this->GetIDForIdent("Intensity_R"));
			$Value_G = GetValueInteger($this->GetIDForIdent("Intensity_G"));
			$Value_B = GetValueInteger($this->GetIDForIdent("Intensity_B"));
			$Value_W = GetValueInteger($this->GetIDForIdent("Intensity_W"));
			$Value_RGB = $Value_R + $Value_G + $Value_B;
			$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
			$Steps = $Fadetime * $FadeScalar;
			
			If (($Value_W == 0) AND ($Value_RGB == 0)) {
				$this->SendDebug("FadeIn", "RGB und W sind 0 -> keine Aktion", 0);
			}
			elseif (($Value_W > 0) AND ($Value_RGB == 0)) {
				$this->SendDebug("FadeIn", "RGB ist 0 -> W faden", 0);
				$Stepwide = $Value_W / $Steps;

				If ($Stepwide > 0) {
					// Fade In			
					for ($i = (0 + $Stepwide) ; $i <= ($l - $Stepwide); $i = $i + round($Stepwide, 2)) {
						$Starttime = microtime(true);
						If ($this->ReadPropertyBoolean("Open") == true) {
							// Ausgang setzen
							$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_W"), "Value" => $i)));
						}
						$Endtime = microtime(true);
						$Delay = intval(($Endtime - $Starttime) * 1000);
						$DelayMax = intval(1000 / $FadeScalar);
						$Delaytime = min($DelayMax, max(0, ($DelayMax - $Delay)));   
						IPS_Sleep($Delaytime);
					}
				}	
			}
			elseif (($Value_W == 0) AND ($Value_RGB > 0)) {
				$this->SendDebug("FadeIn", "W ist 0 -> RGB faden", 0);
				// Umrechnung in HSL
				list($h, $s, $l) = $this->rgbToHsl($Value_R, $Value_G, $Value_B);
				// $l muss von 0 auf den Zielwert gebracht werden
				$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
				$Steps = $Fadetime * $FadeScalar;
				$Stepwide = $l / $Steps;

				// Fade In			
				for ($i = (0 + $Stepwide) ; $i <= ($l - $Stepwide); $i = $i + round($Stepwide, 2)) {
					$Starttime = microtime(true);
					// $i muss jetzt als HSL-Wert wieder in RGB umgerechnet werden
					list($R, $G, $B) = $this->hslToRgb($h, $s, $i);
					$this->SendDebug("FadeIn", "L: ".$i, 0);

					If ($this->ReadPropertyBoolean("Open") == true) {
						// Ausgang setzen
						$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => $R, "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => $G, "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => $B)));
					}
					$Endtime = microtime(true);
					$Delay = intval(($Endtime - $Starttime) * 1000);
					$DelayMax = intval(1000 / $FadeScalar);
					$Delaytime = min($DelayMax, max(0, ($DelayMax - $Delay)));   
					IPS_Sleep($Delaytime);
				}	
			}
			elseif (($Value_W > 0) AND ($Value_RGB > 0)) {
				$this->SendDebug("FadeIn", "RGB und W sind > 0 -> RGBW faden", 0);
				// Umrechnung in HSL
				list($h, $s, $l) = $this->rgbToHsl($Value_R, $Value_G, $Value_B);
				// $l muss von 0 auf den Zielwert gebracht werden
				$Stepwide = $l / $Steps;
				$Stepwide_W = $Value_W / $Steps;
				$j = 1;
				// Fade In			
				for ($i = (0 + $Stepwide) ; $i <= ($l - $Stepwide); $i = $i + round($Stepwide, 2)) {
					$Starttime = microtime(true);
					// $i muss jetzt als HSL-Wert wieder in RGB umgerechnet werden
					list($R, $G, $B) = $this->hslToRgb($h, $s, $i);
					$Value_W = intval($j * $Stepwide_W);
					$j = $j + 1;
					$this->SendDebug("FadeIn", "L: ".$i." W: ".$Value_W, 0);

					If ($this->ReadPropertyBoolean("Open") == true) {
						// Ausgang setzen
						$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => $R, "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => $G, "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => $B)));
						$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_W"), "Value" => $Value_W)));
					}
					$Endtime = microtime(true);
					$Delay = intval(($Endtime - $Starttime) * 1000);
					$DelayMax = intval(1000 / $FadeScalar);
					$Delaytime = min($DelayMax, max(0, ($DelayMax - $Delay)));   
					IPS_Sleep($Delaytime);
				}
				
			}	
		}
		$this->SetBuffer("Fade", 0);
	}
	
	private function FadeOut()
	{
		// RGBW beim Ausschalten Faden
		$this->SendDebug("FadeOut", "Ausfuehrung", 0);
		$this->SetBuffer("Fade", 1);
		$Fadetime = $this->ReadPropertyInteger("FadeIn");
		$Fadetime = min(10, max(0, $Fadetime));
		
		If ($Fadetime > 0) {
			// Zielwert RGB bestimmen
			$Value_R = GetValueInteger($this->GetIDForIdent("Intensity_R"));
			$Value_G = GetValueInteger($this->GetIDForIdent("Intensity_G"));
			$Value_B = GetValueInteger($this->GetIDForIdent("Intensity_B"));
			$Value_W = GetValueInteger($this->GetIDForIdent("Intensity_W"));
			$Value_RGB = $Value_R + $Value_G + $Value_B;
			$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
			$Steps = $Fadetime * $FadeScalar;
			
			If (($Value_W == 0) AND ($Value_RGB == 0)) {
				$this->SendDebug("FadeOut", "RGB und W sind 0 -> keine Aktion", 0);
			}
			elseif (($Value_W > 0) AND ($Value_RGB == 0)) {
				$this->SendDebug("FadeOut", "RGB ist 0 -> W faden", 0);
				$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
				$Steps = $Fadetime * $FadeScalar;
				$Stepwide = $Value_W / $Steps;

				If ($Stepwide > 0) {
					// Fade Out
					for ($i = ($l - $Stepwide) ; $i >= (0 + $Stepwide); $i = $i - round($Stepwide, 2)) {
						$Starttime = microtime(true);

						If ($this->ReadPropertyBoolean("Open") == true) {
							// Ausgang setzen
							$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_W"), "Value" => $i)));
						}
						$Endtime = microtime(true);
						$Delay = intval(($Endtime - $Starttime) * 1000);
						$DelayMax = intval(1000 / $FadeScalar);
						$Delaytime = min($DelayMax, max(0, ($DelayMax - $Delay)));   
						IPS_Sleep($Delaytime);
					}
				}	
			}
			elseif (($Value_W == 0) AND ($Value_RGB > 0)) {
				$this->SendDebug("FadeOut", "W ist 0 -> RGB faden", 0);
				// Umrechnung in HSL
				list($h, $s, $l) = $this->rgbToHsl($Value_R, $Value_G, $Value_B);
				// $l muss von 0 auf den Zielwert gebracht werden
				$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
				$Steps = $Fadetime * $FadeScalar;
				$Stepwide = $l / $Steps;

				// Fade Out
				for ($i = ($l - $Stepwide) ; $i >= (0 + $Stepwide); $i = $i - round($Stepwide, 2)) {
					$Starttime = microtime(true);
					//$this->SendDebug("RGBFadeOut", "Startzeit: ".$Starttime, 0);
					// $i muss jetzt als HSL-Wert wieder in RGB umgerechnet werden
					list($R, $G, $B) = $this->hslToRgb($h, $s, $i);
					$this->SendDebug("FadeOut", "L: ".$i, 0);
					If ($this->ReadPropertyBoolean("Open") == true) {
						// Ausgang setzen
						$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => $R, "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => $G, "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => $B)));
					}
					$Endtime = microtime(true);
					$Delay = intval(($Endtime - $Starttime) * 1000);
					$DelayMax = intval(1000 / $FadeScalar);
					$Delaytime = min($DelayMax, max(0, ($DelayMax - $Delay)));   
					IPS_Sleep($Delaytime);
				}	
			}
			elseif (($Value_W > 0) AND ($Value_RGB > 0)) {
				$this->SendDebug("FadeOut", "RGB und W sind > 0 -> RGBW faden", 0);
				// Umrechnung in HSL
				list($h, $s, $l) = $this->rgbToHsl($Value_R, $Value_G, $Value_B);
				// $l muss von 0 auf den Zielwert gebracht werden
				$Stepwide = $l / $Steps;
				// Fade Out
				$Stepwide_W = $Value_W / $Steps;
				$j = $Steps - 1;
				for ($i = ($l - $Stepwide) ; $i >= (0 + $Stepwide); $i = $i - round($Stepwide, 2)) {
					$Starttime = microtime(true);
					// $i muss jetzt als HSL-Wert wieder in RGB umgerechnet werden
					list($R, $G, $B) = $this->hslToRgb($h, $s, $i);
					$Value_W = intval($j * $Stepwide_W);
					$j = $j - 1;
					$this->SendDebug("FadeOut", "L: ".$i." W: ".$Value_W, 0);

					If ($this->ReadPropertyBoolean("Open") == true) {
						// Ausgang setzen
						$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => $R, "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => $G, "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => $B)));
						$this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_W"), "Value" => $Value_W)));
					}
					$Endtime = microtime(true);
					$Delay = intval(($Endtime - $Starttime) * 1000);
					$DelayMax = intval(1000 / $FadeScalar);
					$Delaytime = min($DelayMax, max(0, ($DelayMax - $Delay)));   
					IPS_Sleep($Delaytime);
				}
			}		
		}
		$this->SetBuffer("Fade", 0);
	}  
	        
	public function Set_Status(Bool $value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Set_Status", "Ausfuehrung", 0);
			SetValueBoolean($this->GetIDForIdent("Status"), $value);
			$FadeInTime = $this->ReadPropertyInteger("FadeIn");
			$FadeOutTime = $this->ReadPropertyInteger("FadeOut");
			If ($value == true) {
				If ($FadeInTime > 0) {
					$this->FadeIn();
				}
				$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => GetValueInteger($this->GetIDForIdent("Intensity_R")), "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => GetValueInteger($this->GetIDForIdent("Intensity_G")), "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => GetValueInteger($this->GetIDForIdent("Intensity_B")))));
				$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_W"), "Value" => GetValueInteger($this->GetIDForIdent("Intensity_W")))));
			}
			else {
				If ($FadeOutTime > 0) {
					$this->FadeOut();
				}
				$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle_RGB", "Pin_R" => $this->ReadPropertyInteger("Pin_R"), "Value_R" => 0, "Pin_G" => $this->ReadPropertyInteger("Pin_G"), "Value_G" => 0, "Pin_B" => $this->ReadPropertyInteger("Pin_B"), "Value_B" => 0)));
				$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin_W"), "Value" => 0)));
			}
		}
	}
	
	// Toggelt den Status
	public function Toggle_Status()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Toggle_Status", "Ausfuehrung", 0);
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
	
	private function rgbToHsl(int $r, int $g, int $b) 
	{
		$r = $r / 255;
		$g = $g / 255;
		$b = $b / 255;
	    	$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$h;
		$s;
		$l = ($max + $min) / 2;
		$d = $max - $min;
		if( $d == 0 ){
			$h = $s = 0; // achromatic
		} else {
			$s = $d / (1 - abs(2 * $l - 1));
			switch( $max ){
			    case $r:
				$h = 60 * fmod((($g - $b) / $d), 6); 
				if ($b > $g) {
				    $h += 360;
				}
				break;
			    case $g: 
				$h = 60 * (($b - $r) / $d + 2); 
				break;
			    case $b: 
				$h = 60 * (($r - $g ) / $d + 4); 
				break;
			}			        	        
		}
	return array(round($h, 2), round($s, 2), round($l, 2));
	} 
	
	private function hslToRgb($h, $s, $l)
	{
	    	$r; 
	    	$g; 
	    	$b;
		$c = (1 - abs(2 * $l - 1)) * $s;
		$x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
		$m = $l - ($c / 2);
		if ($h < 60) {
			$r = $c;
			$g = $x;
			$b = 0;
		} else if ($h < 120) {
			$r = $x;
			$g = $c;
			$b = 0;			
		} else if ($h < 180) {
			$r = 0;
			$g = $c;
			$b = $x;					
		} else if ($h < 240) {
			$r = 0;
			$g = $x;
			$b = $c;
		} else if ($h < 300) {
			$r = $x;
			$g = 0;
			$b = $c;
		} else {
			$r = $c;
			$g = 0;
			$b = $x;
		}
		$r = ($r + $m) * 255;
		$g = ($g + $m) * 255;
		$b = ($b + $m ) * 255;
		
	return array(floor($r), floor($g), floor($b));
	}    
	    
	private function Get_GPIO()
	{
		If ($this->HasActiveParent() == true) {
			$GPIO = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_GPIO")));
		}
		else {
			$AllGPIO = array();
			$AllGPIO[-1] = "undefiniert";
			for ($i = 2; $i <= 27; $i++) {
				$AllGPIO[$i] = "GPIO".(sprintf("%'.02d", $i));
			}
			$GPIO = serialize($AllGPIO);
		}
	return $GPIO;
	}    
	    
	private function HasActiveParent()
    	{
		$Instance = @IPS_GetInstance($this->InstanceID);
		if ($Instance['ConnectionID'] > 0)
		{
			$Parent = IPS_GetInstance($Instance['ConnectionID']);
			if ($Parent['InstanceStatus'] == 102)
			return true;
		}
        return false;
    	}  
}
?>
