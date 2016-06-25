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
	    
	    $this->ConnectParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");  
	    // Modul-Eigenschaftserstellung
	    $this->RegisterPropertyString("IPAddress", "127.0.0.1");
	    $this->RegisterPropertyInteger("Model", 0);
	
	  }
  
	  public function ApplyChanges()
	  {
		//Never delete this line!
		parent::ApplyChanges();
		    
		$this->RegisterVariableString("PinPossible", "PinPossible");
		$this->RegisterVariableString("PinUsed", "PinUsed");
		
		// Zwangskonfiguration des ClientSocket
	        $ParentID = (IPS_GetInstance($this->InstanceID)['ConnectionID']);
	        if ($ParentID > 0)
	        {
	            	if (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('IPAddress'))
	            	{
	                	IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('IPAddress'));
	           	}
	            	if (IPS_GetProperty($ParentID, 'Port') <> 8888)
	            	{
	                	IPS_SetProperty($ParentID, 'Port', 8888);
	           	}
	        }
           	$Typ[0] = array(0, 1, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 21, 22, 23, 24, 25);	
           	$Typ[1] = array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27);
           	$Typ[2] = array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);
           	SetValueString($this->GetIDForIdent("PinPossible"), serialize ($Typ[$this->ReadPropertyInteger('Model')]));
	  }
  	  
  	  public function ReceiveData($JSONString) 
  	  {
 		// Empfangene Daten vom I/O
		$data = json_decode($JSONString);
		IPS_LogMessage("ReceiveData", utf8_decode($data->Buffer));
		 
		// Hier werden die Daten verarbeitet
		 
		// Weiterleitung zu allen Gerät-/Device-Instanzen
		//$this->SendDataToChildren(json_encode(Array("DataID" => "{66164EB8-3439-4599-B937-A365D7A68567}", "Buffer" => $data->Buffer)));
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
		        $this->Set_Intensity($data->Pin_R, $data->Value_R, $data->Pin_G, $data->Value_G, $data->Pin_B, $data->Value_B);
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
		$result = CSCK_SendText(IPS_GetInstance($this->InstanceID)['ConnectionID'], pack("LLLL", 0, $Pin, $Modus, 0));
	return $result;
	}
	
	// Dimmt den gewaehlten Pin
	private function Set_Intensity($Pin, $Value)
	{
		$result = CSCK_SendText(IPS_GetInstance($this->InstanceID)['ConnectionID'], pack("LLLL", 5, $Pin, $Value, 0));
	return $result;
	}
	
	// Setzt die Farbe der RGB-LED
	private function Set_Intensity_RGB($Pin_R, $Value_R, $Pin_G, $Value_G, $Pin_B, $Value_B)
	{
		$result = CSCK_SendText(IPS_GetInstance($this->InstanceID)['ConnectionID'], pack("LLLL", 5, $Pin_R, $Value_R, 0).pack("LLLL", 5, $Pin_G, $Value_G, 0).pack("LLLL", 5, $Pin_B, $Value_B, 0));
	return $result;
	}
	
	// Schaltet den gewaehlten Pin
	private function Set_Status($Pin, $Value)
	{
		$result = CSCK_SendText(IPS_GetInstance($this->InstanceID)['ConnectionID'], pack("LLLL", 5, $Pin, $Value, 0));
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
	
	private function ConnectionTest()
	{
	      If (Sys_Ping($this->ReadPropertyInteger("IPAddress"), 2000)) {
			Echo "PC erreichbar";
			$status = @fsockopen($this->ReadPropertyInteger("IPAddress"), 8888, $errno, $errstr, 10);
				if (!$status) {
					echo "Port geschlossen";
					$this->SetStatus(104);
	   			}
	   			else {
	   				fclose($status);
					echo "Port offen";
					$this->SetStatus(102);
	   			}
		}
		else {
			Echo "PC nicht erreichbar";
			$this->SetStatus(104);
		}
	}
  

}
?>
