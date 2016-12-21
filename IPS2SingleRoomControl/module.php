<?
class IPS2SingleRoomControl extends IPSModule
{
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
		$this->RegisterPropertyInteger("PositionElementMax", 100);
		$this->RegisterTimer("PWM", 0, 'IPS2SRC_PWM($_IPS["TARGET"]);');
		$this->RegisterPropertyInteger("MinSwitchTime", 5);
		$this->RegisterPropertyInteger("PWM_ActuatorID", 0);
		$this->RegisterPropertyFloat("Temperatur_1", 16.0);
		$this->RegisterPropertyFloat("Temperatur_2", 17.0);
		$this->RegisterPropertyFloat("Temperatur_3", 18.0);
		$this->RegisterPropertyFloat("Temperatur_4", 19.0);
		$this->RegisterPropertyFloat("Temperatur_5", 19.5);
		$this->RegisterPropertyFloat("Temperatur_6", 20.0);
		$this->RegisterPropertyFloat("Temperatur_7", 20.5);
		$this->RegisterPropertyFloat("Temperatur_8", 21.0);
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		$this->RegisterVariableFloat("ActualTemperature", "Ist-Temperatur", "~Temperature", 10);
		$this->DisableAction("ActualTemperature");
		$this->RegisterVariableFloat("SetpointTemperature", "Soll-Temperatur", "~Temperature", 20);
		$this->EnableAction("SetpointTemperature");
		$this->RegisterVariableBoolean("OperatingMode", "Betriebsart Automatik", "~Switch", 30);
		$this->EnableAction("OperatingMode");
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
		IPS_SetEventScheduleGroup($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), 0, 127); 
		for ($i = 1; $i < 8; $i++) {
			$Value = $this->ReadPropertyFloat("Temperatur_".$i);
			$this->RegisterScheduleAction($this->GetIDForIdent("IPS2SRC_Event_".$this->InstanceID), $i - 1, $Value."C°", 0xFF0000 + (2 + $i), "IPS2SRC_SetTemperature(\$_IPS['TARGET'], ".$Value.");");
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
	            	$this->Measurement();
	            	//Neuen Wert in die Statusvariable schreiben
	            	SetValueFloat($this->GetIDForIdent($Ident), $Value);
	            	break;
	        case "OperatingMode":
	            	$this->Measurement();
	            	//Neuen Wert in die Statusvariable schreiben
	            	SetValueBoolean($this->GetIDForIdent($Ident), $Value);
			If ($Value == true) {
				$this->DisableAction("SetpointTemperature");
			}
			else {
				$this->EnableAction("SetpointTemperature");
			}
	            	break;
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	public function Measurement()
	{
		SetValueFloat($this->GetIDForIdent("ActualTemperature"), GetValueFloat($this->ReadPropertyInteger("ActualTemperatureID")) );
		
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
			    
		$PositionElement = $this->PID($this->ReadPropertyFloat("KP"), $this->ReadPropertyFloat("KI"), $this->ReadPropertyFloat("KD"), $e, $esum, $ealt, $Ta);
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
		If ($PWMontime> 0) {
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
		$y = min(max($y, 0), $this->ReadPropertyInteger("PositionElementMax"));
		$Stellwert = $y;

	return $Stellwert;
	}
	
	public function SetTemperature($Value)
	{
	
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
