<?
class IPS2SingleRoomControl extends IPSModule
{
    	// ToDo:
	// - Variable Tagesgruppen
	// - Farbauswahl
	// - Boost-Funktion
	// - Abwesenheit
	// - Selbstkonfiguration K-Faktoren	
	
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		
		$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyInteger("ActualTemperatureID", 0);
		$this->RegisterPropertyFloat("KP", 0.0);
		$this->RegisterPropertyFloat("KD", 0.0);
		$this->RegisterPropertyFloat("KI", 0.0);
		$this->RegisterPropertyInteger("Messzyklus", 120);
		$this->RegisterTimer("Messzyklus", 0, 'IPS2SRC_Measurement($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("PositionElementMin", 0);
		$this->RegisterPropertyInteger("PositionElementMax", 100);
		$this->RegisterTimer("PWM", 0, 'IPS2SRC_PWM($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("MinSwitchTime", 5);
		$this->RegisterPropertyInteger("PWM_ActuatorID", 0);
		$this->RegisterPropertyInteger("AutomaticFallback", 120);
		$this->RegisterTimer("AutomaticFallback", 0, 'IPS2SRC_AutomaticFallback($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("WindowStatusID", 0);
		$this->RegisterPropertyInteger("DayStatusID", 0);
		$this->RegisterPropertyInteger("PresenceStatusID", 0);
		$this->RegisterPropertyInteger("TemperatureReduction", 3);
		$this->RegisterPropertyFloat("Temperatur_1", 16.0);
		$this->RegisterPropertyFloat("Temperatur_2", 17.0);
		$this->RegisterPropertyFloat("Temperatur_3", 18.0);
		$this->RegisterPropertyFloat("Temperatur_4", 19.0);
		$this->RegisterPropertyFloat("Temperatur_5", 19.5);
		$this->RegisterPropertyFloat("Temperatur_6", 20.0);
		$this->RegisterPropertyFloat("Temperatur_7", 20.5);
		$this->RegisterPropertyFloat("Temperatur_8", 21.0);
		$this->RegisterPropertyInteger("ColorTemperatur_1", 0xA9F5F2);
		$this->RegisterPropertyInteger("ColorTemperatur_2", 0xF78181);
		$this->RegisterPropertyInteger("ColorTemperatur_3", 0xFE2E2E);
		$this->RegisterPropertyInteger("ColorTemperatur_4", 0xDF0101);
		$this->RegisterPropertyInteger("ColorTemperatur_5", 0x610B0B);
		$this->RegisterPropertyInteger("ColorTemperatur_6", 0x2A0A0A);
		$this->RegisterPropertyInteger("ColorTemperatur_7", 0x80FF00);
		$this->RegisterPropertyInteger("ColorTemperatur_8", 0x298A08);
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		$this->RegisterVariableBoolean("OperatingMode", "Betriebsart Automatik", "~Switch", 30);
		$this->EnableAction("OperatingMode");
		$this->RegisterVariableFloat("ActualTemperature", "Ist-Temperatur", "~Temperature", 10);
		$this->DisableAction("ActualTemperature");
		$this->RegisterVariableFloat("SetpointTemperature", "Soll-Temperatur", "~Temperature.Room", 20);
		If ($this->GetIDForIdent("OperatingMode") == true) {
			$this->DisableAction("SetpointTemperature");
		}
		else {
			$this->EnableAction("SetpointTemperature");
		}
		$this->RegisterVariableInteger("PositionElement", "Stellelement", "~Intensity.100", 40);
		$this->DisableAction("PositionElement");
		$this->RegisterVariableBoolean("PWM_Mode", "PWM-Status", "~Switch", 40);
		$this->EnableAction("OperatingMode");
		$this->RegisterVariableFloat("SumDeviation", "Summe Regelabweichungen", "~Temperature", 50);
		$this->DisableAction("SumDeviation");
		IPS_SetHidden($this->GetIDForIdent("SumDeviation"), true);
		$this->RegisterVariableFloat("ActualDeviation", "Aktuelle Regelabweichung", "~Temperature", 60);
		$this->DisableAction("ActualDeviation");
		IPS_SetHidden($this->GetIDForIdent("ActualDeviation"), true);
		
		// Anlegen der Daten für den Wochenplan
		$this->RegisterEvent("Wochenplan", "IPS2SRC_Event_".$this->InstanceID, 2, $this->InstanceID, 150);
		for ($i = 0; $i <= 6; $i++) {
			IPS_SetEventScheduleGroup($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), $i, pow(2, $i));
		}
		for ($i = 1; $i <= 8; $i++) {
			$Value = $this->ReadPropertyFloat("Temperatur_".$i);
			$this->RegisterScheduleAction($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), $i - 1, $Value."C°", $this->ReadPropertyInteger("ColorTemperatur_".$i), "IPS2SRC_SetTemperature(\$_IPS['TARGET'], ".$Value.");");
		}
		
		// Registrierung für Nachrichten des Wochenplans
		$this->RegisterMessage($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), 10821);
		$this->RegisterMessage($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), 10822);
		$this->RegisterMessage($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), 10823);
		// Registrierung für die Änderung der Ist-Temperatur
		If ($this->ReadPropertyInteger("ActualTemperatureID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("ActualTemperatureID"), 10603);
		}
		// Registrierung für die Änderung des Fensterstatus
		If ($this->ReadPropertyInteger("WindowStatusID") > 0) {
			$this->RegisterMessage($this->ReadPropertyInteger("WindowStatusID"), 10603);
		}
		
		// Zeitstempel für die Differenz der Messungen
		$this->SetBuffer("LastTrigger", time() - 60);
		
		$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->Measurement();
			$this->SetStatus(102);
		}
		else {
			$this->SetStatus(104);
		}
		
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "SetpointTemperature":
	            	//Neuen Wert in die Statusvariable schreiben
	            	SetValueFloat($this->GetIDForIdent($Ident), $Value);
			$this->Measurement();
	            	break;
	        case "OperatingMode":
	            	$this->SetOperatingMode($Value);
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
        IPS_LogMessage("IPS2SingleRoomControl", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data, true));
		switch ($Message) {
			case 10821:
				IPS_LogMessage("IPS2SingleRoomControl", "Wochenplanänderung 1!");
				break;
			case 10822:
				IPS_LogMessage("IPS2SingleRoomControl", "Wochenplanänderung 2!");
				break;
			case 10823:
				IPS_LogMessage("IPS2SingleRoomControl", "Wochenplanänderung 3!");
				break;	
			case 10603:
				//IPS_LogMessage("IPS2SingleRoomControl", "Temperatur- oder Fensterstatusänderung");
				// Änderung der Ist-Temperatur, die Temperatur aus dem angegebenen Sensor in das Modul kopieren
				If ($SenderID == $this->ReadPropertyInteger("ActualTemperatureID")) {
					SetValueFloat($this->GetIDForIdent("ActualTemperature"), GetValueFloat($this->ReadPropertyInteger("ActualTemperatureID")) );
				}
				// Änderung des Fensterstatus
				elseif ($SenderID == $this->ReadPropertyInteger("WindowStatusID")) {
					$this->Measurement();
				}
				break;
		}
    	}
		
	public function Measurement()
	{
		// die Daten aus den Angaben zum Fensterstatus aufbereiten
		If ($this->ReadPropertyInteger("WindowStatusID") == 0) {
			// Es ist keine Variablen angegeben
			$WindowStatus = true;
		}
		elseif ($this->ReadPropertyInteger("WindowStatusID") > 0) {
			// wenn eine Variable angegeben ist, wird der Zustand des Fensters in die Hilfsvariable geschrieben
			$WindowStatus = GetValueBoolean($this->ReadPropertyInteger("WindowStatusID"));
		}
		
		// die Daten aus den Angaben zum Feiertag/Urlaub aufbereiten
		If ($this->ReadPropertyInteger("DayStatusID") == 0) {
			// Es ist keine Variablen angegeben
			$DayStatus = false;
		}
		elseif ($this->ReadPropertyInteger("DayStatusID") > 0) {
			// wenn eine Variable angegeben ist, wird der Zustand des Fensters in die Hilfsvariable geschrieben
			$DayStatus = GetValueBoolean($this->ReadPropertyInteger("DayStatusID"));
		}
		
		// die Daten aus den Angaben zur Abwesenheit aufbereiten
		If ($this->ReadPropertyInteger("PresenceStatusID") == 0) {
			// Es ist keine Variablen angegeben
			$PresenceStatus = true;
		}
		elseif ($this->ReadPropertyInteger("PresenceStatusID") > 0) {
			// wenn eine Variable angegeben ist, wird der Zustand des Fensters in die Hilfsvariable geschrieben
			$PresenceStatus = GetValueBoolean($this->ReadPropertyInteger("PresenceStatusID"));
		}
		
		// wenn der Mode auf Automatik ist, den aktuellen Soll-Wert aus dem Wochenplan lesen
		If (GetValueBoolean($this->GetIDForIdent("OperatingMode")) == true) { 	
			If ($DayStatus == false) {
				$ActionID = $this->GetEventActionID($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), 2, pow(2, date("N") - 1), date("H"), date("i"));
			}
			else {
				// Feiertage/Urlaub wird wie ein Sonntag behandelt
				$ActionID = $this->GetEventActionID($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), 2, 64, date("H"), date("i"));
			}	
			
			If (!$ActionID) {
				IPS_LogMessage("IPS2SingleRoomControl", "Fehler bei der Ermittlung der Wochenplan-Solltemperatur!"); 	
			}
			else {
				$ActionIDTemperature = $this->ReadPropertyFloat("Temperatur_".$ActionID);
				If ($PresenceStatus == true) {
					SetValueFloat($this->GetIDForIdent("SetpointTemperature"), $ActionIDTemperature);
				}
				else {
					SetValueFloat($this->GetIDForIdent("SetpointTemperature"), $ActionIDTemperature - abs($this->ReadPropertyInteger("TemperatureReduction")));
				}
			}
		}
		
		
		
		//Ta = Rechenschrittweite (Abtastzeit)
		$Ta = Round( (time() - (int)$this->GetBuffer("LastTrigger")) / 60, 0);
		//Schutzmechanismus falls Skript innerhalb einer Minute zweimal ausgeführt wird
		$Ta = Max($Ta, 1);
		
		// Die vorherige Regelabweichung ermitteln
		$ealt = GetValueFloat($this->GetIDForIdent("ActualDeviation")); 
		
		//Aktuelle Regelabweichung bestimmen
		$e = GetValueFloat($this->GetIDForIdent("SetpointTemperature")) - GetValueFloat($this->GetIDForIdent("ActualTemperature"));
		
		// Vorherige Regelabweichung durch jetzige ersetzen 
		SetValueFloat($this->GetIDForIdent("ActualDeviation"), $e);
		
		//Die Summe aller vorherigen Regelabweichungen bestimmen
		If (((GetValueInteger($this->GetIDForIdent("PositionElement")) == 0) and ($e < 0)) OR ((GetValueInteger($this->GetIDForIdent("PositionElement")) == 100) and ($e > 0))) {
			// Die Negativ-Werte sollen nicht weiter aufsummiert werden, wenn der Stellmotor schon auf 0 ist bzw. Die Positiv-Werte sollen nicht weiter aufsummiert werden, wenn der Stellmotor schon auf 100 ist
			$esum = GetValueFloat($this->GetIDForIdent("SumDeviation"));
		}
		else {
			$esum = GetValueFloat($this->GetIDForIdent("SumDeviation")) + $e;
		   	SetValueFloat($this->GetIDForIdent("SumDeviation"), $esum);
		}
			    
		// nur wenn das Fenster geschlossen ist, wird eine Berechnung durchgeführt
		If ($WindowStatus == true) {
			$PositionElement = $this->PID($this->ReadPropertyFloat("KP"), $this->ReadPropertyFloat("KI"), $this->ReadPropertyFloat("KD"), $e, $esum, $ealt, $Ta);
		}
		else {
			$PositionElement = 0;
		}
		SetValueInteger($this->GetIDForIdent("PositionElement"), $PositionElement);
		
		// Minimale Schaltänderungszeit in Sekunden
		//$PWMzyklus = time() - (int)$this->GetBuffer("LastTrigger");
		$PWMzyklus = $Ta * 60;
		$PWMmin = $this->ReadPropertyInteger("MinSwitchTime"); 
		
		// Errechnen der On-Zeit
		$PWMontime = $PWMzyklus / 100 * $PositionElement;
		// Schutzmechnismus damit die Minimum-Einschaltzeit eingehalten wird
		If (($PWMontime > 0) and ($PWMontime < $PWMmin)) {
		   $PWMontime = $PWMmin;
		   }
	   	// Schutzmechnismus damit die Minimum-Ausschaltzeit eingehalten wird
		If (($PWMzyklus - $PWMontime) < $PWMmin) {
		   $PWMontime = $PWMzyklus;
		   }
		// Schreiben und setzen
		If ($PWMontime > 0) {
			SetValueBoolean($this->GetIDForIdent("PWM_Mode"), true);
			If ($this->ReadPropertyInteger("PWM_ActuatorID") > 0) {
				SetValueBoolean($this->ReadPropertyInteger("PWM_ActuatorID"), true);
			}
			$this->SetTimerInterval("PWM", (int)$PWMontime * 1000);			
		}
		else {
			SetValueBoolean($this->GetIDForIdent("PWM_Mode"), false);
			If ($this->ReadPropertyInteger("PWM_ActuatorID") > 0) {
				SetValueBoolean($this->ReadPropertyInteger("PWM_ActuatorID"), false);
			}
		}
		
		
		
		
		$this->SetBuffer("LastTrigger", time());
	}
	
	public function PWM()
	{
		SetValueBoolean($this->GetIDForIdent("PWM_Mode"), false);
		If ($this->ReadPropertyInteger("PWM_ActuatorID") > 0) {
			SetValueBoolean($this->ReadPropertyInteger("PWM_ActuatorID"), false);
		}
		$this->SetTimerInterval("PWM", 0);
	}
	
	// Berechnet nächsten Stellwert der Aktoren
	private function PID($Kp, $Ki, $Kd, $e, $esum, $ealt, $Ta)
	{
		//e = aktuelle Reglerabweichung -> Soll-Ist
		//ealt = vorherige Reglerabweichung
		//esum = die Summe aller bisherigen Abweichungen e
		//y = Antwort -> muss im Bereich zwischen 0-100 sein
		//esum = esum + e
		//y = Kp * e + Ki * Ta * esum + Kd * (e – ealt)/Ta
		//ealt = e
		//Kp = Verstärkungsfaktor Proportionalregler
		//Ki = Verstärkungsfaktor Integralregler
		//Kd = Verstärkungsfaktor Differenzialregler

		// Die Berechnung des neuen Regelwertes
		$y = ($Kp * $e + $Ki * $Ta * $esum + $Kd * ($e - $ealt) / $Ta);

	   	// Dieses ist eine Begrenzung des Stellventils, da die Heizkörper sonst sehr heiß werden
		$y = min(max($y, $this->ReadPropertyInteger("PositionElementMin")), $this->ReadPropertyInteger("PositionElementMax"));
		$Stellwert = $y;

	return $Stellwert;
	}
	
	public function SetTemperature(float $Value)
	{
		If ($this->GetIDForIdent("OperatingMode") == true) {
			SetValueFloat($this->GetIDForIdent("SetpointTemperature"), $Value);
			$this->Measurement();
		}
	}
	
	public function SetOperatingMode(bool $Value)
	{
		SetValueBoolean($this->GetIDForIdent("OperatingMode"),  $Value);
		If ($Value == true) {
			$this->DisableAction("SetpointTemperature");
			$this->SetTimerInterval("AutomaticFallback", 0);
			// Aktuellen Wert des Wochenplans auslesen
			$ActionID = $this->GetEventActionID($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), 2, pow(2, date("N") - 1), date("H"), date("i"));
			If (!$ActionID) {
				IPS_LogMessage("IPS2SingleRoomControl", "Fehler bei der Ermittlung der Wochenplan-Solltemperatur!"); 	
			}
			else {	
				$ActionIDTemperature = $this->ReadPropertyFloat("Temperatur_".$ActionID);
				SetValueFloat($this->GetIDForIdent("SetpointTemperature"), $ActionIDTemperature);
			}
			
		}
		else {
			// Timer für automatischen Fallback starten
			$this->EnableAction("SetpointTemperature");
			$this->SetTimerInterval("AutomaticFallback", ($this->ReadPropertyInteger("AutomaticFallback") * 1000 * 60));
		}
	}
	
	public function AutomaticFallback()
	{
		SetValueBoolean($this->GetIDForIdent("OperatingMode"),  true);
		$this->SetTimerInterval("AutomaticFallback", 0);
		// Aktuellen Wert des Wochenplans auslesen
		$ActionID = $this->GetEventActionID($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), 2, pow(2, date("N") - 1), date("H"), date("i"));
		If (!$ActionID) {
			IPS_LogMessage("IPS2SingleRoomControl", "Fehler bei der Ermittlung der Wochenplan-Solltemperatur!"); 	
		}
		else {
			$$ActionIDTemperature = $this->ReadPropertyFloat("Temperatur_".($ActionID + 1));
			SetValueFloat($this->GetIDForIdent("SetpointTemperature"), $$ActionIDTemperature);
		}
		
	}
	
	private function GetEventActionID($EventID, $EventType, $Days, $Hour, $Minute)
	{
		$EventValue = IPS_GetEvent($EventID);
		$Result = false;
		// Prüfen um welche Art von Event es sich handelt
		If ($EventValue['EventType'] == $EventType) {
			$ScheduleGroups = $EventValue['ScheduleGroups'];
			// Anzahl der ScheduleGroups ermitteln	
			$ScheduleGroupsCount = count($ScheduleGroups);

			If ($ScheduleGroupsCount > 0) {
				for ($i = 0; $i <= $ScheduleGroupsCount - 1; $i++) {	
					If ($ScheduleGroups[$i]['Days'] == $Days) {
						$ScheduleGroupDay = $ScheduleGroups[$i];
						$ScheduleGroupsDayCount = count($ScheduleGroupDay['Points']);

						If ($ScheduleGroupsDayCount == 0) {
							IPS_LogMessage("IPS2SingleRoomControl", "Keine Schaltpunkte definiert!"); 	
						}
						elseif ($ScheduleGroupsDayCount == 1) {
							$Result = $ScheduleGroupDay['Points'][0]['ActionID'];
						}
						elseif ($ScheduleGroupsDayCount > 1) {
							for ($j = 0; $j <= $ScheduleGroupsDayCount - 1; $j++) {
								$TimestampScheduleStart = mktime($ScheduleGroupDay['Points'][$j]['Start']['Hour'], $ScheduleGroupDay['Points'][$j]['Start']['Minute'], 0, 0, 0, 0);
								If ($j < $ScheduleGroupsDayCount - 1) {
									$TimestampScheduleEnd = mktime($ScheduleGroupDay['Points'][$j + 1]['Start']['Hour'], $ScheduleGroupDay['Points'][$j + 1]['Start']['Minute'], 0, 0, 0, 0);
								}
								else {
									$TimestampScheduleEnd = mktime(24, 0, 0, 0, 0, 0);
								}
								$Timestamp = mktime($Hour, $Minute, 0, 0, 0, 0);

								If (($Timestamp >= $TimestampScheduleStart) AND ($Timestamp < $TimestampScheduleEnd)) {
									$Result = ($ScheduleGroupDay['Points'][$j]['ActionID']) + 1;
								} 
							}
						}
					}
				}
			}
			else {
				IPS_LogMessage("IPS2SingleRoomControl", "Es sind keine Aktionen eingerichtet!");
			}
		  }
	return $Result;
	}
	
	private function RegisterEvent($Name, $Ident, $Typ, $Parent, $Position)
	{
		if (!IPS_EventExists($this->GetIDForIdent($Ident)))
	        {
	            	$EventID = IPS_CreateEvent($Typ);
			IPS_SetParent($EventID, $Parent);
			IPS_SetIdent($EventID, $Ident);
			IPS_SetName($EventID, $Name);
			IPS_SetPosition($EventID, $Position);
			IPS_SetEventActive($EventID, true);  
			// Initiale Befüllung
	        }
	}
	
	private function RegisterScheduleAction($EventID, $ActionID, $Name, $Color, $Script)
	{
		IPS_SetEventScheduleAction($EventID, $ActionID, $Name, $Color, $Script);
	}
}

?>
