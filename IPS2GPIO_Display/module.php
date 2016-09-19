<?
    // Klassendefinition
    class IPS2GPIO_Display extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
            $this->RegisterPropertyInteger("Baud", 3);
            $this->RegisterPropertyString("ConnectionString", "/dev/ttyAMA0");
            $this->RegisterPropertyBoolean("DateTime", true);
            $this->RegisterPropertyInteger("Brightness", 100);
            $this->RegisterPropertyInteger("SleepNoSerial", 60);
            $this->RegisterPropertyInteger("SleepNoTouch", 60);
            $this->RegisterPropertyBoolean("TouchAwake", true);
            $this->RegisterPropertyInteger("CmdRet", 2);
            $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
	        // Diese Zeile nicht löschen
	      	parent::ApplyChanges();
	        //Connect to available splitter or create a new on
	        $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	   
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("Brightness", "Brightness", "~Intensity.100", 10);
           	$this->EnableAction("Brightness");

		$this->RegisterVariableInteger("Baud", "Baud", "", 110);
		$this->DisableAction("Baud");
		IPS_SetHidden($this->GetIDForIdent("Baud"), true);
		
		$this->RegisterVariableInteger("ButtonNumber", "ButtonNumber", "", 110);
		$this->DisableAction("ButtonNumber");
		IPS_SetHidden($this->GetIDForIdent("ButtonNumber"), false);
		
		$this->RegisterVariableInteger("PageNumber", "PageNumber", "", 110);
		$this->EnableAction("PageNumber");
		IPS_SetHidden($this->GetIDForIdent("PageNumber"), false);
		
		$this->RegisterVariableBoolean("ButtonState", "ButtonState", "", 110);
		$this->DisableAction("ButtonState");
		IPS_SetHidden($this->GetIDForIdent("ButtonState"), false);
		
		$this->RegisterVariableString("ButtonSummary", "ButtonSummary", "", 120);
		$this->DisableAction("ButtonSummary");
		IPS_SetHidden($this->GetIDForIdent("ButtonSummary"), false);
		
		$this->RegisterVariableInteger("Coordinate_X", "Coordinate_X", "", 110);
		$this->DisableAction("Coordinate_X");
		IPS_SetHidden($this->GetIDForIdent("Coordinate_X"), false);
		
		$this->RegisterVariableInteger("Coordinate_Y", "Coordinate_Y", "", 110);
		$this->DisableAction("Coordinate_Y");
		IPS_SetHidden($this->GetIDForIdent("Coordinate_Y"), false);
		
		$this->RegisterVariableBoolean("SleepMode", "SleepMode", "", 110);
		$this->EnableAction("SleepMode");
		IPS_SetHidden($this->GetIDForIdent("SleepMode"), false);
		
		$this->RegisterVariableString("StringReturn", "StringReturn", "", 120);
		$this->DisableAction("StringReturn");
		IPS_SetHidden($this->GetIDForIdent("StringReturn"), false);
		
		$this->RegisterVariableInteger("IntegerReturn", "IntegerReturn", "", 120);
		$this->DisableAction("IntegerReturn");
		IPS_SetHidden($this->GetIDForIdent("IntegerReturn"), false);
		
		$this->RegisterVariableString("Response", "Response", "", 120);
		$this->DisableAction("Response");
		IPS_SetHidden($this->GetIDForIdent("Response"), false);
		
		$this->RegisterVariableBoolean("Touchdisplay", "Touchdisplay", "", 300);
		$this->DisableAction("Touchdisplay");
		IPS_SetHidden($this->GetIDForIdent("Touchdisplay"), false);
		
		$this->RegisterVariableString("DisplayModel", "DisplayModel", "", 310);
		$this->DisableAction("DisplayModel");
		IPS_SetHidden($this->GetIDForIdent("DisplayModel"), false);
		
		$this->RegisterVariableInteger("MCU_Code", "MCU_Code", "", 320);
		$this->DisableAction("MCU_Code");
		IPS_SetHidden($this->GetIDForIdent("MCU_Code"), false);
		
		$this->RegisterVariableString("SerialNumber", "SerialNumber", "", 330);
		$this->DisableAction("SerialNumber");
		IPS_SetHidden($this->GetIDForIdent("SerialNumber"), false);
		
		$this->RegisterVariableString("FlashSize", "FlashSize", "", 340);
		$this->DisableAction("FlashSize");
		IPS_SetHidden($this->GetIDForIdent("FlashSize"), false);
		
		$this->RegisterVariableInteger("FirmwareVersion", "FirmwareVersion", "", 350);
		$this->DisableAction("FirmwareVersion");
		IPS_SetHidden($this->GetIDForIdent("FirmwareVersion"), false);
		
		$this->SetBuffer("Update", false);
		$this->SetBuffer("FileName", "");
		$this->SetBuffer("FileSize", 0);
		
		//ReceiveData-Filter setzen 		    
		$Filter = '((.*"Function":"get_serial".*|.*"Pin":14.*)|(.*"Pin":15.*|.*"Function":"set_serial_data".*)))'; 
 		$this->SetReceiveDataFilter($Filter); 
 
        	// den Handle für dieses Gerät ermitteln
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_serial", "Baud" => 9600, "Device" => $this->ReadPropertyString('ConnectionString'), "InstanceID" => $this->InstanceID )));
		
		$this->Setup();
		$this->SetStatus(102);
  
        }

	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
		case "Brightness":
	            $this->SetBrightness($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            break;
	        case "BrightnessDefault":
	            $this->SetBrightnessDefault($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
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
			 case "get_serial":
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_serial", "Baud" => 9600, "Device" => $this->ReadPropertyString('ConnectionString'), "InstanceID" => $this->InstanceID )));
			   	break;
			 case "set_serial_data":
			   	$ByteMessage = utf8_decode($data->Value);
			        //IPS_LogMessage("IPS2GPIO Display", $ByteMessage);	
			        If (substr($ByteMessage, 0, 5) == "comok") {
			        	$Messages = substr($ByteMessage, 6, -3); 
			        	//IPS_LogMessage("IPS2GPIO Display", $Messages);
			        	$Messages = explode(',', $Messages);
			        	SetValueBoolean($this->GetIDForIdent("Touchdisplay"), $Messages[0]);
			        	SetValueString($this->GetIDForIdent("DisplayModel"), $Messages[2]);
			        	SetValueInteger($this->GetIDForIdent("FirmwareVersion"), $Messages[3]);
			        	SetValueInteger($this->GetIDForIdent("MCU_Code"), $Messages[4]);
			        	SetValueString($this->GetIDForIdent("SerialNumber"), $Messages[5]);
			        	SetValueString($this->GetIDForIdent("FlashSize"), $Messages[6]);
			        }
			        else {
				        $ByteResponse = unpack("H*", $ByteMessage);
				        IPS_LogMessage("IPS2GPIO Display","Empfangene Daten: ".$ByteResponse[1]);
				        If (($this->GetBuffer("Update") == true) AND ($ByteResponse[1] == "05")) {
						// Update starten
						If ($this->GetBuffer("FileCounter") == 0) {
							// Datei öffnen und einlesen
							IPS_LogMessage("IPS2GPIO Display","Öffnen der Update-Datei");
							$handle = fopen($this->GetBuffer("FileName"), "r");
							$this->SetBuffer("FileContent", fread($handle, $this->GetBuffer("FileSize")));
							fclose($handle);
							// Datei in Einheiten <4096 Bytes teilen
							$contentarray = str_split($this->GetBuffer("FileContent"), 4096);
							$this->SetBuffer("FileParts", Count($contentarray));
							$Message = utf8_encode($contentarray[0]);
							IPS_LogMessage("IPS2GPIO Display","Senden Datenpaket 0 von "$this->GetBuffer("FileParts"));
							$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Command" => $Message)));
							$this->SetBuffer("FileCounter", 1);
						}
						elseif ($this->GetBuffer("FileCounter") > 0) {
							$contentarray = str_split($this->GetBuffer("FileContent"), 4096);
							$Message = utf8_encode($contentarray[$this->GetBuffer("FileCounter")]);
							IPS_LogMessage("IPS2GPIO Display","Senden Datenpaket ".$this->GetBuffer("FileCounter")." von ".$this->GetBuffer("FileParts"));
							$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Command" => $Message)));
							$this->SetBuffer("FileCounter", $this->GetBuffer("FileCounter") + 1);
							If ($this->GetBuffer("FileCounter") == $this->GetBuffer("FileParts")) {
								$this->SetBuffer("Update", false);
								IPS_LogMessage("IPS2GPIO Display","Update beendet");
							}
						}
					}
				        else {
				        	$Messages = explode('ffffff', $ByteResponse[1]);
					   	for($i=1;$i<Count($Messages);$i++) {
					   		$this->DisplayResponse($Messages[$i]);
					   		SetValueString($this->GetIDForIdent("Response"), $Messages[$i]);
					   	}
				        }
			        }
			   	break;
			 case "status":
			   	If (($data->Pin == 14) OR ($data->Pin == 15)) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
			case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
	 	}
	return;
 	}
	// Beginn der Funktionen
	
	private function DisplayResponse($Message)
	{
		switch(substr($Message, 0, 2)) {
			case "00": // Invalid instruction 
				IPS_LogMessage("IPS2GPIO Display","Fehler: Ungültiger Befehlsaufruf durch den Nutzer");	
			break;
			case "01": // Successful execution of instruction 
				IPS_LogMessage("IPS2GPIO Display","Erfolgreicher Befehlsaufruf durch den Nutzer");
			break;
			case "03": // Page ID invalid  
				IPS_LogMessage("IPS2GPIO Display","Fehler: Aufruf einer ungültigen PageID");
			break;
			case "04": // Picture ID invalid  
				IPS_LogMessage("IPS2GPIO Display","Fehler: Aufruf einer ungültigen PictureID");
			break;
			case "05": // Font ID invalid
				IPS_LogMessage("IPS2GPIO Display","Fehler: Aufruf einer ungültigen FontID");
			break;
			case "11": // Baud rate setting invalid
				IPS_LogMessage("IPS2GPIO Display","Fehler: Setzen einer ungültigen Baud-Rate");
			break;
			case "12": // Curve control ID number or channel number is invalid
				IPS_LogMessage("IPS2GPIO Display","Fehler: Aufruf einer ungültigen CurveControlID oder ChannelNumber");
			break;
			case "1a": // Variable name invalid
				IPS_LogMessage("IPS2GPIO Display","Fehler: Aufruf einer ungültigen Variablen");
			break;
			case "1b": // Variable operation invalid 
				IPS_LogMessage("IPS2GPIO Display","Fehler: Variablen-Operation fehlgeschlagen");
			break;
			case "1c": // Failed to assign   
				IPS_LogMessage("IPS2GPIO Display","Fehler: Ungültige Attribut-Zuordnung");
			break;
			case "1d": // Operate PERFROM failed   
				IPS_LogMessage("IPS2GPIO Display","Fehler: PERFORM-Operation fehlgeschlagen");
			break;
			case "1e": // Parameter quantity invalid   
				IPS_LogMessage("IPS2GPIO Display","Fehler: Ungültige Parameter-Werte");
			break;
			case "1f": // IO operate failed 
				IPS_LogMessage("IPS2GPIO Display","Fehler: I/O-Operation fehlgeschlagen");
			break;
			case "65": // Touch event return data 
				SetValueInteger($this->GetIDForIdent("PageNumber"), intval(hexdec(substr($Message, 2, 2))));
				SetValueInteger($this->GetIDForIdent("ButtonNumber"), intval(hexdec(substr($Message, 4, 2))));
				SetValueBoolean($this->GetIDForIdent("ButtonState"), boolval(hexdec(substr($Message, 6, 2))));
				SetValueString($this->GetIDForIdent("ButtonSummary"), hexdec(substr($Message, 2, 2)).", ".hexdec(substr($Message, 4, 2)).", ".hexdec(substr($Message, 6, 2)));
				
			break;
			case "66": // Current page ID number returns 
				SetValueInteger($this->GetIDForIdent("PageNumber"), intval(hexdec(substr($Message, 2, 2))));
			break;
			case "67": // Touch coordinate data returns 
				$Coordinate_X = intval(hexdec(substr($Message, 2, 2)) + hexdec(substr($Message, 4, 2)));
				SetValueInteger($this->GetIDForIdent("Coordinate_X"), $Coordinate_X);
				$Coordinate_Y = intval(hexdec(substr($Message, 6, 2)) + hexdec(substr($Message, 8, 2)));
				SetValueInteger($this->GetIDForIdent("Coordinate_Y"), $Coordinate_Y);
			break;
			case "68": // Touch Event in sleep mode 
				$Coordinate_X = intval(hexdec(substr($Message, 2, 2)) + hexdec(substr($Message, 4, 2)));
				SetValueInteger($this->GetIDForIdent("Coordinate_X"), $Coordinate_X);
				$Coordinate_Y = intval(hexdec(substr($Message, 6, 2)) + hexdec(substr($Message, 8, 2)));
				SetValueInteger($this->GetIDForIdent("Coordinate_Y"), $Coordinate_Y);
			break;
			case "70": // String variable data returns  
				$Value = $this->hex2str(substr($Message, 2));
				SetValueString($this->GetIDForIdent("StringReturn"), $Value);
			break;
			case "71": // Numeric variable data returns
				$Value = unpack("V*", substr($Message, 2, 8));
				SetValueInteger($this->GetIDForIdent("IntegerReturn"), $Value[1]);
			break;
			case "86": // Device automatically enters into sleep mode 
				SetValueBoolean($this->GetIDForIdent("SleepMode"), true);
			break;
			case "87": // Device automatically wake up 
				SetValueBoolean($this->GetIDForIdent("SleepMode"), false);
			break;
			case "88": // System successful start up 
				IPS_LogMessage("IPS2GPIO Display","Erfolgreicher Systemstart");
			break;
			case "89": // Start SD card upgrade 
				IPS_LogMessage("IPS2GPIO Display","SD-Card-Upgrade gestartet");
			break;
			case "fe": // Data transparent transmit ready 
				IPS_LogMessage("IPS2GPIO Display","Transparenter Datentransport abgeschlossen");
			break;
			case "co": // Data transparent transmit ready 
				IPS_LogMessage("IPS2GPIO Display", $Message);
			break;
		}
    	return;
	}
	
	private function hex2str($hex)
	{
		$str = '';
    		for($i=0;$i<strlen($hex);$i+=2) $str .= chr(hexdec(substr($hex,$i,2)));
    	return $str;
	}
	
	public function Send($Message)
	{
		$Message = utf8_encode($Message."\xFF\xFF\xFF");
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Command" => $Message)));

	return;
	}
	
	private function Setup()
	{
		// Uhrzeit und Datum aktualisieren
		If ($this->ReadPropertyBoolean("DateTime") == true) {
			$this->SetDateTime();
		}
		// Default-Wert der Helligkeit setzen
		$this->SetBrightnessDefault($this->ReadPropertyInteger("Brightness"));
		// Zeit für Sleep Modus ohne Serielle Kommunikation
		$this->SetSleepNoSerial($this->ReadPropertyInteger("SleepNoSerial"));
		// Zeit für Sleep Modus ohne Touch
		$this->SetSleepNoTouch($this->ReadPropertyInteger("SleepNoTouch"));
		// Touch soll aus Sleep Modus wecken
		$this->SetTouchAwake($this->ReadPropertyBoolean("TouchAwake"));
		// Rückgabeverhalten auf kommandos
		$this->SetCommandReturn($this->ReadPropertyInteger("CmdRet"));
		// Senden der Koordniaten bei Touch
		$this->SetSendTouchCoordinate(1);
		// Display-Daten anfordern
		$this->Send("connect");
	return;
	}
	public function SetBrightness($Value)
	{
		$Value = min(100, max(0, $Value));
		$this->Send("dim=".$Value); 
	return;
	}
	
	private function SetBrightnessDefault($Value)
	{
		$Value = min(100, max(0, $Value));
		$this->Send("dims=".$Value);
	return;
	}
	
	private function SetDateTime()
	{
		date_default_timezone_set("Europe/Berlin");
		$timestamp = time();
		$this->Send("rtc0=".date("Y",$timestamp));
		$this->Send("rtc1=".date("m",$timestamp));
		$this->Send("rtc2=".date("d",$timestamp));
		$this->Send("rtc3=".date("H",$timestamp));
		$this->Send("rtc4=".date("i",$timestamp));
		$this->Send("rtc5=".date("s",$timestamp));
	return;	
	}
	
	private function SetSleepNoSerial($Value)
	{
		$Value = min(65535, max(0, $Value));
		$this->Send("ussp=".$Value);
	return;
	}
	
	private function SetSleepNoTouch($Value)
	{
		$Value = min(65535, max(0, $Value));
		$this->Send("thsp=".$Value);
	return;
	}
	
	private function SetTouchAwake($Value)
	{
		$Value = min(1, max(0, $Value));
		$this->Send("thup=".$Value);
	return;
	}
	
	private function SetCommandReturn($Value)
	{
		$Value = min(3, max(0, $Value));
		$this->Send("bkcmd=".$Value);
	return;
	}
	
	private function SetSendTouchCoordinate($Value)
	{
		$Value = min(1, max(0, $Value));
		$this->Send("sendxy=".$Value);
	return;
	}
	
	public function SetSleep($Value)
	{
		$Value = min(1, max(0, $Value));
		SetValueBoolean($this->GetIDForIdent("SleepMode"), $Value);
		$this->Send("sleep=".$Value);
	return;
	}
	
	public function Update($Filename)
	{
		if (file_exists($Filename)) {
		    $this->SetBuffer("FileName", $Filename);
		    IPS_LogMessage("IPS2GPIO Display","Der angegebene Datei ".$Filename." wurde gefunden.");
		    $this->SetBuffer("FileSize", filesize($Filename));
		    IPS_LogMessage("IPS2GPIO Display","Der angegebene Datei ".$Filename." hat eine Größe von ".$this->GetBuffer("FileSize")." Bytes");
		    // der Update-Prozess kann beginnen
		    $this->SetBuffer("Update", true);
		    $this->SetBuffer("FileCounter", 0);
		    $this->SetBuffer("FileParts", 0);
		    $this->SetBuffer("FileContent", "");
		    $this->Send("0000");
		    $this->Send("whmi-wri ".$this->GetBuffer("FileSize").",9600,0");
		} else {
		    IPS_LogMessage("IPS2GPIO Display","Der angegebene Datei ".$Filename." wurde nicht gefunden!");
		}		
	return;
	}
}
?>
