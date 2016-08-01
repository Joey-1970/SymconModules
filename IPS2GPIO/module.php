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
		$this->RegisterVariableString("PinPossible", "PinPossible");
		$this->DisableAction("PinPossible");
		IPS_SetHidden($this->GetIDForIdent("PinPossible"), true);
		$this->RegisterVariableString("PinUsed", "PinUsed");
		$this->DisableAction("PinUsed");
		IPS_SetHidden($this->GetIDForIdent("PinUsed"), true);
		$this->RegisterVariableString("PinNotify", "PinNotify");
		$this->DisableAction("PinNotify");
		IPS_SetHidden($this->GetIDForIdent("PinNotify"), true);
		$this->RegisterVariableInteger("Handle", "Handle");
		$this->DisableAction("Handle");
		IPS_SetHidden($this->GetIDForIdent("PinI2C"), true);
		$this->RegisterVariableString("PinI2C", "PinI2C");
		$this->DisableAction("PinI2C");
		IPS_SetHidden($this->GetIDForIdent("Handle"), true);
		$this->RegisterVariableBoolean("I2C_Used", "I2C_Used");
		$this->DisableAction("I2C_Used");
		IPS_SetHidden($this->GetIDForIdent("I2C_Used"), true);
		$this->RegisterVariableInteger("HardwareRev", "HardwareRev");
		$this->DisableAction("HardwareRev");
		IPS_SetHidden($this->GetIDForIdent("HardwareRev"), true);
		
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
			$this->CommandClientSocket(pack("LLLL", 17, 0, 0, 0), 16);
			// Notify Handle zurücksetzen falls gesetzt
			If (GetValueInteger($this->GetIDForIdent("Handle")) > 0) {
				$this->ClientSocket(pack("LLLL", 21, GetValueInteger($this->GetIDForIdent("Handle")), 0, 0));
			}
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
		    // GPIO Kommunikation
		    case "set_PWM_dutycycle":
		    	// Dimmt einen Pin
		    	If ($data->Pin >= 0) {
		        	$this->CommandClientSocket(pack("LLLL", 5, $data->Pin, $data->Value, 0), 16);
				IPS_LogMessage("Set Intensity : ",$data->Pin." , ".$data->Value);  
		        }
		        break;
		    case "set_PWM_dutycycle_RGB":
		    	// Setzt die RGB-Farben
		    	If (($data->Pin_R >= 0) AND ($data->Pin_G >= 0) AND ($data->Pin_B >= 0)) {
		        	$this->CommandClientSocket(pack("LLLL", 5, $data->Pin_R, $data->Value_R, 0).pack("LLLL", 5, $data->Pin_G, $data->Value_G, 0).pack("LLLL", 5, $data->Pin_B, $data->Value_B, 0), 48);
				IPS_LogMessage("Set Intensity RGB : ",$data->Pin_R." , ".$data->Value_R." ".$data->Pin_G." , ".$data->Value_G." ".$data->Pin_B." , ".$data->Value_B);  
		    	}
		        break;
		    case "set_value":
		    	// Schaltet den Pin
		    	If ($data->Pin >= 0) {
		    		$this->CommandClientSocket(pack("LLLL", 4, $data->Pin, $data->Value, 0), 16);
				IPS_LogMessage("SetValue Parameter : ",$data->Pin." , ".$data->Value); 
		    	}
		        break;
		    case "set_trigger":
		    	// Setzten einen Trigger
		    	If ($data->Pin >= 0) {
		        	$this->CommandClientSocket(pack("LLLLL", 37, $data->Pin, $data->Time, 4, 1), 16);
				IPS_LogMessage("SetTrigger Parameter : ",$data->Pin." , ".$data->Time);  
		    	}
		        break;
		    
		    // interne Kommunikation
		    case "set_notifypin":
		    	If ($data->Pin >= 0) {
			        // Erstellt ein Array für alle Pins für die die Notifikation erforderlich ist 
			        $PinNotify = unserialize(GetValueString($this->GetIDForIdent("PinNotify")));
			        $PinNotify[] = $data->Pin;
				SetValueString($this->GetIDForIdent("PinNotify"), serialize($PinNotify));
				// Setzt den Glitch Filter
				$this->CommandClientSocket(pack("LLLL", 97, $data->Pin, $data->GlitchFilter, 0), 16);
				IPS_LogMessage("SetGlitchFilter Parameter : ",$data->Pin." , ".$data->GlitchFilter);  
		    	}
		        break;
		   case "set_usedpin":
		   	If ($data->Pin >= 0) {
				// Prüfen, ob der gewählte GPIO bei dem Modell überhaupt vorhanden ist
				$PinPossible = unserialize(GetValueString($this->GetIDForIdent("PinPossible")));
				if (in_array($data->Pin, $PinPossible)) {
			    		//IPS_LogMessage("GPIO Pin: ","Gewählter Pin ist bei diesem Modell verfügbar");
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
			        // Pin in den entsprechenden Mode setzen
			        $this->CommandClientSocket(pack("LLLL", 0, $data->Pin, $data->Modus, 0), 16);
				IPS_LogMessage("SetMode Parameter : ",$data->Pin." , ".$data->Modus);  
			        //$this->Set_Mode($data->Pin, $data->Modus);
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

		   // I2C Kommunikation
		   case "get_handle_i2c":
		   	If (GetValueInteger($this->GetIDForIdent("HardwareRev")) <=3) {
		   		$this->CommandClientSocket(pack("LLLLL", 54, 0, $data->DeviceAddress, 4, 0), 16);	
		   	}
		   	elseif (GetValueInteger($this->GetIDForIdent("HardwareRev")) >3) {
		   		$this->CommandClientSocket(pack("LLLLL", 54, 1, $data->DeviceAddress, 4, 0), 16);
		   	}
		   	break;
		   case "close_handle_i2c":
		   		$this->CommandClientSocket(pack("LLLL", 55, $data->Handle, 0, 0), 16);
		   	break;
		   case "set_used_i2c":
		   	SetValueBoolean($this->GetIDForIdent("I2C_Used"), true);
		   	$PinUsed = unserialize(GetValueString($this->GetIDForIdent("PinUsed")));
		   	$PinI2C = unserialize(GetValueString($this->GetIDForIdent("PinI2C")));
		   	// Arrays zusammenfügen
		   	$PinUsed = array_merge($PinUsed, $PinI2C);
		   	// doppelte Einträge löschen
		   	$PinUsed = array_unique($PinUsed);
		   	SetValueString($this->GetIDForIdent("PinUsed"), serialize($PinUsed));	
		   	break;
		   case "i2c_read_byte":
		   	$this->CommandClientSocket(pack("LLLL", 61, $data->Handle, $data->Register, 0), 16);
			//IPS_LogMessage("I2C Read Byte Parameter : ",$data->Handle." , ".$data->Register);  	
		   	break;
		    case "i2c_read_block_byte":
		   	$this->CommandClientSocket(pack("LLLLL", 67, $data->Handle, $data->Register, 4, $data->Count), 16 + ($data->Count));
			//IPS_LogMessage("I2C Read Block Byte Parameter : ",$data->Handle." , ".$data->Register." , ".$data->Count);  	
		   	break;
		   case "i2c_write_byte":
		   	$this->CommandClientSocket(pack("LLLLL", 62, $data->Handle, $data->Register, 4, $data->Value), 16);
			//IPS_LogMessage("I2C Write Byte Parameter : ",$data->Handle." , ".$data->Register." , ".$data->Value);  	
		   	break;
		   
		   // Serielle Kommunikation
		   case "close_handle_serial":
		   	IPS_LogMessage("GPIO Close Handle Serial", "Handle: ".$data->Handle);
		   	$this->CommandClientSocket(pack("LLLL", 77, $data->Handle, 0, 0), 16);
		   	break;
		   case "get_handle_serial":
	   		// Unfertig!!!
	   		IPS_LogMessage("GPIO Get Handle Serial", "Handle anfordern");
	   		$Device = "/dev/ttyAMA0";
	   		$this->CommandClientSocket(pack("LLLLL", 76, 38400, 0, strlen($Device), $Device), 16);
		   	break;
		   case "write_bytes_serial":
		   	IPS_LogMessage("GPIO Write Bytes Serial", "Handle: ".$data->Handle." Message: ".$data->Message);
		   	$this->CommandClientSocket(pack("L*", 81, $data->Handle, 0, strlen($data->Message), $data->Message), 16);
		   	break;
		}
	    
	    return;
	  }
	
	 public function ReceiveData($JSONString) {
 	    	$CmdPossible = array(19, 21, 99);
 	    	$RDlen = array(16, 32);	
 	    	// Empfangene Daten vom I/O
	    	$Data = json_decode($JSONString);
	    	$Message = utf8_decode($Data->Buffer);
	    	$MessageLen = strlen($Message);
	    	$MessageArray = unpack("L*", $Message);
		$Command = $MessageArray[1];
	    	
	    	If ((in_array($Command, $CmdPossible)) AND (in_array($MessageLen, $RDlen))) {
	    		// wenn es sich um mehrere Standarddatensätze handelt
	    		$DataArray = str_split($Message, 16);
	    		//IPS_LogMessage("GPIO ReceiveData", "Überlänge: ".Count($DataArray)." Command-Datensätze");
	    		for ($i = 0; $i < Count($DataArray); $i++) {
    				$this->ClientResponse($DataArray[$i]);
			}
	    	}
		elseif (($MessageLen / 12) == intval($MessageLen / 12)) {
	    		// wenn es sich um mehrere Notifikationen handelt
	    		$DataArray = str_split($Message, 12);
	    		//IPS_LogMessage("GPIO ReceiveData", "Überlänge: ".Count($DataArray)." Notify-Datensätze");
	    		for ($i = 0; $i < Count($DataArray); $i++) {
				$PinNotify = unserialize(GetValueString($this->GetIDForIdent("PinNotify")));
	
				for ($i = 0; $i < Count($PinNotify); $i++) {
	    				$Bitvalue = boolval($MessageArray[3]&(1<<$PinNotify[$i]));
	    				IPS_LogMessage("GPIO Notify: ","Pin ".$PinNotify[$i]." Value ->".$Bitvalue);
	    				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"notify", "Pin" => $PinNotify[$i], "Value"=> $Bitvalue, "Timestamp"=> $MessageArray[2])));
				}
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
		$PinNotify = array();
		SetValueString($this->GetIDForIdent("PinNotify"), serialize($PinNotify));
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_notifypin")));
		// Notify setzen	
		If (GetValueInteger($this->GetIDForIdent("Handle")) > 0) {
	           	$this->CommandClientSocket(pack("LLLL", 19, GetValueInteger($this->GetIDForIdent("Handle")), $this->CalcBitmask(), 0), 16);
		}
		// Ermitteln ob der I2C-Bus genutzt wird
		SetValueBoolean($this->GetIDForIdent("I2C_Used"), false);
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_used_i2c")));
		// Pins ermitteln die genutzt werden
		$PinUsed = array();
		SetValueString($this->GetIDForIdent("PinUsed"), serialize($PinUsed));
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_usedpin")));
	return;
	}

	private function ClientSocket($message)
	{
		$res = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => utf8_encode($message))));  
	return;	
	}
	
	private function CommandClientSocket($message, $ResponseLen = 16)
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
		if(socket_recv ($sock, $buf, $ResponseLen, MSG_WAITALL ) === FALSE) {
		    	$errorcode = socket_last_error();
		    	$errormsg = socket_strerror($errorcode);
			IPS_LogMessage("GPIO Socket: ", "Fehler beim beim Empfangen ".[$errorcode]." ".$errormsg);
			return;
		}
		// Anfragen mit variabler Rückgabelänge
		$CmdVarLen = array(56, 67, 70, 73, 75, 80, 88, 91, 92, 106, 109);
		$MessageArray = unpack("L*", $buf);
		$Command = $MessageArray[1];
		If (in_array($Command, $CmdVarLen)) {
			$this->ClientResponse($buf);
			//IPS_LogMessage("GPIO ReceiveData", strlen($buf)." Zeichen");
		}
		// Standardantworten
		elseIf ((strlen($buf) == 16) OR ((strlen($buf) / 16) == intval(strlen($buf) / 16))) {
			$DataArray = str_split($buf, 16);
	    		//IPS_LogMessage("GPIO ReceiveData", strlen($buf)." Zeichen");
	    		for ($i = 0; $i < Count($DataArray); $i++) {
    				$this->ClientResponse($DataArray[$i]);
			}
		}
		else {
			IPS_LogMessage("GPIO ReceiveData", strlen($buf)." Zeichen - nicht differenzierbar!");
		}
		
	return;	
	}
	
	private function ClientResponse($Message)
	{
		$response = unpack("L*", $Message);
		switch($response[1]) {
		        case "4":
		        	If ($response[4] == 0) {
		        		IPS_LogMessage("GPIO Write: ", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"result", "Pin" => $response[2], "Value"=> $response[3])));
		        	}
		        	else {
		        		IPS_LogMessage("GPIO Write: ", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden!");
		        	}
		        	break;
		        case "5":
		        	If ($response[4] == 0) {
		        		IPS_LogMessage("GPIO PWM: ", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"result", "Pin" => $response[2], "Value"=> $response[3])));
		        	}
		        	else {
		        		IPS_LogMessage("GPIO PWM: ", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden!");
		        	}
		        	break;
		        case "17":
		            	IPS_LogMessage("GPIO Hardwareermittlung: ","gestartet");
		            	$Model[0] = array(2, 3);
		            	$Model[1] = array(4, 5, 6, 13, 14, 15);
		            	$Model[2] = array(16);
		            	$Typ[0] = array(0, 1, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 21, 22, 23, 24, 25);	
           			$Typ[1] = array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27);
           			$Typ[2] = array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);
           			
           			SetValueInteger($this->GetIDForIdent("HardwareRev"), $response[4]);
           			
           			if (in_array($response[4], $Model[0])) {
    					SetValueString($this->GetIDForIdent("PinPossible"), serialize(array(0, 1, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 21, 22, 23, 24, 25)));
    					SetValueString($this->GetIDForIdent("PinI2C"), serialize(array(0, 1)));
    					IPS_LogMessage("GPIO Hardwareermittlung: ","Raspberry Pi Typ 0");
				}
				else if (in_array($response[4], $Model[1])) {
					SetValueString($this->GetIDForIdent("PinPossible"), serialize(array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27)));
					SetValueString($this->GetIDForIdent("PinI2C"), serialize(array(2, 3)));
					IPS_LogMessage("GPIO Hardwareermittlung: ","Raspberry Pi Typ 1");
				}
				else if ($response[4] >= 16) {
					SetValueString($this->GetIDForIdent("PinPossible"), serialize(array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27)));
					SetValueString($this->GetIDForIdent("PinI2C"), serialize(array(2, 3)));
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
		        case "54":
		        	If ($response[4] >= 0 ) {
           				IPS_LogMessage("GPIO I2C-Handle: ",$response[4]." für Device ".$response[3]);
           				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_handle", "Address" => $response[3], "Handle" => $response[4], "HardwareRev" => GetValueInteger($this->GetIDForIdent("HardwareRev")))));
           			}
           			else {
           				IPS_LogMessage("GPIO I2C-Handle: ",$response[4]." für Device ".$response[3]." nicht vergeben!");
           			}
           			
		        	break;
		        case "55":
           			IPS_LogMessage("GPIO I2C Close Handle: ","Handle: ".$response[2]." Value: ".$response[4]);
		            	break;
		        case "61":
           			IPS_LogMessage("GPIO I2C Read Byte: ","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]);
		            	$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "Handle" => $response[2], "Register" => $response[3], "Value" => $response[4])));
		            	break;
		        case "62":
           			IPS_LogMessage("GPIO I2C Write Byte: ","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]);
		            	$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "Handle" => $response[2], "Register" => $response[3], "Value" => $response[4])));
		            	break;
		        case "67":
           			IPS_LogMessage("GPIO I2C Read Block Byte: ","Handle: ".$response[2]." Register: ".$response[3]." Count: ".$response[4]);
		            	$ByteMessage = substr($Message, -($response[4]));
		            	$ByteResponse = unpack("C*", $ByteMessage);
		            	$ByteArray = serialize($ByteResponse);
		            	//IPS_LogMessage("GPIO I2C Read Block Byte: ", strlen($Message)."  ".count($ByteResponse));
 				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_byte_block", "Handle" => $response[2], "Register" => $response[3], "Count" => $response[4], "ByteArray" => $ByteArray)));
		            	break;
		        case "76":
           			IPS_LogMessage("GPIO Serial Handle: ","Serial Handle: ".$response[4]);
           			$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_handle", "Handle" => $response[4], )));

		            	break;
		        case "77":
           			IPS_LogMessage("GPIO Serial Close Handle: ","Serial Handle: ".$response[2]." Value: ".$response[4]);
		            	break;
		        case "81":
           			IPS_LogMessage("GPIO Serial Write: ","Serial Handle: ".$response[2]." Value: ".$response[4]);
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
           			else {
           				$this->ClientSocket(pack("LLLL", 99, 0, 0, 0));		
           			}
           			break;
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
