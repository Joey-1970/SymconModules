<?
    // Klassendefinition
    class IPS2GPIO_L298N extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("RunningTime_1", 0);
		$this->SetTimerInterval("RunningTime_2", 0);
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin_1L", -1);
		$this->SetBuffer("PreviousPin_1L", -1);
		$this->RegisterPropertyInteger("Pin_1R", -1);
		$this->SetBuffer("PreviousPin_1R", -1);
		$this->RegisterPropertyInteger("Pin_2L", -1);
		$this->SetBuffer("PreviousPin_2L", -1);
		$this->RegisterPropertyInteger("Pin_2R", -1);
		$this->SetBuffer("PreviousPin_2R", -1);
		$this->RegisterPropertyInteger("RunningTime_1", 0);
		$this->RegisterTimer("RunningTime_1", 0, 'I2GL298N_MotorControl($_IPS["TARGET"], 1, 1);');
		$this->RegisterPropertyInteger("RunningTime_2", 0);
		$this->RegisterTimer("RunningTime_2", 0, 'I2GL298N_MotorControl($_IPS["TARGET"], 2, 1);');
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		
		// Profile anlegen
		$this->RegisterProfileInteger("IPS2GPIO.MotorControl", "Information", "", "", 0, 2, 0);
		IPS_SetVariableProfileAssociation("IPS2GPIO.MotorControl", 0, "<=", "HollowArrowLeft", 0x00FF00);
		IPS_SetVariableProfileAssociation("IPS2GPIO.MotorControl", 1, "Stop", "Cross", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2GPIO.MotorControl", 2, "=>", "HollowArrowRight", 0x00FF00);

		// Status-Variablen anlegen
		$this->RegisterVariableInteger("Motor_1", "Motor 1", "IPS2GPIO.MotorControl", 10);
		$this->EnableAction("Motor_1");
		
		$this->RegisterVariableInteger("Motor_2", "Motor 2", "IPS2GPIO.MotorControl", 20);
		$this->EnableAction("Motor_2");
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
		$arrayElements[] = array("type" => "Label", "label" => "Motor Ausgang 1, Linkslauf"); 
		If ($this->ReadPropertyInteger("Pin_1L") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_1L")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_1L")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_1L", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		$arrayElements[] = array("type" => "Label", "label" => "Motor Ausgang 1, Rechtslauf"); 
		If ($this->ReadPropertyInteger("Pin_1R") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_1R")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_1R")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_1R", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		$arrayElements[] = array("type" => "Label", "label" => "Motor Ausgang 2, Linkslauf"); 
		If ($this->ReadPropertyInteger("Pin_2L") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_2L")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_2L")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_2L", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		$arrayElements[] = array("type" => "Label", "label" => "Motor Ausgang 2, Rechtslauf"); 
		If ($this->ReadPropertyInteger("Pin_2R") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_2R")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_2R")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_2R", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Motor 1: Maximale Laufzeit in Sekunden (0 = aus)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "RunningTime_1", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "Motor 2: Maximale Laufzeit in Sekunden (0 = aus)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "RunningTime_2", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayActions = array();
		If ((($this->ReadPropertyInteger("Pin_1L") >= 0) AND ($this->ReadPropertyInteger("Pin_1R") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) OR
				(($this->ReadPropertyInteger("Pin_2L") >= 0) AND ($this->ReadPropertyInteger("Pin_2R") >= 0) AND ($this->ReadPropertyBoolean("Open") == true))) {
			//$arrayActions[] = array("type" => "Button", "label" => "On", "onClick" => 'I2GOUT_Set_Status($id, true);');
			//$arrayActions[] = array("type" => "Button", "label" => "Off", "onClick" => 'I2GOUT_Set_Status($id, false);');
			//$arrayActions[] = array("type" => "Button", "label" => "Toggle Output", "onClick" => 'I2GOUT_Toggle_Status($id);');
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
		If ((intval($this->GetBuffer("PreviousPin_1L")) <> $this->ReadPropertyInteger("Pin_1L")) OR
			(intval($this->GetBuffer("PreviousPin_1R")) <> $this->ReadPropertyInteger("Pin_1R")) OR
			(intval($this->GetBuffer("PreviousPin_2L")) <> $this->ReadPropertyInteger("Pin_2L")) OR
			(intval($this->GetBuffer("PreviousPin_2R")) <> $this->ReadPropertyInteger("Pin_2R"))) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_1L")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_1L"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_1R")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_1R"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_2L")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_2L"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_2R")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_2R"), 0);
		}
		
		SetValueInteger($this->GetIDForIdent("Motor_1"), 1);
		SetValueInteger($this->GetIDForIdent("Motor_2"), 1);
		
             	$this->SetSummary("GPIO: ".$this->ReadPropertyInteger("Pin_1L").", ".$this->ReadPropertyInteger("Pin_1R").", ".$this->ReadPropertyInteger("Pin_2L").", ".$this->ReadPropertyInteger("Pin_2R"));
		
		//ReceiveData-Filter setzen
		$Filter = '((.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin_1L").'.*)|(.*"Pin":'.$this->ReadPropertyInteger("Pin_1R").'.*|.*"Pin":'.$this->ReadPropertyInteger("Pin_2L").'.*)|(.*"Pin":'.$this->ReadPropertyInteger("Pin_2R").'.*))';
		$this->SetReceiveDataFilter($Filter);

		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {	
			If ((($this->ReadPropertyInteger("Pin_1L") >= 0) AND ($this->ReadPropertyInteger("Pin_1R") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) OR
				(($this->ReadPropertyInteger("Pin_2L") >= 0) AND ($this->ReadPropertyInteger("Pin_2R") >= 0) AND ($this->ReadPropertyBoolean("Open") == true))) {
				
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_1L"), "PreviousPin" => $this->GetBuffer("PreviousPin_1L"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_1L", $this->ReadPropertyInteger("Pin_1L"));
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_1R"), "PreviousPin" => $this->GetBuffer("PreviousPin_1R"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_1R", $this->ReadPropertyInteger("Pin_1R"));
				
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_2L"), "PreviousPin" => $this->GetBuffer("PreviousPin_2L"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_2L", $this->ReadPropertyInteger("Pin_2L"));
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_2R"), "PreviousPin" => $this->GetBuffer("PreviousPin_2R"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_2R", $this->ReadPropertyInteger("Pin_2R"));
				
				If ($Result == true) {
					$this->Get_Status(1);
					$this->Get_Status(2);
					
					$this->SetStatus(102);
				}
			}
			else {
				$this->SetStatus(104);
			}
		}
		else {
			$this->SetStatus(104);
		}
	}

	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Motor_1":
	            	If ($this->ReadPropertyBoolean("Open") == true) {
				$this->MotorControl(1, $Value);
		    	}
	            	break;
		case "Motor_2":
	            	If ($this->ReadPropertyBoolean("Open") == true) {
		    		$this->MotorControl(2, $Value);
		    	}
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
			   	If (($data->Pin == $this->ReadPropertyInteger("Pin_1L")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_1R")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_2L")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_2R")) ) {
					$this->SetStatus($data->Status);
				}
			   	break;
	 	}
 	}
	// Beginn der Funktionen
	
	// Schaltet den gewaehlten Pin
	public function MotorControl(Int $Motor, Int $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("MotorControl", "Ausfuehrung Motor: ".$Motor." Wert: ".$Value, 0);
			$Value = min(2, max(0, $Value));
			$Motor = min(2, max(1, $Motor));
			$Pin_L = $this->ReadPropertyInteger("Pin_".$Motor."L");
			$Pin_R = $this->ReadPropertyInteger("Pin_".$Motor."R");
			
			If (($Pin_L >= 0) and ($Pin_R >= 0)) {
				If ($Value == 0) {
					// Linkslauf
					$this->SendDebug("MotorControl", "Linkslauf setzen", 0);
					$this->SetTimerInterval("RunningTime_".$Motor, ($this->ReadPropertyInteger("RunningTime_".$Motor) * 1000));
					$Result_R = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $Pin_R, "Value" => 0 )));
					$Result_L = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $Pin_L, "Value" => 1 )));
				}
				elseIf ($Value == 1) {
					// Stop
					$this->SendDebug("MotorControl", "Stoppen", 0);
					$this->SetTimerInterval("RunningTime_".$Motor, 0);
					$Result_L = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $Pin_L, "Value" => 0 )));
					$Result_R = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $Pin_R, "Value" => 0 )));
				}
				elseIf ($Value == 2) {
					// Rechtslauf
					$this->SendDebug("MotorControl", "Rechtslauf setzen", 0);
					$this->SetTimerInterval("RunningTime_".$Motor, ($this->ReadPropertyInteger("RunningTime_".$Motor) * 1000));
					$Result_L = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $Pin_L, "Value" => 0 )));
					$Result_R = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $Pin_R, "Value" => 1 )));
				}


				IF ((!$Result_L) OR (!$Result_R)) {
					$this->SendDebug("MotorControl", "Fehler beim Setzen des Status!", 0);
					return;
				}
				else {
					SetValueInteger($this->GetIDForIdent("Motor_".$Motor), $Value);
					$this->Get_Status($Motor);
				}
			}
		}
	}    
	
	
	// Ermittelt den Status
	public function Get_Status(Int $Motor)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Get_Status", "Ausfuehrung", 0);
			$Motor = min(2, max(1, $Motor));
			$Pin_L = $this->ReadPropertyInteger("Pin_".$Motor."L");
			$Pin_R = $this->ReadPropertyInteger("Pin_".$Motor."R");
			
			If (($Pin_L >= 0) and ($Pin_R >= 0)) {
				$Result_L = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_value", "Pin" => $Pin_L )));
				$Result_R = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_value", "Pin" => $Pin_R )));
				
				IF (($Result_L < 0) OR ($Result_R < 0)) {
					$this->SendDebug("Get_Status", "Fehler beim Lesen des Status!", 0);
					return;
				}
				else {
					$this->SendDebug("Get_Status", "Ergebnis links: ".(int)$Result_L." rechts: ".(int)$Result_R, 0);
					If (($Result_L == 1) AND ($Result_R == 0)) {
						SetValueInteger($this->GetIDForIdent("Motor_".$Motor), 0);
					}
					elseIf (($Result_L == 0) AND ($Result_R == 1)) {
						SetValueInteger($this->GetIDForIdent("Motor_".$Motor), 2);
					}
					else {
						SetValueInteger($this->GetIDForIdent("Motor_".$Motor), 1);
					}
				}
			}
		}
	}
	
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);        
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
	    
	protected function HasActiveParent()
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
