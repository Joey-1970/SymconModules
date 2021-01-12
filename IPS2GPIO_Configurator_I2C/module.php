<?
    // Klassendefinition
    class IPS2GPIO_Configurator_I2C extends IPSModule 
    {
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		$this->RegisterPropertyInteger("Category", 0);  
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "Label", "caption" => "UNVOLLSTÄNDIGE FUNKTION!");
		$arrayElements[] = array("type" => "SelectCategory", "name" => "Category", "caption" => "Zielkategorie");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");

		// Tabelle für die gefundenen I²C-Devices
		$arraySort = array();
		$arraySort = array("column" => "DeviceTyp", "direction" => "ascending");
		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "Typ", "name" => "DeviceTyp", "width" => "120px", "add" => "");
		$arrayColumns[] = array("caption" => "Adresse", "name" => "DeviceAddress", "width" => "60px", "add" => "");
		$arrayColumns[] = array("caption" => "Bus", "name" => "DeviceBus", "width" => "auto", "add" => "");
		
		$Category = $this->ReadPropertyInteger("Category");
		$RootNames = [];
		$RootId = $Category;
		while ($RootId != 0) {
		    	if ($RootId != 0) {
				$RootNames[] = IPS_GetName($RootId);
		    	}
		    	$RootId = IPS_GetParent($RootId);
			}
		$RootNames = array_reverse($RootNames);
	
		$DeviceArray = array();
		If ($this->HasActiveParent() == true) {
			$DeviceArray = unserialize($this->GetData());
		}
		$arrayValues = array();
		for ($i = 0; $i < Count($DeviceArray); $i++) {
			$arrayCreate = array();
			//$arrayCreate[] = array("moduleID" => "{47286CAD-187A-6D88-89F0-BDA50CBF712F}", "location" => $RootNames, 
			//		       "configuration" => array("StationID" => $StationArray[$i]["StationsID"], "Timer_1" => 10));
			$arrayValues[] = array("Typ" => $DeviceArray[$i]["Typ"], "Adresse" => $DeviceArray[$i]["Adresse"], "Bus" => $DeviceArray[$i]["Bus"],
					       "instanceID" => $DeviceArray[$i]["InstanceID"], 
					       "create" => $arrayCreate);
		}
		
		$arrayElements[] = array("type" => "Configurator", "name" => "I2CDevices", "caption" => "I2C Devices", "rowCount" => 10, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);

			
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If (IPS_GetKernelRunlevel() == 10103) {	
			If ($this->HasActiveParent() == true) {
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}
		}
	}
	    
	// Beginn der Funktionen
	private function GetData()
	{
		$DeviceArray = array();
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "getI2CDeviceArray")));

		If ($Result <> false) {
			$this->SetStatus(102);
			$this->SendDebug("GetData", $Result, 0);
			$ResultArray = array();
			$ResultArray = unserialize($Result);
			
			$i = 0;
			foreach($ResultArray as $Device) {
				$DeviceArray[$i]["Typ"] = $Device[0];
				$DeviceArray[$i]["Adresse"] = $Device[1];
				$DeviceArray[$i]["Bus"] = $Device[2];
				$DeviceArray[$i]["InstanceID"] = 0; //$this->GetStationInstanceID($Stations->id);
				$i = $i + 1;
			}
			$this->SendDebug("GetData", "DeviceArray: ".serialize($DeviceArray), 0);
		}
		else {
			$this->SetStatus(202);
			$this->SendDebug("GetData", "Fehler bei der Datenermittlung!", 0);
		}
	return serialize($DeviceArray);
	}
	
	function GetDeviceInstanceID(string $DeviceID)
	{
		//$guid = "{47286CAD-187A-6D88-89F0-BDA50CBF712F}";
	    	$Result = 0;
	    	/*
		// Modulinstanzen suchen
	    	$InstanceArray = array();
	    	$InstanceArray = @(IPS_GetInstanceListByModuleID($guid));
	    	If (is_array($InstanceArray)) {
			foreach($InstanceArray as $Module) {
				If (strtolower(IPS_GetProperty($Module, "StationID")) == strtolower($StationID)) {
					$this->SendDebug("GetStationInstanceID", "Gefundene Instanz: ".$Module, 0);
					$Result = $Module;
					break;
				}
				else {
					$Result = 0;
				}
			}
		}
		*/
	return $Result;
	}
}
?>
