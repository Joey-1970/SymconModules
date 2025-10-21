<?
// Klassendefinition
class ShotGlassFillingMachine extends IPSModule 
{
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
	}  
	        
	// Überschreibt die interne IPS_Create($id) Funktion
	public function Create() 
	{
		// Diese Zeile nicht löschen.
		parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
		$this->RegisterPropertyBoolean("Open", false);
		
		// Servo
		$this->RegisterPropertyInteger("Pin_Servo", -1);
		$this->SetBuffer("PreviousPin_Servo", -1);
		$this->RegisterPropertyInteger("most_anti_clockwise", 1000);
		$this->RegisterPropertyInteger("midpoint", 1500);
		$this->RegisterPropertyInteger("most_clockwise", 2000);
		$this->RegisterPropertyInteger("Shutdown", 2000);
		$this->RegisterPropertyInteger("RestingPosition", 100);
		for ($i = 1; $i <= 5; $i++) {
			$this->RegisterPropertyInteger("Position_".$i, $i * 20);
		}

		// Relais 1/ Pumpe 1
		$this->RegisterPropertyInteger("Pin_Pump_1", -1);
		$this->SetBuffer("PreviousPin_Pump_1", -1);
		$this->RegisterPropertyFloat("Time_Pump_1", 5.0);

		// Relais 2/ Pumpe 2
		$this->RegisterPropertyInteger("Pin_Pump_2", -1);
		$this->SetBuffer("PreviousPin_Pump_2", -1);
		$this->RegisterPropertyFloat("Time_Pump_2", 5.0);

		// für beide Relais / Pumpen
		$this->RegisterPropertyBoolean("Invert_Pump", false);
		$this->RegisterPropertyInteger("Startoption_Pump", 2);

		// für die TCRT5000
		for ($i = 1; $i <= 5; $i++) {
			$this->RegisterPropertyInteger("Pin_IRSensor_".$i, -1);
			$this->SetBuffer("PreviousPin_IRSensor_".$i, -1);
		}

		// Rundumleuchten
		$this->RegisterPropertyInteger("Pin_RotatingBeacon", -1);
		$this->SetBuffer("PreviousPin_RotatingBeacon", -1);
		$this->RegisterPropertyInteger("Pin_PowerRotatingBeacon", -1);
		$this->SetBuffer("PreviousPin_PowerRotatingBeacon", -1);
		$this->RegisterPropertyInteger("RB_most_anti_clockwise", 1000);
		$this->RegisterPropertyInteger("RB_midpoint", 1500);
		$this->RegisterPropertyInteger("RB_most_clockwise", 2000);

		// Sonstiges
		$this->RegisterPropertyInteger("Modus", 0);
		$this->RegisterPropertyString("PossibleDrinks", "");
		$this->RegisterPropertyInteger("ActivityWatch", 10);
		
		$this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
		$this->RegisterTimer("Shutdown", 0, 'ShotGlassFillingMachine_Shutdown($_IPS["TARGET"]);');
		$this->RegisterTimer("Pump_1", 0, 'ShotGlassFillingMachine_StopPump_1($_IPS["TARGET"]);');
		$this->RegisterTimer("Pump_2", 0, 'ShotGlassFillingMachine_StopPump_2($_IPS["TARGET"]);');
		$this->RegisterTimer("IR_Sensor", 0, 'ShotGlassFillingMachine_GetIRSensor($_IPS["TARGET"]);');
		$this->RegisterTimer("ActivityWatch", 0, 'ShotGlassFillingMachine_ActivityWatch($_IPS["TARGET"]);');

		// Profile erstellen
		$this->RegisterProfileInteger("ShotGlassFillingMachine.Position", "Information", "", "", 0, 5, 0);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.Position", 0, "Ruheposition", "TurnLeft", 0x000000);
		for ($i = 1; $i <= 5; $i++) {
			IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.Position", $i, "Postion ".$i, "TurnRight", 0x000000);
		}

		$this->RegisterProfileInteger("ShotGlassFillingMachine.PreShotGlassFill", "Party", "", "", 0, 2, 0);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.PreShotGlassFill", 0, "Alle Gläser mit Getränk 1 füllen", "Party", 0x000000);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.PreShotGlassFill", 1, "Alle Gläser mit Getränk 2 füllen", "Party", 0x000000);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.PreShotGlassFill", 2, "Individuell füllen", "Party", 0x000000);

		$this->RegisterProfileInteger("ShotGlassFillingMachine.RotatingBeacon", "Bulb", "", "", 0, 4, 0);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.RotatingBeacon", 0, "Schnelles Rundumlicht", "Bulb", 0x000000);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.RotatingBeacon", 1, "Langsames Rundumlicht", "Bulb", 0x000000);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.RotatingBeacon", 2, "Langsames Blinken", "Bulb", 0x000000);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.RotatingBeacon", 3, "Schnelles Blinken", "Bulb", 0x000000);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.RotatingBeacon", 4, "Aus", "Bulb", 0x000000);

		$this->RegisterProfileBoolean("ShotGlassFillingMachine.ShotGlassFill", "Party");
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.ShotGlassFill", 0, "Getränk 1", "Party", 0x000000);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.ShotGlassFill", 1, "Getränk 2", "Party", 0x000000);

		$this->RegisterProfileBoolean("ShotGlassFillingMachine.ShotGlass", "Information");
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.ShotGlass", 0, "Glas", "Ok", 0x00FF00);
		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.ShotGlass", 1, "kein Glas", "Close", 0xFF0000);

		$this->RegisterProfileInteger("ShotGlassFillingMachine.PossibleShots_".$this->InstanceID, "Party", "", "", 0, 10, 0);
		
		// Status-Variablen anlegen
		$this->RegisterVariableInteger("Servo", "Servo", "~Intensity.100", 10);
		$this->RegisterVariableInteger("ServoPosition", "Servo-Position", "ShotGlassFillingMachine.Position", 20);
		$this->RegisterVariableBoolean("State_Pump_1", "Status Pumpe 1", "~Switch", 30);
		$this->RegisterVariableBoolean("State_Pump_2", "Status Pumpe 2", "~Switch", 40);
		for ($i = 1; $i <= 5; $i++) {
			$this->RegisterVariableBoolean("State_IRSensor_".$i, "Status IRSensor ".$i, "ShotGlassFillingMachine.ShotGlass", 40 + ($i * 10));
			$this->DisableAction("State_IRSensor_".$i);
		}
		$this->RegisterVariableBoolean("Start", "Start", "~Switch", 100);
		$this->DisableAction("Start");
		$this->RegisterVariableString("StateText", "Status", "~TextBox", 110);
		$this->RegisterVariableString("StateTextHTML", "Display", "~HTMLBox", 115);
		$this->RegisterVariableBoolean("FillingActive", "Befüllung aktiv", "~Switch", 120);
		$this->RegisterVariableInteger("FillingStep", "Befüllung Schritt", "", 130);
		$this->RegisterVariableInteger("DrinkChoise", "Befüllung wählen", "ShotGlassFillingMachine.PreShotGlassFill", 140);
		$this->EnableAction("DrinkChoise");
		for ($i = 1; $i <= 5; $i++) {
			$this->RegisterVariableBoolean("ShotGlassFill_".$i, "Getränk Glas ".$i, "ShotGlassFillingMachine.ShotGlassFill", 140 + ($i * 10));
			$this->DisableAction("ShotGlassFill_".$i);
		}
		$this->RegisterVariableInteger("PossibleShots_1", "Shots 1", "ShotGlassFillingMachine.PossibleShots_".$this->InstanceID, 200);
		$this->EnableAction("PossibleShots_1");
		$this->RegisterVariableInteger("PossibleShots_2", "Shots 2", "ShotGlassFillingMachine.PossibleShots_".$this->InstanceID, 210);
		$this->EnableAction("PossibleShots_2");
		$this->RegisterVariableBoolean("AfterFilling", "Befüllung erfolgt", "~Switch", 220);
		$this->DisableAction("AfterFilling");

		$this->RegisterVariableInteger("RotatingBeacon", "Rundumleuchte", "ShotGlassFillingMachine.RotatingBeacon", 230);
		$this->EnableAction("RotatingBeacon");
		$this->RegisterVariableBoolean("PowerRotatingBeacon", "Spannung Rundumleuchte(n)", "~Switch", 240);
	}
	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Pin wird doppelt genutzt!");
		$arrayStatus[] = array("code" => 201, "icon" => "error", "caption" => "Pin ist an diesem Raspberry Pi Modell nicht vorhanden!");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "GPIO-Kommunikationfehler!");
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv"); 

		$GPIO = array();
		$GPIO = unserialize($this->Get_GPIO());
		
		$arrayOptions = array();
		If ($this->ReadPropertyInteger("Pin_Servo") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_Servo")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_Servo")));
		}
		If ($this->ReadPropertyInteger("Pin_RotatingBeacon") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_RotatingBeacon")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_RotatingBeacon")));
		}
		If ($this->ReadPropertyInteger("Pin_PowerRotatingBeacon") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_PowerRotatingBeacon")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_PowerRotatingBeacon")));
		}
		If ($this->ReadPropertyInteger("Pin_Pump_1") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_Pump_1")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_Pump_1")));
		}
		If ($this->ReadPropertyInteger("Pin_Pump_2") >= 0 ) {
			$GPIO[$this->ReadPropertyInteger("Pin_Pump_2")] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_Pump_2")));
		}
		for ($i = 1; $i <= 5; $i++) {
			If ($this->ReadPropertyInteger("Pin_IRSensor_".$i) >= 0 ) {
				$GPIO[$this->ReadPropertyInteger("Pin_IRSensor_".$i)] = "GPIO".(sprintf("%'.02d", $this->ReadPropertyInteger("Pin_IRSensor_".$i)));
			}
		}
		
		ksort($GPIO);
		
		// Servo
		$arrayExpansionPanel = array();
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Angabe der GPIO-Nummer (Broadcom-Number) des Servos"); 
		
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayExpansionPanel[] = array("type" => "Select", "name" => "Pin_Servo", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Angabe der Microsekunden bei 50 Hz"); 
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "most_anti_clockwise", "caption" => "Max. Links (µs) - gegen den Uhrzeigersinn", "minimum" => 0); 
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "midpoint", "caption" => "Mittelstellung (µs)", "minimum" => 0); 
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "most_clockwise", "caption" => "Max. Rechts (µs) - im Uhrzeigersinn", "minimum" => 0);
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Zeit bis zur Abschaltung in Microsekunden (0 = keine automatische Abschaltung)"); 
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "Shutdown", "caption" => "Abschaltung (ms)", "minimum" => 0); 
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "ACHTUNG: Falsche Werte können zur Beschädigung des Servo führen!");
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Angabe der Postitionen in Prozent"); 
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "RestingPosition", "caption" => "Ruhe-Position", "minimum" => 0, "maximum" => 100); 
		for ($i = 1; $i <= 5; $i++) {
			$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "Position_".$i, "caption" => "Position ".$i, "minimum" => 0, "maximum" => 100); 
		}
		$arrayElements[] = array("type" => "ExpansionPanel", "caption" => "Servo", "items" => $arrayExpansionPanel);
		
		// Relais 1/ Pumpe 1
		$arrayExpansionPanel = array();
		$arrayExpansionPanel[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für die Pumpe 1"); 
		
		$arrayOptions = array();
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		
		$arrayExpansionPanel[] = array("type" => "Select", "name" => "Pin_Pump_1", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Zeit bis zur Abschaltung in Sekunden (0 = keine automatische Abschaltung)"); 
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "Time_Pump_1", "caption" => "Abschaltung (s)", "minimum" => 0, "maximum" => 10, "digits" => 1); 
		
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		// Relais 2/ Pumpe 2
		$arrayExpansionPanel[] = array("type" => "Label", "label" => "Angabe der GPIO-Nummer (Broadcom-Number) für die Pumpe 2"); 
		
		$arrayOptions = array();
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayExpansionPanel[] = array("type" => "Select", "name" => "Pin_Pump_2", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Zeit bis zur Abschaltung in Sekunden (0 = keine automatische Abschaltung)"); 
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "Time_Pump_2", "caption" => "Abschaltung (s)", "minimum" => 0, "maximum" => 10, "digits" => 1); 
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "_____________________________________________________________________________________________________"); 
		// für beide Relais/Pumpen
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Für beiden Pumpen"); 
		$arrayExpansionPanel[] = array("name" => "Invert_Pump", "type" => "CheckBox",  "caption" => "Invertiere Anzeige");
		$arrayExpansionPanel[] = array("type" => "Label", "label" => "Status des Ausgangs nach Neustart");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Aus", "value" => 0);
		$arrayOptions[] = array("label" => "An", "value" => 1);
		$arrayOptions[] = array("label" => "undefiniert", "value" => 2);
		$arrayExpansionPanel[] = array("type" => "Select", "name" => "Startoption_Pump", "caption" => "Startoption", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "ExpansionPanel", "caption" => "Pumpe(n)", "items" => $arrayExpansionPanel);
		
		// für die TCRT5000
		$arrayExpansionPanel = array();
		
		$arrayOptions = array();
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Angabe der GPIO-Nummer (Broadcom-Number) für IR-Sensoren"); 
		for ($i = 1; $i <= 5; $i++) {
			$arrayExpansionPanel[] = array("type" => "Select", "name" => "Pin_IRSensor_".$i, "caption" => "GPIO-Nr. für Sensor Nr. ".$i, "options" => $arrayOptions );
		}
		$arrayElements[] = array("type" => "ExpansionPanel", "caption" => "IR-Sensor(en)", "items" => $arrayExpansionPanel);
		
		// Rundumleuchten
		$arrayOptions = array();
		foreach($GPIO AS $Value => $Label) {
			$arrayOptions[] = array("label" => $Label, "value" => $Value);
		}
		$arrayExpansionPanel = array();
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Angabe der GPIO-Nummer (Broadcom-Number) der Rundumleuchten für die Steuerung"); 
		$arrayExpansionPanel[] = array("type" => "Select", "name" => "Pin_RotatingBeacon", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Angabe der Microsekunden bei 50 Hz"); 
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "RB_most_anti_clockwise", "caption" => "Max. Links (µs) - gegen den Uhrzeigersinn", "minimum" => 0); 
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "RB_midpoint", "caption" => "Mittelstellung (µs)", "minimum" => 0); 
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "RB_most_clockwise", "caption" => "Max. Rechts (µs) - im Uhrzeigersinn", "minimum" => 0);
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Angabe der GPIO-Nummer (Broadcom-Number) der Rundumleuchten Spannungsversorgung"); 
		$arrayExpansionPanel[] = array("type" => "Select", "name" => "Pin_PowerRotatingBeacon", "caption" => "GPIO-Nr.", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "ExpansionPanel", "caption" => "Rundumleuchte(n)", "items" => $arrayExpansionPanel);

		// Shot Auswahl
		$arrayExpansionPanel = array();
		$arrayExpansionPanel[] = array("type" => "Label", "caption" => "Shot-Auswahl");
		$arraySort = array();
		$arraySort = array("column" => "Name", "direction" => "ascending");
		$arrayEditName = array();
		$arrayEditName = array("type" => "ValidationTextBox");
		$arrayColumns = array();
		$arrayColumns[] = array("label" => "Name", "name" => "Name", "width" => "300px", "add" => "Wodka", "edit" => $arrayEditName);
		$arrayExpansionPanel[] = array("type" => "List", "name" => "PossibleDrinks", "rowCount" => 10, "add" => true, "delete" => true, "sort" => $arraySort, "columns" => $arrayColumns);
		$arrayElements[] = array("type" => "ExpansionPanel", "caption" => "Shot-Auswahl", "items" => $arrayExpansionPanel);
		
		// Sonstiges
		$arrayExpansionPanel = array();
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Produktivmodus", "value" => 0);
		$arrayOptions[] = array("label" => "Kalibrierungsmodus", "value" => 1);
		$arrayExpansionPanel[] = array("type" => "Select", "name" => "Modus", "caption" => "Modus", "options" => $arrayOptions );
		$arrayExpansionPanel[] = array("type" => "NumberSpinner", "name" => "ActivityWatch", "caption" => "Abschaltung (min)", "minimum" => 0, "maximum" => 30, "digits" => 0); 
		$arrayElements[] = array("type" => "ExpansionPanel", "caption" => "Sonstiges", "items" => $arrayExpansionPanel);
		
		$arrayActions = array();
		If (($this->ReadPropertyInteger("Pin_Servo") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$arrayActions = array(); 
			$arrayActions[] = array("type" => "Label", "label" => "Test Center"); 
			$arrayActions[] = array("type" => "TestCenter", "name" => "TestCenter");
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

		if (IPS_GetKernelRunlevel() == KR_READY) {
			// Webhook einrichten
			$this->RegisterHook("/hook/ShotGlassFillingMachine_".$this->InstanceID);
		}
		
		If (intval($this->GetBuffer("PreviousPin_Servo")) <> $this->ReadPropertyInteger("Pin_Servo")) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel Servo - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_Servo")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_Servo"), 0);
		}
		If (intval($this->GetBuffer("PreviousPin_RotatingBeacon")) <> $this->ReadPropertyInteger("Pin_RotatingBeacon")) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel Rundumlicht Steuerung - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_RotatingBeacon")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_RotatingBeacon"), 0);
		}
		If (intval($this->GetBuffer("PreviousPin_PowerRotatingBeacon")) <> $this->ReadPropertyInteger("Pin_PowerRotatingBeacon")) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel Rundumlicht Spannung - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_PowerRotatingBeacon")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_PowerRotatingBeacon"), 0);
		}
		If (intval($this->GetBuffer("PreviousPin_Pump_1")) <> $this->ReadPropertyInteger("Pin_Pump_1")) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel Pumpe 1 - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_Pump_1")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_Pump_1"), 0);
		}
		If (intval($this->GetBuffer("PreviousPin_Pump_2")) <> $this->ReadPropertyInteger("Pin_Pump_2")) {
			$this->SendDebug("ApplyChanges", "Pin-Wechsel Pumpe 2 - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_Pump_2")." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_Pump_2"), 0);
		}
		for ($i = 1; $i <= 5; $i++) {
			If (intval($this->GetBuffer("PreviousPin_IRSensor_".$i)) <> $this->ReadPropertyInteger("Pin_IRSensor_".$i)) {
				$this->SendDebug("ApplyChanges", "Pin-Wechsel IR-Sensor ".$i." - Vorheriger Pin: ".$this->GetBuffer("PreviousPin_IRSensor_".$i)." Jetziger Pin: ".$this->ReadPropertyInteger("Pin_IRSensor_".$i), 0);
			}
		}
		// Summary setzen
		$this->SetSummary("GPIO: ".$this->ReadPropertyInteger("Pin_Servo"));

		//ReceiveData-Filter setzen
        $Filter = '((.*"Function":"get_usedpin".*|.*"Pin":'.$this->ReadPropertyInteger("Pin_Servo").'.*)|
		  	(.*"Pin":'.$this->ReadPropertyInteger("Pin_RotatingBeacon").'.*|.*"Pin":'.$this->ReadPropertyInteger("Pin_PowerRotatingBeacon").'.*)|
		  	(.*"Pin":'.$this->ReadPropertyInteger("Pin_Pump_1").'.*|.*"Pin":'.$this->ReadPropertyInteger("Pin_Pump_2").'.*)|
		  	(.*"Pin":'.$this->ReadPropertyInteger("Pin_IRSensor_1").'.*|.*"Pin":'.$this->ReadPropertyInteger("Pin_IRSensor_2").'.*)|
		  	(.*"Pin":'.$this->ReadPropertyInteger("Pin_IRSensor_3").'.*|.*"Pin":'.$this->ReadPropertyInteger("Pin_IRSensor_4").'.*)|
		  	(.*"Pin":'.$this->ReadPropertyInteger("Pin_IRSensor_4").'.*) )';
		$this->SetReceiveDataFilter($Filter);

		// ((.*"Function":"get_usedpin".*|.*"Pin":1.*)|(.*"Pin":2.*|.*"Pin":3.*)|(.*"Pin":4.*|.*"Pin":5.*)|(.*"Pin":6.*|.*"Pin":7.*)|(.*"Pin":8.*|.*"Pin":9.*)|(.*"Pin":10.*) )
		
		$this->SetTimerInterval("Shutdown", 0);
		$this->SetTimerInterval("Pump_1", 0);
		$this->SetTimerInterval("Pump_2", 0);
		$this->SetTimerInterval("IR_Sensor", 0);
		$this->SetPossibleShotsAssociations();
		$this->SetValue("StateText", "Initialisierung...");
		$this->SetHTMLDisplay();
	
		If ((IPS_GetKernelRunlevel() == 10103) AND ($this->HasActiveParent() == true)) {	
			// Servo
			If (($this->ReadPropertyInteger("Pin_Servo") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				// Servo
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_Servo"), "PreviousPin" => $this->GetBuffer("PreviousPin_Servo"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_Servo", $this->ReadPropertyInteger("Pin_Servo"));
				If ($Result == true) {
					$this->Setup();
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
				}
			}
			
			// Rundumleuchte Steuerung
			If (($this->ReadPropertyInteger("Pin_RotatingBeacon") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
									  "Pin" => $this->ReadPropertyInteger("Pin_RotatingBeacon"), "PreviousPin" => $this->GetBuffer("PreviousPin_RotatingBeacon"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_RotatingBeacon", $this->ReadPropertyInteger("Pin_RotatingBeacon"));
				If ($Result == true) {
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
				}
			}	

			// Rundumleuchte Spannung
			If (($this->ReadPropertyInteger("Pin_PowerRotatingBeacon") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
								  "Pin" => $this->ReadPropertyInteger("Pin_PowerRotatingBeacon"), "PreviousPin" => $this->GetBuffer("PreviousPin_PowerRotatingBeacon"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_PowerRotatingBeacon", $this->ReadPropertyInteger("Pin_PowerRotatingBeacon"));
				If ($Result == true) {
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
					$this->SetPowerRotatingBeacon(false);
					IPS_Sleep(200);
					$this->SetPowerRotatingBeacon(true);
				}
			}	
			
			// Pumpe 1
			If (($this->ReadPropertyInteger("Pin_Pump_1") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
								  "Pin" => $this->ReadPropertyInteger("Pin_Pump_1"), "PreviousPin" => $this->GetBuffer("PreviousPin_Pump_1"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_Pump_1", $this->ReadPropertyInteger("Pin_Pump_1"));
				If ($Result == true) {
					If ($this->ReadPropertyInteger("Startoption_Pump") == 0) {
						$this->SetPumpState(1, false);
					}
					elseif ($this->ReadPropertyInteger("Startoption_Pump") == 1) {
						$this->SetPumpState(1, true);
					}
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
					// Initiale Abfrage des aktuellen Status
					$this->GetPumpState(1);
				}
			}
			
			// Pumpe 2
			If (($this->ReadPropertyInteger("Pin_Pump_2") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
								  "Pin" => $this->ReadPropertyInteger("Pin_Pump_2"), "PreviousPin" => $this->GetBuffer("PreviousPin_Pump_2"), "InstanceID" => $this->InstanceID, "Modus" => 1, "Notify" => false)));
				$this->SetBuffer("PreviousPin_Pump_2", $this->ReadPropertyInteger("Pin_Pump_2"));
				If ($Result == true) {
					If ($this->ReadPropertyInteger("Startoption_Pump") == 0) {
						$this->SetPumpState(2, false);
					}
					elseif ($this->ReadPropertyInteger("Startoption_Pump") == 1) {
						$this->SetPumpState(2, true);
					}
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
					// Initiale Abfrage des aktuellen Status
					$this->GetPumpState(2);
				}
			}

			
			// IR-Sensoren
			for ($i = 1; $i <= 5; $i++) {
				If (($this->ReadPropertyInteger("Pin_IRSensor_".$i) >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", 
										  "Pin" => $this->ReadPropertyInteger("Pin_IRSensor_".$i), "PreviousPin" => $this->GetBuffer("PreviousPin_IRSensor_".$i), "InstanceID" => $this->InstanceID, "Modus" => 0, "Notify" => true, "GlitchFilter" => 150, "Resistance" => 2)));
					$this->SetBuffer("PreviousPin_IRSensor_".$i, $this->ReadPropertyInteger("Pin_IRSensor_".$i));
					If ($Result == true) {
						If ($this->GetStatus() <> 102) {
							$this->SetStatus(102);
						}
					}	
				}
			}
			// Initiale Abfrage des aktuellen Status
			$this->GetIRSensor(); 
			$this->GetPowerRotatingBeacon();
			
			// Start-Button zurücksetzen
			$this->SetValue("Start", false);
			$this->SetValue("FillingActive", false);
			$this->SetValue("FillingStep", 0);
			$this->SetValue("DrinkChoise", 0);
			$this->SetDrink(0);
			$this->SetRotatingBeacon(0);
			$this->SetValue("AfterFilling", false);
			$this->SetValue("RotatingBeacon", 0);
			$this->SetTimerInterval("ActivityWatch", $this->ReadPropertyInteger("ActivityWatch") * 1000 * 60);

			// Modus
			If ($this->ReadPropertyInteger("Modus") == 0) {  //Produktivmodus
				$this->DisableAction("Servo");
				$this->DisableAction("ServoPosition");
				$this->DisableAction("State_Pump_1");
				$this->DisableAction("State_Pump_2");
				$this->DisableAction("RotatingBeacon");										   
			}
			elseif ($this->ReadPropertyInteger("Modus") == 1) { // Kalibriermodus
				$this->EnableAction("Servo");
				$this->EnableAction("ServoPosition");
				$this->EnableAction("State_Pump_1");
				$this->EnableAction("State_Pump_2");
				$this->EnableAction("RotatingBeacon");												   
			}
				
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
		}
	}

	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
		switch ($Message) {
			case IPS_KERNELSTARTED:
				// IPS_KERNELSTARTED
				$this->RegisterHook("/hook/ShotGlassFillingMachine_".$this->InstanceID);
				break;
			
		}
    }     

	protected function ProcessHookData() 
	{		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("ProcessHookData", "Ausfuehrung: ".$_SERVER['HOOK'], 0);
			switch ($_GET['Action']) {
				// Muss angepasst werden
				case 'Start':
			      		$this->SendDebug("ProcessHookData", "Start", 0);
						$this->SetValue("Start", true);
				    	$this->StartFilling();
			      		break;
			    case 'Stop':
			      		$this->SendDebug("ProcessHookData", "Stop", 0);
						$this->SetValue("Start", false);
						// Abbruch!
						$this->SetValue("StateText", "Abbruch...");
						$this->SetHTMLDisplay();
						$this->SetPumpState(1, false);
						$this->SetPumpState(2, false);
						$this->SetValue("FillingActive", false);
						$this->SetValue("FillingStep", 0);
						$this->SetValue("RotatingBeacon", 0);
						$this->SetServoPosition(0);
						$this->SetTimerInterval("Shutdown", 0);
						$this->SetTimerInterval("Pump_1", 0);
						$this->SetTimerInterval("Pump_2", 0);
						$this->SetTimerInterval("IR_Sensor", 0);
			      		break;
			    break;
			}
		}
	}       
	
	public function RequestAction($Ident, $Value) 
	{
  		// ActivityWatch zurücksetzen
		$this->SetTimerInterval("ActivityWatch", $this->ReadPropertyInteger("ActivityWatch") * 1000 * 60);
		If ($this->GetValue("RotatingBeacon") == 4) {
			$this->SetRotatingBeacon(0);
		}
		
		switch($Ident) {
	        case "Servo":
	            If ($this->ReadPropertyBoolean("Open") == true) {
		    		$this->SetServo($Value);
		    	}
	            break;
			case "RotatingBeacon":
	            If ($this->ReadPropertyBoolean("Open") == true) {
		    		$this->SetRotatingBeacon($Value);
		    	}
	            break;
			case "ServoPosition":
	            If ($this->ReadPropertyBoolean("Open") == true) {
		    		$this->SetServoPosition($Value);
		    	}
	            break;
			case "State_Pump_1":
	            If ($this->ReadPropertyBoolean("Open") == true) {
			    	$this->SetPumpState(1, $Value);
			    }
	            break;
			case "State_Pump_2":
	            If ($this->ReadPropertyBoolean("Open") == true) {
			    	$this->SetPumpState(2, $Value);
			    }
	            break;
			case "Start":
				If ($this->ReadPropertyBoolean("Open") == true) {
					$this->SetValue("Start", $Value);
			    	
					If ($Value == true) {
						$this->StartFilling();
					}
					else {
						// Abbruch!
						$this->SetValue("StateText", "Abbruch...");
						$this->SetHTMLDisplay();
						$this->SetPumpState(1, false);
						$this->SetPumpState(2, false);
						$this->SetValue("FillingActive", false);
						$this->SetValue("FillingStep", 0);
						$this->SetValue("RotatingBeacon", 0);
						$this->SetServoPosition(0);
						$this->SetTimerInterval("Shutdown", 0);
						$this->SetTimerInterval("Pump_1", 0);
						$this->SetTimerInterval("Pump_2", 0);
						$this->SetTimerInterval("IR_Sensor", 0);
					}
			    }
	            break;
			case "DrinkChoise":
	            If ($this->ReadPropertyBoolean("Open") == true) {
			    	$this->SetValue("DrinkChoise", $Value);
					$this->SetDrink($Value);
			    }
	            break;
			case "ShotGlassFill_1":
	            If ($this->ReadPropertyBoolean("Open") == true) {
			    	$this->SetValue($Ident, $Value);
			    }
	            break;
			case "ShotGlassFill_2":
	            If ($this->ReadPropertyBoolean("Open") == true) {
			    	$this->SetValue($Ident, $Value);
			    }
	            break;
			case "ShotGlassFill_3":
	            If ($this->ReadPropertyBoolean("Open") == true) {
			    	$this->SetValue($Ident, $Value);
			    }
	            break;
			case "ShotGlassFill_4":
	            If ($this->ReadPropertyBoolean("Open") == true) {
			    	$this->SetValue($Ident, $Value);
			    }
	            break;
			case "ShotGlassFill_5":
	            If ($this->ReadPropertyBoolean("Open") == true) {
			    	$this->SetValue($Ident, $Value);
			    }
	            break;
			case "PossibleShots_1":
	            If ($this->ReadPropertyBoolean("Open") == true) {
			    	$this->SetValue($Ident, $Value);
					$this->SetShotName($Ident, $Value);
			    }
	            break;
			case "PossibleShots_2":
	            If ($this->ReadPropertyBoolean("Open") == true) {
			    	$this->SetValue($Ident, $Value);
					$this->SetShotName($Ident, $Value);
			    }
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
			   case "get_usedpin":
				   	If ($this->ReadPropertyBoolean("Open") == true) {
						$this->ApplyChanges();
					}
					break;
			   case "status":
				   	If (($data->Pin == $this->ReadPropertyInteger("Pin_Servo")) 
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_RotatingBeacon"))
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_Pump_1")) 
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_Pump_2")) 
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_IRSensor_1"))
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_IRSensor_2"))
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_IRSensor_3"))
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_IRSensor_4"))
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_IRSensor_5"))) {
				   			$this->SetStatus($data->Status);
				   	}
				   	break;
				case "notify":
				If (($data->Pin == $this->ReadPropertyInteger("Pin_IRSensor_1"))
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_IRSensor_2"))
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_IRSensor_3"))
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_IRSensor_4"))
						OR ($data->Pin == $this->ReadPropertyInteger("Pin_IRSensor_5"))) {
			   		$this->SendDebug("ReceiveData", "Notify IR-Sensor ".$data->Pin, 0);
					// ActivityWatch zurücksetzen
					$this->SetTimerInterval("ActivityWatch", $this->ReadPropertyInteger("ActivityWatch") * 1000 * 60);
					$this->GetIRSensor();
					If ($this->GetValue("RotatingBeacon") == 4) {
						$this->SetRotatingBeacon(0);
					}
			   	}
			   	break;
			
			
	 	}
 	}
	// Beginn der Funktionen

	public function ActivityWatch()
	{
		$this->SendDebug("ActivityWatch", "Ausfuehrung", 0);
		$this->SetServoPosition(0);
		$this->SetRotatingBeacon(4);
	}
	
	private function SetDrink($Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			If ($Value == 0) {
				for ($i = 1; $i <= 5; $i++) {
					$this->SetValue("ShotGlassFill_".$i, false);
					$this->DisableAction("ShotGlassFill_".$i);
				}
			}
			elseif ($Value == 1) {
				for ($i = 1; $i <= 5; $i++) {
					$this->SetValue("ShotGlassFill_".$i, true);
					$this->DisableAction("ShotGlassFill_".$i);
				}
			}
			elseif ($Value == 2) {
				for ($i = 1; $i <= 5; $i++) {
					$this->EnableAction("ShotGlassFill_".$i);
				}
			}
		}
	}

	private function SetShotName(string $Ident, int $Value)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Number = substr($Ident, -1);
			$ShotName = GetValueFormatted($this->GetIDForIdent($Ident));

			// Pumpen
			IPS_SetName($this->GetIDForIdent("State_Pump_".$Number), "Status Pumpe ".$Number." (".$ShotName.")");
			// Text Befüllung wählen
			IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.ShotGlassFill", $Number - 1, "Getränk ".$Number." (".$ShotName.")", "Party", 0x000000);
			// Befüllart
			IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.PreShotGlassFill", $Number - 1, "Alle Gläser mit Getränk ".$Number." (".$ShotName.") füllen", "Party", 0x000000);
		}
	}
	
	public function SetServo(Int $Value)
	{
		If (($this->ReadPropertyInteger("Pin_Servo") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$this->SendDebug("SetServo", "Ausfuehrung", 0);
			$Left = $this->ReadPropertyInteger("most_anti_clockwise");
			$Right = $this->ReadPropertyInteger("most_clockwise");
			$Shutdown = $this->ReadPropertyInteger("Shutdown");
			
			$Value = min(100, max(0, $Value));
			
			$Value = intval(($Value * ($Right - $Left) / 100) + $Left);
			$this->SendDebug("SetServo", "Errechneter Zielwert: ".$Value, 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin_Servo"), "Value" => $Value)));
			If (!$Result) {
				$this->SendDebug("SetOutput", "Fehler beim Positionieren!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$Output = (($Value - $Left)/ ($Right - $Left)) * 100;
				$this->SetValue("Servo", $Output);
				$this->GetServo();
			}
			
			If ($Shutdown > 0) {
				$this->SetTimerInterval("Shutdown", $Shutdown);
			}
		}
	}

	public function SetServoPosition(Int $Value)
	{
		If (($this->ReadPropertyInteger("Pin_Servo") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$NewPosition = false;
			$this->SendDebug("SetServoPosition", "Ausfuehrung", 0);
			$Left = $this->ReadPropertyInteger("most_anti_clockwise");
			$Right = $this->ReadPropertyInteger("most_clockwise");
			$RestingPosition = $this->ReadPropertyInteger("RestingPosition");
			$Shutdown = $this->ReadPropertyInteger("Shutdown");
			
			$Value = min(5, max(0, $Value));
			$this->SetValue("ServoPosition", $Value);
			If ($Value > 0) {
				$Position = $this->ReadPropertyInteger("Position_".$Value);
			}
			If ($Value == 0) {
				$Value = intval(($RestingPosition * ($Right - $Left) / 100) + $Left);
			}
			else {
				$Value = intval(($Position * ($Right - $Left) / 100) + $Left);
			}

			$Value = min($Right, max($Value, $Left));
			
			$this->SendDebug("SetServoPosition", "Errechneter Zielwert: ".$Value, 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin_Servo"), "Value" => $Value)));
			If (!$Result) {
				$this->SendDebug("SetServoPosition", "Fehler beim Positionieren!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				$NewPosition = false;
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				
				$Output = (($Value - $Left)/ ($Right - $Left)) * 100;
				$this->SetValue("Servo", $Output);
				$NewPosition = True;
				$this->GetServo();
			}
			
			If ($Shutdown > 0) {
				$this->SetTimerInterval("Shutdown", $Shutdown);
			}
		}
	return $NewPosition;
	}

	public function GetServo()
	{
		If (($this->ReadPropertyInteger("Pin_Servo") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$this->SendDebug("GetServo", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_servo", "Pin" => $this->ReadPropertyInteger("Pin_Servo") )));
			If ($Result < 0) {
				$this->SendDebug("GetServo", "Fehler beim Lesen!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$this->SendDebug("GetServo", "Wert: ".$Result, 0);
				$Left = $this->ReadPropertyInteger("most_anti_clockwise");
				$Right = $this->ReadPropertyInteger("most_clockwise");
				$Output = (($Result - $Left)/ ($Right - $Left)) * 100;
				$this->SetValue("Servo", $Output);
			}
		}
	}   
	
	public function Shutdown()
	{
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin_Servo"), "Value" => 0)));
			If (!$Result) {
				$this->SendDebug("Shutdown", "Fehler beim Ausschalten!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
		$this->SetTimerInterval("Shutdown", 0);
	}

	public function RB_Switch()
	{ 
		If (($this->ReadPropertyInteger("Pin_RotatingBeacon") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$this->SendDebug("RB_Switch", "Ausfuehrung", 0);
			$Left = $this->ReadPropertyInteger("RB_most_anti_clockwise");
			$Right = $this->ReadPropertyInteger("RB_most_clockwise");
			$Midpoint = $this->ReadPropertyInteger("RB_midpoint");
			
			// Erster Schritt: 1000µs senden
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin_RotatingBeacon"), "Value" => $Left)));
			If (!$Result) {
				$this->SendDebug("RB_Switch", "Fehler beim Senden des ersten Befehls!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
			else {
				$this->SendDebug("RB_Switch", "Erster Befehl war erfolgreich!", 0);
				// Zweiter Schritt: 2000µs senden
				IPS_Sleep(70);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin_RotatingBeacon"), "Value" => $Right)));
				If (!$Result) {
					$this->SendDebug("Setup", "Fehler beim Senden des zweiten Befehls!", 0);
					If ($this->GetStatus() <> 202) {
						$this->SetStatus(202);
					}
				}
				else {
					$this->SendDebug("RB_Switch", "Zweiter Befehl war erfolgreich!", 0);
					// Dritter Schritt: 1500µs senden
					IPS_Sleep(70);
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin_RotatingBeacon"), "Value" => $Midpoint)));
					If (!$Result) {
						$this->SendDebug("Setup", "Fehler beim Senden des dritten Befehls!", 0);
						If ($this->GetStatus() <> 202) {
							$this->SetStatus(202);
						}
					}
					else {
						$this->SendDebug("RB_Switch", "Dritter Befehl war erfolgreich!", 0);
						// Ausschalten
						$this->SetStatus(102);
					}
				}
			}
		}
	}
	
	public function SetRotatingBeacon(Int $Value)
	{
		If (($this->ReadPropertyInteger("Pin_RotatingBeacon") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$this->SendDebug("SetRotatingBeacon", "Ausfuehrung", 0);
			$Value = min(4, max(0, $Value));

			// Rundumlicht auf einen definierten Startpunkt setzen
			If ($Value == 0) {
				// Rundumlicht auf einen definierten Startpunkt setzen (schnelles Rundumlicht)
				$this->SetPowerRotatingBeacon(false);
				IPS_Sleep(200);
				$this->SetPowerRotatingBeacon(true);
				IPS_Sleep(50);
			}
			elseif (($Value > 0) AND ($Value < 4)) {
				// Rundumlicht auf einen definierten Startpunkt setzen (schnelles Rundumlicht)
				$this->SetPowerRotatingBeacon(false);
				IPS_Sleep(200);
				$this->SetPowerRotatingBeacon(true);
				IPS_Sleep(50);
				// Jetzt eine entspechende Anzahl von Schaltbefehlen senden
				for ($i = 0; $i < $Value; $i++) {
					$this->SendDebug("SetRotatingBeacon", "Schleife: ".$i, 0);
					$this->RB_Switch();
				}
			}
			elseif ($Value == 4) {
				// Spannung ausschalten
				$this->SetPowerRotatingBeacon(false);
			}
			
			$this->SetValue("RotatingBeacon", $Value);

			// Steuerung ausschalten
			IPS_Sleep(100);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin_RotatingBeacon"), "Value" => 0)));
			If (!$Result) {
				$this->SendDebug("Setup", "Fehler beim Ausschalten!", 0);
			}
		}
	}

	public function SetPowerRotatingBeacon(Bool $Value)
	{
		If (($this->ReadPropertyInteger("Pin_PowerRotatingBeacon") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$Value = min(1, max(0, $Value));
			
			$this->SendDebug("SetPowerRotatingBeacon", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $this->ReadPropertyInteger("Pin_PowerRotatingBeacon"), "Value" => $Value )));
			$this->SendDebug("SetPowerRotatingBeacon", "Ergebnis: ".(int)$Result, 0);
			IF (!$Result) {
				$this->SendDebug("SetPowerRotatingBeacon", "Fehler beim Setzen des Status!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$this->SetValue("PowerRotatingBeacon", $Value);

				$this->GetPowerRotatingBeacon();
			}
		}
	}

	public function GetPowerRotatingBeacon()
	{
		If (($this->ReadPropertyInteger("Pin_PowerRotatingBeacon") >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$this->SendDebug("GetPowerRotatingBeacon", "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_value", "Pin" => $this->ReadPropertyInteger("Pin_PowerRotatingBeacon") )));
			If ($Result < 0) {
				$this->SendDebug("GetPowerRotatingBeacon", "Fehler beim Lesen des Status!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$this->SendDebug("GetPowerRotatingBeacon", "Ergebnis: ".(int)$Result, 0);
				$this->SetValue("PowerRotatingBeacon", $Result);
			}
		}
	}
	
	public function SetPumpState(int $Pump, Bool $Value)
	{
		If (($this->ReadPropertyInteger("Pin_Pump_".$Pump) >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$Shutdown = $this->ReadPropertyFloat("Time_Pump_".$Pump);
			$Value = min(1, max(0, $Value));
			
			$this->SendDebug("SetPumpState Pump ".$Pump, "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_value", "Pin" => $this->ReadPropertyInteger("Pin_Pump_".$Pump), "Value" => ($Value ^ $this->ReadPropertyBoolean("Invert_Pump")) )));
			$this->SendDebug("SetPumpState Pump ".$Pump, "Ergebnis: ".(int)$Result, 0);
			IF (!$Result) {
				$this->SendDebug("SetPumpState Pump ".$Pump, "Fehler beim Setzen des Status!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$this->SetValue("State_Pump_".$Pump, ($Value ^ $this->ReadPropertyBoolean("Invert_Pump")));

				If (($Shutdown > 0) AND ($Value == 1)) {
					$this->SetTimerInterval("Pump_".$Pump, $Shutdown * 1000);
				}
				$this->GetPumpState($Pump);
			}
		}
	}

	public function GetPumpState(int $Pump)
	{
		If (($this->ReadPropertyInteger("Pin_Pump_".$Pump) >= 0) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$this->SendDebug("GetPumpState Pump ".$Pump, "Ausfuehrung", 0);
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_value", "Pin" => $this->ReadPropertyInteger("Pin_Pump_".$Pump) )));
			If ($Result < 0) {
				$this->SendDebug("GetPumpState Pump ".$Pump, "Fehler beim Lesen des Status!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
				return;
			}
			else {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$this->SendDebug("GetPumpState Pump ".$Pump, "Ergebnis: ".(int)$Result, 0);
				$this->SetValue("State_Pump_".$Pump, ($Result ^ $this->ReadPropertyBoolean("Invert_Pump")));
			}
		}
	}

	public function StopPump_1()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("StopPump_1", "Ausfuehrung", 0);
			$this->SetPumpState(1, false);
			$this->SetTimerInterval("Pump_1", 0);
			$FillingStep = $this->GetValue("FillingStep");
			$this->SetValue("FillingStep", $FillingStep + 1);
			$this->FillingProcess();
		}
	}

	public function StopPump_2()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("StopPump_2", "Ausfuehrung", 0);
			$this->SetPumpState(2, false);
			$this->SetTimerInterval("Pump_2", 0);
			$FillingStep = $this->GetValue("FillingStep");
			$this->SetValue("FillingStep", $FillingStep + 1);
			$this->FillingProcess();
		}
	}

	public function GetIRSensor()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$StartButtonState = false;
			$AfterFilling = $this->GetValue("AfterFilling");
			for ($i = 1; $i <= 5; $i++) {
				If ($this->ReadPropertyInteger("Pin_IRSensor_".$i) >= 0) {
					$this->SendDebug("GetInput", "Ausfuehrung für Sensor ".$i, 0);
					$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_value", "Pin" => $this->ReadPropertyInteger("Pin_IRSensor_".$i) )));
					If ($Result < 0) {
						$this->SendDebug("GetIRSensor", "Fehler beim Lesen des Status für Sensor ".$i, 0);
						If ($this->GetStatus() <> 202) {
							$this->SetStatus(202);
						}
						$this->DisableAction("Start");
						return;
					}
					else {
						If ($this->GetStatus() <> 102) {
							$this->SetStatus(102);
						}
						$this->SendDebug("GetIRSensor", "Ergebnis: ".(int)$Result, 0);
						If ($this->GetValue("State_IRSensor_".$i) <> boolval($Result)) {
							If ($this->GetValue("State_IRSensor_".$i) <> boolval($Result)) {
								$this->SetValue("State_IRSensor_".$IRSensor, boolval($Result));
								$this->SetHTMLDisplay();
							}
						}
						If (boolval($Result) == false) {
							$StartButtonState = true;
						}
					}
				}
			}
			If (($StartButtonState == true) And ($AfterFilling == false)) {
				$this->EnableAction("Start");
				$this->SetValue("StateText", "Der Spass kann beginnen! Drücke Start zur Befüllung...");
				$this->SetHTMLDisplay();
			}
			elseif (($StartButtonState == true) And ($AfterFilling == true)) {
				$this->DisableAction("Start");
				$this->SetValue("StateText", "PROST!");
				$this->SetHTMLDisplay();
			}
			else {
				$this->DisableAction("Start");
				$this->SetValue("AfterFilling", false);
				$this->SetValue("StateText", "Es muss schon mindestens ein Glas bereitstehen!");
				$this->SetHTMLDisplay();
			}
		}
	}

	public function GetOneIRSensor(int $IRSensor)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IRSensor = min(5, max(1, $IRSensor));
			$IsGlass = true;
			If ($this->ReadPropertyInteger("Pin_IRSensor_".$IRSensor) >= 0) {
				$this->SendDebug("GetOneIRSensor", "Ausfuehrung für Sensor ".$IRSensor, 0);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=>"{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_value", "Pin" => $this->ReadPropertyInteger("Pin_IRSensor_".$IRSensor) )));
				If ($Result < 0) {
					$this->SendDebug("GetOneIRSensor", "Fehler beim Lesen des Status für Sensor ".$IRSensor, 0);
					If ($this->GetStatus() <> 202) {
						$this->SetStatus(202);
					}
					$IsGlass = true;
				}
				else {
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
					$this->SendDebug("GetOneIRSensor", "Ergebnis: ".(int)$Result, 0);
					If ($this->GetValue("State_IRSensor_".$i) <> boolval($Result)) {
						$this->SetValue("State_IRSensor_".$IRSensor, boolval($Result));
						$this->SetHTMLDisplay();
					}
					$IsGlass = !boolval($Result); 
				}
			}
		}
	return $IsGlass;
	}

	private function StartFilling()
	{
		If ($this->GetValue("FillingActive") == false) {
			$this->SendDebug("StartFilling", "Ausfuehrung", 0);
			$this->SetValue("FillingActive", true);
			$this->SetRotatingBeacon(0);
			// Schrittzähler zurücksetzen
			$this->SetValue("FillingStep", 1);
			// Alles in Ausgangsstellung bringen
			$this->SetServoPosition(0);
			$this->SetPumpState(1, false);
			$this->SetPumpState(2, false);
			// Jetzt die Befüllung starten
			$this->FillingProcess();
		}
	}

	private function FillingProcess()
	{
		$this->SendDebug("FillingProcess", "Ausfuehrung", 0);

		$FillingStep = $this->GetValue("FillingStep");
		$this->SetRotatingBeacon(0);
		
		If ($FillingStep <= 5) {
			$IsGlass = $this->GetOneIRSensor($FillingStep);
			
			If ($IsGlass == true) {
				// Fahre die Postion an
				$this->SendDebug("FillingProcess", "Auf Postion ".$FillingStep." ist ein Glas!", 0);
				$this->SetValue("StateText", "Position anfahren...");
				$this->SetHTMLDisplay();
				$NewPosition = $this->SetServoPosition($FillingStep);
				If ($NewPosition == true) {
					$this->SetRotatingBeacon(3);
					IPS_Sleep(1000); 
					// Ausgewählte Pumpe
					$SelectedDrink = $this->GetValue("ShotGlassFill_".$FillingStep) + 1;
					// Pumpe Starten
					$this->SetValue("StateText", "Jetzt geht es los...");
					$this->SetHTMLDisplay();
					$this->SetPumpState($SelectedDrink, true);
				}
			}
			else {
				$this->SendDebug("FillingProcess", "Auf Postion ".$FillingStep." ist kein Glas!", 0);
				// Schrittzähler um einen hochsetzen
				$this->SetValue("FillingStep", $FillingStep + 1);
				$this->FillingProcess();
			}
		}
		else {
			$this->SetValue("FillingActive", false);
			$this->SetValue("Start", false);
			// Schrittzähler zurücksetzen
			$this->SetValue("FillingStep", 0);
			$this->SetServoPosition(0);
			$this->SetValue("AfterFilling", true);
			$this->GetIRSensor();
			$this->SetRotatingBeacon(0);
		}	
	}
	
	private function Setup()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SendDebug("Setup", "Ausfuehrung", 0);
			$RestingPosition = $this->ReadPropertyInteger("RestingPosition");
			$Left = $this->ReadPropertyInteger("most_anti_clockwise");
			$Right = $this->ReadPropertyInteger("most_clockwise");
			$Value = intval(($RestingPosition * ($Right - $Left) / 100) + $Left);
			
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin_Servo"), "Value" => $Value)));
			If (!$Result) {
				$this->SendDebug("Setup", "Fehler beim Stellen der Ruheposition!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
			else {
				$this->SetStatus(102);
				$this->SetValue("Servo", $Value);
				$this->GetServo();
				IPS_Sleep(500);
				$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_servo", "Pin" => $this->ReadPropertyInteger("Pin_Servo"), "Value" => 0)));
				If (!$Result) {
					$this->SendDebug("Setup", "Fehler beim Ausschalten!", 0);
				}
			}
		}
	}

	private function SetPossibleShotsAssociations()
	{
		// Aktuelles Profil aufräumen
		$this->SendDebug("SetPossibleShotsAssociations", "Ausfuehrung", 0);
		$ProfilArray = Array();
		$ProfilArray = IPS_GetVariableProfile("ShotGlassFillingMachine.PossibleShots_".$this->InstanceID);

		// löschen der aktuellen Assoziationen
		foreach ($ProfilArray["Associations"] as $Association)
		{
			@IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.PossibleShots_".$this->InstanceID, $Association["Value"], "", "", -1);
		}
		
		$PossibleDrinksString = $this->ReadPropertyString("PossibleDrinks");
		$PossibleDrinks = json_decode($PossibleDrinksString);
		$this->SendDebug("SetPossibleShotsAssociations", serialize($PossibleDrinks), 0);

		IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.PossibleShots_".$this->InstanceID, -1, "unbenutzt", "Party", -1);
		$i = 0;
		foreach ($PossibleDrinks as $Key => $Value) {
			$this->SendDebug("SetPossibleShotsAssociations", $Value->Name." hinzugefuegt", 0);
			IPS_SetVariableProfileAssociation("ShotGlassFillingMachine.PossibleShots_".$this->InstanceID, $i, $Value->Name, "Party", -1);
			$i = $i + 1;
		}
		
	}

	public function SetHTMLDisplay()
	{
		$this->SendDebug("SetHTMLDisplay", "Ausfuehrung", 0);
		$StatusText = $this->GetValue("StateText");
		
		$StartImage = file_get_contents(__DIR__ . '/../imgs/StartButton.png');
		$StartImage = base64_encode($StartImage);

		$StopImage = file_get_contents(__DIR__ . '/../imgs/StopButton.png');
		$StopImage = base64_encode($StopImage);

		$ShotGlassImage = file_get_contents(__DIR__ . '/../imgs/ShotGlass.png');
		$ShotGlassImage = base64_encode($ShotGlassImage);

		for ($i = 1; $i <= 5; $i++) {
			$this->GetValue("State_IRSensor_".$i);
		}

		$HTMLText = '<style type="text/css">';
		$HTMLText .= '<link rel="stylesheet" href="./.../webfront.css">';
		$HTMLText .= "</style>";
		
		$HTMLText .= '<table style="height: 72px; width: 100%; border-collapse: collapse; border-style: hidden;" border="1">';
		$HTMLText .= '<tbody>';
		$HTMLText .= '<tr style="height: 18px; border-style: hidden;">';
		$HTMLText .= '<td style="width: 40%; height: 18px; text-align: left; vertical-align: middle; border-style: hidden;" colspan="2"><img src="data:image/png;base64,'.$StartImage.'" alt="Start" width="200" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'/hook/ShotGlassFillingMachine_'.$this->InstanceID.'?Action=Start\' })"></td>';
		$HTMLText .= '<td style="width: 20%; height: 18px; border-style: hidden;"></td>';
		$HTMLText .= '<td style="width: 40%; height: 18px; text-align: right; vertical-align: middle; border-style: hidden;" colspan="2"><img src="data:image/png;base64,'.$StopImage.'" alt="Stop" width="200" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'/hook/ShotGlassFillingMachine_'.$this->InstanceID.'?Action=Stop\' })"></td>';
		$HTMLText .= '</tr>';
		$HTMLText .= '<tr style="height: 18px;">';
		$HTMLText .= '<td style="width: 100%; height: 18px; border-style: hidden;" colspan="5"><h1>'.$StatusText.'</h1></td>';
		$HTMLText .= '</tr>';
		
		$HTMLText .= '<tr style="height: 18px;">';
		for ($i = 1; $i <= 5; $i++) {
			If ($this->GetValue("State_IRSensor_".$i) == false) {
				$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;"><img src="data:image/png;base64,'.$ShotGlassImage.'" alt="ShotGlass" width="200"></td>';
			}
			else {
				$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;"><h4>Vergebene Chance</h4></td>';
			}
		/*
		$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;"><h4>ImageGlass 2</h4></td>';
		$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;"><h4>ImageGlass 3</h4></td>';
		$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;"><h4>ImageGlass 4</h4></td>';
		$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;"><h4>ImageGlass 5</h4></td>';
		*/
		$HTMLText .= '</tr>';
		
		$HTMLText .= '<tr style="height: 18px;">';
		$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;">Fill 1</td>';
		$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;">Fill 2</td>';
		$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;">Fill 3</td>';
		$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;">Fill 4</td>';
		$HTMLText .= '<td style="width: 20%; height: 18px; text-align: center; vertical-align: middle; border-style: hidden;">Fill 5</td>';
		$HTMLText .= '</tr>';
		
		$HTMLText .= '</tbody>';
		$HTMLText .= '</table>';
		/*
		$HTMLText = '<style type="text/css">';
		$HTMLText .= '<link rel="stylesheet" href="./.../webfront.css">';
		$HTMLText .= "</style>";
		$HTMLText .= '<table style="height: 91px; width: 100%; border-collapse: collapse; border-style: hidden; float: left;" border="1">';
		$HTMLText .= '<tbody>';
		$HTMLText .= '<tr style="height: 18px;">';
	
		$HTMLText .= '<td style="width: 50%; height: 18px; border-style: hidden; text-align: left; vertical-align: middle;"><img src="data:image/png;base64,'.$StartImage.'" alt="Start" width="200" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'/hook/ShotGlassFillingMachine_'.$this->InstanceID.'?Action=Start\' })"></td>';
                                                                                                   
		$HTMLText .= '<td style="width: 50%; height: 18px; border-style: hidden; text-align: right; vertical-align: middle;"><img src="data:image/png;base64,'.$StopImage.'" alt="Stop" width="200" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'/hook/ShotGlassFillingMachine_'.$this->InstanceID.'?Action=Stop\' })"></td>';
		
		//$HTMLText .= '<td style="width: 50%; height: 18px; border-style: hidden; text-align: left; vertical-align: middle;"><img src="data:image/png;base64,'.$StartImage.'" alt="Start" width="200"/></td>';
		//$HTMLText .= '<td style="width: 50%; height: 18px; border-style: hidden; text-align: right; vertical-align: middle;"><img src="data:image/png;base64,'.$StopImage.'" alt="Stop" width="200"/></td>';

		$HTMLText .= '</tr>';
		$HTMLText .= '<tr style="height: 73px;">';
		$HTMLText .= '<td style="width: 100%; height: 73px;" colspan="2">';
		$HTMLText .= '<h1>'.$StatusText.'</h1>';
		$HTMLText .= '</td>';
		$HTMLText .= '</tr>';
		$HTMLText .= '</tbody>';
		$HTMLText .= '</table>';
		*/
		$this->SetValue("StateTextHTML", $HTMLText);
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

	private function RegisterProfileBoolean($Name, $Icon)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 0);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 0)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);      
	}

	private function RegisterHook($WebHook)
    {
        	$ids = IPS_GetInstanceListByModuleID('{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}');
        	if (count($ids) > 0) {
            		$hooks = json_decode(IPS_GetProperty($ids[0], 'Hooks'), true);
            		$found = false;
            		foreach ($hooks as $index => $hook) {
                		if ($hook['Hook'] == $WebHook) {
                    			if ($hook['TargetID'] == $this->InstanceID) {
                        			return;
                    			}
                    			$hooks[$index]['TargetID'] = $this->InstanceID;
                    			$found = true;
                		}
            		}
            		if (!$found) {
                		$hooks[] = ['Hook' => $WebHook, 'TargetID' => $this->InstanceID];
            		}
            		IPS_SetProperty($ids[0], 'Hooks', json_encode($hooks));
            		IPS_ApplyChanges($ids[0]);
		}
    }
	
	protected function HasActiveParent()
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
