<?
    // Klassendefinition
    class IPS2GPIO_Dimmer extends IPSModule 
    {
 
        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) 
        {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
 
            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
           
            $this->RegisterPropertyInteger("Pin", 2);
 	    $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");	
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
            
            //Connect to available splitter or create a new one
	   $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
	   
	   //Status-Variablen anlegen
	   $this->RegisterVariableBoolean("Status", "Status", "~Switch", 1);
           $this->EnableAction("Status");
           $this->RegisterVariableInteger("Intensity", "Intensity", "~Intensity.255");
           $this->EnableAction("Intensity");
           $this->Set_Mode();
        }
	
	public function ReceiveData($JSONString) 
	{
    	// Empfangene Daten vom Gateway/Splitter
    	$data = json_decode($JSONString);
    	IPS_LogMessage("ReceiveData", utf8_decode($data->Buffer));
 
    	// Datenverarbeitung und schreiben der Werte in die Statusvariablen
    	//SetValue($this->GetIDForIdent("Value"), $data->Buffer);
	}
	// Beginn der Funktionen
	
	// Setzt den gewaehlten Pin in den Output-Modus
	private function Set_Mode()
	{
   		$this->SendDataToParent(json_encode(Array("DataID" => "{A0DAAF26-4A2D-4350-963E-CC02E74BD414}", "Function" => "set_mode", "Pin" => $this->ReadPropertyInteger("Pin"), "Modus" => "OUT")));
   		//$IPSID = $this->InstanceID;
   		//list($result, $IPSUser, $IPSPass) = $this->RemoteAccessData();
   		//$IPSUser = IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "User");
		//$IPSPass = IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "Password");
		//$result = "";
   		//$result = exec('sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "IPAddress").' 8888 '.$IPSUser.' '.$IPSPass.' '.$IPSID.' set_mode '.$this->ReadPropertyInteger("Pin").' OUT');
	return;
	}
	
	// Dimmt den gewaehlten Pin
	public function Set_Intensity($value)
	{
   		SetValue($this->GetIDForIdent("Intensity"), $value);
		If ($this->GetValue(GetIDForIdent("Status")) == true) {
			$IPSID = $this->InstanceID;
   			//list($result, $IPSUser, $IPSPass) = $this->RemoteAccessData();
			$IPSUser = IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "User");
			$IPSPass = IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "Password");
			$result = "";
   			$result = exec('sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "IPAddress").' 8888 '.$IPSUser.' '.$IPSPass.' '.$IPSID.' set_PWM_dutycycle '.$this->ReadPropertyInteger("Pin").' '.$value);
		}
	return $result;
	}
	
	// Dimmt den gewaehlten Pin
	public function Set_Status($value)
	{
		SetValue($this->GetIDForIdent("Status"), $value);
		$IPSID = $this->InstanceID;
   		//list($result, $IPSUser, $IPSPass) = $this->RemoteAccessData();
		$IPSUser = IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "User");
		$IPSPass = IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "Password");
		$result = "";
		If ($this->GetValue(GetIDForIdent("Status")) == true) {
			$result = exec('sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "IPAddress").' 8888 '.$IPSUser.' '.$IPSPass.' '.$IPSID.' set_PWM_dutycycle '.$this->ReadPropertyInteger("Pin").' '.GetValue($this->GetIDForIdent("Intensity")));
		}
		else {
   			$result = exec('sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.IPS_GetProperty((IPS_GetInstance($this->InstanceID)['ConnectionID']), "IPAddress").' 8888 '.$IPSUser.' '.$IPSPass.' '.$IPSID.' set_PWM_dutycycle '.$this->ReadPropertyInteger("Pin").' 0');
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
	      		$User = substr(strstr($ResultArray[0], "="),1); }
		If (strpos($ResultArray[(count($ResultArray))-1], "Password=") === false) {
			$result = false; }
		else {
	      		$Pass = base64_decode(substr(strstr($ResultArray[(count($ResultArray))-1], "="),1)); }
	return array($result, $User, $Pass);
	}

    }
?>
