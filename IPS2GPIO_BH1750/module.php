<?
    // Klassendefinition
    class IPS2GPIO_BH1750 extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Messzyklus", 0);
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 35);
		$this->RegisterPropertyInteger("DeviceBus", 1);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterPropertyInteger("Resulution", 16);
		$this->RegisterPropertyInteger("Sensitivity", 69);
 	    	$this->RegisterPropertyBoolean("Logging", false);
		$this->RegisterPropertyInteger("HysteresisOn", 100);
		$this->RegisterPropertyInteger("HysteresisOff", 0);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GBH_Measurement($_IPS["TARGET"]);');
		
		// Profil anlegen
	    	$this->RegisterProfileFloat("IPS2GPIO.lx", "Bulb", "", " lx", 0, 1000000, 0.1, 2);
		
		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Illuminance", "Illuminance", "IPS2GPIO.lx", 10);
		$this->DisableAction("Illuminance");
		
		$this->RegisterVariableBoolean("Hysteresis", "Hysteresis", "~Switch", 20);
		$this->DisableAction("Hysteresis");
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
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "35 dez. / 0x23h", "value" => 35);
		$arrayOptions[] = array("label" => "92 dez. / 0x5Ch", "value" => 92);

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
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "An den folgenden Werten muss in der Regel nichts verändert werden");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "L-Resulution Mode", "value" => 19);
		$arrayOptions[] = array("label" => "H-Resulution Mode", "value" => 16);
		$arrayOptions[] = array("label" => "H-Resulution Mode 2", "value" => 17);
		$arrayElements[] = array("type" => "Select", "name" => "Resulution", "caption" => "Auflösung", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "Wert muss zwischen 31 und 254 liegen (Default 69)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Sensitivity",  "caption" => "Sensivität"); 
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "Logging", "caption" => "Logging aktivieren"); 
		$arrayElements[] = array("type" => "Label", "label" => "Optional: Definition einer Hysterese Variablen");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "HysteresisOn",  "caption" => "Ein (lx)"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "HysteresisOff",  "caption" => "Aus (lx)"); 
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Hinweise:");
		$arrayElements[] = array("type" => "Label", "label" => "- die Device Adresse lautet 35 dez (0x23h) bei ADDR an GND");
		$arrayElements[] = array("type" => "Label", "label" => "- die Device Adresse lautet 92 dez (0x5Ch) bei ADDR an 5V");
		$arrayElements[] = array("type" => "Label", "label" => "- die I2C-Nutzung muss in der Raspberry Pi-Konfiguration freigegeben werden (sudo raspi-config -> Advanced Options -> I2C Enable = true)");
		$arrayElements[] = array("type" => "Label", "label" => "- die korrekte Nutzung der GPIO ist zwingend erforderlich (GPIO-Nr. 0/1 nur beim Raspberry Pi Model B Revision 1, alle anderen GPIO-Nr. 2/3)");
		$arrayElements[] = array("type" => "Label", "label" => "- auf den korrekten Anschluss von SDA/SCL achten");
		
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

		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// Logging setzen
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Illuminance"), $this->ReadPropertyBoolean("Logging"));
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
					
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					// Setup
					$this->Setup();
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
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BH1750_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Value" => $this->ReadPropertyInteger("Resulution") )));
			If (!$Result) {
				$this->SendDebug("Measurement", "Fehler beim Schreiben der Aufloesung!", 0);
				$this->SetStatus(202);
				return;
			}
			IPS_Sleep(180);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BH1750_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $this->ReadPropertyInteger("DeviceAddress"))));
			$this->SendDebug("Measurement", "Wert: ".$Result, 0);
			If ($Result < 0) {
				$this->SendDebug("Measurement", "Fehler beim Lesen des Wertes!", 0);
				$this->SetStatus(202);
				return;
			}
			else {
				$this->SetStatus(102);
				If ($this->ReadPropertyInteger("Resulution") == 19) {
					// LR
					$Lux = (($Result & 0xff00)>>8) | (($Result & 0x00ff)<<8);
				}
				elseif ($this->ReadPropertyInteger("Resulution") == 16) {
					// HR
					$Lux = (($Result & 0xff00)>>8) | (($Result & 0x00ff)<<8);
				}
				elseif ($this->ReadPropertyInteger("Resulution") == 17) {
					// HR 2
					$Lux = (($Result & 0xff00)>>8) | (($Result & 0x00ff)<<8);
					$Lux = (($Lux & 1) * 0.5) + ($Lux >> 1);
				}

				SetValueFloat($this->GetIDForIdent("Illuminance"), max(0, $Lux / 1.2));
				// Hysteres Variablen setzen
				If ($Lux >= $this->ReadPropertyInteger("HysteresisOn")) {
					SetValueBoolean($this->GetIDForIdent("Hysteresis"), true);
				}
				elseif ($Lux <= $this->ReadPropertyInteger("HysteresisOff")) {
					SetValueBoolean($this->GetIDForIdent("Hysteresis"), false);
				}
			}
		}
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

	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			// Einschalten
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BH1750_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Value" => hexdec("01"))));
			If (!$Result) {
				$this->SendDebug("Setup", "Fehler beim Einschalten!", 0);
				$this->SetStatus(202);
				return;
			}
			IPS_Sleep(100);
			// Sensivität setzen
			$MTreg = max(31, min($this->ReadPropertyInteger("Resulution"), 254));
			// High bit MTreg 01000 + Bit 6,7,8
			$HighMTreg =  (8 << 3) | ($MTreg >> 5);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BH1750_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Value" => $HighMTreg)));
			If (!$Result) {
				$this->SendDebug("Setup", "Fehler beim Schreiben des HighMTreg!", 0);
				$this->SetStatus(202);
				return;
			}
			// Low bit MTreg 011 + Bit 1, 2, 3 ,4, 5
			$LowMTreg =  ($MTreg & 31) | 96;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BH1750_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Value" => $LowMTreg)));
			If (!$Result) {
				$this->SendDebug("Setup", "Fehler beim Schreiben des LowMTreg!", 0);
				$this->SetStatus(202);
				return;
			}
			// Messwerterfassung setzen
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_BH1750_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Value" => $this->ReadPropertyInteger("Resulution") )));
			If (!$Result) {
				$this->SendDebug("Setup", "Fehler beim Schreiben des Auflösung!", 0);
				$this->SetStatus(202);
				return;
			}
		}
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
