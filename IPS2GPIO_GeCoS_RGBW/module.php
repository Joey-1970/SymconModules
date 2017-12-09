<?
    // Klassendefinition
    class IPS2GPIO_GeCoS_RGBW extends IPSModule 
    {
	// PCA9685
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 88);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		$this->RegisterPropertyInteger("FadeScalar", 4);
		$this->RegisterPropertyInteger("FadeIn_1", 0);
		$this->RegisterPropertyInteger("FadeOut_1", 0);
		$this->RegisterPropertyInteger("FadeIn_2", 0);
		$this->RegisterPropertyInteger("FadeOut_2", 0);
		$this->RegisterPropertyInteger("FadeIn_3", 0);
		$this->RegisterPropertyInteger("FadeOut_3", 0);
		$this->RegisterPropertyInteger("FadeIn_4", 0);
		$this->RegisterPropertyInteger("FadeOut_4", 0);
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Device konnte nicht gefunden werden");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		for ($i = 88; $i <= 90; $i++) {
		    	$arrayOptions[] = array("label" => $i." dez. / 0x".strtoupper(dechex($i))."h", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
				$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		
		$arrayOptions = array();
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		foreach($DevicePorts AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Optional: Angabe von Fade-In/-Out-Zeit in Sekunden (0 => aus, max. 30 Sek)");
		$arrayElements[] = array("type" => "Label", "label" => "Gruppe 1 RGBW:");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeIn_1",  "caption" => "Fade-In-Zeit"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeOut_1",  "caption" => "Fade-Out-Zeit");
		$arrayElements[] = array("type" => "Label", "label" => "Gruppe 2 RGBW:");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeIn_2",  "caption" => "Fade-In-Zeit"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeOut_2",  "caption" => "Fade-Out-Zeit");
		$arrayElements[] = array("type" => "Label", "label" => "Gruppe 3 RGBW:");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeIn_3",  "caption" => "Fade-In-Zeit"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeOut_3",  "caption" => "Fade-Out-Zeit");
		$arrayElements[] = array("type" => "Label", "label" => "Gruppe 4 RGBW:");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeIn_4",  "caption" => "Fade-In-Zeit"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "FadeOut_4",  "caption" => "Fade-Out-Zeit");
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
		// Profil anlegen
		$this->RegisterProfileInteger("IPS2GPIO.Intensity4096", "Intensity", "", " %", 0, 4095, 1);
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 3; $i++) {
			$this->RegisterVariableBoolean("Status_RGB_".($i + 1), "Status RGB ".($i + 1), "~Switch", 10 + ($i * 70));
			$this->EnableAction("Status_RGB_".($i + 1));
			$this->RegisterVariableInteger("Color_RGB_".($i + 1), "Farbe ".($i + 1), "~HexColor", 20 + ($i * 70));
			$this->EnableAction("Color_RGB_".($i + 1));
			$this->RegisterVariableInteger("Intensity_R_".($i + 1), "Intensity Rot ".($i + 1), "IPS2GPIO.Intensity4096", 30 + ($i * 70) );
			$this->EnableAction("Intensity_R_".($i + 1));
			$this->RegisterVariableInteger("Intensity_G_".($i + 1), "Intensity Grün ".($i + 1), "IPS2GPIO.Intensity4096", 40 + ($i * 70));
			$this->EnableAction("Intensity_G_".($i + 1));
			$this->RegisterVariableInteger("Intensity_B_".($i + 1), "Intensity Blau ".($i + 1), "IPS2GPIO.Intensity4096", 50 + ($i * 70));
			$this->EnableAction("Intensity_B_".($i + 1));
			$this->RegisterVariableBoolean("Status_W_".($i + 1), "Status Weiß ".($i + 1), "~Switch", 60 + ($i * 70));
			$this->EnableAction("Status_W_".($i + 1));
			$this->RegisterVariableInteger("Intensity_W_".($i + 1), "Intensity Weiß ".($i + 1), "IPS2GPIO.Intensity4096", 70 + ($i * 70));
			$this->EnableAction("Intensity_W_".($i + 1));			
		}
		
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
		$Parts = explode("_", $Ident);
		$Source = $Parts[0];
		$Channel = $Parts[1];
		$Group = $Parts[2];
		
		switch($Source) {
		case "Status":
			$this->SetOutputPinStatus($Group, $Channel, $Value);
	            	break;
		case "Color":
	            	$this->SetOutputPinColor($Group, $Value);
	            	break;
		case "Intensity":
	            	$this->SetOutputPinValue($Group, $Channel, $Value);
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
		
	}
	    
	// Beginn der Funktionen
	public function SetOutputPinValue(Int $Group, String $Channel, Int $Value)
	{ 
		$this->SendDebug("SetOutputPinValue", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$Value = min(4095, max(0, $Value));
		
		$ChannelArray = [
		    "R" => 0,
		    "G" => 4,
		    "B" => 8,
		    "W" => 12,
		];
		
		$StartAddress = (($Group - 1) * 16) + $ChannelArray[$Channel] + 6;
		
		If ($Channel == "W") {
			$Status = GetValueBoolean($this->GetIDForIdent("Status_W_".$Group));
		}
		else {
			$Status = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		}
		
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
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_4_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
			// Ausgang abfragen
			$this->GetOutput($StartAddress + 2);
		}
	}
	
	public function SetOutputPinStatus(Int $Group, String $Channel, Bool $Status)
	{ 
		if (IPS_SemaphoreEnter("SetOutputPinStatus", 1))
		{
			$this->SendDebug("SetOutputPinStatus", "Ausfuehrung", 0);
			$Group = min(4, max(1, $Group));
			$Status = min(1, max(0, $Status));

			$ChannelArray = [
			    "RGB" => 0,
			    "W" => 12,
			];
			$FadeInTime = $this->ReadPropertyInteger("FadeIn_".$Group);
			$FadeOutTime = $this->ReadPropertyInteger("FadeOut_".$Group);

			$StartAddress = (($Group - 1) * 16) + $ChannelArray[$Channel] + 6;
			If ($Channel == "W") {
				If (($FadeInTime > 0) AND ($Status == true)) {
					$this->WFadeIn($Group);
				}
				If (($FadeOutTime > 0) AND ($Status == false)) {
					$this->WFadeOut($Group);
				}
				$Value = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));
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
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_4_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
					// Ausgang abfragen
					$this->GetOutput($StartAddress + 2);
				}
			}
			else {
				If (($FadeInTime > 0) AND ($Status == true)) {
					$this->RGBFadeIn($Group);
				}
				If (($FadeOutTime > 0) AND ($Status == false)) {
					$this->RGBFadeOut($Group);
				}
				$Value_R = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
				$L_Bit_R = $Value_R & 255;
				$H_Bit_R = $Value_R >> 8;
				$Value_G = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
				$L_Bit_G = $Value_G & 255;
				$H_Bit_G = $Value_G >> 8;
				$Value_B = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
				$L_Bit_B = $Value_B & 255;
				$H_Bit_B = $Value_B >> 8;
				If ($Status == true) {
					$H_Bit_R = $this->unsetBit($H_Bit_R, 4);
					$H_Bit_G = $this->unsetBit($H_Bit_G, 4);
					$H_Bit_B = $this->unsetBit($H_Bit_B, 4);
				}
				else {
					$H_Bit_R = $this->setBit($H_Bit_R, 4);
					$H_Bit_G = $this->setBit($H_Bit_G, 4);
					$H_Bit_B = $this->setBit($H_Bit_B, 4);
				}
				If ($this->ReadPropertyBoolean("Open") == true) {
					// Ausgang setzen
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_12_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
										  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
					/*
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
										  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
					If (!$Result) {
						$this->SendDebug("SetOutputPinStatus", "Daten setzen fehlerhaft!", 0);
					}
					*/
					// Ausgang abfragen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Read_Group", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress + 2)));
					$RGB = unserialize($Result);
					for($i = 0; $i < count($RGB); $i++) {
						$this->SetStatusVariables( ($StartAddress + 2) + ($i * 4), $RGB[$i]);
					}
				}
			}
			//Semaphore wieder freigeben!
		    	IPS_SemaphoreLeave("SetOutputPinStatus");
		}
	}    	    
	
	public function ToggleOutputPinStatus(Int $Group, String $Channel)
	{ 
		$this->SendDebug("ToggleOutputPinStatus", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
				
		$ChannelArray = [
		    "RGB" => 0,
		    "W" => 12,
		];
		
		$StartAddress = (($Group - 1) * 16) + $ChannelArray[$Channel] + 6;
		If ($Channel == "W") {
			$Status = GetValueBoolean($this->GetIDForIdent("Status_W_".$Group));
			$Value = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));
			$L_Bit = $Value & 255;
			$H_Bit = $Value >> 8;
			If (!$Status == true) {
				$H_Bit = $this->unsetBit($H_Bit, 4);
			}
			else {
				$H_Bit = $this->setBit($H_Bit, 4);
			}
			If ($this->ReadPropertyBoolean("Open") == true) {
				// Ausgang setzen
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_4_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
				// Ausgang abfragen
				$this->GetOutput($StartAddress + 2);
			}
		}
		else {
			$Status = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
			$Value_R = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
			$L_Bit_R = $Value_R & 255;
			$H_Bit_R = $Value_R >> 8;
			$Value_G = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
			$L_Bit_G = $Value_G & 255;
			$H_Bit_G = $Value_G >> 8;
			$Value_B = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
			$L_Bit_B = $Value_B & 255;
			$H_Bit_B = $Value_B >> 8;
			If (!$Status == true) {
				$H_Bit_R = $this->unsetBit($H_Bit_R, 4);
				$H_Bit_G = $this->unsetBit($H_Bit_G, 4);
				$H_Bit_B = $this->unsetBit($H_Bit_B, 4);
			}
			else {
				$H_Bit_R = $this->setBit($H_Bit_R, 4);
				$H_Bit_G = $this->setBit($H_Bit_G, 4);
				$H_Bit_B = $this->setBit($H_Bit_B, 4);
			}
			If ($this->ReadPropertyBoolean("Open") == true) {
				// Ausgang setzen
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_12_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
									  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
				
				/*$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
										  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));

				If (!$Result) {
					$this->SendDebug("ToggleOutputPinStatus", "Daten setzen fehlerhaft!", 0);
				}
				*/
				// Ausgang abfragen
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Read_Group", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress + 2)));
				$RGB = unserialize($Result);
				for($i = 0; $i < count($RGB); $i++) {
					$this->SetStatusVariables( ($StartAddress + 2) + ($i * 4), $RGB[$i]);
				}
			}
		}		
	}    	    
	        
	private function RGBFadeIn(Int $Group)
	{
		// RGB beim Einschalten Faden
		$this->SendDebug("RGBFadeIn", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$Fadetime = $this->ReadPropertyInteger("FadeIn_".$Group);
		$Fadetime = min(30, max(0, $Fadetime));
		If ($Fadetime > 0) {
			// Zielwert RGB bestimmen
			$Value_R = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
			$Value_G = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
			$Value_B = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
			// Werte skalieren
			$Value_R = 255 / 4095 * $Value_R;
			$Value_G = 255 / 4095 * $Value_G;
			$Value_B = 255 / 4095 * $Value_B;
			// Umrechnung in HSL
			list($h, $s, $l) = $this->rgbToHsl($Value_R, $Value_G, $Value_B);
			// $l muss von 0 auf den Zielwert gebracht werden
			$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
			$Steps = $Fadetime * $FadeScalar;
			$Stepwide = $l / $Steps;
			$StartAddress = (($Group - 1) * 16) + 6;
			
			// Fade In			
			for ($i = (0 + $Stepwide) ; $i <= ($l - $Stepwide); $i = $i + round($Stepwide, 2)) {
			    	$Starttime = microtime(true);
				//$this->SendDebug("RGBFadeIn", "Startzeit: ".$Starttime, 0);
				// $i muss jetzt als HSL-Wert wieder in RGB umgerechnet werden
				list($r, $g, $b) = $this->hslToRgb($h, $s, $i);
				// Werte skalieren
				$Value_R = 4095 / 255 * $r;
				$Value_G = 4095 / 255 * $g;
				$Value_B = 4095 / 255 * $b;
				// Bytes bestimmen
				$L_Bit_R = $Value_R & 255;
				$H_Bit_R = $Value_R >> 8;
				$L_Bit_G = $Value_G & 255;
				$H_Bit_G = $Value_G >> 8;
				$L_Bit_B = $Value_B & 255;
				$H_Bit_B = $Value_B >> 8;
				If ($this->ReadPropertyBoolean("Open") == true) {
					// Ausgang setzen
					//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_12_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
					//			  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
										  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
					If (!$Result) {
						$this->SendDebug("RGBFadeIn", "Daten setzen fehlerhaft!", 0);
					}
					
					If (GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group)) == false) {
						SetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group), true);
					}
				}
				$Endtime = microtime(true);
				//$this->SendDebug("RGBFadeIn", "Endzeit: ".$Endtime, 0);
				$Delay = intval(($Endtime - $Starttime) * 1000);
				$this->SendDebug("RGBFadeIn", "Delay: ".$Delay, 0);
				$DelayMax = intval(1000 / $FadeScalar);
				$Delaytime = min($DelayMax, max(0, ($DelayMax - $Delay)));   
				IPS_Sleep($Delaytime);
			}
				
		}
	}

	private function RGBFadeOut(Int $Group)
	{
		// RGB beim Ausschalten Faden
		$this->SendDebug("RGBFadeOut", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$Fadetime = $this->ReadPropertyInteger("FadeIn_".$Group);
		$Fadetime = min(30, max(0, $Fadetime));
		If ($Fadetime > 0) {
			// Zielwert RGB bestimmen
			$Value_R = GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group));
			$Value_G = GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group));
			$Value_B = GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group));
			// Werte skalieren
			$Value_R = 255 / 4095 * $Value_R;
			$Value_G = 255 / 4095 * $Value_G;
			$Value_B = 255 / 4095 * $Value_B;
			// Umrechnung in HSL
			list($h, $s, $l) = $this->rgbToHsl($Value_R, $Value_G, $Value_B);
			// $l muss von 0 auf den Zielwert gebracht werden
			$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
			$Steps = $Fadetime * $FadeScalar;
			$Stepwide = $l / $Steps;
			$StartAddress = (($Group - 1) * 16) + 6;
			
			// Fade Out
			for ($i = ($l - $Stepwide) ; $i >= (0 + $Stepwide); $i = $i - round($Stepwide, 2)) {
				$Starttime = microtime(true);
				//$this->SendDebug("RGBFadeOut", "Startzeit: ".$Starttime, 0);
			    	// $i muss jetzt als HSL-Wert wieder in RGB umgerechnet werden
				list($r, $g, $b) = $this->hslToRgb($h, $s, $i);
				// Werte skalieren
				$Value_R = 4095 / 255 * $r;
				$Value_G = 4095 / 255 * $g;
				$Value_B = 4095 / 255 * $b;
				// Bytes bestimmen
				$L_Bit_R = $Value_R & 255;
				$H_Bit_R = $Value_R >> 8;
				$L_Bit_G = $Value_G & 255;
				$H_Bit_G = $Value_G >> 8;
				$L_Bit_B = $Value_B & 255;
				$H_Bit_B = $Value_B >> 8;
				If ($this->ReadPropertyBoolean("Open") == true) {
					// Ausgang setzen
					//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_12_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
					//			  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
								"Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
					If (!$Result) {
						$this->SendDebug("RGBFadeOut", "Daten setzen fehlerhaft!", 0);
					}
					If (GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group)) == true) {
						SetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group), false);
					}
				}
				$Endtime = microtime(true);
				$Delay = intval(($Endtime - $Starttime) * 1000);
				$this->SendDebug("RGBFadeOut", "Delay: ".$Delay, 0);
				$DelayMax = intval(1000 / $FadeScalar);
				$Delaytime = min($DelayMax, max(0, ($DelayMax - $Delay)));   
				IPS_Sleep($Delaytime);
			}
				
		}
	}    
	
	private function WFadeIn(Int $Group)
	{
		// W beim Einschalten Faden
		$this->SendDebug("WFadeIn", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$Fadetime = $this->ReadPropertyInteger("FadeIn_".$Group);
		$Fadetime = min(30, max(0, $Fadetime));
		If ($Fadetime > 0) {
			// Zielwert W bestimmen
			$Value_W = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));
			// $l muss von 0 auf den Zielwert gebracht werden
			$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
			$Steps = $Fadetime * $FadeScalar;
			$Stepwide = 4095 / $Steps;
			$StartAddress = (($Group - 1) * 16) + 18;
			
			// Fade In			
			for ($i = (0 + $Stepwide) ; $i <= ($l - $Stepwide); $i = $i + round($Stepwide, 0)) {
				// Werte skalieren
				$Value_W = $i;
				// Bytes bestimmen
				$L_Bit = $Value_W & 255;
				$H_Bit = $Value_W >> 8;
				If ($this->ReadPropertyBoolean("Open") == true) {
					// Ausgang setzen
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_4_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
					If (GetValueBoolean($this->GetIDForIdent("Status_W_".$Group)) == false) {
						SetValueBoolean($this->GetIDForIdent("Status_W_".$Group), true);
					}
				}
				IPS_Sleep(intval(1000 / $FadeScalar));
			}
				
		}
	}
	
	private function WFadeOut(Int $Group)
	{
		// W beim Einschalten Faden
		$this->SendDebug("WFadeOut", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		$Fadetime = $this->ReadPropertyInteger("FadeOut_".$Group);
		$Fadetime = min(30, max(0, $Fadetime));
		If ($Fadetime > 0) {
			// Zielwert W bestimmen
			$Value_W = GetValueInteger($this->GetIDForIdent("Intensity_W_".$Group));
			// $l muss von 0 auf den Zielwert gebracht werden
			$FadeScalar = $this->ReadPropertyInteger("FadeScalar");
			$Steps = $Fadetime * $FadeScalar;
			$Stepwide = 4095 / $Steps;
			$StartAddress = (($Group - 1) * 16) + 18;
			
			// Fade Out			
			for ($i = ($l - $Stepwide) ; $i >= (0 + $Stepwide); $i = $i - round($Stepwide, 0)) {
				// Werte skalieren
				$Value_W = $i;
				// Bytes bestimmen
				$L_Bit = $Value_W & 255;
				$H_Bit = $Value_W >> 8;
				If ($this->ReadPropertyBoolean("Open") == true) {
					// Ausgang setzen
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_4_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit, "Value_4" => $H_Bit)));
					If (GetValueBoolean($this->GetIDForIdent("Status_W_".$Group)) == false) {
						SetValueBoolean($this->GetIDForIdent("Status_W_".$Group), true);
					}
				}
				IPS_Sleep(intval(1000 / $FadeScalar));
			}
				
		}
	}
		
	    
	public function SetOutputPinColor(Int $Group, Int $Color)
	{
		$this->SendDebug("SetOutputPinColor", "Ausfuehrung", 0);
		$Group = min(4, max(1, $Group));
		
		// Farbwerte aufsplitten
		list($Value_R, $Value_G, $Value_B) = $this->Hex2RGB($Color);
		
		$StartAddress = (($Group - 1) * 16) + 6;
		$Status = GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group));
		// Werte skalieren
		$Value_R = 4095 / 255 * $Value_R;
		$Value_G = 4095 / 255 * $Value_G;
		$Value_B = 4095 / 255 * $Value_B;
		
		$L_Bit_R = $Value_R & 255;
		$H_Bit_R = $Value_R >> 8;
		$L_Bit_G = $Value_G & 255;
		$H_Bit_G = $Value_G >> 8;
		$L_Bit_B = $Value_B & 255;
		$H_Bit_B = $Value_B >> 8;
		If ($Status == true) {
			$H_Bit_R = $this->unsetBit($H_Bit_R, 4);
			$H_Bit_G = $this->unsetBit($H_Bit_G, 4);
			$H_Bit_B = $this->unsetBit($H_Bit_B, 4);
		}
		else {
			$H_Bit_R = $this->setBit($H_Bit_R, 4);
			$H_Bit_G = $this->setBit($H_Bit_G, 4);
			$H_Bit_B = $this->setBit($H_Bit_B, 4);
		}
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Ausgang setzen
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_write_12_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
			//					  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write_Channel", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress, 
					  "Value_1" => 0, "Value_2" => 0, "Value_3" => $L_Bit_R, "Value_4" => $H_Bit_R, "Value_5" => 0, "Value_6" => 0, "Value_7" => $L_Bit_G, "Value_8" => $H_Bit_G, "Value_9" => 0, "Value_10" => 0, "Value_11" => $L_Bit_B, "Value_12" => $H_Bit_B)));
			If (!$Result) {
				$this->SendDebug("SetOutputPinColor", "Daten setzen fehlerhaft!", 0);
			}
			
			// Ausgang abfragen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Read_Group", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $StartAddress + 2)));
			$RGB = unserialize($Result);
			for($i = 0; $i < count($RGB); $i++) {
				$this->SetStatusVariables( ($StartAddress + 2) + ($i * 4), $RGB[$i]);
			}
		}
	}
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			// Aktuellen Status feststellen
			$Result_Mode = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Read_Byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 1)));
			$Result_PreScale = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Read_Byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 254)));

			If (($Result_Mode < 0) OR ($Result_PreScale < 0)) {
				$this->SendDebug("Setup", "Lesen der Konfiguration fehlerhaft!", 0);
			}
			else {
				If (($Result_Mode == 4) AND ($Result_PreScale == 50)) {
					$this->SendDebug("Setup", "Lesen der Konfiguration erfolgreich, keine Erneuerung notwendig.", 0);
				}
				else {
					$this->SendDebug("Setup", "Mode: ".$Result_Mode." PreScale: ".$Result_PreScale, 0);
					// Mode 1 in Sleep setzen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 0, "Value" => 16)));
					If (!$Result) {
						$this->SendDebug("Setup", "Ausfuehrung in Sleep setzen fehlerhaft!", 0);
					}
					IPS_Sleep(10);
					// Prescale einstellen
					//$PreScale = round((25000000 / (4096 * $this->ReadPropertyInteger("Frequency"))) - 1);
					$PreScale = 50;
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 254, "Value" => $PreScale)));
					If (!$Result) {
						$this->SendDebug("Setup", "Prescale setzen fehlerhaft!", 0);
					}
					// Mode 1 in Sleep zurücksetzen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 0, "Value" => 32)));
					If (!$Result) {
						$this->SendDebug("Setup", "Mode 1 setzen fehlerhaft!", 0);
					}
					// Mode 2 auf Ausgänge setzen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 1, "Value" => 4)));
					If (!$Result) {
						$this->SendDebug("Setup", "Mode 2 setzen fehlerhaft!", 0);
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
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCA9685_Read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => $Register)));
			if (($Result === NULL) OR ($Result < 0) OR ($Result > 65536)) {// Falls der Splitter einen Fehler hat und 'nichts' zurückgibt.
				$this->SetBuffer("ErrorCounter", ($this->GetBuffer("ErrorCounter") + 1));
				$this->SendDebug("GetOutput", "Keine gueltige Antwort: ".$Result, 0);
				IPS_LogMessage("GeCoS_RGBW", "GetOutput: Keine gueltige Antwort: ".$Result);
				If ($this->GetBuffer("ErrorCounter") <= 3) {
					$this->GetOutput($Register);
				}
			}
			else {
				$this->SendDebug("GetOutput", "Ergebnis: ".$Result, 0);
				$this->SetStatusVariables($Register, $Result);
				$this->SetBuffer("ErrorCounter", 0);
			}
		}
	}
	
	private function SetStatusVariables(Int $Register, Int $Value)
	{
		$ChannelArray = [0 => "R", 4 => "G", 8 => "B", 12=> "W"];
		$Intensity = $Value & 4095;
		$Status = !boolval($Value & 4096); 
		$Group = intval(($Register - 8) / 16) + 1;
		$Channel = ($Register - 8) - (($Group - 1) * 16);
		
		$this->SendDebug("SetStatusVariables", "Gruppe: ".$Group." Kanal: ".$ChannelArray[$Channel]." Intensitaet: ".$Intensity." Status: ".(int)$Status , 0);
		//$this->SendDebug("SetStatusVariables", "Intensitaet: ".$Intensity." Status: ".(int)$Status, 0);
		
		
		If ($Intensity <> GetValueInteger($this->GetIDForIdent("Intensity_".$ChannelArray[$Channel]."_".$Group))) {
			SetValueInteger($this->GetIDForIdent("Intensity_".$ChannelArray[$Channel]."_".$Group), $Intensity);
		}
		If ($ChannelArray[$Channel] == "W") {
			If ($Status <> GetValueBoolean($this->GetIDForIdent("Status_W_".$Group))) {
				SetValueBoolean($this->GetIDForIdent("Status_W_".$Group), $Status);
			}
		}
		else {
			If ($Status <> GetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group))) {
				SetValueBoolean($this->GetIDForIdent("Status_RGB_".$Group), $Status);
			}
		}
		// Farbrad setzen
		$Value_R = intval(255 / 4095 * GetValueInteger($this->GetIDForIdent("Intensity_R_".$Group)));
		$Value_G = intval(255 / 4095 * GetValueInteger($this->GetIDForIdent("Intensity_G_".$Group)));
		$Value_B = intval(255 / 4095 * GetValueInteger($this->GetIDForIdent("Intensity_B_".$Group)));
		SetValueInteger($this->GetIDForIdent("Color_RGB_".$Group), $this->RGB2Hex($Value_R, $Value_G, $Value_B));		
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
