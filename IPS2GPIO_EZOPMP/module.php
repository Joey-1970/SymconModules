<?
    // Klassendefinition
    class IPS2GPIO_EZOPMP extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 103);
		$this->RegisterPropertyInteger("DeviceBus", 1);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'EZOPMP_GetDispensedVolume($_IPS["TARGET"]);');
		
		// Profil anlegen
		$this->RegisterProfileFloat("IPS2GPIO.V", "Electricity", "", " V", -100000, +100000, 0.1, 3);
		
		$this->RegisterProfileFloat("IPS2GPIO.ml", "ErlenmeyerFlask", "", " ml", -100000, +100000, 0.1, 2);
		
		$this->RegisterProfileInteger("IPS2GPIO.Restart", "Information", "", "", 0, 5, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Restart", 0, "powered off", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Restart", 1, "software reset", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Restart", 2, "brown out", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Restart", 3, "watchdog", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.Restart", 4, "unknown", "", -1);	
		
		$this->RegisterProfileInteger("IPS2GPIO.CalibrationPMP", "Gauge", "", "", 0, 4, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.CalibrationPMP", 0, "Keine", "Warning", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.CalibrationPMP", 1, "Fixes Volumen", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.CalibrationPMP", 2, "Volumen/Zeit", "", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.CalibrationPMP", 3, "Fixes Volumen & Volumen/Zeit", "", -1);	
		
		$this->RegisterProfileInteger("IPS2GPIO.PumpState", "Gauge", "", "", 0, 4, 0);
		IPS_SetVariableProfileAssociation("IPS2GPIO.PumpState", 0, "unbekannt", "Warning", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.PumpState", 1, "Stop", "", -1);	
		
		//Status-Variablen anlegen
		$this->RegisterVariableString("DeviceType", "Device Typ", "", 10);
		$this->RegisterVariableString("Firmware", "Firmware", "", 20);
		$this->RegisterVariableInteger("Restart", "Letzter Neustart", "IPS2GPIO.Restart", 30);
		$this->RegisterVariableFloat("Voltage", "Volt Elektronik", "IPS2GPIO.V", 40);
		$this->RegisterVariableFloat("PumpVoltage", "Volt Pumpe", "IPS2GPIO.V", 50);
		$this->RegisterVariableBoolean("PumpState", "Status Pumpe", "~Switch", 60);
		$this->RegisterVariableInteger("PumpStateSwitch", "Status Pumpe", "IPS2GPIO.PumpState", 70);
		$this->EnableAction("PumpStateSwitch");
		$this->RegisterVariableBoolean("PausePumpState", "Pumpen Pause", "~Switch", 80);
		$this->EnableAction("PausePumpState");
		$this->RegisterVariableFloat("DispensedVolume", "Abgegebene Menge", "IPS2GPIO.ml", 90);
		$this->RegisterVariableFloat("TotalDispensedVolume", "Total abgegebene Menge", "IPS2GPIO.ml", 100);
		$this->RegisterVariableFloat("AbsoluteDispensedVolume", "Absolut abgegebene Menge", "IPS2GPIO.ml", 110);
		$this->RegisterVariableInteger("Calibration", "Kalibration", "IPS2GPIO.CalibrationPMP", 120);
		
		
		
		$this->RegisterVariableBoolean("LED", "LED", "~Switch", 130);
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
		$arrayOptions[] = array("label" => "103 dez. / 0x67h", "value" => 103);

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
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		
		$arrayActions = array(); 
		$arrayActions[] = array("type" => "Label", "caption" => "Wichtiger Hinweis: Bitte dazu die Bedienungsanleitung beachten!"); 
		$ArrayRowLayout = array();
		$ArrayRowLayout[] = array("type" => "Button", "caption" => "Kalibrierung", "onClick" => 'EZOPMP_Calibration($id, $Value);'); 
		$ArrayRowLayout[] = array("type" => "NumberSpinner", "name" => "Value", "caption" => "Wert", "digits" => 2, "minimum" => 0);
		$arrayActions[] = array("type" => "RowLayout", "items" => $ArrayRowLayout);
		$arrayActions[] = array("type" => "Button", "caption" => "Kalibrierung löschen", "onClick" => 'EZOPMP_CalibrationClear($id);'); 
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

		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					// Firmware und Device-Typ einlesen
					$this->GetFirmware();
					// LED Status
					$this->GetLEDState();
					// Status
					$this->GetStatus();
					// Pump Voltage
					$this->GetPumpVoltage();
					// Kalibrierung prüfen
					$this->GetCalibration();
					// Erste Messdaten einlesen
					$this->GetDispensedVolume();
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
			case "PausePumpState":
				$this->PauseDispensing();
				break;
			case "PumpState":
				If ($Value == 1) {
					// Stop
					$this->StopDispensing();
				}
				
				break;
			default:
			    throw new Exception("Invalid Ident");
	    	}
	}
	    
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case 10001:
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
			$this->SetStatus(202);
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
			$this->SetStatus(202);
			return false;
		}
		else {
			$this->SetStatus(102);
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
				
			case "PumpVoltage":
				$this->SendDebug("ReadResult", "PumpVoltage", 0);
				$this->SetValue("PumpVoltage", floatval($ResultParts[1]));
				break;
				
			case "DispensedVolume":
				$this->SendDebug("ReadResult", "DispensedVolume", 0);
				$this->SetValue("DispensedVolume", floatval($ResultParts[0]));
				break;
				
			case "TotalDispensedVolume":
				$this->SendDebug("ReadResult", "TotalDispensedVolume", 0);
				$this->SetValue("TotalDispensedVolume", floatval($ResultParts[1]));
				break;
				
			case "AbsoluteDispensedVolume":
				$this->SendDebug("ReadResult", "AbsoluteDispensedVolume", 0);
				$this->SetValue("AbsoluteDispensedVolume", floatval($ResultParts[1]));
				break;
			
			case "StopDispensing":
				$this->SendDebug("ReadResult", "StopDispensing", 0);
				$this->SetValue("PumpStateSwitch", 1);
				break;
				
			case "PausePumpState":
				$this->SendDebug("ReadResult", "PausePumpState", 0);
				$this->SetValue("PausePumpState", boolval($ResultParts[1]));
				break;
			
			case "PauseDispensing":
				$this->SendDebug("ReadResult", "PauseDispensing", 0);
				break;
				
			case "StartDispensing":
				$this->SendDebug("ReadResult", "StartDispensing", 0);
				$this->SetValue("PumpState", boolval($ResultParts[2]));				
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
	    
	public function GetPumpVoltage()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetPumpVoltage", "Ausfuehrung", 0);
			$Message = "PV,?";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("PumpVoltage", 11);
				return $Result;
			}
		return true;
		}
	}    
	    
	public function GetStatus()
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
				If ($Result == true) {
					$this->GetPumpVoltage();
				}
				return $Result;
			}
		}
	}
	
	public function GetDispensedVolume()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetDispensedVolume", "Ausfuehrung", 0);
			$Message = "R";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("DispensedVolume", 7);
				If ($Result == true) {
					$this->GetTotalDispensedVolume();
					$this->GetAbsoluteDispensedVolume();
					$this->GetStatus();
				}
				return $Result;
			}
		}
	}    
	
	public function GetTotalDispensedVolume()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetTotalDispensedVolume", "Ausfuehrung", 0);
			$Message = "TV,?";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("TotalDispensedVolume", 13);
				return $Result;
			}
		}
	}        
	    
	public function GetAbsoluteDispensedVolume()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetAbsoluteDispensedVolume", "Ausfuehrung", 0);
			$Message = "ATV,?";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("AbsoluteDispensedVolume", 14);
				return $Result;
			}
		}
	}        
	
	public function StopDispensing()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("StopDispensing", "Ausfuehrung", 0);
			$Message = "X";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("StopDispensing", 13);
				return $Result;
			}
		}
	}       
	    
	public function PauseDispensing()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("PauseDispensing", "Ausfuehrung", 0);
			$Message = "P";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("PauseDispensing", 2);
				If ($Result == true) {
					$this->GetPauseState();
				}
				return $Result;
			}
		}
	}         
	
	public function GetPauseState()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetPauseState", "Ausfuehrung", 0);
			$Message = "P,?";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("PausePumpState", 6);
				return $Result;
			}
		}
	}	    
	
	public function StartDispensing(Int $Milliliters, Int $Minute, Int $Direction) // $Direction true = normal, false = reverse
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("StartDispensing", "Ausfuehrung", 0);
			If (($Milliliters == 0) AND ($Minute == 0) AND ($Direction == true)) { 
				// Continuous dispensing normal
				$Message = "D,*";
			}
			elseif (($Milliliters == 0) AND ($Minute == 0) AND ($Direction == false)) { 
				// Continuous dispensing reverse
				$Message = "D,-*";
			}
			elseif (($Milliliters > 0) AND ($Minute == 0) AND ($Direction == true)) { 
				// Volume dispensing normal
				$Message = "D,".$Milliliters;
			}
			elseif (($Milliliters > 0) AND ($Minute == 0) AND ($Direction == false)) { 
				// Volume dispensing normal
				$Message = "D,-".$Milliliters;
			}
			elseif (($Milliliters > 0) AND ($Minute > 0) AND ($Direction == true)) { 
				// Dose over time dispensing normal
				$Message = "D,".$Milliliters.",".$Minute;
			}
			elseif (($Milliliters > 0) AND ($Minute > 0) AND ($Direction == false)) { 
				// Dose over time dispensing normal
				$Message = "D,-".$Milliliters.",".$Minute;
			}
			
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("StartDispensing", 2);
				If ($Result == true) {
					$this->GetDispensingState();
				}
				return $Result;
			}
		}
	}          
	
	public function GetDispensingState()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetDispensingState", "Ausfuehrung", 0);
			$Message = "D,?";
			$Result = $this->Write($Message);
			If ($Result == false) {
				return false;
			}
			else {
				IPS_Sleep(300);
				$Result = $this->Read("DispensingState", 13);
				return $Result;
			}
		}
	}	     
	    
	public function Calibration(float $Value)
	{
		// Eventuell muss hier das Komma in einen Punkt umgewandelt werden?
		
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
