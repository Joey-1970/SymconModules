<?
class IPS2Redundancy extends IPSModule
{
    	// ToDo:
	// - Variable Tagesgruppen
	// - Farbauswahl
	// - Selbstkonfiguration K-Faktoren	
	
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		
		$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyInteger("ActualTemperatureID", 0);
		$this->RegisterPropertyFloat("KP", 0.0);
		
	}
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		// Logging setzen
				
		// Registrierung für Nachrichten
	
		$this->SetTimerInterval("Messzyklus", ($this->ReadPropertyInteger("Messzyklus") * 1000));
		
		If ($this->ReadPropertyBoolean("Open") == true) {
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
	            	
	            	break;
	        
	        default:
	            throw new Exception("Invalid Ident");
	    	}
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case 10803:
				
				break;
			
		}
    	}
		



	

}
?>
