<?
    // Klassendefinition
    class IPS2GPIO_AS3935 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 3);
		$this->RegisterPropertyInteger("DeviceBus", 1);	
		$this->RegisterPropertyInteger("Pin", -1);
		$this->SetBuffer("PreviousPin", -1);
		$this->RegisterPropertyInteger("MinNumLigh", 0);
		$this->RegisterPropertyInteger("NoiseFloorLevel", 2);
		$this->RegisterPropertyInteger("MaskDisturber", 0);
		$this->RegisterPropertyInteger("FrequencyDivisionRatio", 0);
		$this->RegisterPropertyInteger("AFEGain", 36);
		$this->RegisterPropertyInteger("WDTH", 1);
		$this->RegisterPropertyInteger("SREJ", 2);
		$this->RegisterPropertyInteger("TunCap", 0);
		
		// Profil anlegen
		$this->RegisterProfileInteger("IPS2GPIO.km", "Distance", "", " km", 0, 10000, 1);
		
		$this->RegisterProfileInteger("IPS2GPIO.interrupt", "Information", "", "", 0, 3, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.interrupt", 0, "kein", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.interrupt", 1, "Geräusch Level zu hoch", "Graph", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.interrupt", 2, "Störer detektiert", "Graph", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.interrupt", 3, "Blitz detektiert", "Electricity", -1);
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("LastInterrupt", "Letzte Meldung", "~UnixTimestamp", 10);
		$this->DisableAction("LastInterrupt");
		
		$this->RegisterVariableInteger("Interrupt", "Auslöser", "IPS2GPIO.interrupt", 20);
		$this->DisableAction("Interrupt");
		
		$this->RegisterVariableInteger("Distance", "Entfernung", "IPS2GPIO.km", 30);
           	$this->DisableAction("Distance");
		
		$this->RegisterVariableInteger("Energy", "Energie", "", 40);
           	$this->DisableAction("Energy");
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
		$arrayOptions[] = array("label" => "3 dez. / 0x03h", "value" => 3);
		$arrayOptions[] = array("label" => "4 dez. / 0x04h", "value" => 4);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		
		$arrayOptions = array();
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		foreach($DevicePorts AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );

		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt"); 
		
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
		
		// NF_LEV Byte 0x01 [6:4] Default 2
		$arrayElements[] = array("type" => "Label", "label" => "Geräuschpegel (uVrms) - (Outdoor/Indoor)"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "390/28", "value" => 0);
		$arrayOptions[] = array("label" => "630/45", "value" => 1);
		$arrayOptions[] = array("label" => "860/62", "value" => 2);
		$arrayOptions[] = array("label" => "1100/78", "value" => 3);
		$arrayOptions[] = array("label" => "1140/95", "value" => 4);
		$arrayOptions[] = array("label" => "1570/112", "value" => 5);
		$arrayOptions[] = array("label" => "1800/130", "value" => 6);
		$arrayOptions[] = array("label" => "2000/146", "value" => 7);
		$arrayElements[] = array("type" => "Select", "name" => "NoiseFloorLevel", "caption" => "Geräuschpegel", "options" => $arrayOptions );
		
		// AFE_GB Byte 0x00 [5:1] Default 18 (Indoor), 14 (Outdoor) (+ Bit [0] = 36/28)
		$arrayElements[] = array("type" => "Label", "label" => "Nutzung Default = Indoor"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Indoor", "value" => 36); // Default
		$arrayOptions[] = array("label" => "Outdoor", "value" => 28); 
		$arrayElements[] = array("type" => "Select", "name" => "AFEGain", "caption" => "Nutzung", "options" => $arrayOptions );
		
		// MASK_DIST byte 0x03 [5] Default = 0
		$arrayElements[] = array("type" => "Label", "label" => "Störer anzeigen Default = Ja"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Ja", "value" => 0);
		$arrayOptions[] = array("label" => "Nein", "value" => 1);
		$arrayElements[] = array("type" => "Select", "name" => "MaskDisturber", "caption" => "Anzeige", "options" => $arrayOptions );
		
		// WDTH Byte 0x01 [3:0] Default 1
		$arrayElements[] = array("type" => "Label", "label" => "Schwellwert (Watchdog Threshold) Default = 1"); 
		$arrayOptions = array();
		for ($i = 0; $i <= 10; $i++) {
			$arrayOptions[] = array("label" => $i, "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "WDTH", "caption" => "Schwellwert", "options" => $arrayOptions );
		
		// SREJ Byte 0x02 [3:0] Default = 2
		$arrayElements[] = array("type" => "Label", "label" => "Spitzen Ablehnung (Spike Rejection) Default = 2"); 
		$arrayOptions = array();
		for ($i = 0; $i <= 10; $i++) {
			$arrayOptions[] = array("label" => $i, "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "SREJ", "caption" => "Spitzen Ablehnung", "options" => $arrayOptions );
		
		// TUN_CAP Byte 0x08 [3:0] Default = 0
		$arrayElements[] = array("type" => "Label", "label" => "Interner Kondensator (pF) Default = 0pF"); 
		$arrayOptions = array();
		for ($i = 0; $i <= 15; $i++) {
			$arrayOptions[] = array("label" => ($i * 8)."pF", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "TunCap", "caption" => "Größe", "options" => $arrayOptions );
		
		// MIN_NUM_LIGH Byte 0x02 [5:4] Default = 0
		$arrayElements[] = array("type" => "Label", "label" => "Minimale Anzahl der Detektionen in den letzten 15 Minuten bevor ein Interrupt ausgelöst wird. Default = 1"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "1", "value" => 0);
		$arrayOptions[] = array("label" => "5", "value" => 1);
		$arrayOptions[] = array("label" => "9", "value" => 2);
		$arrayOptions[] = array("label" => "16", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "MinNumLigh", "caption" => "Anzahl", "options" => $arrayOptions );
		
		// LCO_FDIV Byte 0 [7:6] Default = 0
		$arrayElements[] = array("type" => "Label", "label" => "Frequenzteilungsverhältnis anpassen Default = 16"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "16", "value" => 0);
		$arrayOptions[] = array("label" => "32", "value" => 1);
		$arrayOptions[] = array("label" => "64", "value" => 2);
		$arrayOptions[] = array("label" => "128", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "FrequencyDivisionRatio", "caption" => "Teilungsverhältnis", "options" => $arrayOptions );
		
		 
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 

		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
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
		
		//ReceiveData-Filter setzen
		$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
		$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*))';
		$this->SetReceiveDataFilter($Filter);

		// Summary setzen
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		$this->SetSummary("DA: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." DB: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]." GPIO: ".$this->ReadPropertyInteger("Pin"));

		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$ResultI2C = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
								
				$ResultPin = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 0)));
				$this->SetBuffer("PreviousPin", $this->ReadPropertyInteger("Pin"));
				If (($ResultI2C == true) AND ($ResultPin == true)) {
					// Erste Messdaten einlesen
					$this->Setup();
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
			case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
					If (($data->Value == 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						SetValueInteger($this->GetIDForIdent("LastInterrupt"), time() );
						$this->GetOutput();
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						$this->GetOutput();
					}
			   	}
			   	break; 
			
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
	public function GetOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetOutput", "Ausfuehrung", 0);
			$tries = 10;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_AS3935_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Count" => 9 )));
				If ($Result < 0) {
					$this->SendDebug("GetOutput", "Fehler beim Lesen der Werte!", 0);
					$this->SetStatus(202);
					If ($tries < 5) {
						IPS_Sleep(100);
					}
				}
				else {
					$this->SendDebug("GetOutput", "Ergebnis: ".$Result, 0);
					
					If (is_array(unserialize($Result))) {
						$this->SetStatus(102);
						$Data = array();
						$Data = unserialize($Result);
						/*
						$PowerDown = $Data[1] & 1;
						$this->SendDebug("PowerDown", $PowerDown, 0);

						$AFEGainBoost = $Data[1] & 62;
						$this->SendDebug("AFEGainBoost", $AFEGainBoost, 0);

						$NoiseFloorLevel = $Data[2] & 112;
						$this->SendDebug("NoiseFloorLevel", $NoiseFloorLevel, 0);
						$WatchdogThreshold = $Data[2] & 15;
						$this->SendDebug("WatchdogThreshold", $WatchdogThreshold, 0);

						$MinNumLigh = $Data[3] & 48;
						$this->SendDebug("MinNumLigh", $MinNumLigh, 0);

						$LcoFdiv = $Data[4] & 192;
						$this->SendDebug("LcoFdiv", $LcoFdiv, 0);
						$MaskDisturber = $Data[4] & 32;
						$this->SendDebug("MaskDisturber", $MaskDisturber, 0);

						$LCO = $Data[9] & 128;
						$this->SendDebug("LCO", $LCO, 0);
						$SRCO = $Data[9] & 64;
						$this->SendDebug("SRCO", $SRCO, 0);
						$TRCO = $Data[9] & 32;
						$this->SendDebug("TRCO", $TRCO, 0);
						$Capacitor = $Data[9] & 15;
						$this->SendDebug("Capacitor", $Capacitor, 0);
						*/

						/*
						1 Noise Level to high
						4 Disturber Detected
						8 Lightning interrupt
						*/
						$InterruptAssociation = array(0 => 0, 1 => 1, 4 => 2, 8 => 3);
						$Interrupt = $Data[4] & 15;
						$this->SendDebug("Interrupt", $Interrupt, 0);
						SetValueInteger($this->GetIDForIdent("Interrupt"),  $InterruptAssociation[$Interrupt]);

						//If ($Interrupt == 8) {
						$this->SendDebug("Energie LSB", $Data[5], 0);
						$this->SendDebug("Energie MSB", $Data[6], 0);
						$this->SendDebug("Energie MMSB", ($Data[7] & 31), 0);
						$Energy = (($Data[7] & 31) << 16) | ($Data[6] << 8) | $Data[5] ;
						SetValueInteger($this->GetIDForIdent("Energy"), $Energy);
						$this->SendDebug("Energy", $Energy, 0);

						$Distance = $Data[8] & 63;
						SetValueInteger($this->GetIDForIdent("Distance"), $Distance);
						$this->SendDebug("Distance", $Distance, 0);
						//}
						break;
					}
					
				}
			$tries--;
			} while ($tries);  
		}
	}
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			$Register = array();
			$Register[0] = $this->ReadPropertyInteger("AFEGain");
			$Register[1] = ($this->ReadPropertyInteger("NoiseFloorLevel") << 4) | $this->ReadPropertyInteger("WDTH");
			$Register[2] = (3 << 6) | ($this->ReadPropertyInteger("NoiseFloorLevel") << 4) | $this->ReadPropertyInteger("SREJ");
			$Register[3] = ($this->ReadPropertyInteger("FrequencyDivisionRatio") << 6) | ($this->ReadPropertyInteger("MaskDisturber") << 5) | (0 << 4) | 0;
			$Register[8] = $this->ReadPropertyInteger("TunCap");

			foreach($Register AS $Key => $Value) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_AS3935_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $Key, "Value" => $Value)));
				If (!$Result) {
					$this->SendDebug("Setup", "Schreiben von Wert ".$Value." in Register ".$Key." nicht erfolgreich!", 0);
					$this->SetStatus(202);
				}
				else {
					$this->SetStatus(102);
				}
			}
		}
	}    
	
	public function Calibrate()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Calibrate", "Ausfuehrung", 0);
			// Zur Kalibrierung auffordern
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_AS3935_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 61, "Value" => 150)));
			If (!$Result) {
				$this->SendDebug("Calibrate", "Schreiben von Wert 150 in Register 61 nicht erfolgreich!", 0);
			}
			$Value = (1 << 4) | $this->ReadPropertyInteger("TunCap");
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_AS3935_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 8, "Value" => $Value)));
			If (!$Result) {
				$this->SendDebug("Calibrate", "Schreiben von Wert ".$Value." in Register 8 nicht erfolgreich!", 0);
				$this->SetStatus(202);
				return;
			}
			else {
				$this->SetStatus(102);
			}
			IPS_Sleep(2);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_AS3935_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 8, "Value" =>  $this->ReadPropertyInteger("TunCap"))));
			If (!$Result) {
				$this->SendDebug("Calibrate", "Schreiben von Wert ".$this->ReadPropertyInteger("TunCap")." in Register 8 nicht erfolgreich!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
			}
		}
	}
	
	public function Reset()
	{ 
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Reset", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_AS3935_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 60, "Value" => 150)));
			If (!$Result) {
				$this->SendDebug("Reset", "Schreiben von Wert 150 in Register 60 nicht erfolgreich!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
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
