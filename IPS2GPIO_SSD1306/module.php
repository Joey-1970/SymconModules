<?
    // Klassendefinition
    class IPS2GPIO_SSD1306 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// https://github.com/adafruit/Adafruit_Python_SSD1306/blob/master/Adafruit_SSD1306/SSD1306.py
		
		// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 60);
		$this->RegisterPropertyInteger("DeviceBus", 1);
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
		$arrayOptions[] = array("label" => "60 dez. / 0x3Ch", "value" => 60);
		$arrayOptions[] = array("label" => "61 dez. / 0x3Dh", "value" => 61);
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
		
		$arrayActions = array();
		$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}  
	    
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
   
		
	    	// Profil anlegen
	    	
		//Status-Variablen anlegen
		
		
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// Summary setzen
			$DevicePorts = array();
			$DevicePorts = unserialize($this->Get_I2C_Ports());
			$this->SetSummary("DA: 0x".dechex($this->ReadPropertyInteger("DeviceAddress"))." DB: ".$DevicePorts[$this->ReadPropertyInteger("DeviceBus")]);
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
		
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
				If ($Result == true) {
					
					// Setup
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
	
	    

	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			$InitArray = array();
			$InitArray = array(0xAE, 0xD5, 0x80, 0x3F, 0xD3, 0x00, 0x40 | 0x00, 0x8D, 0x10, 0x20, 0x00, 0xA0 | 0x01, 0xC8, 0xDA, 0x12, 0x81, 0x9F,
				      0xD9, 0x22, 0xDB, 0x40, 0xA4, 0xA6);
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_SSD1306_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("00"), 
										  "Parameter" => serialize($InitArray) )));
			If (!$Result) {
				$this->SendDebug("Setup", "Fehler beim Setzen der Daten", 0);
			}
			/*
			self.command(SSD1306_DISPLAYOFF)                    # 0xAE
			self.command(SSD1306_SETDISPLAYCLOCKDIV)            # 0xD5
			self.command(0x80)                                  # the suggested ratio 0x80
			self.command(SSD1306_SETMULTIPLEX)                  # 0xA8
			self.command(0x3F)
			self.command(SSD1306_SETDISPLAYOFFSET)              # 0xD3
			self.command(0x0)                                   # no offset
			self.command(SSD1306_SETSTARTLINE | 0x0)            # line #0
			self.command(SSD1306_CHARGEPUMP)                    # 0x8D
			if self._vccstate == SSD1306_EXTERNALVCC:
			    self.command(0x10)
			else:
			    self.command(0x14)
			self.command(SSD1306_MEMORYMODE)                    # 0x20
			self.command(0x00)                                  # 0x0 act like ks0108
			self.command(SSD1306_SEGREMAP | 0x1)
			self.command(SSD1306_COMSCANDEC)
			self.command(SSD1306_SETCOMPINS)                    # 0xDA
			self.command(0x12)
			self.command(SSD1306_SETCONTRAST)                   # 0x81
			if self._vccstate == SSD1306_EXTERNALVCC:
			    self.command(0x9F)
			else:
			    self.command(0xCF)
			self.command(SSD1306_SETPRECHARGE)                  # 0xd9
			if self._vccstate == SSD1306_EXTERNALVCC:
			    self.command(0x22)
			else:
			    self.command(0xF1)
			self.command(SSD1306_SETVCOMDETECT)                 # 0xDB
			self.command(0x40)
			self.command(SSD1306_DISPLAYALLON_RESUME)           # 0xA4
			self.command(SSD1306_NORMALDISPLAY)                 # 0xA6

			*/
			
		}
	}

	public function SetDisplayOn()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetDisplayOn", "Ausfuehrung", 0);
			$ConfigArray = array();
			$ConfigArray[0] = 0xAF;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_SSD1306_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("00"), 
										  "Parameter" => serialize($ConfigArray) )));
			If (!$Result) {
				$this->SendDebug("SetDisplayOn", "Fehler beim Setzen der Daten", 0);
			}
				
		}
	}

	public function SetDisplayOff()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetDisplayOff", "Ausfuehrung", 0);
			$ConfigArray = array();
			$ConfigArray[0] = 0xAE;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_SSD1306_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("00"), 
										  "Parameter" => serialize($ConfigArray) )));
			If (!$Result) {
				$this->SendDebug("SetDisplayOff", "Fehler beim Setzen der Daten", 0);
			}
		}
	}

	public function SetNormalDisplay()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetNormalDisplay", "Ausfuehrung", 0);
			$ConfigArray = array();
			$ConfigArray[0] = 0xA6;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_SSD1306_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("00"), 
										  "Parameter" => serialize($ConfigArray) )));
			If (!$Result) {
				$this->SendDebug("SetNormalDisplay", "Fehler beim Setzen der Daten", 0);
			}
		}
	}

	public function SetInverseDisplay()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetInverseDisplay", "Ausfuehrung", 0);
			$ConfigArray = array();
			$ConfigArray[0] = 0xA7;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_SSD1306_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("00"), 
										  "Parameter" => serialize($ConfigArray) )));
			If (!$Result) {
				$this->SendDebug("SetInverseDisplay", "Fehler beim Setzen der Daten", 0);
			}
		}
	}

	public function SetContrastControl(Int $Contrast)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetContrastControl", "Ausfuehrung", 0);
			$Contrast = min(255, max(0, $Contrast));
			$ConfigArray = array();
			$ConfigArray[0] = 0x81;
			$ConfigArray[1] = $Contrast;
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_SSD1306_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => hexdec("00"), 
										  "Parameter" => serialize($ConfigArray) )));
			If (!$Result) {
				$this->SendDebug("SetContrastControl", "Fehler beim Setzen der Daten", 0);
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
