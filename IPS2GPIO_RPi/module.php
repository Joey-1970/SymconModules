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
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'I2GRPi_Measurement_1($_IPS["TARGET"]);');
		
		// Profil anlegen
		$this->RegisterProfileFloat("IPS2GPIO.MB", "Information", "", " MB", 0, 1000000, 0.1, 1);
		$this->RegisterProfileFloat("IPS2GPIO.mhz", "Speedo", "", " MHz", 0, 10000, 0.1, 1);
		
		// Status-Variablen anlegen
		$this->RegisterVariableString("Board", "Board", "", 10);
		$this->RegisterVariableString("Revision", "Revision", "", 20);
		$this->RegisterVariableString("Hardware", "Hardware", "", 30);
		$this->RegisterVariableString("Serial", "Serial", "", 40);
		$this->RegisterVariableString("Software", "Software", "", 50);
		$this->RegisterVariableFloat("MemoryCPU", "Memory CPU", "IPS2GPIO.MB", 60);
		$this->RegisterVariableFloat("MemoryGPU", "Memory GPU", "IPS2GPIO.MB", 70);
		$this->RegisterVariableString("Hostname", "Hostname", "", 80);
		$this->RegisterVariableString("Uptime", "Uptime", "", 90);
		
		// CPU/GPU
		$this->RegisterVariableFloat("TemperaturCPU", "Temperature CPU", "~Temperature", 100);
		$this->RegisterVariableFloat("TemperaturGPU", "Temperature GPU", "~Temperature", 110);
		$this->RegisterVariableFloat("VoltageCPU", "Voltage CPU", "~Volt", 120);
		$this->RegisterVariableFloat("ARM_Frequenzy", "ARM Frequenzy", "IPS2GPIO.mhz", 130);
		
		// CPU Auslastung
		$this->RegisterVariableFloat("AverageLoad", "CPU AverageLoad", "~Intensity.1", 140);
		$this->SetBuffer("PrevTotal", 0);
		$this->SetBuffer("PrevIdle", 0);

		// Arbeitsspeicher
		$this->RegisterVariableFloat("MemoryTotal", "Memory Total", "IPS2GPIO.MB", 200);
		$this->RegisterVariableFloat("MemoryFree", "Memory Free", "IPS2GPIO.MB", 210);
		$this->RegisterVariableFloat("MemoryAvailable", "Memory Available", "IPS2GPIO.MB", 220);
		
		// SD-Card
		$this->RegisterVariableFloat("SD_Card_Total", "SD-Card Total", "IPS2GPIO.MB", 300);
		$this->RegisterVariableFloat("SD_Card_Used", "SD-Card Used", "IPS2GPIO.MB", 310);
		$this->RegisterVariableFloat("SD_Card_Available", "SD-Card Available", "IPS2GPIO.MB", 320);
		$this->RegisterVariableFloat("SD_Card_Used_rel", "SD-Card Used (rel)", "~Intensity.1", 330);
       }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 
 			
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
				
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
			//ReceiveData-Filter setzen
			$Filter = '(.*"Function":"get_start_trigger".*|.*"InstanceID":'.$this->InstanceID.'.*)';
			$this->SetReceiveDataFilter($Filter);
				
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
				$this->Measurement();
				$this->Measurement_1();
				$this->SetStatus(102);
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
			case "get_start_trigger":
			   	$this->ApplyChanges();
				break;
	 	}
 	}
	
	// Beginn der Funktionen
	public function Measurement()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND (IPS_GetKernelRunlevel() == 10103)) {
			$this->SendDebug("Measurement", "Ausfuehrung", 0);
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

			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray), "CommandNumber" => 0, "IsArray" => true )));
			$this->SendDebug("Measurement", "Daten: ".$Result, 0);
			$ResultArray = unserialize(utf8_decode($Result));
			If (is_array($ResultArray) == false) {
				$this->SendDebug("Measurement", "Fehler bei der Datenermittlung!", 0);
				return;
			}
			
			for ($i = 0; $i < Count($ResultArray); $i++) {
				switch(key($ResultArray)) {
					case "0":
						// Betriebssystem
						$Result = $ResultArray[key($ResultArray)];
						$this->SetValue("Software", $Result);
						break;
					case "1":
						// Hardware-Daten
						$HardwareArray = explode("\n", $ResultArray[key($ResultArray)]);
						for ($j = 0; $j <= Count($HardwareArray) - 1; $j++) {
							If (Substr($HardwareArray[$j], 0, 8) == "Hardware") {
								$PartArray = explode(":", $HardwareArray[$j]);
								$this->SetValue("Hardware", trim($PartArray[1]));
							}
							If (Substr($HardwareArray[$j], 0, 8) == "Revision") {
								$PartArray = explode(":", $HardwareArray[$j]);
								$this->SetValue("Revision", trim($PartArray[1]));
								$this->SetValue("Board", $this->GetHardware(hexdec($PartArray[1])) );
							}
							If (Substr($HardwareArray[$j], 0, 6) == "Serial") {
								$PartArray = explode(":", $HardwareArray[$j]);
								$this->SetValue("Serial", trim($PartArray[1]));
							}

						}
						break;
					case "2":
						// CPU Speicher
						$Result = intval(substr($ResultArray[key($ResultArray)], 4, -1));
						$this->SetValue("MemoryCPU", $Result);
						break;
					case "3":
						// GPU Speicher
						$Result = intval(substr($ResultArray[key($ResultArray)], 4, -1));
						$this->SetValue("MemoryGPU", $Result);
						break;
					case "4":
						// Hostname
						$Result = trim($ResultArray[key($ResultArray)]);
						$this->SetValue("Hostname", $Result);
						$this->SetSummary($Result);
						break;

				}
				Next($ResultArray);
			}
		}
	}
	    
	    
	 // Führt eine Messung aus
	public function Measurement_1()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND (IPS_GetKernelRunlevel() == 10103)) {
			$this->SendDebug("Measurement_1", "Ausfuehrung", 0);
			$CommandArray = Array();
			// GPU Temperatur
			$CommandArray[0] = "/opt/vc/bin/vcgencmd measure_temp";
			// CPU Temperatur
			$CommandArray[1] = "cat /sys/class/thermal/thermal_zone0/temp";
			// Spannung
			$CommandArray[2] = "/opt/vc/bin/vcgencmd measure_volts";
			// ARM Frequenz
			$CommandArray[3] = "vcgencmd measure_clock arm";
			// CPU Auslastung über /proc/stat
			$CommandArray[4] = "cat /proc/stat";
			// Speicher
			$CommandArray[5] = "cat /proc/meminfo | grep Mem";
			// SD-Card
			$CommandArray[6] = "df -P | grep /dev/root";
			// Uptime
			$CommandArray[7] = "uptime";


			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray), "CommandNumber" => 1, "IsArray" => true )));
			$this->SendDebug("Measurement_1", "Daten: ".$Result, 0);
			$ResultArray = unserialize(utf8_decode($Result));
			If (is_array($ResultArray) == false) {
				$this->SendDebug("Measurement_1", "Fehler bei der Datenermittlung!", 0);
				return;
			}
			for ($i = 0; $i < Count($ResultArray); $i++) {
				switch(key($ResultArray)) {
					case "0":
						// GPU Temperatur
						$Result = floatval(substr($ResultArray[key($ResultArray)], 5, -2));
						$Result = min(200, max(-20, $Result));
						$this->SetValue("TemperaturGPU", $Result);
						break;

					case "1":
						// CPU Temperatur
						$Result = floatval(intval($ResultArray[key($ResultArray)]) / 1000);
						$Result = min(200, max(-20, $Result));						
						$this->SetValue("TemperaturCPU", $Result);
						break;
					case "2":
						// CPU Spannung
						$Result = floatval(substr($ResultArray[key($ResultArray)], 5, -1));
						$Result = min(10, max(0, $Result));							
						$this->SetValue("VoltageCPU", $Result);
						break;
					case "3":
						// ARM Frequenz
						$Result = intval(substr($ResultArray[key($ResultArray)], 14))/1000000;
						$Result = min(2500, max(0, $Result));						
						$this->SetValue("ARM_Frequenzy", $Result);
						break;
					case "4":
						// CPU Auslastung über proc/stat
						$LoadAvgArray = explode("\n", $ResultArray[key($ResultArray)]);
						$LineOneArray = explode(" ", $LoadAvgArray[0]);
						// Array mit "cpu" und "" löschen
						unset($LineOneArray[array_search("cpu", $LineOneArray)]);
						unset($LineOneArray[array_search("", $LineOneArray)]);
						// Array neu durchnummerieren
						$LineOneArray = array_merge($LineOneArray);
						If (count($LineOneArray) >= 8) {
							$this->SendDebug(__CLASS__ . '::' . __FUNCTION__, "LineOneArray=" . print_r($LineOneArray, true), 0);
							// Idle = idle + iowait
							//$Idle = floatval($LineOneArray[3]) + floatval($LineOneArray[4]);
							$Idle = intval($LineOneArray[3]) + intval($LineOneArray[4]);
							$this->SendDebug("IPS2GPIO RPi", "idle=$LineOneArray[3], iowait=$LineOneArray[4] => Idle=$Idle", 0);
							// NonIdle = user+nice+system+irq+softrig+steal
							//$NonIdle = floatval($LineOneArray[0]) + floatval($LineOneArray[1]) + floatval($LineOneArray[2]) + floatval($LineOneArray[5]) + floatval($LineOneArray[6]) + floatval($LineOneArray[7]);
							$NonIdle = intval($LineOneArray[0]) + intval($LineOneArray[1]) + intval($LineOneArray[2]) + intval($LineOneArray[5]) + intval($LineOneArray[6]) + intval($LineOneArray[7]);

							$this->SendDebug(__CLASS__ . '::' . __FUNCTION__, "user=$LineOneArray[0], nice=$LineOneArray[1], system=$LineOneArray[2], irq=$LineOneArray[5], softrig=$LineOneArray[6], steal=$LineOneArray[7] => NonIdle=$NonIdle", 0);
							// Total = Idle + NonIdle
							$Total = $Idle + $NonIdle;
							// Differenzen berechnen
							$TotalDiff = $Total - intval($this->GetBuffer("PrevTotal"));
							$IdleDiff = $Idle - intval($this->GetBuffer("PrevIdle"));
							// Auslastung berechnen
							$CPU_Usage = (($TotalDiff - $IdleDiff) / $TotalDiff);
							$this->SendDebug(__CLASS__ . '::' . __FUNCTION__, "Total=$Total, Idle=$Idle, TotalDiff=$TotalDiff, IdleDiff=$IdleDiff, CPU_Usage=$CPU_Usage", 0);
							// Wert nur ausgeben, wenn der Buffer schon einmal mit den aktuellen Werten beschrieben wurde
							If (intval($this->GetBuffer("PrevTotal")) + intval($this->GetBuffer("PrevIdle")) > 0) {
								//IPS_LogMessage("IPS2GPIO RPi", "CPU-Auslastung bei ".$CPU_Usage."%");
								$this->SetValue("AverageLoad", $CPU_Usage);
							}
							else {
								$this->SetValue("AverageLoad", 0);
							}
							// Aktuelle Werte für die nächste Berechnung in den Buffer schreiben
							$this->SetBuffer("PrevTotal", $Total);
							$this->SetBuffer("PrevIdle", $Idle);
						}
						else {
							$this->SetValue("AverageLoad", 0);
							IPS_LogMessage("IPS2GPIO RPi", "Es ist ein unbekannter Fehler bei der CPU-Usage-Berechnung aufgetreten!");
						}
						break;
					case "5":
						// Speicher
						$MemArray = explode("\n", $ResultArray[key($ResultArray)]);
						$this->SetValue("MemoryTotal", intval(substr($MemArray[0], 16, -3)) / 1000);
						$this->SetValue("MemoryFree", intval(substr($MemArray[1], 16, -3)) / 1000);
						$this->SetValue("MemoryAvailable", intval(substr($MemArray[2], 16, -3)) / 1000);
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
						If (count($MemArray) == 4) {
							$this->SetValue("SD_Card_Total", intval($MemArray[0]) / 1000);
							$this->SetValue("SD_Card_Used", intval($MemArray[1]) / 1000);
							$this->SetValue("SD_Card_Available", intval($MemArray[2]) / 1000);
							$this->SetValue("SD_Card_Used_rel", intval($MemArray[3]) / 100 );
						}
						else {
							$this->SetValue("SD_Card_Total", 0);
							$this->SetValue("SD_Card_Used", 0);
							$this->SetValue("SD_Card_Available", 0);
							$this->SetValue("SD_Card_Used_rel", 0);
						}	
						break;
					case "7":
						// Uptime
						$UptimeArray = explode(",", $ResultArray[key($ResultArray)]);
						$pos = strpos($UptimeArray[0], "days");
						if ($pos !== false) {
						    $this->SetValue("Uptime", trim(substr($UptimeArray[0].$UptimeArray[1], 12)));
						} else {
						    $this->SetValue("Uptime", trim(substr($UptimeArray[0], 12)));
						}
						break;

				}
				Next($ResultArray);
			}
		}
	}
 	
	public function PiReboot()
	{
		$Command = "sudo reboot";
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => $Command, "CommandNumber" => 3, "IsArray" => false )));
	}    
	
	public function PiShutdown()
	{
		$Command = "sudo shutdown –h 0";
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
			10625154 => "Rev.a22082 3 Model B PCB-Rev. 1.2 1GB Embest", 44044353 => "Rev.2a01041 2 Model B PCB-Rev. 1.1 1GB Sony (overvoltage)", 10494163 => "Rev.a020d3 3 Model B+ PCB-Rev. 1.3 1GB Sony",
			10498321 => "Rev.a03111 4 Model B PCB-Rev. 1.1 1GB Sony UK", 11546897 => "Rev.b03111 4 Model B PCB-Rev. 1.1 2GB Sony UK", 12595473 => "Rev.c03111 4 Model B PCB-Rev. 1.1 4GB Sony UK");
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
