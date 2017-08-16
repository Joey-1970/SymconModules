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
		$this->RegisterPropertyInteger("MinNumLigh", 0);
		$this->RegisterPropertyInteger("NoiseFloorLevel", 0);
		$this->RegisterPropertyInteger("FrequencyDivisionRatio", 0);
		$this->RegisterPropertyInteger("AFEGain", 36);
		$this->RegisterPropertyInteger("WDTH", 0);
		$this->RegisterPropertyInteger("SREJ", 2);
		$this->RegisterPropertyInteger("TunCap", 0);
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
 		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "3 dez. / 0x03h", "value" => 3);
		$arrayOptions[] = array("label" => "4 dez. / 0x04h", "value" => 4);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "I²C-Bus 0", "value" => 0);
		$arrayOptions[] = array("label" => "I²C-Bus 1", "value" => 1);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 0", "value" => 3);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 1", "value" => 4);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 2", "value" => 5);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 3", "value" => 6);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 4", "value" => 7);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 5", "value" => 8);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 6", "value" => 9);
		$arrayOptions[] = array("label" => "MUX I²C-Bus 7", "value" => 10);
		
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );

		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "ungesetzt", "value" => -1);
		for ($i = 0; $i <= 27; $i++) {
			$arrayOptions[] = array("label" => $i, "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		
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
		
		$arrayElements[] = array("type" => "Label", "label" => "Nutzung"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Indoor", "value" => 36);
		$arrayOptions[] = array("label" => "Outdoor", "value" => 28);
		$arrayElements[] = array("type" => "Select", "name" => "AFEGain", "caption" => "Nutzung", "options" => $arrayOptions );
	
		$arrayElements[] = array("type" => "Label", "label" => "Schwellwert (Watchdog Threshold)"); 
		$arrayOptions = array();
		for ($i = 0; $i <= 10; $i++) {
			$arrayOptions[] = array("label" => $i, "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "WDTH", "caption" => "Schwellwert", "options" => $arrayOptions );
	
		$arrayElements[] = array("type" => "Label", "label" => "Spitzen Ablehnung (Spike Rejection)"); 
		$arrayOptions = array();
		for ($i = 0; $i <= 10; $i++) {
			$arrayOptions[] = array("label" => $i, "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "SREJ", "caption" => "Spitzen Ablehnung", "options" => $arrayOptions );
	
		$arrayElements[] = array("type" => "Label", "label" => "Interner Kondensator (pF)"); 
		$arrayOptions = array();
		for ($i = 0; $i <= 10; $i++) {
			$arrayOptions[] = array("label" => ($i * 8)."pF", "value" => $i);
		}
		$arrayElements[] = array("type" => "Select", "name" => "TunCap", "caption" => "Größe", "options" => $arrayOptions );
	
		$arrayElements[] = array("type" => "Label", "label" => "Minimale Anzahl der Detektionen in den letzten 15 Minuten bevor ein Interrupt ausgelöst wird"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "1", "value" => 0);
		$arrayOptions[] = array("label" => "5", "value" => 1);
		$arrayOptions[] = array("label" => "9", "value" => 2);
		$arrayOptions[] = array("label" => "16", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "MinNumLigh", "caption" => "Anzahl", "options" => $arrayOptions );

		$arrayElements[] = array("type" => "Label", "label" => "Frequenzteilungsverhältnis anpassen"); 
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
		// Device Adresse prüfen
	    	If (($this->ReadPropertyInteger("DeviceAddress") < 0) OR ($this->ReadPropertyInteger("DeviceAddress") > 128)) {
	    		IPS_LogMessage("IPS2GPIO MCP3424","I2C-Device Adresse in einem nicht definierten Bereich!");  
	    	}
	    	// Profil anlegen
		$this->RegisterProfileInteger("IPS2GPIO.km", "Entfernung", "", " km", 0, 10000, 1);
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("Distance", "Entfernung", "IPS2GPIO.km", 10);
           	$this->EnableAction("Distance");
		IPS_SetHidden($this->GetIDForIdent("Distance"), false);
		
		$this->RegisterVariableInteger("Energy", "Energie", "", 20);
           	$this->EnableAction("Energy");
		IPS_SetHidden($this->GetIDForIdent("Energy"), false);		
		
		If (IPS_GetKernelRunlevel() == 10103) {						
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			//$this->SendDebug("IPS2GPIO", $Filter, 0);
			$this->SetReceiveDataFilter($Filter);
		
			
			If (($this->ReadPropertyInteger("Pin") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
								
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 0)));

				// Erste Messdaten einlesen
				$this->Setup();
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}	
		}
		else {

		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   		$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
					$this->GetOutput();
			   	}
			   	break; 
			
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
	public function GetOutput()
	{
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_AS3935_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Count" => 9 )));
		$Data = array();
		$Data = unserialize($Result);
		$this->SendDebug("Daten", $Result, 0);
		
		$PowerDown = $Data[1] & 1;
		$this->SendDebug("PowerDown", $PowerDown, 0);
		
		/*
		Indoor = 18
		Outdoor = 14
		*/
		$AFEGainBoost = $Data[1] & 62;
		$this->SendDebug("AFEGainBoost", $AFEGainBoost, 0);
		
		$NoiseFloorLevel = $Data[2] & 112;
		$this->SendDebug("NoiseFloorLevel", $NoiseFloorLevel, 0);
		$WatchdogThreshold = $Data[2] & 15;
		$this->SendDebug("WatchdogThreshold", $WatchdogThreshold, 0);
		/*
		0 = 1 Blitze
		1 = 5
		2 = 9 
		3 = 16
		*/
		$MinNumLigh = $Data[3] & 48;
		$this->SendDebug("MinNumLigh", $MinNumLigh, 0);
		
		/*
		1 Noise Level to high
		4 Disturber Detected
		8 Lightning interrupt
		*/
		$Interrupt = $Data[4] & 15;
		$this->SendDebug("Interrupt", $Interrupt, 0);
		
		$LcoFdiv = $Data[4] & 192;
		$this->SendDebug("LcoFdiv", $LcoFdiv, 0);
		$MaskDisturber = $Data[4] & 32;
		$this->SendDebug("MaskDisturber", $MaskDisturber, 0);

		$Energy = (($Data[7] & 31) << 16) | ($Data[6] << 8) | $Data[5] ;
		SetValueInteger($this->GetIDForIdent("Energy"), $Energy);
		$this->SendDebug("Energy", $Energy, 0);
		
		$Distance = $Data[8] & 63;
		SetValueInteger($this->GetIDForIdent("Distance"), $Distance);
		$this->SendDebug("Distance", $Distance, 0);
		
		
		$LCO = $Data[9] & 128;
		$this->SendDebug("LCO", $LCO, 0);
		$SRCO = $Data[9] & 64;
		$this->SendDebug("SRCO", $SRCO, 0);
		$TRCO = $Data[9] & 32;
		$this->SendDebug("TRCO", $TRCO, 0);
		$Capacitor = $Data[9] & 15;
		$this->SendDebug("Capacitor", $Capacitor, 0);
		
		
	}
	    
	private function Setup()
	{
		$Register = array();
		$Register[0] = $this->ReadPropertyInteger("AFEGain");
		$Register[1] = ($this->ReadPropertyInteger("NoiseFloorLevel") << 4) | $this->ReadPropertyInteger("WDTH");
		$Register[2] = (3 << 6) | ($this->ReadPropertyInteger("NoiseFloorLevel") << 4) | $this->ReadPropertyInteger("SREJ");
		$Register[3] = ($this->ReadPropertyInteger("FrequencyDivisionRatio") << 6) | (0 << 5) | (0 << 4) | 0;
		$Register[8] = $this->ReadPropertyInteger("TunCap");
		
		foreach($Register AS $Key => $Value) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_AS3935_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $Key, "Value" => $Value)));
			If (!$Result) {
				$this->SendDebug("Setup", "Schreiben von Wert ".$Value." in Register ".$Key." nicht erfolgreich!", 0);
			}
		}
	}    
	
	private function Get_I2C_Ports()
	{
		$I2C_Ports = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_get_ports")));
	return $I2C_Ports;
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

}
?>
