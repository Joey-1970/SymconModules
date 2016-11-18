<?
    // Klassendefinition
    class IPS2Enigma extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
           	$this->RegisterPropertyBoolean("Open", 0);
	    	$this->RegisterPropertyString("IPAddress", "127.0.0.1");
        }
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
		// Diese Zeile nicht löschen
		parent::ApplyChanges();
		//Status-Variablen anlegen
		$this->RegisterVariableBoolean("Status", "Status", "~Switch", 10);
		$this->EnableAction("Status");
            	


        }
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Status":
	            $this->Set_Status($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValue($this->GetIDForIdent($Ident), $Value);
	            break;
	        default:
	            throw new Exception("Invalid Ident");
	    }
	}
	

	// Beginn der Funktionen
	
	// Schaltet den gewaehlten Pin
	public function Set_Status(Bool $Value)
	{
	
	return;
	}
	
	// Toggelt den Status
	public function Toggle_Status()
	{
	
	return;
	}
}
?>
