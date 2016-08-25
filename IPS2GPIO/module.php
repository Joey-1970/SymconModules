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
	    $this->RegisterPropertyBoolean("I2C_Used", true);
	    $this->RegisterPropertyBoolean("Serial_Used", true);
	    $this->RegisterPropertyBoolean("SPI_Used", true);
	    $this->ConnectParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
	  }
  
	  public function ApplyChanges()
	  {
		//Never delete this line!
		parent::ApplyChanges();
		$this->RegisterVariableInteger("Handle", "Handle", "", 100);
		$this->DisableAction("Handle");
		IPS_SetHidden($this->GetIDForIdent("Handle"), true);
		
		$this->RegisterVariableInteger("HardwareRev", "HardwareRev", "", 105);
		$this->DisableAction("HardwareRev");
		IPS_SetHidden($this->GetIDForIdent("HardwareRev"), true);
		
		$this->RegisterVariableString("PinPossible", "PinPossible", "", 110);
		$this->DisableAction("PinPossible");
		IPS_SetHidden($this->GetIDForIdent("PinPossible"), true);
		
		$this->RegisterVariableString("PinUsed", "PinUsed", "", 120);
		$this->DisableAction("PinUsed");
		IPS_SetHidden($this->GetIDForIdent("PinUsed"), true);
		
		$this->RegisterVariableString("PinNotify", "PinNotify", "", 130);
		$this->DisableAction("PinNotify");
		IPS_SetHidden($this->GetIDForIdent("PinNotify"), true);
		
		$this->RegisterVariableBoolean("I2C_Used", "I2C_Used", "", 140);
		$this->DisableAction("I2C_Used");
		IPS_SetHidden($this->GetIDForIdent("I2C_Used"), true);
		
		$this->RegisterVariableString("PinI2C", "PinI2C", "", 150);
		$this->DisableAction("PinI2C");
		IPS_SetHidden($this->GetIDForIdent("PinI2C"), true);
		
		$this->RegisterVariableString("I2C_Handle", "I2C_Handle", "", 160);
		$this->DisableAction("I2C_Handle");
		IPS_SetHidden($this->GetIDForIdent("I2C_Handle"), true);
		$I2C_DeviceHandle = array();
		SetValueString($this->GetIDForIdent("I2C_Handle"), serialize($I2C_DeviceHandle));
		
		$this->RegisterVariableBoolean("Serial_Used", "Serial_Used", "", 170);
		$this->DisableAction("Serial_Used");
		IPS_SetHidden($this->GetIDForIdent("Serial_Used"), true);
		
		$this->RegisterVariableInteger("Serial_Handle", "Serial_Handle", "", 180);
		$this->DisableAction("Serial_Handle");
		IPS_SetHidden($this->GetIDForIdent("Serial_Handle"), true);
		
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
			If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
				// Handle löschen
				//$this->ClientSocket(pack("LLLL", 21, GetValueInteger($this->GetIDForIdent("Handle")), 0, 0));
			}
			// Notify Starten
			SetValueInteger($this->GetIDForIdent("Handle"), -1);
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
		        	//IPS_LogMessage("IPS2GPIO Set Intensity : ",$data->Pin." , ".$data->Value);
		        	$this->CommandClientSocket(pack("LLLL", 5, $data->Pin, $data->Value, 0), 16);
		        }
		        break;
		    case "set_PWM_dutycycle_RGB":
		    	// Setzt die RGB-Farben
		    	If (($data->Pin_R >= 0) AND ($data->Pin_G >= 0) AND ($data->Pin_B >= 0)) {
		        	//IPS_LogMessage("IPS2GPIO Set Intensity RGB : ",$data->Pin_R." , ".$data->Value_R." ".$data->Pin_G." , ".$data->Value_G." ".$data->Pin_B." , ".$data->Value_B);  
		        	$this->CommandClientSocket(pack("LLLL", 5, $data->Pin_R, $data->Value_R, 0).pack("LLLL", 5, $data->Pin_G, $data->Value_G, 0).pack("LLLL", 5, $data->Pin_B, $data->Value_B, 0), 48);
		    	}
		        break;
		    case "set_value":
		    	// Schaltet den Pin
		    	If ($data->Pin >= 0) {
		    		//IPS_LogMessage("IPS2GPIO SetValue Parameter : ",$data->Pin." , ".$data->Value); 
		    		$this->CommandClientSocket(pack("LLLL", 4, $data->Pin, $data->Value, 0), 16);
		    	}
		        break;
		    case "set_trigger":
		    	// Setzten einen Trigger
		    	If ($data->Pin >= 0) {
		        	//IPS_LogMessage("IPS2GPIO SetTrigger Parameter : ",$data->Pin." , ".$data->Time);
		        	$this->CommandClientSocket(pack("LLLLL", 37, $data->Pin, $data->Time, 4, 1), 16);
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
				//IPS_LogMessage("IPS2GPIO SetGlitchFilter Parameter : ",$data->Pin." , ".$data->GlitchFilter);
				$this->CommandClientSocket(pack("LLLL", 97, $data->Pin, $data->GlitchFilter, 0), 16);
		    	}
		        break;
		   case "set_usedpin":
		   	If ($data->Pin >= 0) {
				// Prüfen, ob der gewählte GPIO bei dem Modell überhaupt vorhanden ist
				$PinPossible = unserialize(GetValueString($this->GetIDForIdent("PinPossible")));
				if (in_array($data->Pin, $PinPossible)) {
			    		//IPS_LogMessage("IPS2GPIO Pin: ","Gewählter Pin ist bei diesem Modell verfügbar");
			    		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>102, "HardwareRev"=>GetValueInteger($this->GetIDForIdent("HardwareRev")))));
				}
				else {
					IPS_LogMessage("IPS2GPIO Pin: ","Gewählter Pin ist bei diesem Modell nicht verfügbar!");
					$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>201, "HardwareRev"=>GetValueInteger($this->GetIDForIdent("HardwareRev")))));
				}
				// Erstellt ein Array für alle Pins die genutzt werden 	
				$PinUsed = unserialize(GetValueString($this->GetIDForIdent("PinUsed")));
				// Prüft, ob der ausgeählte Pin schon einmal genutzt wird
			        If (in_array($data->Pin, $PinUsed)) {
			        	IPS_LogMessage("IPS2GPIO Pin", "Achtung: Pin ".$data->Pin." wird mehrfach genutzt!");
			        	$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>200, "HardwareRev"=>GetValueInteger($this->GetIDForIdent("HardwareRev")))));
			        }
			        $PinUsed[] = $data->Pin;
			        // Pin in den entsprechenden R/W-Mode setzen
			        //IPS_LogMessage("SetMode Parameter : ",$data->Pin." , ".$data->Modus);
			        $this->CommandClientSocket(pack("LLLL", 0, $data->Pin, $data->Modus, 0), 16);
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
		   		IPS_LogMessage("IPS2GPIO Pin", "Pin ".$PinFreeArray[0]." ist noch ungenutzt");
			        $this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"freepin", "Pin"=>$PinFreeArray[0])));
		   	}
		   	else {
		   		IPS_LogMessage("IPS2GPIO Pin", "Achtung: Kein ungenutzter Pin gefunden");	
		   	}
		   	break;

		   // I2C Kommunikation
		   case "set_used_i2c":
		   	SetValueBoolean($this->GetIDForIdent("I2C_Used"), true);
		   	
		   	// die genutzten Device Adressen anlegen
		   	$I2C_DeviceHandle = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
		   	$I2C_DeviceHandle[$data->DeviceAddress] = -1;
		   	// genutzte Device-Adressen noch ohne Handle sichern
		   	SetValueString($this->GetIDForIdent("I2C_Handle"), serialize($I2C_DeviceHandle));
		   	// Handle ermitteln
		   	If (GetValueInteger($this->GetIDForIdent("HardwareRev")) <=3) {
		   		$this->CommandClientSocket(pack("LLLLL", 54, 0, $data->DeviceAddress, 4, 0), 16);	
		   	}
		   	elseif (GetValueInteger($this->GetIDForIdent("HardwareRev")) >3) {
		   		$this->CommandClientSocket(pack("LLLLL", 54, 1, $data->DeviceAddress, 4, 0), 16);
		   	}
		   	//IPS_LogMessage("IPS2GPIO I2C Handle: ","Device Adresse: ".$data->DeviceAddress.", Hardware Rev:: ".GetValueInteger($this->GetIDForIdent("HardwareRev"))); 
		   	break;
		   case "i2c_read_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Byte Parameter : ",$data->Handle." , ".$data->Register); 
		   	If ($this->GetI2C_DeviceHandle($data->DeviceAddress) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle($data->DeviceAddress), $data->Register, 0), 16);
		   	}
		   	break;
		   case "i2c_read_word":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Word Parameter : ","DeviceAdresse: ".$data->DeviceAddress.", Handle: ".$this->GetI2C_DeviceHandle($data->DeviceAddress)." ,Register: ".$data->Register);
		   	If ($this->GetI2C_DeviceHandle($data->DeviceAddress) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 63, intval($this->GetI2C_DeviceHandle($data->DeviceAddress)), $data->Register, 0), 16);
		   	}
		   	break; 
		   case "i2c_read_block_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Block Byte Parameter : ",$data->Handle." , ".$data->Register." , ".$data->Count);  	
		   	If ($this->GetI2C_DeviceHandle($data->DeviceAddress) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle($data->DeviceAddress), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;
		   case "i2c_write_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Write Byte : ",$data->Handle." , ".$data->Register." , ".$data->Value);  	
		   	If ($this->GetI2C_DeviceHandle($data->DeviceAddress) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle($data->DeviceAddress), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		   case "i2c_write_byte_onhandle":
		   	//IPS_LogMessage("IPS2GPIO I2C Write Byte Handle: ","DeviceAdresse: ".$data->DeviceAddress.", Handle: ".$this->GetI2C_DeviceHandle($data->DeviceAddress).", Wert: ".$data->Value);  	
		   	If ($this->GetI2C_DeviceHandle($data->DeviceAddress) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 60, intval($this->GetI2C_DeviceHandle($data->DeviceAddress)), $data->Value, 0), 16);
		   	}
		   	break;	
		   	
		   
		   // Serielle Kommunikation
		   case "close_handle_serial":
		   	IPS_LogMessage("IPS2GPIO Close Handle Serial", "Handle: ".GetValueInteger($this->GetIDForIdent("Serial_Handle")));
		   	$this->CommandClientSocket(pack("LLLL", 77, GetValueInteger($this->GetIDForIdent("Serial_Handle")), 0, 0), 16);
		   	break;
		   case "get_handle_serial":
	   		IPS_LogMessage("IPS2GPIO Get Handle Serial", "Handle anfordern");
	   		$this->ClientSocket(pack("L*", 76, $data->Baud, 0, strlen($data->Device)).$data->Device, 16);
		   	break;
		   case "write_bytes_serial":
		   	$Command = utf8_decode($data->Command);
		   	IPS_LogMessage("IPS2GPIO Write Bytes Serial", "Handle: ".GetValueInteger($this->GetIDForIdent("Serial_Handle"))." Command: ".$Command);
		   	$this->CommandClientSocket(pack("L*", 81, GetValueInteger($this->GetIDForIdent("Serial_Handle")), 0, strlen($Command)).$Command, 16);
		   	break;
		}
	    
	    return;
	  }
	
	 public function ReceiveData($JSONString) {
 	    	$CmdPossible = array(19, 21, 76, 99);
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
	    		//IPS_LogMessage("IPS2GPIO ReceiveData", "Überlänge: ".Count($DataArray)." Command-Datensätze");
	    		for ($i = 0; $i < Count($DataArray); $i++) {
    				$this->ClientResponse($DataArray[$i]);
			}
	    	}
		elseif (($MessageLen / 12) == intval($MessageLen / 12)) {
	    		// wenn es sich um mehrere Notifikationen handelt
	    		$DataArray = str_split($Message, 12);
	    		//IPS_LogMessage("IPS2GPIO ReceiveData", "Überlänge: ".Count($DataArray)." Notify-Datensätze");
	    		$PinNotify = unserialize(GetValueString($this->GetIDForIdent("PinNotify")));
	    		for ($i = 0; $i < min(5, Count($DataArray)); $i++) {
				$MessageParts = unpack("L*", $DataArray[$i]);
				for ($j = 0; $j < Count($PinNotify); $j++) {
	    				$Bitvalue = boolval($MessageParts[3]&(1<<$PinNotify[$j]));
	    				IPS_LogMessage("IPS2GPIO Notify: ","Pin ".$PinNotify[$j]." Value ->".$Bitvalue);
	    				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"notify", "Pin" => $PinNotify[$j], "Value"=> $Bitvalue, "Timestamp"=> $MessageArray[2])));
				}
			}
		}
	 	else {
	 		IPS_LogMessage("IPS2GPIO ReceiveData", "Überlänge: Datensätze nicht differenzierbar!");
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
		If (GetValueInteger($this->GetIDForIdent("Handle")) >= 0) {
	           	$this->CommandClientSocket(pack("LLLL", 19, GetValueInteger($this->GetIDForIdent("Handle")), $this->CalcBitmask(), 0), 16);
		}
		// Ermitteln ob der I2C-Bus genutzt wird und welcher Device Adressen
		// Bisherige I2C-Handle löschen
		$I2C_DeviceHandle = array_values(unserialize(GetValueString($this->GetIDForIdent("I2C_Handle"))));
		for ($i = 0; $i < Count($I2C_DeviceHandle); $i++) {
			$this->CommandClientSocket(pack("L*", 55, $I2C_DeviceHandle[$i], 0, 0), 16);
		}
		// Pins ermitteln die genutzt werden
		$PinUsed = array();
		SetValueBoolean($this->GetIDForIdent("I2C_Used"), false);
		// Reservieren der Schnittstellen GPIO
		If (($this->ReadPropertyBoolean("I2C_Used") == true) AND (GetValueInteger($this->GetIDForIdent("HardwareRev"))) <= 3) {
			$PinUsed[] = 0; 
			$PinUsed[] = 1;
			$this->CommandClientSocket(pack("LLLL", 0, 0, 4, 0), 16);
			$this->CommandClientSocket(pack("LLLL", 0, 1, 4, 0), 16);
		}
		elseif (($this->ReadPropertyBoolean("I2C_Used") == true) AND (GetValueInteger($this->GetIDForIdent("HardwareRev"))) > 3) {
			$PinUsed[] = 2; 
			$PinUsed[] = 3;
			$this->CommandClientSocket(pack("LLLL", 0, 2, 4, 0), 16);
			$this->CommandClientSocket(pack("LLLL", 0, 3, 4, 0), 16);
		}
		elseif ($this->ReadPropertyBoolean("Serial_Used") == true)  {
			$PinUsed[] = 14; 
			$PinUsed[] = 15;
			$this->CommandClientSocket(pack("LLLL", 0, 14, 4, 0), 16);
			$this->CommandClientSocket(pack("LLLL", 0, 15, 4, 0), 16);
			SetValueInteger($this->GetIDForIdent("Serial_Handle"), -1);
			SetValueBoolean($this->GetIDForIdent("Serial_Used"), false);
			
		}
		elseif ($this->ReadPropertyBoolean("SPI_Used") == true)  {
			for ($i = 7; $i < 11; $i++) {
    				$PinUsed[] = $i;
			}
		}
		// Sichern der Voreinstellungen
		SetValueString($this->GetIDForIdent("PinUsed"), serialize($PinUsed));
		// Ermitteln der genutzten I2C-Adressen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_used_i2c")));
		// Ermitteln der sonstigen genutzen GPIO
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_usedpin")));
	return;
	}

	private function ClientSocket($message)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$res = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => utf8_encode($message))));  
		}
	return;	
	}
	
	private function CommandClientSocket($message, $ResponseLen = 16)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			// Socket erstellen
			if(!($sock = socket_create(AF_INET, SOCK_STREAM, 0))) {
				$errorcode = socket_last_error();
			    	$errormsg = socket_strerror($errorcode);
			    	IPS_LogMessage("IPS2GPIO Socket: ", "Fehler beim Erstellen ".[$errorcode]." ".$errormsg);
			    	return;
			}
			// Timeout setzen
			socket_set_option($sock,SOL_SOCKET, SO_RCVTIMEO, array("sec"=>2, "usec"=>0));
			// Verbindung aufbauen
			if(!(socket_connect($sock, $this->ReadPropertyString("IPAddress"), 8888))) {
				$errorcode = socket_last_error();
			    	$errormsg = socket_strerror($errorcode);
				IPS_LogMessage("IPS2GPIO Socket: ", "Fehler beim Verbindungsaufbaus ".[$errorcode]." ".$errormsg);
				return;
			}
			// Message senden
			if( ! socket_send ($sock, $message, strlen($message), 0))
			{
				$errorcode = socket_last_error();
			    	$errormsg = socket_strerror($errorcode);
				IPS_LogMessage("IPS2GPIO Socket: ", "Fehler beim beim Senden ".[$errorcode]." ".$errormsg);
				return;
			}
			//Now receive reply from server
			if(socket_recv ($sock, $buf, $ResponseLen, MSG_WAITALL ) === FALSE) {
			    	$errorcode = socket_last_error();
			    	$errormsg = socket_strerror($errorcode);
				IPS_LogMessage("IPS2GPIO Socket: ", "Fehler beim beim Empfangen ".[$errorcode]." ".$errormsg);
				return;
			}
			// Anfragen mit variabler Rückgabelänge
			$CmdVarLen = array(56, 67, 70, 73, 75, 80, 88, 91, 92, 106, 109);
			$MessageArray = unpack("L*", $buf);
			$Command = $MessageArray[1];
			If (in_array($Command, $CmdVarLen)) {
				$this->ClientResponse($buf);
				//IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen");
			}
			// Standardantworten
			elseIf ((strlen($buf) == 16) OR ((strlen($buf) / 16) == intval(strlen($buf) / 16))) {
				$DataArray = str_split($buf, 16);
		    		//IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen");
		    		for ($i = 0; $i < Count($DataArray); $i++) {
	    				$this->ClientResponse($DataArray[$i]);
				}
			}
			else {
				IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen - nicht differenzierbar!");
			}
		}
	return;	
	}
	
	private function ClientResponse($Message)
	{
		$response = unpack("L*", $Message);
		switch($response[1]) {
		        case "0":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO Set Mode: ", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO Set Mode: ", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
		        case "4":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO Write: ", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"result", "Pin" => $response[2], "Value"=> $response[3])));
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO Write: ", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
		        case "5":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO PWM: ", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"result", "Pin" => $response[2], "Value"=> $response[3])));
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO PWM: ", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
		        case "17":
		            	//IPS_LogMessage("IPS2GPIO Hardwareermittlung: ","gestartet");
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
    					IPS_LogMessage("IPS2GPIO Hardwareermittlung: ","Raspberry Pi Typ 0");
				}
				else if (in_array($response[4], $Model[1])) {
					SetValueString($this->GetIDForIdent("PinPossible"), serialize(array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27)));
					SetValueString($this->GetIDForIdent("PinI2C"), serialize(array(2, 3)));
					IPS_LogMessage("IPS2GPIO Hardwareermittlung: ","Raspberry Pi Typ 1");
				}
				else if ($response[4] >= 16) {
					SetValueString($this->GetIDForIdent("PinPossible"), serialize(array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27)));
					SetValueString($this->GetIDForIdent("PinI2C"), serialize(array(2, 3)));
					IPS_LogMessage("IPS2GPIO Hardwareermittlung: ","Raspberry Pi Typ 2");
				}
				else
					IPS_LogMessage("IPS2GPIO Hardwareermittlung: ","nicht erfolgreich! Fehler:".$this->GetErrorText(abs($response[4])));
				break;
           		case "19":
           			IPS_LogMessage("IPS2GPIO Notify: ","gestartet");
		            	break;
           		case "21":
           			IPS_LogMessage("IPS2GPIO Notify: ","gestoppt");
		            	break;
		        case "54":
		        	If ($response[4] >= 0 ) {
           				//IPS_LogMessage("IPS2GPIO I2C Handle: ",$response[4]." für Device ".$response[3]);
           				$I2C_DeviceHandle = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
 					$I2C_DeviceHandle[$response[3]] = $response[4];
 					SetValueString($this->GetIDForIdent("I2C_Handle"), serialize($I2C_DeviceHandle));
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO I2C Handle: ","Fehler: ".$this->GetErrorText(abs($response[4]))." Handle für Device ".$response[3]." nicht vergeben!");
           			}
           			
		        	break;
		        case "55":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Close Handle: ","Handle: ".$response[2]." Value: ".$response[4]);
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO I2C Close Handle: ","Handle: ".$response[2]." Value: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "60":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Write Byte Handle: ","Handle: ".$response[2]." Value: ".$response[4]);
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceAddress" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO I2C Write Byte Handle: ","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "61":
		            	If ($response[4] >= 0) {
		            		//IPS_LogMessage("IPS2GPIO I2C Read Byte: ","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]);
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceAddress" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
		            	}
		            	else {
		            		IPS_LogMessage("IPS2GPIO I2C Read Byte: ","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$this->GetErrorText(abs($response[4])));	
		            	}
		            	break;
		        case "62":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Write Byte: ","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]);
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceAddress" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO I2C Write Byte: ","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "63":
		            	If ($response[4] >= 0) {
		            		//IPS_LogMessage("IPS2GPIO I2C Read Word: ","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]);
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceAddress" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
		            	}
		            	else {
		            		IPS_LogMessage("IPS2GPIO I2C Read Word: ","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$this->GetErrorText(abs($response[4])));
		            	}
		            	break;
		        case "67":
           			//IPS_LogMessage("IPS2GPIO I2C Read Block Byte: ","Handle: ".$response[2]." Register: ".$response[3]." Count: ".$response[4]);
		            	$ByteMessage = substr($Message, -($response[4]));
		            	$ByteResponse = unpack("C*", $ByteMessage);
		            	$ByteArray = serialize($ByteResponse);
 				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_byte_block", "DeviceAddress" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Count" => $response[4], "ByteArray" => $ByteArray)));
		            	break;
		        case "76":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Serial Handle: ","Serial Handle: ".$response[4]);
           				SetValueInteger($this->GetIDForIdent("Serial_Handle"), $response[4]);
           				SetValueBoolean($this->GetIDForIdent("Serial_Used"), true);
				}
				else {
					IPS_LogMessage("IPS2GPIO I2C Get Serial Handle: ","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
				}
		            	break;
		        case "77":
           			IPS_LogMessage("IPS2GPIO Serial Close Handle: ","Serial Handle: ".$response[2]." Value: ".$response[4]);
		            	break;
		        case "81":
           			If ($response[4] >= 0) {
           				IPS_LogMessage("IPS2GPIO Serial Write: ","Serial Handle: ".$response[2]." Value: ".$response[4]);
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Serial Write: ","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
 
           			}
  		            	break;
		        case "97":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO GlitchFilter: ","gesetzt");
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO GlitchFilter: ","Fehler beim Setzen: ".$this->GetErrorText(abs($response[4])));
           			}
         
		            	break;
		        case "99":
           			If ($response[4] >= 0 ) {
           				IPS_LogMessage("IPS2GPIO Handle: ",$response[4]);
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
			IPS_LogMessage("IPS2GPIO Netzanbindung: ","Raspberry Pi gefunden");
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geschlossen!");
					$this->SetStatus(104);
	   			}
	   			else {
	   				fclose($status);
					IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geöffnet");
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
	
	private function GetI2C_DeviceHandle($DeviceAddress)
	{
		// Gibt für ein Device den verknüpften Handle aus
		$I2C_HandleData = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
 		If (array_key_exists($DeviceAddress, $I2C_HandleData)) {
 			$I2C_Handle = $I2C_HandleData[$DeviceAddress];
 		}
 		else {
 			$I2C_Handle = -1;	
 		}			  
	return $I2C_Handle;
	}
	
	private function GetI2C_HandleDevice($I2C_Handle)
	{
		// Gibt für ein I2C-Device die Adresse aus
		$I2C_HandleData = unserialize(GetValueString($this->GetIDForIdent("I2C_Handle")));
 		If (array_search($I2C_Handle, $I2C_HandleData) == false) {
 			$I2C_Device = -1;
 		}
 		else {
 			$I2C_Device = array_search($I2C_Handle, $I2C_HandleData);	
 		}			  
	return $I2C_Device;
	}
	
	private function GetParentID()
	{
		$ParentID = (IPS_GetInstance($this->InstanceID)['ConnectionID']);  
	return $ParentID;
	}
  	
  	private function GetParentStatus()
	{
		$Status = (IPS_GetInstance($this->GetParentID())['InstanceStatus']);  
	return $Status;
	}
  	
  	private function GetErrorText($ErrorNumber)
	{
		$ErrorMessage = array(2 => "PI_BAD_USER_GPIO", 3 => "PI_BAD_GPIO", 4 => "PI_BAD_MODE", 5 => "PI_BAD_LEVEL", 6 => "PI_BAD_PUD", 7 => "PI_BAD_PULSEWIDTH",
			8 => "PI_BAD_DUTYCYCLE", 15 => "PI_BAD_WDOG_TIMEOUT", 21 => "PI_BAD_DUTYRANGE", 24 => "PI_NO_HANDLE", 25 => "PI_BAD_HANDLE",
			35 => "PI_BAD_WAVE_BAUD", 36 => "PI_TOO_MANY_PULSES", 37 => "PI_TOO_MANY_CHARS", 38 => "PI_NOT_SERIAL_GPIO", 41 => "PI_NOT_PERMITTED",
			42 => "PI_SOME_PERMITTED", 43 =>"PI_BAD_WVSC_COMMND", 44 => "PI_BAD_WVSM_COMMND", 45 =>"PI_BAD_WVSP_COMMND", 46 => "PI_BAD_PULSELEN",
			47 => "PI_BAD_SCRIPT", 48 => "PI_BAD_SCRIPT_ID", 49 => "PI_BAD_SER_OFFSET", 50 => "PI_GPIO_IN_USE", 51 =>"PI_BAD_SERIAL_COUNT",
			52 => "PI_BAD_PARAM_NUM", 53 => "PI_DUP_TAG", 54 => "PI_TOO_MANY_TAGS", 55 => "PI_BAD_SCRIPT_CMD", 56 => "PI_BAD_VAR_NUM",
			57 => "PI_NO_SCRIPT_ROOM", 58 => "PI_NO_MEMORY", 59 => "PI_SOCK_READ_FAILED", 60 => "PI_SOCK_WRIT_FAILED", 61 => "PI_TOO_MANY_PARAM",
			62 => "PI_SCRIPT_NOT_READY", 63 => "PI_BAD_TAG", 64 => "PI_BAD_MICS_DELAY", 65 => "PI_BAD_MILS_DELAY", 66 => "PI_BAD_WAVE_ID",
			67 => "PI_TOO_MANY_CBS", 68 => "PI_TOO_MANY_OOL", 69 => "PI_EMPTY_WAVEFORM", 70 => "PI_NO_WAVEFORM_ID", 71 => "PI_I2C_OPEN_FAILED",
			72 => "PI_SER_OPEN_FAILED", 73 => "PI_SPI_OPEN_FAILED", 74 => "PI_BAD_I2C_BUS", 75 => "PI_BAD_I2C_ADDR", 76 => "PI_BAD_SPI_CHANNEL",
			77 => "PI_BAD_FLAGS", 78 => "PI_BAD_SPI_SPEED", 79 => "PI_BAD_SER_DEVICE", 80 => "PI_BAD_SER_SPEED", 81 => "PI_BAD_PARAM",
			82 => "PI_I2C_WRITE_FAILED", 83 => "PI_I2C_READ_FAILED", 84 => "PI_BAD_SPI_COUNT", 85 => "PI_SER_WRITE_FAILED",
			86 => "PI_SER_READ_FAILED", 87 => "PI_SER_READ_NO_DATA", 88 => "PI_UNKNOWN_COMMAND", 89 => "PI_SPI_XFER_FAILED",
			91 => "PI_NO_AUX_SPI", 92 => "PI_NOT_PWM_GPIO", 93 => "PI_NOT_SERVO_GPIO", 94 => "PI_NOT_HCLK_GPIO", 95 => "PI_NOT_HPWM_GPIO",
			96 => "PI_BAD_HPWM_FREQ", 97 => "PI_BAD_HPWM_DUTY", 98 => "PI_BAD_HCLK_FREQ", 99 => "PI_BAD_HCLK_PASS", 100 => "PI_HPWM_ILLEGAL",
			101 => "PI_BAD_DATABITS", 102 => "PI_BAD_STOPBITS", 103 => "PI_MSG_TOOBIG", 104 => "PI_BAD_MALLOC_MODE", 107 => "PI_BAD_SMBUS_CMD",
			108 => "PI_NOT_I2C_GPIO", 109 => "PI_BAD_I2C_WLEN", 110 => "PI_BAD_I2C_RLEN", 111 => "PI_BAD_I2C_CMD", 112 => "PI_BAD_I2C_BAUD",
			113 => "PI_CHAIN_LOOP_CNT", 114 => "PI_BAD_CHAIN_LOOP", 115 => "PI_CHAIN_COUNTER", 116 => "PI_BAD_CHAIN_CMD", 117 => "PI_BAD_CHAIN_DELAY",
			118 => "PI_CHAIN_NESTING", 119 => "PI_CHAIN_TOO_BIG", 120 => "PI_DEPRECATED", 121 => "PI_BAD_SER_INVERT", 124 => "PI_BAD_FOREVER",
			125 => "PI_BAD_FILTER", 126 => "PI_BAD_PAD", 127 => "PI_BAD_STRENGTH", 128 => "PI_FIL_OPEN_FAILED", 129 => "PI_BAD_FILE_MODE",
			130 => "PI_BAD_FILE_FLAG", 131 => "PI_BAD_FILE_READ", 132 => "PI_BAD_FILE_WRITE", 133 => "PI_FILE_NOT_ROPEN",
			134 => "PI_FILE_NOT_WOPEN", 135 => "PI_BAD_FILE_SEEK", 136 => "PI_NO_FILE_MATCH", 137 => "PI_NO_FILE_ACCESS",
			138 => "PI_FILE_IS_A_DIR", 139 => "PI_BAD_SHELL_STATUS", 140 => "PI_BAD_SCRIPT_NAME");
		If (array_key_exists($ErrorNumber, $ErrorMessage)) {
			$ErrorText = $ErrorMessage[$ErrorNumber];
		}
		else {
			$ErrorText = "unknown Error -".$ErrorNumber;
		}
	return $ErrorText;
	}
  	
}
?>
