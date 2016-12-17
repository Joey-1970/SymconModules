<?
// Klassendefinition
class IPS2SingleRoomControl extends IPSModule 
{
    	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyInteger("ActualTemperatureID", 0);
		$this->RegisterPropertyInteger("KP", 0);
		$this->RegisterPropertyInteger("KD", 0);
		$this->RegisterPropertyInteger("KI", 0);
		$this->RegisterPropertyInteger("Messzyklus", 120);
		$this->RegisterTimer("Messzyklus", 0, 'IPS2SRC_Measurement($_IPS["TARGET"]);');
		
        return;
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
		
		$this->SetBuffer("LastTrigger", time() - 60);
		
		$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->Measurement();
			$this->SetStatus(102);
		}
		else {
			$this->SetStatus(104);
		}
		
	return;
	}
	
	public function Measurement()
	{
		
		//Ta = Rechenschrittweite (Abtastzeit)
		$Ta = Round((time() - $this->GetBuffer("LastTrigger") / 60, 0);
		//Schutzmechanismus falls Skript innerhalb einer Minute zweimal ausgeführt wird
		$Ta = Max($Ta, 1);
		
		//Aktuelle Regelabweichung bestimmen
		//$e = $this->ReadPropertyFloat("SetpointTemperature") - GetValueFloat($this->ReadPropertyInteger("ActualTemperatureID"));
		
		//Die Summe aller vorherigen Regelabweichungen bestimmen
		
			    
			    
		$this->SetBuffer("LastTrigger", time());
	return;
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

	   	// Dieses ist eine Begrenzung des Stellventils auf 50%, da die Heizkörper sonst sehr heiß werden
		$y = min(max($y, 0), 50);
		$Stellwert = $y;

	return $Stellwert;
	}


}
?>   
