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
		$this->RegisterPropertyInteger("Pin_INT", -1);
		$this->SetBuffer("PreviousPin_INT", -1);
		$this->RegisterPropertyInteger("Messzyklus", 0);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GMCP23017_GetOutput($_IPS["TARGET"]);');
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterPropertyInteger("GPAIODIR".$i, 1);
			$this->RegisterPropertyInteger("GPAIPOL".$i, 0);
			$this->RegisterPropertyInteger("GPAINTEN".$i, 0);
			$this->RegisterPropertyInteger("GPAPU".$i, 0);
		}
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterPropertyInteger("GPBIODIR".$i, 1);
			$this->RegisterPropertyInteger("GPBIPOL".$i, 0);
			$this->RegisterPropertyInteger("GPBINTEN".$i, 0);
			$this->RegisterPropertyInteger("GPBPU".$i, 0);
		}
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("LastInterrupt", "Letzte Meldung INT", "~UnixTimestamp", 10);
		$this->DisableAction("LastInterrupt");
		
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterVariableBoolean("GPA".$i, "GPA".$i, "~Switch", ($i * 10 + 20));
		   	$this->RegisterVariableBoolean("GPB".$i, "GPB".$i, "~Switch", ($i * 10 + 100));
		}
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
		
		$arrayElements[] = array("type" => "Label", "label" => "Optional: Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt A/B"); 
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_INT") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_INT")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_INT")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_INT", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		
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
		
		$arrayOptions_GPPU = array();
		$arrayOptions_GPPU[] = array("label" => "kein Pull-up (Default)", "value" => 0);
		$arrayOptions_GPPU[] = array("label" => "Pull-up (100kOhm)", "value" => 1); 
		
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration Port A");
		for ($i = 0; $i <= 7; $i++) {
		   	$arrayElements[] = array("type" => "Label", "label" => "Konfiguration des GPA ".$i);
			$arrayElements[] = array("type" => "Select", "name" => "GPAIODIR".$i, "caption" => "Nutzung", "options" => $arrayOptions_IODIR );	
			$arrayElements[] = array("type" => "Select", "name" => "GPAIPOL".$i, "caption" => "Negation", "options" => $arrayOptions_IPOL );	
			$arrayElements[] = array("type" => "Select", "name" => "GPAINTEN".$i, "caption" => "Interrupt", "options" => $arrayOptions_GPINTEN );
			$arrayElements[] = array("type" => "Select", "name" => "GPAPU".$i, "caption" => "Pull-up", "options" => $arrayOptions_GPPU );
		}
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration Port B");
		for ($i = 0; $i <= 7; $i++) {
		   	$arrayElements[] = array("type" => "Label", "label" => "Konfiguration des GPB ".$i);
			$arrayElements[] = array("type" => "Select", "name" => "GPBIODIR".$i, "caption" => "Nutzung", "options" => $arrayOptions_IODIR );
			$arrayElements[] = array("type" => "Select", "name" => "GPBIPOL".$i, "caption" => "Negation", "options" => $arrayOptions_IPOL );	
			$arrayElements[] = array("type" => "Select", "name" => "GPBINTEN".$i, "caption" => "Interrupt", "options" => $arrayOptions_GPINTEN );
			$arrayElements[] = array("type" => "Select", "name" => "GPBPU".$i, "caption" => "Pull-up", "options" => $arrayOptions_GPPU );
		}
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
		
		If ( intval($this->GetBuffer("PreviousPin_INT")) <> $this->ReadPropertyInteger("Pin_INT") ) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_INT")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_INT"), 0);
		}

		//Status-Variablen anlegen
		$SetTimer = false;
		for ($i = 0; $i <= 7; $i++) {
			If ($this->ReadPropertyInteger("GPAIODIR".$i) == 0) {
				$this->EnableAction("GPA".$i);
			}
			else {
				$this->DisableAction("GPA".$i);
				$SetTimer = true;
			}
			
			If ($this->ReadPropertyInteger("GPBIODIR".$i) == 0) {
				$this->EnableAction("GPB".$i);
			}
			else {
				$this->DisableAction("GPB".$i);
				$SetTimer = true;
			}
		}
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin_INT").'.*))';
			//$this->SendDebug("IPS2GPIO", $Filter, 0);
			$this->SetReceiveDataFilter($Filter);
		
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$ResultI2C = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				
				If ($this->ReadPropertyInteger("Pin_INT") >= 0) {
					$ResultPin_INT_A = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_INT"), "PreviousPin" => $this->GetBuffer("PreviousPin_INT"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 0)));
				
					$this->SetBuffer("PreviousPin_INT", $this->ReadPropertyInteger("Pin_INT"));
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
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					If (($data->Pin == $this->ReadPropertyInteger("Pin_INT")) AND (intval($data->Value) == 1)) {
						SetValueInteger($this->GetIDForIdent("LastInterrupt"), time() );
						$this->SendDebug("Interrupt", "Wert: ".intval($data->Value), 0);
						$this->Interrupt();
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
		$Source = substr($Ident, 0, 3);
		$Pin = substr($Ident, 3, 1);
		
		switch($Source) {
		case "GPA":
			$this->SetOutputPin("A", intval($Pin), $Value);
	            	break;
		case "GPB":
			$this->SetOutputPin("B", intval($Pin), $Value);
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}  
	    
	// Beginn der Funktionen
	public function GetOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetOutput", "Ausfuehrung", 0);
			// Adressen 12 13
			
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("12"), "Count" => 4)));
				If ($Result < 0) {
					$this->SendDebug("GetOutput", "Einlesen der Werte fehlerhaft!", 0);
					$this->SetStatus(202);
					//return;
				}
				else {
					If (is_array(unserialize($Result))) {
						$this->SetStatus(102);
						$OutputArray = array();
						// für Ausgänge LAT benutzen für Eingänge PORT 

						$OutputArray = unserialize($Result);
						// Ergebnis sichern
						$this->SetBuffer("GPA", $OutputArray[1]);
						$this->SetBuffer("GPB", $OutputArray[2]);

						$GPAIODIR = intval($this->GetBuffer("GPAIODIR"));
						$GPBIODIR = intval($this->GetBuffer("GPBIODIR"));
						$GPIOA = $OutputArray[1];
						$GPIOB = $OutputArray[2];
						$OLATA = $OutputArray[3];
						$OLATB = $OutputArray[4];
						$this->SendDebug("GetOutput", "GPIOA: ".$GPIOA." GPIOB: ".$GPIOB." OLATA: ".$OLATA." OLATB: ".$OLATB, 0);
						// Statusvariablen setzen
						for ($i = 0; $i <= 7; $i++) {
							// Port A
							If (boolval($GPAIODIR & (1 << $i))) {
								$Value = $GPIOA & pow(2, $i);
							}
							else {
								$Value = $OLATA & pow(2, $i);
							}
							If (GetValueBoolean($this->GetIDForIdent("GPA".$i)) == !$Value) {
								SetValueBoolean($this->GetIDForIdent("GPA".$i), $Value);
							}
							// Port B
							If (boolval($GPBIODIR & (1 << $i))) {
								$Value = $GPIOB & pow(2, $i);
							}
							else {
								$Value = $OLATB & pow(2, $i);
							}
							If (GetValueBoolean($this->GetIDForIdent("GPB".$i)) == !$Value) {
								SetValueBoolean($this->GetIDForIdent("GPB".$i), $Value);
							}
						}
						break;
					}
				}
			$tries--;
			} while ($tries);  
		}
	}
	
	private function Interrupt()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Interrupt", "Ausfuehrung", 0);
			// Adressen 12 13
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("0E"), "Count" => 4)));
				If ($Result < 0) {
					$this->SendDebug("Interrupt", "Einlesen der Werte fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					If (is_array(unserialize($Result))) {
						$this->SetStatus(102);
						$OutputArray = array();
						// für Ausgänge LAT benutzen für Eingänge PORT 

						$OutputArray = unserialize($Result);

						$GPAIODIR = intval($this->GetBuffer("GPAIODIR"));
						$GPBIODIR = intval($this->GetBuffer("GPBIODIR"));
						$INTFA = $OutputArray[1]; // INTFA Interrupt Flag Register (zeigt welcher Eingang den Interrupt ausgelöst hat)
						$INTFB = $OutputArray[2]; // INTFB Interrupt Flag Register (zeigt welcher Eingang den Interrupt ausgelöst hat)
						$INTCAPA = $OutputArray[3]; // INTCAPA Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
						$INTCAPB = $OutputArray[4]; // INTCAPB Interrupt Captured Value (zeigt den Zustand des GPIO wo der Interrupt eintrat)
						$this->SendDebug("Interrupt", "INTFA: ".$INTFA." INTFB: ".$INTFB." INTCAPA: ".$INTCAPA." INTCAPB: ".$INTCAPB, 0);

						// Statusvariablen setzen
						for ($i = 0; $i <= 7; $i++) {
							// Port A
							If (boolval($GPAIODIR & (1 << $i))) {
								$Value = $INTCAPA & pow(2, $i);
								If (GetValueBoolean($this->GetIDForIdent("GPA".$i)) == !$Value) {
									SetValueBoolean($this->GetIDForIdent("GPA".$i), $Value);
								}
							}

							// Port B
							If (boolval($GPBIODIR & (1 << $i))) {
								$Value = $INTCAPB & pow(2, $i);
								If (GetValueBoolean($this->GetIDForIdent("GPB".$i)) == !$Value) {
									SetValueBoolean($this->GetIDForIdent("GPB".$i), $Value);
								}
							}
						}
						$this->GetOutput();
						break;
					}
				}
			$tries--;
			} while ($tries);  
		}
	}    
	    
	public function SetOutputPin(String $Port, Int $Pin, Bool $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetOutputPin", "Ausfuehrung", 0);
			$Pin = min(7, max(0, $Pin));
			$Value = boolval($Value);
			// Maske für Ausgänge 
			$GPAIODIRout = 255 - intval($this->GetBuffer("GPAIODIR"));
			$GPBIODIRout = 255 - intval($this->GetBuffer("GPBIODIR"));
			
			$tries = 3;
			do {
				If ($Port == "A") {
					$GPA = intval($this->GetBuffer("GPA"));	
					If ($Value == true) {
						$GPA = $this->setBit($GPA, $Pin);
					}
					else {
						$GPA = $this->unsetBit($GPA, $Pin);
					}
					// Neuen Wert senden
					$OutputArray = Array();
					$OutputArray[0] = $GPA & $GPAIODIRout;

					// Adresse 14
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("14"), 
											  "Parameter" => serialize($OutputArray) )));
					If (!$Result) {
						$this->SendDebug("SetOutputPin", "Setzen der Ausgaenge fehlerhaft!", 0);
						$this->SetStatus(202);
					}
					else {
						$this->SetStatus(102);
						for ($i = 0; $i <= 7; $i++) {
							If ($GPAIODIRout & pow(2, $i)) {
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
							}
						}
						$this->GetOutput();
						break;
					}	
				}
				elseif ($Port == "B") {
					$GPB = intval($this->GetBuffer("GPB"));	
					If ($Value == true) {
						$GPB = $this->setBit($GPB, $Pin);
					}
					else {
						$GPB = $this->unsetBit($GPB, $Pin);
					}
					// Neuen Wert senden
					$OutputArray = Array();
					$OutputArray[0] = $GPB & $GPBIODIRout;

					// Adresse 15
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("15"), 
											  "Parameter" => serialize($OutputArray) )));
					If (!$Result) {
						$this->SendDebug("SetOutputPin", "Setzen der Ausgaenge fehlerhaft!", 0);
						$this->SetStatus(202);
					}
					else {
						$this->SetStatus(102);
						for ($i = 0; $i <= 7; $i++) {
							If ($GPBIODIRout & pow(2, $i)) {
								If ($OutputArray[0] & pow(2, $i)) {
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
						}
						$this->GetOutput();
						break;
					}
				}
			$tries--;
			} while ($tries);  
		}
	}
	    
	public function SetOutput(Int $PortA, Int $PortB)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$tries = 3;
			do {
				$this->SendDebug("SetOutput", "Ausfuehrung", 0);
				$PortA = min(255, max(0, $PortA));
				$PortB = min(255, max(0, $PortB));

				// Maske für Ausgänge 
				$GPAIODIRout = 255 - intval($this->GetBuffer("GPAIODIR"));
				$GPBIODIRout = 255 - intval($this->GetBuffer("GPBIODIR"));

				$OutputArray = Array();
				$OutputArray[0] = $PortA & $GPAIODIRout;
				$OutputArray[1] = $PortB & $GPBIODIRout;

				// Adressen 14 15
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("14"), 
											  "Parameter" => serialize($OutputArray) )));
				If (!$Result) {
					$this->SendDebug("SetOutput", "Setzen der Ausgaenge fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					$this->SetStatus(102);
					// Statusvariablen setzen
					for ($i = 0; $i <= 7; $i++) {
						If ($GPAIODIRout & pow(2, $i)) {
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
						}
						If ($GPBIODIRout & pow(2, $i)) {
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
					}
					$this->GetOutput();
					break;
				}
			$tries--;
			} while ($tries);  
		}
	}   
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			$Config = 0;
			// Bit 0: irrelevant
			// Bit 1: INTPOL Polarität des Interrupts
			$INTPOL = 0;
			$Config = $Config | ($INTPOL << 1);
			// Bit 2: ODR Open-Drain oder aktiver Treiber beim Interrupt
			$ODR = 0;
			$Config = $Config | ($ODR << 2);
			// Bit 3: irrelvant, nur bei der SPI-Version nutzbar
			// Bit 4: DISSLW Defaultwert = 0
			// Bit 5: SEQOP Defaultwert = 0, automatische Adress-Zeiger inkrement
			// Bit 6: MIRROR Interrupt-Konfiguration
			$MIRROR = 1;
			$Config = $Config | ($MIRROR << 6);
			// Bit 7: BANK Defaultwert = 0 Register sind in derselben Bank
			
			// ConfigByte senden!
			$this->SendDebug("Setup", "Config-Byte: ".$Config, 0);
			$ConfigArray = array();
			$ConfigArray[0] = $Config;
			$ConfigArray[1] = $Config;
			// Adressen 0A 0B
			$tries = 5;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("A0"), 
											  "Parameter" => serialize($ConfigArray) )));
				If (!$Result) {
					$this->SendDebug("Setup", "Basis-Konfigurations-Byte setzen fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					$this->SendDebug("Setup", "Basis-Konfigurations-Byte erfolgreich gesetzt", 0);
					$this->SetStatus(102);
					break;
				}
			$tries--;
			} while ($tries);  
			
			$ConfigArray = array();
			// IO-Bytes ermitteln
			$GPAIODIR = $this->GetConfigByte("GPAIODIR");
			$ConfigArray[0] = $GPAIODIR; // Adresse 00
			$this->SetBuffer("GPAIODIR", $GPAIODIR);
			
			$GPBIODIR = $this->GetConfigByte("GPBIODIR");
			$ConfigArray[1] = $GPBIODIR; // Adresse 01
			$this->SetBuffer("GPBIODIR", $GPBIODIR);
			$this->SendDebug("Setup", "IO-Byte A: ".$GPAIODIR." IO-Byte B: ".$GPBIODIR, 0);
			
			// Polariät des Eingangs ermitteln
			$GPAIPOL = $this->GetConfigByte("GPAIPOL");
			$ConfigArray[2] = $GPAIPOL; // Adresse 02
			
			$GPBIPOL = $this->GetConfigByte("GPBIPOL");
			$ConfigArray[3] = $GPBIPOL; // Adresse 03
			$this->SendDebug("Setup", "Polaritaets-Byte A: ".$GPAIPOL." Polaritaets-Byte B: ".$GPBIPOL, 0);
			
			// Interrupt enable ermitteln
			$GPAINTEN = $this->GetConfigByte("GPAINTEN");
			$ConfigArray[4] = $GPAINTEN; // Adresse 04
			
			$GPBINTEN = $this->GetConfigByte("GPBINTEN");
			$ConfigArray[5] = $GPBINTEN; // Adresse 05
			$this->SendDebug("Setup", "Interrupt-Byte A: ".$GPAINTEN." Interrupt-Byte B: ".$GPBINTEN, 0);
			
			// Referenzwert-Byte ermitteln
			$ConfigArray[6] = 0; // Adresse 06
			$ConfigArray[7] = 0; // Adresse 07
			$this->SendDebug("Setup", "Referenzwert-Byte A/B = 0", 0);
			
			// Interrupt-Referenz-Byte ermitteln
			$ConfigArray[8] = 0; // Adresse 08
			$ConfigArray[9] = 0; // Adresse 09
			$this->SendDebug("Setup", "Interrupt-Referenzwert-Byte A/B = 0", 0);
			
			// Pull-Up-Byte ermitteln
			$GPAPU = $this->GetConfigByte("GPAPU");
			$ConfigArray[10] = $GPAPU; // Adresse 0C
			
			$GPBPU = $this->GetConfigByte("GPBPU");
			$ConfigArray[11] = $GPBPU; // Adresse 0D
			$this->SendDebug("Setup", "Pull-up-Byte A: ".$GPAPU." Pull-up-Byte B: ".$GPBPU, 0);
			$tries = 5;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_MCP23017_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("00"), 
											  "Parameter" => serialize($ConfigArray) )));
				If (!$Result) {
					$this->SendDebug("Setup", "Konfigurations-Byte setzen fehlerhaft!", 0);
					$this->SetTimerInterval("Messzyklus", 0);
					$this->SetStatus(202);
				}
				else {
					$this->SendDebug("Setup", "Konfigurations-Byte erfolgreich gesetzt", 0);
					$this->SetStatus(102);
					$this->GetOutput();
					break;
				}
			$tries--;
			} while ($tries);  
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
	
	private function setBit($byte, $significance) { 
 		// ein bestimmtes Bit auf 1 setzen
 		return $byte | 1 << $significance;   
 	} 
	
	private function unsetBit($byte, $significance) {
	    // ein bestimmtes Bit auf 0 setzen
	    return $byte & ~(1 << $significance);
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
