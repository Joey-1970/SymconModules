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
		
		$this->RegisterPropertyBoolean("PON", true);
		
		// Annährungs-Sensorik
		$this->RegisterPropertyBoolean("PEN", true);
		$this->RegisterPropertyBoolean("PIEN", true);
		$this->RegisterPropertyInteger("PILT", 0);
		$this->RegisterPropertyInteger("PIHT", 255);
		$this->RegisterPropertyInteger("PPERS", 0);
		$this->RegisterPropertyInteger("PPULSE", 0);
		$this->RegisterPropertyInteger("LDRIVE", 0);
		$this->RegisterPropertyInteger("PGAIN", 0);
		$this->RegisterPropertyInteger("PPLEN", 0);
		$this->RegisterPropertyBoolean("PSIEN", true);
		$this->RegisterPropertyBoolean("CPSIEN", true);
		$this->RegisterPropertyInteger("LED_BOOST", 0);
		
		// Ambilight-Sensorik
		$this->RegisterPropertyBoolean("AEN", true);
		$this->RegisterPropertyBoolean("AIEN", true);
		$this->RegisterPropertyBoolean("WEN", true);
		$this->RegisterPropertyInteger("ATIME", 255);
		$this->RegisterPropertyInteger("WTIME", 255);
		$this->RegisterPropertyInteger("AILT", 0);
		$this->RegisterPropertyInteger("AIHT", 65535);
		$this->RegisterPropertyInteger("APERS", 0);
		$this->RegisterPropertyInteger("AGAIN", 0);
		
		// Gestik Sensorik
		$this->RegisterPropertyBoolean("GEN", true);
		$this->RegisterPropertyBoolean("GIEN", true);
		$this->RegisterPropertyInteger("GPENTH", 40);
		$this->RegisterPropertyInteger("GEXTH", 30);
		$this->RegisterPropertyInteger("GEXPERS", 0);
		$this->RegisterPropertyInteger("GEXMSK", 0);
		$this->RegisterPropertyInteger("GFIFOTH", 0);
		$this->RegisterPropertyInteger("GGAIN", 0);
		$this->RegisterPropertyInteger("GLDRIVE", 0);
		$this->RegisterPropertyInteger("GWTIME", 0);
		$this->RegisterPropertyInteger("GPLEN", 0);
		$this->RegisterPropertyInteger("GPULSE", 0);
		$this->RegisterPropertyInteger("GDIMS", 0);
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
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration Annährungs-Sensor");  
		$arrayElements[] = array("name" => "PEN", "type" => "CheckBox",  "caption" => "Annährungs Sensor aktivieren"); 
		$arrayElements[] = array("name" => "PIEN", "type" => "CheckBox",  "caption" => "Annährungs Interrupt aktivieren"); 
		$arrayElements[] = array("name" => "PSIEN", "type" => "CheckBox",  "caption" => "Sättigungs Interrupt aktivieren"); 
		$arrayElements[] = array("type" => "Label", "label" => "Ein Interrupt wird ausgelöst, wenn er außerhalb der im folgenden definierten Schwellwerte liegt");
		$arrayElements[] = array("type" => "Label", "label" => "Unterer Schwellwert für Annährungs-Interrupt (0-255)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "PILT",  "caption" => "Wert");
		$arrayElements[] = array("type" => "Label", "label" => "Oberer Schwellwert für Annährungs-Interrupt (0-255)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "PIHT",  "caption" => "Wert");
		$arrayElements[] = array("type" => "Label", "label" => "Die Anzahl der Werte die zu einer Interruptauslösung führen können hier definiert werden");
		$arrayElements[] = array("type" => "Label", "label" => "Bis auf den Default-Wert beziehen sich die Werte auf diejenigen außerhalb der Schwellwerte");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Jede Messung (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "Jeden Wert", "value" => 1);
		$arrayOptions[] = array("label" => "2 sequenzielle Werte", "value" => 2);
		$arrayOptions[] = array("label" => "3 sequenzielle Werte", "value" => 3);
		$arrayOptions[] = array("label" => "4 sequenzielle Werte", "value" => 4);
		$arrayOptions[] = array("label" => "5 sequenzielle Werte", "value" => 5);
		$arrayOptions[] = array("label" => "6 sequenzielle Werte", "value" => 6);
		$arrayOptions[] = array("label" => "7 sequenzielle Werte", "value" => 7);
		$arrayOptions[] = array("label" => "8 sequenzielle Werte", "value" => 8);
		$arrayOptions[] = array("label" => "9 sequenzielle Werte", "value" => 9);
		$arrayOptions[] = array("label" => "10 sequenzielle Werte", "value" => 10);
		$arrayOptions[] = array("label" => "11 sequenzielle Werte", "value" => 11);
		$arrayOptions[] = array("label" => "12 sequenzielle Werte", "value" => 12);
		$arrayOptions[] = array("label" => "13 sequenzielle Werte", "value" => 13);
		$arrayOptions[] = array("label" => "14 sequenzielle Werte", "value" => 14);
		$arrayOptions[] = array("label" => "15 sequenzielle Werte", "value" => 15);
		$arrayElements[] = array("type" => "Select", "name" => "PPERS", "caption" => "Kontrollrate", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Impulslänge der LED"); 
		$Micro = utf8_encode(chr(181))."s";
		//$Micro = "us";
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "4".$Micro." (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "8".$Micro, "value" => 1);
		$arrayOptions[] = array("label" => "16".$Micro, "value" => 2);
		$arrayOptions[] = array("label" => "32".$Micro, "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "PPLEN", "caption" => "Impulslänge", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "Anzahl der LDR-Impulse (1-65)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "PPULSE",  "caption" => "Anzahl");
		
		$arrayElements[] = array("type" => "Label", "label" => "LED Strom"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "100mA (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "50mA", "value" => 1);
		$arrayOptions[] = array("label" => "25mA", "value" => 2);
		$arrayOptions[] = array("label" => "12.5mA", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "LDRIVE", "caption" => "Stromstärke", "options" => $arrayOptions );

		$arrayElements[] = array("type" => "Label", "label" => "Annährungsverstärkung"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "1x (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "2x", "value" => 1);
		$arrayOptions[] = array("label" => "4x", "value" => 2);
		$arrayOptions[] = array("label" => "8x", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "PGAIN", "caption" => "Faktor", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "LED Boost Annährung/Gestik"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "100% (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "150%", "value" => 1);
		$arrayOptions[] = array("label" => "200%", "value" => 2);
		$arrayOptions[] = array("label" => "300%", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "LED_BOOST", "caption" => "Boost", "options" => $arrayOptions );

		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");  
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration Ambilight-Sensor");
		$arrayElements[] = array("name" => "AEN", "type" => "CheckBox",  "caption" => "Ambilight Sensor aktivieren"); 
		$arrayElements[] = array("name" => "AIEN", "type" => "CheckBox",  "caption" => "Ambilight Interrupt aktivieren");
		$arrayElements[] = array("name" => "CPSIEN", "type" => "CheckBox",  "caption" => "Sättigungs Interrupt aktivieren"); 
		$arrayElements[] = array("name" => "WEN", "type" => "CheckBox",  "caption" => "Wartezeit"); 
		$arrayElements[] = array("type" => "Label", "label" => "Integration Zeit Register (0=>712ms - 255=>2.78ms)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "ATIME",  "caption" => "Wert");
		$arrayElements[] = array("type" => "Label", "label" => "Wartezeit Register (0=>712ms - 255=>2.78ms)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "WTIME",  "caption" => "Wert");
		$arrayElements[] = array("type" => "Label", "label" => "Ein Interrupt wird ausgelöst, wenn er außerhalb der im folgenden definierten Schwellwerte liegt");
		$arrayElements[] = array("type" => "Label", "label" => "Unterer Schwellwert für Ambilght-Sensing-Interrupt (0-65535)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "AILT",  "caption" => "Wert");
		$arrayElements[] = array("type" => "Label", "label" => "Oberer Schwellwert für Ambilght-Sensing-Interrupt (0-65535)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "AIHT",  "caption" => "Wert");
		$arrayElements[] = array("type" => "Label", "label" => "Die Anzahl der Werte die zu einer Interruptauslösung führen können hier definiert werden");
		$arrayElements[] = array("type" => "Label", "label" => "Bis auf den Default-Wert beziehen sich die Werte auf diejenigen außerhalb der Schwellwerte");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Jeden Messung (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "Jeden Wert", "value" => 1);
		$arrayOptions[] = array("label" => "2 sequenzielle Werte", "value" => 2);
		$arrayOptions[] = array("label" => "3 sequenzielle Werte", "value" => 3);
		$arrayOptions[] = array("label" => "5 sequenzielle Werte", "value" => 4);
		$arrayOptions[] = array("label" => "10 sequenzielle Werte", "value" => 5);
		$arrayOptions[] = array("label" => "15 sequenzielle Werte", "value" => 6);
		$arrayOptions[] = array("label" => "20 sequenzielle Werte", "value" => 7);
		$arrayOptions[] = array("label" => "25 sequenzielle Werte", "value" => 8);
		$arrayOptions[] = array("label" => "30 sequenzielle Werte", "value" => 9);
		$arrayOptions[] = array("label" => "35 sequenzielle Werte", "value" => 10);
		$arrayOptions[] = array("label" => "40 sequenzielle Werte", "value" => 11);
		$arrayOptions[] = array("label" => "45 sequenzielle Werte", "value" => 12);
		$arrayOptions[] = array("label" => "50 sequenzielle Werte", "value" => 13);
		$arrayOptions[] = array("label" => "55 sequenzielle Werte", "value" => 14);
		$arrayOptions[] = array("label" => "60 sequenzielle Werte", "value" => 15);
		$arrayElements[] = array("type" => "Select", "name" => "APERS", "caption" => "Kontrollrate", "options" => $arrayOptions );

		// ALS and Color Gain Control 0x8F Bit 1:0
		$arrayElements[] = array("type" => "Label", "label" => "ALS und Farbverstärkung"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "1x (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "4x", "value" => 1);
		$arrayOptions[] = array("label" => "16x", "value" => 2);
		$arrayOptions[] = array("label" => "64x", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "AGAIN", "caption" => "Faktor", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");  
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration Gestik-Sensor");
		$arrayElements[] = array("name" => "GEN", "type" => "CheckBox",  "caption" => "Gestik Sensor"); 
		$arrayElements[] = array("name" => "GIEN", "type" => "CheckBox",  "caption" => "Gestik Interrupt"); 
		
		$arrayElements[] = array("type" => "Label", "label" => "Unterer Schwellwert für Gestik-Interrupt (0-255)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "GPENTH",  "caption" => "Wert");
		$arrayElements[] = array("type" => "Label", "label" => "Oberer Schwellwert für Gestik-Interrupt (0-255)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "GEXTH",  "caption" => "Wert");
		
		$arrayElements[] = array("type" => "Label", "label" => "Anzahl Gestik Ende"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Erstes Gestik Ende Ergebnis (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "Zweites Gestik Ende Ergebnis", "value" => 1);
		$arrayOptions[] = array("label" => "Viertes Gestik Ende Ergebnis", "value" => 2);
		$arrayOptions[] = array("label" => "Siebtes Gestik Ende Ergebnis", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "GEXPERS", "caption" => "Anzahl", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Interrupt Auslösung"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Nach erstem Datensatz (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "Nach viertem Datensatz", "value" => 1);
		$arrayOptions[] = array("label" => "Nach achtem Datensatz", "value" => 2);
		$arrayOptions[] = array("label" => "Nach sechszehntem Datensatz", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "GFIFOTH", "caption" => "Anzahl", "options" => $arrayOptions );
	
		$arrayElements[] = array("type" => "Label", "label" => "Gestik Konfiguration - Gain Control"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "1x (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "2x", "value" => 1);
		$arrayOptions[] = array("label" => "4x", "value" => 2);
		$arrayOptions[] = array("label" => "8x", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "GGAIN", "caption" => "Faktor", "options" => $arrayOptions );

		// LED Drive Strength 
		$arrayElements[] = array("type" => "Label", "label" => "LED Strom"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "100mA (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "50mA", "value" => 1);
		$arrayOptions[] = array("label" => "25mA", "value" => 2);
		$arrayOptions[] = array("label" => "12,5mA", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "GLDRIVE", "caption" => "Stromstärke", "options" => $arrayOptions );
		
		// GWTIME
		$arrayElements[] = array("type" => "Label", "label" => "Wartezeit zwischen zwei Gestik-Erkennungsphasen"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "0ms (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "2.8ms", "value" => 1);
		$arrayOptions[] = array("label" => "5.6ms", "value" => 2);
		$arrayOptions[] = array("label" => "8.4ms", "value" => 3);
		$arrayOptions[] = array("label" => "14.0ms", "value" => 4);
		$arrayOptions[] = array("label" => "22.4ms", "value" => 5);
		$arrayOptions[] = array("label" => "30.8ms", "value" => 6);
		$arrayOptions[] = array("label" => "39.2ms", "value" => 7);
		$arrayElements[] = array("type" => "Select", "name" => "GWTIME", "caption" => "Zeit", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Impulslänge der LED"); 
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "4".$Micro."(Default)", "value" => 0);
		$arrayOptions[] = array("label" => "8".$Micro, "value" => 1);
		$arrayOptions[] = array("label" => "16".$Micro, "value" => 2);
		$arrayOptions[] = array("label" => "32".$Micro, "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "GPLEN", "caption" => "Impulslänge", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Anzahl der LDR-Impulse (1-65)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "GPULSE",  "caption" => "Anzahl");
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
		// Profile erstellen	
		$this->RegisterProfileInteger("IPS2GPIO.Lux", "Bulb", "", " lx", 0, 65535, 1);
		
		//Status-Variablen anlegen
             	$this->RegisterVariableInteger("ChipID", "Chip ID", "", 10);
		$this->DisableAction("ChipID");
		
		$this->RegisterVariableInteger("Intensity_W", "Intensität Weiß", "IPS2GPIO.Lux", 20);
	        $this->DisableAction("Intensity_W");
		
		$this->RegisterVariableInteger("Intensity_R", "Intensität Rot", "IPS2GPIO.Lux", 30);
	        $this->DisableAction("Intensity_R");
		
		$this->RegisterVariableInteger("Intensity_G", "Intensität Grün", "IPS2GPIO.Lux", 40);
	        $this->DisableAction("Intensity_G");
		
		$this->RegisterVariableInteger("Intensity_B", "Intensität Blau", "IPS2GPIO.Lux", 50);
	        $this->DisableAction("Intensity_B");
		
		$this->RegisterVariableInteger("Interrupt", "Letzte Interrupt", "~UnixTimestamp", 90);
		$this->DisableAction("Interrupt");
		
		$this->RegisterVariableInteger("InterruptAINT", "Letzte Interrupt Ambilight", "~UnixTimestamp", 100);
		$this->DisableAction("InterruptAINT");
		
		$this->RegisterVariableInteger("InterruptPINT", "Letzte Interrupt Nährung", "~UnixTimestamp", 110);
		$this->DisableAction("InterruptPINT");
		
		$this->RegisterVariableInteger("InterruptGINT", "Letzte Interrupt Gestik", "~UnixTimestamp", 120);
		$this->DisableAction("InterruptPINT");		
		
		// Summary setzen
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		$this->SetSummary("DA: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." DB: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]." GPIO: ".$this->ReadPropertyInteger("Pin"));

		//ReceiveData-Filter setzen
		$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
		$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*))';
		$this->SetReceiveDataFilter($Filter);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
			If (intval($this->GetBuffer("PreviousPin")) <> $this->ReadPropertyInteger("Pin")) {
				$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin"), 0);
			}
			
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
			case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
					If (($data->Value == 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Interrupt", "Wert: ".(int)$data->Value." -> keine Aktion", 0);
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Interrupt", "Wert: ".(int)$data->Value." -> Daten einlesen", 0);
						SetValueInteger($this->GetIDForIdent("Interrupt"), time() );
						//$this->Measurement();
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
				return;
			}
			else {
				If (is_array(unserialize($Result)) == true) {
					$Result = unserialize($Result);
					$ChipID = $Result[1];
					$this->SendDebug("Setup", "DeviceID: ".$ChipID, 0);
					If (($ChipID == 0xAB) OR ($ChipID == 0x9C)) {
						SetValueInteger($this->GetIDForIdent("ChipID"), $ChipID);
					}
					else {
						$this->SendDebug("Setup", "Laut Chip ID ist es kein zulaessiger ADPS9960! Ermittelt: ".$ChipID, 0);
					}
					$this->SetStatus(102);
				}
			}
			
			//****************************************************************************************
			// Konfiguration des Annährungs-Sensors
			// ENABLE wird am Ende gesetzt
			$PEN = $this->ReadPropertyBoolean("PEN");
			$PIEN = $this->ReadPropertyBoolean("PIEN");
			
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
			
			$PPERS = $this->ReadPropertyInteger("PPERS");
			
			$PPLEN = $this->ReadPropertyInteger("PPLEN");
			$PPULSE = $this->ReadPropertyInteger("PPULSE");
			$PPULSE = min(65, max(1, $PPULSE));
			$ProximityPulseCountRegister = ($PPULSE - 1) | ($PPLEN << 6);
			if (!$this->WriteData(0x8E, $ProximityPulseCountRegister, "PPULSE")) {
				return false;
			}
			
			$LDRIVE = $this->ReadPropertyInteger("LDRIVE");
			$PGAIN = $this->ReadPropertyInteger("PGAIN");
			
			$PSIEN = $this->ReadPropertyBoolean("PSIEN");
			$LED_BOOST = $this->ReadPropertyInteger("LED_BOOST");
			
			if (!$this->WriteData(0x9D, 0, "POFFSET_UR")) {
				return false;
			}
			
			if (!$this->WriteData(0x9E, 0, "POFFSET_DL")) {
				return false;
			}
			
			if (!$this->WriteData(0x9F, 0, "CONFIG3")) {
				return false;
			}
			
			//****************************************************************************************
			// Konfiguration des Ambilight-Sensors
			$AEN = $this->ReadPropertyBoolean("AEN");
			$WEN = $this->ReadPropertyBoolean("WEN");
			$AIEN = $this->ReadPropertyBoolean("AIEN");
			
			$ATIME = $this->ReadPropertyInteger("ATIME");
			$ATIME = min(255, max(0, $ATIME));
			if (!$this->WriteData(0x81, $ATIME, "ATIME")) {
				return false;
			}
			
			$WTIME = $this->ReadPropertyInteger("WTIME");
			$WTIME = min(255, max(0, $WTIME));
			if (!$this->WriteData(0x83, $WTIME, "WTIME")) {
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
			
			$APERS = $this->ReadPropertyInteger("APERS");
			$PersistanceRegister = $APERS | ($PPERS << 4);
			if (!$this->WriteData(0x8C, $PersistanceRegister, "PERS")) {
				return false;
			}
			
			if (!$this->WriteData(0x8D, 0x60, "CONFIG1")) {
				return false;
			}
			
			$AGAIN = $this->ReadPropertyInteger("AGAIN");
			$ControlRegisterOne = $AGAIN | ($PGAIN << 2) | ($LDRIVE << 6);
			if (!$this->WriteData(0x8F, $ControlRegisterOne, "CONTROL")) {
				return false;
			}
			
			$CPSIEN = $this->ReadPropertyBoolean("CPSIEN");
			$ConfigurationRegisterTwo = 1 | ($LED_BOOST << 4) | ($CPSIEN << 6) | ($PSIEN << 7);
			if (!$this->WriteData(0x90, $ConfigurationRegisterTwo, "CONFIG2")) {
				return false;
			}
			
			
			//****************************************************************************************
			// Konfiguration des Gestik-Sensors
			$GEN = $this->ReadPropertyBoolean("GEN");
			
			$GIEN = $this->ReadPropertyBoolean("GIEN");
			$GestureConfigurationFourRegister = ($GIEN < 1);
			if (!$this->WriteData(0xAB, $GestureConfigurationFourRegister, "GCONF4")) {
				return false;
			} 
			
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
			
			$GFIFOTH = $this->ReadPropertyInteger("GFIFOTH");
			$GEXMSK = $this->ReadPropertyInteger("GEXMSK");
			$GEXPERS = $this->ReadPropertyInteger("GEXPERS");
			$GestureConfigurationOneRegister = $GEXPERS | ($GEXMSK << 2) | ($GFIFOTH << 6);
			if (!$this->WriteData(0xA2, $GestureConfigurationOneRegister, "GCONF1")) {
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
			
			$GPLEN = $this->ReadPropertyInteger("GPLEN");
			$GPULSE = $this->ReadPropertyInteger("GPULSE");
			$GesturePulseCountRegister = $GPULSE | ($GPLEN << 6);
			if (!$this->WriteData(0xA6, $GesturePulseCountRegister, "GPULSE")) {
				return false;
			}
			
			$GDIMS = $this->ReadPropertyInteger("GDIMS");
			if (!$this->WriteData(0xAA, $GDIMS, "GCONF3")) {
				return false;
			}
			
			//****************************************************************************************
			// Set ENABLE register
			$PON = $this->ReadPropertyBoolean("PON");
			$EnableRegister = $PON | ($AEN << 1) | ($PEN << 2) |($WEN << 3) | ($AIEN << 4) | ($PIEN << 5) | ($GEN << 6);
			$this->SendDebug("Setup", "EnableRegister: ".$EnableRegister." PON: ".$PON." AEN: ".$AEN." PEN: ".$PEN." WEN: ".$WEN." AIEN: ".$AIEN." PIEN: ".$PIEN." GEN: ".$GEN, 0);
			if (!$this->WriteData(0x80, $EnableRegister, "ENABLE")) {
				return false;
			}
			
			// Interrupt zum Test erzwingen
			$this->WriteData(0xE4, 0, "IFORCE");
			
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
					$this->SendDebug("WriteData", "Setzen ".$RegisterName." fehlerhaft!", 0);
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
	
	private function ReadData(Int $Register, String $RegisterName)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read_byte", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $Register)));
				$this->SendDebug("ReadData", "Ausfuehrung lesen von: ".$RegisterName, 0);
				$Response = -1;
				
				If ($Result < 0) {
					$this->SendDebug("ReadData", "Lesen ".$RegisterName." fehlerhaft!", 0);
				}
				else {
					$this->SetStatus(102);
					$Response = $Result;
					break;
				} 
			$tries--;
			} while ($tries); 
			
			If (!$tries) {
				$this->SetStatus(202);
				$Response = -1;
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
			
			$tries = 3;
			do {
				// Lesen des Status, Helligkeit, RGB und Annährung
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x93, "Count" => 10)));
				If ($Result < 0) {
					$this->SendDebug("Measurement", "Ermittlung der Daten fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					If (is_array(unserialize($Result)) == true) {
						$this->SetStatus(102);
						//$this->SendDebug("Measurement", "Daten: ".$Result, 0);
						$Result = unserialize($Result);
						// Status
						$Status = $Result[1];
						$AVALID = boolval($Status & 1); // Ambilight Sensor Ergebnis gültig
						$PVALID = boolval($Status & 2); // Annährungswert ist gültig
						$GINT = boolval($Status & 4); // Gestrik Interrupt
						$AINT = boolval($Status & 16); // Ambilight Interrupt
						$PINT = boolval($Status & 32); // Annährungs-Interrupt
						$PGSAT = boolval($Status & 64); // Analoges Sättigungs Ereignis -> Löschung durch PICLEAR
						$CPSAT = boolval($Status & 128); // Weise Fotodiode am oberen Ende des Bereiches -> Löschung durch CICLEAR
						$this->SendDebug("Measurement", "Status: ".$Status." AVALID: ".$AVALID." PVALID: ".$PVALID." GINT: ".$GINT." AINT: ".$AINT." PINT: ".$PINT." PGSAT: ".$PGSAT." CPSAT: ".$CPSAT, 0);

						If ($AINT) {
							SetValueInteger($this->GetIDForIdent("InterruptAINT"), time());
						}
						If ($GINT) {
							SetValueInteger($this->GetIDForIdent("InterruptGINT"), time());
						}
						If ($PINT) {
							SetValueInteger($this->GetIDForIdent("InterruptPINT"), time());
						}

						If ($AVALID) {
							// RGBW Farbergebnis
							$W = ($Result[2] | ($Result[3] << 8));
							$R = ($Result[4] | ($Result[5] << 8));
							$G = ($Result[6] | ($Result[7] << 8));
							$B = ($Result[8] | ($Result[9] << 8));
							$this->SendDebug("Measurement", "Rohwerte - Weiss: ".$W." Rot: ".$R." Gruen: ".$G." Blau: ".$B , 0);

							SetValueInteger($this->GetIDForIdent("Intensity_W"), $W);
							SetValueInteger($this->GetIDForIdent("Intensity_R"), $R);
							SetValueInteger($this->GetIDForIdent("Intensity_G"), $G);
							SetValueInteger($this->GetIDForIdent("Intensity_B"), $B);
						}
						If ($PVALID) {
							// Nährung
							$this->SendDebug("Measurement", "Naehrung: ".$Result[10], 0);
						}
						break;
					}
				}
			$tries--;
			} while ($tries);  
			
			$tries = 3;
			do {
				// Lesen Gestik FIFO Level und Gestik Status
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0xAE, "Count" => 2)));
				If ($Result < 0) {
					$this->SendDebug("Measurement", "Ermittlung der Daten fehlerhaft!", 0);
					$this->SetStatus(202);

				}
				else {
					If (is_array(unserialize($Result)) == true) {
						$this->SetStatus(102);
						//$this->SendDebug("Measurement", "Daten: ".$Result, 0);
						$Result = unserialize($Result);
						$GFLVL = $Result[1];
						$GVALID = boolval($Result[2] & 1);
						$GFOV = boolval($Result[2] & 2);
						$this->SendDebug("Measurement", "Gestik FIFO Level: ".$GFLVL." Gestik Status: ".$GVALID, 0);
						$GFLVL = $Result[1];
						break;
					}
				}
			$tries--;
			} while ($tries);  
				
			If ($GVALID) {
				// Lesen Gestik
				$GestureArray = array();
				for ($i = 0; $i < $GFLVL; $i++) {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_APDS9960_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0xFC, "Count" => 4)));
					If ($Result < 0) {
						$this->SendDebug("Measurement", "Ermittlung der Daten fehlerhaft!", 0);
						$this->SetStatus(202);
					}
					else {
						If (is_array(unserialize($Result)) == true) {
							$this->SetStatus(102);
							//$this->SendDebug("Measurement", "Daten: ".$Result, 0);
							$Result = unserialize($Result);
							$this->SendDebug("Measurement", "Gestik FIFO Zyklus: ".($i + 1)." Up: ".$Result[1]." Down: ".$Result[2]." Left: ".$Result[3]." Right: ".$Result[4], 0);
							$GestureArray[$i][0] = $Result[1]; // Up-Wert
							$GestureArray[$i][1] = $Result[2]; // Down-Wert
							$GestureArray[$i][2] = $Result[3]; // Left-Wert
							$GestureArray[$i][3] = $Result[4]; // Right-Wert
						}
					}
				}
				// Gestik-Array-Analyse
				
			}
			
			// Zurücksetzen der Flags
			If (($PGSAT) OR ($PINT)) {
				$this->WriteData(0xE5, 0, "PICLEAR");
			}
			If (($CPSAT) OR ($AINT)) {
				$this->WriteData(0xE6, 0, "CICLEAR");
			}

			//$this->WriteData(0xE7, 0, "AICLEAR");
			
			/*
			// Status-Byte zur Kontrolle noch einmal einlesen
			$Result = $this->ReadData(0x93, "STATUS");
			If ($Result >= 0) {
				$Status = $Result;
				$AVALID = boolval($Status & 1); // Ambilight Sensor Ergebnis gültig
				$PVALID = boolval($Status & 2); // Annährungswert ist gültig
				$GINT = boolval($Status & 4); // Gestrik Interrupt
				$AINT = boolval($Status & 16); // Ambilight Interrupt
				$PINT = boolval($Status & 32); // Annährungs-Interrupt
				$PGSAT = boolval($Status & 64); // Analoges Sättigungs Ereignis -> Löschung durch PICLEAR
				$CPSAT = boolval($Status & 128); // Weise Fotodiode am oberen Ende des Bereiches -> Löschung durch CICLEAR
				$this->SendDebug("Measurement", "Status: ".$Status." AVALID: ".$AVALID." PVALID: ".$PVALID." GINT: ".$GINT." AINT: ".$AINT." PINT: ".$PINT." PGSAT: ".$PGSAT." CPSAT: ".$CPSAT, 0);
			}
			*/
		}
	}
	    
	private function RGB2Hex($r, $g, $b)
	{
		$Hex = hexdec(str_pad(dechex($r), 2,'0', STR_PAD_LEFT).str_pad(dechex($g), 2,'0', STR_PAD_LEFT).str_pad(dechex($b), 2,'0', STR_PAD_LEFT));
	return $Hex;
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
