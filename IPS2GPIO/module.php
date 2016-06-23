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
	    $this->RegisterPropertyBoolean("Open", false);
	    $this->RegisterPropertyInteger("Model", 0);
	
	  }
  
	  public function ApplyChanges()
	  {
	    //Never delete this line!
	    parent::ApplyChanges();
	    
	    $this->RegisterVariableString("User", "User");
	    $this->RegisterVariableString("Password", "Password");
	    $this->RegisterVariableString("PinPossible", "PinPossible");
	    $this->RegisterVariableString("PinUsed", "PinUsed");
	    $this->RemoteAccessData();
	    // Zwangskonfiguration des ClientSocket
            $ParentID = IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "IPAddress");
            if ($ParentID > 0)
            {
            	if (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('IPAddress'))
            	{
                	IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('IPAddress'));
           	}
            	if (IPS_GetProperty($ParentID, 'Port') <> $this->ReadPropertyInteger('Port'))
            	{
                	IPS_SetProperty($ParentID, 'Port', $this->ReadPropertyInteger('Port'));
           	}
            }
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
		    case 2:
		        echo "i ist gleich 2";
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
   		$IPSID = $this->InstanceID;
   		$result = exec('sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.$this->ReadPropertyString("IPAddress").' 8888 '.GetValue($this->GetIDForIdent("User")).' '.GetValue($this->GetIDForIdent("Password")).' '.$IPSID.' set_mode '.$Pin.' '.$Modus);
	return $result;
	}
	
	// Dimmt den gewaehlten Pin
	private function Set_Intensity($Pin, $Value)
	{
   		$IPSID = $this->InstanceID;
   		$result = exec('sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.$this->ReadPropertyString("IPAddress").' 8888 '.GetValue($this->GetIDForIdent("User")).' '.GetValue($this->GetIDForIdent("Password")).' '.$IPSID.' set_PWM_dutycycle '.$Pin.' '.$Value);
		$result = 'sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.$this->ReadPropertyString("IPAddress").' 8888 '.GetValue($this->GetIDForIdent("User")).' '.GetValue($this->GetIDForIdent("Password")).' '.$IPSID.' set_PWM_dutycycle '.$Pin.' '.$Value;
		SetValueString(47271, $result);
	return $result;
	}
	
	// Schaltet den gewaehlten Pin
	private function Set_Status($Pin, $Value)
	{
		$IPSID = $this->InstanceID;
		$result = exec('sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.$this->ReadPropertyString("IPAddress").' 8888 '.GetValue($this->GetIDForIdent("User")).' '.GetValue($this->GetIDForIdent("Password")).' '.$IPSID.' set_PWM_dutycycle '.$Pin.' '.$Value);
		
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
  
  	// Ermittelt den User und das Passwort für den Fernzugriff (nur RPi)
	private function RemoteAccessData()
	{
	   	$result = true;
	   	exec('sudo cat /root/.symcon', $ResultArray);
	   	If (strpos($ResultArray[0], "Licensee=") === false) {
			$result = false; }
		else {
	      		//$User = substr(strstr($ResultArray[0], "="),1); 
	      		SetValue($this->GetIDForIdent("User"), substr(strstr($ResultArray[0], "="),1));}
		If (strpos($ResultArray[(count($ResultArray))-1], "Password=") === false) {
			$result = false; }
		else {
	      		//$Pass = base64_decode(substr(strstr($ResultArray[(count($ResultArray))-1], "="),1)); 
			SetValue($this->GetIDForIdent("Password"), base64_decode(substr(strstr($ResultArray[(count($ResultArray))-1], "="),1)));}
	return $result;
	}
}
?>
