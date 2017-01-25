<?
    // Klassendefinition
    class IPS2GPIO_1Wire extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
            	$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyInteger("Messzyklus", 15);
		$this->RegisterTimer("Messzyklus", 0, 'I2G1W_Measurement($_IPS["TARGET"]);');
 	    	$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
        }
       	
	public function GetConfigurationForm() { 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft"); 
		
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv"); 
 		$arrayElements[] = array("type" => "Label", "label" => "GPIO 4 (Pin 7) ist dafür ausschließlich zu verwenden"); 
  		$arrayElements[] = array("type" => "Label", "label" => "Wiederholungszyklus in Sekunden (0 -> aus, 15 sek -> Minimum)"); 
		$arrayElements[] = array("type" => "IntervalBox", "name" => "Messzyklus", "caption" => "Messzyklus (sek)");
 		
		$SensorArray = unserialize(GetValueString($this->GetIDForIdent("SensorArray")));
		If (is_array($SensorArray) {
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________"); 
			$arrayElements[] = array("type" => "Label", "label" => "Detektierte 1-Wire Sensoren:");
			for ($i = 0; $i < Count($SensorArray); $i++) {
				$arrayElements[] = array("type" => "Label", "label" => $SensorArray[$i]);
			}
		}
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	} 

	
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
                // Diese Zeile nicht löschen
                parent::ApplyChanges();
                //Connect to available splitter or create a new one
	        $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	   
	        //Status-Variablen anlegen
	        $this->RegisterVariableString("SensorArray", "SensorArray", "", 5);
		$this->DisableAction("SensorArray");
		IPS_SetHidden($this->GetIDForIdent("SensorArray"), true);
            
                //ReceiveData-Filter setzen
		$Filter = '((.*"Function":"get_usedpin".*|.*"Pin":"4".*)|.*"InstanceID":'.$this->InstanceID.'.*)';
		//$Filter = '(.*"Function":"get_usedpin".*|.*"Pin":"4".*)';
		$this->SetReceiveDataFilter($Filter);
		
		If (IPS_GetKernelRunlevel() == 10103) {
			If ($this->ReadPropertyBoolean("Open") == true) {
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => 4, "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->Setup();
				$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
				// Erste Messung durchführen
				$this->Measurement();
				$this->SetStatus(102);
			}
			else {
				$this->SetTimerInterval("Messzyklus", 0);
				$this->SetStatus(104);
			}
		}
		else {
			$this->SetTimerInterval("Messzyklus", 0);
		}
	}
	
	public function ReceiveData($JSONString) 
	{
	    	// Empfangene Daten vom Gateway/Splitter
	    	$data = json_decode($JSONString);
	 	switch ($data->Function) {
			  
			case "get_usedpin":
			   	If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => 4, "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
					$this->ApplyChanges();
				}
				break;
			case "status":
			   	If ($data->Pin == $this->ReadPropertyInteger("Pin")) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
			case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
			case "set_1wire_devices":
			   	$ResultArray = unserialize(utf8_decode($data->Result));
				SetValueString($this->GetIDForIdent("SensorArray"), utf8_decode($data->Result));
				If (count($ResultArray) > 0 ) {
					for ($i = 0; $i < Count($ResultArray); $i++) {
						//IPS_LogMessage("IPS2GPIO 1-Wire: ","Sensor ".$ResultArray[$i]);
						$Ident = "Sensor_".str_replace("-", "", $ResultArray[$i]);
						$this->RegisterVariableFloat($Ident, "Sensor_".$ResultArray[$i], "~Temperature", ($i + 1) *10);
						$this->DisableAction($Ident);
						$Ident = "CRC_".str_replace("-", "", $ResultArray[$i]);
						$this->RegisterVariableBoolean($Ident, "CRC_".$ResultArray[$i], "~Alert.Reversed", ($i + 1) *12);
						$this->DisableAction($Ident);
					}
				}
				else {
					IPS_LogMessage("IPS2GPIO 1-Wire","Keine 1-Wire-Sensoren gefunden!");
				}	
			   	break;
			case "set_1wire_data":
			   	$ResultArray = unserialize(utf8_decode($data->Result));
				$SensorArray = unserialize(GetValueString($this->GetIDForIdent("SensorArray")));
				
				If (count($ResultArray) > 0 ) {
					for ($i = 0; $i < Count($ResultArray); $i++) {
						$Ident = "Sensor_".str_replace("-", "", $SensorArray[$i]);
						$LinesArray = explode(chr(10), $ResultArray[$i]);
						// Temperatur auskoppeln
						SetValueFloat($this->GetIDForIdent("$Ident"), (int)substr($LinesArray[1], -5) / 1000);
						// CRC auskoppeln
						$Ident = "CRC_".str_replace("-", "", $SensorArray[$i]);
						If (trim(substr($LinesArray[0], -4)) == "YES") {
							SetValueBoolean($this->GetIDForIdent("$Ident"), true);
						}
						else {
							SetValueBoolean($this->GetIDForIdent("$Ident"), false);
						}

					}
				}
				else {
					IPS_LogMessage("IPS2GPIO 1-Wire","Es konnten keine 1-Wire-Messergebnisse ermittelt werden!");
				}		
			   	break;
	 	}
 	}
	// Beginn der Funktionen
	private function Setup()
	{
		// Ermittlung der angeschlossenen Sensoren
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_1wire_devices", "InstanceID" => $this->InstanceID )));
	}
	    
	public function Measurement()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$CommandArray = Array();
			// Zusammenstellung der Sensoren
			$SensorArray = unserialize(GetValueString($this->GetIDForIdent("SensorArray")));
			If (count($SensorArray) > 0 ) {
				for ($i = 0; $i < Count($SensorArray); $i++) {
					$CommandArray[$i] = "cat /sys/bus/w1/devices/".$SensorArray[$i]."/w1_slave";
					//IPS_LogMessage("IPS2GPIO 1-Wire: ","Sensoranfrage: ".$CommandArray[$i]);
				}
				$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_1W_data", "InstanceID" => $this->InstanceID,  "Command" => serialize($CommandArray) )));
			}
			else {
				IPS_LogMessage("IPS2GPIO 1-Wire","Keine Sensoren vorhanden!");
			}
		}
	}
}
?>
