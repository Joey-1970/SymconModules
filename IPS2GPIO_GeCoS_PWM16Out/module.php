<?
    // Klassendefinition
    class IPS2GPIO_GeCoS_PWM16Out extends IPSModule 
    {
	// PCA9685
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 82);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		$this->RegisterPropertyInteger("Frequency", 100);
		$this->RegisterPropertyInteger("FadeScalar", 4);
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterPropertyInteger("FadeTime_".$i, 0);
		}
		
		// Profil anlegen
		$this->RegisterProfileInteger("IPS2GPIO.Intensity4096", "Intensity", "", " %", 0, 4095, 1);
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 15; $i++) {
			$this->RegisterVariableBoolean("Output_Bln_X".$i, "Ausgang X".$i, "~Switch", ($i + 1) * 10);
			$this->EnableAction("Output_Bln_X".$i);	
			
			$this->RegisterVariableInteger("Output_Int_X".$i, "Ausgang X".$i, "IPS2GPIO.Intensity4096", (($i + 1) * 10) + 5);
			$this->EnableAction("Output_Int_X".$i);	
		}
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device konnte nicht gefunden werden");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "I²C-Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		for ($i = 82; $i <= 87; $i++) {
		    	$arrayOptions[] = array("label" => $i." dez. / 0x".strtoupper(dechex($i))."h", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		foreach($DevicePorts AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "100", "value" => 100);
		$arrayOptions[] = array("label" => "200", "value" => 200);
		$arrayElements[] = array("type" => "Select", "name" => "Frequency", "caption" => "Frequenz (Hz)", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Optional: Angabe der Standard Fade-In/-Out-Zeit in Sekunden (0 => aus, max. 10 Sek)");
		for ($i = 0; $i <= 15; $i++) {
			$arrayElements[] = array("type" => "Label", "label" => "Kanal ".$i.":");
			$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeTime_".$i,  "caption" => "Fade Zeit"); 
		}
		$arrayElements[] = array("type" => "Label", "label" => "Schritte pro Sekunde: (1 - 16)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeScalar",  "caption" => "Fade Schritte"); 
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");

		
		$arrayElements[] = array("type" => "Button", "label" => "Herstellerinformationen", "onClick" => "echo 'https://www.gedad.de/projekte/projekte-f%C3%BCr-privat/gedad-control/'");
		
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}           
	  
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		$this->SetBuffer("ErrorCounter", 0);
		
		// Summary setzen
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		$this->SetSummary("Adresse: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." Bus: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]);

		
		//ReceiveData-Filter setzen
		$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
		$Filter = '((.*"Function":"get_used_i2c".*|.*"InstanceID":'.$this->InstanceID.'.*)|.*"Function":"status".*)';
		$this->SetReceiveDataFilter($Filter);

		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					// Setup
					$this->Setup();
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
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "get_used_i2c":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->ApplyChanges();
				}
				break;
			case "status":
			   	If ($data->HardwareRev <= 3) {
				   	If (($data->Pin == 0) OR ($data->Pin == 1)) {
				   		$this->SetStatus($data->Status);		
				   	}
			   	}
				else if ($data->HardwareRev > 3) {
					If (($data->Pin == 2) OR ($data->Pin == 3)) {
				   		$this->SetStatus($data->Status);
				   	}
				}
			   	break;
	 	}
 	}
	
	public function RequestAction($Ident, $Value) 
	{
		$Source = substr($Ident, 7, 3);  
		$Number = intval(substr($Ident, 12, 2));
		
		switch($Source) {
		case "Bln":
			$this->SetOutputPinStatus($Number, $Value);
	            	break;
		case "Int":
	            	$this->SetOutputPinValue($Number, $Value);
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	    
	// Beginn der Funktionen
	public function SetOutputPinValue(Int $Output, Int $Value)
	{ 
		$this->SendDebug("SetOutputPinValue", "Ausfuehrung", 0);
		$Output = min(15, max(0, $Output));
		$Value = min(4095, max(0, $Value));
		
		$ByteArray = array();
		$StartAddress = ($Output * 4) + 6;
		$Status = GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Output));
		$L_Bit = $Value & 255;
		$H_Bit = $Value >> 8;
		
		If ($Status == true) {
			$H_Bit = $this->unsetBit($H_Bit, 4);
		}
		else {
			$H_Bit = $this->setBit($H_Bit, 4);
		}
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Ausgang setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel_White", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
								  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
			If (!$Result) {
				$this->SendDebug("SetOutputPinStatus", "Daten setzen fehlerhaft!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				// Ausgang abfragen
				$this->GetOutput($StartAddress + 2);
			}
		}
	}
	
	public function SetOutputPinStatus(Int $Output, Bool $Status)
	{ 
		$this->SendDebug("SetOutputPinStatus", "Ausfuehrung", 0);
		$Output = min(15, max(0, $Output));
		$Status = min(1, max(0, $Status));
		$CurrentStatus = GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Output));
		If ($CurrentStatus == $Status) {
			return;
		}
		
		$ByteArray = array();
		$StartAddress = ($Output * 4) + 6;
		$Value = GetValueInteger($this->GetIDForIdent("Output_Int_X".$Output));
		$L_Bit = $Value & 255;
		$H_Bit = $Value >> 8;
		$FadeTime = $this->ReadPropertyInteger("FadeTime_".$Output);
		
		If ($Status == true) {
			$H_Bit = $this->unsetBit($H_Bit, 4);
			If ($FadeTime > 0) {
				$this->FadeIn($Output);
			}
		}
		else {
			$H_Bit = $this->setBit($H_Bit, 4);
			If ($FadeTime > 0) {
				$this->FadeOut($Output);
			}
		}
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Ausgang setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel_White", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
								  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
			If (!$Result) {
				$this->SendDebug("SetOutputPinStatus", "Daten setzen fehlerhaft!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				// Ausgang abfragen
				$this->GetOutput($StartAddress + 2);
			}
		}
	}     
	
	public function ToggleOutputPinStatus(Int $Output)
	{ 
		$this->SendDebug("ToggleOutputPinStatus", "Ausfuehrung", 0);
		$Status = GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Output));
		$this->SetOutputPinStatus($Output, !$Status);
	}         
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			
			// Prescale einstellen
			$PreScale = round((25000000 / (4096 * $this->ReadPropertyInteger("Frequency"))) - 1);
			// Aktuellen Status feststellen
			$Result_Mode = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Read_Byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 1)));
			$Result_PreScale = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Read_Byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 254)));
			If (($Result_Mode < 0) OR ($Result_PreScale < 0)) {
				$this->SendDebug("Setup", "Lesen der Konfiguration fehlerhaft!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				If (($Result_Mode == 4) AND ($Result_PreScale == $PreScale)) {
					$this->SendDebug("Setup", "Lesen der Konfiguration erfolgreich, keine Erneuerung notwendig.", 0);
				}
				else {
					$this->SendDebug("Setup", "Mode: ".$Result_Mode." PreScale: ".$Result_PreScale, 0);
					// Mode 1 in Sleep setzen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 0, "Value" => 16)));
					If (!$Result) {
						$this->SendDebug("Setup", "Ausfuehrung in Sleep setzen fehlerhaft!", 0);
						$this->SetStatus(202);
					}
					IPS_Sleep(10);
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 254, "Value" => $PreScale)));
					If (!$Result) {
						$this->SendDebug("Setup", "Prescale setzen fehlerhaft!", 0);
						$this->SetStatus(202);
					}
					// Mode 1 in Sleep zurücksetzen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 0, "Value" => 32)));
					If (!$Result) {
						$this->SendDebug("Setup", "Mode 1 setzen fehlerhaft!", 0);
						$this->SetStatus(202);
					}
					// Mode 2 auf Ausgänge setzen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 1, "Value" => 4)));
					If (!$Result) {
						$this->SendDebug("Setup", "Mode 2 setzen fehlerhaft!", 0);
						$this->SetStatus(202);
					}
				}
			}
				
			// Ausgänge initial einlesen
			for ($i = 6; $i < 70; $i = $i + 4) {
				$this->GetOutput($i + 2);
			}
		}
	}
	    
	private function GetOutput(Int $Register)
	{
		$this->SendDebug("GetOutput", "Ausfuehrung", 0);
		If ($this->ReadPropertyBoolean("Open") == true) {
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $Register)));
				if ($Result < 0) {
					$this->SendDebug("GetOutput", "Lesen der Ausgaenge fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					//$this->SendDebug("GetOutput", "Ergebnis: ".$Result, 0);
					$this->SetStatusVariables($Register, $Result);
					$this->SetStatus(102);
					break;
				}
			$tries--;
			} while ($tries);  
		}
	}

	private function FadeIn(Int $Channel)
	{
		// W beim Einschalten Faden
		$this->SendDebug("FadeIn", "Ausfuehrung", 0);
		$Channel = min(15, max(0, $Channel));
		$Fadetime = $this->ReadPropertyInteger("FadeTime_".$Channel);
		$Fadetime = min(10, max(0, $Fadetime));
		// Zielwert W bestimmen
		$Value_W = GetValueInteger($this->GetIDForIdent("Output_Int_X".$Channel));
		$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
		
		If (($Fadetime > 0) AND ($Value_W > 50)) {
			// muss von 0 auf den Zielwert gebracht werden
			$Steps = $Fadetime * $FadeScalar;
			$Stepwide = $Value_W / $Steps;
			$StartAddress = ($Channel * 4) + 6;
			
			// Fade In	
			for ($i = (0 + $Stepwide); $i <= ($Value_W - $Stepwide); $i = $i + round($Stepwide, 2)) {
				// Werte skalieren
				$Value = intval($i);
				// Bytes bestimmen
				$L_Bit = $Value & 255;
				$H_Bit = $Value >> 8;
				$this->SendDebug("FadeIn", "Weisswert: ".$Value, 0);
				$Starttime = microtime(true);
				If ($this->ReadPropertyBoolean("Open") == true) {
					// Ausgang setzen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel_White", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
										  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
					If (!$Result) {
						$this->SendDebug("FadeIn", "Daten setzen fehlerhaft!", 0);
					}
					else {
						If (GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Channel)) == false) {
							SetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Channel), true);
						}
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
	
	private function FadeOut(Int $Channel)
	{
		// W beim Ausschalten Faden
		$this->SendDebug("FadeOut", "Ausfuehrung", 0);
		$Channel = min(15, max(0, $Channel));
		$Fadetime = $this->ReadPropertyInteger("FadeTime_".$Channel);
		$Fadetime = min(10, max(0, $Fadetime));
		// Istwert W bestimmen
		$Value_W = GetValueInteger($this->GetIDForIdent("Output_Int_X".$Channel));
		$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
		
		If (($Fadetime > 0) AND ($Value_W > 50)) {
			// muss vom Zielwert auf 0 gebracht werden
			$Steps = $Fadetime * $FadeScalar;
			$Stepwide = $Value_W / $Steps;
			$StartAddress = ($Channel * 4) + 6;
			
			// Fade Out			
			for ($i = ($Value_W - $Stepwide) ; $i >= (0 + $Stepwide); $i = $i - round($Stepwide, 2)) {
				// Werte skalieren
				$Value = intval($i);
				// Bytes bestimmen
				$L_Bit = $Value & 255;
				$H_Bit = $Value >> 8;
				$this->SendDebug("FadeOut", "Weisswert: ".$Value, 0);
				$Starttime = microtime(true);
				If ($this->ReadPropertyBoolean("Open") == true) {
					// Ausgang setzen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel_White", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
										  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
					If (!$Result) {
						$this->SendDebug("FadeOut", "Daten setzen fehlerhaft!", 0);
					}
					else {
						If (GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Channel)) == false) {
							SetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Channel), true);
						}
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
	
	public function FadeTo(Int $Channel, Int $TargetValueRel, Int $Fadetime)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("FadeTo", "Ausfuehrung", 0);
			$Channel = min(15, max(0, $Channel));
			$TargetValueRel = min(100, max(0, $TargetValueRel));
			$TargetValue = 4095 / 100 * $TargetValueRel;
			$Fadetime = min(10, max(1, $Fadetime));
			$CurrentValue = GetValueInteger($this->GetIDForIdent("Output_Int_X".$Channel));
			
			$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
			$Steps = $Fadetime * $FadeScalar;
			$Difference = abs($TargetValue - $CurrentValue);
			$Stepwide = $Difference / $Steps;
			$StartAddress = ($Channel * 4) + 6;
			
			If ($TargetValue == $CurrentValue) {
				// es gibt nichts zu tun
				return;
			}
			elseif (($TargetValue > $CurrentValue) AND ($Difference > 50)) {
				// FadeIn
				for ($i = ($CurrentValue + $Stepwide); $i <= ($TargetValue - $Stepwide); $i = $i + round($Stepwide, 2)) {
					// Werte skalieren
					$Value = intval($i);
					// Bytes bestimmen
					$L_Bit = $Value & 255;
					$H_Bit = $Value >> 8;
					$this->SendDebug("FadeTo", "Weisswert: ".$Value, 0);
					$Starttime = microtime(true);
					If ($this->ReadPropertyBoolean("Open") == true) {
						// Ausgang setzen
						$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel_White", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
											  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
						If (!$Result) {
							$this->SendDebug("FadeTo", "Daten setzen fehlerhaft!", 0);
						}
					}
					$Endtime = microtime(true);
					$Delay = intval(($Endtime - $Starttime) * 1000);
					$DelayMax = intval(1000 / $FadeScalar);
					$Delaytime = min($DelayMax, max(0, ($DelayMax - $Delay)));   
					IPS_Sleep($Delaytime);
				}
			}	
			elseif (($TargetValue < $CurrentValue) AND ($Difference > 50)) {
				// FadeOut			
				for ($i = ($CurrentValue - $Stepwide) ; $i >= ($TargetValue + $Stepwide); $i = $i - round($Stepwide, 2)) {
					// Werte skalieren
					$Value = intval($i);
					// Bytes bestimmen
					$L_Bit = $Value & 255;
					$H_Bit = $Value >> 8;
					$this->SendDebug("FadeTo", "Weisswert: ".$Value, 0);
					$Starttime = microtime(true);
					If ($this->ReadPropertyBoolean("Open") == true) {
						// Ausgang setzen
						$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel_White", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
											  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
						If (!$Result) {
							$this->SendDebug("FadeTo", "Daten setzen fehlerhaft!", 0);
						}
					}
					$Endtime = microtime(true);
					$Delay = intval(($Endtime - $Starttime) * 1000);
					$DelayMax = intval(1000 / $FadeScalar);
					$Delaytime = min($DelayMax, max(0, ($DelayMax - $Delay)));   
					IPS_Sleep($Delaytime);
				}
			}
			$this->SetOutputPinValue($Channel, $TargetValue);
		}
	}
	    
	private function SetStatusVariables(Int $Register, Int $Value)
	{
		$Intensity = $Value & 4095;
		$Status = !boolval($Value & 4096); 
		$Number = ($Register - 8) / 4;
		
		$this->SendDebug("SetStatusVariables", "Itensitaet: ".$Intensity." Status: ".(int)$Status, 0);
		
		If ($Intensity <> GetValueInteger($this->GetIDForIdent("Output_Int_X".$Number))) {
			SetValueInteger($this->GetIDForIdent("Output_Int_X".$Number), $Intensity);
		}
		If ($Status <> GetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Number))) {
			SetValueBoolean($this->GetIDForIdent("Output_Bln_X".$Number), $Status);
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
	
	private function setBit($byte, $significance) { 
 		// ein bestimmtes Bit auf 1 setzen
 		return $byte | 1<<$significance;   
 	} 
	
	private function unsetBit($byte, $significance) {
	    // ein bestimmtes Bit auf 0 setzen
	    return $byte & ~(1<<$significance);
	}
	    
	private function Get_I2C_Ports()
	{
		If ($this->HasActiveParent() == true) {
			$I2C_Ports = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_get_ports")));
		}
		else {
			$DevicePorts = array();
			$DevicePorts[0] = "I²C-Bus 0";
			$DevicePorts[1] = "I²C-Bus 1";
			for ($i = 3; $i <= 10; $i++) {
				$DevicePorts[$i] = "MUX I²C-Bus ".($i - 3);
			}
			$I2C_Ports = serialize($DevicePorts);
		}
	return $I2C_Ports;
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
