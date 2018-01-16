<?
    // Klassendefinition
    class IPS2GPIO_MCP23017 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 32);
		$this->RegisterPropertyInteger("DeviceBus", 1);	
		$this->RegisterPropertyInteger("Pin_INT_A", -1);
		$this->SetBuffer("PreviousPin_INT_A", -1);
		$this->RegisterPropertyInteger("Pin_INT_B", -1);
		$this->SetBuffer("PreviousPin_INT_B", -1);
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterPropertyInteger("GPAIODIR".$i, 1);
			$this->RegisterPropertyInteger("GPAIPOL".$i, 0);
			$this->RegisterPropertyInteger("GPAINTEN".$i, 0);
			$this->RegisterPropertyInteger("GPADEFVAL".$i, 0);
			$this->RegisterPropertyInteger("GPAINTCON".$i, 0);
		}
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterPropertyInteger("GPBIODIR".$i, 1);
			$this->RegisterPropertyInteger("GPBIPOL".$i, 0);
			$this->RegisterPropertyInteger("GPBINTEN".$i, 0);
			$this->RegisterPropertyInteger("GPBDEFVAL".$i, 0);
			$this->RegisterPropertyInteger("GPBINTCON".$i, 0);
		}
		$this->RegisterPropertyInteger("INTPOL", 0);
		$this->RegisterPropertyInteger("ODR", 0);
		$this->RegisterPropertyInteger("MIRROR", 0);
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
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		for ($i = 32; $i <= 39; $i++) {
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
		
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt A"); 
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_INT_A") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_INT_A")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_INT_A")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_INT_A", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für den Interrupt B"); 
		$arrayOptions = array();
		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		If ($this->ReadPropertyInteger("Pin_INT_B") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_INT_B")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_INT_B")));
		}
		ksort($GPIO);
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayElements[] = array("type" => "Select", "name" => "Pin_INT_B", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration der Ports");
		$arrayOptions_IODIR = array();
		$arrayOptions_IODIR[] = array("label" => "Ausgang", "value" => 0); 
		$arrayOptions_IODIR[] = array("label" => "Eingang", "value" => 1);
		
		$arrayOptions_IPOL = array();
		$arrayOptions_IPOL[] = array("label" => "Eingang nicht negiert", "value" => 0);
		$arrayOptions_IPOL[] = array("label" => "Eingang negiert", "value" => 1); 
		
		$arrayOptions_GPINTEN = array();
		$arrayOptions_GPINTEN[] = array("label" => "kein Interrupt-Auslöser", "value" => 0);
		$arrayOptions_GPINTEN[] = array("label" => "Interrupt-Auslöser", "value" => 1); 
		
		$arrayOptions_DEFVAL = array();
		$arrayOptions_DEFVAL[] = array("label" => "Aus", "value" => 0);
		$arrayOptions_DEFVAL[] = array("label" => "Ein", "value" => 1); 
		
		$arrayOptions_INTCON = array();
		$arrayOptions_INTCON[] = array("label" => "Vorheriger Pin-Status", "value" => 0);
		$arrayOptions_INTCON[] = array("label" => "Vergleichswert", "value" => 1); 
		
		for ($i = 0; $i <= 7; $i++) {
		   	$arrayElements[] = array("type" => "Label", "label" => "Konfiguration des GPA".$i);
			$arrayElements[] = array("type" => "Select", "name" => "GPAIODIR".$i, "caption" => "Nutzung", "options" => $arrayOptions_IODIR );	
			$arrayElements[] = array("type" => "Select", "name" => "GPAIPOL".$i, "caption" => "Negation", "options" => $arrayOptions_IPOL );	
			$arrayElements[] = array("type" => "Select", "name" => "GPAINTEN".$i, "caption" => "Interrupt", "options" => $arrayOptions_GPINTEN );
			$arrayElements[] = array("type" => "Select", "name" => "GPADEFVAL".$i, "caption" => "Vergleichswert", "options" => $arrayOptions_DEFVAL );
			$arrayElements[] = array("type" => "Select", "name" => "GPAINTCON".$i, "caption" => "Interruptwert", "options" => $arrayOptions_INTCON );	
		}
		for ($i = 0; $i <= 7; $i++) {
		   	$arrayElements[] = array("type" => "Label", "label" => "Konfiguration des GPB".$i);
			$arrayElements[] = array("type" => "Select", "name" => "GPBIODIR".$i, "caption" => "Nutzung", "options" => $arrayOptions_IODIR );
			$arrayElements[] = array("type" => "Select", "name" => "GPBIPOL".$i, "caption" => "Negation", "options" => $arrayOptions_IPOL );	
			$arrayElements[] = array("type" => "Select", "name" => "GPBINTEN".$i, "caption" => "Interrupt", "options" => $arrayOptions_GPINTEN );
			$arrayElements[] = array("type" => "Select", "name" => "GPBDEFVAL".$i, "caption" => "Vergleichswert", "options" => $arrayOptions_DEFVAL );
			$arrayElements[] = array("type" => "Select", "name" => "GPBINTCON".$i, "caption" => "Interruptwert", "options" => $arrayOptions_INTCON );	
		}
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
		$arrayElements[] = array("type" => "Label", "label" => "Konfiguration des Interrupt");
		$arrayElements[] = array("type" => "Label", "label" => "Polarität des Interrupt");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Aktiv - Low", "value" => 0);
		$arrayOptions[] = array("label" => "Aktiv - High", "value" => 1); 
		$arrayElements[] = array("type" => "Select", "name" => "INTPOL", "caption" => "Polarität", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Ausgangs-Konfiguration (wenn Open-Drain ausgewählt, wird Polarität überschrieben)");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Aktiver Treiber", "value" => 0);
		$arrayOptions[] = array("label" => "Open Drain", "value" => 1); 
		$arrayElements[] = array("type" => "Select", "name" => "ODR", "caption" => "Konfiguration", "options" => $arrayOptions );
		
		$arrayElements[] = array("type" => "Label", "label" => "Spiegelung der Interrupt-Ausgänge");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Int A -> Port A, Int B -> Port B", "value" => 0);
		$arrayOptions[] = array("label" => "Interrupt ist intern verbunden", "value" => 1); 
		$arrayElements[] = array("type" => "Select", "name" => "MIRROR", "caption" => "Spiegelung", "options" => $arrayOptions );	
		 
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
		
		If ((intval($this->GetBuffer("PreviousPin_INT_A")) <> $this->ReadPropertyInteger("Pin_INT_A")) OR (intval($this->GetBuffer("PreviousPin_INT_B")) <> $this->ReadPropertyInteger("Pin_INT_B"))) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_INT_A")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_INT_A"), 0);
			$this->SendDebug("ApplyChanges", "Pin-Wechsel - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_INT_B")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_INT_B"), 0);
		}
		// Device Adresse prüfen
	    	If (($this->ReadPropertyInteger("DeviceAddress") < 0) OR ($this->ReadPropertyInteger("DeviceAddress") > 128)) {
	    		IPS_LogMessage("IPS2GPIO MCP23017","I2C-Device Adresse in einem nicht definierten Bereich!");  
	    	}
	    	// Profil anlegen
		
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("LastInterrupt_A", "Letzte Meldung INT A", "~UnixTimestamp", 10);
		$this->DisableAction("LastInterrupt_A");
		IPS_SetHidden($this->GetIDForIdent("LastInterrupt_A"), true);
		
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterVariableBoolean("GPA".$i, "GPA".$i, "~Switch", ($i * 10 + 20));
			If ($this->ReadPropertyInteger("GPAIODIR".$i) == 0) {
				$this->EnbleAction("GPA".$i);
			}
			else {
				$this->DisableAction("GPA".$i);
			}
			IPS_SetHidden($this->GetIDForIdent("GPA".$i), false);
		}
		
		$this->RegisterVariableInteger("LastInterrupt_B", "Letzte Meldung INT B", "~UnixTimestamp", 100);
		$this->DisableAction("LastInterrupt_B");
		IPS_SetHidden($this->GetIDForIdent("LastInterrupt_B"), true);
		
		for ($i = 0; $i <= 7; $i++) {
		   	$this->RegisterVariableBoolean("GPB".$i, "GPB".$i, "~Switch", ($i * 10 + 110));
			If ($this->ReadPropertyInteger("GPBIODIR".$i) == 0) {
				$this->EnbleAction("GPB".$i);
			}
			else {
				$this->DisableAction("GPB".$i);
			}
			IPS_SetHidden($this->GetIDForIdent("GPB".$i), false);
		}
		
		
		
		
			
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|(.*"Function":"status".*|.*"Pin":'.$this->ReadPropertyInteger("Pin_INT_A").'.*)|(.*"Pin":'.$this->ReadPropertyInteger("Pin_INT_B").'.*))';
			//$this->SendDebug("IPS2GPIO", $Filter, 0);
			$this->SetReceiveDataFilter($Filter);
		
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$ResultI2C = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				
				If ($this->ReadPropertyInteger("Pin_INT_A") >= 0) {
					$ResultPin_INT_A = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_INT_A"), "PreviousPin" => $this->GetBuffer("PreviousPin_INT_A"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 0)));
				
					$this->SetBuffer("PreviousPin_INT_A", $this->ReadPropertyInteger("Pin_INT_A"));
				}
				If ($this->ReadPropertyInteger("Pin_INT_B") >= 0) {
					$ResultPin_INT_B = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_INT_B"), "PreviousPin" => $this->GetBuffer("PreviousPin_INT_B"), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 5, "Resistance" => 0)));
				
					$this->SetBuffer("PreviousPin_INT_B", $this->ReadPropertyInteger("Pin_INT_B"));
				}
				If ($ResultI2C == true) {
					// Erste Messdaten einlesen
					//$this->Setup();
					$this->SetStatus(102);
				}
			}
			else {
				$this->SetStatus(104);
			}	
		}
		else {
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "notify":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin_INT_A")) {
					If (($data->Value == 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						SetValueInteger($this->GetIDForIdent("LastInterrupt_A"), time() );
						$this->GetOutput();
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						$this->GetOutput();
					}
			   	}
				elseif ($data->Pin == $this->ReadPropertyInteger("Pin_INT_B")) {
					If (($data->Value == 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						SetValueInteger($this->GetIDForIdent("LastInterrupt_B"), time() );
						$this->GetOutput();
					}
					elseIf (($data->Value == 1) AND ($this->ReadPropertyBoolean("Open") == true)) {
						$this->SendDebug("Notify", "Wert: ".(int)$data->Value, 0);
						$this->GetOutput();
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
	public function GetOutput()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "GetOutput", 0);
			
		}
	}
	    
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			
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
