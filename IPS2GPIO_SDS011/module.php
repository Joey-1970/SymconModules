<?
    // Klassendefinition
    class IPS2GPIO_SDS011 extends IPSModule 
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
            	// https://github.com/kadamski/arduino_sds011/blob/master/lib/Sds011/Sds011.cpp
		// https://cdn.sparkfun.com/assets/parts/1/2/2/7/5/Laser_Dust_Sensor_Control_Protocol_V1.3.pdf
		// https://forum-raspberrypi.de/forum/thread/32634-nova-pm2-5-pm10-feinstaub-sensor-sds011-am-pi-anschlie%C3%9Fen/
		
		// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin_RxD", -1);
		$this->SetBuffer("PreviousPin_RxD", -1);
		$this->RegisterPropertyInteger("Pin_TxD", -1);
		$this->SetBuffer("PreviousPin_TxD", -1);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'I2GSDS011_QueryData($_IPS["TARGET"]);');
		$this->RegisterPropertyBoolean("Logging", false);
            	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		
		// Profil anlegen
		$Suffix = " ".chr(181)."g/m³";
		$this->RegisterProfileFloat("IPS2GPIO.SDS011", "Intensity", "", $Suffix, 0, 1000, 0.1, 1);
		
		$this->RegisterProfileInteger("IPS2GPIO.SDS011RM", "Information", "", "", 0, 1, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.SDS011RM", 0, "Aktiv Modus", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.SDS011RM", 1, "Abruf Modus", "Information", -1);
		
		$this->RegisterProfileInteger("IPS2GPIO.SDS011SW", "Information", "", "", 0, 1, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.SDS011SW", 0, "Schlaf Modus", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.SDS011SW", 1, "Arbeits Modus", "Information", -1);
		
		$this->RegisterProfileInteger("IPS2GPIO.SDS011WP", "Information", "", "", 0, 1, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.SDS011WP", 0, "Kontinuierlich", "Information", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.SDS011WP", 1, "Sparmodus", "Information", -1);
		
		// Statusvariablen anlegen
		$this->RegisterVariableFloat("PM25", "PM 2.5", "IPS2GPIO.SDS011", 10);
		$this->DisableAction("PM25");
		IPS_SetHidden($this->GetIDForIdent("PM25"), false);
		
		$this->RegisterVariableFloat("PM10", "PM 10", "IPS2GPIO.SDS011", 20);
		$this->DisableAction("PM10");
		IPS_SetHidden($this->GetIDForIdent("PM10"), false);
		
		$this->RegisterVariableFloat("PM10", "PM 10", "IPS2GPIO.SDS011", 20);
		$this->DisableAction("PM10");
		IPS_SetHidden($this->GetIDForIdent("PM10"), false);
		
		$this->RegisterVariableInteger("Firmware", "Firmware", "~UnixTimestampDate", 30);
		$this->DisableAction("Firmware");
		IPS_SetHidden($this->GetIDForIdent("Firmware"), true);
		
		$this->RegisterVariableInteger("ReportingMode", "Report Modus", "IPS2GPIO.SDS011RM", 40);
		$this->DisableAction("ReportingMode");
		IPS_SetHidden($this->GetIDForIdent("ReportingMode"), true);
		
		$this->RegisterVariableInteger("SleepWork", "Status", "IPS2GPIO.SDS011SW", 50);
		$this->DisableAction("SleepWork");
		IPS_SetHidden($this->GetIDForIdent("SleepWork"), true);
		
		$this->RegisterVariableInteger("WorkingPeriod", "Arbeitsweise", "IPS2GPIO.SDS011WP", 60);
		$this->DisableAction("WorkingPeriod");
		IPS_SetHidden($this->GetIDForIdent("WorkingPeriod"), true);
		
		$this->RegisterVariableInteger("WorkingPeriodTime", "Zeit", "", 70);
		$this->DisableAction("WorkingPeriodTime");
		IPS_SetHidden($this->GetIDForIdent("WorkingPeriodTime"), true);
        }
	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Serieller Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number)"); 
  		
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_RxD") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_RxD")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_RxD")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_RxD", "caption" => "GPIO-Nr. RxD", "options" => $arrayOptions );
		
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_TxD") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_TxD")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_TxD")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_TxD", "caption" => "GPIO-Nr. TxD", "options" => $arrayOptions );
				
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (3 sek -> Minimum)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "Logging", "caption" => "Logging aktivieren"); 
		
		$arrayActions = array();
		If ($this->ReadPropertyBoolean("Open") == true) {
					}
		else {
			$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		}
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}      
	    
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
	        // Diese Zeile nicht löschen
	      	parent::ApplyChanges();
		If ( ( intval($this->GetBuffer("PreviousPin_RxD")) <> $this->ReadPropertyInteger("Pin_RxD") ) OR ( intval($this->GetBuffer("PreviousPin_TxD")) <> $this->ReadPropertyInteger("Pin_TxD") ) ) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel RxD - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_RxD")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_RxD"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel TxD - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_TxD")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_TxD"), 0);
		}
		
        	If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// Logging setzen
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("PM25"), $this->ReadPropertyBoolean("Logging"));
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("PM10"), $this->ReadPropertyBoolean("Logging"));
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);

			// den Handle für dieses Gerät ermitteln
			If (($this->ReadPropertyInteger("Pin_RxD") >= 0) AND ($this->ReadPropertyInteger("Pin_TxD") >= 0) AND ($this->ReadPropertyBoolean("Open") == true) ) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "open_bb_serial_sds011", "Baud" => 9600, "Pin_RxD" => $this->ReadPropertyInteger("Pin_RxD"), "PreviousPin_RTxD" => $this->GetBuffer("PreviousPin_RxD"), "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "PreviousPin_TxD" => $this->GetBuffer("PreviousPin_TxD"), "InstanceID" => $this->InstanceID )));
				$this->SetBuffer("PreviousPin_RxD", $this->ReadPropertyInteger("Pin_RxD"));
				$this->SetBuffer("PreviousPin_TxD", $this->ReadPropertyInteger("Pin_TxD"));
				$Messzyklus = max(3, $this->ReadPropertyInteger("Messzyklus"));
				$this->SetTimerInterval("Messzyklus", ($Messzyklus * 1000));
				// ReportingMode auf anfragegesteuert setzen
				$this->SetReportingMode(false);
				$this->GetSleepWork();
				$this->GetWorkingPeriod();
				// Firmware ermitteln
				$this->GetFirmware();
				// Erste Daten
				$this->QueryData();
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
	       
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	public function ReceiveData($JSONString) 
	{
		$data = json_decode($JSONString);
	 	switch ($data->Function) {
			 case "get_serial":
			   	$this->ApplyChanges();
				break;
			 case "status":
			   	If (($data->Pin == $this->ReadPropertyInteger("Pin_RxD")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_TxD"))) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
	 	}
 	}
	
	// Beginn der Funktionen
	public function GetData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetData", "Ausfuehrung", 0);
			IPS_Sleep(50); // Damit alle Daten auch da sind
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "read_bb_serial", "Pin_RxD" => $this->ReadPropertyInteger("Pin_RxD") )));
			If (!$Result) {
				$this->SendDebug("GetData", "Lesen des Dateneingangs nicht erfolgreich!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				$ByteMessage = array();
				$ByteMessage = unpack("C*", $Result);
				//$this->SendDebug("GetData", $Result, 0);
				//$this->SendDebug("GetData", count($ByteMessage), 0);
				//$this->SendDebug("GetData", serialize($ByteMessage), 0);
				// Plausibilitätskontrolle
				If (count($ByteMessage) == 10) {
					If ( ($ByteMessage[1] == 0xAA) AND ($ByteMessage[10] == 0xAB) ) {
						$this->SetStatus(102);
						If ($ByteMessage[2] == 0xC0) {
							// Messwerte
							$PM25 = ($ByteMessage[4] << 8)|$ByteMessage[3];
							$PM10 = ($ByteMessage[6] << 8)|$ByteMessage[5];
							$this->SendDebug("GetData", "Messwerte: ".$PM25." - ".$PM10, 0);
							SetValueFloat($this->GetIDForIdent("PM25"), ($PM25 / 10));
							SetValueFloat($this->GetIDForIdent("PM10"), ($PM10 / 10));
						}
						elseif (($ByteMessage[2] == 0xC5) AND ($ByteMessage[3] == 0x02)) {
							// DataReportingMode
							$this->SendDebug("GetData", "ReportingMode: ".$ByteMessage[5], 0);
							SetValueInteger($this->GetIDForIdent("ReportingMode"), $ByteMessage[5]);
						}
						elseif (($ByteMessage[2] == 0xC5) AND ($ByteMessage[3] == 0x06)) {
							// Sleep and Work
							$this->SendDebug("GetData", "SleepWorkMode: ".$ByteMessage[5], 0);
							SetValueInteger($this->GetIDForIdent("SleepWork"), $ByteMessage[5]);
						}
						elseif (($ByteMessage[2] == 0xC5) AND ($ByteMessage[3] == 0x07)) {
							// Firmwareversion
							$FirmewareDate = $ByteMessage[6].".".$ByteMessage[5].".".(2000 + intval($ByteMessage[4]));
							$this->SendDebug("GetData", "Firmware: ".$FirmewareDate, 0);
							SetValueInteger($this->GetIDForIdent("Firmware"), strtotime($FirmewareDate));
						}
						elseif (($ByteMessage[2] == 0xC5) AND ($ByteMessage[3] == 0x08)) {
							// Working Period
							$WorkingPeriod = min(1, max(0, $ByteMessage[5]));
							$this->SendDebug("GetData", "WorkingPeriod: ".$WorkingPeriod." Zeit: ".$ByteMessage[5], 0);
							SetValueInteger($this->GetIDForIdent("WorkingPeriod"), $WorkingPeriod);
							SetValueInteger($this->GetIDForIdent("WorkingPeriodTime"), $ByteMessage[5]);
						}
						else {
							$this->SendDebug("GetData", "Datensatz konnte nicht identifiziert werden!", 0);
						}
					}
					else {
						$this->SendDebug("GetData", "Fehlerhafter Datensatz!", 0);
						$this->SetStatus(202);
					}
				}
				else {
					$this->SendDebug("GetData", "Falsche Datenlaenge!", 0);
					$this->SetStatus(202);
				}
			}
		}
	}				
	    
	private function GetReportingMode()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetReportingMode", "Ausfuehrung", 0);
			$Checksum = (0x02 + 0xFF + 0xFF) & 0xFF;
		
			$Message = array(0xAA, 0xB4, 0x02, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}
	    
	private function SetReportingMode(Bool $ActiveMode)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			// Entscheidet ob der SDS011 automatisch im Sekundentakt sendet oder nur auf Anfrage (per Definition nur auf Anfrage)
			$this->SendDebug("SetReportingMode", "Ausfuehrung", 0);
			$Checksum = (0x02 + 0x01 + intval(!$ActiveMode) + 0xFF + 0xFF) & 0xFF;
			
			$Message = array(0xAA, 0xB4, 0x02, 0x01, !$ActiveMode, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}    
	
	public function QueryData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("QueryData", "Ausfuehrung", 0);
			$Checksum = (0x04 + 0xFF + 0xFF) & 0xFF;
			
			$Message = array(0xAA, 0xB4, 0x04, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}       
	
	private function GetSleepWork()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetSleepWork", "Ausfuehrung", 0);
			$Checksum = (0x06 + 0xFF + 0xFF) & 0xFF;
			
			$Message = array(0xAA, 0xB4, 0x06, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}
	
	private function SetSleepWork(Bool $Active)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetSleepWork", "Ausfuehrung", 0);
			$Checksum = (0x06 + 0x01 + intval(!$Active) + 0xFF + 0xFF) & 0xFF;

			$Message = array(0xAA, 0xB4, 0x02, 0x01, intval(!$Active), 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}        
	
	private function GetWorkingPeriod()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetWorkingPeriod", "Ausfuehrung", 0);
			$Checksum = (0x08 + 0xFF + 0xFF) & 0xFF;
			
			$Message = array(0xAA, 0xB4, 0x08, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}
	
	private function SetWorkingPeriod(Int $Time)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetWorkingPeriod", "Ausfuehrung", 0);
			$Time = min(30, max(1, $Time));
			$Checksum = (0x08 + 0x01 + $Time + 0xFF + 0xFF) & 0xFF;
			
			$Message = pack("C*", 0xAA, 0xB4, 0x02, 0x01, $Time, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	} 
	   
	private function GetFirmware()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetFirmware", "Ausfuehrung", 0);
			$Checksum = (0x07 + 0xFF + 0xFF) & 0xFF;

			$Message = array(0xAA, 0xB4, 0x07, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
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
