<?
class IPS2GPIO_IO extends IPSModule
{
	  // Der Konstruktor des Moduls
	  // Überschreibt den Standard Kontruktor von IPS
	  public function __construct($InstanceID) 
	  {
	      // Diese Zeile nicht löschen
	      parent::__construct($InstanceID);
	 	
	
	            // Selbsterstellter Code
	  }
  
	  public function Create() 
	  {
	    // Diese Zeile nicht entfernen
	    parent::Create();
	    
	    // Modul-Eigenschaftserstellung
	    $this->RegisterPropertyBoolean("Open", 0);
	    $this->RegisterPropertyString("IPAddress", "127.0.0.1");
	    
	
	  }
  
	  public function ApplyChanges()
	  {
		//Never delete this line!
		parent::ApplyChanges();
		    
		$this->RegisterVariableString("PinPossible", "PinPossible");
		$this->RegisterVariableString("PinUsed", "PinUsed");
		
		If($this->ConnectionTest()) {
			$this->ClientSocket(pack("LLLL", 17, 0, 0, 0));
		}
		// Hardware feststellen
           	
	  }
  	  


	  public function ForwardData($JSONString) 
	  {
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	IPS_LogMessage("ForwardData", utf8_decode($data->Function));
	 	switch ($data->Function) {
		    case "set_mode":
		        $this->Set_Mode($data->Pin, $data->Modus);
		        break;
		    case "set_PWM_dutycycle":
		        $this->Set_Intensity($data->Pin, $data->Value);
		        break;
		    case "set_PWM_dutycycle_RGB":
		        $this->Set_Intensity_RGB($data->Pin_R, $data->Value_R, $data->Pin_G, $data->Value_G, $data->Pin_B, $data->Value_B);
		        break;
		    case "pin_possible":
		        $this->PinPossible($data->Pin);
		        break;
		}
	    	// Hier würde man den Buffer im Normalfall verarbeiten
	    	// z.B. CRC prüfen, in Einzelteile zerlegen
	 	$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Buffer" => $data->Function)));
	    	// Weiterleiten zur I/O Instanz
	    	
	 
	    	// Weiterverarbeiten und durchreichen
	    return;
	  }

  
	  public function RequestAction($Ident, $Value) 
	  {
		    switch($Ident) {
		        case "Open":
		            If ($Value = True) {
		            		$this->SetStatus(101);
		            		$this->ConnectionTest();
		            	}
		 	   else {
		 	   		$this->SetStatus(104);
		 	   	}
		            //Neuen Wert in die Statusvariable schreiben
		            SetValue($this->GetIDForIdent($Ident), $Value);
		            break;
		        default:
		            throw new Exception("Invalid Ident");
		    }
	 
	   }
  
	// Setzt den gewaehlten Pin in den geforderten Modus
	private function Set_Mode($Pin, $Modus)
	{		
		$this->ClientSocket(pack("LLLL", 0, $Pin, $Modus, 0));
	return;
	}
	
	// Dimmt den gewaehlten Pin
	private function Set_Intensity($Pin, $Value)
	{
		$this->ClientSocket(pack("LLLL", 5, $Pin, $Value, 0));
	return;
	}
	
	// Setzt die Farbe der RGB-LED
	private function Set_Intensity_RGB($Pin_R, $Value_R, $Pin_G, $Value_G, $Pin_B, $Value_B)
	{
		$this->ClientSocket(pack("LLLL", 5, $Pin_R, $Value_R, 0).pack("LLLL", 5, $Pin_G, $Value_G, 0).pack("LLLL", 5, $Pin_B, $Value_B, 0));
	return;
	}
			
	// Schaltet den gewaehlten Pin
	private function Set_Status($Pin, $Value)
	{
		$this->ClientSocket(pack("LLLL", 5, $Pin, $Value, 0));
	return;
	}
	
	private function PinPossible($Pin)
	{
		$PinPossible = unserialize(GetValueString($this->GetIDForIdent("PinPossible")));
		if (in_array($Pin, $a)) {
    			$result = true;
		}
		else {
			$result = false;
			Echo "Pin ist an diesem Modell nicht verfügbar!";
		}
		
	return $result;
	}
	
	private function ClientSocket($message)
	{
		// create Socket 
		if(!($sock = socket_create(AF_INET, SOCK_STREAM, 0))) { 
		    	$errorcode = socket_last_error(); 
		    	$errormsg = socket_strerror($errorcode); 
		    	die("Couldn't create socket: [$errorcode] $errormsg \n"); 
		} 
				
		// establish Connection
		if(!socket_connect($sock , $this->ReadPropertyString("IPAddress") , 8888)) { 
		    	$errorcode = socket_last_error(); 
		    	$errormsg = socket_strerror($errorcode); 
			die("Could not connect: [$errorcode] $errormsg \n"); 
		} 
			
		//Send the message to the server 
		if( ! socket_send ( $sock , $message , strlen($message) , 0)) { 
		    	$errorcode = socket_last_error(); 
		    	$errormsg = socket_strerror($errorcode); 
			die("Could not send data: [$errorcode] $errormsg \n"); 
		} 
		
		//Now receive reply from server 
		if(socket_recv ( $sock , $buf , 16, MSG_WAITALL ) === FALSE) { 
		    	$errorcode = socket_last_error(); 
		    	$errormsg = socket_strerror($errorcode);  
		    	die("Could not receive data: [$errorcode] $errormsg \n"); 
		} 
		
		// received message 
		$this->ClientResponse($buf);
	return;	
	}
	
	private function ClientResponse($Message)
	{
		$response = unpack("L*", $Message);
		switch($response[1]) {
		        case "17":
		            	$Model[0] = array(2, 3);
		            	$Model[1] = array(4, 5, 6, 13, 14, 15);
		            	$Model[2] = array(16);
		            	$Typ[0] = array(0, 1, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 21, 22, 23, 24, 25);	
           			$Typ[1] = array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27);
           			$Typ[2] = array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);
           			
           			if (in_array($response[4], $Model[0])) {
    					SetValueString($this->GetIDForIdent("PinPossible"), serialize($Typ[0]));
				}
				else if (in_array($response[4], $Model[1])) {
					SetValueString($this->GetIDForIdent("PinPossible"), serialize($Typ[1]));
				}
				else if ($response[4] >= 16) {
					SetValueString($this->GetIDForIdent("PinPossible"), serialize($Typ[2]));
				}
           			
           			
		            break;
		        
		    }
	return;
	}
	
	private function ConnectionTest()
	{
	      $result = false;
	      If (Sys_Ping($this->ReadPropertyString("IPAddress"), 2000)) {
			// PC erreichbar
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
				if (!$status) {
					// Port geschlossen
					$this->SetStatus(104);
	   			}
	   			else {
	   				fclose($status);
					// Port offen
					$result = true;
					$this->SetStatus(102);
	   			}
		}
		else {
			// PC nicht erreichbar
			$this->SetStatus(104);
		}
	return $result;
	}
  

}
?>
