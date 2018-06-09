<?
    // Klassendefinition
    class IPS2GPIO_SUSV extends IPSModule 
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
 		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("DeviceAddress", 15);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		$this->RegisterPropertyInteger("Pin", 27);
		$this->SetBuffer("PreviousPin", 27);
		
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GSUSV_Measurement($_IPS["TARGET"]);');
		
		// Profil anlegen
	    	$this->RegisterProfileFloat("IPS2GPIO.V", "Electricity", "", " V", -100000, +100000, 0.1, 3);
	    	$this->RegisterProfileFloat("IPS2GPIO.mA", "Electricity", "", " mA", -100000, +100000, 0.1, 3);
		
		$this->RegisterProfileInteger("IPS2GPIO.BatteryStatus", "Battery", "", "", 0, 2, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.BatteryStatus", 0, "Lädt", "EnergyStorage", 0x0000FF);
		IPS_SetVariableProfileAssociation("IPS2GPIO.BatteryStatus", 1, "Geladen", "Ok", 0x00FF00);
		IPS_SetVariableProfileAssociation("IPS2GPIO.BatteryStatus", 2, "Fehler", "Alert", 0xFF0000);
		
		$this->RegisterProfileInteger("IPS2GPIO.ChargeStatus", "Battery", "", "", 0, 1, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.ChargeStatus", 0, "Deactiviert", "Close", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2GPIO.ChargeStatus", 1, "Aktiviert", "Ok", 0x00FF00);
		
		$this->RegisterProfileInteger("IPS2GPIO.ChargeCurrent", "Battery", "", "", 0, 2, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.ChargeCurrent", 0, "1000 mA", "EnergyStorage", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.ChargeCurrent", 1, "500 mA", "EnergyStorage", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.ChargeCurrent", 2, "300 mA", "EnergyStorage", -1);
		
		$this->RegisterProfileInteger("IPS2GPIO.PowerStatus", "Battery", "", "", 0, 1, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.PowerStatus", 0, "Sekundär", "Plug", 0x00FF00);
		IPS_SetVariableProfileAssociation("IPS2GPIO.PowerStatus", 1, "Primär", "Battery", 0xFF0000);
		
		// Status-Variablen anlegen
		$this->RegisterVariableFloat("Firmware", "Firmware Version", "", 10);
		$this->DisableAction("Firmware");
		
		$this->RegisterVariableString("Model", "USV Modell", "", 20);
		$this->DisableAction("Model");
		
		$this->RegisterVariableFloat("Voltage", "Spannung extern", "IPS2GPIO.V", 30);
		$this->DisableAction("Voltage");
		
		$this->RegisterVariableFloat("PowerExtern", "Strom extern", "IPS2GPIO.mA", 40);
		$this->DisableAction("PowerExtern");
		
		$this->RegisterVariableFloat("BatteryVoltage", "Spannung Batterie", "IPS2GPIO.V", 50);
		$this->DisableAction("Voltage");
		
		$this->RegisterVariableFloat("PowerBattery", "Strom Batterie", "IPS2GPIO.mA", 60);
		$this->DisableAction("PowerBattery");
		
		$this->RegisterVariableInteger("BatteryStatus", "Status Batterie", "IPS2GPIO.BatteryStatus", 70);
		$this->DisableAction("BatteryStatus");
		
		$this->RegisterVariableInteger("ChargeStatus", "Lade Status", "IPS2GPIO.ChargeStatus", 80);
		$this->EnableAction("ChargeStatus");
		
		$this->RegisterVariableInteger("ChargeCurrent", "Lade Strom", "IPS2GPIO.ChargeCurrent", 90);
		$this->EnableAction("ChargeCurrent");
		
		$this->RegisterVariableInteger("PowerStatus", "Power Status", "IPS2GPIO.PowerStatus", 100);
		$this->DisableAction("PowerStatus");
		
		$this->RegisterVariableInteger("LastInterrupt", "Letzte Meldung", "~UnixTimestamp", 110);
		$this->DisableAction("LastInterrupt");
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
 			
		$arrayOptions[] = array("label" => "15 dez. / 0x0Fh", "value" => 15);
		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		
		$arrayElements[] = array("type" => "Label", "label" => "Hinweise:");
		$arrayElements[] = array("type" => "Label", "label" => "- eine erfolgreiche Installation der S.USV Soft- und Hardware wird vorausgesetzt");
		$arrayElements[] = array("type" => "Label", "label" => "- der I²C-Bus ist nicht wählbar (1)");
		$arrayElements[] = array("type" => "Label", "label" => "- die GPIO-Nummer (Broadcom-Number) für die Statusänderung ist nicht wählbar (27)");
		$arrayElements[] = array("type" => "Label", "label" => "- die I2C-Nutzung muss in der Raspberry Pi-Konfiguration freigegeben werden (sudo raspi-config -> Advanced Options -> I2C Enable = true)");
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
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// Logging setzen
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*))';
			$this->SetReceiveDataFilter($Filter);
					
			If ($this->ReadPropertyBoolean("Open") == true) {
				If ($this->ReadPropertyInteger("Pin") >= 0) {
					$ResultPin = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
										  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 0)));	
				}
				
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					
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
			case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
					// Pinlevel "low" bedeutet, dass die Primärspannung anliegt - insofern alles okay. Die USV zieht den Pin auf "high", wenn sie auf den Akku umschaltet.
					
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
			// Firmware und Model
			$this->Read_Status(0x22, 4);
		}
	}
	    
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Spannung
			$this->Read_Status(0xD0, 3);
			// Strom Extern
			$this->Read_Status(0xD1, 3);
			// Strom Batterie
			$this->Read_Status(0xD2, 3);
			// Batterie Spannung
			$this->Read_Status(0xD3, 3);
			// Batterie Status
			$this->Read_Status(0xD4, 2);
			// Lade-Status und Lade-Strom (max)
			$this->Read_Status(0x35, 3);
			// Power Status
			$this->Read_Status(0x45, 2);
		}
	}
	    
	    
	// Führt eine Messung aus
	private function Read_Status(int $Register, int $Count)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			IPS_Sleep(80);
			$tries = 5;
			do {
				$this->SendDebug("Read_Status", "Ausfuehrung", 0);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_SUSV_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => $Register, "Count" => $Count)));

				If ($Result < 0) {
					$this->SendDebug("Read_Status", "Fehler beim Einlesen der Werte!", 0);
					$this->SetStatus(202);
				}
				else {
					// Daten der Messung
					If (is_array(unserialize($Result))) {
						$this->SetStatus(102);
						$DataArray = array();
						$DataArray = unserialize($Result);
						If ($Count > 1) {
							//$this->SendDebug("Read_Status", $DataArray[1], 0);
							switch ($DataArray[1]) {
								case 208:
									// Externe Spannung
									//$Voltage = (($DataArray[2] << 8) | $DataArray[3]) / 1000;
									$Voltage = (($DataArray[3] << 8) | $DataArray[2]) / 1000;
									SetValueFloat($this->GetIDForIdent("Voltage"), $Voltage);
									break;    
								case 209:
									// Externer Strom
									$PowerExtern = (($DataArray[2] << 8) | $DataArray[3]) / 100;
									SetValueFloat($this->GetIDForIdent("PowerExtern"), $PowerExtern);
									break;
								case 210:
									// Batterie Strom
									$PowerBattery = (($DataArray[2] << 8) | $DataArray[3]) / 100;
									SetValueFloat($this->GetIDForIdent("PowerBattery"), $PowerBattery);
									break;
								case 211:
									// Batterie Spannung
									//$BatteryVoltage = (($DataArray[2] << 8) | $DataArray[3]) / 1000;
									$BatteryVoltage = (($DataArray[3] << 8) | $DataArray[2]) / 1000;
									SetValueFloat($this->GetIDForIdent("BatteryVoltage"), $BatteryVoltage);
									break;
								case 212:
									// Batterie Status
									SetValueInteger($this->GetIDForIdent("BatteryStatus"), $DataArray[2]);
									$this->SendDebug("Read_Status", "Batterie Status: ".$DataArray[2], 0);
									break;
								case 53:
									// Lade Status / Strom
									$ChargeStatus = $DataArray[2];
									SetValueInteger($this->GetIDForIdent("ChargeStatus"), $ChargeStatus);
									$ChargeCurrent = $DataArray[3];
									SetValueInteger($this->GetIDForIdent("ChargeCurrent"), $ChargeCurrent);
									break;
								case 69:
									// Power Status
									$PowerStatus = $DataArray[2];
									SetValueInteger($this->GetIDForIdent("PowerStatus"), $PowerStatus);
									break;
								case 34:
									// Firmware/Model
									$Firmware = floatval($DataArray[2]  + ($DataArray[3] / 100));
									SetValueFloat($this->GetIDForIdent("Firmware"), $Firmware);
									$Model = $DataArray[4];
									If ($Model == 0) {
										SetValueString($this->GetIDForIdent("Model"), "S.USV Advanced");
									}
									else {
										SetValueString($this->GetIDForIdent("Model"), "S.USV Basic");
									}
									break;
							}
						}
						else {
							/*
							If ($DataArray[1] == 212) {
								// nur Batterie Status
								SetValueInteger($this->GetIDForIdent("BatteryStatus"), $DataArray[2]);
								$this->SendDebug("Read_Status", "Batterie Status: ".$DataArray[2], 0);
							}
							*/
						}
					}
					break;
				}
			$tries--;
			} while ($tries);  
		}
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
