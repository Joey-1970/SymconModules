<?
    // Klassendefinition
    class IPS2GPIO_RPi extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
		// Diese Zeile nicht löschen.
		parent::Create();
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'I2GRPi_Measurement_1($_IPS["TARGET"]);');
		$this->RegisterPropertyBoolean("LoggingTempCPU", false);
 	    	$this->RegisterPropertyBoolean("LoggingTempGPU", false);
 	    	$this->RegisterPropertyBoolean("LoggingLoadAvg", false);
       }
 
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                 // Diese Zeile nicht löschen
                 parent::ApplyChanges();
                 //Connect to available splitter or create a new one
	         $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	   	
		// Profil anlegen
		$this->RegisterProfileInteger("megabyte", "Information", "", " MB", 0, 1000000, 1);
		$this->RegisterProfileInteger("kilobyte", "Information", "", " kb", 0, 1000000, 1);
		$this->RegisterProfileFloat("frequenzy.mhz", "Speedo", "", " MHz", 0, 10000, 0.1, 1);
		
		//Status-Variablen anlegen
		$this->RegisterVariableString("Board", "Board", "", 10);
		$this->DisableAction("Board");
		$this->RegisterVariableString("Revision", "Revision", "", 20);
		$this->DisableAction("Revision");
		$this->RegisterVariableString("Hardware", "Hardware", "", 30);
		$this->DisableAction("Hardware");
		$this->RegisterVariableString("Serial", "Serial", "", 40);
		$this->DisableAction("Serial");
		$this->RegisterVariableString("Software", "Software", "", 50);
		$this->DisableAction("Software");
		$this->RegisterVariableInteger("MemoryCPU", "Memory CPU", "megabyte", 60);
		$this->DisableAction("MemoryCPU");
		$this->RegisterVariableInteger("MemoryGPU", "Memory GPU", "megabyte", 70);
		$this->DisableAction("MemoryGPU");
		$this->RegisterVariableString("Hostname", "Hostname", "", 80);
		$this->DisableAction("Hostname");
		$this->RegisterVariableString("Uptime", "Uptime", "", 90);
		$this->DisableAction("Uptime");
		// CPU/GPU
		$this->RegisterVariableFloat("TemperaturCPU", "Temperature CPU", "~Temperature", 100);
		$this->DisableAction("TemperaturCPU");
		$this->RegisterVariableFloat("TemperaturGPU", "Temperature GPU", "~Temperature", 110);
		$this->DisableAction("TemperaturGPU");
		$this->RegisterVariableFloat("VoltageCPU", "Voltage CPU", "~Volt", 120);
		$this->DisableAction("VoltageCPU");
		$this->RegisterVariableFloat("ARM_Frequenzy", "ARM Frequenzy", "frequenzy.mhz", 130);
		$this->DisableAction("ARM_Frequenzy");
		$this->RegisterVariableFloat("AverageLoad1Min", "CPU AverageLoad 1 Min", "~Intensity.1", 140);
		$this->DisableAction("AverageLoad1Min");
		$this->RegisterVariableFloat("AverageLoad5Min", "CPU AverageLoad 5 Min", "~Intensity.1", 150);
		$this->DisableAction("AverageLoad5Min");
		$this->RegisterVariableFloat("AverageLoad15Min", "CPU AverageLoad 15 Min", "~Intensity.1", 160);
		$this->DisableAction("AverageLoad15Min");
		// Arbeitsspeicher
		$this->RegisterVariableFloat("MemoryTotal", "Memory Total", "megabyte", 200);
		$this->DisableAction("MemoryTotal");
		$this->RegisterVariableFloat("MemoryFree", "Memory Free", "megabyte", 210);
		$this->DisableAction("MemoryFree");
		$this->RegisterVariableFloat("MemoryAvailable", "Memory Available", "megabyte", 220);
		$this->DisableAction("MemoryAvailable");
		// SD-Card
		$this->RegisterVariableFloat("SD_Card_Total", "SD-Card Total", "megabyte", 300);
		$this->DisableAction("SD_Card_Total");
		$this->RegisterVariableFloat("SD_Card_Used", "SD-Card Used", "megabyte", 310);
		$this->DisableAction("SD_Card_Used");
		$this->RegisterVariableFloat("SD_Card_Available", "SD-Card Available", "megabyte", 320);
		$this->DisableAction("SD_Card_Available");
		$this->RegisterVariableFloat("SD_Card_Used_rel", "SD-Card Used (rel)", "~Intensity.1", 330);
		$this->DisableAction("SD_Card_Used_rel");
		
                If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("TemperaturCPU"), $this->ReadPropertyBoolean("LoggingTempCPU"));
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("TemperaturGPU"), $this->ReadPropertyBoolean("LoggingTempGPU"));
			AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("AverageLoad15Min"), $this->ReadPropertyBoolean("LoggingLoadAvg"));
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);

			//ReceiveData-Filter setzen
			$Filter = '(.*"Function":"get_start_trigger".*|.*"InstanceID":'.$this->InstanceID.'.*)';
			$this->SetReceiveDataFilter($Filter);
				
			$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
			$this->Measurement();
			$this->Measurement_1();
			$this->SetStatus(102);
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
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "set_RPi_connect":
				$ResultArray = unserialize(utf8_decode($data->Result));
				If ($data->CommandNumber == 0) {
					for ($i = 0; $i < Count($ResultArray); $i++) {
						switch(key($ResultArray)) {
							case "0":
								// Betriebssystem
								$Result = $ResultArray[key($ResultArray)];
								SetValueString($this->GetIDForIdent("Software"), $Result);
								break;
							case "1":
								// Hardware-Daten
								$HardwareArray = explode("\n", $ResultArray[key($ResultArray)]);
								for ($j = 0; $j <= Count($HardwareArray) - 1; $j++) {
								    	If (Substr($HardwareArray[$j], 0, 8) == "Hardware") {
										$PartArray = explode(":", $HardwareArray[$j]);
										SetValueString($this->GetIDForIdent("Hardware"), trim($PartArray[1]));
									}
									If (Substr($HardwareArray[$j], 0, 8) == "Revision") {
										$PartArray = explode(":", $HardwareArray[$j]);
										SetValueString($this->GetIDForIdent("Revision"), trim($PartArray[1]));
										SetValueString($this->GetIDForIdent("Board"), $this->GetHardware(hexdec($PartArray[1])) );
									}
									If (Substr($HardwareArray[$j], 0, 6) == "Serial") {
										$PartArray = explode(":", $HardwareArray[$j]);
										SetValueString($this->GetIDForIdent("Serial"), trim($PartArray[1]));
									}

								}
								break;
							case "2":
								// CPU Speicher
								$Result = intval(substr($ResultArray[key($ResultArray)], 4, -1));
								SetValueInteger($this->GetIDForIdent("MemoryCPU"), $Result);
								break;
							case "3":
								// GPU Speicher
								$Result = intval(substr($ResultArray[key($ResultArray)], 4, -1));
								SetValueInteger($this->GetIDForIdent("MemoryGPU"), $Result);
								break;
							case "4":
								// Hostname
								$Result = trim($ResultArray[key($ResultArray)]);
								SetValueString($this->GetIDForIdent("Hostname"), $Result);
								break;
							
						}
						Next($ResultArray);
					}
				}
				elseIf ($data->CommandNumber == 1) {
					for ($i = 0; $i < Count($ResultArray); $i++) {
						switch(key($ResultArray)) {
							case "0":
								// GPU Temperatur
								$Result = floatval(substr($ResultArray[key($ResultArray)], 5, -2));
								SetValueFloat($this->GetIDForIdent("TemperaturGPU"), $Result);
								break;

							case "1":
								// CPU Temperatur
								$Result = floatval(intval($ResultArray[key($ResultArray)]) / 1000);
								SetValueFloat($this->GetIDForIdent("TemperaturCPU"), $Result);
								break;
							case "2":
								// CPU Spannung
								$Result = floatval(substr($ResultArray[key($ResultArray)], 5, -1));
								SetValueFloat($this->GetIDForIdent("VoltageCPU"), $Result);
								break;
							case "3":
								// ARM Frequenz
								$Result = intval(substr($ResultArray[key($ResultArray)], 14))/1000000;
								SetValueFloat($this->GetIDForIdent("ARM_Frequenzy"), $Result);
								break;
							case "4":
								// Auslastung
								$ResultPart = preg_Split("/[\s,]+/", $ResultArray[key($ResultArray)]);
								SetValueFloat($this->GetIDForIdent("AverageLoad1Min"), $ResultPart[0]);
								SetValueFloat($this->GetIDForIdent("AverageLoad5Min"), $ResultPart[1]);
								SetValueFloat($this->GetIDForIdent("AverageLoad15Min"), $ResultPart[2]);
								break;
							case "5":
								// Speicher
								$MemArray = explode("\n", $ResultArray[key($ResultArray)]);
								SetValueFloat($this->GetIDForIdent("MemoryTotal"), intval(substr($MemArray[0], 16, -3)) / 1000);
								SetValueFloat($this->GetIDForIdent("MemoryFree"), intval(substr($MemArray[1], 16, -3)) / 1000);
								SetValueFloat($this->GetIDForIdent("MemoryAvailable"), intval(substr($MemArray[2], 16, -3)) / 1000);
								break;
							case "6":
								// SD-Card
								$Result = trim(substr($ResultArray[key($ResultArray)], 10, -4));
								// Array anhand der Leerzeichen trennen
								$MemArray = explode(" ", $Result);
								// Leere ArrayValues löschen
								$MemArray = array_filter($MemArray);
								// Array neu durchnummerieren
								$MemArray = array_merge($MemArray);
								//IPS_LogMessage("IPS2GPIO RPi", serialize($MemArray));
								SetValueFloat($this->GetIDForIdent("SD_Card_Total"), intval($MemArray[0]) / 1000);
								SetValueFloat($this->GetIDForIdent("SD_Card_Used"), intval($MemArray[1]) / 1000);
								SetValueFloat($this->GetIDForIdent("SD_Card_Available"), intval($MemArray[2]) / 1000);
								SetValueFloat($this->GetIDForIdent("SD_Card_Used_rel"), intval($MemArray[3]) / 100 );
								break;
							case "7":
								// Uptime
								$UptimeArray = explode(",", $ResultArray[key($ResultArray)]);
								$pos = strpos($UptimeArray[0], "days");
								if ($pos !== false) {
								    SetValueString($this->GetIDForIdent("Uptime"), trim(substr($UptimeArray[0].$UptimeArray[1], 12)));
								} else {
								    SetValueString($this->GetIDForIdent("Uptime"), trim(substr($UptimeArray[0], 12)));
								}
								//IPS_LogMessage("IPS2GPIO RPi", $ResultArray[key($ResultArray)]);
								break;
						}
						Next($ResultArray);
					}
				}
				break;
			case "get_start_trigger":
			   	$this->ApplyChanges();
				break;
	 	}
	return;
 	}
	// Beginn der Funktionen
	public function Measurement()
	{
		// Daten werden nur einmalig nach Start oder bei Änderung eingelesen
		$CommandArray = Array();
		// Betriebsystem
		$CommandArray[0] = "cat /proc/version";
		// Hardware-Daten
		$CommandArray[1] = "cat /proc/cpuinfo";
		// CPU Speicher
		$CommandArray[2] = "vcgencmd get_mem arm";
		// GPU Speicher
		$CommandArray[3] = "vcgencmd get_mem gpu";
		// Hostname
		$CommandArray[4] = "hostname";
		
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray), "CommandNumber" => 0, "IsArray" => true )));
	}
	    
	    
	 // Führt eine Messung aus
	public function Measurement_1()
	{
		$CommandArray = Array();
		// GPU Temperatur
		$CommandArray[0] = "/opt/vc/bin/vcgencmd measure_temp";
		// CPU Temperatur
		$CommandArray[1] = "cat /sys/class/thermal/thermal_zone0/temp";
		// Spannung
		$CommandArray[2] = "/opt/vc/bin/vcgencmd measure_volts";
		// ARM Frequenz
		$CommandArray[3] = "vcgencmd measure_clock arm";
		// CPU Auslastung
		$CommandArray[4] = "cat /proc/loadavg";
		// Speicher
		$CommandArray[5] = "cat /proc/meminfo | grep Mem";
		// SD-Card
		$CommandArray[6] = "df -P | grep /dev/root";
		// Uptime
		$CommandArray[7] = "uptime";
		
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray), "CommandNumber" => 1, "IsArray" => true )));
	}
 	
	public function PiReboot()
	{
		$Command = "sudo reboot";
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => $Command, "CommandNumber" => 3, "IsArray" => false )));
		
	}    
	
	public function PiShutdown()
	{
		$Command = "sudo shutdown –h";
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => $Command, "CommandNumber" => 3, "IsArray" => false )));
		
	}       
	    
	public function SetDisplayPower(bool $Value)
	{
		If ($Value == true) {
			$Status = 1;
		}
		else {
			$Status = 0;
		}
		$Command = "vcgencmd display_power ".$Status;
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => $Command, "CommandNumber" => 3, "IsArray" => false )));
		
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
	
	private function GetHardware(Int $RevNumber)
	{
		$Hardware = array(2 => "Rev.0002 Model B PCB-Rev. 1.0 256MB", 3 => "Rev.0003 Model B PCB-Rev. 1.0 256MB", 4 => "Rev.0004 Model B PCB-Rev. 2.0 256MB Sony", 5 => "Rev.0005 Model B PCB-Rev. 2.0 256MB Qisda", 
			6 => "Rev.0006 Model B PCB-Rev. 2.0 256MB Egoman", 7 => "Rev.0007 Model A PCB-Rev. 2.0 256MB Egoman", 8 => "Rev.0008 Model A PCB-Rev. 2.0 256MB Sony", 9 => "Rev.0009 Model A PCB-Rev. 2.0 256MB Qisda",
			13 => "Rev.000d Model B PCB-Rev. 2.0 512MB Egoman", 14 => "Rev.000e Model B PCB-Rev. 2.0 512MB Sony", 15 => "Rev.000f Model B PCB-Rev. 2.0 512MB Qisda", 16 => "Rev.0010 Model B+ PCB-Rev. 1.0 512MB Sony",
			17 => "Rev.0011 Compute Module PCB-Rev. 1.0 512MB Sony", 18 => "Rev.0012 Model A+ PCB-Rev. 1.1 256MB Sony", 19 => "Rev.0013 Model B+ PCB-Rev. 1.2 512MB", 20 => "Rev.0014 Compute Module PCB-Rev. 1.0 512MB Embest",
			21 => "Rev.0015 Model A+ PCB-Rev. 1.1 256/512MB Embest", 10489920 => "Rev.a01040 2 Model B PCB-Rev. 1.0 1GB", 10489921 => "Rev.a01041 2 Model B PCB-Rev. 1.1 1GB Sony", 10620993 => "Rev.a21041 2 Model B PCB-Rev. 1.1 1GB Embest",
			10625090 => "Rev.a22042 2 Model B PCB-Rev. 1.2 1GB Embest", 9437330 => "Rev.900092 Zero PCB-Rev. 1.2 512MB Sony", 9437331 => "Rev.900093 Zero PCB-Rev. 1.3 512MB Sony", 10494082 => "Rev.a02082 3 Model B PCB-Rev. 1.2 1GB Sony",
			10625154 => "Rev.a22082 3 Model B PCB-Rev. 1.2 1GB Embest");
		If (array_key_exists($RevNumber, $Hardware)) {
			$HardwareText = $Hardware[$RevNumber];
		}
		else {
			$HardwareText = "Unbekannte Revisions Nummer!";
		}
	return $HardwareText;
	}
}
?>
