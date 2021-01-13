<?
    // Klassendefinition
    class IPS2GPIO_MCP3424 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 104);
		$this->RegisterPropertyInteger("DeviceBus", 1);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
		for ($i = 0; $i <= 3; $i++) {
			$this->RegisterPropertyInteger("Resolution_".$i, 0);
			$this->RegisterPropertyInteger("Amplifier_".$i, 0);
			$this->RegisterPropertyBoolean("Active_".$i, true);
		}
            	$this->RegisterTimer("Messzyklus", 0, 'I2GAD2_Measurement($_IPS["TARGET"]);');
		
		// Profil anlegen
	    	$this->RegisterProfileFloat("IPS2GPIO.mV", "Electricity", "", " mV", -100000, +100000, 0.1, 3);
		
		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Channel_1", "Kanal 1", "IPS2GPIO.mV", 10);
          	$this->DisableAction("Channel_1");
		
		$this->RegisterVariableFloat("Channel_2", "Kanal 2", "IPS2GPIO.mV", 20);
          	$this->DisableAction("Channel_2");
		
		$this->RegisterVariableFloat("Channel_3", "Kanal 3", "IPS2GPIO.mV", 30);
          	$this->DisableAction("Channel_3");
		
		$this->RegisterVariableFloat("Channel_4", "Kanal 4", "IPS2GPIO.mV", 40);
          	$this->DisableAction("Channel_4");
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "I²C-Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "104 dez. / 0x68h", "value" => 104);
		$arrayOptions[] = array("label" => "106 dez. / 0x6Ah", "value" => 106);
		$arrayOptions[] = array("label" => "108 dez. / 0x6Ch", "value" => 108);
		$arrayOptions[] = array("label" => "110 dez. / 0x6Eh", "value" => 110);
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		$arrayOptions = array();
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		foreach($DevicePorts AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 15 sek -> Minimum)"); 
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Messzyklus (sek)");
		
		
		$arrayOptionsResolution = array();
		$arrayOptionsResolution[] = array("label" => "12 Bit", "value" => 0);
		$arrayOptionsResolution[] = array("label" => "14 Bit", "value" => 1);
		$arrayOptionsResolution[] = array("label" => "16 Bit", "value" => 2);
		$arrayOptionsResolution[] = array("label" => "18 Bit", "value" => 3);
		
		$arrayOptionsAmplifier = array();
		$arrayOptionsAmplifier[] = array("label" => "1x", "value" => 0);
		$arrayOptionsAmplifier[] = array("label" => "2x", "value" => 1);
		$arrayOptionsAmplifier[] = array("label" => "4x", "value" => 2);
		$arrayOptionsAmplifier[] = array("label" => "8x", "value" => 3);
		
		
		for ($i = 0; $i <= 3; $i++) {
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
			$arrayElements[] = array("type" => "Label", "label" => "Optionen Kanal ".($i + 1));
			$arrayElements[] = array("type" => "Label", "label" => "Auflösung des Kanals wählen (Default 12 Bit)");
			$arrayElements[] = array("name" => "Active_".$i, "type" => "CheckBox",  "caption" => "Aktiv"); 
			$arrayElements[] = array("type" => "Select", "name" => "Resolution_".$i, "caption" => "Auflösung", "options" => $arrayOptionsResolution );
			$arrayElements[] = array("type" => "Label", "label" => "Verstärkung des Kanals wählen (Default 1x)");
			$arrayElements[] = array("type" => "Select", "name" => "Amplifier_".$i, "caption" => "Verstärkung", "options" => $arrayOptionsAmplifier );
		}
				
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		// Summary setzen
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		$this->SetSummary("DA: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." DB: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]);

		// ReceiveData-Filter setzen
		$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
		$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
		$this->SetReceiveDataFilter($Filter);
		
		$MeasurementData = array();
		$this->SetBuffer("MeasurementData", serialize($MeasurementData));
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					// Erste Messdaten einlesen
					$this->Measurement();
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				$this->SetStatus(104);
			}	
		}
		else {
			$this->SetTimerInterval("Messzyklus", 0);
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
					//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
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
	// Beginn der Funktionen
	// Führt eine Messung aus
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Measurement", "Ausfuehrung", 0);
			// Messwerterfassung setzen
			$i = 0;
			for ($i = 0; $i <= 3; $i++) {
				If ($this->ReadPropertyBoolean("Active_".$i) == true) {
					$Configuration = ($i << 5) | (1 << 4) | ($this->ReadPropertyInteger("Resolution_".$i) << 2) | $this->ReadPropertyInteger("Amplifier_".$i);
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP3424_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Value" => $Configuration)));
					If (!$Result) {
						$this->SendDebug("Measurement", "Setzen der Konfiguration Port ".$i." fehlerhaft!", 0);
						$this->SetStatus(202);
						return;
					}
					IPS_Sleep(320);
					$MeasurementData = array();
					If ($this->ReadPropertyInteger("Resolution_".$i) <= 2) { 
						$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP3424_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 3)));
						If ($Result < 0) {
							$this->SendDebug("Measurement", "Einlesen der Werte fehlerhaft!", 0);
							$this->SetStatus(202);
							return;
						}
						else {
							$this->SetStatus(102);
							$MeasurementData = unserialize($Result);
						}
					}
					elseif ($this->ReadPropertyInteger("Resolution_".$i) == 3) {
						$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP3424_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $this->ReadPropertyInteger("DeviceAddress"), "Count" => 4)));
						If ($Result < 0) {
							$this->SendDebug("Measurement", "Einlesen der Werte fehlerhaft!", 0);
							$this->SetStatus(202);
							return;
						}
						else {
							$this->SetStatus(102);
							$MeasurementData = unserialize($Result);
						}
					}
				
					// Auslesen des Konfigurations-Registers
					$Configuration = $MeasurementData[count($MeasurementData)];
					$Amplifier = ($Configuration & 3);
					$Resolution = ($Configuration & 12) >> 2;
					$Channel = ($Configuration & 96) >> 5;
					$ReadyBit = ($Configuration & 128) >> 7;
					//IPS_LogMessage("IPS2GPIO MCP", "Anzahl Daten: ".count($MeasurementData)." Verst: ".$Amplifier." Aufl:: ".$Resolution." RDY:".$ReadyBit);
					If ($ReadyBit == false) {
						switch ($Resolution) {
							case 0:	
								//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 12 Bit");
								$SignBit = ($MeasurementData[1] & 8) >> 3;
								$Value = (($MeasurementData[1] & 15) << 8) | $MeasurementData[2];
								If ($SignBit == 0) {
									$Value = $Value;
								}
								else {
									$Value = -($this->bitflip($Value));
								}
								break;
							case 1:
								//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 14 Bit");
								$SignBit = ($MeasurementData[1] & 32) >> 5;
								$Value = (($MeasurementData[1] & 63) << 8) | $MeasurementData[2];
								If ($SignBit == 0) {
									$Value = $Value * 0.25;
								}
								else {
									$Value = -($this->bitflip($Value)) * 0.25;
								}
								break;
							case 2:	
								//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 16 Bit");
								$SignBit = ($MeasurementData[1] & 128) >> 7;
								$Value = (($MeasurementData[1] & 255) << 8) | $MeasurementData[2];
								If ($SignBit == 0) {
									$Value = $Value * 0.0625;
								}
								else {
									$Value = -($this->bitflip($Value)) * 0.0625;
								}
								break;
							case 3:
								//IPS_LogMessage("IPS2GPIO MCP", "Auflösung 18 Bit");
								$SignBit = ($MeasurementData[1] & 2) >> 1;
								$Value = (($MeasurementData[1] & 3) << 16) | ($MeasurementData[2] << 8) | $MeasurementData[3];
								If ($SignBit == 0) {
									$Value = $Value * 0.015625;
								}
								else {
									$Value = -($this->bitflip($Value)) * 0.015625;
								}
								break;	
						}	
						SetValueFloat($this->GetIDForIdent("Channel_".($Channel + 1)), $Value);
					}
				}
			}
		}
	}
	        
	private function bitflip($Value)
	{
	   	// Umwandlung in einen Binär-String
		$bin = decbin($Value);
	   	$not = "";
	   	// Umstellung der Binär-Strings
		for ($i = 0; $i < strlen($bin); $i++)
	   		{
	      		if($bin[$i] == 0) { $not .= '1'; }
	      		if($bin[$i] == 1) { $not .= '0'; }
	   	}
		// Rückgabe als Integer
	return bindec($not);
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
