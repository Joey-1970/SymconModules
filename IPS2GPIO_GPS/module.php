<?
    // Klassendefinition
    class IPS2GPIO_GPS extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin_RxD", -1);
		$this->SetBuffer("PreviousPin_RxD", -1);
		$this->RegisterPropertyInteger("Pin_TxD", -1);
		$this->SetBuffer("PreviousPin_TxD", -1);
            	
            	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		
		// Profil anlegen
		$this->RegisterProfileFloat("IPS2GPIO.m", "Distance", "", " m", -10000, +10000, 0.1, 1);
	   
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("Timestamp", "Zeitstempel", "~UnixTimestampTime", 10);
		$this->DisableAction("Timestamp");
		IPS_SetHidden($this->GetIDForIdent("Timestamp"), false);
		
		$this->RegisterVariableFloat("Latitude", "Breitengrad", "", 20);
		$this->DisableAction("Latitude");
		IPS_SetHidden($this->GetIDForIdent("Latitude"), false);
		
		$this->RegisterVariableString("LatitudeLocal", "Breitengrad Lokalität", "", 30);
		$this->DisableAction("LatitudeLocal");
		IPS_SetHidden($this->GetIDForIdent("LatitudeLocal"), false);
		
		$this->RegisterVariableFloat("Longitude", "Längengrad", "", 40);
		$this->DisableAction("Longitude");
		IPS_SetHidden($this->GetIDForIdent("Longitude"), false);
		
		$this->RegisterVariableString("LongitudeLocal", "Längengrad Lokalität", "", 50);
		$this->DisableAction("LongitudeLocal");
		IPS_SetHidden($this->GetIDForIdent("LongitudeLocal"), false);
		
		$this->RegisterVariableString("MeasurementQuality", "Qualität der Messung", "", 60);
		$this->DisableAction("MeasurementQuality");
		IPS_SetHidden($this->GetIDForIdent("MeasurementQuality"), false);
		
		$this->RegisterVariableInteger("Satellites", "Anzahl Satelliten", "", 70);
		$this->DisableAction("Satellites");
		IPS_SetHidden($this->GetIDForIdent("Satellites"), false);
		
		$this->RegisterVariableFloat("Precision", "Genauigkeit", "", 80);
		$this->DisableAction("Precision");
		IPS_SetHidden($this->GetIDForIdent("Precision"), false);
		
		$this->RegisterVariableFloat("Height", "Höhe über NN", "IPS2GPIO.m", 90);
		$this->DisableAction("Height");
		IPS_SetHidden($this->GetIDForIdent("Height"), false);
		
		$this->RegisterVariableString("Status", "Status der Bestimmung", "", 100);
		$this->DisableAction("Status");
		IPS_SetHidden($this->GetIDForIdent("Status"), false);
		
		$this->RegisterVariableFloat("CogT", "Kurs (wahr)", "~WindDirection.F", 110);
		$this->DisableAction("CogT");
		IPS_SetHidden($this->GetIDForIdent("CogT"), false);
		
		$this->RegisterVariableFloat("CogM", "Kurs (magnetisch)", "~WindDirection.F", 120);
		$this->DisableAction("CogM");
		IPS_SetHidden($this->GetIDForIdent("CogM"), false);
		
		$this->RegisterVariableFloat("Kph", "Geschwindigkeit", "~WindSpeed.kmh", 130);
		$this->DisableAction("Kph");
		IPS_SetHidden($this->GetIDForIdent("Kph"), false);
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
		
		$this->SetBuffer("Serial_GPS_Data", "");

		//ReceiveData-Filter setzen 		    
		$Filter = '((.*"Function":"get_serial".*|.*"Pin":".$this->ReadPropertyInteger("Pin_RxD").".*)|(.*"Pin":".$this->ReadPropertyInteger("Pin_TxD").".*|.*"Function":"set_serial_gps_data".*))'; 
 		$this->SetReceiveDataFilter($Filter); 
 
        	If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// den Handle für dieses Gerät ermitteln
			//$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_serial", "Baud" => 9600, "Device" => $this->ReadPropertyString('ConnectionString'), "InstanceID" => $this->InstanceID )));
			If (($this->ReadPropertyInteger("Pin_RxD") >= 0) AND ($this->ReadPropertyInteger("Pin_TxD") >= 0) AND ($this->ReadPropertyBoolean("Open") == true) ) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "open_bb_serial_gps", "Baud" => 9600, "Pin_RxD" => $this->ReadPropertyInteger("Pin_RxD"), "PreviousPin_RTxD" => $this->GetBuffer("PreviousPin_RxD"), "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "PreviousPin_TxD" => $this->GetBuffer("PreviousPin_TxD"), "InstanceID" => $this->InstanceID )));
				$this->SetBuffer("PreviousPin_RxD", $this->ReadPropertyInteger("Pin_RxD"));
				$this->SetBuffer("PreviousPin_TxD", $this->ReadPropertyInteger("Pin_TxD"));
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
		
	        default:
	            throw new Exception("Invalid Ident");
	    }
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	/*
		http://chris.cnie.de/avr/neo-6.html
		•RMC (Recommended Minimum data) Uhrzeit, Status, Längengrad, Breitengrad, Geschwindigkeit in Knoten, Kurs, 
		Datum und ein Modusfeld. Die Uhrzeit ist in UTC (GMT) in der Form HHMMSS.SS, also Stunden, Minuten und Sekunden 
		mit zwei Nachkommastellen für die Sekunden. Die Postion wird in DDMM.MMMMM {N|S} bzw. DDDMM.MMMMM {E|W} 
		angegeben; also Grad, Minuten und fünf Nachkommastellen der Winkelminuten. Der Längengrad kann maximal bis 
		90 Grad gehen und wird deshalb zweistellig angegeben; der Breitengrad bis 180 Grad und deshalb immer dreistellig. 
		Das Statusfeld hat den Wert A, wenn die Werte gültig sind und den Wert V bei einer Warnung des Empfängers. 
		Das Datum wird DDMMYY, also Tag Monat und Jahr angegeben. Im Modusfeld wird die Art des Fixes angegeben. 
		N steht für keinen erfolgreichen FIX und A für einen erfolgreichen. 
		•VTG (Course over ground and Ground speed) Kurs, Status, Geschwindigkeit in Knoten, Geschwindigkeit in Kilometer pro Stunde und ein Modusfeld. Das Statusfeld ist leer, wenn kein Kurs bestimmt werden konnte (kein FIX) und sonst T wie true. Im Modusfeld wird die Art des Fixes angegeben. N steht für keinen erfolgreichen FIX und A für einen erfolgreichen. 
		•GGA (Global positioning system fix data) Uhrzeit, Längengrad, Breitengrad, Status, Anzahl genutzter Satelliten, horizontale Genauigkeit (HDOP, Horizontal Dilution of Precision), Höhe über dem Mehresspiegel in Metern und die "Geoid Separation" in Metern. 
		•GSA (GNSS DOP and Active Satellites) Modus, Status und Liste der zum Fix genutzten Satelliten. Im Feld Modus wird zwischen M für manuell und A für automatisch unterschieden. Dabei geht es um die Unterscheidung zwischen 2D- und 3D-Fix. Im Feld Status ist dann die Art des Fixes ersichtlich: 1.kein Fix
		2.2D- Fix
		3.3D- Fix

		•GSV (GNSS Satellites in View) Je maximal vier Satelliten mit ihren Daten pro Meldung. Jede Meldung beginnt mit der Gesamtzahl der GSV-Meldungen und der laufenden Nummer der Meldung. 
		•GLL (Latitude and longitude, with time of position fix and status) Längengrad, Breitengrad, Uhrzeit, Status und Modus. Die Inhalte entsprechen den jeweiligen Feldern der RMC-Meldung. 
		
		http://www.kowoma.de/gps/zusatzerklaerungen/NMEA.htm
		*/
		
		
		$data = json_decode($JSONString);
	 	switch ($data->Function) {
			case "get_serial":
			   	$this->ApplyChanges();
				break;
			case "set_serial_gps_data":
				If (strlen($this->GetBuffer("Serial_GPS_Data")) < 2000) {
					$this->SetBuffer("Serial_GPS_Data", $this->GetBuffer("Serial_GPS_Data").utf8_decode($data->Value));
				}
				else {
					$this->SendDebug("Datenanalyse","Serial_GPS_Data > 2000: ".$this->GetBuffer("Serial_GPS_Data"), 0);
					$this->SetBuffer("Serial_GPS_Data", utf8_decode($data->Value));
				}
				$subject = $this->GetBuffer("Serial_GPS_Data");
				$replace = "";
				// unvollständigen Datensatzanfang löschen, vollständiger Datensatz beginnt mit $GPRMC
				$pattern = '$GPRMC';
				$PositionStart = strpos($subject, $pattern);
				If ($PositionStart > 0) {
					// wenn $GPRMC gefunden wird, alles vor $GPRMC löschen
					$subject =  substr_replace ($subject , $replace , 0, $PositionStart);
				}
				// komplette Datensätze suchen
				$pattern = '/(\$GPRMC|\$GPGGA|\$GPVTG)([^(\r\n|\n|\r)]*)(\r\n|\n|\r)/'; 
				preg_match_all($pattern, $subject, $treffer);
				// Relevantes Ergebnis herausfiltern
				$GPS_Data = array();
				$GPS_Data = $treffer[0];
				$this->SetResult(serialize($GPS_Data));
				// Herauslöschen der gesendeten Datensätze
				$pattern = '/(\$GPRMC|\$GPVTG|\$GPGGA|\$GPGSA|\$GPGSV|\$GPGLL|\$GPTXT)([^(\r\n|\n|\r)]*)(\r\n|\n|\r)/'; 
				$subject = preg_replace($pattern, $replace, $subject);
				$this->SetBuffer("Serial_GPS_Data", $subject);
				If (strlen($subject) > 200) {
					$this->SendDebug("Datenanalyse","Serial_GPS_Data > 200: ".$subject, 0);
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
	private function SetResult(String $Data)
	{
		$Sendung = array();
		$Sendung = unserialize($Data);
		$this->SendDebug("Datenanalyse", "Neuer Datensatz:" , 0);
		foreach($Sendung AS $GPS_Data) {
			$GPS_Data = preg_replace("/[[:cntrl:]]/i", "", $GPS_Data);
			$this->SendDebug("Datenanalyse", "GPS-Daten: ".$GPS_Data , 0);
			$GPS_Data_Array = array();
			$GPS_Data_Array = explode(",", $GPS_Data);
			switch ($GPS_Data_Array[0]) {
				case '$GPVTG':
					// $GPVTG,cogt,T,cogm,M,sog,N,kph,K,mode*cs
					//$this->SendDebug("Datenanalyse", "GPVTG" , 0);
					// GPS-Daten: $GPVTG,,T,,M,0.779,N,1.443,K,A*28
					//$GPVTG,cogt,T,cogm,M,sog,N,kph,K,mode*cs
					SetValueFloat($this->GetIDForIdent("CogT"), (float)$GPS_Data_Array[1]);
					SetValueFloat($this->GetIDForIdent("CogM"), (float)$GPS_Data_Array[3]);
					SetValueFloat($this->GetIDForIdent("Kph"), (float)$GPS_Data_Array[7]);
					break;
				case '$GPGGA':
					// $GPGGA,hhmmss.ss,Latitude,N,Longitude,E,FS,NoSV,HDOP,msl,m,Altref,m,DiffAge,DiffStation*cs
					//$this->SendDebug("Datenanalyse", "GPGGA" , 0);
					// GPS-Daten: $GPGGA,040322.00,5321.54268,N,01023.67622,E,1, 06,1.76,5.7,M,44.8,M,,*58
					$GPSTime = (float)$GPS_Data_Array[1];
					$UnixTime = strtotime($GPSTime);
					SetValueInteger($this->GetIDForIdent("Timestamp"), $UnixTime);
					SetValueFloat($this->GetIDForIdent("Latitude"), ((float)$GPS_Data_Array[2] / 100));
					$Local = array("N" => "Nord", "S" => "Süd", "E" => "Ost", "W" => "West");
					If (array_key_exists($GPS_Data_Array[3], $Local)) {
						SetValueString($this->GetIDForIdent("LatitudeLocal"), $Local[$GPS_Data_Array[3]]);
					}
					SetValueFloat($this->GetIDForIdent("Longitude"), ((float)$GPS_Data_Array[4] / 100));
					If (array_key_exists($GPS_Data_Array[5], $Local)) {
						SetValueString($this->GetIDForIdent("LongitudeLocal"), $Local[$GPS_Data_Array[5]]);
					}
					$MeasurementQuality = array(0 => "ungültig", 1 => "GPS", 2 => "DGPS", 6 => "geschätzt" );
					If (array_key_exists($GPS_Data_Array[6], $MeasurementQuality)) {
						SetValueString($this->GetIDForIdent("MeasurementQuality"), $MeasurementQuality[(int)$GPS_Data_Array[6]]);
					}
					SetValueInteger($this->GetIDForIdent("Satellites"), (int)$GPS_Data_Array[7]);
					SetValueFloat($this->GetIDForIdent("Precision"), (float)$GPS_Data_Array[8]);
					SetValueFloat($this->GetIDForIdent("Height"), (float)$GPS_Data_Array[9]);
					break;
				case '$GPGSA':
					// $GPGSA,Smode,FS{,sv},PDOP,HDOP,VDOP*cs
					//$this->SendDebug("Datenanalyse", "GPGSA" , 0);
					break;
				case '$GPGSV':
					// $GPGSV,NoMsg,MsgNo,NoSv,{,sv,elv,az,cno}*cs
					//$this->SendDebug("Datenanalyse", "GPGSV" , 0);
					break;
				case '$GPTXT':
					//$this->SendDebug("Datenanalyse", "GPTXT" , 0);
					break;
				case '$GPRMC':
					// $GPRMC,hhmmss,status,latitude,N,longitude,E,spd,cog,ddmmyy,mv,mvE,mode*cs
					//$this->SendDebug("Datenanalyse", "GPRMC" , 0);
					// GPS-Daten: $GPRMC,174952.00,A,5321.54883,N,01023.67784,E,1.443,,210917,,,A*7F
					$Status = array("A" => "gültig", "V" => "ungültig");
					SetValueString($this->GetIDForIdent("Status"), $Status[$GPS_Data_Array[2]]);

					break;
				case '$GPGLL':
					// $GPGLL,Latitude,N,Longitude,E,hhmmss.ss,Valid,Mode*cs
					//$this->SendDebug("Datenanalyse", "GPGLL" , 0);
					break;
				case '$GPZDA':
					// $GPZDA,hhmmss.ss,day,month,year,ltzh,ltzn*cs
					//$this->SendDebug("Datenanalyse", "GPZDA" , 0);
					break;
				case '$GPGST':
					// $GPGST,hhmmss.ss,range_rms,std_major,std_minor,hdg,std_lat,std_long,std_alt*cs
					//$this->SendDebug("Datenanalyse", "GPGST" , 0);
					break;
				case '$GPGRS':
					// $GPGRS,hhmmss.ss, mode {,residual}*cs
					//$this->SendDebug("Datenanalyse", "GPGRS" , 0);
					break;
			}

		}
	}
	    
	public function Send(String $Message)
	{
		$Message = utf8_encode($Message."\r\n");
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytes_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => $Message)));
	}
	
	public function HotStart()
	{
		$this->Send('$PMTK101*32');
	}
	
	public function WarmStart()
	{
		$this->Send('$PMTK102*31');
	}

	public function ColdStart()
	{
		$this->Send('$PMTK103*30');
	}
	
	public function FullColdStart()
	{
		$this->Send('$PMTK104*37');
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
