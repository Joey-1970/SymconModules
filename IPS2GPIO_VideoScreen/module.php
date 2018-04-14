<?
    // Klassendefinition
    class IPS2GPIO_VideoScreen extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("RunningTime", 0);
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
		$this->RegisterPropertyInteger("RunningTime", 0);
		$this->RegisterTimer("RunningTime", 0, 'I2GVS_MotorControl($_IPS["TARGET"], 1);');
		
		// Profilen anlegen
		$this->RegisterProfileInteger("IPS2GPIO.MotorControl", "Information", "", "", 0, 2, 0);
		IPS_SetVariableProfileAssociation("IPS2GPIO.MotorControl", 0, "<=", "HollowArrowLeft", 0x00FF00);
		IPS_SetVariableProfileAssociation("IPS2GPIO.MotorControl", 1, "Stop", "Cross", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2GPIO.MotorControl", 2, "=>", "HollowArrowRight", 0x00FF00);
		
		// Status-Variablen anlegen
		$this->RegisterVariableInteger("Motor", "Leinwand", "IPS2GPIO.MotorControl", 10);
		$this->EnableAction("Motor");
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
		$arrayElements[] = array("type" => "Label", "label" => "Laufzeit: Maximale Laufzeit in Sekunden (0 = aus)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "RunningTime", "caption" => "Sekunden");
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
		
		SetValueInteger($this->GetIDForIdent("Motor"), 1);
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// Logging setzen
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
					
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					$this->Setup();
					$this->SetStatus(102);
				}
			}
			else {
				$this->SetStatus(104);
			}
		}
		else {
			$this->SetStatus(104);
		}
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Motor":
	           	If ($this->ReadPropertyBoolean("Open") == true) {
				$this->MotorControl($Value);
		    	}
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
	 	}
 	}
	
	// Beginn der Funktionen
	// Führt eine Messung aus
	public function Read_Status()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Read_Status", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8574_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00)));
			If ($Result < 0) {
				$this->SendDebug("Read_Status", "Fehler beim Einlesen der Ausgänge!", 0);
				return -1;
			}
			else {
				// Daten der Messung
				$StatusArray = array(1 => 2, 2 => 0, 3 => 1);
				$StatusTextArray = array(1 => "Rechtslauf", 2 => "Linkslauf", 3 => "Stop");
				$Result = $Result & 3;
				If (GetValueInteger($this->GetIDForIdent("Motor")) <> $StatusArray[$Result]) {
					SetValueInteger($this->GetIDForIdent("Motor"), $StatusArray[$Result]);
				}
				$this->SendDebug("Read_Status", $StatusTextArray[$Result], 0);
				$this->SetBuffer("Status", $Result);
			}
		}
	return $Result;
	}
	
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			$Bitmask = 3;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8574_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00, "Value" => $Bitmask)));
			If (!$Result) {
				$this->SendDebug("SetOutput", "Setzen der Ausgaenge fehlerhaft!", 0);
				return;
			}
			else {
				$this->Read_Status();
			}
		}
	}
	
	public function MotorControl(Int $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("MotorControl", "Ausfuehrung", 0);
			$Value = min(2, max(0, $Value));
			// Aktuellen Status abfragen
			$Status = intval($this->GetBuffer("Status"));
			If ($Status >= 0) {
				// Stopvarianten
				If (($Status == 3) AND ($Value == 1)) {
					// Wenn gestoppt wird obwohl im Status Stop
					$this->SendDebug("MotorControl", "Keine Aktion notwendig", 0);
				}
				elseif (($Status <> 3) AND ($Value == 1)) {
					// Wenn gestoppt werden soll
					$this->SendDebug("MotorControl", "Stop", 0);
					$this->Set_Status(3);
					$this->SetTimerInterval("RunningTime", 0);
				}
				// Linkslaufvarianten
				elseif (($Status == 2) AND ($Value == 0)) {
					// Wenn Linkslauf angefordert wird obwohl im Linkslauf
					$this->SendDebug("MotorControl", "Keine Aktion notwendig", 0);
				}
				elseif (($Status == 3) AND ($Value == 0)) {
					// wenn Linkslauf angefordert wird und aktuell gestoppt
					$this->SendDebug("MotorControl", "Linkslauf", 0);
					$this->Set_Status(2);
					$this->SetTimerInterval("RunningTime", ($this->ReadPropertyInteger("RunningTime") * 1000));
				}
				elseif (($Status == 1) AND ($Value == 0)) {
					// wenn Linkslauf angefordert wird und aktuell Rechtslauf
					$this->SendDebug("MotorControl", "Linkslauf", 0);
					$this->Set_Status(3);
					IPS_Sleep(50);
					$this->Set_Status(2);
					$this->SetTimerInterval("RunningTime", ($this->ReadPropertyInteger("RunningTime") * 1000));
				}
				// Rechtslaufvarianten
				elseif (($Status == 1) AND ($Value == 2)) {
					// Wenn Rechtslauf angefordert wird obwohl im Rechtslauf
					$this->SendDebug("MotorControl", "Keine Aktion notwendig", 0);
				}
				elseif (($Status == 3) AND ($Value == 2)) {
					// wenn Rechtslauf angefordert wird und aktuell gestoppt
					$this->SendDebug("MotorControl", "Rechtslauf", 0);
					$this->Set_Status(1);
					$this->SetTimerInterval("RunningTime", ($this->ReadPropertyInteger("RunningTime") * 1000));
				}
				elseif (($Status == 2) AND ($Value == 2)) {
					// wenn Rechtslauf angefordert wird und aktuell Linkslauf
					$this->SendDebug("MotorControl", "Rechtslauf", 0);
					$this->Set_Status(3);
					IPS_Sleep(50);
					$this->Set_Status(1);
					$this->SetTimerInterval("RunningTime", ($this->ReadPropertyInteger("RunningTime") * 1000));
				}
			}
		}
	}
	
	private function Set_Status(Int $Value)
	{
		$Value = min(3, max(0, $Value));
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_PCF8574_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00, "Value" => $Value)));

		If (!$Result) {
			$this->SendDebug("MotorControl", "Setzen des Ausgangs fehlerhaft!", 0);
		}
		else {
			$StatusArray = array(1 => 2, 2 => 0, 3 => 1);
			SetValueInteger($this->GetIDForIdent("Motor"), $StatusArray[$Value]);
			$this->Read_Status();
			
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
