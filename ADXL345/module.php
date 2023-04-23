<?
    // Klassendefinition
    class ADXL345 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 83);
		$this->RegisterPropertyInteger("DeviceBus", 1);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'ADXL345_Measurement($_IPS["TARGET"]);');
		$this->RegisterPropertyBoolean("FullResolution", true);
		$this->RegisterPropertyInteger("RangeSetting", 0);
		$this->RegisterPropertyInteger("DataRate", 10);
		
		$this->RegisterAttributeFloat("xOffset", 0.0);
		$this->RegisterAttributeFloat("yOffset", 0.0);
		$this->RegisterAttributeFloat("zOffset", 0.0);
        }
	    
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "I²C-Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "83 dez. / 0x53h", "value" => 83);
		$arrayOptions[] = array("label" => "29 dez. / 0x1Dh", "value" => 29);

		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		$arrayOptions = array();
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		foreach($DevicePorts AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );

		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Messzyklus", "caption" => "Sekunden", "minimum" => 0);
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 

		$arrayElements[] = array("type" => "CheckBox", "name" => "FullResolution", "caption" => "Full Resolution"); 
		
		$arrayElements[] = array("type" => "Label", "label" => "Range Setting (g)"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "±2", "value" => 0);
		$arrayOptions[] = array("label" => "±4", "value" => 1);
		$arrayOptions[] = array("label" => "±8", "value" => 2);
		$arrayOptions[] = array("label" => "±16", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "RangeSetting", "caption" => "Range Setting", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Data Rate (Hz)"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "3200", "value" => 15);
		$arrayOptions[] = array("label" => "1600", "value" => 14);
		$arrayOptions[] = array("label" => "800", "value" => 13);
		$arrayOptions[] = array("label" => "400", "value" => 12);
		$arrayOptions[] = array("label" => "200", "value" => 11);
		$arrayOptions[] = array("label" => "100", "value" => 10);
		$arrayOptions[] = array("label" => "50", "value" => 9);
		$arrayOptions[] = array("label" => "25", "value" => 8);
		$arrayOptions[] = array("label" => "12.5", "value" => 7);
		$arrayOptions[] = array("label" => "6.25", "value" => 6);
		$arrayOptions[] = array("label" => "3.13", "value" => 5);
		$arrayOptions[] = array("label" => "1.56", "value" => 4);
		$arrayOptions[] = array("label" => "0.78", "value" => 3);
		$arrayOptions[] = array("label" => "0.39", "value" => 2);
		$arrayOptions[] = array("label" => "0.20", "value" => 1);
		$arrayOptions[] = array("label" => "0.10", "value" => 0);
		$arrayElements[] = array("type" => "Select", "name" => "DataRate", "caption" => "Date Rate", "options" => $arrayOptions );
		
		$arrayActions = array(); 
		$arrayActions[] = array("type" => "Label", "caption" => "Test Center"); 
		$arrayActions[] = array("type" => "TestCenter", "name" => "TestCenter");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}  
	    
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Profil anlegen
		$this->RegisterProfileFloat("ADXL345.acceleration", "Move", "", "g", 0, 100, 0.01, 2);
		$this->RegisterProfileFloat("ADXL345.degrees", "Winddirection", "", "°", 0, 360, 0.01, 2);
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("ChipID", "Chip ID", "", 10);
		$this->DisableAction("ChipID");
		
		$this->RegisterVariableBoolean("Calibration", "Kalibrierung", "~Switch", 20);
		$this->EnableAction("Calibration");
		
		$this->RegisterVariableFloat("X_Axis", "X-Achse", "ADXL345.acceleration", 30);
		
		$this->RegisterVariableFloat("Y_Axis", "Y-Achse", "ADXL345.acceleration", 40);
		
		$this->RegisterVariableFloat("Z_Axis", "Z-Achse", "ADXL345.acceleration", 50);
		
		$this->RegisterVariableFloat("X_Angle", "X-Winkel", "ADXL345.degrees", 35);
		
		$this->RegisterVariableFloat("Y_Angle", "Y-Winkel", "ADXL345.degrees", 45);
		
		$this->RegisterVariableFloat("Z_Angle", "Z-Winkel", "ADXL345.degrees", 55);
		
		// Summary setzen
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		$this->SetSummary("DA: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." DB: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]);

		// ReceiveData-Filter setzen
		$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
		$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
		$this->SetReceiveDataFilter($Filter);

		If ((IPS_GetKernelRunlevel() == KR_READY) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					// Parameterdaten zum Baustein senden
					$this->Setup();
					// Erste Messdaten einlesen
					$this->Measurement();
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				If ($this->GetStatus() <> 104) {
					$this->SetStatus(104);
				}
			}	
		}
		else {
			$this->SetTimerInterval("Messzyklus", 0);
			If ($this->GetStatus() <> 104) {
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
  		switch($Ident) {
			case "Calibration":
				$this->Calibration();
				break;
			default:
			    throw new Exception("Invalid Ident");
	    	}
	}
	    
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case IPS_KERNELSTARTED:
				// IPS_KERNELSTARTED
				$this->ApplyChanges();
				break;
		}
    	}     
	    
	// Beginn der Funktionen
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			// Lesen der ChipID
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_ADXL345_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00)));
			If ($Result < 0) {
				$this->SendDebug("Setup", "Fehler beim Einlesen der Chip ID", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$this->SetValue("ChipID", $Result);
				If ($Result <> 0xE5) {
					$this->SendDebug("Setup", "Laut Chip ID ist es kein ADXL345!", 0);
				}
			}
			
			// pi.i2c_write_byte_data(h, 0x2d, 0)  # POWER_CTL reset.
   			// pi.i2c_write_byte_data(h, 0x2d, 8)  # POWER_CTL measure.
   			// pi.i2c_write_byte_data(h, 0x31, 0)  # DATA_FORMAT reset.
   			// pi.i2c_write_byte_data(h, 0x31, 11) # DATA_FORMAT full res +/- 16g.
			
			$POWER_CTL = 0; // reset
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_ADXL345_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x2d, "Value" => $POWER_CTL)));
			If (!$Result) {
				$this->SendDebug("Setup", "POWER_CTL reset setzen fehlerhaft!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			
			$POWER_CTL = 8; // measure
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_ADXL345_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x2d, "Value" => $POWER_CTL)));
			If (!$Result) {
				$this->SendDebug("Setup", "POWER_CTL measure setzen fehlerhaft!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			
			$DATA_FORMAT = 0; // reset
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_ADXL345_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x31, "Value" => $DATA_FORMAT)));
			If (!$Result) {
				$this->SendDebug("Setup", "DATA_FORMAT reset fehlerhaft!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			
			$RangeSetting = $this->ReadPropertyInteger("RangeSetting");
			$Full_Res = $this->ReadPropertyBoolean("FullResolution");
			$DATA_FORMAT = ($Full_Res << 3)|$RangeSetting;
			//$DATA_FORMAT = 11; // resolution
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_ADXL345_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x31, "Value" => $DATA_FORMAT)));
			If (!$Result) {
				$this->SendDebug("Setup", "DATA_FORMAT setzen fehlerhaft!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			
			$DataRate = $this->ReadPropertyInteger("DataRate");
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_ADXL345_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x2C, "Value" => $DataRate)));
			If (!$Result) {
				$this->SendDebug("Setup", "Data Rate setzen fehlerhaft!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
		}
	}
	    
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Measurement", "Ausfuehrung", 0);
			
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_ADXL345_read_block", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x32, "Count" => 6)));
				If ($Result < 0) {
					$this->SendDebug("Measurement", "Einlesen der Werte fehlerhaft!", 0);
					If ($this->GetStatus() <> 202) {
						$this->SetStatus(202);
					}
				}
				else {
					
					If (is_array(unserialize($Result))) {
						If ($this->GetStatus() <> 102) {
							$this->SetStatus(102);
						}
						$DataArray = array();
						// $DataArray[1] - X-Axis Data 0
						// $DataArray[2] - X-Axis Data 1
						// $DataArray[3] - Y-Axis Data 0
						// $DataArray[4] - Y-Axis Data 1
						// $DataArray[5] - Z-Axis Data 0
						// $DataArray[6] - Z-Axis Data 1
						$DataArray = unserialize($Result);
						// Ergebnis sichern
						$xRaw = (($DataArray[2] & 0xff) << 8) | ($DataArray[1] & 0xff);
						$yRaw = (($DataArray[4] & 0xff) << 8) | ($DataArray[3] & 0xff);
						$zRaw = (($DataArray[6] & 0xff) << 8) | ($DataArray[5] & 0xff);
						
						$this->SendDebug("Measurement", "Roh-Ergebnis x: ".$xRaw." y: ".$yRaw." z: ".$zRaw, 0);
						
						If ($this->ReadPropertyBoolean("FullResolution") == true) {
							$RangeDevisor = 256;
						}
						else {
							$RangeSetting = $this->ReadPropertyInteger("RangeSetting");
							$RangeFactorArray = [256, 128, 64, 32];
							$RangeDevisor = $RangeFactorArray[$RangeSetting];
						}
						
						$xRaw = $this->bin16dec($xRaw) / $RangeDevisor;
						$yRaw = $this->bin16dec($yRaw) / $RangeDevisor;
						$zRaw = $this->bin16dec($zRaw) / $RangeDevisor;

						$this->SendDebug("Measurement", "Ergebnis nach Zweierkomplement x: ".$xRaw." y: ".$yRaw." z: ".$zRaw, 0);
						
						// Korrektur der Werte (ToDo)
						
						$xCorr = $xRaw;
						$yCorr = $yRaw;
						$zCorr = $zRaw;
						
						$this->SetValue("X_Axis", $xCorr);
						$this->SetValue("Y_Axis", $yCorr);
						$this->SetValue("Z_Axis", $zCorr);
						
						
						// Berechnung der Winkel
						$xCorr = min(1, Max($xCorr, -1));
						$xAngle = (asin($xCorr)) * 57.296;
						
						$yCorr = min(1, Max($yCorr, -1));
						$yAngle = (asin($yCorr)) * 57.296;
						
						$zCorr = min(1, Max($zCorr, -1));
						$zAngle = (asin($zCorr)) * 57.296;
						
						$this->SetValue("X_Angle", $xAngle);
						$this->SetValue("Y_Angle", $yAngle);
						$this->SetValue("Z_Angle", $zAngle);
   
					}
				}
			$tries--;
			} while ($tries);  
		}
	}
	
	private function Calibration()
	{
	    	If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Calibration", "Ausfuehrung", 0);
			$this->SetValue("Calibration", true);
			
			// Die aktuellen Offset-Werte einlesen
			$xOffset = $this->ReadAttributeFloat("xOffset");
			$yOffset = $this->ReadAttributeFloat("yOffset");
			$zOffset = $this->ReadAttributeFloat("zOffset");
			
			$this->SendDebug("Calibration", "xOffset: ".$xOffset, 0);
			
			// Die aktuellen um den Offset korrgierten Werte einlesen
			$xCorr = $this->getValue("X_Axis");
			$yCorr = $this->getValue("Y_Axis");
			$zCorr = $this->getValue("Z_Axis");
			
			$this->SendDebug("Calibration", "xCorr: ".$xCorr, 0);
			
			// Den um den Offset bereinigten Wert berechnen
			$xRaw = $xCorr + $xOffset;
			$yRaw = $yCorr + $yOffset;
			$zRaw = $zCorr + $zOffset;
			
			$this->SendDebug("Calibration", "xRaw: ".$xRaw, 0);
			
			// Die Differenz zwischen dem Roh-Wert und 0 berechnen
			$xNewOffset = 0 - $xRaw;
			$yNewOffset = 0 - $yRaw;
			$zNewOffset = 0 - $zRaw;
			
			$this->SendDebug("Calibration", "xNewOffset: ".$xNewOffset, 0);
						
			// Den neuen Wert sichern
			$this->SetAttributeFloat("xOffset", $xNewOffset);
			$this->SetAttributeFloat("yOffset", $yNewOffset);
			$this->SetAttributeFloat("zOffset", $zNewOffset);
			
			
			$this->SetValue("Calibration", false);
		}
	return;
	}    
	    
	private function bin16dec($dec) 
	{
	    	// converts 16bit binary number string to integer using two's complement
	    	$BinString = decbin($dec);
		$DecNumber = bindec($BinString) & 0xFFFF; // only use bottom 16 bits
	    	If (0x8000 & $DecNumber) {
			$DecNumber = - (0x010000 - $DecNumber);
	    	}
	return $DecNumber;
	}  
	    
	private function bin8dec($dec) 
	{
	    	// converts 8bit binary number string to integer using two's complement
	    	$BinString = decbin($dec);
		$DecNumber = bindec($BinString) & 0xFF; // only use bottom 16 bits
	    	If (0x80 & $DecNumber) {
			$DecNumber = - (0x0100 - $DecNumber);
	    	}
	return $DecNumber;
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
	    
	private function RegisterProfileFloat($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize, $Digits)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 2);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 2)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	        IPS_SetVariableProfileDigits($Name, $Digits);
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
}
?>
