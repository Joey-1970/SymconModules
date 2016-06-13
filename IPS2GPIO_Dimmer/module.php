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
	// Beginn der Funktionen
	
	// Setzt einen Pin in einen bestimmten Modus
	private function Set_Mode()
	{
   		$RPiIP = IPS_GetProperty((IPS_GetInstance(47271)['ConnectionID']), "IPAddress");
   		$RPIPort = 8888;
   		$IPSID = 29149;
   		$GPIOPin = $this->ReadPropertyInteger("Pin");
   		list($result, $IPSUser, $IPSPass) = $this->RemoteAccessData();
		$result = "";
		SetValueString(47271, 'sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.$RPiIP.' '.$RPiPort.' '.$IPSUser.' '.$IPSPass.' '.$IPSID.' set_mode '.$GPIOPin.' OUT');
   		$result = exec('sudo python '.IPS_GetKernelDir().'modules/SymconModules/IPS2GPIO/ips2gpio.py '.$RPiIP.' '.$RPiPort.' '.$IPSUser.' '.$IPSPass.' '.$IPSID.' set_mode '.$GPIOPin.' OUT');
	return $result;
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
