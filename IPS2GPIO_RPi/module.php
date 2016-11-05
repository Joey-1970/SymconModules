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
		$this->RegisterPropertyInteger("Messzyklus1", 60);
		$this->RegisterPropertyInteger("Messzyklus2", 60);
		$this->RegisterTimer("Messzyklus1", 0, 'I2GRPi_Measurement_1($_IPS["TARGET"]);');
		$this->RegisterTimer("Messzyklus2", 0, 'I2GRPi_Measurement_2($_IPS["TARGET"]);');
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
		$this->RegisterVariableFloat("TemperaturCPU", "Temperatur CPU", "~Temperature", 10);
		$this->DisableAction("TemperaturCPU");
		$this->RegisterVariableFloat("TemperaturGPU", "Temperatur GPU", "~Temperature", 20);
		$this->DisableAction("TemperaturGPU");
		$this->RegisterVariableFloat("VoltageCPU", "Spannung CPU", "~Volt", 30);
		$this->DisableAction("VoltageCPU");
		$this->RegisterVariableInteger("MemoryCPU", "Speicher CPU", "megabyte", 40);
		$this->DisableAction("MemoryCPU");
		$this->RegisterVariableInteger("MemoryGPU", "Speicher GPU", "megabyte", 50);
		$this->DisableAction("MemoryGPU");
		$this->RegisterVariableFloat("ARM_Frequenzy", "Taktung ARM", "frequenzy.mhz", 60);
		$this->DisableAction("ARM_Frequenzy");
		$this->RegisterVariableFloat("AverageLoad1Min", "CPU Auslastung 1 Min", "~Intensity.1", 70);
		$this->DisableAction("AverageLoad1Min");
		$this->RegisterVariableFloat("AverageLoad5Min", "CPU Auslastung 5 Min", "~Intensity.1", 80);
		$this->DisableAction("AverageLoad5Min");
		$this->RegisterVariableFloat("AverageLoad15Min", "CPU Auslastung 15 Min", "~Intensity.1", 90);
		$this->DisableAction("AverageLoad15Min");
		$this->RegisterVariableInteger("MemoryTotal", "Memory Total", "kilobyte", 100);
		$this->DisableAction("MemoryTotal");
		$this->RegisterVariableInteger("MemoryFree", "Memory Free", "kilobyte", 110);
		$this->DisableAction("MemoryFree");
		$this->RegisterVariableInteger("MemoryAvailable", "Memory Available", "kilobyte", 120);
		$this->DisableAction("MemoryAvailable");
		 
                If (IPS_GetKernelRunlevel() == 10103) {
			// Logging setzen
			/*
			for ($i = 0; $i <= 4; $i++) {
				AC_SetLoggingStatus(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0], $this->GetIDForIdent("MAC".$i), $this->ReadPropertyBoolean("LoggingMAC".$i)); 
			} 
			IPS_ApplyChanges(IPS_GetInstanceListByModuleID("{43192F0B-135B-4CE7-A0A7-1475603F3060}")[0]);
			*/

			//ReceiveData-Filter setzen
			$Filter = '(.*"Function":"get_start_trigger".*|.*"InstanceID":'.$this->InstanceID.'.*)';
			$this->SetReceiveDataFilter($Filter);
				
			$this->SetTimerInterval("Messzyklus1", ($this->ReadPropertyInteger("Messzyklus1") * 1000));
			//$this->SetTimerInterval("Messzyklus2", ($this->ReadPropertyInteger("Messzyklus2") * 1000));
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
								//$Result = floatval(substr($ResultArray[key($ResultArray)], 5, -2));
								//SetValueFloat($this->GetIDForIdent("TemperaturGPU"), $Result);
								break;

							case "1":
								// Hardware-Daten
								$HardwareArray = explode("\n", $ResultArray[key($ResultArray)]);
								IPS_LogMessage("IPS2GPIO RPi: ", "Daten: ".$ResultArray[key($ResultArray)]." Count: ".count($HardwareArray));
								//$Result = floatval(intval($ResultArray[key($ResultArray)]) / 1000);
								//SetValueFloat($this->GetIDForIdent("TemperaturCPU"), $Result);
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
								// CPU Speicher
								$Result = intval(substr($ResultArray[key($ResultArray)], 4, -1));
								SetValueInteger($this->GetIDForIdent("MemoryCPU"), $Result);
								break;
							case "4":
								// CPU Speicher
								$Result = intval(substr($ResultArray[key($ResultArray)], 4, -1));
								SetValueInteger($this->GetIDForIdent("MemoryGPU"), $Result);
								break;
							case "5":
								// ARM Frequenz
								$Result = intval(substr($ResultArray[key($ResultArray)], 14))/1000000;
								SetValueFloat($this->GetIDForIdent("ARM_Frequenzy"), $Result);
								break;
							case "6":
								// Auslastung
								$ResultPart = preg_Split("/[\s,]+/", $ResultArray[key($ResultArray)]);
								SetValueFloat($this->GetIDForIdent("AverageLoad1Min"), $ResultPart[0]);
								SetValueFloat($this->GetIDForIdent("AverageLoad5Min"), $ResultPart[1]);
								SetValueFloat($this->GetIDForIdent("AverageLoad15Min"), $ResultPart[2]);
								break;
							case "7":
								// Speicher
								$MemArray = explode("\n", $ResultArray[key($ResultArray)]);
								SetValueInteger($this->GetIDForIdent("MemoryTotal"), intval(substr($MemArray[0], 16, -3)));
								SetValueInteger($this->GetIDForIdent("MemoryFree"), intval(substr($MemArray[1], 16, -3)));
								SetValueInteger($this->GetIDForIdent("MemoryAvailable"), intval(substr($MemArray[2], 16, -3)));
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
		// CPU Speicher
		$CommandArray[3] = "vcgencmd get_mem arm";
		// GPU Speicher
		$CommandArray[4] = "vcgencmd get_mem gpu";
		// ARM Frequenz
		$CommandArray[5] = "vcgencmd measure_clock arm";
		// CPU Auslastung
		$CommandArray[6] = "cat /proc/loadavg";
		// Speicher
		$CommandArray[7] = "cat /proc/meminfo | grep Mem";
		
		
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray), "CommandNumber" => 1, "IsArray" => true )));
	}
 	
	public function Measurement_2()
	{
		//$Command = "/opt/vc/bin/vcgencmd measure_temp";
		//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_RPi_connect", "InstanceID" => $this->InstanceID,  "Command" => $Command, "CommandNumber" => 0 )));
		
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
}
?>
