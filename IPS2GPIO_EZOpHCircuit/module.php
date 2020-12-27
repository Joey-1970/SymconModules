<?
    // Klassendefinition
    class IPS2GPIO_EZOpHCircuit extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 99);
		$this->RegisterPropertyInteger("DeviceBus", 1);
 	    	$this->RegisterPropertyInteger("Messzyklus", 60);
            	$this->RegisterTimer("Messzyklus", 0, 'EZOpHCircuit_Measurement($_IPS["TARGET"]);');
		
		// Profil anlegen
	    			
		
		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Firmware", "Firmware", "", 10);
		
		$this->RegisterVariableBoolean("LED", "LED", "~Switch", 20);
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
		$arrayOptions[] = array("label" => "99 dez. / 0x63h", "value" => 99);

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
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 1 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		
		
		$arrayActions = array(); 
		$arrayActions[] = array("type" => "Label", "label" => "Test Center"); 
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
	// Beginn der Funktionen

	// Führt eine Messung aus
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Measurement", "Ausfuehrung", 0);
			
		}
	}	
	    
	public function SetLEDState(bool $State)			
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetLEDState", "Ausfuehrung", 0);
			$Message = "L,".intval($State);
			$MessageArray = (unpack("C*", $Message));
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_EZOphCircuit_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Parameter" => serialize($MessageArray) )));
			$this->SendDebug("SetLEDState", "Ergebnis: ".$Result, 0);
			If (!$Result) {
				$this->SendDebug("Setup", "SetLEDState setzen fehlerhaft!", 0);
				$this->SetStatus(202);
				return false;
			}
		return true;
		}
	}
	    
	public function GetLEDState()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetLEDState", "Ausfuehrung", 0);
			$Message = "L,?";
			$MessageArray = (unpack("C*", $Message));
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_EZOphCircuit_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Parameter" => serialize($MessageArray) )));
			$this->SendDebug("GetLEDState", "Ergebnis: ".$Result, 0);
			If (!$Result) {
				$this->SendDebug("Setup", "GetLEDState setzen fehlerhaft!", 0);
				$this->SetStatus(202);
				return false;
			}
			else {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_EZOphCircuit_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Count" => 4 )));
				$this->SendDebug("GetLEDState", "Ergebnis: ".$Result, 0);
				If ($Result < 0) {
					$this->SendDebug("Setup", "GetLEDState lesen fehlerhaft!", 0);
					$this->SetStatus(202);
					return false;
				}
				else {
					$this->SetStatus(102);
					$ResultData = array();
					$ResultData = unserialize($Result);
					$ResultString = implode(array_map("chr", $ResultData)); 
					$this->SendDebug("GetLEDState", "Ergebnis: ".$ResultString, 0);
					$this->ReadResult($ResultString);
				}
			}
		return true;
		}
	}	
	
	public function GetFirmware()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetFirmware", "Ausfuehrung", 0);
			$Message = "i";
			$MessageArray = (unpack("C*", $Message));
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_EZOphCircuit_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Parameter" => serialize($MessageArray) )));
			$this->SendDebug("GetFirmware", "Ergebnis: ".$Result, 0);
			If (!$Result) {
				$this->SendDebug("Setup", "GetFirmware setzen fehlerhaft!", 0);
				$this->SetStatus(202);
				return false;
			}
			else {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_EZOphCircuit_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Count" => 10 )));
				$this->SendDebug("GetFirmware", "Ergebnis: ".$Result, 0);
				If ($Result < 0) {
					$this->SendDebug("Setup", "GetFirmware lesen fehlerhaft!", 0);
					$this->SetStatus(202);
					return false;
				}
				else {
					$this->SetStatus(102);
					$ResultData = array();
					$ResultData = unserialize($Result);
					$ResultString = implode(array_map("chr", $ResultData)); 
					$this->SendDebug("GetFirmware", "Ergebnis: ".$ResultString, 0);
					$this->ReadResult($ResultString);
				}
			}
		return true;
		}
	}    
	
	private function ReadResult($ResultString)
	{
		$ResultParts = explode(",", $ResultString);
		switch ($ResultParts[0]) {
			case "L":
				$this->SendDebug("ReadResult", "LED", 0);
				$this->SetValue("LED", boolval($ResultParts[1]));
				break;
			case "i":
				$this->SendDebug("ReadResult", "Device Information", 0);
				$this->SetValue("Firmware", floatval($ResultParts[2]));
				break;
			
			default:
			    throw new Exception("Invalid Ident");
	    	}
		
	}
	    
	/*
	255 no data to send
	254 still processing, not ready
	2 syntax error
	1 successful request
	*/
	    
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
