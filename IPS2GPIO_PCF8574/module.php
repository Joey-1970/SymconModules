<?
    // Klassendefinition
    class IPS2GPIO_PCF8574 extends IPSModule 
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
		$this->RegisterPropertyInteger("DeviceAddress", 32);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		$this->RegisterPropertyInteger("Pin", -1);
		$this->SetBuffer("PreviousPin", -1);
		for ($i = 0; $i <= 7; $i++) {
 	    		$this->RegisterPropertyBoolean("P".$i, false);
			$this->RegisterPropertyBoolean("LoggingP".$i, false);
		}	
		$this->RegisterPropertyInteger("Startoption", 0);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GIO1_Read_Status($_IPS["TARGET"]);');
		
		// Status-Variablen anlegen
		$this->RegisterVariableInteger("LastInterrupt", "Letzte Meldung", "~UnixTimestamp", 10);
		$this->DisableAction("LastInterrupt");
		
		for ($i = 0; $i <= 7; $i++) {
			$this->RegisterVariableBoolean("P".$i, "P".$i, "~Switch", 10 * $i + 20);
		}
          	
          	$this->RegisterVariableInteger("Value", "Value", "", 100);
		$this->EnableAction("Value");
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
		for ($i = 32; $i <= 39; $i++) {
		    	$arrayOptions[] = array("label" => $i." dez. / 0x".strtoupper(dechex($i))."h", "value" => $i);
		}
		for ($i = 56; $i <= 63; $i++) {
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
		
		$arrayElements[] = array("type" => "Label", "label" => "Definition der Ein- und Ausgänge (aktiviert => Eingang)");
		for ($i = 0; $i <= 7; $i++) {
			$arrayElements[] = array("type" => "CheckBox", "name" => "P".$i, "caption" => "P".$i);
			$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingP".$i, "caption" => "Logging P".$i." aktivieren");
		}
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Eingänge High, Ausgänge Low", "value" => 0);
		$arrayOptions[] = array("label" => "Eingänge High, Ausgänge High", "value" => 1);		
		$arrayElements[] = array("type" => "Select", "name" => "Startoption", "caption" => "Startoptionen", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Hinweise:");
		$arrayElements[] = array("type" => "Label", "label" => "- die Device Adresse lautet 32 bis 39 dez (0x20h - 0x27h) bei einem PCF8574");
		$arrayElements[] = array("type" => "Label", "label" => "- die Device Adresse lautet 56 bis 63 dez (0x38h - 0x3Fh) bei einem PCF8574A");
		$arrayElements[] = array("type" => "Label", "label" => "- die I2C-Nutzung muss in der Raspberry Pi-Konfiguration freigegeben werden (sudo raspi-config -> Advanced Options -> I2C Enable = true)");
		$arrayElements[] = array("type" => "Label", "label" => "- die korrekte Nutzung der GPIO ist zwingend erforderlich (GPIO-Nr. 0/1 nur beim Raspberry Pi Model B Revision 1, alle anderen GPIO-Nr. 2/3)");
		$arrayElements[] = array("type" => "Label", "label" => "- auf den korrekten Anschluss von SDA/SCL achten");			
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
		
		$SetTimer = false;
		for ($i = 0; $i <= 7; $i++) {
			If ($this->ReadPropertyBoolean("P".$i) == true) {
				// wenn true dann Eingang, dann disable		
 				$this->DisableAction("P".$i);
				$SetTimer = true;
 			}		
 			else {		
 				// Ausgang muss manipulierbar sein		
 				$this->EnableAction("P".$i);			
 			}
		}
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// Logging setzen
			for ($i = 0; $i <= 7; $i++) {
				AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("P".$i), $this->ReadPropertyBoolean("LoggingP".$i)); 
			} 
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
			
			// Summary setzen
			$DevicePorts = array();
			$DevicePorts = unserialize($this->Get_I2C_Ports());
			$this->SetSummary("Adresse: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." Bus: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]." GPIO: ".$this->ReadPropertyInteger("Pin"));

			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin").'.*))';
			$this->SetReceiveDataFilter($Filter);
					
			If ($this->ReadPropertyBoolean("Open") == true) {
				If ($this->ReadPropertyInteger("Pin") >= 0) {
					$ResultPin = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
										  "Pin" => $this->ReadPropertyInteger("Pin"), "PreviousPin" => $this->GetBuffer("PreviousPin"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 0)));	
				}
				$this->SetBuffer("PreviousPin", $this->ReadPropertyInteger("Pin"));
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					If ($SetTimer == true) {
						$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					}
					else {
						$this->SetTimerInterval("Messzyklus", 0);
					}
					$this->Setup();
					// Erste Messdaten einlesen
					$this->Read_Status();
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
	
	public function RequestAction($Ident, $Value) 
	{
  		$Source = substr($Ident, 0, 1);
		$Pin = substr($Ident, 1, 1);
		
		switch($Source) {
	        case "P":
	            $this->SetPinOutput(intval($Pin), $Value);
	            break;
	        case "V":
	            $this->SetOutput($Value);
	            break;
	        default:
	            throw new Exception("Invalid Ident");
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
						$this->Read_Status();
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
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
	public function Read_Status()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$tries = 5;
			do {
				$this->SendDebug("Read_Status", "Ausfuehrung", 0);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8574_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00)));
				If ($Result < 0) {
					$this->SendDebug("Read_Status", "Fehler beim Einlesen der Ausgänge!", 0);
					$this->SetStatus(202);
				}
				else {
					$this->SetStatus(102);
					// Daten der Messung
					SetValueInteger($this->GetIDForIdent("Value"), $Result);
					$this->SetBuffer("Output", $Result);
					$Result  = str_pad(decbin($Result), 8, '0', STR_PAD_LEFT );
					for ($i = 0; $i <= 7; $i++) {
						SetValueBoolean($this->GetIDForIdent("P".$i), substr($Result, 7-$i, 1));
					}
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
			If ($this->ReadPropertyInteger("Startoption") == 0) {
				$Bitmask = 0;
				for ($i = 0; $i <= 7; $i++) {
					If ($this->ReadPropertyBoolean("P".$i) == true) {
						// wenn true dann Eingang		
						$Bitmask = $Bitmask + pow(2, $i);
					}		
				}
				$this->SetOutput($Bitmask);
			}
			elseif ($this->ReadPropertyInteger("Startoption") == 1) {
				$this->SetOutput(255);
			}
		}
	}
	
	public function SetPinOutput(Int $Pin, Bool $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetPinOutput", "Ausfuehrung", 0);
			// Setzt einen bestimmten Pin auf den vorgegebenen Wert
			$Pin = min(7, max(0, $Pin));
			$Value = boolval($Value);
			// Aktuellen Status abfragen
			//$this->Read_Status();
			// Bitmaske erstellen
			//$Bitmask = GetValueInteger($this->GetIDForIdent("Value"));
			$Bitmask = intval($this->GetBuffer("Output"));
			If ($Value == true) {
				$Bitmask = $this->setBit($Bitmask, $Pin);
			}
			else {
				$Bitmask = $this->unsetBit($Bitmask, $Pin);
			}
			$Bitmask = min(255, max(0, $Bitmask));
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8574_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00, "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("SetPinOutput", "Setzen des Ausgangs fehlerhaft!", 0);
				$this->SetStatus(202);
				return;
			}
			else {
				$this->SetStatus(102);
				SetValueBoolean($this->GetIDForIdent("P".$Pin), $Value);
				$this->Read_Status();
			}
		}
	}
	
	public function SetOutput(Int $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetOutput", "Ausfuehrung", 0);
			// Setzt alle Ausgänge
			$Value = min(255, max(0, $Value));
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8574_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00, "Value" => $Value)));

			If (!$Result) {
				$this->SendDebug("SetOutput", "Setzen der Ausgaenge fehlerhaft!", 0);
				$this->SetStatus(202);
				return;
			}
			else {
				$this->SetStatus(102);
				$this->Read_Status();
			}
		}
	}
	
	private function setBit($byte, $significance) { 
 		// ein bestimmtes Bit auf 1 setzen
 		return $byte | 1<<$significance;   
 	} 

	private function unsetBit($byte, $significance) {
	    // ein bestimmtes Bit auf 0 setzen
	    return $byte & ~(1<<$significance);
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
