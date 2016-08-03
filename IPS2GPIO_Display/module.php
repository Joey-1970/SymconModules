<?
    // Klassendefinition
    class IPS2GPIO_Display extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
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
		
		$this->RegisterVariableInteger("Handle", "Handle", "", 110);
		$this->DisableAction("Handle");
		IPS_SetHidden($this->GetIDForIdent("Handle"), true);
		$this->RegisterVariableInteger("Baud", "Baud", "", 110);
		$this->DisableAction("Baud");
		IPS_SetHidden($this->GetIDForIdent("Baud"), true);

             	If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
             		// Handle löschen
             		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "close_handle_serial", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")))));
             		SetValueInteger($this->GetIDForIdent("Handle"), -1);
             	}
            	// den Handle für dieses Gerät ermitteln
            	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "get_handle_serial", "Baud" => 9600, "Device" => "/dev/ttyAMA0")));


  
        }

	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {

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
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => 14, "Modus" => "W")));
			   	$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_usedpin", "Pin" => 15, "Modus" => "R")));
			   	break;
			 case "status":
			   	If (($data->Pin == 14) OR ($data->Pin == 15)) {
			   		$this->SetStatus($data->Status);
			   	}
			   	break;
			case "set_serial_handle":
			   	SetValueInteger($this->GetIDForIdent("Handle"), $data->Handle);
			   	break;
			case "freepin":
			   	// Funktion zum erstellen dynamischer Pulldown-Menüs
			   	break;
	 	}
	return;
 	}
	// Beginn der Funktionen
	
	public function Brightness($Value)
	{
		$Value = min(100, max(0, $Value));
		$Message = utf8_encode("dims=".$Value).chr(255).chr(255).chr(255); 
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Command" => $Message)));

	return;
	}
	
	public function Set_Time()
	{
		date_default_timezone_set("Europe/Berlin");
		$timestamp = time();
		$Message = "rtc3=".date("H",$timestamp)."\xFF\xFF\xFF";
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Command" => $Message)));
		$Message = "rtc4=".date("i",$timestamp)."\xFF\xFF\xFF"; 
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Command" => $Message)));
		$Message = "rtc5=".date("s",$timestamp)."\xFF\xFF\xFF";
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Command" => $Message)));
	return;	
	}
	
	public function Set_Date()
	{
		date_default_timezone_set("Europe/Berlin");
		$timestamp = time();
		$Message = "rtc0=".date("Y",$timestamp)."\xFF\xFF\xFF";
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Command" => $Message)));
		$Message = "rtc1=".date("m",$timestamp)."\xFF\xFF\xFF"; 
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Command" => $Message)));
		$Message = "rtc2=".date("d",$timestamp)."\xFF\xFF\xFF";
		$this->SendDataToParent(json_encode(Array("DataID"=> "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "write_bytes_serial", "Handle" => GetValueInteger($this->GetIDForIdent("Handle")), "Command" => $Message)));
	return;	
	}
	
}
?>
