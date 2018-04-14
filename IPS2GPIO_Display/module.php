<?
    // Klassendefinition
    class IPS2GPIO_Display extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyInteger("Baud", 3);
		$this->RegisterPropertyInteger("Pin_RxD", -1);
		$this->SetBuffer("PreviousPin_RxD", -1);
		$this->RegisterPropertyInteger("Pin_TxD", -1);
		$this->SetBuffer("PreviousPin_TxD", -1);
            	$this->RegisterPropertyBoolean("DateTime", true);
            	$this->RegisterPropertyInteger("Brightness", 100);
            	$this->RegisterPropertyInteger("SleepNoSerial", 60);
            	$this->RegisterPropertyInteger("SleepNoTouch", 60);
            	$this->RegisterPropertyBoolean("TouchAwake", true);
            	$this->RegisterPropertyBoolean("SendTouchCoordinate", true);
            	$this->RegisterPropertyInteger("CmdRet", 2);
            	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("Brightness", "Brightness", "~Intensity.100", 10);
           	$this->EnableAction("Brightness");

		$this->RegisterVariableInteger("Baud", "Baud", "", 110);
		$this->DisableAction("Baud");
		
		$this->RegisterVariableInteger("ButtonNumber", "ButtonNumber", "", 110);
		$this->DisableAction("ButtonNumber");
		
		$this->RegisterVariableInteger("PageNumber", "PageNumber", "", 110);
		$this->DisableAction("PageNumber");
		
		$this->RegisterVariableBoolean("ButtonState", "ButtonState", "", 110);
		$this->DisableAction("ButtonState");
		
		$this->RegisterVariableString("ButtonSummary", "ButtonSummary", "", 120);
		$this->DisableAction("ButtonSummary");
		
		$this->RegisterVariableInteger("Coordinate_X", "Coordinate_X", "", 110);
		$this->DisableAction("Coordinate_X");
		
		$this->RegisterVariableInteger("Coordinate_Y", "Coordinate_Y", "", 110);
		$this->DisableAction("Coordinate_Y");
		
		$this->RegisterVariableBoolean("SleepMode", "SleepMode", "", 110);
		$this->DisableAction("SleepMode");
		
		$this->RegisterVariableString("StringReturn", "StringReturn", "", 120);
		$this->DisableAction("StringReturn");
		
		$this->RegisterVariableInteger("IntegerReturn", "IntegerReturn", "", 120);
		$this->DisableAction("IntegerReturn");
		
		$this->RegisterVariableString("Response", "Response", "", 120);
		$this->DisableAction("Response");
		
		$this->RegisterVariableBoolean("Touchdisplay", "Touchdisplay", "", 300);
		$this->DisableAction("Touchdisplay");
		
		$this->RegisterVariableString("DisplayModel", "DisplayModel", "", 310);
		$this->DisableAction("DisplayModel");
		
		$this->RegisterVariableInteger("MCU_Code", "MCU_Code", "", 320);
		$this->DisableAction("MCU_Code");
		
		$this->RegisterVariableString("SerialNumber", "SerialNumber", "", 330);
		$this->DisableAction("SerialNumber");
		
		$this->RegisterVariableString("FlashSize", "FlashSize", "", 340);
		$this->DisableAction("FlashSize");
		
		$this->RegisterVariableInteger("FirmwareVersion", "FirmwareVersion", "", 350);
		$this->DisableAction("FirmwareVersion");
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

		
		
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "2400", "value" => 1);
		$arrayOptions[] = array("label" => "4800", "value" => 2);
		$arrayOptions[] = array("label" => "9600", "value" => 3);
		$arrayOptions[] = array("label" => "19200", "value" => 4);
		$arrayOptions[] = array("label" => "38400", "value" => 5);
		$arrayOptions[] = array("label" => "57600", "value" => 6);
		$arrayOptions[] = array("label" => "115200", "value" => 7);
		$arrayElements[] = array("type" => "Select", "name" => "Baud", "caption" => "Baud", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "CheckBox", "name" => "DateTime", "caption" => "Datum/Uhrzeit aktualisieren");
		$arrayElements[] = array("type" => "Label", "label" => "Setzen der Default Display Helligkeit (0-100)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Brightness", "caption" => "Display-Helligkeit");
		$arrayElements[] = array("type" => "Label", "label" => "Display Sleep Modus ohne Serielle Kommunikation (0->aus, 3-65535 Sekunden)");
        	$arrayElements[] = array("type" => "NumberSpinner", "name" => "SleepNoSerial", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "Label", "label" => "Display Sleep Modus ohne Touch (0->aus, 3-65535 Sekunden)");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "SleepNoTouch", "caption" => "Sekunden");
		$arrayElements[] = array("type" => "CheckBox", "name" => "TouchAwake", "caption" => "Touch beendet Sleep Modus");
		$arrayElements[] = array("type" => "CheckBox", "name" => "SendTouchCoordinate", "caption" => "Sende XY");
  		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Keine Rückgabe", "value" => 0);
		$arrayOptions[] = array("label" => "Nur erfolgreiche Daten", "value" => 1);
		$arrayOptions[] = array("label" => "Nur fehlerhafte Daten", "value" => 2);
		$arrayOptions[] = array("label" => "Alle Daten", "value" => 3);
		$arrayElements[] = array("type" => "Select", "name" => "CmdRet", "caption" => "Return der Kommandos", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Steuerung eines Nextion Enhanced Displays");
		
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
		
		$this->SetBuffer("Update", false);
		$this->SetBuffer("FileName", "");
		$this->SetBuffer("FileSize", 0);
		
		//ReceiveData-Filter setzen 
		$Filter = '((.*"Function":"get_serial".*|.*"Pin":".$this->ReadPropertyInteger("Pin_RxD").".*)|(.*"Pin":".$this->ReadPropertyInteger("Pin_TxD").".*|.*"Function":"set_serial_data".*))'; 
		//$Filter = '((.*"Function":"get_serial".*|.*"Pin":14.*)|(.*"Pin":15.*|.*"Function":"set_serial_data".*))'; 
 		$this->SetReceiveDataFilter($Filter); 
 
        	If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// den Handle für dieses Gerät ermitteln
			//$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_serial", "Baud" => 9600, "Device" => $this->ReadPropertyString('ConnectionString'), "InstanceID" => $this->InstanceID )));
			If (($this->ReadPropertyInteger("Pin_RxD") >= 0) AND ($this->ReadPropertyInteger("Pin_TxD") >= 0) AND ($this->ReadPropertyBoolean("Open") == true) ) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "open_bb_serial_display", "Baud" => 9600, "Pin_RxD" => $this->ReadPropertyInteger("Pin_RxD"), "PreviousPin_RxD" => $this->GetBuffer("PreviousPin_RxD"), "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "PreviousPin_TxD" => $this->GetBuffer("PreviousPin_TxD"), "InstanceID" => $this->InstanceID )));
				$this->SetBuffer("PreviousPin_RxD", $this->ReadPropertyInteger("Pin_RxD"));
				$this->SetBuffer("PreviousPin_TxD", $this->ReadPropertyInteger("Pin_TxD"));
				$this->Setup();
				$this->SetStatus(102);
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
		case "Brightness":
	            $this->SetBrightness($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            break;
	        /*
		case "BrightnessDefault":
	            $this->SetBrightnessDefault($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            break;
		*/
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
			   	$this->ApplyChanges();
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
					$this->SendDebug("Empfangene Daten", $ByteResponse[1], 0);
				        //IPS_LogMessage("IPS2GPIO Display","Empfangene Daten: ".$ByteResponse[1]);
				        If (($this->GetBuffer("Update") == true) AND ($ByteResponse[1] == "05")) {
						// Update starten
						// Datei öffnen und einlesen
						//IPS_LogMessage("IPS2GPIO Display","Öffnen der Update-Datei");
						$handle = fopen($this->GetBuffer("FileName"), "r");
						$FileContent = fread($handle, $this->GetBuffer("FileSize"));
						fclose($handle);
						// Datei in Einheiten <4096 Bytes teilen
						$contentarray = str_split($FileContent, 4096);
						$Message = utf8_encode($contentarray[$this->GetBuffer("FileCounter")]);
						$this->SendDebug("Update", "Senden Datenpaket ".$this->GetBuffer("FileCounter")." von ".$this->GetBuffer("FileParts"), 0);
						//IPS_LogMessage("IPS2GPIO Display","Senden Datenpaket ".$this->GetBuffer("FileCounter")." von ".$this->GetBuffer("FileParts"));
						
						If ($this->GetBuffer("FileCounter") == 0) {
							// Sichern der Anzahl der Datei-Teile
							$this->SetBuffer("FileParts", Count($contentarray));
						}
						// Senden der Daten
						$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Command" => $Message)));
						$this->SetBuffer("FileCounter", $this->GetBuffer("FileCounter") + 1);

						// Prüfen, ob es sich um den letzten Datei-Teil handelt
						If ($this->GetBuffer("FileCounter") == $this->GetBuffer("FileParts")) {
							$this->SetBuffer("Update", false);
							$this->SendDebug("Update", "beendet", 0);
							IPS_LogMessage("IPS2GPIO Display","Update beendet");
						}
					}
				        else {
				        	$Messages = explode('ffffff', $ByteResponse[1]);
					   	for($i=1;$i<Count($Messages);$i++) {
					   		IPS_LogMessage("IPS2GPIO Display", $Messages[$i]);
							$this->SendDebug("Message", $Messages[$i], 0);
							$this->DisplayResponse($Messages[$i]);
					   		SetValueString($this->GetIDForIdent("Response"), $Messages[$i]);
					   	}
				        }
			        }
			   	break;
			 case "status":
			   	If (($data->Pin == $this->ReadPropertyInteger("Pin_RxD")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_TxD"))) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
	 	}
 	}
	// Beginn der Funktionen
	
	private function DisplayResponse(String $Message)
	{
		switch(substr($Message, 0, 2)) {
			case "00": // Invalid instruction 
				IPS_LogMessage("IPS2GPIO Display","Fehler: Ungültiger Befehlsaufruf durch den Nutzer");	
			break;
			case "01": // Successful execution of instruction 
				IPS_LogMessage("IPS2GPIO Display","Erfolgreicher Befehlsaufruf durch den Nutzer");
			break;
			case "02": // Component ID invalid 
				IPS_LogMessage("IPS2GPIO Display","Fehler: Aufruf einer ungültigen KomponentenID");
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
	}
	
	private function hex2str(String $hex)
	{
		$str = '';
    		for($i=0;$i<strlen($hex);$i+=2) $str .= chr(hexdec(substr($hex,$i,2)));
    	return $str;
	}
	
	public function Send(String $Message)
	{
		$Message = utf8_encode($Message."\xFF\xFF\xFF");
		//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Command" => $Message)));
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytes_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => $Message)));
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
		$this->SetSendTouchCoordinate($this->ReadPropertyBoolean("SendTouchCoordinate"));
		// Display-Daten anfordern
		$this->Send("connect");
	}
	
	public function SetBrightness(Int $Value)
	{
		$Value = min(100, max(0, $Value));
		$this->Send("dim=".$Value); 
	}
	
	private function SetBrightnessDefault(Int $Value)
	{
		$Value = min(100, max(0, $Value));
		$this->Send("dims=".$Value);
	}
	
	private function SetDateTime()
	{		date_default_timezone_set("Europe/Berlin");
		$timestamp = time();
		$this->Send("rtc0=".date("Y",$timestamp));
		$this->Send("rtc1=".date("m",$timestamp));
		$this->Send("rtc2=".date("d",$timestamp));
		$this->Send("rtc3=".date("H",$timestamp));
		$this->Send("rtc4=".date("i",$timestamp));
		$this->Send("rtc5=".date("s",$timestamp));
	}
	
	private function SetSleepNoSerial(Int $Value)
	{
		$Value = min(65535, max(0, $Value));
		$this->Send("ussp=".$Value);
	}
	
	private function SetSleepNoTouch(Int $Value)
	{
		$Value = min(65535, max(0, $Value));
		$this->Send("thsp=".$Value);
	}
	
	private function SetTouchAwake(Bool $Value)
	{
		$Value = min(1, max(0, $Value));
		$this->Send("thup=".$Value);
	}
	
	private function SetCommandReturn(Int $Value)
	{
		$Value = min(3, max(0, $Value));
		$this->Send("bkcmd=".$Value);
	}
	
	private function SetSendTouchCoordinate(Bool $Value)
	{
		$Value = min(1, max(0, $Value));
		$this->Send("sendxy=".$Value);
	}
	
	public function SetSleep(Bool $Value)
	{
		$Value = min(1, max(0, $Value));
		SetValueBoolean($this->GetIDForIdent("SleepMode"), $Value);
		$this->Send("sleep=".$Value);
	}
	
	public function Reset()
	{
		$this->Send("rest");
	}
	
	public function Update(String $Filename)
	{
		if (file_exists($Filename)) {
		    //$this->Send("connect");
		    $this->SetBuffer("FileName", $Filename);
		    //IPS_LogMessage("IPS2GPIO Display","Die angegebene Datei ".$Filename." wurde gefunden.");
		    $this->SetBuffer("FileSize", filesize($Filename));
		    //IPS_LogMessage("IPS2GPIO Display","Der angegebene Datei ".$Filename." hat eine Größe von ".$this->GetBuffer("FileSize")." Bytes");
		    // der Update-Prozess kann beginnen
		    $this->SetBuffer("Update", true);
		    $this->SetBuffer("FileCounter", 0);
		    $this->SetBuffer("FileParts", 0);
		    $this->Send("0000");
		    $this->Send("whmi-wri ".$this->GetBuffer("FileSize").",9600,0");
		} else {
			 $this->SendDebug("Update", "Fehler: Die angegebene Datei ".$Filename." wurde nicht gefunden!", 0);
		    	IPS_LogMessage("IPS2GPIO Display","Fehler: Die angegebene Datei ".$Filename." wurde nicht gefunden!");
		}		
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
