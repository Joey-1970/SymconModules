<?
    // Klassendefinition
    class IPS2GPIO_DS3231 extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Messzyklus", 0);
		$this->SetTimerInterval("NTPUpdate", 0);
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
		// Diese Zeile nicht löschen.
            	parent::Create();
 	    	$this->RegisterPropertyBoolean("Open", false);
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
 	    	$this->RegisterPropertyInteger("DeviceAddress", 104);
		$this->RegisterPropertyInteger("DeviceBus", 1);
		$this->RegisterPropertyInteger("Messzyklus", 60);
		$this->RegisterTimer("Messzyklus", 0, 'I2GDS3231_GetRTC($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("NTPTimeout", 10);
		$this->RegisterPropertyString("NTPHost", "pool.ntp.org");
		$this->RegisterPropertyInteger("NTPUpdate", 60);
		$this->RegisterTimer("NTPUpdate", 0, 'I2GDS3231_SetRTCFromNTP($_IPS["TARGET"]);');
		
		// Profile erstellen
		$this->RegisterProfileInteger("IPS2GPIO.NTPStatus", "Network", "", "", 0, 3, 1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.NTPStatus", 0, "unbekannt", "Network", -1);
		IPS_SetVariableProfileAssociation("IPS2GPIO.NTPStatus", 1, "offline", "Network", 0xFF0000);
		IPS_SetVariableProfileAssociation("IPS2GPIO.NTPStatus", 2, "online", "Network", 0x00FF00);
		
		// Statusvariablen anlegen
		$this->RegisterVariableInteger("RTC_Timestamp", "RTC Zeitstempel", "~UnixTimestamp", 10);
		$this->DisableAction("RTC_Timestamp");
		
		$this->RegisterVariableFloat("RTC_Temperature", "RTC Temperatur", "~Temperature", 20);
		$this->DisableAction("RTC_Temperature");
		
		$this->RegisterVariableInteger("NTP_Timestamp", "NTP Zeitstempel", "~UnixTimestamp", 30);
		$this->DisableAction("NTP_Timestamp");
							  
		$this->RegisterVariableInteger("NTP_Status", "NTP Status", "IPS2GPIO.NTPStatus", 40);
		$this->DisableAction("NTP_Status");
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
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "104 dez. / 0x68h", "value" => 104);
		
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
		
		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus) (optional)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");  
		$arrayElements[] = array("type" => "Label", "label" => "NTP-Abfrageintervall in Sekunden (0 -> aus) (optional)");
		$arrayElements[] = array("type" => "IntervalBox", "name" => "NTPUpdate", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "NTPHost", "caption" => "NTP-Host");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "NTPTimeout", "caption" => "NTP-Timeout");
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

			
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {					
						  
			SetValueInteger($this->GetIDForIdent("NTP_Status"), 0);
			
			//ReceiveData-Filter setzen
			$this->SetBuffer("DeviceIdent", (($this->ReadPropertyInteger("DeviceBus") << 7) + $this->ReadPropertyInteger("DeviceAddress")));
			$Filter = '((.*"Function":"get_used_i2c".*|.*"DeviceIdent":'.$this->GetBuffer("DeviceIdent").'.*)|.*"Function":"status".*)';
			$this->SetReceiveDataFilter($Filter);
			
			If ($this->ReadPropertyBoolean("Open") == true) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_used_i2c", "DeviceAddress" => $this->ReadPropertyInteger("DeviceAddress"), "DeviceBus" => $this->ReadPropertyInteger("DeviceBus"), "InstanceID" => $this->InstanceID)));
								
				If ($Result == true) {
					$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
					$this->SetTimerInterval("NTPUpdate", ($this->ReadPropertyInteger("NTPUpdate") * 1000));
					$this->Setup();
				}
				else {
					$this->SetTimerInterval("Messzyklus", 0);
					$this->SetTimerInterval("NTPUpdate", 0);
				}
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				$this->SetTimerInterval("NTPUpdate", 0);
				$this->SetStatus(104);
			}	
		}
		else {
			$this->SetTimerInterval("Messzyklus", 0);
			$this->SetTimerInterval("NTPUpdate", 0);
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
 	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			$DateArray = array();
			// Config-Byte bestimmen
			
			$DataArray[0] = 60;
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_DS3231_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 0x0E, 
											  "Parameter" => serialize($DataArray) )));
			If (!$Result) {
				$this->SendDebug("SetRTC", "Setzen des Config-Byte fehlerhaft!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
			}	
		}
	}
	    
	public function GetRTC()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetRTC", "Ausfuehrung", 0);
			
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_DS3231_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 0x00, "Count" => 7)));
				If ($Result < 0) {
					$this->SendDebug("GetRTC", "Einlesen der Zeit fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					//$this->SendDebug("GetRTC", "Ergebnis: ".$Result, 0);
					If (is_array(unserialize($Result))) {
						$this->SetStatus(102);
						$DataArray = array();
						// $DataArray[1] - Sekunden
						// $DataArray[2] - Minuten
						// $DataArray[3] - Stunden
						// $DataArray[4] - Tag
						// $DataArray[5] - Monat und Jahrhundert
						// $DataArray[6] - Jahr
						$DataArray = unserialize($Result);
						// Ergebnis sichern
						$Sec = str_pad(dechex($DataArray[1] & 127), 2 ,'0', STR_PAD_LEFT);
						$Min = str_pad(dechex($DataArray[2] & 127), 2 ,'0', STR_PAD_LEFT);
						// 24 Stunden Anzeige
						$Hour = str_pad(dechex($DataArray[3] & 63), 2 ,'0', STR_PAD_LEFT);
						
						$Date = str_pad(dechex($DataArray[5] & 63), 2 ,'0', STR_PAD_LEFT);;
						$Month = str_pad(dechex($DataArray[6] & 31), 2 ,'0', STR_PAD_LEFT);
						$Century = ($DataArray[6] >> 7) & 1;
						$Year = str_pad(dechex($DataArray[7] & 255), 2 ,'0', STR_PAD_LEFT);
						If ($Century == 1) {
							$Year = $Year + 2000;
						}
						else {
							$Year = $Year + 1900;	
						}
						$Timestamp = mktime(intval($Hour), intval($Min), intval($Sec), intval($Month), intval($Date), intval($Year));
						$this->SendDebug("GetRTC", "Ergebnis: ".date("d.m.Y H:i:s", $Timestamp), 0);
						SetValueInteger($this->GetIDForIdent("RTC_Timestamp"), $Timestamp);
						
						
						break;
					}
				}
			$tries--;
			} while ($tries);  
		}
	}
	
	public function GetTemperature()  
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetTemperature", "Ausfuehrung", 0);
			
			$tries = 3;
			do {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_DS3231_read", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "Register" => 17, "Count" => 2)));
				If ($Result < 0) {
					$this->SendDebug("GetTemperature", "Einlesen der Temperatur fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					//$this->SendDebug("GetTemperature", "Ergebnis: ".$Result, 0);
					If (is_array(unserialize($Result))) {
						$this->SetStatus(102);
						$DataArray = array();
						$DataArray = unserialize($Result);
						
						$MSBofTemp = $DataArray[1];
						$LSBofTemp = $DataArray[2];
						$MSBofTemp = ($MSBofTemp & 127);
						$LSBofTemp = ($LSBofTemp >> 6) * 0.25;
						$Temp = $MSBofTemp + $LSBofTemp;
						SetValueFloat($this->GetIDForIdent("RTC_Temperature"), $Temp);
						$this->SendDebug("GetTemperature", "Ergebnis: ".$Temp, 0);
						break;
					}
				}
			$tries--;
			} while ($tries);  
		}	
	}
	    
	public function SetRTC()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetRTC", "Ausfuehrung", 0);
			$DateArray = array();
			$DataArray = array($this->decbcd(date("s")), $this->decbcd(date("i")), $this->decbcd(date("H")), ($this->decbcd(date("w")) + 1), $this->decbcd(date("d")), ($this->decbcd(date("m")) | 128), $this->decbcd(date("y")) );
			$this->SendDebug("SetRTC", "Datensatz: ".serialize($DataArray), 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_DS3231_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 0x00, 
											  "Parameter" => serialize($DataArray) )));
			If (!$Result) {
				$this->SendDebug("SetRTC", "Setzen der Zeit fehlerhaft!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				$this->GetRTC();
			}	
		}
	} 
	
	public function SetRTCFromIPS()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetRTCFromIPS", "Ausfuehrung", 0);
			$DateArray = array();
			$DataArray = array($this->decbcd(date("s")), $this->decbcd(date("i")), $this->decbcd(date("H")), ($this->decbcd(date("w")) + 1), $this->decbcd(date("d")), ($this->decbcd(date("m")) | 128), $this->decbcd(date("y")) );
			$this->SendDebug("SetRTCFromIPS", "Datensatz: ".serialize($DataArray), 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_DS3231_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 0x00, 
											  "Parameter" => serialize($DataArray) )));
			If (!$Result) {
				$this->SendDebug("SetRTCFromIPS", "Setzen der Zeit fehlerhaft!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				$this->GetRTC();
			}	
		}
	}     
	    
	public function SetRTCFromNTP()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetRTCFromNTP", "Ausfuehrung", 0);
			$timeout = $this->ReadPropertyInteger("NTPTimeout");
			$timeout = min(15, max(1, $timeout));
			$host = $this->ReadPropertyString("NTPHost");
			
			// http://doc-tcpip.org/Ntp/basics.html
			// https://gist.github.com/bohwaz/6d01bf00fdb4721a601c4b9fc1007d81
			$socket = stream_socket_client('udp://' . $host . ':123', $errno, $errstr, (int)$timeout);
			if (!$socket) {
			    	$this->SendDebug("SetRTCFromNTP", "Fehler in der Verbindung: ".$errstr, 0);
				SetValueInteger($this->GetIDForIdent("NTP_Status"), 1);
			} else {
				$msg = "\010" . str_repeat("\0", 47);
				fwrite($socket, $msg);
				$response = fread($socket, 48);
				fclose($socket);
				// unpack to unsigned long
				$data = unpack('N12', $response);
				// 9 =  Receive Timestamp (rec): Time at the server when the request arrived
				// from the client, in NTP timestamp format.
				$timestamp = sprintf('%u', $data[9]);
				// NTP = number of seconds since January 1st, 1900
				// Unix time = seconds since January 1st, 1970
				// remove 70 years in seconds to get unix timestamp from NTP time
				$timestamp -= 2208988800;
				$this->SendDebug("SetRTCFromNTP", "Ergebnis: ".date("d.m.Y H:i:s", $timestamp), 0);
				SetValueInteger($this->GetIDForIdent("NTP_Timestamp"), $timestamp);
				SetValueInteger($this->GetIDForIdent("NTP_Status"), 2);

				$DateArray = array();
				$DataArray = array($this->decbcd(date("s"), $timestamp), $this->decbcd(date("i"), $timestamp), $this->decbcd(date("H"), $timestamp), ($this->decbcd(date("w"), $timestamp) + 1), $this->decbcd(date("d"), $timestamp), ($this->decbcd(date("m"), $timestamp) | 128), $this->decbcd(date("y"), $timestamp) );
				//$this->SendDebug("SetRTCFromNTP", "Datensatz: ".serialize($DataArray), 0);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "i2c_DS3231_write", "DeviceIdent" => $this->GetBuffer("DeviceIdent"), "InstanceID" => $this->InstanceID, "Register" => 0x00, 
												  "Parameter" => serialize($DataArray) )));
				If (!$Result) {
					$this->SendDebug("SetRTCFromNTP", "Setzen der Zeit fehlerhaft!", 0);
					$this->SetStatus(202);
				}
				else {
					$this->SetStatus(102);
					$this->GetRTC();
				}
			}
		}
	}   
	    
	private function decbcd(int $DecValue)
	{
		$BCD = intval(hexdec($DecValue));
	return $BCD;
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
