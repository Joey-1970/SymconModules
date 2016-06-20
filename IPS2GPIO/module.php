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
    
  }
  
  // Beispiel innerhalb einer Gateway/Splitter Instanz
public function ForwardData($JSONString) {
 
    // Empfangene Daten von der Device Instanz
    $data = json_decode($JSONString);
    IPS_LogMessage("ForwardData", utf8_decode($data->Buffer));
 
    // Hier würde man den Buffer im Normalfall verarbeiten
    // z.B. CRC prüfen, in Einzelteile zerlegen
 
    // Weiterleiten zur I/O Instanz
    $resultat = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => $data->Buffer)));
 
    // Weiterverarbeiten und durchreichen
    return $resultat;

  
  public function RequestAction($Ident, $Value) 
  {
    switch($Ident) {
        case "Open":
            If ($Value = True)
            	{
            		$this->SetStatus(101);
            		$this->ConnectionTest();
            	}
 	   else
 	   	{
 	   		$this->SetStatus(104);
 	   	}
            //Neuen Wert in die Statusvariable schreiben
            SetValue($this->GetIDForIdent($Ident), $Value);
            break;
        default:
            throw new Exception("Invalid Ident");
    }
 
   }
  
	private function ConnectionTest()
	{
	      If (Sys_Ping($this->ReadPropertyInteger("IPAddress"), 2000)) 
	      {
			Echo "PC erreichbar";
			$status = @fsockopen($this->ReadPropertyInteger("IPAddress"), 8888, $errno, $errstr, 10);
				if (!$status) 
				{
					echo "Port geschlossen";
					$this->SetStatus(104);
	   			}
	   			else 
	   			{
	   				fclose($status);
					echo "Port offen";
					$this->SetStatus(102);
	   			}
		}
		else 
		{
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
