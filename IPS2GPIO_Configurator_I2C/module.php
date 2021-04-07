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
		$arrayColumns[] = array("caption" => "Typ", "name" => "DeviceTyp", "width" => "200px", "add" => "");
		$arrayColumns[] = array("caption" => "Adresse", "name" => "DeviceAddress", "width" => "100px", "add" => "");
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
		$TypeArray = array("AS3935" => "{BC292F9B-7CAB-4195-A85D-A6228B521E08}", "S.USV" => "{E6955943-F7F1-48CD-979D-45EEDCF91629}", "PCF8574" => "{E1E9F012-A15A-4C05-834E-7893DFE34526}",
				     "PCF8591" => "{A2E052CE-055C-4249-A536-7082B233B583}", "PCF8583" => "{95276FA0-4847-411E-B700-2E5F1866A7F6}", "GeCoS PWM16Out" => "{2ED6393D-E9A6-4C68-824C-90530EDDCE5C}",
				     "GeCoS RGBW" => "{3AB26B93-0DD1-4F5C-AFC8-1C3A855F7D14}", "iAQ" => "{1ABC9D19-31BF-4482-8FE0-6D3843D1D77A}", "BH1750" => "{C3884BB9-1D68-4AF7-B73E-357D810042A7}",
				     "EZO ORP" => "{51401510-EBA1-2C99-5B39-3C0C9C9758B6}", "EZO PH" => "{4D846905-0066-AB5D-F997-DC01CB1D975E}", "MCP3424" => "{0EBA825C-47AD-4BC6-AC0D-1ADF9CD55AB2}",
				     "BME280" => "{64E6464A-664C-46DE-B49F-8629497ED56F}", "BME680" => "{54EBA6FB-A557-4CB9-B384-933D6F5155B6}", "BMP180" => "{9D970308-36E7-428D-8AC0-D8C1496DDCCA}");
		$BusArray = array("I²C-Bus 0", "I²C-Bus 1", "unknown", "MUX I²C-Bus 0", "MUX I²C-Bus 1", "MUX I²C-Bus 2", "MUX I²C-Bus 3", "MUX I²C-Bus 4", "MUX I²C-Bus 5", "MUX I²C-Bus 6", "MUX I²C-Bus 7",);

		$arrayValues = array();
		for ($i = 0; $i < Count($DeviceArray); $i++) {
			If (array_key_exists($DeviceArray[$i]["Typ"], $TypeArray)) {
				$arrayCreate = array();
				$GUID = $TypeArray[$DeviceArray[$i]["Typ"]];
				$Bus = array_search($DeviceArray[$i]["Bus"], $BusArray);
				$arrayCreate = array("moduleID" => $GUID, "location" => $RootNames, 
					       "configuration" => array("DeviceAddress" => $DeviceArray[$i]["Adresse"], "DeviceBus" => $Bus));
				$arrayValues[] = array("DeviceTyp" => $DeviceArray[$i]["Typ"], "DeviceAddress" => $DeviceArray[$i]["Adresse"], "DeviceBus" => $DeviceArray[$i]["Bus"],
					       "instanceID" => $DeviceArray[$i]["InstanceID"], "create" => $arrayCreate);
			}
			else {
				$arrayValues[] = array("DeviceTyp" => $DeviceArray[$i]["Typ"], "DeviceAddress" => $DeviceArray[$i]["Adresse"], "DeviceBus" => $DeviceArray[$i]["Bus"],
					       "instanceID" => $DeviceArray[$i]["InstanceID"]);
			}
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
			//$this->SendDebug("GetData", $Result, 0);
			$ResultArray = array();
			$ResultArray = unserialize($Result);
			
			$i = 0;
			foreach($ResultArray as $Device) {
				$DeviceArray[$i]["Typ"] = $Device[0];
				$DeviceArray[$i]["Adresse"] = $Device[1];
				$DeviceArray[$i]["Bus"] = $Device[2];
				$DeviceArray[$i]["InstanceID"] = $this->GetDeviceInstanceID($Device[0], $Device[1], $Device[2]);
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
	
	function GetDeviceInstanceID(string $Type, int $Address, string $Bus)
	{
		$Result = 0;

		$TypeArray = array("AS3935" => "{BC292F9B-7CAB-4195-A85D-A6228B521E08}", "S.USV" => "{E6955943-F7F1-48CD-979D-45EEDCF91629}", "PCF8574" => "{E1E9F012-A15A-4C05-834E-7893DFE34526}",
				     "PCF8591" => "{A2E052CE-055C-4249-A536-7082B233B583}", "PCF8583" => "{95276FA0-4847-411E-B700-2E5F1866A7F6}", "GeCoS PWM16Out" => "{2ED6393D-E9A6-4C68-824C-90530EDDCE5C}",
				     "GeCoS RGBW" => "{3AB26B93-0DD1-4F5C-AFC8-1C3A855F7D14}", "iAQ" => "{1ABC9D19-31BF-4482-8FE0-6D3843D1D77A}", "BH1750" => "{C3884BB9-1D68-4AF7-B73E-357D810042A7}",
				     "EZO ORP" => "{51401510-EBA1-2C99-5B39-3C0C9C9758B6}", "EZO PH" => "{4D846905-0066-AB5D-F997-DC01CB1D975E}", "MCP3424" => "{0EBA825C-47AD-4BC6-AC0D-1ADF9CD55AB2}",
				     "BME280" => "{64E6464A-664C-46DE-B49F-8629497ED56F}", "BME680" => "{54EBA6FB-A557-4CB9-B384-933D6F5155B6}", "BMP180" => "{9D970308-36E7-428D-8AC0-D8C1496DDCCA}",
				     "DS3231" => "{EA8A9345-DC36-4D40-8AA9-BB07329AAF7B}");
		$BusArray = array("I²C-Bus 0", "I²C-Bus 1", "MUX I²C-Bus 0", "unknown", "MUX I²C-Bus 1", "MUX I²C-Bus 2", "MUX I²C-Bus 3", "MUX I²C-Bus 4", "MUX I²C-Bus 5", "MUX I²C-Bus 6", "MUX I²C-Bus 7",);

		
		/*
		// Bausteine fehlen noch
		// MCP3424|DS3231
		$SearchArray[] = 104;
		$DeviceName[] = "MCP3424|DS3231";
		
		// PCF8574
		for ($i = 32; $i <= 34; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8574|MCP23017";
		}
		
		// BH1750
		$SearchArray[] = 35;
		$DeviceName[] = "BH1750|MCP23017";
		
		// PCF8574
		for ($i = 36; $i <= 39; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8574|MCP23017";
		}
		*/
		
		
		if ((array_key_exists($Type, $TypeArray)) AND (in_array($Bus, $BusArray))) {
			$guid = $TypeArray[$Type];
			// Modulinstanzen suchen
			$InstanceArray = array();
			$InstanceArray = @(IPS_GetInstanceListByModuleID($guid));
			If (is_array($InstanceArray)) {
				foreach($InstanceArray as $Module) {
					If ((IPS_GetProperty($Module, "DeviceAddress") == $Address) AND (IPS_GetProperty($Module, "DeviceBus") == array_search($Bus, $BusArray))) {
						$this->SendDebug("GetDeviceInstanceID", "Gefundene Instanz: ".$Module, 0);
						$Result = $Module;
						break;
					}
					else {
						$Result = 0;
					}
				}
			}
		}
	return $Result;
	}
}
?>
