<?
    // Klassendefinition
    class IPS2GPIO_SDS011 extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Messzyklus", 0);
	}
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// https://github.com/kadamski/arduino_sds011/blob/master/lib/Sds011/Sds011.cpp
		// https://cdn.sparkfun.com/assets/parts/1/2/2/7/5/Laser_Dust_Sensor_Control_Protocol_V1.3.pdf
		// https://forum-raspberrypi.de/forum/thread/32634-nova-pm2-5-pm10-feinstaub-sensor-sds011-am-pi-anschlie%C3%9Fen/
		
		// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Pin_RxD", -1);
		$this->SetBuffer("PreviousPin_RxD", -1);
		$this->RegisterPropertyInteger("Pin_TxD", -1);
		$this->SetBuffer("PreviousPin_TxD", -1);
		$this->RegisterTimer("Messzyklus", 0, 'I2GSDS011_GetData($_IPS["TARGET"]);');
            	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		
		// Profil anlegen
		$this->RegisterProfileInteger("IPS2GPIO.SDS011", "Intensity", "", " ug/m³", 0, 999, 1);
		
		// Statusvariablen anlegen
		$this->RegisterVariableInteger("PM25", "PM 2.5", "IPS2GPIO.SDS011", 10);
		$this->DisableAction("PM25");
		IPS_SetHidden($this->GetIDForIdent("PM25"), false);
		
		$this->RegisterVariableInteger("PM10", "PM 10", "IPS2GPIO.SDS011", 20);
		$this->DisableAction("PM10");
		IPS_SetHidden($this->GetIDForIdent("PM10"), false);

        }
	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Serieller Kommunikationfehler!");
		
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
		
        	If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {
			// den Handle für dieses Gerät ermitteln
			If (($this->ReadPropertyInteger("Pin_RxD") >= 0) AND ($this->ReadPropertyInteger("Pin_TxD") >= 0) AND ($this->ReadPropertyBoolean("Open") == true) ) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "open_bb_serial_sds011", "Baud" => 9600, "Pin_RxD" => $this->ReadPropertyInteger("Pin_RxD"), "PreviousPin_RTxD" => $this->GetBuffer("PreviousPin_RxD"), "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "PreviousPin_TxD" => $this->GetBuffer("PreviousPin_TxD"), "InstanceID" => $this->InstanceID )));
				$this->SetBuffer("PreviousPin_RxD", $this->ReadPropertyInteger("Pin_RxD"));
				$this->SetBuffer("PreviousPin_TxD", $this->ReadPropertyInteger("Pin_TxD"));
				$this->SetTimerInterval("Messzyklus", 2 * 1000);
				$this->SetStatus(102);
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
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
		$data = json_decode($JSONString);
	 	switch ($data->Function) {
			 case "get_serial":
			   	$this->ApplyChanges();
				break;
			 case "status":
			   	If (($data->Pin == $this->ReadPropertyInteger("Pin_RxD")) OR ($data->Pin == $this->ReadPropertyInteger("Pin_TxD"))) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
	 	}
 	}
	
	// Beginn der Funktionen
	public function GetData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetData", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "read_bb_serial", "Pin_RxD" => $this->ReadPropertyInteger("Pin_RxD") )));
			If (!$Result) {
				$this->SendDebug("GetData", "Lesen des Dateneingangs nicht erfolgreich!", 0);
				$this->SetStatus(202);
			}
			else {
				$this->SetStatus(102);
				$ByteMessage = array();
				$ByteMessage = unpack("C*", $Result);
				$this->SendDebug("GetData", $Result, 0);
				$this->SendDebug("GetData", serialize($ByteMessage), 0);
				
				$StartKey = array_search(170, $ByteMessage);
				/*
				If (($StartKey === 0) OR ($StartKey > 0)) {
				    	// Startwert wurde gefunden
				    	// Daten vor AA entfernen
				    	If ($StartKey > 0) {
						for ($i = 0; $i < $StartKey; $i++) {
						    unset ($ByteMessage[$i]);
						}
						$ByteMessage = array_values($ByteMessage);
						If (isset($ByteMessage[9])) {
							for ($i = 0; $i <= 9; $i++) {
								$ResponseArray[$i] = $ByteMessage[$i];
								unset($ByteMessage[$i]); 
								// $ResponseArray[$i] an Funktion zur Auswertung senden
							}
							$ByteMessage = array_values($ByteMessage);
							// Restarray zum Anfügen an nächsten ankommenden Datensatz sichern
						}
						else {
						    	// Endwert nicht gefunden
						    	$ByteMessage = array_values($ByteMessage);
							// Restarray zum Anfügen an nächsten ankommenden Datensatz sichern
							return;
						}
						
				    	} 
				}
				else {
				    	$ByteMessage = array_values($ByteMessage);
					// Restarray zum Anfügen an nächsten ankommenden Datensatz sichern
					
					// Kein Startwert vorhanden, Schleife beenden
				    	return;
				}
				*/
			}
			
		}
	}				
	
	private function EvaluateData()
	{
		// Daten aufteilen
	}
	
	    
	public function GetReportingMode()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetReportingMode", "Ausfuehrung", 0);
			$Checksum = (0x02 + 0xFF + 0xFF) & 0xFF;
			//$Message = pack("C*", 0xAA, 0xB4, 0x02, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytes_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => $Message)));
			
			$Message = array(0xAA, 0xB4, 0x02, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}
	    
	public function SetReportingMode(Bool $ActiveMode)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetReportingMode", "Ausfuehrung", 0);
			$Checksum = (0x02 + 0x01 + intval(!$ActiveMode) + 0xFF + 0xFF) & 0xFF;
			//$Message = pack("C*", 0xAA, 0xB4, 0x02, 0x01, !$ActiveMode, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytes_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => $Message)));
			
			$Message = array(0xAA, 0xB4, 0x02, 0x01, !$ActiveMode, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}    
	
	public function QueryData()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("QueryData", "Ausfuehrung", 0);
			$Checksum = (0x04 + 0xFF + 0xFF) & 0xFF;
			//$Message = pack("C*", 0xAA, 0xB4, 0x04, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytes_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => $Message)));
			
			$Message = array(0xAA, 0xB4, 0x04, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}       
	
	public function GetSleepWork()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetSleepWork", "Ausfuehrung", 0);
			$Checksum = (0x06 + 0xFF + 0xFF) & 0xFF;
			//$Message = pack("C*", 0xAA, 0xB4, 0x06, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytes_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => $Message)));
			
			$Message = array(0xAA, 0xB4, 0x06, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}
	
	public function SetSleepWork(Bool $Active)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetSleepWork", "Ausfuehrung", 0);
			$Checksum = (0x06 + 0x01 + intval(!$Active) + 0xFF + 0xFF) & 0xFF;
			//$Message = pack("C*", 0xAA, 0xB4, 0x02, 0x01, intval(!$Active), 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytes_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => $Message)));

			$Message = array(0xAA, 0xB4, 0x02, 0x01, intval(!$Active), 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}        
	
	public function GetWorkingPeriod()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetWorkingPeriod", "Ausfuehrung", 0);
			$Checksum = (0x08 + 0xFF + 0xFF) & 0xFF;
			//$Message = pack("C*", 0xAA, 0xB4, 0x08, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytes_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => $Message)));
			
			$Message = array(0xAA, 0xB4, 0x08, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	}
	
	public function SetWorkingPeriod(Int $Time)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("SetWorkingPeriod", "Ausfuehrung", 0);
			$Time = min(30, max(1, $Time));
			$Checksum = (0x08 + 0x01 + $Time + 0xFF + 0xFF) & 0xFF;
			//$Message = pack("C*", 0xAA, 0xB4, 0x02, 0x01, $Time, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytes_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => $Message)));
			
			$Message = pack("C*", 0xAA, 0xB4, 0x02, 0x01, $Time, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
		}
	} 
	    
	private function GetFirmware()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("GetFirmware", "Ausfuehrung", 0);
			$Checksum = (0x07 + 0xFF + 0xFF) & 0xFF;
			//$Message = pack("C*", 0xAA, 0xB4, 0x07, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			//$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytes_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => $Message)));
			
			$Message = array(0xAA, 0xB4, 0x07, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0xFF, 0xFF, $Checksum, 0xAB);
			$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bb_bytesarray_serial", "Baud" => 9600, "Pin_TxD" => $this->ReadPropertyInteger("Pin_TxD"), "Command" => serialize($Message) )));

			$this->GetData();
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
