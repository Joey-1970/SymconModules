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
	    $this->ConnectParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
	    
	
	  }
  
	  public function ApplyChanges()
	  {
		//Never delete this line!
		parent::ApplyChanges();
		//$this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Host" => $this->ReadPropertyString("IPAddress"))));
		$this->RegisterVariableString("PinPossible", "PinPossible");
		$this->RegisterVariableString("PinUsed", "PinUsed");
		$this->RegisterVariableString("PinNotify", "PinNotify");
		$this->RegisterVariableInteger("Handle", "Handle");
		
		
		If($this->ConnectionTest()) {
			// Hardware feststellen
			$this->ClientSocket(pack("LLLL", 17, 0, 0, 0));
			// Notify Starten
			$this->ClientSocket(pack("LLLL", 99, 0, 0, 0));
			
			$this->Get_PinUpdate;
		}
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
		        $this->PinPossible($data->DataID, $data->InstanzID, $data->Pin);
		        break;
		    case "set_glitchfilter":
		        
		        $this->Set_GlitchFilter($data->Pin, $data->Value);
		        break;
		    case "set_notifypin":
		        // Erstellt ein Array für alle Pins für die die Notifikation erforderlich ist 
		        $PinNotify = unserialize(GetValueString($this->GetIDForIdent("PinNotify")));
		        $PinNotify[] = $data->Pin;
			SetValueString($this->GetIDForIdent("PinNotify"), serialize($PinNotify));
		        break;
		   case "set_usedpin":
		        // Erstellt ein Array für alle Pins die genutzt werden 
		        $PinUsed = unserialize(GetValueString($this->GetIDForIdent("PinUsed")));
		        $PinUsed[] = $data->Pin;
			SetValueString($this->GetIDForIdent("PinUsed"), serialize($PinUsed));
		        break;
		   case "get_pinupdate":
		   	$this->Get_PinUpdate();
		   	break;
		}
	    
	    return;
	  }
	
	 public function ReceiveData($JSONString) {
 
	    // Empfangene Daten vom I/O
	    $data = json_decode($JSONString);
	    IPS_LogMessage("ReceiveData", "Länge: ".strlen(utf8_decode($data->Buffer))." Test ".utf8_decode($data->Buffer));
	 	$this->ClientResponse(utf8_decode($data->Buffer));
	    // Hier werden die Daten verarbeitet
	 
	    // Weiterleitung zu allen Gerät-/Device-Instanzen
	    //$this->SendDataToChildren(json_encode(Array("DataID" => "{66164EB8-3439-4599-B937-A365D7A68567}", "Buffer" => $data->Buffer)));
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
	           	$this->ClientSocket(pack("LLLL", 19, GetValueInteger($this->GetIDForIdent("Handle")), $this->CalcBitmask(), 0));
		}
		// Pins ermitteln die genutzt werden
		SetValueString($this->GetIDForIdent("PinUsed"), "");
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_usedpin")));
	return;
	}
	
	
	// Setzt den gewaehlten Pin in den geforderten Modus
	private function Set_Mode($Pin, $Modus)
	{		
		$this->ClientSocket(pack("LLLL", 0, $Pin, $Modus, 0));
		IPS_LogMessage("SetMode Parameter : ",$Pin." , ".$Modus);  
	return;
	}

	// Setzt den gewaehlten Pin in den geforderten Modus
	private function Set_GlitchFilter($Pin, $Value)
	{		
		$this->ClientSocket(pack("LLLL", 97, $Pin, $Value, 0));
		IPS_LogMessage("SetGlitchFilter Parameter : ",$Pin." , ".$Value);  
	return;
	}
	
	// Dimmt den gewaehlten Pin
	private function Set_Intensity($Pin, $Value)
	{
		$this->ClientSocket(pack("LLLL", 5, $Pin, $Value, 0));
		IPS_LogMessage("Set Intensity : ",$Pin." , ".$Value);  
	return;
	}
	
	// Setzt die Farbe der RGB-LED
	private function Set_Intensity_RGB($Pin_R, $Value_R, $Pin_G, $Value_G, $Pin_B, $Value_B)
	{
		$this->ClientSocket(pack("LLLL", 5, $Pin_R, $Value_R, 0).pack("LLLL", 5, $Pin_G, $Value_G, 0).pack("LLLL", 5, $Pin_B, $Value_B, 0));
		IPS_LogMessage("Set Intensity RGB : ",$Pin_R." , ".$Value_R." ".$Pin_G." , ".$Value_G." ".$Pin_B." , ".$Value_B);  
	return;
	}
			
	// Schaltet den gewaehlten Pin
	private function Set_Status($Pin, $Value)
	{
		$this->ClientSocket(pack("LLLL", 5, $Pin, $Value, 0));
	return;
	}
	
	private function PinPossible($DataID, $InstanzID, $Pin)
	{
		// Prüfen, ob der gewählte GPIO bei dem Modell überhaupt vorhanden ist
		$PinPossible = unserialize(GetValueString($this->GetIDForIdent("PinPossible")));
		if (in_array($Pin, $PinPossible)) {
    			$result = true;
    			IPS_LogMessage("GPIO Auswahl: ","Gewählter Pin ist bei diesem Modell verfügbar");
		}
		else {
			$result = false;
			IPS_LogMessage("GPIO Auswahl: ","Gewählter Pin ist bei diesem Modell nicht verfügbar!");
		}
		// Prüfen ob der gewählten GPIO noch nicht in eine der anderen Instanzen genutzt wird
		$PinUsed = unserialize(GetValueString($this->GetIDForIdent("PinUsed")));
		if (in_array($Pin, $PinUsed)) {
    			$result = false;
    			IPS_LogMessage("GPIO Auswahl: ","Gewählter Pin wird schon genutzt!");
		}
		else {
			$result = true;
			IPS_LogMessage("GPIO Auswahl: ","Gewählter Pin ist verfügbar!");
			$PinUsed[] = $Pin;
			SetValueString($this->GetIDForIdent("PinUsed"), serialize($PinUsed));
		}
		
		$this->SendDataToChildren(json_encode(Array("DataID" =>"{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"pin_possible", "InstanzID" =>$InstanzID, "Result"=>$result)));
	return;
	}
	
	private function ClientSocket($message)
	{
		$res = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => utf8_encode($message))));  
		
	
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
		elseif ((strlen($Message) == 12) OR (strlen($Message) > 16)) {
			$Message = substr($Message, 0, 12);
			$response = unpack("L*", $Message);
			IPS_LogMessage("GPIO Notify: ","Meldung");		
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
  

}
?>
