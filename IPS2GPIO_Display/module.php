<?
    // Klassendefinition
    class IPS2GPIO_Display extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
            $this->RegisterPropertyInteger("Baud", 3);
            $this->RegisterPropertyString("ConnectionString", "/dev/ttyAMA0");
            $this->RegisterPropertyBoolean("DateTime", true);
            $this->RegisterPropertyInteger("Brightness", 100);
            $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
	        // Diese Zeile nicht löschen
	      	parent::ApplyChanges();
	        //Connect to available splitter or create a new on
	        $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	   
		//Status-Variablen anlegen
		$this->RegisterVariableInteger("Brightness", "Brightness", "~Intensity.100", 10);
           	$this->EnableAction("Brightness");

		$this->RegisterVariableInteger("Baud", "Baud", "", 110);
		$this->DisableAction("Baud");
		IPS_SetHidden($this->GetIDForIdent("Baud"), true);
		
         	// den Handle für dieses Gerät ermitteln
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_serial", "Baud" => 9600, "Device" => $this->ReadPropertyString('ConnectionString') )));
		
		$this->Setup();

  
        }

	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
		case "Brightness":
	            $this->SetBrightness($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
	            break;
	        case "BrightnessDefault":
	            $this->SetBrightnessDefault($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValueInteger($this->GetIDForIdent($Ident), $Value);
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
			 case "status":
			   	If (($data->Pin == 14) OR ($data->Pin == 15)) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
			case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
	 	}
	return;
 	}
	// Beginn der Funktionen
	
	private function Send($Message)
	{
		$Message = utf8_encode($Message."\xFF\xFF\xFF");
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Command" => $Message)));

	return;
	}
	
	private function Setup($Message)
	{
		// Uhrzeit und Datum aktualisieren
		If ($this->ReadPropertyBoolean("DateTime") == true) {
			$this->SetDateTime();
		}
		// Default-Wert der Helligkeit setzen
		$this->SetBrightnessDefault($this->ReadPropertyInteger("Brightness"))
	return;
	}
	public function SetBrightness($Value)
	{
		$Value = min(100, max(0, $Value));
		$this->Send("dim=".$Value); 
	return;
	}
	
	private function SetBrightnessDefault($Value)
	{
		$Value = min(100, max(0, $Value));
		$this->Send("dims=".$Value);
	return;
	}
	
	private function SetDateTime()
	{
		date_default_timezone_set("Europe/Berlin");
		$timestamp = time();
		$this->Send("rtc0=".date("Y",$timestamp));
		$this->Send("rtc1=".date("m",$timestamp));
		$this->Send("rtc2=".date("d",$timestamp));
		$this->Send("rtc3=".date("H",$timestamp));
		$this->Send("rtc4=".date("i",$timestamp));
		$this->Send("rtc5=".date("s",$timestamp));
	return;	
	}
	

	
}
?>
