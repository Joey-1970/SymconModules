<?
    // Klassendefinition
    class IPS2GPIO_EZOORPCircuit extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 98);
		$this->RegisterPropertyInteger("DeviceBus", 1);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'EZOORPCircuit_GetORPValue($_IPS["TARGET"]);');
		
		// Profil anlegen
		$this->RegisterProfileFloat("IPS2GPIO.V", "Electricity", "", " V", -100000, +100000, 0.1, 3);
		
		$this->RegisterProfileFloat("IPS2GPIO.mV", "Electricity", "", " mV", -100000, +100000, 0.1, 3);
		
		$this->RegisterProfileInteger("IPS2GPIO.Restart", "Information", "", "", 0, 5, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Restart", 0, "powered off", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Restart", 1, "software reset", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Restart", 2, "brown out", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Restart", 3, "watchdog", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Restart", 4, "unknown", "", -1);	
		
		$this->RegisterProfileInteger("IPS2GPIO.ORP_Rating", "Gauge", "", "", 0, 4, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.ORP_Rating", 0, "Zu Niedrig", "Warning", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2GPIO.ORP_Rating", 1, "Ideal", "Ok", 0x00FF00);
		IPS_SetVariableProfileAssociation("IPS2GPIO.ORP_Rating", 2, "Zu Hoch", "Warning", 0xFF0000);
		
		$this->RegisterProfileInteger("IPS2GPIO.Calibration", "Gauge", "", "", 0, 4, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Calibration", 0, "Keine", "Warning", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Calibration", 1, "Ein-Punkt-Kalibrierung", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Calibration", 2, "Zwei-Punkt-Kalibrierung", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Calibration", 3, "Drei-Punkt-Kalibrierung", "", -1);	
		
		//Status-Variablen anlegen
		$this->RegisterVariableString("DeviceType", "Device Typ", "", 10);
		$this->RegisterVariableString("Firmware", "Firmware", "", 20);
		$this->RegisterVariableInteger("Restart", "Letzter Neustart", "IPS2GPIO.Restart", 30);
		$this->RegisterVariableFloat("Voltage", "Volt", "IPS2GPIO.V", 40);
		$this->RegisterVariableFloat("ORP", "ORP", "IPS2GPIO.mV", 50);
		$this->RegisterVariableFloat("Deviation", "Abweichung", "", 55);
		$this->RegisterVariableInteger("ORP_Rating", "ORP Bewertung", "IPS2GPIO.ORP_Rating", 60);
		$this->RegisterVariableInteger("Calibration", "Kalibration", "IPS2GPIO.Calibration", 70);
		
		$this->RegisterVariableBoolean("LED", "LED", "~Switch", 80);
		$this->EnableAction("LED");
        }
	    
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "I²C-Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "98 dez. / 0x62h", "value" => 98);

		$arrayElements[] = array("type" => "Select", "name" => "DeviceAddress", "caption" => "Device Adresse", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "I²C-Bus (Default ist 1)");
		$arrayOptions = array();
		$DevicePorts = array();
		$DevicePorts = unserialize($this->Get_I2C_Ports());
		foreach($DevicePorts AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "DeviceBus", "caption" => "Device Bus", "options" => $arrayOptions );

		$arrayElements[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Messzyklus", "caption" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)", "suffix" => "Sekunden", "minimum" => 0);		
		$arrayActions = array(); 
		$arrayActions[] = array("type" => "Label", "caption" => "Wichtiger Hinweis: Bitte dazu die Bedienungsanleitung beachten!"); 
		$ArrayRowLayout = array();
		$ArrayRowLayout[] = array("type" => "Button", "caption" => "Kalibrierung", "onClick" => 'EZOORPCircuit_Calibration($id, $Value);'); 
		$ArrayRowLayout[] = array("type" => "NumberSpinner", "name" => "Value", "caption" => "Wert");
		$arrayActions[] = array("type" => "RowLayout", "items" => $ArrayRowLayout);
		$arrayActions[] = array("type" => "Button", "caption" => "Kalibrierung löschen", "onClick" => 'EZOOPRCircuit_CalibrationClear($id);'); 
		$arrayActions[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayActions[] = array("type" => "Label", "caption" => "Test Center"); 
		$arrayActions[] = array("type" => "TestCenter", "name" => "TestCenter");
		
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

		If ((IPS_GetKernelRunlevel() == KR_READY) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					// Firmware und Device-Typ einlesen
					$this->GetFirmware();
					// LED Status
					$this->GetLEDState();
					// Status
					$this->GetStatusInformation();
					// Kalibrierung prüfen
					$this->GetCalibration();
					// Erste Messdaten einlesen
					$this->GetORPValue();
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				If ($this->GetStatus() <> 104) {
					$this->SetStatus(104);
				}
				$this->SetValue("LED", false);
				$this->SetValue("DeviceType", "unbekannt");
				$this->SetValue("Firmware", "unbekannt");
				$this->SetValue("ORP", 0);
				$this->SetValue("Deviation", 0);
				$this->SetValue("ORP_Rating", 0);
				$this->SetValue("Restart", 4);
				$this->SetValue("Voltage", 0);
				$this->SetValue("Calibration", 0);
			}	
		}
		else {
			$this->SetTimerInterval("Messzyklus", 0);
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
			$this->SetValue("LED", false);
			$this->SetValue("DeviceType", "unbekannt");
			$this->SetValue("Firmware", "unbekannt");
			$this->SetValue("ORP", 0);
			$this->SetValue("Deviation", 0);
			$this->SetValue("ORP_Rating", 0);
			$this->SetValue("Restart", 4);
			$this->SetValue("Voltage", 0);
			$this->SetValue("Calibration", 0);
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
	    
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
			case "LED":
				$this->SetLEDState($Value);
				break;

			default:
			    throw new Exception("Invalid Ident");
	    	}
	}
	    
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case IPS_KERNELSTARTED:
				// IPS_KERNELSTARTED
				$this->ApplyChanges();
				break;
		}
    	}     
	    
	// Beginn der Funktionen

	private function Write(string $Message)
	{
		$MessageArray = (unpack("C*", $Message));
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_EZOCircuit_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Parameter" => serialize($MessageArray) )));
		$this->SendDebug("Write", "Ergebnis: ".$Result, 0);
		If (!$Result) {
			$this->SendDebug("Setup", "Schreibvorgang fehlerhaft!", 0);
			If ($this->GetStatus() <> 202) {
				$this->SetStatus(202);
			}
			return false;
		}
		else {
			return true;
		}
	}
	
	private function Read(string $Function, int $DataCount)
	{
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_EZOCircuit_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Count" => $DataCount )));
		$this->SendDebug("Read", "Ergebnis: ".$Result, 0);
		If ($Result < 0) {
			$this->SendDebug("Read", "Lesevorgang fehlerhaft!", 0);
			If ($this->GetStatus() <> 202) {
				$this->SetStatus(202);
			}
			return false;
		}
		else {
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			$ResultData = array();
			$this->SendDebug("Read", "Roh-Ergebnis: ".$Result, 0);
			$ResultData = unserialize($Result);
			
			// erstes Element enthält das grundsätzliche Ergebnis
			$ResultQualityArray = array(1 => "Successful request", 2 => "Syntax Error", 254 => "Still processing, not ready", 255 => "No data to send");
			$FirstByte = array_shift($ResultData);
			if (array_key_exists($FirstByte, $ResultQualityArray)) {
				$this->SendDebug("Read", "Ergebnis: ".$ResultQualityArray[$FirstByte], 0);
			}
			else {
				$this->SendDebug("Read", "Das Ergebnisbyte hat einen unbekannten Status!", 0);
				return false;
			}
			
			// letztes Element muss 0 sein
			$LastByte = array_pop($ResultData);
			If ($LastByte <> 0) {
				$this->SendDebug("Read", "Letztes Byte ist ungleich 0!", 0);
				return false;
			}
			
			If (count($ResultData) > 0) {
				$ResultString = implode(array_map("chr", $ResultData)); 
				$this->SendDebug("Read", "Ergebnis: ".$ResultString, 0);
				$this->ReadResult($Function, $ResultString);
			}
			return true;
		}
	}
	
	private function ReadResult(string $Function, string $ResultString)
	{
		$this->SendDebug("ReadResult", $ResultString, 0);
		$ResultParts = explode(",", $ResultString);
		switch ($Function) {
			case "LED":
				$this->SendDebug("ReadResult", "LED", 0);
				$this->SetValue("LED", boolval($ResultParts[1]));
				break;

			case "FW":
				$this->SendDebug("ReadResult", "Device Information", 0);
				$this->SetValue("DeviceType", $ResultParts[1]);
				$this->SetValue("Firmware", $ResultParts[2]);
				break;
			
			case "ORP":
				$this->SendDebug("ReadResult", "ORP", 0);
				$this->SetValue("ORP", $ResultParts[0]);
				$this->SetValue("Deviation", round($ResultParts[0] - 750, 1));
				If ($ResultParts[0] < 700) {
					$this->SetValue("ORP_Rating", 0);
				}
				elseif ($ResultParts[0] > 800) {
					$this->SetValue("ORP_Rating", 2);
				}
				else {
					$this->SetValue("ORP_Rating", 1);
				}
				break;
				
			case "Status":
				$this->SendDebug("ReadResult", "Status", 0);
				$RestartArray = array("P", "S", "B", "W", "U");
				$this->SetValue("Restart", array_search($ResultParts[1], $RestartArray));
				$this->SetValue("Voltage", $ResultParts[2]);
				break;
				
			case "Calibration":
				$this->SendDebug("ReadResult", "Calibration", 0);
				$this->SetValue("Calibration", intval($ResultParts[1]));
				break;
			default:
			    throw new Exception("Invalid Ident");
	    	}
		
	}    
	    
	public function SetLEDState(bool $State)			
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetLEDState", "Ausfuehrung", 0);
			$Message = "L,".intval($State);
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("LED", 2);
				If ($Result == true) {
					$this->GetLEDState();
				}
				return $Result;
			}
		}
	}
	    
	public function GetLEDState()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetLEDState", "Ausfuehrung", 0);
			$Message = "L,?";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("LED", 6);
				return $Result;
			}
		}
	}	
	  
	public function GetFirmware()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetFirmware", "Ausfuehrung", 0);
			$Message = "i";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("FW", 13);
				return $Result;
			}
		return true;
		}
	}    
	
	public function GetORPValue()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetORPValue", "Ausfuehrung", 0);
			$Message = "R";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(900);
				$Result = $this->Read("ORP", 7);
				If ($Result == true) {
					$this->GetStatusInformation();
				}
				return $Result;
			}
		}
	}
	    
	public function GetStatusInformation()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetStatus", "Ausfuehrung", 0);
			$Message = "Status";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("Status", 17);
				return $Result;
			}
		}
	}
	
	public function Calibration(float $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Calibration", "Ausfuehrung", 0);
			$Message = "Cal,".$Value;
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(900);
				$Result = $this->Read("Cal", 2);
				If ($Result == true) {
					$this->GetCalibration();
				}
				return $Result;
			}
		}
	}
	    
	public function CalibrationClear()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("CalibrationClear", "Ausfuehrung", 0);
			$Message = "Cal,clear";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("CalClear", 2);
				If ($Result == true) {
					$this->GetCalibration();
				}
				return $Result;
			}
		}
	}
	    
	public function GetCalibration()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetCalibration", "Ausfuehrung", 0);
			$Message = "Cal,?";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("Calibration", 8);
				return $Result;
			}
		}
	}
	    
	public function SetI2CAddress(int $Address)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetI2CAddress", "Ausfuehrung", 0);
			If (($Address < 1) OR ($Address > 127)) {
				$this->SendDebug("SetI2CAddress", "I2C-Adresse muss zwischen 1 und 127 sein!", 0);
				return false;
			}
			$Message = "I2C,".$Address;
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				$this->SendDebug("SetI2CAddress", "Ausfuehrung erfolgreich", 0);
				return true;
			}
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
}
?>
