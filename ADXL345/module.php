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
		$this->RegisterPropertyInteger("RangeSetting", 0);
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
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 

		$arrayElements[] = array("type" => "Label", "label" => "Range Setting"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "±2 g", "value" => 0);
		$arrayOptions[] = array("label" => "±4 g", "value" => 1);
		$arrayOptions[] = array("label" => "±8 g", "value" => 2);
		$arrayOptions[] = array("label" => "±16 g", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "RangeSetting", "caption" => "Range Setting", "options" => $arrayOptions );
		
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
		
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("ChipID", "Chip ID", "", 10);
		$this->DisableAction("ChipID");
		
		$this->RegisterVariableBoolean("Calibration", "Kalibrierung", "~Switch", 20);
		$this->EnableAction("Calibration");
		
		$this->RegisterVariableFloat("X_Axis", "X-Achse", "", 30);
		
		$this->RegisterVariableFloat("Y_Axis", "Y-Achse", "", 40);
		
		$this->RegisterVariableFloat("Z_Axis", "Z-Achse", "", 50);
		
		
		
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
				//$this->SetLEDState($Value);
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
			
			// Minimalkombination nach dem Beispiel von Boris
			$DATA_FORMAT = 0x08;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_ADXL345_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x31, "Value" => $DATA_FORMAT)));
			If (!$Result) {
				$this->SendDebug("Setup", "DATA_FORMAT setzen fehlerhaft!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			
			/*
			$osrs_t = $this->ReadPropertyInteger("OSRS_T"); // Oversampling Measure temperature x1, x2, x4, x8, x16 (dec: 0 (off), 1, 2, 3, 4)
			$osrs_p = $this->ReadPropertyInteger("OSRS_P"); // Oversampling Measure pressure x1, x2, x4, x8, x16 (dec: 0 (off), 1, 2, 3, 4)
			$osrs_h = $this->ReadPropertyInteger("OSRS_H"); // Oversampling Measure humidity x1, x2, x4, x8, x16 (dec: 0 (off), 1, 2, 3, 4)
			$mode = $this->ReadPropertyInteger("Mode"); // 0 = Power Off (Sleep Mode), x01 und x10 Force Mode, 11 Normal Mode
			$t_sb = $this->ReadPropertyInteger("SB_T"); // StandBy Time: dec: 0 (0.5ms) - 5 (1000ms), 6 (10ms), 7 (20ms)
			$filter = $this->ReadPropertyInteger("IIR_Filter"); // IIR-Filter 0-> off - 2, 4, 8, 16 (dec: 0 (off) - 4)
			$spi3w_en = 0;

			$ctrl_meas_reg = (($osrs_t << 5)|($osrs_p << 2)|$mode);
			$config_reg = (($t_sb << 5)|($filter << 2)|$spi3w_en);
			$ctrl_hum_reg = $osrs_h;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME280_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F2"), "Value" => $ctrl_hum_reg)));
			If (!$Result) {
				$this->SendDebug("Setup", "ctrl_hum_reg setzen fehlerhaft!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME280_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F4"), "Value" => $ctrl_meas_reg)));
			If (!$Result) {
				$this->SendDebug("Setup", "ctrl_meas_reg setzen fehlerhaft!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BME280_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("F5"), "Value" => $config_reg)));
			If (!$Result) {
				$this->SendDebug("Setup", "config_reg setzen fehlerhaft!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			*/
			
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
						$xAxis = (($DataArray[2] & 0xff) << 8) | ($DataArray[1] & 0xff);
						$yAxis = (($DataArray[4] & 0xff) << 8) | ($DataArray[3] & 0xff);
						$zAxis = (($DataArray[6] & 0xff) << 8) | ($DataArray[5] & 0xff);
						
						$xAxis = $this->bin16dec($xAxis) / 256.0;
						$yAxis = $this->bin16dec($yAxis) / 256.0;
						$zAxis = $this->bin16dec($zAxis) / 256.0;

						$this->SendDebug("Measurement", "Ergebnis x: ".$xAxis." y: ".$yAxis." z: ".$zAxis, 0);
						$this->SetValue("X_Axis", $xAxis);
						$this->SetValue("Y_Axis", $yAxis);
						$this->SetValue("Z_Axis", $zAxis);
						
						
						break;
					}
				}
			$tries--;
			} while ($tries);  
		}
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
