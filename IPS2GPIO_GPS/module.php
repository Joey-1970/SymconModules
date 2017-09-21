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
	    	$this->RegisterPropertyInteger("Baud", 3);
		$this->RegisterPropertyInteger("Pin_RxD", -1);
		$this->RegisterPropertyInteger("Pin_TxD", -1);
            	
            	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
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
	   
		//Status-Variablen anlegen
		

		//ReceiveData-Filter setzen 		    
		$Filter = '((.*"Function":"get_serial".*|.*"Pin":".$this->ReadPropertyInteger("Pin_RxD").".*)|(.*"Pin":".$this->ReadPropertyInteger("Pin_TxD").".*|.*"Function":"set_serial_gps_data".*))'; 
 		$this->SetReceiveDataFilter($Filter); 
 
        	If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// den Handle für dieses Gerät ermitteln
			//$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_serial", "Baud" => 9600, "Device" => $this->ReadPropertyString('ConnectionString'), "InstanceID" => $this->InstanceID )));
			If (($this->ReadPropertyInteger("Pin_RxD") >= 0) AND ($this->ReadPropertyInteger("Pin_TxD") >= 0) AND ($this->ReadPropertyBoolean("Open") == true) ) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "open_bb_serial_gps", "Baud" => 9600, "Pin_RxD" => $this->ReadPropertyInteger("Pin_RxD"), "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "InstanceID" => $this->InstanceID )));

				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}
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
		•RMC (Recommended Minimum data) Uhrzeit, Status, Längengrad, Breitengrad, Geschwindigkeit in Knoten, Kurs, Datum und ein Modusfeld. Die Uhrzeit ist in UTC (GMT) in der Form HHMMSS.SS, also Stunden, Minuten und Sekunden mit zwei Nachkommastellen für die Sekunden. Die Postion wird in DDMM.MMMMM {N|S} bzw. DDDMM.MMMMM {E|W} angegeben; also Grad, Minuten und fünf Nachkommastellen der Winkelminuten. Der Längengrad kann maximal bis 90 Grad gehen und wird deshalb zweistellig angegeben; der Breitengrad bis 180 Grad und deshalb immer dreistellig. Das Statusfeld hat den Wert A, wenn die Werte gültig sind und den Wert V bei einer Warnung des Empfängers. Das Datum wird DDMMYY, also Tag Monat und Jahr angegeben. Im Modusfeld wird die Art des Fixes angegeben. N steht für keinen erfolgreichen FIX und A für einen erfolgreichen. 
		•VTG (Course over ground and Ground speed) Kurs, Status, Geschwindigkeit in Knoten, Geschwindigkeit in Kilometer pro Stunde und ein Modusfeld. Das Statusfeld ist leer, wenn kein Kurs bestimmt werden konnte (kein FIX) und sonst T wie true. Im Modusfeld wird die Art des Fixes angegeben. N steht für keinen erfolgreichen FIX und A für einen erfolgreichen. 
		•GGA (Global positioning system fix data) Uhrzeit, Längengrad, Breitengrad, Status, Anzahl genutzter Satelliten, horizontale Genauigkeit (HDOP, Horizontal Dilution of Precision), Höhe über dem Mehresspiegel in Metern und die "Geoid Separation" in Metern. 
		•GSA (GNSS DOP and Active Satellites) Modus, Status und Liste der zum Fix genutzten Satelliten. Im Feld Modus wird zwischen M für manuell und A für automatisch unterschieden. Dabei geht es um die Unterscheidung zwischen 2D- und 3D-Fix. Im Feld Status ist dann die Art des Fixes ersichtlich: 1.kein Fix
		2.2D- Fix
		3.3D- Fix

		•GSV (GNSS Satellites in View) Je maximal vier Satelliten mit ihren Daten pro Meldung. Jede Meldung beginnt mit der Gesamtzahl der GSV-Meldungen und der laufenden Nummer der Meldung. 
		•GLL (Latitude and longitude, with time of position fix and status) Längengrad, Breitengrad, Uhrzeit, Status und Modus. Die Inhalte entsprechen den jeweiligen Feldern der RMC-Meldung. 
		*/
		
		
		$data = json_decode($JSONString);
	 	switch ($data->Function) {
			 case "get_serial":
			   	$this->ApplyChanges();
				break;
			 case "set_serial_gps_data":
			   	$Sendung = array();
				$Sendung = unserialize($data->Value);
				foreach($Sendung AS $GPS_Data) {
					$GPS_Data = preg_replace("/[[:cntrl:]]/i", "", $GPS_Data);
					$this->SendDebug("Datenanalyse", "GPS-Daten: ".$GPS_Data , 0);
					$GPS_Data_Array = array();
					$GPS_Data_Array = explode(",", $GPS_Data);
					switch ($GPS_Data_Array[0]) {
						case "$GPVTG":
							// $GPVTG,cogt,T,cogm,M,sog,N,kph,K,mode*cs
							$this->SendDebug("Datenanalyse", "GPVTG" , 0);
							break;
						case "$GPGGA":
							// $GPGGA,hhmmss.ss,Latitude,N,Longitude,E,FS,NoSV,HDOP,msl,m,Altref,m,DiffAge,DiffStation*cs
							$this->SendDebug("Datenanalyse", "GPGGA" , 0);
							break;
						case "$GPGSA":
							// $GPGSA,Smode,FS{,sv},PDOP,HDOP,VDOP*cs
							$this->SendDebug("Datenanalyse", "GPGSA" , 0);
							break;
						case "$GPGSV":
							// $GPGSV,NoMsg,MsgNo,NoSv,{,sv,elv,az,cno}*cs
							$this->SendDebug("Datenanalyse", "GPGSV" , 0);
							break;
						case "$GPTXT":
							$this->SendDebug("Datenanalyse", "GPTXT" , 0);
							break;
						case "$GPRMC":
							// $GPRMC,hhmmss,status,latitude,N,longitude,E,spd,cog,ddmmyy,mv,mvE,mode*cs
							$this->SendDebug("Datenanalyse", "GPRMC" , 0);
							break;
						case "$GPGLL":
							// $GPGLL,Latitude,N,Longitude,E,hhmmss.ss,Valid,Mode*cs
							$this->SendDebug("Datenanalyse", "GPGLL" , 0);
							break;
						case "$GPZDA":
							// $GPZDA,hhmmss.ss,day,month,year,ltzh,ltzn*cs
							$this->SendDebug("Datenanalyse", "GPZDA" , 0);
							break;
						case "$GPGST":
							// $GPGST,hhmmss.ss,range_rms,std_major,std_minor,hdg,std_lat,std_long,std_alt*cs
							$this->SendDebug("Datenanalyse", "GPGST" , 0);
							break;
						case "$GPGRS":
							// $GPGRS,hhmmss.ss, mode {,residual}*cs
							$this->SendDebug("Datenanalyse", "GPGRS" , 0);
							break;
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
