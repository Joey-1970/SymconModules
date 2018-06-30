<?
    // Klassendefinition
    class IPS2GPIO_PCF8591 extends IPSModule 
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
		$this->RegisterPropertyInteger("DeviceAddress", 72);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		for ($i = 0; $i <= 3; $i++) {
 	    		$this->RegisterPropertyBoolean("Ain".$i, true);
 	    		$this->RegisterPropertyBoolean("LoggingAin".$i, false);
		}
 	    	$this->RegisterPropertyBoolean("LoggingOut", false);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'I2GAD1_Measurement($_IPS["TARGET"]);');
		
		// Status-Variablen anlegen
		for ($i = 0; $i <= 3; $i++) {
			$this->RegisterVariableInteger("Channel_".$i, "Channel ".$i, "~Intensity.255", $i * 10 + 10);
			$this->DisableAction("Channel_".$i);
		}
		
		$this->RegisterVariableInteger("Output", "Output", "~Intensity.255", 50);
          	$this->EnableAction("Output");
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
		for ($i = 72; $i <= 79; $i++) {
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
		$arrayElements[] = array("type" => "Label", "label" => "Genutzte Eingänge");
		for ($i = 0; $i <= 3; $i++) {
			$arrayElements[] = array("type" => "CheckBox", "name" => "Ain".$i, "caption" => "Ain".$i);
			$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingAin".$i, "caption" => "Logging Ain".$i." aktivieren");
		}
		$arrayElements[] = array("type" => "CheckBox", "name" => "LoggingOut", "caption" => "Logging Output aktivieren");
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Hinweise:");
		$arrayElements[] = array("type" => "Label", "label" => "- die Device Adresse lautet 72 dez (0x48h) bei A0 an GND");
		$arrayElements[] = array("type" => "Label", "label" => "- die Device Adresse lautet 73 dez (0x49h) bei A0 an 5V");
		$arrayElements[] = array("type" => "Label", "label" => "- die Device Adresse kann weitere Werte bei Beschaltung von A1 und A2 annehmen");
		$arrayElements[] = array("type" => "Label", "label" => "- eine Abfrage des Ausgangs ist nicht direkt möglich, dazu muss er auf einen Eingang geschaltet werden");
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
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {	
			// Logging setzen
			for ($i = 0; $i <= 3; $i++) {
				AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Channel_".$i), $this->ReadPropertyBoolean("LoggingAin".$i)); 
			} 
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("Output"), $this->ReadPropertyBoolean("LoggingOut")); 
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);

			// Summary setzen
			$DevicePorts = array();
			$DevicePorts = unserialize($this->Get_I2C_Ports());
			$this->SetSummary("Adresse: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." Bus: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]);

			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
		
			
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
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Output":
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
			  /*
			case "set_i2c_data":
			  	If ($data->DeviceIdent == $this->GetBuffer("DeviceIdent")) {
			  		// Daten der Messung
			  		//IPS_LogMessage("IPS2GPIO PCF8591","Ergebnisse sind angekommen für Register ".$data->Register." Wert ".$data->Value. " WP ".$this->GetBuffer("WriteProtection"));
					If ($this->GetBuffer("WriteProtection") == "false") {
						//IPS_LogMessage("IPS2GPIO PCF8591","WP = false");
			  			If ($data->Register == hexdec("40")) {
							//IPS_LogMessage("IPS2GPIO PCF8591","Daten für 40");
				  			SetValueInteger($this->GetIDForIdent("Channel_0"), $data->Value);
				  		}
				   		If ($data->Register == hexdec("41")) {
				   			SetValueInteger($this->GetIDForIdent("Channel_1"), $data->Value);
				   		}	
				   		If ($data->Register == hexdec("42")) {
				   			SetValueInteger($this->GetIDForIdent("Channel_2"), $data->Value);
				   		}
				   		If ($data->Register == hexdec("43")) {
				   			SetValueInteger($this->GetIDForIdent("Channel_3"), $data->Value);
				   		}
			  		}
			  	}
			  	break;
			*/
	 	}
 	}
	
	// Beginn der Funktionen
	// Führt eine Messung aus
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Measurement", "Ausfuehrung", 0);
			for ($i = 0; $i <= 3; $i++) {
				If ($this->ReadPropertyBoolean("Ain".$i) == true) {
					// Aktualisierung der Messerte anfordern
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8591_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("40")|($i & 3) )));
					If ($Result < 0) {
						$this->SendDebug("Measurement", "Einlesen der Werte fehlerhaft!", 0);
						$this->SetStatus(202);
						return;
					}

					// Messwerte einlesen
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8591_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("40")|($i & 3) )));
					If ($Result < 0) {
						$this->SendDebug("Measurement", "Einlesen der Werte fehlerhaft!", 0);
						$this->SetStatus(202);
						return;
					}
					else {
						$this->SetStatus(102);
						If (GetValueInteger($this->GetIDForIdent("Channel_".$i)) <> $Result) {
							SetValueInteger($this->GetIDForIdent("Channel_".$i), $Result);
						}
					}
				}
				else {
					If (GetValueInteger($this->GetIDForIdent("Channel_".$i)) <> 0) {
						SetValueInteger($this->GetIDForIdent("Channel_".$i), 0);
					}
				}
			}
		}
	}
	
	public function SetOutput(Int $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetOutput", "Ausfuehrung", 0);
			$Value = min(255, max(0, $Value));
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8591_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => hexdec("40"), "Value" => $Value)));
			If (!$Result) {
				$this->SendDebug("SetOutput", "Setzen des Ausgangs fehlerhaft!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				//Neuen Wert in die Statusvariable schreiben
	            		If (GetValueInteger($this->GetIDForIdent("Output")) <> $Value) {
					SetValueInteger($this->GetIDForIdent("Output"), $Value);
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
