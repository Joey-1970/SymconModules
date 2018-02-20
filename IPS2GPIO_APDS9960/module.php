<?
    // Klassendefinition
    class IPS2GPIO_APDS9960 extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Messzyklus", 0);
	}
	    
	// https://github.com/sparkfun/APDS-9960_RGB_and_Gesture_Sensor/blob/master/Libraries/Arduino/APDS-9960_RGB_and_Gesture_Sensor_Arduino_Library/src/SparkFun_APDS9960.h
	// https://github.com/sparkfun/APDS-9960_RGB_and_Gesture_Sensor/blob/master/Libraries/Arduino/APDS-9960_RGB_and_Gesture_Sensor_Arduino_Library/src/SparkFun_APDS9960.cpp
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
		// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 57);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'I2GAPDS9960_Measurement($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("Pin", -1);
		$this->SetBuffer("PreviousPin", -1);
		
		$this->RegisterPropertyBoolean("PON", false);
		$this->RegisterPropertyBoolean("AEN", false);
		$this->RegisterPropertyBoolean("PEN", false);
		$this->RegisterPropertyBoolean("WEN", false);
		$this->RegisterPropertyBoolean("AIEN", false);
		$this->RegisterPropertyBoolean("PIEN", false);
		$this->RegisterPropertyBoolean("GEN", false);
		
		$this->RegisterPropertyInteger("LDRIVE", 0);
		$this->RegisterPropertyInteger("PGAIN", 0);
		$this->RegisterPropertyInteger("AGAIN", 0);
		$this->RegisterPropertyInteger("PILT", 0);
		$this->RegisterPropertyInteger("PIHT", 50);
		$this->RegisterPropertyInteger("AILT", 65535);
		$this->RegisterPropertyInteger("AIHT", 0);
		$this->RegisterPropertyInteger("GPENTH", 40);
		$this->RegisterPropertyInteger("GEXTH", 30);
		
		$this->RegisterPropertyInteger("GGAIN", 0);
		$this->RegisterPropertyInteger("GLDRIVE", 0);
		$this->RegisterPropertyInteger("GWTIME", 0);
	
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
		$arrayOptions[] = array("label" => "57 dez. / 0x39h", "value" => 57);
		
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
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt (optional)"); 
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
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus) (optional)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");  
		$arrayElements[] = array("name" => "PON", "type" => "CheckBox",  "caption" => "Power"); 
		$arrayElements[] = array("name" => "AEN", "type" => "CheckBox",  "caption" => "Ambilight Sensor"); 
		$arrayElements[] = array("name" => "PEN", "type" => "CheckBox",  "caption" => "Annährungs Sensor"); 
		$arrayElements[] = array("name" => "WEN", "type" => "CheckBox",  "caption" => "Wartezeit"); 
		$arrayElements[] = array("name" => "AIEN", "type" => "CheckBox",  "caption" => "Ambilight Interrupt");
		$arrayElements[] = array("name" => "PIEN", "type" => "CheckBox",  "caption" => "Annährungs Interrupt"); 
		$arrayElements[] = array("name" => "GEN", "type" => "CheckBox",  "caption" => "Gestik Sensor"); 
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");  
		// LED Drive Strength 0x8F Bit 7:6
		$arrayElements[] = array("type" => "Label", "label" => "LED Drive Strength"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "100 mA (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "50 mA", "value" => 1);
		$arrayOptions[] = array("label" => "25 mA", "value" => 2);
		$arrayOptions[] = array("label" => "12,5 mA", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "LDRIVE", "caption" => "Stromstärke", "options" => $arrayOptions );

		// Proximity Gain Control 0x8F Bit 3:2
		$arrayElements[] = array("type" => "Label", "label" => "Annährungsverstärkung"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "1x (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "2x", "value" => 1);
		$arrayOptions[] = array("label" => "4x", "value" => 2);
		$arrayOptions[] = array("label" => "8x", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "PGAIN", "caption" => "Faktor", "options" => $arrayOptions );

		// ALS and Color Gain Control 0x8F Bit 1:0
		$arrayElements[] = array("type" => "Label", "label" => "ALS und Farbverstärkung"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "1x (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "4x", "value" => 1);
		$arrayOptions[] = array("label" => "16x", "value" => 2);
		$arrayOptions[] = array("label" => "64x", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "AGAIN", "caption" => "Faktor", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Unterer Schwellwert für Annährungs-Interrupt (0-255)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "PILT",  "caption" => "Wert");
		
		$arrayElements[] = array("type" => "Label", "label" => "Oberer Schwellwert für Annährungs-Interrupt (0-255)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "PIHT",  "caption" => "Wert");

		$arrayElements[] = array("type" => "Label", "label" => "Unterer Schwellwert für Ambilght-Sensing-Interrupt (0-65535)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "AILT",  "caption" => "Wert");
		
		$arrayElements[] = array("type" => "Label", "label" => "Oberer Schwellwert für Ambilght-Sensing-Interrupt (0-65535)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "AIHT",  "caption" => "Wert");
		
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");  

		$arrayElements[] = array("type" => "Label", "label" => "Eingangs-Schwellwert für Gestik (0-255)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "GPENTH",  "caption" => "Wert");
		
		$arrayElements[] = array("type" => "Label", "label" => "Ausgangs-Schwellwert für Gestik (0-255)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "GEXTH",  "caption" => "Wert");
		
		$arrayElements[] = array("type" => "Label", "label" => "Gestik Konfiguration - Gain Control"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "1x (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "2x", "value" => 1);
		$arrayOptions[] = array("label" => "4x", "value" => 2);
		$arrayOptions[] = array("label" => "8x", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "GGAIN", "caption" => "Faktor", "options" => $arrayOptions );

		// LED Drive Strength 
		$arrayElements[] = array("type" => "Label", "label" => "LED Drive Strength"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "100 mA (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "50 mA", "value" => 1);
		$arrayOptions[] = array("label" => "25 mA", "value" => 2);
		$arrayOptions[] = array("label" => "12,5 mA", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "GLDRIVE", "caption" => "Stromstärke", "options" => $arrayOptions );
		
		// GWTIME
		$arrayElements[] = array("type" => "Label", "label" => "GWTIME"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "0 ms (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "2.8 ms", "value" => 1);
		$arrayOptions[] = array("label" => "5.6 ms", "value" => 2);
		$arrayOptions[] = array("label" => "8.4 ms", "value" => 3);
		$arrayOptions[] = array("label" => "14.0 ms", "value" => 4);
		$arrayOptions[] = array("label" => "22.4 ms", "value" => 5);
		$arrayOptions[] = array("label" => "30.8 ms", "value" => 6);
		$arrayOptions[] = array("label" => "39.2 ms", "value" => 7);
		$arrayElements[] = array("type" => "Select", "name" => "GWTIME", "caption" => "Zeit", "options" => $arrayOptions );

		
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
			
		
		//Status-Variablen anlegen
             	$this->RegisterVariableInteger("ChipID", "Chip ID", "", 10);
		$this->DisableAction("ChipID");
		IPS_SetHidden($this->GetIDForIdent("ChipID"), true);
		
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
			If (intval($this->GetBuffer("PreviousPin")) <> $this->ReadPropertyInteger("Pin")) {
				$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin"), 0);
			}		  
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*))';
			$this->SetReceiveDataFilter($Filter);
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				If ($this->ReadPropertyInteger("Pin") >= 0) {
					$ResultPin = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
										  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 2)));	
				}
				else {
					$ResultPin = true;
				}
				$this->SetBuffer("PreviousPin", $this->ReadPropertyInteger("Pin"));
				
				$ResultI2C = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
								
				If (($ResultI2C == true) AND ($ResultPin == true)) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					$this->Setup();
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				$this->SetStatus(104);
			}	
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
						$this->SendDebug("Interrupt", "Wert: ".(int)$data->Value." -> Counter auslesen", 0);
						SetValueInteger($this->GetIDForIdent("LastInterrupt"), time() );
						//$this->GetCounterByInterrupt();
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Interrupt", "Wert: ".(int)$data->Value." -> keine Aktion", 0);
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
 	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			// Ermittlung der Device ID
			// Read ID register and check against known values for APDS-9960
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x92, "Count" => 1)));
			If ($Result < 0) {
				$this->SendDebug("Setup", "Ermittlung der DeviceID fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				$this->SendDebug("Setup", "DeviceID: ", 0);
				$Result = unserialize($Result);
				$ChipID = $Result[1];
				If (($ChipID == 0xAB) OR ($ChipID == 0x9C)) {
					SetValueInteger($this->GetIDForIdent("ChipID"), $ChipID);
				}
				else {
					$this->SendDebug("Setup", "Laut Chip ID ist es kein zulaessiger ADPS9960! Ermittelt: ".$ChipID, 0);
				}
				$this->SetStatus(102);
			}
			
			// Set ENABLE register to 0 (disable all features)
			$PON = $this->ReadPropertyBoolean("PON");
			$AEN = $this->ReadPropertyBoolean("AEN");
			$PEN = $this->ReadPropertyBoolean("PEN");
			$WEN = $this->ReadPropertyBoolean("WEN");
			$AIEN = $this->ReadPropertyBoolean("AIEN");
			$PIEN = $this->ReadPropertyBoolean("PIEN");
			$GEN = $this->ReadPropertyBoolean("GEN");
			$EnableRegister = $PON | ($AEN << 1) | ($PEN << 2) |($WEN << 3) | ($AIEN << 4) | ($PIEN << 5) | ($GEN << 6);
			if (!$this->WriteData(0x80, $EnableRegister, "ENABLE")) {
				return false;
			}
			
			// Set default values for ambient light and proximity registers
			if (!$this->WriteData(0x81, 219, "ATIME")) {
				return false;
			}

			if (!$this->WriteData(0x83, 246, "WTIME")) {
				return false;
			}
			
			if (!$this->WriteData(0x8E, 0x87, "PPULSE")) {
				return false;
			}
			
			if (!$this->WriteData(0x9D, 0, "POFFSET_UR")) {
				return false;
			}
			
			if (!$this->WriteData(0x9E, 0, "POFFSET_DL")) {
				return false;
			}
			
			if (!$this->WriteData(0x8D, 0x60, "CONFIG1")) {
				return false;
			}
			
			$LDRIVE = $this->ReadPropertyInteger("LDRIVE");
			$PGAIN = $this->ReadPropertyInteger("PGAIN");
			$AGAIN = $this->ReadPropertyInteger("AGAIN");
			$ControlRegisterOne = $AGAIN | ($PGAIN << 2) | ($LDRIVE << 6);
			if (!$this->WriteData(0x8F, $ControlRegisterOne, "CONTROL")) {
				return false;
			}
			
			$PILT = $this->ReadPropertyInteger("PILT");
			$PILT = min(255, max(0, $PILT));
			if (!$this->WriteData(0x89, $PILT, "PILT")) {
				return false;
			}
			
			$PIHT = $this->ReadPropertyInteger("PIHT");
			$PIHT = min(255, max(0, $PIHT));
			if (!$this->WriteData(0x8B, $PIHT, "PIHT")) {
				return false;
			}
			
			$AILT = $this->ReadPropertyInteger("AILT");
			$AILT = min(65535, max(0, $AILT));
			$AILTL = $AILT & 0x00FF;
			if (!$this->WriteData(0x84, $AILTL, "AILTL")) {
				return false;
			}
			$AILTH = ($AILT & 0xFF00) >> 8;
			if (!$this->WriteData(0x85, $AILTH, "AILTH")) {
				return false;
			}
			$AIHT = $this->ReadPropertyInteger("AIHT");
			$AIHT = min(65535, max(0, $AIHT));
			$AIHTL = $AIHT & 0x00FF;
			if (!$this->WriteData(0x86, $AIHTL, "AIHTL")) {
				return false;
			}
			$AIHTH = ($AIHT & 0xFF00) >> 8;
			if (!$this->WriteData(0x87, $AIHTH, "AIHTH")) {
				return false;
			}
			
			if (!$this->WriteData(0x8C, 0x11, "PERS")) {
				return false;
			}
			
			if (!$this->WriteData(0x90, 0x01, "CONFIG2")) {
				return false;
			}
			
			if (!$this->WriteData(0x9F, 0, "CONFIG3")) {
				return false;
			}
			
			// Set default values for gesture sense registers
			$GPENTH = $this->ReadPropertyInteger("GPENTH");
			$GPENTH = min(255, max(0, $GPENTH));
			if (!$this->WriteData(0xA0, $GPENTH, "GPENTH")) {
				return false;
			}
			
			$GEXTH = $this->ReadPropertyInteger("GEXTH");
			$GEXTH = min(255, max(0, $GEXTH));
			if (!$this->WriteData(0xA1, $GEXTH, "GEXTH")) {
				return false;
			}
			
			if (!$this->WriteData(0xA2, 0x40, "GCONF1")) {
				return false;
			}
			
			$GGAIN = $this->ReadPropertyInteger("GGAIN");
			$GLDRIVE = $this->ReadPropertyInteger("GLDRIVE");
			$GWTIME = $this->ReadPropertyInteger("GWTIME");
			$GestureRegisterTwo = $GWTIME | ($GLDRIVE << 3) | ($GGAIN << 5);
			if (!$this->WriteData(0xA3, $GestureRegisterTwo, "GCONF2")) {
				return false;
			}
			
			if (!$this->WriteData(0xA4, 0, "GOFFSET_U")) {
				return false;
			}
			
			if (!$this->WriteData(0xA5, 0, "GOFFSET_D")) {
				return false;
			}
			
			if (!$this->WriteData(0xA7, 0, "GOFFSET_L")) {
				return false;
			}
			
			if (!$this->WriteData(0xA9, 0, "GOFFSET_R")) {
				return false;
			}
			
			if (!$this->WriteData(0xA6, 0xC9, "GPULSE")) {
				return false;
			}
			
			if (!$this->WriteData(0xAA, 0, "GCONF3")) {
				return false;
			}
			
			$Result = $this->SetGestureIntEnable(0);
			If ($Result == false) {
				return false;
			}
			
		}
	}
	    
	private function WriteData(Int $Register, Int $Value, String $RegisterName)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$tries = 3;
			do {
				$this->SendDebug("WriteData", "Ausfuehrung setzen von: ".$RegisterName, 0);
				$Response = false;
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $Register, "Value" => $Value)));
				If (!$Result) {
					$this->SendDebug("Setup", "Setzen ".$RegisterName." fehlerhaft!", 0);
				}
				else {
					$this->SetStatus(102);
					$Response = true;
					break;
				} 
			$tries--;
			} while ($tries); 
			
			If (!$tries) {
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				$Response = false;
			}
		}
	return $Response;
	}
	    
	private function GetMode()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetMode", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x80, "Count" => 1)));
			If ($Result < 0) {
				$this->SendDebug("Setup", "Ermittlung des Status fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
			}
			else {
				$this->SetStatus(102);
			}
		}
	return $Result[1];
	}

	private function SetMode(Int $Mode, Int $Enable)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetMode", "Ausfuehrung", 0);
			
			/* Acceptable parameters for setMode
			POWER                   0
			AMBIENT_LIGHT           1
			PROXIMITY               2
			WAIT                    3
			AMBIENT_LIGHT_INT       4
			PROXIMITY_INT           5
			GESTURE                 6
			ALL                     7
			*/
			
			/* Read current ENABLE register */
			$Bitmask = $this->GetMode();
			If ($Bitmask < 0) {
				return false;
			}
			/* Change bit(s) in ENABLE register */
			$Enable = $Enable & 0x01;
			
			If (($Mode >= 0) AND ($Mode <= 6)) {
				If ($Enable) {
					$Bitmask = $Bitmask | (1 << $Mode);
				}
				else {
					$Bitmask = $Bitmask | ~(1 << $Mode);
				}
			}
			elseif ($Mode == 7) {
				If ($Enable) {
					$Bitmask = 0x7F;
				}
				else {
					$Bitmask = 0x00;
				}
			}
			
			if (!$this->WriteData(0x80, $Bitmask, "ENABLE")) {
				return false;
			} 
			return true;  		
		}
	}
   
	private function SetGestureIntEnable(Int $Enable)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetGestureIntEnable", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0xAB, "Count" => 1)));
			If ($Result < 0) {
				$this->SendDebug("Setup", "Ermittlung des GestureIntEnable fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return false;
			}
			else {
				$this->SetStatus(102);
				/* Set bits in register to given value */
				$Enable = $Enable & 0x01;
				$Enable = $Enable << 1;
				$Result = unserialize($Result);
				$Value = $Result[1];
				$Value = $Value & 0xFD;
				$Value = $Value | $Enable;
				if (!$this->WriteData(0xAB, $Value, "GCONF4")) {
					return false;
				} 
				return true;
			}
		}
	}
	    
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Measurement", "Ausfuehrung", 0);
			// Lesen des Status, Helligkeit, RGB und Annährung
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x93, "Count" => 10)));
			If ($Result < 0) {
				$this->SendDebug("Measurement", "Ermittlung der Daten fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					$this->SendDebug("Measurement", "Daten: ".$Result, 0);
					$Result = unserialize($Result);
					// Status
					$this->SendDebug("Measurement", "Status: ".$Result[1], 0);
					// Clear Channel
					$this->SendDebug("Measurement", "Clear Channel: ".($Result[2] | ($Result[3] << 8)), 0);
					// Red Channel
					$this->SendDebug("Measurement", "Red Channel: ".($Result[4] | ($Result[5] << 8)), 0);
					// Green Channel
					$this->SendDebug("Measurement", "Green Channel: ".($Result[6] | ($Result[7] << 8)), 0);
					// Blue Channel
					$this->SendDebug("Measurement", "Green Channel: ".($Result[8] | ($Result[9] << 8)), 0);
					// Nährung
					$this->SendDebug("Measurement", "Naehrung: ".$Result[10], 0);
				}
			}
			
			// Lesen Gestik FIFO Level und Gestik Status
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0xAE, "Count" => 2)));
			If ($Result < 0) {
				$this->SendDebug("Measurement", "Ermittlung der Daten fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					$this->SendDebug("Measurement", "Daten: ".$Result, 0);
					$Result = unserialize($Result);
					$this->SendDebug("Measurement", "Gestik FIFO Level: ".$Result[1], 0);
					$this->SendDebug("Measurement", "Gestik Status: ".$Result[2], 0);
				}
			}
			
			// Lesen Gestik
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0xFC, "Count" => 4)));
			If ($Result < 0) {
				$this->SendDebug("Measurement", "Ermittlung der Daten fehlerhaft!", 0);
				$this->SetStatus(202);
				$this->SetTimerInterval("Messzyklus", 0);
				return;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$this->SetStatus(102);
					$this->SendDebug("Measurement", "Daten: ".$Result, 0);
					$Result = unserialize($Result);
					$this->SendDebug("Measurement", "Gestik FIFO Up: ".$Result[1], 0);
					$this->SendDebug("Measurement", "Gestik FIFO Down: ".$Result[2], 0);
					$this->SendDebug("Measurement", "Gestik FIFO Left: ".$Result[3], 0);
					$this->SendDebug("Measurement", "Gestik FIFO Right: ".$Result[4], 0);
				}
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
