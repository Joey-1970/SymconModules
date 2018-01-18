<?
    // Klassendefinition
    class IPS2GPIO_MCP23017 extends IPSModule 
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
 	    	$this->RegisterPropertyInteger("DeviceAddress", 32);
		$this->RegisterPropertyInteger("DeviceBus", 1);	
		$this->RegisterPropertyInteger("Pin_INT_A", -1);
		$this->SetBuffer("PreviousPin_INT_A", -1);
		$this->RegisterPropertyInteger("Pin_INT_B", -1);
		$this->SetBuffer("PreviousPin_INT_B", -1);
		$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GMCP23017_GetOutput($_IPS["TARGET"]);');
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterPropertyInteger("GPAIODIR".$i, 1);
			$this->RegisterPropertyInteger("GPAIPOL".$i, 0);
			$this->RegisterPropertyInteger("GPAINTEN".$i, 0);
			$this->RegisterPropertyInteger("GPADEFVAL".$i, 0);
			$this->RegisterPropertyInteger("GPAINTCON".$i, 0);
			$this->RegisterPropertyInteger("GPAPU".$i, 0);
		}
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterPropertyInteger("GPBIODIR".$i, 1);
			$this->RegisterPropertyInteger("GPBIPOL".$i, 0);
			$this->RegisterPropertyInteger("GPBINTEN".$i, 0);
			$this->RegisterPropertyInteger("GPBDEFVAL".$i, 0);
			$this->RegisterPropertyInteger("GPBINTCON".$i, 0);
			$this->RegisterPropertyInteger("GPBPU".$i, 0);
		}
		$this->RegisterPropertyInteger("INTPOL", 0);
		$this->RegisterPropertyInteger("ODR", 0);
		$this->RegisterPropertyInteger("MIRROR", 0);
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
		for ($i = 32; $i <= 39; $i++) {
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
		
		$arrayElements[] = array("type" => "Label", "label" => "Optional: Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt A"); 
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_INT_A") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_INT_A")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_INT_A")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_INT_A", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Optional: Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt B"); 
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_INT_B") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_INT_B")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_INT_B")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_INT_B", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "Optional: Lesen der Eingänge in Sekunden (0 -> aus, 5 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration der Ports");
		$arrayOptions_IODIR = array();
		$arrayOptions_IODIR[] = array("label" => "Ausgang", "value" => 0); 
		$arrayOptions_IODIR[] = array("label" => "Eingang (Default)", "value" => 1);
		
		$arrayOptions_IPOL = array();
		$arrayOptions_IPOL[] = array("label" => "Eingang nicht negieren (Default)", "value" => 0);
		$arrayOptions_IPOL[] = array("label" => "Eingang negieren", "value" => 1); 
		
		$arrayOptions_GPINTEN = array();
		$arrayOptions_GPINTEN[] = array("label" => "keine Auslösung (Default)", "value" => 0);
		$arrayOptions_GPINTEN[] = array("label" => "Auslösung", "value" => 1); 
		
		$arrayOptions_DEFVAL = array();
		$arrayOptions_DEFVAL[] = array("label" => "Aus (Default)", "value" => 0);
		$arrayOptions_DEFVAL[] = array("label" => "Ein", "value" => 1); 
		
		$arrayOptions_INTCON = array();
		$arrayOptions_INTCON[] = array("label" => "Änderung Pin-Status (Default)", "value" => 0);
		$arrayOptions_INTCON[] = array("label" => "Referenzwert", "value" => 1); 
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration Port A");
		
		$arrayOptions_GPPU = array();
		$arrayOptions_GPPU[] = array("label" => "kein Pull-up (Default)", "value" => 0);
		$arrayOptions_GPPU[] = array("label" => "Pull-up (100kOhm)", "value" => 1); 
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration Port A");

		for ($i = 0; $i <= 7; $i++) {
		   	$arrayElements[] = array("type" => "Label", "label" => "Konfiguration des GPA ".$i);
			$arrayElements[] = array("type" => "Select", "name" => "GPAIODIR".$i, "caption" => "Nutzung", "options" => $arrayOptions_IODIR );	
			$arrayElements[] = array("type" => "Select", "name" => "GPAIPOL".$i, "caption" => "Negation", "options" => $arrayOptions_IPOL );	
			$arrayElements[] = array("type" => "Select", "name" => "GPAINTEN".$i, "caption" => "Interrupt", "options" => $arrayOptions_GPINTEN );
			$arrayElements[] = array("type" => "Select", "name" => "GPADEFVAL".$i, "caption" => "Referenzwert", "options" => $arrayOptions_DEFVAL );
			$arrayElements[] = array("type" => "Select", "name" => "GPAINTCON".$i, "caption" => "Interrupttrigger", "options" => $arrayOptions_INTCON );
			$arrayElements[] = array("type" => "Select", "name" => "GPAPU".$i, "caption" => "Pull-up", "options" => $arrayOptions_GPPU );
		}
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration Port B");
		for ($i = 0; $i <= 7; $i++) {
		   	$arrayElements[] = array("type" => "Label", "label" => "Konfiguration des GPB ".$i);
			$arrayElements[] = array("type" => "Select", "name" => "GPBIODIR".$i, "caption" => "Nutzung", "options" => $arrayOptions_IODIR );
			$arrayElements[] = array("type" => "Select", "name" => "GPBIPOL".$i, "caption" => "Negation", "options" => $arrayOptions_IPOL );	
			$arrayElements[] = array("type" => "Select", "name" => "GPBINTEN".$i, "caption" => "Interrupt", "options" => $arrayOptions_GPINTEN );
			$arrayElements[] = array("type" => "Select", "name" => "GPBDEFVAL".$i, "caption" => "Referenzwert", "options" => $arrayOptions_DEFVAL );
			$arrayElements[] = array("type" => "Select", "name" => "GPBINTCON".$i, "caption" => "Interrupttrigger", "options" => $arrayOptions_INTCON );
			$arrayElements[] = array("type" => "Select", "name" => "GPBPU".$i, "caption" => "Pull-up", "options" => $arrayOptions_GPPU );
		}
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration des Interrupt");
		$arrayElements[] = array("type" => "Label", "label" => "Polarität des Interrupt");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Aktiv - Low (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "Aktiv - High", "value" => 1); 
		$arrayElements[] = array("type" => "Select", "name" => "INTPOL", "caption" => "Polarität", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Ausgangs-Konfiguration (wenn Open-Drain ausgewählt, wird Polarität überschrieben)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Aktiver Treiber (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "Open Drain", "value" => 1); 
		$arrayElements[] = array("type" => "Select", "name" => "ODR", "caption" => "Konfiguration", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Zuordnung der Interrupt-Ausgänge");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Int A->Port A, Int B->Port B (Default)", "value" => 0);
		$arrayOptions[] = array("label" => "Interrupt ist intern verbunden", "value" => 1); 
		$arrayElements[] = array("type" => "Select", "name" => "MIRROR", "caption" => "Spiegelung", "options" => $arrayOptions );	
		 
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
		
		If ((intval($this->GetBuffer("PreviousPin_INT_A")) <> $this->ReadPropertyInteger("Pin_INT_A")) OR (intval($this->GetBuffer("PreviousPin_INT_B")) <> $this->ReadPropertyInteger("Pin_INT_B"))) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_INT_A")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_INT_A"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_INT_B")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_INT_B"), 0);
		}
		// Device Adresse prüfen
	    	If (($this->ReadPropertyInteger("DeviceAddress") < 0) OR ($this->ReadPropertyInteger("DeviceAddress") > 128)) {
	    		IPS_LogMessage("IPS2GPIO MCP23017","I2C-Device Adresse in einem nicht definierten Bereich!");  
	    	}
	    	// Profil anlegen
		
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("LastInterrupt_A", "Letzte Meldung INT A", "~UnixTimestamp", 10);
		$this->DisableAction("LastInterrupt_A");
		IPS_SetHidden($this->GetIDForIdent("LastInterrupt_A"), true);
		
		$SetTimer = false;
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterVariableBoolean("GPA".$i, "GPA".$i, "~Switch", ($i * 10 + 20));
			If ($this->ReadPropertyInteger("GPAIODIR".$i) == 0) {
				$this->EnableAction("GPA".$i);
			}
			else {
				$this->DisableAction("GPA".$i);
				$SetTimer = true;
			}
			IPS_SetHidden($this->GetIDForIdent("GPA".$i), false);
		}
		
		$this->RegisterVariableInteger("LastInterrupt_B", "Letzte Meldung INT B", "~UnixTimestamp", 100);
		$this->DisableAction("LastInterrupt_B");
		IPS_SetHidden($this->GetIDForIdent("LastInterrupt_B"), true);
		
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterVariableBoolean("GPB".$i, "GPB".$i, "~Switch", ($i * 10 + 110));
			If ($this->ReadPropertyInteger("GPBIODIR".$i) == 0) {
				$this->EnableAction("GPB".$i);
			}
			else {
				$this->DisableAction("GPB".$i);
				$SetTimer = true;
			}
			IPS_SetHidden($this->GetIDForIdent("GPB".$i), false);
		}
		
		
		
		
			
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin_INT_A").'.*)|(.*"Pin":'.$this->ReadPropertyInteger("Pin_INT_B").'.*))';
			//$this->SendDebug("IPS2GPIO", $Filter, 0);
			$this->SetReceiveDataFilter($Filter);
		
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$ResultI2C = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				
				If ($this->ReadPropertyInteger("Pin_INT_A") >= 0) {
					$ResultPin_INT_A = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_INT_A"), "PreviousPin" => $this->GetBuffer("PreviousPin_INT_A"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 0)));
				
					$this->SetBuffer("PreviousPin_INT_A", $this->ReadPropertyInteger("Pin_INT_A"));
				}
				If ($this->ReadPropertyInteger("Pin_INT_B") >= 0) {
					$ResultPin_INT_B = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_INT_B"), "PreviousPin" => $this->GetBuffer("PreviousPin_INT_B"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 0)));
				
					$this->SetBuffer("PreviousPin_INT_B", $this->ReadPropertyInteger("Pin_INT_B"));
				}
				If ($ResultI2C == true) {
					$Messzyklus = $this->ReadPropertyInteger("Messzyklus");
					If (($Messzyklus > 0) AND ($Messzyklus < 5)) {
						$Messzyklus = 5;
					}
					If ($SetTimer == true) {
						$this->SetTimerInterval("Messzyklus", ($Messzyklus * 1000));
					}
					else {
						$this->SetTimerInterval("Messzyklus", 0);
					}
					// Erste Messdaten einlesen
					$this->Setup();
					$this->SetStatus(102);
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
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
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin_INT_A")) {
					If (($data->Value == 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						SetValueInteger($this->GetIDForIdent("LastInterrupt_A"), time() );
						$this->GetOutput();
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						$this->GetOutput();
					}
			   	}
				elseif ($data->Pin == $this->ReadPropertyInteger("Pin_INT_B")) {
					If (($data->Value == 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						SetValueInteger($this->GetIDForIdent("LastInterrupt_B"), time() );
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
	
	public function RequestAction($Ident, $Value) 
	{
		$Port = substr($Ident, 2, 1);
		$Pin = substr($Ident, 3, 1);
		$this->SetOutputPin($Port, intval($Pin), $Value);
	}  
	    
	// Beginn der Funktionen
	public function GetOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetOutput", "Ausfuehrung", 0);
			// Adressen 12 13
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("12"), "Count" => 2)));
			If ($Result < 0) {
				$this->SendDebug("GetOutput", "Einlesen der Werte fehlerhaft!", 0);
				return;
			}
			else {
				$this->SendDebug("GetOutput", "Ergbnis: ".$Result, 0);
				If (is_array(unserialize($Result))) {
					$PortData = array();
					$PortData = unserialize($Result);
					// Ergebnis sichern
				}
				// Statusvariablen setzen
				
			}
		}
	}
	
	public function SetOutputPin(String $Port, Int $Pin, Bool $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Pin = min(7, max(0, $Pin));
			$Value = boolval($Value);
			
			If ($Port == "A") {
					
			}
			elseif ($Port == "B") {
				
			}
		}
	}
	    
	public function SetOutput(Int $PortA, Int $PortB)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetOutput", "Ausfuehrung", 0);
			$PortA = min(255, max(0, $PortA));
			$PortB = min(255, max(0, $PortB));
			
			$OutputArray = Array();
			$OutputArray[0] = $PortA;
			$OutputArray[1] = $PortB;
			
			// Adressen 14 15
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("14"), 
										  "Parameter" => serialize($OutputArray) )));
			If (!$Result) {
				$this->SendDebug("SetOutput", "Setzen der Ausgänge fehlerhaft!", 0);
			}
			else {
				// Statusvariablen setzen
				for ($i = 0; $i <= 7; $i++) {
					If ($OutputArray[0] & pow(2, $i)) {
					    	If (GetValueBoolean($this->GetIDForIdent("GPA".$i)) == false) {
							SetValueBoolean($this->GetIDForIdent("GPA".$i), true);
						}
					}
					else {
						If (GetValueBoolean($this->GetIDForIdent("GPA".$i)) == true) {
							SetValueBoolean($this->GetIDForIdent("GPA".$i), false);
						}
					}
					If ($OutputArray[1] & pow(2, $i)) {
					    	If (GetValueBoolean($this->GetIDForIdent("GPB".$i)) == false) {
							SetValueBoolean($this->GetIDForIdent("GPB".$i), true);
						}
					}
					else {
						If (GetValueBoolean($this->GetIDForIdent("GPB".$i)) == true) {
							SetValueBoolean($this->GetIDForIdent("GPB".$i), false);
						}
					}
				}
				$this->GetOutput();
			}
		}
	}   
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			$Config = 0;
			// Bit 0: irrelevant
			// Bit 1: INTPOL Polarität des Interrupts
			$INTPOL = $this->ReadPropertyInteger("INTPOL");
			$Config = $Config | ($INTPOL << 1);
			// Bit 2: ODR Open-Drain oder aktiver Treiber beim Interrupt
			$ODR = $this->ReadPropertyInteger("ODR");
			$Config = $Config | ($ODR << 2);
			// Bit 3: irrelvant, nur bei der SPI-Version nutzbar
			// Bit 4: DISSLW Defaultwert = 0
			// Bit 5: SEQOP Defaultwert = 0, automatische Adress-Zeiger inkrement
			// Bit 6: MIRROR Interrupt-Konfiguration
			$MIRROR = $this->ReadPropertyInteger("MIRROR");
			$Config = $Config | ($MIRROR << 6);
			// Bit 7: BANK Defaultwert = 0 Register sind in derselben Bank
			
			// ConfigByte senden!
			$this->SendDebug("Setup", "Config-Byte: ".$Config, 0);
			$ConfigArray = array();
			$ConfigArray[] = $Config;
			$ConfigArray[] = $Config;
			// Adressen 0A 0B
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("A0"), 
										  "Parameter" => serialize($ConfigArray) )));
			If (!$Result) {
				$this->SendDebug("Setup", "Basis-Konfigurations-Byte setzen fehlerhaft!", 0);
			}
			else {
				$this->SendDebug("Setup", "Basis-Konfigurations-Byte erfolgreich gesetzt", 0);
			}
			
			$ConfigArray = array();
			// IO-Bytes ermitteln
			$GPAIODIR = $this->GetConfigByte("GPAIODIR");
			$ConfigArray[] = $GPAIODIR;
			$this->SendDebug("Setup", "IO-Byte A: ".$GPAIODIR, 0);
			// Adresse 00
			
			$GPBIODIR = $this->GetConfigByte("GPBIODIR");
			$ConfigArray[] = $GPBIODIR;
			$this->SendDebug("Setup", "IO-Byte B: ".$GPBIODIR, 0);
			// Adresse 01
			
			// Polariät des Eingangs ermitteln
			$GPAIPOL = $this->GetConfigByte("GPAIPOL");
			$ConfigArray[] = $GPAIPOL;
			$this->SendDebug("Setup", "Polaritäts-Byte A: ".$GPAIPOL, 0);
			// Adresse 02
			
			$GPBIPOL = $this->GetConfigByte("GPBIPOL");
			$ConfigArray[] = $GPBIPOL;
			$this->SendDebug("Setup", "Polaritäts-Byte B: ".$GPBIPOL, 0);
			// Adresse 03
			
			// Interrupt enable ermitteln
			$GPAINTEN = $this->GetConfigByte("GPAINTEN");
			$ConfigArray[] = $GPAINTEN;
			$this->SendDebug("Setup", "Interrupt-Byte A: ".$GPAINTEN, 0);
			// Adresse 04
			
			$GPBINTEN = $this->GetConfigByte("GPBINTEN");
			$ConfigArray[] = $GPBINTEN;
			$this->SendDebug("Setup", "Interrupt-Byte B: ".$GPBINTEN, 0);
			// Adresse 05
			
			// Referenzwert-Byte ermitteln
			$GPADEFVAL = $this->GetConfigByte("GPADEFVAL");
			$ConfigArray[] = $GPADEFVAL;
			$this->SendDebug("Setup", "Referenzwert-Byte A: ".$GPADEFVAL, 0);
			// Adresse 06
			
			$GPBDEFVAL = $this->GetConfigByte("GPBDEFVAL");
			$ConfigArray[] = $GPBDEFVAL;
			$this->SendDebug("Setup", "Referenzwert-Byte B: ".$GPBDEFVAL, 0);
			// Adresse 07
			
			// Interrupt-Referenz-Byte ermitteln
			$GPAINTCON = $this->GetConfigByte("GPAINTCON");
			$ConfigArray[] = $GPAINTCON;
			$this->SendDebug("Setup", "Interrrupt-Referenz-Byte A: ".$GPAINTCON, 0);
			// Adresse 08
			
			$GPBINTCON = $this->GetConfigByte("GPBINTCON");
			$ConfigArray[] = $GPBINTCON;
			$this->SendDebug("Setup", "Interrrupt-Referenz-Byte B: ".$GPBINTCON, 0);
			// Adresse 09
			
			// Pull-Up-Byte ermitteln
			$GPAPU = $this->GetConfigByte("GPAPU");
			$ConfigArray[] = $GPAPU;
			$this->SendDebug("Setup", "Pull-up-Byte A: ".$GPAPU, 0);
			// Adresse 0C
			
			$GPBPU = $this->GetConfigByte("GPBPU");
			$ConfigArray[] = $GPBPU;
			$this->SendDebug("Setup", "Pull-up-Byte B: ".$GPBPU, 0);
			// Adresse 0D
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_Write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("00"), 
										  "Parameter" => serialize($ConfigArray) )));
			If (!$Result) {
				$this->SendDebug("Setup", "Konfigurations-Byte setzen fehlerhaft!", 0);
			}
			else {
				$this->SendDebug("Setup", "Konfigurations-Byte erfolgreich gesetzt", 0);
			}
			
		}
	}    
	
	private function GetConfigByte(String $ConfigTyp)
	{
		$Result = 0;
		for ($i = 0; $i <= 7; $i++) {
			$BitValue = $this->ReadPropertyInteger($ConfigTyp.$i);
			If ($BitValue == 1) {
				$Result = $Result + pow(2, $i);
			}
		}
	return $Result;
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
