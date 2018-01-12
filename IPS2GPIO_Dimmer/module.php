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
		$this->SetBuffer("PreviousPin", -1);
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
		If ($this->ReadPropertyInteger("Pin") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		
		$arrayElements[] = array("type" => "Select", "name" => "Pin", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Optional: Angabe von Fade-In/-Out-Zeit in Sekunden (0 => aus, max. 10 Sek)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeIn",  "caption" => "Fade-In-Zeit"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeOut",  "caption" => "Fade-Out-Zeit");
		$arrayElements[] = array("type" => "Label", "label" => "Schritte pro Sekunde: (1 - 20)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeScalar",  "caption" => "Schritte"); 

		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		
		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$arrayActions[] = array("type" => "Button", "label" => "On", "onClick" => 'I2GDMR_Set_Status($id, true);');
			$arrayActions[] = array("type" => "Button", "label" => "Off", "onClick" => 'I2GDMR_Set_Status($id, false);');
			$arrayActions[] = array("type" => "Button", "label" => "Toggle", "onClick" => 'I2GDMR_Toggle_Status($id);');
			$arrayActions[] = array("type" => "Label", "label" => "Dimmen");
			$arrayActions[] = array("type" => "HorizontalSlider", "name" => "Slider", "minimum" => 0,  "maximum" => 255, "onChange" => 'I2GDMR_Set_Intensity($id, $Slider);');
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
		If (intval($this->GetBuffer("PreviousPin")) <> $this->ReadPropertyInteger("Pin")) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin"), 0);
		}
		
		//Status-Variablen anlegen
		$this->RegisterVariableBoolean("Status", "Status", "~Switch", 10);
	        $this->EnableAction("Status");
	        $this->RegisterVariableInteger("Intensity", "Intensity", "~Intensity.255", 20);
	        $this->EnableAction("Intensity");
           
           	//ReceiveData-Filter setzen
		$Filter = '(.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*)';
		$this->SetReceiveDataFilter($Filter);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin", $this->ReadPropertyInteger("Pin"));
				If ($Result == true) {
					$this->SetStatus(102);
				}
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
	            break;
	        case "Intensity":
	            $this->Set_Intensity($Value);
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
					$this->ApplyChanges();
				}
				break;
			case "status":
				If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
					$this->SetStatus($data->Status);
				}
				break;
		}
 	}
	// Beginn der Funktionen

	// Dimmt den gewaehlten Pin
	public function Set_Intensity(Int $value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Set_Intensity", "Ausfuehrung", 0);
			$value = min(255, max(0, $value));
			If (GetValueBoolean($this->GetIDForIdent("Status")) == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $value)));
				If (!$Result) {
					$this->SendDebug("Set_Intensity", "Fehler beim Schreiben des Wertes!", 0);
					return;
				}
				else {
					SetValueInteger($this->GetIDForIdent("Intensity"), $value);
				}
			}
			else {
				SetValueInteger($this->GetIDForIdent("Intensity"), $value);
			}
		}
	}
	
	private function FadeIn()
	{
		// beim Einschalten Faden
		$this->SendDebug("FadeIn", "Ausfuehrung", 0);
		$this->SetBuffer("Fade", 1);
		$Fadetime = $this->ReadPropertyInteger("FadeIn");
		$Fadetime = min(10, max(0, $Fadetime));
		If ($Fadetime > 0) {
			// Zielwert bestimmen
			$Value = GetValueInteger($this->GetIDForIdent("Intensity"));
	
			$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
			$FadeScalar = min(20, max(1, $FadeScalar));
			$Steps = $Fadetime * $FadeScalar;
			$Stepwide = $Value / $Steps;
			
			If ($Value <= 3) {
				$this->SendDebug("FadeIn", "W ist 0 -> keine Aktion", 0);
			}
			elseif ($Value > 3) {
				// Fade In			
				for ($i = (0 + $Stepwide) ; $i <= ($l - $Stepwide); $i = $i + round($Stepwide, 2)) {
					$Starttime = microtime(true);

					If ($this->ReadPropertyBoolean("Open") == true) {
						// Ausgang setzen
						$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $Value)));
						If (!$Result) {
							$this->SendDebug("FadeIn", "Fehler beim Schreiben des Wertes!", 0);
							return; 
						}
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
		// beim Ausschalten Faden
		$this->SendDebug("FadeOut", "Ausfuehrung", 0);
		$this->SetBuffer("Fade", 1);
		$Fadetime = $this->ReadPropertyInteger("FadeIn");
		$Fadetime = min(10, max(0, $Fadetime));
		If ($Fadetime > 0) {
			// Zielwert RGB bestimmen
			$Value = GetValueInteger($this->GetIDForIdent("Intensity"));
			
			$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
			$FadeScalar = min(20, max(1, $FadeScalar));
			$Steps = $Fadetime * $FadeScalar;
			$Stepwide = $Value / $Steps;
			
			If ($Value <= 3) {
				$this->SendDebug("FadeOut", "W ist 0 -> keine Aktion", 0);
			}
			elseif ($Value > 3) {
			// Fade Out
				for ($i = ($l - $Stepwide) ; $i >= (0 + $Stepwide); $i = $i - round($Stepwide, 2)) {
					$Starttime = microtime(true);
					
					If ($this->ReadPropertyBoolean("Open") == true) {
						// Ausgang setzen
						$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => $Value)));
						If (!$Result) {
							$this->SendDebug("FadeOut", "Fehler beim Schreiben des Wertes!", 0);
							return; 
						}
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
	    
	// Schaltet den gewaehlten Pin
	public function Set_Status(Bool $value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Set_Status", "Ausfuehrung", 0);
			$FadeInTime = $this->ReadPropertyInteger("FadeIn");
			$FadeOutTime = $this->ReadPropertyInteger("FadeOut");

			If ($value == true) {
				If ($FadeInTime > 0) {
					$this->FadeIn();
				}
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => GetValueInteger($this->GetIDForIdent("Intensity")))));
				If (!$Result) {
					$this->SendDebug("Set_Status", "Fehler beim Schreiben des Wertes!", 0);
					return; 
				}
				else {
					$this->Get_Status();
					SetValueBoolean($this->GetIDForIdent("Status"), true);
				}
			}
			else {
				If ($FadeOutTime > 0) {
					$this->FadeOut();
				}
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin"), "Value" => 0)));
				If (!$Result) {
					$this->SendDebug("Set_Status", "Fehler beim Schreiben des Wertes!", 0);
					return;
				}
				else {
					SetValueBoolean($this->GetIDForIdent("Status"), false);
				}
			}
		}
	}
	
	public function Get_Status()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Get_Status", "Ausfuehrung", 0);
			
			If (GetValueBoolean($this->GetIDForIdent("Status")) == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_PWM_dutycycle", "Pin" => $this->ReadPropertyInteger("Pin") )));
				If ($Result < 0) {
					$this->SendDebug("Get_Status", "Fehler beim Lesen des Wertes!", 0);
					return;
				}
				else {
					SetValueInteger($this->GetIDForIdent("Intensity"), $Result);
				}
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
