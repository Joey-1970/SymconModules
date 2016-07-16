<?
class IPS2GPIO_IO extends IPSModule
{
	  public function __construct($InstanceID) {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
         }

	  public function Create() 
	  {
	    // Diese Zeile nicht entfernen
	    parent::Create();
	    
	    // Modul-Eigenschaftserstellung
	    $this->RegisterPropertyBoolean("Open", 0);
	    $this->RegisterPropertyString("IPAddress", "127.0.0.1");
	    $this->ConnectParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
	  }
  
	  public function ApplyChanges()
	  {
		//Never delete this line!
		parent::ApplyChanges();
		$this->SetStatus(101);
		$this->RegisterVariableString("PinPossible", "PinPossible");
		$this->RegisterVariableString("PinUsed", "PinUsed");
		$this->RegisterVariableString("PinNotify", "PinNotify");
		$this->RegisterVariableInteger("Handle", "Handle");
		
		$ParentID = $this->GetParentID();
		If ($ParentID > 0) {
			If (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('IPAddress')) {
	                	IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('IPAddress'));
			}
			If (IPS_GetProperty($ParentID, 'Port') <> 8888) {
	                	IPS_SetProperty($ParentID, 'Port', 8888);
			}
		}
		
		If($this->ConnectionTest()) {
			// Hardware feststellen
			$this->CommandClientSocket(pack("LLLL", 17, 0, 0, 0));
			// Notify Starten
			SetValueInteger($this->GetIDForIdent("Handle"), 0);
			$this->ClientSocket(pack("LLLL", 99, 0, 0, 0));
			
			$this->Get_PinUpdate();
			$this->SetStatus(102);
		}
	  }

	  public function ForwardData($JSONString) 
	  {
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	
	 	switch ($data->Function) {
		    case "set_mode":
		        If ($data->Pin >= 0) {
		        	$this->Set_Mode($data->Pin, $data->Modus);
		        }
		        break;
		    case "set_PWM_dutycycle":
		    	If ($data->Pin >= 0) {
		        	$this->Set_Intensity($data->Pin, $data->Value);
		    	}
		        break;
		    case "set_PWM_dutycycle_RGB":
		    	If (($data->Pin_R >= 0) AND ($data->Pin_G >= 0) AND ($data->Pin_B >= 0)) {
		        	$this->Set_Intensity_RGB($data->Pin_R, $data->Value_R, $data->Pin_G, $data->Value_G, $data->Pin_B, $data->Value_B);
		    	}
		        break;
		    case "set_glitchfilter":
		    	If ($data->Pin >= 0) {
		        	$this->Set_GlitchFilter($data->Pin, $data->Value);
		    	}
		        break;
		    case "set_notifypin":
		    	If ($data->Pin >= 0) {
			        // Erstellt ein Array für alle Pins für die die Notifikation erforderlich ist 
			        $PinNotify = unserialize(GetValueString($this->GetIDForIdent("PinNotify")));
			        $PinNotify[] = $data->Pin;
				SetValueString($this->GetIDForIdent("PinNotify"), serialize($PinNotify));
		    	}
		        break;
		   case "set_usedpin":
		   	If ($data->Pin >= 0) {
				// Prüfen, ob der gewählte GPIO bei dem Modell überhaupt vorhanden ist
				$PinPossible = unserialize(GetValueString($this->GetIDForIdent("PinPossible")));
				if (in_array($data->Pin, $PinPossible)) {
			    		IPS_LogMessage("GPIO Pin: ","Gewählter Pin ist bei diesem Modell verfügbar");
			    		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>102)));
				}
				else {
					IPS_LogMessage("GPIO Pin: ","Gewählter Pin ist bei diesem Modell nicht verfügbar!");
					$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>201)));
				}
				// Erstellt ein Array für alle Pins die genutzt werden 	
				$PinUsed = unserialize(GetValueString($this->GetIDForIdent("PinUsed")));
			        If (is_array($PinUsed)) {	
					// Prüft, ob der ausgeählte Pin schon einmal genutzt wird
				        If (in_array($data->Pin, $PinUsed)) {
				        	IPS_LogMessage("GPIO Pin", "Achtung: Pin ".$data->Pin." wird mehrfach genutzt!");
				        	$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>200)));
				        }
			        }
			        $PinUsed[] = $data->Pin;
				SetValueString($this->GetIDForIdent("PinUsed"), serialize($PinUsed));
		   	}
		        break;
		   case "get_pinupdate":
		   	$this->Get_PinUpdate();
		   	break;
		   case "get_freepin":
		   	$PinPossible = unserialize(GetValueString($this->GetIDForIdent("PinPossible")));
		   	$PinUsed = unserialize(GetValueString($this->GetIDForIdent("PinUsed")));
		   	$PinFreeArray = array_diff_assoc($PinPossible, $PinUsed);
		   	If (is_array($PinFreeArray)) {
		   		IPS_LogMessage("GPIO Pin", "Pin ".$PinFreeArray[0]." ist noch ungenutzt");
			        $this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"freepin", "Pin"=>$PinFreeArray[0])));
		   	}
		   	else {
		   		IPS_LogMessage("GPIO Pin", "Achtung: Kein ungenutzter Pin gefunden");	
		   	}
		   	break;
		}
	    
	    return;
	  }
	
	 public function ReceiveData($JSONString) {
 	    	$RDlen[0] = array(32);	
	        $RDlen[1] = array(24, 36, 48, 60, 72, 84, 96, 108, 120, 132, 144, 156, 168, 180);
 	    	// Empfangene Daten vom I/O
	    	$Data = json_decode($JSONString);
	    	$Message = utf8_decode($Data->Buffer);
	    	$MessageLen = strlen($Message);
	    	IPS_LogMessage("GPIO ReceiveData", "Länge: ".$MessageLen);
	 	// Wenn die Datenlänge wie erwartet eintrifft
	 	If (($MessageLen == 12) or ($MessageLen == 16)) {
	 		$this->ClientResponse($Message);
	 	}
	    	elseif (in_array($MessageLen, $RDlen[0])) {
	    		// wenn es sich um mehrere Standarddatensätze handelt
	    		$DataArray = str_split($Message, 16);
	    		IPS_LogMessage("GPIO ReceiveData", "Überlänge: ".Count($DataArray)." Command-Datensätze");
	    		for ($i = 0; $i < Count($DataArray); $i++) {
    				$this->ClientResponse($DataArray[$i]);
			}
	    	}
		elseif (in_array($MessageLen, $RDlen[1])) {
	    		// wenn es sich um mehrere Notifikationen handelt, nur die erste Änderung übermitteln
	    		$DataArray = str_split($Message, 12);
	    		//$Message = substr($Message, 0, 12);
	    		IPS_LogMessage("GPIO ReceiveData", "Überlänge: ".Count($DataArray)." Notify-Datensätze");
	    		for ($i = 0; $i < Count($DataArray); $i++) {
    				$this->ClientResponse($DataArray[$i]);
			}
		}
	 	else {
	 		IPS_LogMessage("GPIO ReceiveData", "Überlänge: Datensätze nicht differenzierbar!");
	 	}
	 	
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
  
	// Aktualisierung der genutzten Pins und der Notifikation
	private function Get_PinUpdate()
	{
		// Pins ermitteln für die ein Notify erforderlich ist
		SetValueString($this->GetIDForIdent("PinNotify"), "");
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_notifypin")));
		// Notify setzen	
		If (GetValueInteger($this->GetIDForIdent("Handle")) > 0) {
	           	$this->CommandClientSocket(pack("LLLL", 19, GetValueInteger($this->GetIDForIdent("Handle")), $this->CalcBitmask(), 0));
		}
		// Pins ermitteln die genutzt werden
		SetValueString($this->GetIDForIdent("PinUsed"), "");
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_usedpin")));
	return;
	}
	
	// Setzt den gewaehlten Pin in den geforderten Modus
	private function Set_Mode($Pin, $Modus)
	{		
		$this->CommandClientSocket(pack("LLLL", 0, $Pin, $Modus, 0));
		IPS_LogMessage("SetMode Parameter : ",$Pin." , ".$Modus);  
	return;
	}

	// Setzt den gewaehlten Pin in den geforderten Modus
	private function Set_GlitchFilter($Pin, $Value)
	{		
		$this->CommandClientSocket(pack("LLLL", 97, $Pin, $Value, 0));
		IPS_LogMessage("SetGlitchFilter Parameter : ",$Pin." , ".$Value);  
	return;
	}
	
	// Dimmt den gewaehlten Pin
	private function Set_Intensity($Pin, $Value)
	{
		$this->CommandClientSocket(pack("LLLL", 5, $Pin, $Value, 0));
		IPS_LogMessage("Set Intensity : ",$Pin." , ".$Value);  
	return;
	}
	
	// Setzt die Farbe der RGB-LED
	private function Set_Intensity_RGB($Pin_R, $Value_R, $Pin_G, $Value_G, $Pin_B, $Value_B)
	{
		$this->CommandClientSocket(pack("LLLL", 5, $Pin_R, $Value_R, 0).pack("LLLL", 5, $Pin_G, $Value_G, 0).pack("LLLL", 5, $Pin_B, $Value_B, 0));
		IPS_LogMessage("Set Intensity RGB : ",$Pin_R." , ".$Value_R." ".$Pin_G." , ".$Value_G." ".$Pin_B." , ".$Value_B);  
	return;
	}
			
	// Schaltet den gewaehlten Pin
	private function Set_Status($Pin, $Value)
	{
		$this->CommandClientSocket(pack("LLLL", 5, $Pin, $Value, 0));
	return;
	}

	private function ClientSocket($message)
	{
		$res = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => utf8_encode($message))));  
	return;	
	}
	
	private function CommandClientSocket($message)
	{
		// Socket erstellen
		if(!($sock = socket_create(AF_INET, SOCK_STREAM, 0))) {
			$errorcode = socket_last_error();
		    	$errormsg = socket_strerror($errorcode);
		    	IPS_LogMessage("GPIO Socket: ", "Fehler beim Erstellen ".[$errorcode]." ".$errormsg);
		    	return;
		}
		
		// Verbindung aufbauen
		if(!socket_connect($sock, $this->ReadPropertyString("IPAddress"), 8888)) {
			$errorcode = socket_last_error();
		    	$errormsg = socket_strerror($errorcode);
			IPS_LogMessage("GPIO Socket: ", "Fehler beim Verbindungsaufbaus ".[$errorcode]." ".$errormsg);
			return;
		}
		
		// Message senden
		if( ! socket_send ($sock, $message, strlen($message), 0))
		{
			$errorcode = socket_last_error();
		    	$errormsg = socket_strerror($errorcode);
			IPS_LogMessage("GPIO Socket: ", "Fehler beim beim Senden ".[$errorcode]." ".$errormsg);
			return;
		}
		
		//Now receive reply from server
		if(socket_recv ($sock, $buf, 16, MSG_WAITALL ) === FALSE) {
		    	$errorcode = socket_last_error();
		    	$errormsg = socket_strerror($errorcode);
			IPS_LogMessage("GPIO Socket: ", "Fehler beim beim Empfangen ".[$errorcode]." ".$errormsg);
			return;
		}
		
		$DataArray = str_split($buf, 16);
	    	IPS_LogMessage("GPIO ReceiveData", Count($DataArray)." Command-Datensätze");
	    	for ($i = 0; $i < Count($DataArray); $i++) {
    			$this->ClientResponse($DataArray[$i]);
		}
		
	return;	
	}
	
	private function ClientResponse($Message)
	{
		If (strlen($Message) == 16) {
			$response = unpack("L*", $Message);
			switch($response[1]) {
			        case "5":
			        	IPS_LogMessage("GPIO PWM: ","erfolgreich gesendet");
			        	break;
			        case "17":
			            	IPS_LogMessage("GPIO Hardwareermittlung: ","gestartet");
			            	$Model[0] = array(2, 3);
			            	$Model[1] = array(4, 5, 6, 13, 14, 15);
			            	$Model[2] = array(16);
			            	$Typ[0] = array(0, 1, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 21, 22, 23, 24, 25);	
	           			$Typ[1] = array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27);
	           			$Typ[2] = array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);
	           			
	           			if (in_array($response[4], $Model[0])) {
	    					SetValueString($this->GetIDForIdent("PinPossible"), serialize($Typ[0]));
	    					IPS_LogMessage("GPIO Hardwareermittlung: ","Raspberry Pi Typ 0");
					}
					else if (in_array($response[4], $Model[1])) {
						SetValueString($this->GetIDForIdent("PinPossible"), serialize($Typ[1]));
						IPS_LogMessage("GPIO Hardwareermittlung: ","Raspberry Pi Typ 1");
					}
					else if ($response[4] >= 16) {
						SetValueString($this->GetIDForIdent("PinPossible"), serialize($Typ[2]));
						IPS_LogMessage("GPIO Hardwareermittlung: ","Raspberry Pi Typ 2");
					}
					else
						IPS_LogMessage("GPIO Hardwareermittlung: ","nicht erfolgreich!");
					break;
	           		case "19":
	           			IPS_LogMessage("GPIO Notify: ","gestartet");
			            	break;
	           		case "21":
	           			IPS_LogMessage("GPIO Notify: ","gestoppt");
			            	break;
			        case "97":
	           			IPS_LogMessage("GPIO GlitchFilter: ","gesetzt");
			            	break;
			        case "99":
	           			If ($response[4] > 0 ) {
	           				IPS_LogMessage("GPIO Handle: ",$response[4]);
	           				SetValueInteger($this->GetIDForIdent("Handle"), $response[4]);
	           				
	           				$this->ClientSocket(pack("LLLL", 19, $response[4], $this->CalcBitmask(), 0));
	           			}
	           			break;
			    }
		}
		elseif (strlen($Message) == 12) {
			
			$response = unpack("L*", $Message);
					
			IPS_LogMessage("GPIO Notify: ","Meldung: ".count($response)." ".$response[1]." ".$response[2]." ".$response[3]);
		
			$PinNotify = unserialize(GetValueString($this->GetIDForIdent("PinNotify")));

			for ($i = 0; $i < Count($PinNotify); $i++) {
    				$Bitvalue = boolval($response[3]&(1<<$PinNotify[$i]));
    				IPS_LogMessage("GPIO Notify: ","Pin ".$PinNotify[$i]." Value ->".$Bitvalue);
    				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"notify", "Pin" => $PinNotify[$i], "Value"=> $Bitvalue)));
			}
		}
		else {
			IPS_LogMessage("GPIO Notify: ","Meldung konnte nicht dekodiert werden!");		
		}
	return;
	}
	
	private function CalcBitmask()
	{
		$PinNotify = unserialize(GetValueString($this->GetIDForIdent("PinNotify")));

		$Bitmask = 0;
		for ($i = 0; $i < Count($PinNotify); $i++) {
    			$Bitmask = $Bitmask + pow(2, $PinNotify[$i]);
		}
	return $Bitmask;	
	}
	
	private function ConnectionTest()
	{
	      $result = false;
	      If (Sys_Ping($this->ReadPropertyString("IPAddress"), 2000)) {
			IPS_LogMessage("GPIO Netzanbindung: ","Raspberry Pi gefunden");
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("GPIO Netzanbindung: ","Port ist geschlossen!");
					$this->SetStatus(104);
	   			}
	   			else {
	   				fclose($status);
					IPS_LogMessage("GPIO Netzanbindung: ","Port ist geöffnet");
					$result = true;
					$this->SetStatus(102);
	   			}
		}
		else {
			IPS_LogMessage("GPIO Netzanbindung: ","Raspberry Pi nicht gefunden!");
			$this->SetStatus(104);
		}
	return $result;
	}
	
	private function GetParentID()
	{
		$ParentID = (IPS_GetInstance($this->InstanceID)['ConnectionID']);  
	return $ParentID;
	}
  
}
?>
