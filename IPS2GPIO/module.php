<?
class IPS2GPIO_IO extends IPSModule
{
	private $Socket = false;
	
	public function __construct($InstanceID) 
	{
            	parent::__construct($InstanceID);
	}
	
	public function __destruct()
	{
		if ($this->Socket) {
		    	socket_close($this->Socket);
		}
	} 

	public function Create() 
	{
	    	// Diese Zeile nicht entfernen
	    	parent::Create();
	    
	    	// Modul-Eigenschaftserstellung
	    	$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyString("IPAddress", "127.0.0.1");
	    	$this->RegisterPropertyString("User", "User");
	    	$this->RegisterPropertyString("Password", "Passwort");
		$this->RegisterPropertyInteger("MUX", 0);
		$this->RegisterPropertyInteger("OW", 0);
		$this->RegisterPropertyInteger("I2C0", 0);
		$this->RegisterPropertyString("Raspi_Config", "");
		$this->RegisterPropertyString("I2C_Devices", "");
		$this->RegisterPropertyString("OW_Devices", "");
		$this->RegisterPropertyBoolean("Multiplexer", false);
		$this->RegisterPropertyBoolean("AutoRestart", true);
	    	$this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
		$PinNotify = array();
		$this->SetBuffer("PinNotify", serialize($PinNotify));
		$PinPossible = array();
		$this->SetBuffer("PinPossible", serialize($PinPossible));
		$PinUsed = array();
		$this->SetBuffer("PinUsed", serialize($PinUsed));
		$OWDeviceArray = array();
		$this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));
		$I2C_HandleData = array();
		$this->SetBuffer("I2C_Handle", serialize($I2C_HandleData));
		$this->RegisterPropertyBoolean("AudioDAC", false);
		
		// Statusvariablen anlegen
		$this->RegisterVariableString("Hardware", "Hardware", "", 10);
		$this->DisableAction("Hardware");

		$this->RegisterVariableInteger("SoftwareVersion", "SoftwareVersion", "", 20);
		$this->DisableAction("SoftwareVersion");

		$this->RegisterVariableInteger("LastKeepAlive", "Letztes Keep Alive", "~UnixTimestamp", 30);
		$this->DisableAction("LastKeepAlive");

		$this->RegisterVariableBoolean("PigpioStatus", "Pigpio Status", "~Alert.Reversed", 40);
		$this->DisableAction("PigpioStatus");
	}
  	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 200, "icon" => "error", "caption" => "Instanz ist fehlerhaft");
		
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "CheckBox", "name" => "Open", "caption" => "Aktiv");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
 		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "IPAddress", "caption" => "IP");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Zugriffsdaten des Raspberry Pi SSH:");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "User", "caption" => "User");
		$arrayElements[] = array("type" => "PasswordTextBox", "name" => "Password", "caption" => "Password");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Detaillierung der genutzten I²C-Schnittstelle:");
		
		If (($this->ConnectionTest()) AND ($this->SearchSpecialI2CDevices(112) == true))  {
			$arrayOptions = array();
			$arrayOptions[] = array("label" => "Kein MUX", "value" => 0);
			$arrayOptions[] = array("label" => "TCA9548a Adr. 112/0x70", "value" => 1);
			$arrayOptions[] = array("label" => "PCA9542 Adr. 112/0x70", "value" => 2);
			$arrayElements[] = array("type" => "Select", "name" => "MUX", "caption" => "MUX-Auswahl", "options" => $arrayOptions );
		}
		else {
			$arrayElements[] = array("type" => "Label", "label" => "Es wurde kein MUX gefunden.");
		}
		$arrayOptions = array();
		$arrayElements[] = array("type" => "Label", "label" => "Nutzung der I²C-Schnittstelle 0:");
		$arrayOptions[] = array("label" => "Nein", "value" => 0);
		$arrayOptions[] = array("label" => "Ja", "value" => 1);
		$arrayElements[] = array("type" => "Select", "name" => "I2C0", "caption" => "I²C 0", "options" => $arrayOptions );
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		If (($this->ConnectionTest()) AND ($this->SearchSpecialI2CDevices(24) == true))  {
			$arrayOptions = array();
			$arrayOptions[] = array("label" => "Kein DS2482", "value" => 0);
			$arrayOptions[] = array("label" => "DS2482 Adr. 24/0x18", "value" => 1);
			$arrayElements[] = array("type" => "Select", "name" => "OW", "caption" => "1-Wire Auswahl", "options" => $arrayOptions );
		}
		else {
			$arrayElements[] = array("type" => "Label", "label" => "Es wurde kein DS2482 gefunden.");
		}
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Analyse der Raspberry Pi Konfiguration:");
		$arraySort = array();
		$arraySort[] = array("column" => "Typ", "direction" => "ascending");
		$arrayColumns = array();
		$arrayColumns[] = array("label" => "Service", "name" => "ServiceTyp", "width" => "200px", "add" => "");
		$arrayColumns[] = array("label" => "Status", "name" => "ServiceStatus", "width" => "auto", "add" => "");
		$ServiceArray = array();
		$ServiceArray = unserialize($this->CheckConfig());
		$arrayValues[] = array("ServiceTyp" => "I²C", "ServiceStatus" => $ServiceArray["I2C"]["Status"], "rowColor" => $ServiceArray["I2C"]["Color"]);
		$arrayValues[] = array("ServiceTyp" => "Serielle Schnittstelle (RS232)", "ServiceStatus" => $ServiceArray["Serielle Schnittstelle"]["Status"], "rowColor" => $ServiceArray["Serielle Schnittstelle"]["Color"]);
		$arrayValues[] = array("ServiceTyp" => "Shell Zugriff", "ServiceStatus" => $ServiceArray["Shell Zugriff"]["Status"], "rowColor" => $ServiceArray["Shell Zugriff"]["Color"]);
		$arrayValues[] = array("ServiceTyp" => "PIGPIO Server", "ServiceStatus" => $ServiceArray["PIGPIO Server"]["Status"], "rowColor" => $ServiceArray["PIGPIO Server"]["Color"]);
		$arrayValues[] = array("ServiceTyp" => "1-Wire-Server", "ServiceStatus" => $ServiceArray["1-Wire-Server"]["Status"], "rowColor" => $ServiceArray["1-Wire-Server"]["Color"]);
		$arrayElements[] = array("type" => "List", "name" => "Raspi_Config", "caption" => "Konfiguration", "rowCount" => 5, "add" => false, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");		
		// Tabelle für die gefundenen I²C-Devices
		$arrayColumns = array();
		$arrayColumns[] = array("label" => "Typ", "name" => "DeviceTyp", "width" => "120px", "add" => "");
		$arrayColumns[] = array("label" => "Adresse", "name" => "DeviceAddress", "width" => "60px", "add" => "");
		$arrayColumns[] = array("label" => "Bus", "name" => "DeviceBus", "width" => "60px", "add" => "");
		$arrayColumns[] = array("label" => "Instanz ID", "name" => "InstanceID", "width" => "70px", "add" => "");
		$arrayColumns[] = array("label" => "Status", "name" => "DeviceStatus", "width" => "auto", "add" => "");		
		// Tabelle für die gefundenen 1-Wire-Devices
		$arrayOWColumns = array();
		$arrayOWColumns[] = array("label" => "Typ", "name" => "DeviceTyp", "width" => "120px", "add" => "");
		$arrayOWColumns[] = array("label" => "Serien-Nr.", "name" => "DeviceSerial", "width" => "120px", "add" => "");
		$arrayOWColumns[] = array("label" => "Instanz ID", "name" => "InstanceID", "width" => "70px", "add" => "");
		$arrayOWColumns[] = array("label" => "Status", "name" => "DeviceStatus", "width" => "auto", "add" => "");
		
		If (($this->ConnectionTest()) AND ($this->ReadPropertyBoolean("Open") == true) AND ($this->GetBuffer("I2C_Enabled") == 1)) {
			// I²C-Devices einlesen und in das Values-Array kopieren
			$DeviceArray = array();
			$DeviceArray = unserialize($this->SearchI2CDevices());
			$arrayValues = array();
			If (count($DeviceArray , COUNT_RECURSIVE) >= 4) {
				for ($i = 0; $i < Count($DeviceArray); $i++) {
					$arrayValues[] = array("DeviceTyp" => $DeviceArray[$i][0], "DeviceAddress" => $DeviceArray[$i][1], "DeviceBus" => $DeviceArray[$i][2], "InstanceID" => $DeviceArray[$i][3], "DeviceStatus" => $DeviceArray[$i][4], "rowColor" => $DeviceArray[$i][5]);
				}
				$arrayElements[] = array("type" => "List", "name" => "I2C_Devices", "caption" => "I²C-Devices", "rowCount" => 5, "add" => false, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);
				$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
			}
			else {
				$arrayElements[] = array("type" => "Label", "label" => "Es wurden keine I²C-Devices gefunden.");
			}
			
			If ($this->GetBuffer("OW_Handle") >= 0) {
				// 1-Wire-Devices einlesen und in das Values-Array kopieren
				$OWDeviceArray = array();
				$this->OWSearchStart();
				$OWDeviceArray = unserialize($this->GetBuffer("OWDeviceArray"));
				If (count($OWDeviceArray , COUNT_RECURSIVE) >= 4) {
					$arrayOWValues = array();
					for ($i = 0; $i < Count($OWDeviceArray); $i++) {
						$arrayOWValues[] = array("DeviceTyp" => $OWDeviceArray[$i][0], "DeviceSerial" => $OWDeviceArray[$i][1], "InstanceID" => $OWDeviceArray[$i][2], "DeviceStatus" => $OWDeviceArray[$i][3], "rowColor" => $OWDeviceArray[$i][4]);
					}
					$arrayElements[] = array("type" => "List", "name" => "OW_Devices", "caption" => "1-Wire-Devices", "rowCount" => 5, "add" => false, "delete" => false, "sort" => $arraySort, "columns" => $arrayOWColumns, "values" => $arrayOWValues);
					$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
				}
				else {
					$arrayElements[] = array("type" => "Label", "label" => "Es wurden keine 1-Wire-Devices gefunden.");
				}
			}
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		}
		If (($this->ConnectionTest()) AND ($this->ReadPropertyBoolean("Open") == true)) {
			$arrayElements[] = array("type" => "Label", "label" => "Führt einen Restart des PIGPIO aus:");
			$arrayElements[] = array("type" => "Button", "label" => "PIGPIO Restart", "onClick" => 'I2G_PIGPIOD_Restart($id);');
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		}
		$arrayElements[] = array("type" => "Label", "label" => "Wird ein Audio Hat wie z.B. Hifiberry parallel verwendet, muss diese Option gewählt werden.");
		$arrayElements[] = array("type" => "Label", "label" => "Die Nutzung von PWM (Dimmer, RGB, RGBW usw.) ist dann nicht möglich!");
		$arrayElements[] = array("type" => "CheckBox", "name" => "AudioDAC", "caption" => "Vorhanden");
		$arrayElements[] = array("type" => "Label", "label" => "Führt einen automatischren Restart des PIGPIO aus:");
		$arrayElements[] = array("type" => "CheckBox", "name" => "AutoRestart", "caption" => "Auto Restart");	
		
		$arrayActions = array();
		If ($this->ReadPropertyBoolean("Open") == true) {   
			$arrayActions[] = array("type" => "Label", "label" => "Aktuell sind keine Testfunktionen definiert");
		}
		else {
			$arrayActions[] = array("type" => "Label", "label" => "Diese Funktionen stehen erst nach Eingabe und Übernahme der erforderlichen Daten zur Verfügung!");
		}
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	} 
	
	
	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		// Nachrichten abonnieren
		// Kernel
	        $this->RegisterMessage(0, 10100); // Alle Kernelmessages (10103 muss im MessageSink ausgewertet werden.)
		
		If (IPS_GetKernelRunlevel() == 10103) {
			$this->SetBuffer("ModuleReady", 0);
			$this->SetBuffer("Handle", -1);
			$this->SetBuffer("HardwareRev", 0);
			$Typ = array(2 => 2, 3, 4, 7 => 7, 8, 9, 10, 11, 14 => 14, 15, 17 => 17, 18, 22 => 22, 23, 24, 25, 27 => 27);
			$this->SetBuffer("PinPossible", serialize($Typ));
			$this->SetBuffer("PinI2C", "");
			$this->SetBuffer("I2CSearch", 0);
			$this->SetBuffer("I2C_Enabled", 0);
			$this->SetBuffer("I2C_0_Configured", 0);
			$this->SetBuffer("I2C_1_Configured", 0);
			$this->SetBuffer("Serial_Configured", 0);
			$this->SetBuffer("Serial_Display_Configured", 0);
			$this->SetBuffer("Serial_Display_RxD", -1);
			$this->SetBuffer("Serial_GPS_Configured", 0);
			$this->SetBuffer("Serial_GPS_RxD", -1);
			$this->SetBuffer("Serial_GPS_Data", "");
			$this->SetBuffer("Serial_SDS011_Configured", 0);
			$this->SetBuffer("Serial_SDS011_RxD", -1);
			$this->SetBuffer("1Wire_Configured", 0);
			$this->SetBuffer("SerialNotify", 0);
			$this->SetBuffer("SerialScriptID", -1);
			$this->SetBuffer("Default_I2C_Bus", 1);
			$this->SetBuffer("Default_Serial_Bus", 0);
			$this->SetBuffer("MUX_Handle", -1);
			$this->SetBuffer("OW_Handle", -1);
			$this->SetBuffer("NotifyBitmask", -1);
			$this->SetBuffer("LastNotify", -1);
			$PinNotify = array();
			$this->SetBuffer("PinNotify", serialize($PinNotify));
			
			$this->SetBuffer("owLastDevice", 0);
			$this->SetBuffer("owLastDiscrepancy", 0);
			$this->SetBuffer("owTripletDirection", 1);
			$this->SetBuffer("owTripletFirstBit", 0);
			$this->SetBuffer("owTripletSecondBit", 0);
			$this->SetBuffer("owDeviceAddress_0", 0);
			$this->SetBuffer("owDeviceAddress_1", 0);
			
			$this->SetBuffer("IR_RC5_Toggle", 0);
			
			$ParentID = $this->GetParentID();
		        // Änderung an den untergeordneten Instanzen
		        $this->RegisterMessage($this->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
		        $this->RegisterMessage($this->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
		        // INSTANCEMESSAGE
		        $this->RegisterMessage($ParentID, 10505); // Status hat sich geändert
	
			If ($ParentID > 0) {
				If (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('IPAddress')) {
		                	IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('IPAddress'));
				}
				If (IPS_GetProperty($ParentID, 'Port') <> 8888) {
		                	IPS_SetProperty($ParentID, 'Port', 8888);
				}
				If (IPS_GetProperty($ParentID, 'Open') <> $this->ReadPropertyBoolean("Open")) {
		                	IPS_SetProperty($ParentID, 'Open', $this->ReadPropertyBoolean("Open"));
				}
				If (IPS_GetName($ParentID) == "Client Socket") {
		                	IPS_SetName($ParentID, "IPS2GPIO");
				}
				if(IPS_HasChanges($ParentID))
				{
				    	$Result = @IPS_ApplyChanges($ParentID);
					If ($Result) {
						$this->SendDebug("ApplyChanges", "Einrichtung des Client Socket erfolgreich", 0);
					}
					else {
						$this->SendDebug("ApplyChanges", "Einrichtung des Client Socket nicht erfolgreich!", 0);
					}
				}
			}
	
			If (($this->ConnectionTest()) AND ($this->ReadPropertyBoolean("Open") == true))  {
				$this->SendDebug("ApplyChanges", "Starte Vorbereitung", 0);
				If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == false) {
					SetValueBoolean($this->GetIDForIdent("PigpioStatus"), true);
				}
				$this->CheckConfig();
				// Hardware und Softwareversion feststellen
				$this->CommandClientSocket(pack("L*", 17, 0, 0, 0).pack("L*", 26, 0, 0, 0), 32);
				
				// Alle Waveforms löschen
				$this->CommandClientSocket(pack("L*", 27, 0, 0, 0), 16);
				
				// I2C-Handle zurücksetzen
				If ($this->GetBuffer("I2C_Enabled") == 1) {
					$this->ResetI2CHandle(0);
				}
							
				// Notify Starten
				$Handle = $this->ClientSocket(pack("L*", 99, 0, 0, 0));
				$this->SetBuffer("Handle", $Handle);
				$this->SendDebug("Handle", (int)$Handle, 0);
				
				// MUX einrichten
				If (($this->ReadPropertyInteger("MUX") > 0) AND ($this->GetBuffer("I2C_Enabled") == 1)) {
					$MUX_Handle = $this->CommandClientSocket(pack("L*", 54, 1, 112, 4, 0), 16);
					$this->SetBuffer("MUX_Handle", $MUX_Handle);
					$this->SendDebug("MUX Handle", $MUX_Handle, 0);
					$this->SetBuffer("MUX_Channel", -1);
					If ($MUX_Handle >= 0) {
						// MUX setzen
						$this->SetMUX(0);
					}
				}
				
				// OW einrichten
				If (($this->ReadPropertyInteger("OW") > 0) AND ($this->GetBuffer("I2C_Enabled") == 1)) {
					$OW_Handle = $this->CommandClientSocket(pack("L*", 54, 1, 24, 4, 0), 16);
					$this->SetBuffer("OW_Handle", $OW_Handle);
					$this->SendDebug("OW Handle", $OW_Handle, 0);
				}
			
				$I2C_DeviceHandle = array();
				$this->SetBuffer("I2C_Handle", serialize($I2C_DeviceHandle));
				
				// Vorbereitung beendet
				$this->SendDebug("ApplyChanges", "Beende Vorbereitung", 0);
				$this->SetBuffer("ModuleReady", 1);
				
				// Ermitteln der genutzten I2C-Adressen
				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_used_i2c")));
				// Ermitteln der sonstigen Seriellen Schnittstellen-Daten
				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_serial")));
				// Ermitteln der sonstigen genutzen GPIO
				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_usedpin")));
				// Start-trigger für andere Instanzen (BT, RPi)
				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_start_trigger")));

				If ($Handle >= 0) {
					// Notify setzen
					$this->CommandClientSocket(pack("L*", 19, $Handle, $this->CalcBitmask(), 0), 16);
				}
					
				$this->SetStatus(102);
				
			}
			else {
				$this->SetStatus(104);
				$this->SetBuffer("ModuleReady", 0);
			}
		}
		else {
			return;
		}
	}

	public function GetConfigurationForParent()
	{
	  	$JsonArray = array( "Host" => $this->ReadPropertyString('IPAddress'), "Port" => 8888, "Open" => $this->ReadPropertyBoolean("Open"));
	  	$Json = json_encode($JsonArray);        
	  	return $Json;
	}  
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
        IPS_LogMessage("IPS2GPIO MessageSink", "Message from SenderID ".$SenderID." with Message ".$Message."\r\n Data: ".print_r($Data, true));
		switch ($Message) {
			case 10100:
				If ($Data[0] == 10103) {
					$this->ApplyChanges();
				}
				break;
			case 11101:
				IPS_LogMessage("IPS2GPIO MessageSink", "Instanz ".$SenderID." wurde verbunden");
				break;
			case 11102:
				$this->SendDebug("MessageSink", "Instanz  ".$SenderID." wurde getrennt", 0);
				//IPS_LogMessage("IPS2GPIO MessageSink", "Instanz  ".$SenderID." wurde getrennt");
				// Prüfung für die Pin-Belegung
				$PinUsed = array();
				$PinUsed = unserialize($this->GetBuffer("PinUsed"));
				foreach($PinUsed as $Pin => $InstanceID ){
					If ($InstanceID == $SenderID) {
						unset($PinUsed[$Pin]);
						$this->SendDebug("MessageSink", "Pin ".$Pin." wurde freigegeben", 0);
				    	}
				}
				$this->SetBuffer("PinUsed", serialize($PinUsed));
				
				break;	
			case 10505:
				If ($Data[0] == 102) {
					$this->ApplyChanges();
				}
				elseif ($Data[0] == 200) {
					If ($this->ReadPropertyBoolean("AutoRestart") == true) {
						$this->ConnectionTest();
					}
					If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == true) {
						SetValueBoolean($this->GetIDForIdent("PigpioStatus"), false);
					}
				}
				break;
		}
    	}
	  
	 public function ForwardData($JSONString) 
	 {
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	$Result = -999;
	 	switch ($data->Function) {
		    // GPIO Kommunikation
		case "set_PWM_dutycycle":
		    	// Dimmt einen Pin
		    	If ($data->Pin >= 0) {
		        	$Result = $this->CommandClientSocket(pack("L*", 5, $data->Pin, $data->Value, 0), 16);
		        }
		        break;
		case "get_PWM_dutycycle":
		    	// Dimmt einen Pin
		    	If ($data->Pin >= 0) {
				$Result = $this->CommandClientSocket(pack("L*", 83, $data->Pin, 0, 0), 16);
		        }
		        break;
		case "set_PWM_dutycycle_RGB":
		    	// Setzt die RGB-Farben
		    	If (($data->Pin_R >= 0) AND ($data->Pin_G >= 0) AND ($data->Pin_B >= 0)) {
		        	$Result = $this->CommandClientSocket(pack("L*", 5, $data->Pin_R, $data->Value_R, 0).pack("L*", 5, $data->Pin_G, $data->Value_G, 0).pack("L*", 5, $data->Pin_B, $data->Value_B, 0), 48);
		    	}
		        break;
		case "get_PWM_dutycycle_RGB":
		    	// Dimmt einen Pin
		    	If (($data->Pin_R >= 0) AND ($data->Pin_G >= 0) AND ($data->Pin_B >= 0)) {
				$Color = Array();
				$Color[] = $this->CommandClientSocket(pack("L*", 83, $data->Pin_R, 0, 0), 16);
				$Color[] = $this->CommandClientSocket(pack("L*", 83, $data->Pin_G, 0, 0), 16);
				$Color[] = $this->CommandClientSocket(pack("L*", 83, $data->Pin_B, 0, 0), 16);
				$Result = serialize($Color);
		        }
		        break;
		case "set_PWM_dutycycle_RGBW":
		    	// Setzt die RGBW-Farben
		    	If (($data->Pin_R >= 0) AND ($data->Pin_G >= 0) AND ($data->Pin_B >= 0) AND ($data->Pin_W >= 0)) {
		        	$Result = $this->CommandClientSocket(pack("L*", 5, $data->Pin_R, $data->Value_R, 0).pack("L*", 5, $data->Pin_G, $data->Value_G, 0).pack("L*", 5, $data->Pin_B, $data->Value_B, 0).pack("L*", 5, $data->Pin_W, $data->Value_W, 0), 64);
		    	}
		        break;
		case "get_PWM_dutycycle_RGBW":
		    	// Dimmt einen Pin
		    	If (($data->Pin_R >= 0) AND ($data->Pin_G >= 0) AND ($data->Pin_B >= 0) AND ($data->Pin_W >= 0)) {
		        	$Color = Array();
				$Color[] = $this->CommandClientSocket(pack("L*", 83, $data->Pin_R, 0, 0), 16);
				$Color[] = $this->CommandClientSocket(pack("L*", 83, $data->Pin_G, 0, 0), 16);
				$Color[] = $this->CommandClientSocket(pack("L*", 83, $data->Pin_B, 0, 0), 16);
				$Color[] = $this->CommandClientSocket(pack("L*", 83, $data->Pin_W, 0, 0), 16);
				$Result = serialize($Color);
		        }
		        break;
		case "get_value":
		    	// Liest den Pin
		    	If ($data->Pin >= 0) {
		    		//$this->SendDebug("get_value", "Pin: ".$data->Pin, 0);
		    		$Result = $this->CommandClientSocket(pack("L*", 3, $data->Pin, 0, 0), 16);
 		    	}
		        break;
		case "set_value":
		    	// Schaltet den Pin
		    	If ($data->Pin >= 0) {
		    		//IPS_LogMessage("IPS2GPIO SetValue Parameter : ",$data->Pin." , ".$data->Value); 
		    		$Result = $this->CommandClientSocket(pack("L*", 4, $data->Pin, $data->Value, 0), 16);
		    	}
		        break;
		case "set_trigger":
		    	// Setzt einen Trigger
		    	If ($data->Pin >= 0) {
		        	//IPS_LogMessage("IPS2GPIO SetTrigger Parameter : ",$data->Pin." , ".$data->Time);
		        	$Result = $this->CommandClientSocket(pack("L*", 37, $data->Pin, $data->Time, 4, 1), 16);
		    	}
		        break;
		case "set_servo":
		    	// Setzt ein Servo S/SERVO u v - Set GPIO servo pulsewidth
		    	If ($data->Pin >= 0) {
		        	//IPS_LogMessage("IPS2GPIO SetTrigger Parameter : ",$data->Pin." , ".$data->Time);
		        	$Result = $this->CommandClientSocket(pack("L*", 8, $data->Pin, $data->Value, 0), 16);
		    	}
		        break;
		case "get_servo":
		    	// Servo GPW u - Get GPIO servo pulsewidth
		    	If ($data->Pin >= 0) {
		        	//IPS_LogMessage("IPS2GPIO SetTrigger Parameter : ",$data->Pin." , ".$data->Time);
				$Result = $this->CommandClientSocket(pack("L*", 84, $data->Pin, 0, 0), 16);
		    	}
		        break;
				
		    
		// interne Kommunikation
		case "set_usedpin":
		   	If ($this->GetBuffer("ModuleReady") == 1) {
				If ($data->Pin >= 0) {
					// Prüfen, ob der gewählte GPIO bei dem Modell überhaupt vorhanden ist
					$PinPossible = array();
					$PinPossible = unserialize($this->GetBuffer("PinPossible"));
					if (in_array($data->Pin, $PinPossible)) {
						//IPS_LogMessage("IPS2GPIO Pin: ","Gewählter Pin ist bei diesem Modell verfügbar");
						$this->SendDebug("set_usedpin", "Gewaehlter Pin ".$data->Pin." ist bei diesem Modell verfuegbar", 0);
						$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>102, "HardwareRev"=>$this->GetBuffer("HardwareRev"), "InstanceID" => $data->InstanceID )));
					}
					else {
						$this->SendDebug("set_usedpin", "Gewaehlter Pin ".$data->Pin." ist bei diesem Modell nicht verfuegbar!", 0);
						IPS_LogMessage("IPS2GPIO Pin: ","Gewählter Pin ".$data->Pin." ist bei diesem Modell nicht verfügbar!");
						$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>201, "HardwareRev"=>$this->GetBuffer("HardwareRev"), "InstanceID" => $data->InstanceID )));
					}
					// Erstellt ein Array für alle Pins die genutzt werden 	
					$PinUsed = array();
					$PinUsed = unserialize($this->GetBuffer("PinUsed"));
					// Prüft, ob der ausgeählte Pin schon einmal genutzt wird
					If (is_array($PinUsed)) {
						If (array_key_exists(intval($data->Pin), $PinUsed)) {
							If (($PinUsed[$data->Pin] <> $data->InstanceID) AND ($PinUsed[$data->Pin] <> 99999)) {
								IPS_LogMessage("IPS2GPIO Pin", "Achtung: Pin ".$data->Pin." wird mehrfach genutzt!");
								$this->SendDebug("set_usedpin", "Achtung: Pin ".$data->Pin." wird mehrfach genutzt!", 0);
								$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>200, "HardwareRev"=>$this->GetBuffer("HardwareRev"), "InstanceID" => $data->InstanceID )));
							}	
						}
					}
					// Genutzten Pin dem Array hinzufügen
					$PinUsed[intval($data->Pin)] = $data->InstanceID;
					// Prüfen ob ein Wechsel des Pins stattgefunden hat
					If ((intval($data->Pin) <> intval($data->PreviousPin)) AND (intval($data->PreviousPin) > -1)) {
						unset($PinUsed[intval($data->PreviousPin)]);
					}
					$this->SetBuffer("PinUsed", serialize($PinUsed));
					// Messages einrichten
					$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
					$this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
					// Erstellt ein Array für alle Pins für die die Notifikation erforderlich ist
					If ($data->Notify == true) {
						$PinNotify = array();
						$PinNotify = unserialize($this->GetBuffer("PinNotify"));
						if (in_array(intval($data->Pin), $PinNotify) == false) {
							$PinNotify[] = intval($data->Pin);
							$this->SendDebug("set_usedpin", "Gewaehlter Pin ".$data->Pin." wurde dem Notify hinzugefuegt", 0);
						}
						$this->SetBuffer("PinNotify", serialize($PinNotify));
						// startet das Notify neu
						$this->CommandClientSocket(pack("L*", 19, $this->GetBuffer("Handle"), $this->CalcBitmask(), 0), 16);
						// Setzt den Glitch Filter
						//IPS_LogMessage("IPS2GPIO SetGlitchFilter Parameter",$data->Pin." , ".$data->GlitchFilter);
						$this->CommandClientSocket(pack("L*", 97, $data->Pin, $data->GlitchFilter, 0), 16);
					}
					// Pin in den entsprechenden R/W-Mode setzen, ggf. gleichzeitig Pull-Up/Down setzen
					If ($data->Modus == 0) {
						// R/W-Mode und Pull Up/Down Widerstände für den Pin setzen
						// PI_PUD_OFF  0
						// PI_PUD_DOWN 1
						// PI_PUD_UP   2
						$this->CommandClientSocket(pack("LLLL", 0, $data->Pin, 0, 0).pack("LLLL", 2, $data->Pin, $data->Resistance, 0), 32);
					}
					else {
						// R/W-Mode setzen
						//IPS_LogMessage("IPS2GPIO SetMode",$data->Pin." , ".$data->Modus);
						$this->CommandClientSocket(pack("LLLL", 0, $data->Pin, $data->Modus, 0), 16);
						$this->SetBuffer("PinUsed", serialize($PinUsed));
					}
				}
				$Result = true;
			}
			else {
				$Result = false;
			}
		        break;
		case "get_GPIO":
		   	$PinPossible = array();
			$PinPossible = unserialize($this->GetBuffer("PinPossible"));
			$PinUsed = array();
		   	$PinUsed = unserialize($this->GetBuffer("PinUsed"));
			//$this->SendDebug("PinUsed", $this->GetBuffer("PinUsed"), 0);
		   	$PinUsedKeys = array();
			$PinUsedKeys = (array_keys($PinUsed));
			$PinFreeArray = array();
			If (is_array($PinUsed)) {
				$PinFreeArray = array_diff($PinPossible, $PinUsedKeys);
			}
			else {
				$PinFreeArray = $PinPossible;
			}
			$arrayGPIO = array();
			$arrayGPIO[-1] = "undefiniert";
			foreach($PinFreeArray AS $Value) {
				$arrayGPIO[$Value] = "GPIO".(sprintf("%'.02d", $Value));
			}
		   	return serialize($arrayGPIO);
			break;

		// I2C Kommunikation
		case "set_used_i2c":
			If ($this->GetBuffer("ModuleReady") == 1) {
				$DevicePorts = array();
				$DevicePorts[0] = "I2C-Bus 0";
				$DevicePorts[1] = "I2C-Bus 1";
				for ($i = 3; $i <= 10; $i++) {
					$DevicePorts[$i] = "MUX I2C-Bus ".($i - 3);
				}
				// Konfiguration für I²C Bus 0 - GPIO 28/29 an P5
				If (($this->GetBuffer("I2C_0_Configured") == 0) AND (intval($data->DeviceBus) == 0)) {
					$PinUsed = array();
					// Reservieren der Schnittstellen für I²C
					$this->CommandClientSocket(pack("LLLL", 0, 28, 4, 0).pack("LLLL", 0, 29, 4, 0), 32);
					// Sichern der Einstellungen
					$this->SetBuffer("I2C_0_Configured", 1);
					$this->SendDebug("Set Used I2C", "GPIO-Mode fuer I2C Bus 0 gesetzt", 0);
				}
				// Konfiguration für I²C Bus 1 (Default) - GPIO 0/1 bzw. 2/3 an P1
				If (($this->GetBuffer("I2C_1_Configured") == 0) AND (intval($data->DeviceBus) == 1)) {
					$PinUsed = array();
					$PinUsed = unserialize($this->GetBuffer("PinUsed"));
					// Reservieren der Schnittstellen für I²C
					If ($this->GetBuffer("HardwareRev") <= 3) {
						$PinUsed[0] = 99999; 
						$PinUsed[1] = 99999;
						$this->CommandClientSocket(pack("L*", 0, 0, 4, 0).pack("L*", 0, 1, 4, 0), 32);
					}
					elseif ($this->GetBuffer("HardwareRev") > 3) {
						$PinUsed[2] = 99999; 
						$PinUsed[3] = 99999;
						$this->CommandClientSocket(pack("L*", 0, 2, 4, 0).pack("L*", 0, 3, 4, 0), 32);
					}
					// Sichern der Einstellungen
					$this->SetBuffer("PinUsed", serialize($PinUsed));
					$this->SetBuffer("I2C_1_Configured", 1);
					$this->SendDebug("Set Used I2C", "GPIO-Mode fuer I2C Bus 1 gesetzt", 0);
				}

				// die genutzten Device Adressen anlegen
				$I2C_DeviceHandle = unserialize($this->GetBuffer("I2C_Handle"));
				// Messages einrichten
				$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
				$this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
				
				// Handle ermitteln
				$DeviceBus = min(1, intval($data->DeviceBus));
				$Handle = $this->CommandClientSocket(pack("L*", 54, $DeviceBus, intval($data->DeviceAddress), 4, 0), 16);	
				$this->SendDebug("Set Used I2C", "Handle fuer Device-Adresse ".$data->DeviceAddress." an Bus ".$DevicePorts[intval($data->DeviceBus)].": ".$Handle, 0);
				$I2C_DeviceHandle[($data->DeviceBus << 7) + $data->DeviceAddress] = $Handle;
				// genutzte Device-Ident mit Handle sichern
				$this->SetBuffer("I2C_Handle", serialize($I2C_DeviceHandle));	
				// Testweise lesen
				If ($Handle >= 0) {
					// MUX auf den entsprechende Bus umschalten
					If (intval($data->DeviceBus) >= 3) {
						$this->SetMUX(intval($data->DeviceBus)); 
					}
						
					$Result = $this->CommandClientSocket(pack("L*", 59, $Handle, 0, 0), 16);
					If ($Result >= 0) {
						$this->SendDebug("Set Used I2C", "Test-Lesen auf Device-Adresse ".$data->DeviceAddress." Bus ".($data->DeviceBus)." erfolgreich!", 0);
						//$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"status", "InstanceID" => $data->InstanceID, "Status" => 102)));
					}
					else {
						$this->SendDebug("Set Used I2C", "Test-Lesen auf Device-Adresse ".$data->DeviceAddress." Bus ".($data->DeviceBus)." nicht erfolgreich!", 0);
						IPS_LogMessage("IPS2GPIO I2C", "Test-Lesen auf Device-Adresse ".$data->DeviceAddress." Bus ".($data->DeviceBus - 4)." nicht erfolgreich!");
						//$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"status", "InstanceID" => $data->InstanceID, "Status" => 201)));
					}		
				}
				$Result = true;
			}
			else {
				$Result = false;
			}
		   	break;
		case "i2c_get_ports":
		   	$DevicePorts = array();
			If ($this->ReadPropertyInteger("I2C0") == 1) {
				$DevicePorts[0] = "I²C-Bus 0";
			}
			$DevicePorts[1] = "I²C-Bus 1";
			If ($this->ReadPropertyInteger("MUX") == 1) {
				// TCA9548a
				for ($i = 3; $i <= 10; $i++) {
					$DevicePorts[$i] = "MUX I²C-Bus ".($i - 3);
				}
			}
			elseif ($this->ReadPropertyInteger("MUX") == 2) {
				// PCA9542
				for ($i = 3; $i <= 4; $i++) {
					$DevicePorts[$i] = "MUX I²C-Bus ".($i - 3);
				}
			}
		   	$Result = serialize($DevicePorts);
		   	break;
		 case "i2c_read_byte":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break;
		 case "i2c_read_bytes":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$this->CommandClientSocket(pack("L*", 56, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Count, 0), 16 + ($data->Count));
		   	}
			break;  
		
		case "i2c_read_word":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$this->CommandClientSocket(pack("L*", 63, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break; 
		   case "i2c_read_block_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Block Byte Parameter : ",$data->Handle." , ".$data->Register." , ".$data->Count);  	
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;
		   case "i2c_write_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Write Byte : ",$data->Handle." , ".$data->Register." , ".$data->Value);  	
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		   case "i2c_read_byte_onhandle":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Byte Handle: ","DeviceAdresse: ".$data->DeviceAddress.", Handle: ".$this->GetI2C_DeviceHandle($data->DeviceAddress));  	
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 59, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), 0, 0), 16);
		   	}
		   	break;	

		   case "i2c_write_byte_onhandle":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$this->CommandClientSocket(pack("L*", 60, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Value, 0), 16);
		   	}
		   	break;	
		case "i2c_PCF8574_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$Result = $this->CommandClientSocket(pack("L*", 59, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), 0, 0), 16);

				//$Result = $this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   		//$Result = $this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
		   	break;	 
		case "i2c_PCF8574_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
				
				
		case "i2c_PCF8583_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
		   	break;	 
		case "i2c_PCF8583_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		case "i2c_PCF8583_write_array": 
			// I2CWI h r bvs - smb Write I2C Block Data
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$ParameterArray = array();
				$ParameterArray = unserialize($data->Parameter);
				$Result = $this->CommandClientSocket(pack("LLLLC*", 68, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, count($ParameterArray), 
									  ...$ParameterArray), 16);
			}
			break;
		case "i2c_AS3935_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 56, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Count, 0), 16 + ($data->Count));
		   	}
			break;
		case "i2c_AS3935_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		case "i2c_MCP3424_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Value, 0), 16);
		   	}
		   	break;	
		case "i2c_MCP3424_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 56, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Count, 0), 16 + ($data->Count));
		   	}
			break;  
		case "i2c_BH1750_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Value, 0), 16);
		   	}
		   	break;
		case "i2c_BH1750_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 63, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break;
		case "i2c_BMP180_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		case "i2c_BMP180_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break;
		case "i2c_BMP180_read_block":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;
		case "i2c_BME280_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		case "i2c_BME280_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break;
		case "i2c_BME280_read_block":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;
		case "i2c_BME680_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		case "i2c_BME680_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break;
		case "i2c_BME680_read_block":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;
		case "i2c_iAQ_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 56, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Count, 0), 16 + ($data->Count));
		   	}
			break;
		case "i2c_PCA9685_Write": // Module PWM und RGBW
			// I2CWB h r bv - smb Write Byte Data: write byte to register  	
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
			}
			break;
		case "i2c_PCA9685_Read_Byte": // Module PWM und RGBW
			// I2CRW h r - smb Read Word Data: read word from register
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$Result = $this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
			}
			break;  
		case "i2c_PCA9685_Read": // Module PWM und RGBW
			// I2CRW h r - smb Read Word Data: read word from register
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$Result = $this->CommandClientSocket(pack("L*", 63, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
			}
			break;  
		case "i2c_PCA9685_Read_Group": // Modul RGBW
			// I2CRW h r - smb Read Word Data: read word from register
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$Color = Array();
				$Color[] = $this->CommandClientSocket(pack("L*", 63, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
				$Color[] = $this->CommandClientSocket(pack("L*", 63, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 4, 0), 16);
				$Color[] = $this->CommandClientSocket(pack("L*", 63, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 8, 0), 16);
				$Result = serialize($Color);
			}
			break; 
		case "i2c_PCA9685_Write_Channel_RGBW": // Modul RGBW
			// I2CWI h r bvs - smb Write I2C Block Data
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$Result = $this->CommandClientSocket(pack("LLLLC*", 68, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 12, 
									  $data->Value_1, $data->Value_2, $data->Value_3, $data->Value_4, $data->Value_5, $data->Value_6, 
									  $data->Value_7, $data->Value_8, $data->Value_9, $data->Value_10, $data->Value_11, $data->Value_12), 16);
			}
			break;  
		case "i2c_PCA9685_Write_Channel_White": // Modul RGBW
			// I2CWI h r bvs - smb Write I2C Block Data
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$Result = $this->CommandClientSocket(pack("LLLLC*", 68, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, 
									  $data->Value_1, $data->Value_2, $data->Value_3, $data->Value_4), 16);
			}
			break;  
		case "i2c_write_4_byte":
			// I2CWB h r bv - smb Write Byte Data: write byte to register  	
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value_1).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 1, 4, $data->Value_2).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 2, 4, $data->Value_3).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 3, 4, $data->Value_4), 64);
			}
			break;
		case "i2c_write_12_byte":
			// I2CWB h r bv - smb Write Byte Data: write byte to register  	
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value_1).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 1, 4, $data->Value_2).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 2, 4, $data->Value_3).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 3, 4, $data->Value_4).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 4, 4, $data->Value_5).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 5, 4, $data->Value_6).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 6, 4, $data->Value_7).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 7, 4, $data->Value_8).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 8, 4, $data->Value_9).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 9, 4, $data->Value_10).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 10, 4, $data->Value_11).
							   pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register + 11, 4, $data->Value_12), 192);
			}
			break;
		case "i2c_MCP23017_write": 
			// I2CWI h r bvs - smb Write I2C Block Data
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$ParameterArray = array();
				$ParameterArray = unserialize($data->Parameter);
				$Result = $this->CommandClientSocket(pack("LLLLC*", 68, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, count($ParameterArray), 
									  ...$ParameterArray), 16);
			}
			break;
		case "i2c_MCP23017_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;  
		case "i2c_PCF8591_write": 	
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		case "i2c_PCF8591_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break;
		case "i2c_SSD1306_write": 
			// I2CWI h r bvs - smb Write I2C Block Data
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$ParameterArray = array();
				$ParameterArray = unserialize($data->Parameter);
				$Result = $this->CommandClientSocket(pack("LLLLC*", 68, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, count($ParameterArray), 
									  ...$ParameterArray), 16);
			}
			break;
		case "i2c_DS3231_write": 
			// I2CWI h r bvs - smb Write I2C Block Data
			If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
				$ParameterArray = array();
				$ParameterArray = unserialize($data->Parameter);
				$Result = $this->CommandClientSocket(pack("LLLLC*", 68, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, count($ParameterArray), 
									  ...$ParameterArray), 16);
			}
			break;
		case "i2c_DS3231_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;  
		case "i2c_APDS9960_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;   
		case "i2c_APDS9960_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		case "i2c_APDS9960_read_byte":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break;
		case "i2c_SUSV_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;
		case "i2c_SUSV_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
				$this->SetI2CBus(intval($data->DeviceIdent));
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		// Serielle Kommunikation
		case "get_handle_serial":
	   		If ($this->GetBuffer("ModuleReady") == 1) {
				If ($this->GetBuffer("Serial_Configured") == 0) {
					$PinUsed = array();
					$PinUsed = unserialize($this->GetBuffer("PinUsed"));
					// Raspberry Pi 3 = Alt5(Rxd1/TxD1) => 2
					// Alle anderen = Alt0(Rxd0/TxD0) => 4
					If ($this->GetBuffer("Default_Serial_Bus") == 0) {
						$this->CommandClientSocket(pack("L*", 0, 14, 4, 0).pack("L*", 0, 15, 4, 0), 32);
					}
					elseif ($this->GetBuffer("Default_Serial_Bus") == 1) {
						// Beim Raspberry Pi 3 ist Bus 0 schon durch die Bluetooth-Schnittstelle belegt
						$this->CommandClientSocket(pack("L*", 0, 14, 2, 0).pack("L*", 0, 15, 2, 0), 32);
					}
					$PinUsed[14] = 99999; 
					$PinUsed[15] = 99999;
					$this->SetBuffer("PinUsed", serialize($PinUsed));
					$this->SetBuffer("Serial_Configured", 1);
					$this->SendDebug("Get Serial Handle", "Mode der GPIO fuer Seriellen Bus gesetzt", 0);
					//Skripte für Seriellen Datenempfang senden
					//$Script = "tag 999 wait p0 mils p1 evt p2 jmp 999";
					$Script = "tag 999 wait p0 mils p1 evt p2";
					$SerialScriptID = $this->CommandClientSocket(pack("L*", 38, 0, 0, strlen($Script)).$Script, 16);
					//$SerialScriptID = $this->CommandClientSocket(pack("L*", 38, 0, 0, strlen($Script)).pack("C*", $Script), 16);
					$this->SetBuffer("SerialScriptID", $SerialScriptID );
					$Parameter = array();
					$Parameter = array(32768, 50, 1);

					$this->SendDebug("Serial Skript ID", "SerialScriptID: ".(int)$SerialScriptID, 0);
					If ($this->GetBuffer("SerialScriptID") >= 0) {
						$Result = $this->StartProc((int)$SerialScriptID, serialize($Parameter));
					}
					// Event setzen für den seriellen Anschluss
					$Handle = $this->GetBuffer("Handle");
					If ($Handle >= 0) {
						$this->CommandClientSocket(pack("L*", 115, $Handle, 1, 0), 16);
					}
				}


				$SerialHandle = $this->CommandClientSocket(pack("L*", 76, $data->Baud, 0, strlen($data->Device)).$data->Device, 16);

				$this->SetBuffer("Serial_Handle", (int)$SerialHandle);
				$this->SendDebug("Serial_Handle", (int)$SerialHandle, 0);

				// Messages einrichten
				$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
				$this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
				$Result = true;
			}
			else {
				$Result = false;
			}
	   		break;
		case "write_bytes_serial":
		   	$Command = utf8_decode($data->Command);
		   	//IPS_LogMessage("IPS2GPIO Write Bytes Serial", "Handle: ".GetValueInteger($this->GetIDForIdent("Serial_Handle"))." Command: ".$Command);
		   	$this->CommandClientSocket(pack("L*", 81, $this->GetBuffer("Serial_Handle"), 0, strlen($Command)).$Command, 16);
		   	IPS_Sleep(75);
			$this->CheckSerial();
			break;
		case "open_bb_serial_display":
	   		If ($this->GetBuffer("ModuleReady") == 1) {
				If ($this->GetBuffer("Serial_Display_Configured") == 0) {
					$PinUsed = array();
					$PinUsed = unserialize($this->GetBuffer("PinUsed"));
					// GPIO RxD als Input konfigurieren
					$this->CommandClientSocket(pack("L*", 0, (int)$data->Pin_RxD, 0, 0), 16);
					$PinUsed[(int)$data->Pin_RxD] = $data->InstanceID; 
					$this->SetBuffer("Serial_Display_RxD", (int)$data->Pin_RxD);
					// GPIO TxD als Output konfigurieren
					$this->CommandClientSocket(pack("L*", 0, (int)$data->Pin_TxD, 1, 0), 16);
					$PinUsed[(int)$data->Pin_TxD] = $data->InstanceID; 
					$this->SendDebug("Display", "Mode der GPIO fuer Seriellen Bus gesetzt", 0);
					$this->SetBuffer("PinUsed", serialize($PinUsed));
					
					// SLRC u - Close GPIO for bit bang serial data	
					$this->CommandClientSocket(pack("L*", 44, (int)$data->Pin_RxD, 0, 0), 16);
					
					//SLRO u b db - Open GPIO for bit bang serial data
					$this->CommandClientSocket(pack("L*", 42, (int)$data->Pin_RxD, $data->Baud, 4, 8), 16);
					// Event setzen für den seriellen Anschluss
					$Handle = $this->GetBuffer("Handle");
					If ($Handle >= 0) {
						$this->CommandClientSocket(pack("L*", 115, $Handle, pow(2, (int)$data->Pin_RxD), 0), 16);
					}
					$this->SetBuffer("Serial_Display_Configured", 1);
				}
				
				// Messages einrichten
				$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
				$this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
				$Result = true;
			}
			else {
				$Result = false;
			}
	   		break;
		case "write_bb_bytes_serial":	
		   	$Command = $data->Command;
			$this->SendDebug("Serielle Sendung", "GPIO: ".$data->Pin_TxD." Baud: ".$data->Baud. " Text: ".$Command, 0);
		   	$Result = $this->CommandClientSocket(pack("L*", 29, $data->Pin_TxD, $data->Baud, (12 + strlen($Command)), 8, 4, 0).$Command, 16);
			// WVCRE 	49 	0 	0 	0
			If ($Result > 0) {
				$WaveID = $this->CommandClientSocket(pack("L*", 49, 0, 0, 0), 16);
				If ($WaveID >= 0) {
					// WVTX 	51 	wave_id 	0 	0
					$Result = $this->CommandClientSocket(pack("L*", 51, $WaveID, 0, 0), 16);
					If ($Result >= 0) {
						// WVDEL 	50 	wave_id 	0 	0
						$this->CommandClientSocket(pack("L*", 50, $WaveID, 0, 0), 16);
						// Daten gleich abholen
						If ($this->GetBuffer("Serial_PTLB10VE_TxD") == $data->Pin_TxD) {
							IPS_Sleep(50);
							$this->CommandClientSocket(pack("L*", 43, $this->GetBuffer("Serial_PTLB10VE_RxD"), 8192, 0), 16 + 8192);
						}

					}
				}
			}
			break;
		case "write_bb_bytesarray_serial":	
		   	$CommandArray = array();
			$CommandArray = unserialize($data->Command);
			$Command = pack("C*", ...$CommandArray);
			$this->SendDebug("Serielle Sendung", "GPIO: ".$data->Pin_TxD." Baud: ".$data->Baud. " Text: ".$Command, 0);
		   	$Result = $this->CommandClientSocket(pack("L*", 29, $data->Pin_TxD, $data->Baud, (12 + strlen($Command)), 8, 4, 0).$Command, 16);
			// WVCRE 	49 	0 	0 	0
			If ($Result > 0) {
				$WaveID = $this->CommandClientSocket(pack("L*", 49, 0, 0, 0), 16);
				If ($WaveID >= 0) {
					// WVTX 	51 	wave_id 	0 	0
					$Result = $this->CommandClientSocket(pack("L*", 51, $WaveID, 0, 0), 16);
					If ($Result >= 0) {
						// WVDEL 	50 	wave_id 	0 	0
						$this->CommandClientSocket(pack("L*", 50, $WaveID, 0, 0), 16);
					}
				}
			}
			break;
		case "open_bb_serial_gps":
	   		If ($this->GetBuffer("ModuleReady") == 1) {
				If ($this->GetBuffer("Serial_GPS_Configured") == 0) {
					$PinUsed = array();
					$PinUsed = unserialize($this->GetBuffer("PinUsed"));
					// GPIO RxD als Input konfigurieren
					$this->CommandClientSocket(pack("L*", 0, (int)$data->Pin_RxD, 0, 0), 16);
					$PinUsed[(int)$data->Pin_RxD] = $data->InstanceID; 
					$this->SetBuffer("Serial_GPS_RxD", (int)$data->Pin_RxD);
					// GPIO TxD als Output konfigurieren
					$this->CommandClientSocket(pack("L*", 0, (int)$data->Pin_TxD, 1, 0), 16);
					$PinUsed[(int)$data->Pin_TxD] = $data->InstanceID; 
					$this->SendDebug("GPIO", "Mode der GPIO fuer Seriellen Bus gesetzt", 0);
					$this->SetBuffer("PinUsed", serialize($PinUsed));
					
					// SLRC u - Close GPIO for bit bang serial data	
					$this->CommandClientSocket(pack("L*", 44, (int)$data->Pin_RxD, 0, 0), 16);
					
					//SLRO u b db - Open GPIO for bit bang serial data
					$this->CommandClientSocket(pack("L*", 42, (int)$data->Pin_RxD, $data->Baud, 4, 8), 16);
					// Event setzen für den seriellen Anschluss
					$Handle = $this->GetBuffer("Handle");
					If ($Handle >= 0) {
						$this->CommandClientSocket(pack("L*", 115, $Handle, pow(2, (int)$data->Pin_RxD), 0), 16);
					}
					$this->SetBuffer("Serial_GPS_Configured", 1);
				}
				
				// Messages einrichten
				$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
				$this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
				$Result = true;
			}
			else {
				$Result = false;
			}
	   		break;	
		case "open_bb_serial_ptlb10ve":
	   		If ($this->GetBuffer("ModuleReady") == 1) {
				If ($this->GetBuffer("Serial_PTLB10VE_Configured") == 0) {
					$PinUsed = array();
					$PinUsed = unserialize($this->GetBuffer("PinUsed"));
					// GPIO RxD als Input konfigurieren
					$this->CommandClientSocket(pack("L*", 0, (int)$data->Pin_RxD, 0, 0), 16);
					$PinUsed[(int)$data->Pin_RxD] = $data->InstanceID; 
					$this->SetBuffer("Serial_PTLB10VE_RxD", (int)$data->Pin_RxD);
					// GPIO TxD als Output konfigurieren
					$this->CommandClientSocket(pack("L*", 0, (int)$data->Pin_TxD, 1, 0), 16);
					$this->SetBuffer("Serial_PTLB10VE_TxD", (int)$data->Pin_TxD);
					$PinUsed[(int)$data->Pin_TxD] = $data->InstanceID; 
					$this->SendDebug("GPIO", "Mode der GPIO fuer Seriellen Bus gesetzt", 0);
					$this->SetBuffer("PinUsed", serialize($PinUsed));
					
					// SLRC u - Close GPIO for bit bang serial data	
					$this->CommandClientSocket(pack("L*", 44, (int)$data->Pin_RxD, 0, 0), 16);
					
					//SLRO u b db - Open GPIO for bit bang serial data
					$this->CommandClientSocket(pack("L*", 42, (int)$data->Pin_RxD, $data->Baud, 4, 8), 16);
					$this->SetBuffer("Serial_PTLB10VE_Configured", 1);
				}
				
				// Messages einrichten
				$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
				$this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
				$Result = true;
			}
			else {
				$Result = false;
			}
	   		break;	
		case "open_bb_serial_sds011":
	   		If ($this->GetBuffer("ModuleReady") == 1) {
				If ($this->GetBuffer("Serial_SDS011_Configured") == 0) {
					$PinUsed = array();
					$PinUsed = unserialize($this->GetBuffer("PinUsed"));
					// GPIO RxD als Input konfigurieren
					$this->CommandClientSocket(pack("L*", 0, (int)$data->Pin_RxD, 0, 0), 16);
					$PinUsed[(int)$data->Pin_RxD] = $data->InstanceID; 
					$this->SetBuffer("Serial_SDS011_RxD", (int)$data->Pin_RxD);
					// GPIO TxD als Output konfigurieren
					$this->CommandClientSocket(pack("L*", 0, (int)$data->Pin_TxD, 1, 0), 16);
					$this->SetBuffer("Serial_SDS011_TxD", (int)$data->Pin_TxD);
					$PinUsed[(int)$data->Pin_TxD] = $data->InstanceID; 
					$this->SendDebug("GPIO", "Mode der GPIO fuer Seriellen Bus gesetzt", 0);
					$this->SetBuffer("PinUsed", serialize($PinUsed));
					
					// SLRC u - Close GPIO for bit bang serial data	
					$this->CommandClientSocket(pack("L*", 44, (int)$data->Pin_RxD, 0, 0), 16);
					
					//SLRO u b db - Open GPIO for bit bang serial data
					$this->CommandClientSocket(pack("L*", 42, (int)$data->Pin_RxD, $data->Baud, 4, 8), 16);
					$this->SetBuffer("Serial_SDS011_Configured", 1);
				}
				
				// Messages einrichten
				$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
				$this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
				$Result = true;
			}
			else {
				$Result = false;
			}
	   		break;	
		case "read_bb_serial":
			$Result = $this->CommandClientSocket(pack("L*", 43, (int)$data->Pin_RxD, 8192, 0), 16 + 8192);
		   	break;
		case "check_bytes_serial":
		   	//IPS_LogMessage("IPS2GPIO Check Bytes Serial", "Handle: ".GetValueInteger($this->GetIDForIdent("Serial_Handle")));
		   	$this->CommandClientSocket(pack("L*", 82, $this->GetBuffer("Serial_Handle"), 0, 0), 16);
		   	break;
		// IR-Kommunikation
		case "IR_Remote":
			$PulseArray = array();
			$PulseArray = unserialize($data->Pulse);
			//WVAG 	28 	0 	0 	12*X 	gpioPulse_t pulse[X]
			$Result = $this->CommandClientSocket(pack("L*", 28, 0, 0, 12 * (count($PulseArray) / 3), ...$PulseArray), 16);
			// WVCRE 	49 	0 	0 	0
			If ($Result > 0) {
				$WaveID = $this->CommandClientSocket(pack("L*", 49, 0, 0, 0), 16);
				If ($WaveID >= 0) {
					// WVTX 	51 	wave_id 	0 	0
					$Result = $this->CommandClientSocket(pack("L*", 51, $WaveID, 0, 0), 16);
					If ($Result >= 0) {
						// WVDEL 	50 	wave_id 	0 	0
						$this->CommandClientSocket(pack("L*", 50, $WaveID, 0, 0), 16);
					}
				}
			}
			break;
		case "IR_Remote_GetWaveID":
			$PulseArray = array();
			$PulseArray = unserialize($data->Pulse);
			//WVAG 	28 	0 	0 	12*X 	gpioPulse_t pulse[X]
			$Result = $this->CommandClientSocket(pack("L*", 28, 0, 0, 12 * (count($PulseArray) / 3), ...$PulseArray), 16);
			// WVCRE 	49 	0 	0 	0
			If ($Result > 0) {
				// Ermittle Wave ID
				$Result = $this->CommandClientSocket(pack("L*", 49, 0, 0, 0), 16);
			}
			break;
		case "IR_Remote_RC5":
			$Address = intval($data->Address);
			$Command = intval($data->Command);
			$GPIO = intval($data->GPIO);
			$Repeats = intval($data->Repeats);
			$Toggle = $this->GetBuffer("IR_RC5_Toggle");
				
			// WVNEW - Initialise a new waveform
			$this->CommandClientSocket(pack("L*", 53, 0, 0, 0), 16);
			$Mark = array();
			$Mark = $this->IR_Carrier($GPIO, 36000, 889, 0.5);
			//WVAG 	28 	0 	0 	12*X 	gpioPulse_t pulse[X]
			$Result = $this->CommandClientSocket(pack("L*", 28, 0, 0, 12 * (count($Mark) / 3), ...$Mark), 16);
			If ($Result > 0) {
				// WVCRE - Create a waveform
				$Mark_ID = $this->CommandClientSocket(pack("L*", 49, 0, 0, 0), 16);
				
				$Space = array();
				$Space = [0, 0, 889];
				$Result = $this->CommandClientSocket(pack("L*", 28, 0, 0, 12 * (count($Space) / 3), ...$Space), 16);
				If ($Result > 0) {
					// WVCRE - Create a waveform
					$Space_ID = $this->CommandClientSocket(pack("L*", 49, 0, 0, 0), 16);
					$Bit = array();
					$Bit = ($Mark_ID, $Space_ID, $Space_ID, $Mark_ID);
					
					IF ($Toggle == 1) {
						$Toggle = 0;
					}
					else {
						$Toggle = 1;
					}
					$this->SetBuffer("Toggle", $Toggle);
					$Data = (3 << 12) | ($Toggle << 11) | ($Address << 6) | $Command;
					
					$Chain = array();
					for ($i = 14 - 1; $i > -1; $i--) {
						$Chain[] = $Bit[($Data >> $i) & 1];
					}
					for ($i = 0; $i <= $Repeats; $i++) {
						//WVCHA bvs - Transmits a chain of waveforms
						//WVCHA 93 0 0 X uint8_t data[X]
						$Result = $this->CommandClientSocket(pack("LLLLC*", 93, 0, 0, count($Chain), ...$Chain), 16);
						IPS_Sleep(100);
					
					}
				
				}
				
			}

				
			break;
		// Raspberry Pi Kommunikation
		case "get_RPi_connect":
		   	// SSH Connection
			If ($data->IsArray == false) {
				// wenn es sich um ein einzelnes Kommando handelt
				//IPS_LogMessage("IPS2GPIO SSH-Connect", $data->Command );
				$Result = $this->SSH_Connect($data->Command);
				//IPS_LogMessage("IPS2GPIO SSH-Connect", $Result );
				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_RPi_connect", "InstanceID" => $data->InstanceID, "CommandNumber" => $data->CommandNumber, "Result"=>utf8_encode($Result), "IsArray"=>false  )));
			}
			else {
				// wenn es sich um ein Array von Kommandos handelt
				$Result = $this->SSH_Connect_Array($data->Command);
				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_RPi_connect", "InstanceID" => $data->InstanceID, "CommandNumber" => $data->CommandNumber, "Result"=>utf8_encode($Result), "IsArray"=>true  )));
			}
			break;
		
		// 1-Wire
		case "get_1wire_devices":
			If ($this->GetBuffer("1Wire_Configured") == 0) {
				$PinUsed = array();
				$PinUsed = unserialize($this->GetBuffer("PinUsed"));
				$this->CommandClientSocket(pack("L*", 0, 4, 1, 0), 16);
				$PinUsed[4] = 99999; 
				$this->SetBuffer("PinUsed", serialize($PinUsed));
				$this->SetBuffer("1Wire_Configured", 1);
				$this->SendDebug("Get Serial Handle", "Mode der GPIO fuer 1Wire gesetzt", 0);
			}
			$Result = utf8_encode($this->GetOneWireDevices());
			//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_1wire_devices", "InstanceID" => $data->InstanceID, "Result"=>$Result )));
			break;
		case "get_1W_data":
			$Result = utf8_encode($this->SSH_Connect_Array($data->Command));
			//IPS_LogMessage("IPS2GPIO 1-Wire-Data", $Result );
			//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_1wire_data", "InstanceID" => $data->InstanceID, "Result"=>$Result )));
			break;
		// 1-Wire
		case "get_OWDevices":
			 If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
				$j = 0;
				$OWDeviceArray = array();
				$this->OWSearchStart();
				$OWDeviceArray = unserialize($this->GetBuffer("OWDeviceArray"));
				$DeviceSerialArray = array();
				If (count($OWDeviceArray ,COUNT_RECURSIVE) >= 4) {
					for ($i = 0; $i < Count($OWDeviceArray); $i++) {
						$DeviceSerial = $OWDeviceArray[$i][1];
						$FamilyCode = substr($DeviceSerial, -2);
						If (($FamilyCode == $data->FamilyCode) AND ($OWDeviceArray[$i][2] == 0)) {
							$DeviceSerialArray[$j][0] = $DeviceSerial; // DeviceAdresse
							$DeviceSerialArray[$j][1] = $OWDeviceArray[$i][5]; // Erster Teil der Adresse
							$DeviceSerialArray[$j][2] = $OWDeviceArray[$i][6]; // Zweiter Teil der Adresse
							$j = $j + 1;
						}
					}
				}
				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_OWDevices", "InstanceID" => $data->InstanceID, "Result"=>serialize($DeviceSerialArray) ))); 
				$Result = true;
			}
			else {
				$Result = false;
			}
			 break;
		case "set_OWDevices":
			If ($this->GetBuffer("ModuleReady") == 1) {
				// die genutzten Device Adressen anlegen
				$OWInstanceArray[$data->InstanceID]["DeviceSerial"] = $data->DeviceSerial;
				$OWDeviceArray = array();
				$OWDeviceArray = unserialize($this->GetBuffer("OWDeviceArray"));
				If (count($OWDeviceArray , COUNT_RECURSIVE) >= 4) {
					for ($i = 0; $i < Count($OWDeviceArray); $i++) {
						If ($OWDeviceArray[$i][1] == $data->DeviceSerial) {
							$OWInstanceArray[$data->InstanceID]["Address_0"] = $OWDeviceArray[$i][5];
							$OWInstanceArray[$data->InstanceID]["Address_1"] = $OWDeviceArray[$i][6];
						}
					}
				}
				else {
					$OWInstanceArray[$data->InstanceID]["Address_0"] = 0;
					$OWInstanceArray[$data->InstanceID]["Address_1"] = 0;	
				}
				 $OWInstanceArray[$data->InstanceID]["Status"] = "Angemeldet";
				 $this->SetBuffer("OWInstanceArray", serialize($OWInstanceArray));
				 // Messages einrichten
				 $this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden
				 $this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt
			}
			break;
		case "get_DS18S20Temperature":
			If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
				 if (IPS_SemaphoreEnter("OW", 3000))
				 {
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);
					if ($this->OWVerify()) {
						if ($this->OWReset()) { //Reset was successful
							$this->OWSelect();
							$this->OWWriteByte(0x44); //start conversion
							IPS_Sleep($data->Time); //Wait for conversion
							$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
							$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);
							if ($this->OWReset()) { //Reset was successful
								$this->OWSelect();
								$this->OWWriteByte(0xBE); //Read Scratchpad
								$Celsius = $this->OWRead_18S20_Temperature();
								$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_DS18S20Temperature", "InstanceID" => $data->InstanceID, "Result"=>$Celsius )));
							}
						}
					}
					else {
						$this->SendDebug("get_DS18S20Temperature", "OWVerify: Device wurde nicht gefunden!", 0);
						$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "InstanceID" => $data->InstanceID, "Status" => 201)));
					}
					IPS_SemaphoreLeave("OW");
				}
				else {
					$this->SendDebug("DS18S20Temperature", "Semaphore Abbruch", 0);
				}
			}
			break;
		 case "get_DS18B20Temperature":
			If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
				if (IPS_SemaphoreEnter("OW", 3000))
				{
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);
					if ($this->OWVerify()) {
						if ($this->OWReset()) { //Reset was successful
							$this->OWSelect();
							$this->OWWriteByte(0x44); //start conversion
							IPS_Sleep($data->Time); //Wait for conversion
							$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
							$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);
							if ($this->OWReset()) { //Reset was successful
								$this->OWSelect();
								$this->OWWriteByte(0xBE); //Read Scratchpad
								$Celsius = $this->OWRead_18B20_Temperature(); 
								$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_DS18B20Temperature", "InstanceID" => $data->InstanceID, "Result"=>$Celsius )));
							}
						}
					}
					else {
						$this->SendDebug("get_DS18B20Temperature", "OWVerify: Device wurde nicht gefunden!", 0);
						$this->SendDataToChildren(json_encode(Array("DataID" => "{573FFA75-2A0C-48AC-BF45-FCB01D6BF910}", "Function"=>"status", "InstanceID" => $data->InstanceID, "Status" => 201)));
					}
					IPS_SemaphoreLeave("OW");
				}
				else {
					$this->SendDebug("DS18B20Temperature", "Semaphore Abbruch", 0);
				}
			}
			break;
		case "set_DS18B20Setup":
			If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
				if (IPS_SemaphoreEnter("OW", 3000))
				{
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);
					 if ($this->OWReset()) { //Reset was successful
						$this->OWSelect();
						$this->OWWriteByte(78); 
						$this->OWWriteByte(0); 
						$this->OWWriteByte(0); 
						$this->OWWriteByte($data->Resolution); 
					}
					IPS_SemaphoreLeave("OW");
				}
				else {
					$this->SendDebug("DS18B20Setup", "Semaphore Abbruch", 0);
				}
			}
			break;
		case "get_DS2413State":
			If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
				if (IPS_SemaphoreEnter("OW", 2000))
				{
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);
					if ($this->OWVerify()) {
						if ($this->OWReset()) { //Reset was successful
							$this->OWSelect();
							$this->OWWriteByte(0xF5); //PIO ACCESS READ
							$Result = $this->OWRead_2413_State();	
							$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_DS2413State", "InstanceID" => $data->InstanceID, "Result"=>$Result )));
						}
					}
					else {
						$this->SendDebug("get_DS2413State", "OWVerify: Device wurde nicht gefunden!", 0);
						$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "InstanceID" => $data->InstanceID, "Status" => 201)));
					}
					IPS_SemaphoreLeave("OW");
				}
				else {
					$this->SendDebug("DS2413State", "Semaphore Abbruch", 0);
				}
			}
			break;
		case "set_DS2413Setup":
			If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
				if (IPS_SemaphoreEnter("OW", 3000))
				{
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);
					 if ($this->OWReset()) { //Reset was successful
						$this->OWSelect();
						$this->OWWriteByte(0x5A); //PIO ACCESS WRITE
						$Value = $data->Setup;
						$this->OWWriteByte($Value); 
						$this->OWWriteByte($Value ^ 0xFF); 
					 }
					IPS_SemaphoreLeave("OW");
				}
				else {
					$this->SendDebug("DS2413Setup", "Semaphore Abbruch", 0);
				}
			}
			break;
		case "get_DS2438Measurement":
			If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
				if (IPS_SemaphoreEnter("OW", 3000))
				{
					$this->SetBuffer("owDeviceAddress_0", $data->DeviceAddress_0);
					$this->SetBuffer("owDeviceAddress_1", $data->DeviceAddress_1);
					if ($this->OWVerify()) {
						// Erster Schritt: VDD ermitteln
						if ($this->OWReset()) { //Reset was successful
							$this->OWSelect();
							$this->OWWriteByte(0x4E);
							$this->OWWriteByte(0x00);
							$this->OWWriteByte(0x07);
							if ($this->OWReset()) { //Reset was successful
								$this->OWSelect();
								$this->OWWriteByte(0xB4); //start A/D V conversion
								IPS_Sleep(10); //Wait for conversion
								if ($this->OWReset()) { //Reset was successful
									$this->OWSelect();
									$this->OWWriteByte(0xB8); //Recall memory
									$this->OWWriteByte(0x00); //Recall memory
									IPS_Sleep(10); 
									if ($this->OWReset()) { //Reset was successful
										$this->OWSelect();
										$this->OWWriteByte(0xBE); //Read Scratchpad
										$this->OWWriteByte(0x00); //Read Scratchpad
										list($Celsius, $Voltage_VAD, $Current) = $this->OWRead_2438();
									}
								}
							}	
						}
						if ($this->OWReset()) { //Reset was successful
							$this->OWSelect();
							$this->OWWriteByte(0x4E);
							$this->OWWriteByte(0x00);
							$this->OWWriteByte(0x0F);
							if ($this->OWReset()) { //Reset was successful
								$this->OWSelect();
								$this->OWWriteByte(0x44); //start C° conversion
								IPS_Sleep(10); //Wait for conversion
								if ($this->OWReset()) { //Reset was successful
									$this->OWSelect();
									$this->OWWriteByte(0xB4); //start A/D V conversion
									IPS_Sleep(10); //Wait for conversion
									if ($this->OWReset()) { //Reset was successful
										$this->OWSelect();
										$this->OWWriteByte(0xB8); //Recall memory
										$this->OWWriteByte(0x00); //Recall memory
										IPS_Sleep(10); 
										if ($this->OWReset()) { //Reset was successful
											$this->OWSelect();
											$this->OWWriteByte(0xBE); //Read Scratchpad
											$this->OWWriteByte(0x00); //Read Scratchpad
											list($Celsius, $Voltage_VDD, $Current) = $this->OWRead_2438();
											$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", 
												"Function"=>"set_DS2438", "InstanceID" => $data->InstanceID, "Temperature"=>$Celsius, "Voltage_VDD"=>$Voltage_VDD , "Voltage_VAD"=>$Voltage_VAD, "Current"=>$Current )));
										}
									}
								}
							}
						}
					}
					else {
						$this->SendDebug("get_DS2438Measurement", "OWVerify: Device wurde nicht gefunden!", 0);
						$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "InstanceID" => $data->InstanceID, "Status" => 201)));
					}
					IPS_SemaphoreLeave("OW");
				}
				else {
					$this->SendDebug("DS2438Measurement", "Semaphore Abbruch", 0);
				}
			}
			break;
		
		}
	return $Result;
	}

	public function ReceiveData($JSONString) {
		// Empfangene Daten vom I/O
	    	$Data = json_decode($JSONString);
	    	$Message = utf8_decode($Data->Buffer);
	    	$MessageLen = strlen($Message);
	    	$MessageArray = unpack("L*", $Message);
		$GPSDataRead = false;
		$this->SendDebug("Datenanalyse", "Laenge: ".$MessageLen." Anzahl: ".Count($MessageArray), 0);
		
		// Analyse der eingegangenen Daten
		for ($i = 1; $i <= Count($MessageArray); $i++) {
			//$this->SendDebug("Datenanalyse", "Datensatz ".$i." von ".Count($MessageArray), 0);
			
			
			
			// Struktur:
			// H seqno: starts at 0 each time the handle is opened and then increments by one for each report.
			// H flags: three flags are defined, PI_NTFY_FLAGS_WDOG, PI_NTFY_FLAGS_ALIVE, and PI_NTFY_FLAGS_EVENT. 
				//If bit 5 is set (PI_NTFY_FLAGS_WDOG) then bits 0-4 of the flags indicate a GPIO which has had a watchdog timeout. 
				//If bit 6 is set (PI_NTFY_FLAGS_ALIVE) this indicates a keep alive signal on the pipe/socket and is sent once a minute in the absence of other notification activity. 
				//If bit 7 is set (PI_NTFY_FLAGS_EVENT) then bits 0-4 of the flags indicate an event which has been triggered. 
			// I tick: the number of microseconds since system boot. It wraps around after 1h12m. 
			// I level: indicates the level of each GPIO. If bit 1<<x is set then GPIO x is high. 
			$Command = $MessageArray[$i];
			$SeqNo = $MessageArray[$i] & 65535;
			$Flags = $MessageArray[$i] >> 16;
			$Event = (int)boolval($Flags & 128);
			$EventNumber = $Flags & 31;
			$KeepAlive = (int)boolval($Flags & 64);
			$WatchDog = (int)boolval($Flags & 32);
			$WatchDogNumber = $Flags & 31;
			$Tick = $MessageArray[$i + 1];
			$Level = $MessageArray[$i + 2];
			
			// Prüfen ob es sich um ein Kommando handelt
			If ($Command == 99) {
				// es handelt sich um ein Kommando
				if (array_key_exists($i + 3, $MessageArray)) {
					If (($MessageArray[$i] == 99) AND ($MessageArray[$i + 1] == 0) AND ($MessageArray[$i + 2] == 0)) {
						$this->SendDebug("Datenanalyse", "Kommando: ".$MessageArray[$i], 0);
						$this->ClientResponse(pack("L*", $MessageArray[$i], $MessageArray[$i + 1], $MessageArray[$i + 2], $MessageArray[$i + 3]));
						If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == false) {
							SetValueBoolean($this->GetIDForIdent("PigpioStatus"), true);
						}
						$i = $i + 3;
					}
				}
			}
			elseif ($KeepAlive == 1) {
				$this->SendDebug("Datenanalyse", "KeepAlive - SeqNo: ".$SeqNo, 0);
				SetValueInteger($this->GetIDForIdent("LastKeepAlive"), time() );
				If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == false) {
					SetValueBoolean($this->GetIDForIdent("PigpioStatus"), true);
				}
				$i = $i + 2;
			}
			elseif ($WatchDog == 1) {
				$this->SendDebug("Datenanalyse", "WatchDog-Nummer: ".$WatchDogNumber." - SeqNo: ".$SeqNo, 0);
				If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == false) {
					SetValueBoolean($this->GetIDForIdent("PigpioStatus"), true);
				}
				$i = $i + 2;
			}
			elseif ($Event == 1) {
				$this->SendDebug("Datenanalyse", "Event-Nummer: ".$EventNumber." - SeqNo: ".$SeqNo, 0);
				If ($EventNumber == $this->GetBuffer("Serial_Display_RxD")) {
					// Daten des Displays	-
					$this->CommandClientSocket(pack("L*", 43, $this->GetBuffer("Serial_Display_RxD"), 8192, 0), 16 + 8192);
					//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_data", "Value"=> utf8_encode($Result) )));

				}
				elseIf ($EventNumber == $this->GetBuffer("Serial_GPS_RxD")) {
					// Daten GPS
					If ($GPSDataRead == false) {
						$this->CommandClientSocket(pack("L*", 43, $this->GetBuffer("Serial_GPS_RxD"), 8192, 0), 16 + 8192);
						//$this->SendDebug("Datenanalyse", "GPS-Daten: ".strlen($Result), 0);
						//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_gps_data", "Value"=> utf8_encode($Result) )));
						$GPSDataRead = true;
					}
				}
				elseIf ($EventNumber == $this->GetBuffer("Serial_PTLB10VE_RxD")) {
					// Daten PTLB10VE
					$this->CommandClientSocket(pack("L*", 43, $this->GetBuffer("Serial_PTLB10VE_RxD"), 8192, 0), 16 + 8192);
				}
				elseIf ($EventNumber == $this->GetBuffer("Serial_SDS011_RxD")) {
					// Daten SDS011
					$this->CommandClientSocket(pack("L*", 43, $this->GetBuffer("Serial_SDS011_RxD"), 8192, 0), 16 + 8192);
				}
				If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == false) {
					SetValueBoolean($this->GetIDForIdent("PigpioStatus"), true);
				}
				$i = $i + 2;
			}
			else {
				$PinNotify = array();
				$PinNotify = unserialize($this->GetBuffer("PinNotify"));
				$NotifyBitmask = intval($this->GetBuffer("NotifyBitmask"));
				$LastNotify = intval($this->GetBuffer("LastNotify"));
				
				// Daten bereinigen
				$Level = $Level & $NotifyBitmask;
				
				for ($j = 0; $j < Count($PinNotify); $j++) {
					$Bitvalue = boolval($Level & (1 << $PinNotify[$j]));
					$LastBitvalue = boolval($LastNotify & (1 << $PinNotify[$j]));
					
					$this->SendDebug("Datenanalyse", "Event: Interrupt - Bit ".$PinNotify[$j]." Aktueller Wert: ".(int)$Bitvalue." Letzter Wert: ".(int)$LastBitvalue." - SeqNo: ".$SeqNo, 0);
					If ($LastNotify == -1) {
						// ohne Vergleichswert an alle Instanzen senden
						$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"notify", "Pin" => $PinNotify[$j], "Value"=> $Bitvalue, "Timestamp"=> $Tick)));
					}
					elseif ($LastNotify >= 0) {
						// den Vergleichswert auf Veränderungen untersuchen
						If ($LastBitvalue <> $Bitvalue) {
							$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"notify", "Pin" => $PinNotify[$j], "Value"=> $Bitvalue, "Timestamp"=> $Tick)));
						}
					}
				}
				$this->SetBuffer("LastNotify", $Level);
				
				If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == false) {
					SetValueBoolean($this->GetIDForIdent("PigpioStatus"), true);
				}
				$i = $i + 2;
			}		
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

	private function ClientSocket(String $message)
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$res = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => utf8_encode($message))));  
		}
	}
	
	
	private function CommandClientSocket(String $message, $ResponseLen = 16)
	{
		$Result = -999;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			if (IPS_SemaphoreEnter("ClientSocket", 300))
			{
				if (!$this->Socket)
				{
					// Socket erstellen
					if(!($this->Socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP))) {
						$errorcode = socket_last_error();
						$errormsg = socket_strerror($errorcode);
						//IPS_LogMessage("GeCoS_IO Socket", "Fehler beim Erstellen ".$errorcode." ".$errormsg);
						//$this->SendDebug("CommandClientSocket", "Fehler beim Erstellen ".$errorcode." ".$errormsg, 0);
						IPS_SemaphoreLeave("ClientSocket");
						return;
					}
					//$this->SendDebug("CommandClientSocket", "Socket wurde erzeugt", 0);
					// Timeout setzen
					socket_set_option($this->Socket, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>0, "usec"=>150000));
					// Verbindung aufbauen
					if(!(socket_connect($this->Socket, $this->ReadPropertyString("IPAddress"), 8888))) {
						$errorcode = socket_last_error();
						$errormsg = socket_strerror($errorcode);
						//IPS_LogMessage("GeCoS_IO Socket", "Fehler beim Verbindungsaufbaus ".$errorcode." ".$errormsg);
						//$this->SendDebug("CommandClientSocket", "Fehler beim Verbindungsaufbaus ".$errorcode." ".$errormsg, 0);
						IPS_SemaphoreLeave("ClientSocket");
						return;
					}


					if (!$this->Socket) {
						IPS_LogMessage("IPS2GPIO Socket", "Fehler beim Verbindungsaufbau ".$errno." ".$errstr);
						$this->SendDebug("CommandClientSocket", "Fehler beim Verbindungsaufbau ".$errno." ".$errstr, 0);
						// Testballon an IPS-ClientSocket
						$this->ClientSocket(pack("L*", 99, 0, 0, 0));						
						$this->SetStatus(201);
						IPS_SemaphoreLeave("ClientSocket");
						return $Result;
					}
				}

				// Message senden
				if( ! socket_send ($this->Socket, $message, strlen($message), 0))
				{
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("IPS2GPIO Socket", "Fehler beim Senden ".$errorcode." ".$errormsg);
					IPS_SemaphoreLeave("ClientSocket");
					return;
				}
				//Now receive reply from server
				$MessageCommand = unpack("L*", $message);
				If ($MessageCommand <> 43) {
					if(socket_recv ($this->Socket, $buf, $ResponseLen, MSG_WAITALL ) === false) {
						$errorcode = socket_last_error();
						$errormsg = socket_strerror($errorcode);
						IPS_LogMessage("IPS2GPIO Socket", "Fehler beim Empfangen ".$errorcode." ".$errormsg);
						$this->SendDebug("CommandClientSocket", "Fehler beim Empfangen ".$errorcode." ".$errormsg, 0);
						IPS_SemaphoreLeave("ClientSocket");
						If ($errorcode == 11) {
							$this->PIGPIOD_Restart();
						}
						//socket_close($this->Socket);
						return;
					}
				}
				elseIf ($MessageCommand == 43) {
					if(socket_recv ($this->Socket, $buf, (16 + 1024), MSG_DONTWAIT ) === false) {
						$errorcode = socket_last_error();
						$errormsg = socket_strerror($errorcode);
						IPS_LogMessage("IPS2GPIO Socket", "Fehler beim Empfangen ".$errorcode." ".$errormsg);
						$this->SendDebug("CommandClientSocket", "Fehler beim Empfangen ".$errorcode." ".$errormsg, 0);
						IPS_SemaphoreLeave("ClientSocket");
						If ($errorcode == 11) {
							$this->PIGPIOD_Restart();
						}
						//socket_close($this->Socket);
						return;
					}
				}
				IPS_SemaphoreLeave("ClientSocket");
			}
			// Anfragen mit variabler Rückgabelänge
			$CmdVarLen = array(43, 56, 67, 70, 73, 75, 80, 88, 91, 92, 106, 109);
			$MessageArray = unpack("L*", $buf);
			$Command = $MessageArray[1];
			//IPS_LogMessage("IPS2GPIO ReceiveData", "Command: ".$Command);
			If (in_array($Command, $CmdVarLen)) {
				$Result = $this->ClientResponse($buf);
				//IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen");
			}
			// Standardantworten
			elseIf ((strlen($buf) == 16) OR ((strlen($buf) / 16) == intval(strlen($buf) / 16))) {
				$DataArray = str_split($buf, 16);
				//IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen");
				for ($i = 0; $i < Count($DataArray); $i++) {
					$Result = $this->ClientResponse($DataArray[$i]);
				}
			}
			else {
				IPS_LogMessage("IPS2GPIO ReceiveData", strlen($buf)." Zeichen - nicht differenzierbar!");
			}
		}	
	return $Result;
	}
	

	private function ClientResponse(String $Message)
	{
		$response = unpack("L*", $Message);
		$Result = $response[4];
		switch($response[1]) {
		        case "0":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO Set Mode", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO Set Mode", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
		        case "2":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO Set Pull-up/Down-Widerstand", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO Set Pull-up/Down-Widerstand", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
			case "3":
		        	If ($response[4] >= 0) {
		        		//IPS_LogMessage("IPS2GPIO Read", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        		//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"result", "Pin" => $response[2], "Value"=> $response[3])));
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO Read", "Pin: ".$response[2]." konnte nicht erfolgreich ermittelt werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
			case "4":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO Write", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        		//$this->SendDebug("ClientResponse", "Write Pin: ".$response[2]." Wert: ".$response[3], 0);
					$Result = true;
					//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"result", "Pin" => $response[2], "Value"=> $response[3])));
		        	}
		        	else {
		        		$this->SendDebug("ClientResponse", "Write Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("IPS2GPIO Write", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
					$Result = false;
		        	}
		        	break;
		        case "5":
		        	If ($response[4] == 0) {
		        		//IPS_LogMessage("IPS2GPIO PWM", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"result", "Pin" => $response[2], "Value"=> $response[3])));
					$Result = true;
		        	}
		        	else {
		        		IPS_LogMessage("IPS2GPIO PWM", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
					$Result = false;
		        	}
		        	break;
		        case "8":
		        	If ($response[4] == 0) {
		        		$Result = true;
					//IPS_LogMessage("IPS2GPIO PWM", "Pin: ".$response[2]." Wert: ".$response[3]." erfolgreich gesendet");
		        		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"result", "Pin" => $response[2], "Value"=> $response[3])));
		        	}
		        	else {
		        		$Result = false;
					IPS_LogMessage("IPS2GPIO PWM", "Pin: ".$response[2]." Wert: ".$response[3]." konnte nicht erfolgreich gesendet werden! Fehler:".$this->GetErrorText(abs($response[4])));
		        	}
		        	break;
			case "9":
           			If ($response[4] >= 0 ) {
           				//IPS_LogMessage("GeCoS_IO Handle",$response[4]);
           				$this->SendDebug("WatchDog", "gesetzt", 0);
           			}
           			else {
           				IPS_LogMessage("GeCoS_IO WatchDog","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
					$this->SendDebug("WatchDog", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
           			}
		            	break;
			case "17":
		            	$Model[0] = array(2, 3);
		            	$Model[1] = array(4, 5, 6, 13, 14, 15);
		            	$Model[2] = array(16);
		            	$Typ[0] = array(0, 1, 4 => 4, 7 => 7, 8, 9, 10, 11, 14 => 14, 15, 17 => 17, 18, 21 => 21, 22, 23, 24, 25);	
           			$Typ[1] = array(2 => 2, 3, 4, 7 => 7, 8, 9, 10, 11, 14 => 14, 15, 17 => 17, 18, 22 => 22, 23, 24, 25, 27 => 27);
           			$Typ[2] = array(2 => 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);
           			
           			$this->SetBuffer("HardwareRev", $response[4]);
				SetValueString($this->GetIDForIdent("Hardware"), $this->GetHardware($response[4]));
           			
           			if (in_array($response[4], $Model[0])) {
    					$this->SetBuffer("PinPossible", serialize($Typ[0])); 
    					$this->SetBuffer("PinI2C", serialize(array(0, 1))); 
    					$this->SendDebug("Hardwareermittlung", "Raspberry Pi Typ 0", 0);
				}
				else if (in_array($response[4], $Model[1])) {
					$this->SetBuffer("PinPossible", serialize($Typ[1]));
					$this->SetBuffer("PinI2C", serialize(array(2, 3))); 
					$this->SendDebug("Hardwareermittlung", "Raspberry Pi Typ 1", 0);
				}
				else if ($response[4] >= 16) {
					$this->SetBuffer("PinPossible", serialize($Typ[2]));
					$this->SetBuffer("PinI2C", serialize(array(2, 3)));
					$this->SendDebug("Hardwareermittlung", "Raspberry Pi Typ 2", 0);
				}
				else {
					IPS_LogMessage("IPS2GPIO Hardwareermittlung","nicht erfolgreich! Fehler:".$this->GetErrorText(abs($response[4])));
					$this->SendDebug("Hardwareermittlung", "nicht erfolgreich! Fehler:".$this->GetErrorText(abs($response[4])), 0);
				}	
				break;
           		case "19":
           			//IPS_LogMessage("IPS2GPIO Notify","gestartet");
				$this->SendDebug("Notify", "gestartet", 0);
		            	break;
           		case "21":
           			//IPS_LogMessage("IPS2GPIO Notify","gestoppt");
				$this->SendDebug("Notify", "gestoppt", 0);
		            	break;
			case "26":
           			If ($response[4] >= 0 ) {
					SetValueInteger($this->GetIDForIdent("SoftwareVersion"), $response[4]);
					If ($response[4] < 67 ) {
						IPS_LogMessage("IPS2GPIO PIGPIO Software Version","Bitte neuste PIGPIO-Software installieren!");
						$this->SendDebug("PIGPIO Version", "Bitte neuste PIGPIO-Software installieren!", 0);
					}
					else {
						$this->SendDebug("PIGPIO Version", "PIGPIO-Software ist aktuell", 0);
					}
				}
           			else {
           				IPS_LogMessage("IPS2GPIO PIGPIO Software Version","Fehler: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
			case "27":
				$this->SendDebug("Waveforms", "geloescht", 0);
		            	break;
			case "28":
				If ($response[4] >= 0) {
					$this->SendDebug("Pulse", "Anzahl: ".$response[4], 0);
           				$Result = $response[4];
           			}
           			else {
           				$Result = -1;
					$this->SendDebug("Pulse", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
           			}
		            	break;
			case "29":
           			If ($response[4] >= 0) {
           				$this->SendDebug("Bit Bang Serial", "Anzahl gesendet: ".$response[4], 0);
					$Result = true;
           			}
           			else {
           				$this->SendDebug("Bit Bang Serial", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("IPS2GPIO","Bit Bang Serial mit Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
					$Result = false;
           			}
		            	break;
		         case "37":
           			If ($response[4] >= 0) {
           				$Result = true;
           			}
           			else {
           				$Result = false;
           			}
		            	break;
			case "38":
           			If ($response[4] >= 0) {
					$this->SendDebug("Skriptsendung", "Skript-ID: ".(int)$Result, 0);
           				$Result = $response[4]; // SkriptID
           			}
           			else {
           				$Result = -1;
					$this->SendDebug("Skript", "Registrierung mit Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("IPS2GPIO","Skriptregistrierung mit Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
			case "40":
           			If ($response[4] >= 0) {
           				$Result = $response[4]; // Skriptstatus
           			}
           			else {
           				$Result = -1;
					$this->SendDebug("Skript", "Start mit Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
           			}
		            	break;
			case "42":
           			If ($response[4] >= 0) {
           				$Result = true;
           			}
           			else {
           				$Result = false;
					$this->SendDebug("Serial Bit Bang", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("IPS2GPIO","Serial Bit Bang mit Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
 
           			}
		            	break;
			case "43":
           			If ($response[4] >= 0) {
					$Result = utf8_encode(substr($Message, -($response[4])));
					$this->SendDebug("SLR", "Serielle-Daten: ".strlen($Result), 0);
					If ($response[2] == $this->GetBuffer("Serial_GPS_RxD")) {
						$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_gps_data", "Value"=> utf8_encode($Result) )));
					}
					elseif ($response[2] == $this->GetBuffer("Serial_Display_RxD")) {
						$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_data", "Value"=> utf8_encode($Result) )));
					}
					elseif ($response[2] == $this->GetBuffer("Serial_PTLB10VE_RxD")) {
						//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_PTLB10VE_data", "Value"=> $Message )));
						$Result = substr($Message, -($response[4]));
					}
					elseif ($response[2] == $this->GetBuffer("Serial_SDS011_RxD")) {
						//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_SDS011_data", "Value"=> utf8_encode($Result) )));
						$Result = substr($Message, -($response[4]));
						//$Result = $Message;
					}
					//$this->SendDebug("Serielle Daten", "Text: ".$Result, 0);
				}
		            	else {
           				$Result = -1;
					$this->SendDebug("Serielle Daten", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("IPS2GPIO Serielle Daten", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
				break;
			case "44":
           			If ($response[4] >= 0) {
           				$Result = true;
           			}
           			else {
           				$Result = false;
					// $this->SendDebug("Serial Bit Bang", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					//IPS_LogMessage("IPS2GPIO","Serial Bit Bang mit Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
			case "49":
           			If ($response[4] >= 0) {
           				$Result = $response[4];
           			}
           			else {
           				$this->SendDebug("Waveform", "erstellt, Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("IPS2GPIO Waveform", "erstellt, Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
			case "50":
           			If ($response[4] >= 0) {
           				$Result = $response[4];
           			}
           			else {
           				$this->SendDebug("Waveform", "geloescht, Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("IPS2GPIO Waveform", "geloescht, Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
			case "51":
           			If ($response[4] >= 0) {
           				$Result = $response[4];
           			}
           			else {
           				$this->SendDebug("Waveform", "gesendet, Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("IPS2GPIO Waveform", "gesendet, Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
			case "54":
		        	If ($response[4] >= 0 ) {
           				If ($this->GetBuffer("I2CSearch") == 0) {
						// Hier wird der ermittelte Handle der DiviceAdresse/Bus hinzugefügt
						$I2C_DeviceHandle[($response[2] << 7) + $response[3]] = $response[4];
					}
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO I2C Handle","Fehlermeldung: ".$this->GetErrorText(abs($response[4]))." Handle für Device ".$response[3]." nicht vergeben!");
           			}
           			
		        	break;
		        case "55":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Close Handle","Handle: ".$response[2]." Value: ".$response[4]);
           			}
           			else {
           				//IPS_LogMessage("IPS2GPIO I2C Close Handle","Handle: ".$response[2]." Value: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "56":
           			If ($response[4] >= 0) {
					//IPS_LogMessage("IPS2GPIO I2C Read Bytes","Handle: ".$response[2]." Register: ".$response[3]." Count: ".$response[4]);
					$ByteMessage = substr($Message, -($response[4]));
					$ByteResponse = unpack("C*", $ByteMessage);
					$ByteArray = serialize($ByteResponse);
					$Result = serialize($ByteResponse);
					//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_byte_block", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Count" => $response[4], "ByteArray" => $ByteArray)));
				}
		            	else {
           				IPS_LogMessage("IPS2GPIO I2C Read Bytes","Handle: ".$response[2]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
				break; 
			case "59":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Read Byte Handle","Handle: ".$response[2]." Value: ".$response[4]);
		            		If ($this->GetBuffer("I2CSearch") == 0) {
						//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Value" => $response[4])));
					}
           			}
           			else {
           				If ($this->GetBuffer("I2CSearch") == 0) {
						IPS_LogMessage("IPS2GPIO I2C Read Byte Handle","Handle: ".$response[2]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
					}
           			}
		            	break;
		        case "60":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Write Byte Handle","Handle: ".$response[2]." Value: ".$response[4]);
		            		$Result = true;
					$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
           			}
           			else {
           				$Result = false;
					IPS_LogMessage("IPS2GPIO I2C Write Byte Handle","Handle: ".$response[2]." Value: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "61":
		            	If ($response[4] >= 0) {
					//IPS_LogMessage("IPS2GPIO I2C Read Byte","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]." DeviceSign: ".$this->GetI2C_HandleDevice($response[2]));
		            		//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
		            	}
		            	else {
					If ($this->GetBuffer("I2CSearch") == 0) {
						IPS_LogMessage("IPS2GPIO I2C Read Byte","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));	
					}
		            	}
		            	break;
		        case "62":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Write Byte","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]);
		            		//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
					$Result = true;
           			}
           			else {
           				$Result = false;
					IPS_LogMessage("IPS2GPIO I2C Write Byte","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
		            	break;
		        case "63":
		            	If ($response[4] >= 0) {
		            		//IPS_LogMessage("IPS2GPIO I2C Read Word","Handle: ".$response[2]." Register: ".$response[3]." Value: ".$response[4]);
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
		            	}
		            	else {
		            		IPS_LogMessage("IPS2GPIO I2C Read Word","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
		            	}
		            	break;
		        case "67":
           			If ($response[4] >= 0) {
					//IPS_LogMessage("IPS2GPIO I2C Read Block Byte","Handle: ".$response[2]." Register: ".$response[3]." Count: ".$response[4]." DeviceSign: ".$this->GetI2C_HandleDevice($response[2]));
					$ByteMessage = substr($Message, -($response[4]));
					$ByteResponse = unpack("C*", $ByteMessage);
					$ByteArray = serialize($ByteResponse);
					$Result = serialize($ByteResponse);
					$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_byte_block", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Count" => $response[4], "ByteArray" => $ByteArray)));
				}
				else {
		            		IPS_LogMessage("IPS2GPIO I2C Read Block Byte","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
		            	}
				break;
			case "68":
           			If ($response[4] >= 0) {
					$Result = true;
				}
				else {
					$Result = false;
				}
		            	break;
		        case "76":
           			If ($response[4] >= 0) {
					// 
				}
				else {
					IPS_LogMessage("IPS2GPIO I2C Get Serial Handle","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
				}
		            	break;
		        case "77":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Serial Close Handle","Serial Handle: ".$response[2]." Value: ".$response[4]);
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Serial Close Handle","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));	
           			}
           			
		            	break;
		        case "80":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Serial Read","Serial Handle: ".$response[2]." Value: ".substr($Message, -($response[4])));
           				If ($response[4] > 0) {
	           				$Result = utf8_encode(substr($Message, -($response[4])));
						//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_data", "Value"=>utf8_encode(substr($Message, -($response[4]))) )));
           				}
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Serial Read","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
  		            	break;
		        case "81":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Serial Write","Serial Handle: ".$response[2]." Value: ".$response[4]);
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Serial Write","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
  		            	break;
  		        case "82":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO Check Bytes Serial","Serial Handle: ".$response[2]." Bytes zum Lesen: ".$response[4]);
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO Check Bytes Serial","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
          			}
  		            	break;
			case "83":
           			If ($response[4] >= 0) {
           				
           			}
           			else {
					IPS_LogMessage("IPS2GPIO PWM dutycycle","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
          			}
  		            	break;
			case "84":
           			If ($response[4] >= 0) {
           				
           			}
           			else {
					IPS_LogMessage("IPS2GPIO Check Servo Pulsewidth","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
          			}
  		            	break;
		        case "93":
           			If ($response[4] >= 0) {
           				$this->SendDebug("WaveChain", "erfolgreich", 0);
           			}
           			else {
					IPS_LogMessage("IPS2GPIO WaveChain","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
          			}
  		            	break;
			
			case "97":
           			If ($response[4] >= 0) {
           				$this->SendDebug("GlitchFilter", "gesetzt", 0);
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO GlitchFilter","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
         
		            	break;
		        case "99":
           			If ($response[4] >= 0 ) {
           				//$this->SendDebug("Handle", $response[4], 0);
					$this->SetBuffer("Handle", $response[4]);
         			}
           			else {
           				$this->ClientSocket(pack("L*", 99, 0, 0, 0));		
           			}
           			break;
			case "115":
           			If ($response[4] >= 0) {
           				$this->SendDebug("Event Monitor", "gesetzt", 0);
           			}
           			else {
           				$this->SendDebug("Event Monitor", "Fehler beim Setzen: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("IPS2GPIO Set Event Monitor","Fehler beim Setzen: ".$this->GetErrorText(abs($response[4])));
           			}
         
		            	break;
			case "116":
           			If ($response[4] >= 0) {
					$this->SendDebug("Event Monitor", "gemeldet", 0);
           			}
           			else {
           				$this->SendDebug("Event Monitor", "Fehlermeldung: ".$this->GetErrorText(abs($response[4])), 0);
					IPS_LogMessage("IPS2GPIO Trigger Event","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
         
		            	break;
	
		}
	return $Result;
	}
	
	public function CheckSerial()
	{
		$Result = $this->CommandClientSocket(pack("L*", 82, $this->GetBuffer("Serial_Handle"), 0, 0), 16);
		//IPS_LogMessage("GeCoS_IO CheckSerial", $Result);
		If ($Result > 0) {
			$Data = $this->CommandClientSocket(pack("L*", 80, $this->GetBuffer("Serial_Handle"), $Result, 0), 16 + $Result);
			$Message = utf8_encode($Data);
			$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_data", "Value" => $Message )));
			//$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_data", "Buffer" => $Message)));
			//IPS_LogMessage("GeCoS_IO CheckSerial", $Data);
		}
		
	}
	
	private function SendProc(String $Message)
	{
		// Sendet ein Skript an PIGPIO
		$Result = $this->CommandClientSocket(pack("L*", 38, 0, 0, strlen($Message)).pack("C*", $Message), 16);
		If ($Result < 0) {
			$this->SendDebug("Skriptsendung", "Fehlgeschlagen!", 0);
			return -1;
		}
		else {
			$this->SendDebug("Skriptsendung", "Skript-ID: ".(int)$Result, 0);
			return $Result;
		}
	}
	
	private function StartProc(Int $ScriptID, String $Parameter)
	{
		// Startet ein PIGPIO-Skript32768, 50, 1
		$ParameterArray = array();
		$ParameterArray = unserialize($Parameter);
		$Result = $this->CommandClientSocket(pack("L*", 40, $ScriptID, 0, 4 * count($ParameterArray)).pack("L*", ...$ParameterArray), 16);
		//$Result = $this->CommandClientSocket(pack("L*", 40, $ScriptID, 0, (4 * count($ParameterArray)), ...$ParameterArray), 16);
		//$Result = $this->CommandClientSocket(pack("L*", 40, $ScriptID, 0, (4 * 3), 32768, 50, 1), 16);
		If ($Result < 0) {
			$this->SendDebug("Skriptstart", "Skript-ID: ".(int)$ScriptID." Anzahl Parameter: ".count($ParameterArray), 0);
			$this->SendDebug("Skriptstart", "Fehlgeschlagen!", 0);
			return -1;
		}
		else {
			$StatusArray = array("wird initialisiert", "angehalten", "laeuft", "wartet", "fehlerhaft");
			$this->SendDebug("Skriptstart", "Skript-ID: ".(int)$ScriptID. " Status: ".($StatusArray[(int)$Result]), 0);
			/*
			0	being initialised
			1	halted
			2	running
			3	waiting
			4	failed
			*/
			
			return $Result;
		}
	}
	
	public function PIGPIOD_Restart()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			// Verbindung trennen
			IPS_SetProperty($this->GetParentID(), "Open", false);
			IPS_ApplyChanges($this->GetParentID());
			// PIGPIO beenden und neu starten
			$this->SSH_Connect("sudo killall pigpiod");
			If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == true) {
				SetValueBoolean($this->GetIDForIdent("PigpioStatus"), false);
			}
			// Wartezeit
			IPS_Sleep(2000);
			If ($this->ReadPropertyString("User") == "root") {
				If ($this->ReadPropertyBoolean("AudioDAC") == true) {
					$this->SSH_Connect("pigpiod -t 0 -s 10");
				}
				else {
					$this->SSH_Connect("pigpiod -s 10");
				}
			}
			else {
				If ($this->ReadPropertyBoolean("AudioDAC") == true) {
					$this->SSH_Connect("sudo pigpiod -t 0 -s 10");
				}
				else {
					$this->SSH_Connect("sudo pigpiod -s 10");
				}
			}
			// Wartezeit
			IPS_Sleep(2000);
			IPS_SetProperty($this->GetParentID(), "Open", true);
			IPS_ApplyChanges($this->GetParentID());			
		}
	}
	
	public function SSH_Connect(String $Command)
	{
	        If (($this->ReadPropertyBoolean("Open") == true) ) {
			set_include_path(__DIR__.'/libs');
			require_once (__DIR__ . '/libs/Net/SSH2.php');

			$ssh = new Net_SSH2($this->ReadPropertyString("IPAddress"));
			$login = @$ssh->login($this->ReadPropertyString("User"), $this->ReadPropertyString("Password"));
			if ($login == false)
			{
			    	IPS_LogMessage("IPS2GPIO SSH-Connect","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			    	$Result = "";
				return false;
			}
			$Result = $ssh->exec($Command);

			$ssh->disconnect();
		}
		else {
			$Result = "";
		}
	
        return $Result;
	}

	private function SSH_Connect_Array(String $Command)
	{
	        If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			set_include_path(__DIR__.'/libs');
			require_once (__DIR__ . '/libs/Net/SSH2.php');

			$ssh = new Net_SSH2($this->ReadPropertyString("IPAddress"));
			$login = @$ssh->login($this->ReadPropertyString("User"), $this->ReadPropertyString("Password"));
			if ($login == false)
			{
			    	IPS_LogMessage("IPS2GPIO SSH-Connect","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			    	$Result = "";
				return false;
			}
			$ResultArray = Array();
			$CommandArray = unserialize($Command);
			for ($i = 0; $i < Count($CommandArray); $i++) {
				$ResultArray[key($CommandArray)] = $ssh->exec($CommandArray[key($CommandArray)]);
				next($CommandArray);
			}
			$ssh->disconnect();
			$Result = serialize($ResultArray);
		}
		else {
			$ResultArray = Array();
			$Result = serialize($ResultArray);
		}
		//IPS_LogMessage("IPS2GPIO SSH-Connect","Ergebnis: ".$Result);
        return $Result;
	}
	
	private function GetOneWireDevices()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			set_include_path(__DIR__.'/libs');
			require_once (__DIR__ . '/libs/Net/SFTP.php');

			$sftp = new Net_SFTP($this->ReadPropertyString("IPAddress"));
			$login = @$sftp->login($this->ReadPropertyString("User"), $this->ReadPropertyString("Password"));
			
			if ($login == false)
			{
			    	IPS_LogMessage("IPS2GPIO SFTP-Connect","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			    	$Result = "";
				return false;
			}
			//IPS_LogMessage("IPS2GPIO SFTP-Connect","Verbindung hergestellt");
			
			$Path = "/sys/bus/w1/devices";
			// Prüfen, ob der 1-Wire Server die Verzeichnisse angelegt hat
			if (!$sftp->file_exists($Path)) {
				IPS_LogMessage("IPS2GPIO SFTP-Connect",$Path." nicht gefunden! Ist 1-Wire aktiviert?");
				return;
			}
			
			// den Inhalt des Verzeichnisses ermitteln
			$Sensors = array();
			$Dir = $sftp->nlist($Path);
			for ($i = 0; $i < Count($Dir); $i++) {
				if ($Dir[$i] != "." && $Dir[$i] != ".." && $Dir[$i] != "w1_bus_master1") {
					$Sensors[] = $Dir[$i];
					//IPS_LogMessage("IPS2GPIO SFTP-Connect", $Dir[$i]);
				}
			}
			
			$Result = serialize($Sensors);
			
		}
		else {
			$ResultArray = Array();
			$Result = serialize($ResultArray);
		}
	return $Result;
	}
		
	private function CalcBitmask()
	{
		$PinNotify = array();
		//$this->SendDebug("CalcBitmask", "PinNotify: ".$this->GetBuffer("PinNotify"), 0);
		$PinNotify = unserialize($this->GetBuffer("PinNotify"));
		$Bitmask = 0;
		for ($i = 0; $i < Count($PinNotify); $i++) {
    			$Bitmask = $Bitmask + pow(2, $PinNotify[$i]);
		}
		$this->SetBuffer("NotifyBitmask", $Bitmask);
	return $Bitmask;	
	}
	
	private function ConnectionTest()
	{
	      $result = false;
	      If (Sys_Ping($this->ReadPropertyString("IPAddress"), 2000)) {
			//IPS_LogMessage("IPS2GPIO Netzanbindung","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert");
		      	$this->SendDebug("Netzanbindung", "Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert", 0);
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geschlossen!");
					$this->SendDebug("Netzanbindung", "Port ist geschlossen!", 0);
					If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == true) {
						SetValueBoolean($this->GetIDForIdent("PigpioStatus"), false);
					}
					// Versuchen PIGPIO zu starten
					IPS_LogMessage("IPS2GPIO Netzanbindung: ","Versuche PIGPIO per SSH zu starten...");
					$this->SendDebug("Netzanbindung", "Versuche PIGPIO per SSH zu starten...", 0);
					If ($this->ReadPropertyString("User") == "root") {
						If ($this->ReadPropertyBoolean("AudioDAC") == true) {
							$this->SSH_Connect("pigpiod -t 0 -s 10");
						}
						else {
							$this->SSH_Connect("pigpiod -s 10");
						}
					}
					else {
						If ($this->ReadPropertyBoolean("AudioDAC") == true) {
							$this->SSH_Connect("sudo pigpiod -t 0 -s 10");
						}
						else {
							$this->SSH_Connect("sudo pigpiod -s 10");
						}
					}
					$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
					if (!$status) {
						IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geschlossen!");
						$this->SendDebug("Netzanbindung", "Port ist geschlossen!", 0);
						If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == true) {
							SetValueBoolean($this->GetIDForIdent("PigpioStatus"), false);
						}
						$this->SetStatus(104);
					}
					else {
						fclose($status);
						//IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geöffnet");
						$this->SendDebug("Netzanbindung", "Port ist geoeffnet", 0);
						$result = true;
						$this->SetStatus(102);
					}
	   			}
	   			else {
	   				fclose($status);
					//IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geöffnet");
					$this->SendDebug("Netzanbindung", "Port ist geoeffnet", 0);
					$result = true;
					$this->SetStatus(102);
	   			}
		}
		else {
			IPS_LogMessage("GPIO Netzanbindung: ","IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			$this->SendDebug("Netzanbindung", "IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!", 0);
			If (GetValueBoolean($this->GetIDForIdent("PigpioStatus")) == true) {
				SetValueBoolean($this->GetIDForIdent("PigpioStatus"), false);
			}
			$this->SetStatus(104);
		}
	return $result;
	}
	
	private function SetI2CBus($DeviceIdent)
	{
		// DeviceBus aus dem DeviceIdent extrahieren
		$DeviceBus = $DeviceIdent >> 7;
		// Umschaltung wenn an einen MUX angeschlossen
		If ($DeviceBus >= 3) {
			$this->SetMUX($DeviceBus);
		}	
	}
	
	private function SetMUX($Port)
	{
		$Success = false;
		$DevicePorts = array();
		$DevicePorts[0] = "I2C-Bus 0";
		$DevicePorts[1] = "I2C-Bus 1";
		for ($i = 3; $i <= 10; $i++) {
			$DevicePorts[$i] = "MUX I2C-Bus ".($i - 3);
		}
		// PCA9542
		// 0 = No Channel selected
		// 4 = Channel 0
		// 5 = Channel 1
		
		// TCA9548a
		// 0 = No Channel selected
		// 1 = Channel 0 enable
		// 2 = Channel 1 enable
		// 4 = Channel 2 enable
		// 8 = Channel 3 enable
		// 16 = Channel 4 enable
		// 32 = Channel 5 enable
		// 64 = Channel 6 enable
		// 128 = Channel 7 enable
		
		If (intval($this->GetBuffer("MUX_Channel")) <> $Port) {
			$this->SetBuffer("MUX_Channel", $Port);
			$MUX_Handle = $this->GetBuffer("MUX_Handle");
			$MUX = $this->ReadPropertyInteger("MUX");
			
			If ($MUX_Handle >= 0) {
				If (($Port == 0) OR ($Port == 1)) {
					// Ausschalten des MUX
					$Result = $this->CommandClientSocket(pack("L*", 60, $MUX_Handle, 0, 0), 16);
					If ($Result > 0) {
						$this->SendDebug("SetMUX", "MUX ausgeschaltet", 0);
						$Success = true;
					}
					else {
						$this->SendDebug("SetMUX", "Es ist ein Fehler aufgetreten!", 0);
						$Success = false;
					}	
				}
				else {
					$DevicePort = 0;
					If ($MUX == 1) {
						// TCA9548a Adr. 112/0x70
						$DevicePort = $Port - 3;
						$DevicePort = pow(2, $DevicePort);
					}
					elseif ($MUX == 2) {
						// PCA9542 Adr. 112/0x70
						$DevicePort = $DevicePort + 1;
					}
					// Den MUX auf den richtigen Kanal setzen
					
					$Result = $this->CommandClientSocket(pack("L*", 60, $MUX_Handle, $DevicePort, 0), 16);
					If ($Result > 0) {
						$this->SendDebug("SetMUX", "MUX-Umschaltung auf ".$DevicePorts[$Port], 0);
						$Success = true;
					}
					else {
						$this->SendDebug("SetMUX", "Es ist ein Fehler aufgetreten!", 0);
						$Success = false;
					}	
				}
			}	
			else {
				$this->SendDebug("SetMUX", "MUX konnte nicht gesetzt werden!", 0);
			}
		}
	return $Success;
	}

	private function GetI2C_DeviceHandle(Int $DeviceAddress)
	{
		// Gibt für ein Device den verknüpften Handle aus
		$I2C_HandleData = unserialize($this->GetBuffer("I2C_Handle"));
 		If (array_key_exists($DeviceAddress, $I2C_HandleData)) {
 			$I2C_Handle = $I2C_HandleData[$DeviceAddress];
 		}
 		else {
 			$I2C_Handle = -1;	
 		}			  
	return $I2C_Handle;
	}
	
	private function GetI2C_HandleDevice(Int $I2C_Handle)
	{
		// Gibt für ein I2C-Device die Adresse aus
		$I2C_HandleData = unserialize($this->GetBuffer("I2C_Handle"));
 		If (array_search($I2C_Handle, $I2C_HandleData) == false) {
 			$I2C_Device = -1;
 		}
 		else {
 			$I2C_Device = array_search($I2C_Handle, $I2C_HandleData);	
 		}			  
	return $I2C_Device;
	}
	
	private function ResetI2CHandle($MinHandle = 0)
	{
		$this->SendDebug("ResetI2CHandle", "I2C Handle loeschen", 0);
		$Handle = $this->CommandClientSocket(pack("L*", 54, 1, 1, 4, 0), 16);
		for ($i = $MinHandle; $i <= $Handle ; $i++) {
			$this->CommandClientSocket(pack("L*", 55, $i, 0, 0), 16);
		}
	}
	
	private function ResetSerialHandle()
	{
		$this->SendDebug("ResetSerialHandle", "Serial Handle loeschen", 0);
		$SerialHandle = $this->CommandClientSocket(pack("L*", 76, 9600, 0, strlen("/dev/serial0") )."/dev/serial0", 16);
		for ($i = 0; $i <= $SerialHandle; $i++) {
			$this->CommandClientSocket(pack("L*", 77, $i, 0, 0), 16);
		}
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
	
	private function CheckConfig()
	{
		$arrayCheckConfig = array();
		$arrayCheckConfig["I2C"]["Status"] = "unbekannt";
		$arrayCheckConfig["I2C"]["Color"] = "#FFFF00";
		$this->SetBuffer("I2C_Enabled", 0);
		$arrayCheckConfig["Serielle Schnittstelle"]["Status"] = "unbekannt";
		$arrayCheckConfig["Serielle Schnittstelle"]["Color"] = "#FFFF00";
		$arrayCheckConfig["Shell Zugriff"]["Status"] = "unbekannt";
		$arrayCheckConfig["Shell Zugriff"]["Color"] = "#FFFF00";
		$arrayCheckConfig["PIGPIO Server"]["Status"] = "unbekannt";
		$arrayCheckConfig["PIGPIO Server"]["Color"] = "#FFFF00";
		$arrayCheckConfig["1-Wire-Server"]["Status"] = "unbekannt";
		$arrayCheckConfig["1-Wire-Server"]["Color"] = "#FFFF00";
		
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			set_include_path(__DIR__.'/libs');
			require_once (__DIR__ . '/libs/Net/SFTP.php');
			$sftp = new Net_SFTP($this->ReadPropertyString("IPAddress"));
			$login = @$sftp->login($this->ReadPropertyString("User"), $this->ReadPropertyString("Password"));
			
			if ($login == false)
			{
			    	$this->SendDebug("CheckConfig", "Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!", 0);
				IPS_LogMessage("IPS2GPIO CheckConfig","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			    	$Result = "";
				return serialize($arrayCheckConfig);
			}
			
			// I²C Schnittstelle
			$PathConfig = "";
			// Erster Versuch bei Standardinstallationen
			if ($sftp->file_exists("/boot/config.txt")) {
				$PathConfig = "/boot/config.txt";
			}
			// Zweiter Versuch für Kodi LibreELEC
			elseif ($sftp->file_exists("/flash/config.txt")) {
				$PathConfig = "/flash/config.txt";
			}
			else {
				$this->SendDebug("CheckConfig", "config.txt wurde nicht gefunden!", 0);
				IPS_LogMessage("IPS2GPIO CheckConfig", "config.txt wurde nicht gefunden!");
			}
			
			If ($PathConfig <> "") {
				$FileContentConfig = $sftp->get($PathConfig);
				// Prüfen ob I2C aktiviert ist
				$Pattern = "/(?:\r\n|\n|\r)(\s*)(device_tree_param|dtparam)=([^,]*,)*i2c(_arm)?(=(on|true|yes|1))(\s*)($:\r\n|\n|\r)/";
				if (preg_match($Pattern, $FileContentConfig)) {
					$this->SendDebug("CheckConfig", "I2C ist aktiviert", 0);
					$arrayCheckConfig["I2C"]["Status"] = "aktiviert";
					$arrayCheckConfig["I2C"]["Color"] = "#00FF00";
					$this->SetBuffer("I2C_Enabled", 1);
				} else {
					$this->SendDebug("CheckConfig", "I2C ist deaktiviert!", 0);
					IPS_LogMessage("IPS2GPIO CheckConfig", "I2C ist deaktiviert!");
					$arrayCheckConfig["I2C"]["Status"] = "deaktiviert";
					$arrayCheckConfig["I2C"]["Color"] = "#FF0000";
					$this->SetBuffer("I2C_Enabled", 0);
				}
				// Prüfen ob 1-Wie-Server aktiviert ist
				$Pattern = "/(?:\r\n|\n|\r)(\s*)(dtoverlay)(=(w1-gpio))(\s*)($:\r\n|\n|\r)/";
				if (preg_match($Pattern, $FileContentConfig)) {
					$this->SendDebug("CheckConfig", "1-Wire-Server ist aktiviert", 0);
					$arrayCheckConfig["1-Wire-Server"]["Status"] = "aktiviert";
					$arrayCheckConfig["1-Wire-Server"]["Color"] = "#00FF00";			
				} else {
					$this->SendDebug("CheckConfig", "1-Wire-Server ist deaktiviert!", 0);
					IPS_LogMessage("IPS2GPIO CheckConfig", "1-Wire-Server ist deaktiviert!");
					$arrayCheckConfig["1-Wire-Server"]["Status"] = "deaktiviert";
					$arrayCheckConfig["1-Wire-Server"]["Color"] = "#FF0000";
				}
				// Prüfen ob die serielle Schnittstelle aktiviert ist
				$Pattern = "/(?:\r\n|\n|\r)(\s*)(enable_uart)(=(on|true|yes|1))(\s*)($:\r\n|\n|\r)/";
				if (preg_match($Pattern, $FileContentConfig)) {
					$this->SendDebug("CheckConfig", "Serielle Schnittstelle ist aktiviert!", 0);
					IPS_LogMessage("IPS2GPIO CheckConfig", "Serielle Schnittstelle ist aktiviert!");
					$arrayCheckConfig["Serielle Schnittstelle"]["Status"] = "aktiviert";
					$arrayCheckConfig["Serielle Schnittstelle"]["Color"] = "#FF0000";			
				} else {
					$this->SendDebug("CheckConfig", "Serielle Schnittstelle ist deaktiviert!", 0);
					$arrayCheckConfig["Serielle Schnittstelle"]["Status"] = "deaktiviert";
					$arrayCheckConfig["Serielle Schnittstelle"]["Color"] = "#00FF00";
				}
				
			}
			
			//Serielle Schnittstelle
			$PathCmdline = "";
			// Erster Versuch bei Standardinstallationen
			if ($sftp->file_exists("/boot/cmdline.txt")) {
				$PathCmdline = "/boot/cmdline.txt";
			}
			// Zweiter Versuch für Kodi LibreELEC
			elseif ($sftp->file_exists("/flash/cmdline.txt")) {
				$PathCmdline = "/flash/cmdlineg.txt";
			}
			else {
				$this->SendDebug("CheckConfig", "cmdline.txt wurde nicht gefunden!", 0);
				IPS_LogMessage("IPS2GPIO CheckConfig", "cmdline.txt wurde nicht gefunden!");
			}
			
			If ($PathCmdline <> "") {
				$FileContentCmdline = $sftp->get($PathCmdline);
				// Prüfen ob die Shell der serielle Schnittstelle aktiviert ist
				$Pattern = "/console=(serial0|ttyAMA(0|1)|tty(0|1))/";
				if (preg_match($Pattern, $FileContentCmdline)) {
					$this->SendDebug("CheckConfig", "Shell-Zugriff auf serieller Schnittstelle ist deaktiviert", 0);
					$arrayCheckConfig["Shell Zugriff"]["Status"] = "deaktiviert";
					$arrayCheckConfig["Shell Zugriff"]["Color"] = "#00FF00";
				} else {
					$this->SendDebug("CheckConfig", "Shell-Zugriff auf serieller Schnittstelle ist aktiviert!", 0);
					IPS_LogMessage("IPS2GPIO CheckConfig", "Shell-Zugriff auf serieller Schnittstelle ist aktiviert!");
					$arrayCheckConfig["Shell Zugriff"]["Status"] = "aktiviert";
					$arrayCheckConfig["Shell Zugriff"]["Color"] = "#FF0000";
				}
			}
			
			//PIGPIOD
			$PathPIGPIOD = "/etc/systemd/system/pigpiod.service.d/public.conf";
			// Prüfen, ob die Datei existiert
			if ($sftp->file_exists($PathPIGPIOD)) {
				$this->SendDebug("CheckConfig", "PIGPIO-Server ist aktiviert", 0);
				$arrayCheckConfig["PIGPIO Server"]["Status"] = "aktiviert";
				$arrayCheckConfig["PIGPIO Server"]["Color"] = "#00FF00";
			}
			else {
				$this->SendDebug("CheckConfig", "PIGPIO-Server ist deaktiviert!", 0);
				IPS_LogMessage("IPS2GPIO CheckConfig", "PIGPIO-Server ist deaktiviert!");
				$arrayCheckConfig["PIGPIO Server"]["Status"] = "deaktiviert";
				$arrayCheckConfig["PIGPIO Server"]["Color"] = "#FF0000";
			}
			
		}
			
	return serialize($arrayCheckConfig);
	}
  	
	private function SearchI2CDevices()
	{
		$DeviceArray = Array();
		$DeviceName = Array();
		$SearchArray = Array();
		$DevicePorts = array();
		$DevicePorts[0] = "I²C-Bus 0";
		$DevicePorts[1] = "I²C-Bus 1";
		for ($i = 3; $i <= 10; $i++) {
			$DevicePorts[$i] = "MUX I²C-Bus ".($i - 3);
		}
		
		
		$k = 0;
		// AS3935
		for ($i = 3; $i <= 4; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "AS3935";
		}
		// S.USV
		$SearchArray[] = 15;
		$DeviceName[] = "S.USV";
		// PCF8574
		for ($i = 32; $i <= 34; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8574|MCP23017";
		}
		// BH1750
		$SearchArray[] = 35;
		$DeviceName[] = "BH1750|MCP23017";
		// PCF8574
		for ($i = 36; $i <= 39; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8574|MCP23017";
		}
		// PCF8574
		$SearchArray[] = 56;
		$DeviceName[] = "PCF8574";
		// APDS9960
		$SearchArray[] = 57;
		$DeviceName[] = "PCF8574|APDS9960";
		// PCF8574
		for ($i = 58; $i <= 59; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8574";
		}
		// PCF8574|SSD1306
		for ($i = 60; $i <= 61; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8574|SSD1306";
		}
		// PCF8574
		for ($i = 62; $i <= 63; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8574";
		}
		// PCF8591
		for ($i = 72; $i <= 79; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8591";
		}
		// PCF8583
		for ($i = 80; $i <= 81; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8583";
		}
		// GeCoS PWM16Out PCA9685
		for ($i = 82; $i <= 87; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "GeCoS PWM16Out";
		}
		// GeCoS RGBW PCA9685
		for ($i = 88; $i <= 90; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "GeCoS RGBW";
		}
		// iAQ
		for ($i = 90; $i <= 91; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "iAQ";
		}
		// BH1750
		$SearchArray[] = 92;
		$DeviceName[] = "BH1750";
		// iAQ
		for ($i = 93; $i <= 95; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "iAQ";
		}
		// MCP3424|DS3231
		$SearchArray[] = 104;
		$DeviceName[] = "MCP3424|DS3231";
		// MCP3424
		for ($i = 105; $i <= 110; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "MCP3424";
		}
		// BME280+BME680
		$SearchArray[] = 118;
		$DeviceName[] = "BME280/680";
		// BME280+BME680+BmP180
		$SearchArray[] = 119;
		$DeviceName[] = "BME280/680/BMP180";
						
		// Start der Suche markieren
		$this->SetBuffer("I2CSearch", 1);
		
		// Start der Suche bestimmen
		If ($this->ReadPropertyInteger("I2C0") == 1) {
			// Wenn der Bus 0 genutzt wird
			$I2CSearchStart = 0;
		}
		else {
			// in allen anderen Fällen
			$I2CSearchStart = 1;
		}
		$MUX = $this->ReadPropertyInteger("MUX");
		If ($MUX == 1) {
			// TCA9548a Adr. 112/0x70
			$I2CSearchEnd = 10;
		}
		elseif ($MUX == 2) {
			// PCA9542 Adr. 112/0x70
			$I2CSearchEnd = 5;
		}
		else {
			$I2CSearchEnd = 1;
		}
		
		for ($j = $I2CSearchStart; $j <= $I2CSearchEnd; $j++) {
			// es gibt keinen DeviceBus 2
			If ($j == 2) {
				$j++;
			}
			
			for ($i = 0; $i < count($SearchArray); $i++) {
				
				// Handle ermitteln
				$DeviceBus = min(1, $j);
				$Handle = $this->CommandClientSocket(pack("L*", 54, $DeviceBus, $SearchArray[$i], 4, 0), 16);
				//$this->SendDebug("SearchI2CDevices", "Device prüfen auf Bus: ".$j." Adresse: ".$i, 0);

				if ($Handle >= 0) {
					// MUX auf den entsprechende Bus umschalten
					If ($j >= 3) {
						$this->SetMUX($j); 
					}
					else {
						$this->SetMUX(0);
					}
					// Testweise lesen
					$Result = $this->CommandClientSocket(pack("L*", 59, $Handle, 0, 0), 16);
					//$this->SendDebug("SearchI2CDevices", "Device lesen auf Bus: ".$j." Adresse: ".$i, 0);

					If ($Result >= 0) {
						$this->SendDebug("SearchI2CDevices", "Device gefunden auf Bus: ".$j." Adresse: ".$SearchArray[$i]." Ergebnis des Test-Lesen: ".$Result, 0);
						$DeviceArray[$k][0] = $this->I2CDeviceSpecification($DeviceName[$i], $Handle, $SearchArray[$i]);
						$DeviceArray[$k][1] = $SearchArray[$i];
						$DeviceArray[$k][2] = $DevicePorts[$j];
						$DeviceArray[$k][3] = 0;
						$DeviceArray[$k][4] = "OK";
						// Farbe gelb für erreichbare aber nicht registrierte Instanzen
						$DeviceArray[$k][5] = "#FFFF00";
						$k = $k + 1;
						//IPS_LogMessage("GeCoS_IO I2C-Suche","Ergebnis: ".$DeviceName[$i]." DeviceAddresse: ".$SearchArray[$i]." an Bus: ".($j - 4));
					}
					// Handle löschen
					$Result = $this->CommandClientSocket(pack("L*", 55, $Handle, 0, 0), 16);
					//$this->SendDebug("SearchI2CDevices", "Ergebnis des Handle-Loeschen: ".$Result, 0);
				}
					
			}
		}
		$this->SetBuffer("I2CSearch", 0);
	return serialize($DeviceArray);
	}
	
	private function I2CDeviceSpecification($DefaultDeviceName, Int $Handle, Int $DeviceAddress)
	{
		$DeviceName = $DefaultDeviceName;
		If (($DeviceAddress == 118) OR ($DeviceAddress == 119)) {
			// BME280/680
			// Lesen der ChipID
			$Result = $this->CommandClientSocket(pack("L*", 61, $Handle, hexdec("D0"), 0), 16);
			If ($Result < 0) {
				$this->SendDebug("I2CDeviceSpecification", "Fehler beim Einlesen der BME Chip ID", 0);
			}
			else {
				If ($Result == 96) {
					$DeviceName = "BME280";
				}
				elseif ($Result == 97) {
					$DeviceName = "BME680";
				}
				elseif ($Result == 85) {
					$DeviceName = "BMP180";
				}
			}
		}
		/*
		elseIf (($DeviceAddress >= 32) AND ($DeviceAddress <= 39)) {
			// PCF8574/BH1750/MCP23017
			$Result = $this->CommandClientSocket(pack("L*", 61, $Handle, hexdec("15"), 0), 16);
			If ($Result < 0) {
				$this->SendDebug("I2CDeviceSpecification", "PCF8574/BH1750", 0);
				If ($DeviceAddress == 35) {
					$DeviceName = "BH1750";
				}
				else {
					$DeviceName = "PCF8574";
				}	
			}
			else {
				$DeviceName = "MCP23017";
			}
		}
		*/
	Return $DeviceName;
	}

	private function SearchSpecialI2CDevices(Int $DeviceAddress)
	{
		$Response = false;		
		$this->SetBuffer("I2CSearch", 1);
		
		// Handle ermitteln
		$Handle = $this->CommandClientSocket(pack("L*", 54, 1, $DeviceAddress, 4, 0), 16);
		//$this->SendDebug("SearchI2CDevices", "Device prüfen auf Bus: ".$j." Adresse: ".$i, 0);

		if ($Handle >= 0) {
			// Testweise lesen
			$Result = $this->CommandClientSocket(pack("L*", 59, $Handle, 0, 0), 16);

			If ($Result >= 0) {
				$this->SendDebug("SearchSpecialI2CDevices", "Device gefunden auf Bus: 1 Adresse: ".$DeviceAddress." Ergebnis des Test-Lesen: ".$Result, 0);
				$Response = true;
			}
			else {
				$Response = false;
			}
			// Handle löschen
			$Result = $this->CommandClientSocket(pack("L*", 55, $Handle, 0, 0), 16);
		}
		$this->SetBuffer("I2CSearch", 0);
	return $Response;
	}
	
  	private function GetErrorText(Int $ErrorNumber)
	{
		$ErrorMessage = array(1 => "PI_INIT_FAILED", 2 => "PI_BAD_USER_GPIO", 3 => "PI_BAD_GPIO", 4 => "PI_BAD_MODE", 5 => "PI_BAD_LEVEL", 6 => "PI_BAD_PUD", 7 => "PI_BAD_PULSEWIDTH",
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
			138 => "PI_FILE_IS_A_DIR", 139 => "PI_BAD_SHELL_STATUS", 140 => "PI_BAD_SCRIPT_NAME", 141 => "PI_BAD_SPI_BAUD",
			142 => "PI_NOT_SPI_GPIO", 143 => "PI_BAD_EVENT_ID" );
		If (array_key_exists($ErrorNumber, $ErrorMessage)) {
			$ErrorText = $ErrorMessage[$ErrorNumber];
		}
		else {
			$ErrorText = "unknown Error -".$ErrorNumber;
		}
	return $ErrorText;
	}
  	
	private function GetHardware(Int $RevNumber)
	{
		$Hardware = array(2 => "Rev.0002 Model B PCB-Rev. 1.0 256MB", 3 => "Rev.0003 Model B PCB-Rev. 1.0 256MB", 4 => "Rev.0004 Model B PCB-Rev. 2.0 256MB Sony", 5 => "Rev.0005 Model B PCB-Rev. 2.0 256MB Qisda", 
			6 => "Rev.0006 Model B PCB-Rev. 2.0 256MB Egoman", 7 => "Rev.0007 Model A PCB-Rev. 2.0 256MB Egoman", 8 => "Rev.0008 Model A PCB-Rev. 2.0 256MB Sony", 9 => "Rev.0009 Model A PCB-Rev. 2.0 256MB Qisda",
			13 => "Rev.000d Model B PCB-Rev. 2.0 512MB Egoman", 14 => "Rev.000e Model B PCB-Rev. 2.0 512MB Sony", 15 => "Rev.000f Model B PCB-Rev. 2.0 512MB Qisda", 16 => "Rev.0010 Model B+ PCB-Rev. 1.0 512MB Sony",
			17 => "Rev.0011 Compute Module PCB-Rev. 1.0 512MB Sony", 18 => "Rev.0012 Model A+ PCB-Rev. 1.1 256MB Sony", 19 => "Rev.0013 Model B+ PCB-Rev. 1.2 512MB", 20 => "Rev.0014 Compute Module PCB-Rev. 1.0 512MB Embest",
			21 => "Rev.0015 Model A+ PCB-Rev. 1.1 256/512MB Embest", 10489920 => "Rev.a01040 2 Model B PCB-Rev. 1.0 1GB", 10489921 => "Rev.a01041 2 Model B PCB-Rev. 1.1 1GB Sony", 10620993 => "Rev.a21041 2 Model B PCB-Rev. 1.1 1GB Embest",
			10625090 => "Rev.a22042 2 Model B PCB-Rev. 1.2 1GB Embest", 9437330 => "Rev.900092 Zero PCB-Rev. 1.2 512MB Sony", 9437331 => "Rev.900093 Zero PCB-Rev. 1.3 512MB Sony", 9437377 => "Rev.9000c1 Zero W PCB-Rev. 1.1 512MB Sony", 
			10494082 => "Rev.a02082 3 Model B PCB-Rev. 1.2 1GB Sony", 10625154 => "Rev.a22082 3 Model B PCB-Rev. 1.2 1GB Embest", 44044353 => "Rev.2a01041 2 Model B PCB-Rev. 1.1 1GB Sony (overvoltage)", 10494163 => "Rev.a020d3 3 Model B+ PCB-Rev. 1.3 1GB Sony");
		If (array_key_exists($RevNumber, $Hardware)) {
			$HardwareText = $Hardware[$RevNumber];
		}
		else {
			$HardwareText = "Unbekannte Revisions Nummer!";
		}
		// Einige Besonderheiten setzen
		If ($RevNumber <= 3) {
			$this->SetBuffer("Default_I2C_Bus", 0);
		}
		else {
			$this->SetBuffer("Default_I2C_Bus", 1);
		}
		If (($RevNumber == 10494082) OR ($RevNumber == 10625154)) {
			$this->SetBuffer("Default_Serial_Bus", 1);
		}
		else {
			$this->SetBuffer("Default_Serial_Bus", 0);
		}
	return $HardwareText;
	}
	
	private function GetOWHardware(string $FamilyCode)
	{
		$OWHardware = array("10" => "DS18S20 Temperatur", "12" => "DS2406 Switch", "1D" => "DS2423 Counter" , "28" => "DS18B20 Temperatur", "3A" => "DS2413 2 Ch. Switch", "29" => "DS2408 8 Ch.Switch", "05" => "DS2405 Switch", "26" => "DS2438 Batt.Monitor");
		If (array_key_exists($FamilyCode, $OWHardware)) {
			$OWHardwareText = $OWHardware[$FamilyCode];
		}
		else {
			$OWHardwareText = "Unbekannter 1-Wire-Typ!";
		}
		
	return $OWHardwareText;
	}
	
	private function OWInstanceArraySearch(String $SearchKey, String $SearchValue)
	{
		$Result = 0;
		If (is_array(unserialize($this->GetBuffer("OWInstanceArray"))) == true) {
			$OWInstanceArray = Array();
			$OWInstanceArray = unserialize($this->GetBuffer("OWInstanceArray"));
			If (count($OWInstanceArray, COUNT_RECURSIVE) >= 4) {
				foreach ($OWInstanceArray as $Type => $Properties) {
					foreach ($Properties as $Property => $Value) {
						If (($Property == $SearchKey) AND ($Value == $SearchValue)) {
							$Result = $Type;
						}
					}
				}
			}
		}
	return $Result;
	}
	
	public function OWSearchStart()
	{
		if (IPS_SemaphoreEnter("OW", 3000))
			{
			$this->SetBuffer("owLastDevice", 0);
			$this->SetBuffer("owLastDiscrepancy", 0);
			$this->SetBuffer("owDeviceAddress_0", 0xFFFFFFFF);
			$this->SetBuffer("owDeviceAddress_1", 0xFFFFFFFF);
			$OWDeviceArray = Array();
			$this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));
			$Result = 1;
			$SearchNumber = 0;
			while($Result == 1) {
				$Result = $this->OWSearch($SearchNumber);
				$SearchNumber++;
			}
			IPS_SemaphoreLeave("OW");
		}
		else {
			$this->SendDebug("OWSearchStart", "Semaphore Abbruch", 0);
		}	
	}
	
	private function DS2482Reset() 
	{
    		$this->SendDebug("DS2482Reset", "Function: Resetting DS2482", 0);
		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetBuffer("OW_Handle"), 240, 0), 16); //reset DS2482
		
		If ($Result < 0) {
			$this->SendDebug("DS2482Reset", "DS2482 Reset Failed", 0);
    		}
	}
	
	private function OWSearch(int $SearchNumber)
	{
		$this->SendDebug("SearchOWDevices", "Suche gestartet", 0);
    		$bitNumber = 1;
    		$lastZero = 0;
  		$deviceAddress4ByteIndex = 1; //Fill last 4 bytes first, data from onewire comes LSB first.
     		$deviceAddress4ByteMask = 1;
 		
		if ($this->GetBuffer("owLastDevice")) {
			$this->SendDebug("SearchOWDevices", "OW Suche beendet", 0);
			$this->SetBuffer("owLastDevice", 0);
			$this->SetBuffer("owLastDiscrepancy", 0);
			$this->SetBuffer("owDeviceAddress_0", 0xFFFFFFFF);
			$this->SetBuffer("owDeviceAddress_1", 0xFFFFFFFF);
		}
		else {
			if (!$this->OWReset()) { //if there are no parts on 1-wire, return false
			    	$this->SetBuffer("owLastDiscrepancy", 0);
			return 0;
			}
			$this->OWWriteByte(240); //Issue the Search ROM command
			do { // loop to do the search
				if ($bitNumber < $this->GetBuffer("owLastDiscrepancy")) {
					if ($this->GetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex) & $deviceAddress4ByteMask) {
						$this->SetBuffer("owTripletDirection", 1);
					} 
					else {
						$this->SetBuffer("owTripletDirection", 0);
					}
				} 
				else if ($bitNumber == $this->GetBuffer("owLastDiscrepancy")) { //if equal to last pick 1, if not pick 0
					$this->SetBuffer("owTripletDirection", 1);
				} 
				else {
					$this->SetBuffer("owTripletDirection", 0);
				}
				if (!$this->OWTriplet()) {
					return 0;
				}
				//if 0 was picked then record its position in lastZero
				if ($this->GetBuffer("owTripletFirstBit") == 0 && $this->GetBuffer("owTripletSecondBit") == 0 && $this->GetBuffer("owTripletDirection") == 0) {
					$lastZero = $bitNumber;
				}
				 //check for no devices on 1-wire
				if ($this->GetBuffer("owTripletFirstBit") == 1 && $this->GetBuffer("owTripletSecondBit") == 1) {
					break;
				}
				//set or clear the bit in the SerialNum byte serial_byte_number with mask
				if ($this->GetBuffer("owTripletDirection") == 1) {
					$this->SetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex, intval($this->GetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex)) | $deviceAddress4ByteMask);
					//$this->SendDebug("SearchOWDevices", "owTripletDirection = 1 ".$this->GetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex), 0);
				} 
				else {
					$this->SetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex, intval($this->GetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex)) & (~$deviceAddress4ByteMask));
					//$this->SendDebug("SearchOWDevices", "owTripletDirection = 0 ".$this->GetBuffer("owDeviceAddress_".$deviceAddress4ByteIndex), 0);
				}
				$bitNumber++; //increment the byte counter bit number
				$deviceAddress4ByteMask = $deviceAddress4ByteMask << 1; //shift the bit mask left
				if ($deviceAddress4ByteMask == 0) { //if the mask is 0 then go to other address block and reset mask to first bit
					$deviceAddress4ByteIndex--;
					$deviceAddress4ByteMask = 1;
            			}
        		} while ($deviceAddress4ByteIndex > -1);
			
			if ($bitNumber == 65) { //if the search was successful then
            			$this->SetBuffer("owLastDiscrepancy", $lastZero);
            			if ($this->GetBuffer("owLastDiscrepancy") == 0) {
                			$this->SetBuffer("owLastDevice", 1);
            			} 
				else {
                			$this->SetBuffer("owLastDevice", 0);
            			}
			    
				$SerialNumber = sprintf("%X", $this->GetBuffer("owDeviceAddress_0")).sprintf("%X", $this->GetBuffer("owDeviceAddress_1"));
				$FamilyCode = substr($SerialNumber, -2);
				$this->SendDebug("SearchOWDevices", "OneWire Device Address = ".$SerialNumber, 0);
				//$this->SendDebug("SearchOWDevices", "OneWire Device Address = ".$this->GetBuffer("owDeviceAddress_0")." ".$this->GetBuffer("owDeviceAddress_1"), 0);
				$OWDeviceArray = Array();
 				$OWDeviceArray = unserialize($this->GetBuffer("OWDeviceArray"));
				$OWDeviceArray[$SearchNumber][0] = $this->GetOWHardware($FamilyCode); // Typ
				$OWDeviceArray[$SearchNumber][1] = $SerialNumber; // Seriennumber
				$OWDeviceArray[$SearchNumber][2] =  $this->OWInstanceArraySearch("DeviceSerial", $SerialNumber); // Instanz
				$OWDeviceArray[$SearchNumber][3] = "OK"; // Status
				If ($OWDeviceArray[$SearchNumber][2] == 0) {
					// Farbe gelb für nicht registrierte Instanzen
					$OWDeviceArray[$SearchNumber][4] = "#FFFF00";
				}
				else {
					// Farbe grün für erreichbare und registrierte Instanzen
					$OWDeviceArray[$SearchNumber][4] = "#00FF00";
				}
				$OWDeviceArray[$SearchNumber][5] = $this->GetBuffer("owDeviceAddress_0"); // erster Teil der dezimalen Seriennummer
				$OWDeviceArray[$SearchNumber][6] = $this->GetBuffer("owDeviceAddress_1"); // zweiter Teil der dezimalen Seriennummer
				$this->SetBuffer("OWDeviceArray", serialize($OWDeviceArray));
				
				if ($this->OWCheckCRC()) {
					return 1;
			    	} 
				else {
					$this->SendDebug("SearchOWDevices", "OneWire device address CRC check failed", 0);
					return 1;
			    	}   
        		}
			
   		}
 		$this->SendDebug("SearchOWDevices", "No One-Wire Devices Found, Resetting Search", 0);
   		$this->SetBuffer("owLastDiscrepancy", 0);
  		$this->SetBuffer("owLastDevice", 0);
    	return 0;
	}			
			
	private function OWCheckCRC() 
	{
    		$crc = 0;
     		$j = 0;
     		$da32bit = $this->GetBuffer("owDeviceAddress_1");
    		for($j = 0; $j < 4; $j++) { //All four bytes
			$crc = $this->AddCRC($da32bit & 0xFF, $crc);
			//server.log(format("CRC = %.2X", crc));
        		$da32bit = $da32bit >> 8; //Shift right 8 bits
		}	
		$da32bit = $this->GetBuffer("owDeviceAddress_0");
		for($j = 0; $j < 3; $j++) { //only three bytes
        		$crc = $this->AddCRC($da32bit & 0xFF, $crc);
        		//server.log(format("CRC = %.2X", crc));
        		$da32bit = $da32bit >> 8; //Shift right 8 bits
    		}
		//$this->SendDebug("OWCheckCRC", "CRC = ".$crc, 0);
		//$this->SendDebug("OWCheckCRC", "DA  = ".$da32bit, 0);
    		
    		if (($da32bit & 0xFF) == $crc) { //last byte of address should match CRC of other 7 bytes
        		$this->SendDebug("OWCheckCRC", "CRC Passed", 0);
        		return 1; //match
    		}
	return 0; //bad CRC
	}
	
	private function AddCRC($inbyte, $crc) 
	{
	    	$j = 0;
    		for($j = 0; $j < 8; $j++) {
        		$mix = ($crc ^ $inbyte) & 0x01;
			//$mix = (pow($crc, $inbyte)) & 0x01;
        		$crc = $crc >> 1;
        		if ($mix) {
				$crc = $crc ^ 0x8C;
				//$crc = pow($crc, 0x8C);
			}
        		$inbyte = $inbyte >> 1;
    		}
    	return $crc;
	}
	
	private function OWReset() 
	{
    		$this->SendDebug("OWReset", "I2C Reset", 0);
		// Write Byte to Handle
		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetBuffer("OW_Handle"), 180, 0), 16);//1-wire reset
		
		If ($Result < 0) {
			$this->SendDebug("OWReset", "I2C Reset Failed", 0);
			return 0;
    		}
		
     		$loopcount = 0;
    		while (true) {
        		$loopcount++;
			// Read Byte from Handle
			$Data = $this->OWStatusRegister();//Read the status register
        		If ($Data < 0) {
				$this->SendDebug("OWReset", "I2C Read Status Failed", 0);
				return 0;
    			}
			else {
				//$this->SendDebug("OWReset", "Read Status Byte: ".$Data, 0);
            			if ($Data & 0x01) { // 1-Wire Busy bit
                			//server.log("One-Wire bus is busy");
                			if ($loopcount > 100) {
                    				$this->SendDebug("OWReset", "One-Wire busy too long", 0);
                    				return 0;
                			}
                			IPS_Sleep(10);//Wait, try again
            			} 
				else {
					//server.log("One-Wire bus is idle");
					if ($Data & 0x04) { //Short Detected bit
						$this->SendDebug("OWReset", "One-Wire Short Detected", 0);
						return 0;
					}
					if ($Data & 0x02) { //Presense-Pulse Detect bit
						//$this->SendDebug("OWReset", "One-Wire Devices Found", 0);
						break;
					} 
					else {
						$this->SendDebug("OWReset", "No One-Wire Devices Found", 0);
						return 0;
					}
            			}
        		}
    		}
    	return 1;
	}
	
	private function OWWriteByte($byte) 
	{
		//$this->SendDebug("OWWriteByte", "Function: Write Byte to One-Wire", 0);
    		
		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 225, 240), 16); //set read pointer (E1) to the status register (F0)
		If ($Result < 0) {
			$this->SendDebug("OWWriteByte", "I2C Write Failed", 0);
			return -1;
    		}
		
    		$loopcount = 0;
    		while (true) {
        		$loopcount++;
        		$Data = $this->OWStatusRegister();//Read the status register
			If ($Data < 0) {
				$this->SendDebug("OWWriteByte", "I2C Read Status Failed", 0);
				return -1;
    			} 
			else {
            			//$this->SendDebug("OWWriteByte", "Read Status Byte: ".$Data, 0);
				if ($Data & 0x01) { // 1-Wire Busy bit
					//server.log("One-Wire bus is busy");
					if ($loopcount > 100) {
						$this->SendDebug("OWWriteByte", "One-Wire busy too long", 0);
						return -1;
					}
					IPS_Sleep(10);//Wait, try again
				} 
				else {
					//$this->SendDebug("OWWriteByte", "One-Wire bus is idle", 0);
					break;
				}
        		}
    		}
   
		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 165, $byte), 16); //set write byte command (A5) and send data (byte)
		If ($Result < 0) { //Device failed to acknowledge
        		$this->SendDebug("OWWriteByte", "I2C Write Byte Failed.", 0);
        		return -1;
    		}
    		$loopcount = 0;
    		while (true) {
        		$loopcount++;
 			$Data = $this->OWStatusRegister();//Read the status register
			If ($Data < 0) {
            			$this->SendDebug("OWWriteByte", "I2C Read Status Failed", 0);
            			return -1;
        		} 
			else {
            			//$this->SendDebug("OWWriteByte", "Read Status Byte: ".$Data, 0);
            			if ($Data & 0x01) { // 1-Wire Busy bit
                			$this->SendDebug("OWWriteByte", "One-Wire bus is busy", 0);
                			if ($loopcount > 100) {
                    				$this->SendDebug("OWWriteByte", "One-Wire busy for too long", 0);
                    				return -1;
                			}
                			IPS_Sleep(10);//Wait, try again
            			} 
				else {
                			//$this->SendDebug("OWWriteByte", "One-Wire bus is idle", 0);
                			break;
            			}
        		}
    		}
    	//$this->SendDebug("OWWriteByte", "One-Wire Write Byte complete", 0);
    	return 0;
	}
	
	private function OWTriplet() 
	{
		//$this->SendDebug("OWTriplet", "Function: OneWire Triplet", 0);
		if ($this->GetBuffer("owTripletDirection") > 0) {
			$this->SetBuffer("owTripletDirection", 255);
		}
		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 120, $this->GetBuffer("owTripletDirection")), 16); //send 1-wire triplet and direction
		If ($Result < 0) { //Device failed to acknowledge message
        		$this->SendDebug("OWTriplet", "OneWire Triplet Failed", 0);
        		return 0;
    		}
	    	$loopcount = 0;
		while (true) {
			$loopcount++;
			
			$Data = $this->OWStatusRegister();//Read the status register
			If ($Data < 0) {
            			$this->SendDebug("OWTriplet", "I2C Read Status Failed", 0);
            			return -1; 
			} 
			else {		
		    		//$this->SendDebug("OWTriplet", "Read Status Byte: ".$Data, 0);
		    		if ($Data & 0x01) { // 1-Wire Busy bit
					$this->SendDebug("OWTriplet", "One-Wire bus is busy", 0);
					if ($loopcount > 100) {
			    			$this->SendDebug("OWTriplet", "One-Wire busy for too long", 0);
			    			return -1;
					}
					IPS_Sleep(10);//Wait, try again
		    		} 
				else {
					//$this->SendDebug("OWTriplet", "One-Wire bus is idle", 0);
					if ($Data & 0x20) {
						$this->SetBuffer("owTripletFirstBit", 1);
					} 
					else {
						$this->SetBuffer("owTripletFirstBit", 0);
					}
					if ($Data & 0x40) {
						$this->SetBuffer("owTripletSecondBit", 1);
					} 
					else {
						$this->SetBuffer("owTripletSecondBit", 0);
					}
					if ($Data & 0x80) {
						$this->SetBuffer("owTripletDirection", 1);
					} 
					else {
						$this->SetBuffer("owTripletDirection", 0);
					}
				return 1;
				}
			}
		}
	}
	
	private function OWSelect() 
	{
    		$this->SendDebug("OWSelect", "Selecting device", 0);
    		$this->OWWriteByte(85); //Issue the Match ROM command 55Hex
    		
    		for($i = 1; $i >= 0; $i--) {
        		$da32bit = $this->GetBuffer("owDeviceAddress_".$i);
        		for($j = 0; $j < 4; $j++) {
            			//server.log(format("Writing byte: %.2X", da32bit & 0xFF));
            			$this->OWWriteByte($da32bit & 255); //Send lowest byte
            			$da32bit = $da32bit >> 8; //Shift right 8 bits
        		}
    		}
	}
	
	private function OWRead_18B20_Temperature() 
	{
    		$data = Array();
		$celsius = -99;
    		for($i = 0; $i < 5; $i++) { //we only need 5 of the bytes
        		$data[$i] = $this->OWReadByte();
        		//server.log(format("read byte: %.2X", data[i]));
    		}
 
    		$raw = ($data[1] << 8) | $data[0];
    		$SignBit = $raw & 0x8000;  // test most significant bit
    		if ($SignBit) {
			$raw = ($raw ^ 0xffff) + 1;
		} // negative, 2's compliment
		$cfg = $data[4] & 0x60;
		if ($cfg == 0x60) {
			$this->SendDebug("OWReadTemperature", "12 bit resolution", 0);
			//server.log("12 bit resolution"); //750 ms conversion time
		} 
		else if ($cfg == 0x40) {
			$this->SendDebug("OWReadTemperature", "11 bit resolution", 0);
			//server.log("11 bit resolution"); //375 ms
			$raw = $raw & 0xFFFE;
		} 
		else if ($cfg == 0x20) {
			$this->SendDebug("OWReadTemperature", "10 bit resolution", 0);
			//server.log("10 bit resolution"); //187.5 ms
			$raw = $raw & 0xFFFC;
		} 
		else { //if (cfg == 0x00)
			$this->SendDebug("OWReadTemperature", "9 bit resolution", 0);
			//server.log("9 bit resolution"); //93.75 ms
			$raw = $raw & 0xFFF8;
		}
		//server.log(format("rawtemp= %.4X", raw));
		$celsius = $raw / 16.0;
		if ($SignBit) {
			$celsius = $celsius * (-1);
		}
		//server.log(format("Temperature = %.1f °C", celsius));
		$SerialNumber = sprintf("%X", $this->GetBuffer("owDeviceAddress_0")).sprintf("%X", $this->GetBuffer("owDeviceAddress_1"));
		$this->SendDebug("OWRead_18B20_Temperature", "OneWire Device Address = ".$SerialNumber. " Temperatur = ".$celsius, 0);
	return $celsius;
	}
	
	private function OWRead_18S20_Temperature() 
	{
    		$data = Array();
		$celsius = -99;
    		for($i = 0; $i < 2; $i++) { //we only need 2 of the bytes
        		$data[$i] = $this->OWReadByte();
        		//server.log(format("read byte: %.2X", data[i]));
    		}
 
    		$raw = ($data[1] << 8) | $data[0];
    		$SignBit = $raw & 0x8000;  // test most significant bit
    		if ($SignBit) {
			$raw = ($raw ^ 0xffff) + 1;
		} // negative, 2's compliment
		
		//server.log(format("rawtemp= %.4X", raw));
		$celsius = $raw / 2.0;
		if ($SignBit) {
			$celsius = $celsius * (-1);
		}
		//server.log(format("Temperature = %.1f °C", celsius));
		$SerialNumber = sprintf("%X", $this->GetBuffer("owDeviceAddress_0")).sprintf("%X", $this->GetBuffer("owDeviceAddress_1"));
		$this->SendDebug("OWRead_18S20_Temperature", "OneWire Device Address = ".$SerialNumber. " Temperatur = ".$celsius, 0);
	return $celsius;
	}
	
	private function OWRead_2413_State() 
	{
		$result = -99;
    		$result = $this->OWReadByte();
		$SerialNumber = sprintf("%X", $this->GetBuffer("owDeviceAddress_0")).sprintf("%X", $this->GetBuffer("owDeviceAddress_1"));
    		$this->SendDebug("OWRead_2413_State", "OneWire Device Address = ".$SerialNumber. " State = ".$result, 0);
	return $result;
	}
	
	private function OWReadByte() 
	{
    		//See if the 1wire bus is idle
    		//server.log("Function: Read Byte from One-Wire");
    		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 225, 240), 16); //set read pointer (E1) to the status register (F0)
		If ($Result < 0) { //Device failed to acknowledge
			$this->SendDebug("OWReadByte", "I2C Write Failed", 0);
			return -1;
    		}
		
    		$loopcount = 0;
   		while (true) {
        		$loopcount++;
			$Data = $this->OWStatusRegister();//Read the status register
			If ($Data < 0) {
				$this->SendDebug("OWReadByte", "I2C Read Status Failed", 0);
				return -1;
    			} 
			else {
            			//$this->SendDebug("OWReadByte", "Read Status Byte: ".$Data, 0);
            			if ($Data & 0x01) { // 1-Wire Busy bit
                			//server.log("One-Wire bus is busy");
                			if ($loopcount > 100) {
                    				$this->SendDebug("OWReadByte", "One-Wire busy for too long", 0);
                    				return -1;
					}
					IPS_Sleep(10); //Wait, try again
				} 
				else {
					//server.log("One-Wire bus is idle");
					break;
				}
        		}
    		}
   
    		//Send a read command, then wait for the 1wire bus to finish
		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetBuffer("OW_Handle"), 150, 0), 16); //send read byte command (96)
		If ($Result < 0) {
			$this->SendDebug("OWReadByte", "I2C Write read-request Failed", 0);
			return -1;
		} 
    
    		$loopcount = 0;
    		while (true) {
        		$loopcount++;
        		
			$Data = $this->OWStatusRegister();//Read the status register
			If ($Data < 0) {
            			$this->SendDebug("OWReadByte", "I2C Read Status Failed", 0);
            			return -1; 
			} 
			else {
            			//$this->SendDebug("OWReadByte", "Read Status Byte: ".$Data, 0);
            			if ($Data[0] & 0x01) { // 1-Wire Busy bit
                			//server.log("One-Wire bus is busy");
                			if ($loopcount > 100) {
                    				$this->SendDebug("OWReadByte", "One-Wire busy for too long", 0);
                    				return -1;
                			}
                			IPS_Sleep(10); //Wait, try again
            			} 
				else {
					//server.log("One-Wire bus is idle");
					break;
				}
        		}
    		}
   
		//Go get the data byte
		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 225, 225), 16); //set read pointer (E1) to the read data register (E1)
		If ($Result < 0) { //Device failed to acknowledge
			$this->SendDebug("OWReadByte", "I2C Write Failed", 0);
			return -1;
		}
		$Data = $this->CommandClientSocket(pack("L*", 59, $this->GetBuffer("OW_Handle"), 0, 0), 16);//Read the status register
		If ($Data < 0) {
			$this->SendDebug("OWReadByte", "I2C Read Status Failed", 0);
			return -1;
		} 
		else {
			//server.log(format("Read Data Byte = %d", data[0]));
		}
    		//server.log("One-Wire Read Byte complete");
    	return $Data;
	}
	
	private function OWStatusRegister()
	{
		$Result = $this->CommandClientSocket(pack("LLLLCC", 57, $this->GetBuffer("OW_Handle"), 0, 2, 225, 240), 16); //set read pointer (E1) to the read status register (F0)
		If ($Result < 0) { //Device failed to acknowledge
			$this->SendDebug("OWStatusRegister", "I2C Write Failed", 0);
			$Result = -1;
		}
		else {
			$Result = $this->CommandClientSocket(pack("L*", 59, $this->GetBuffer("OW_Handle"), 0, 0), 16);//Read the status register
			//$this->SendDebug("OWStatusRegister", "Read Status Byte: ".$Result, 0);
		}
	return $Result;
	}
	
	private function OWVerify()
	{
		//--------------------------------------------------------------------------
		// Verify the device with the ROM number in ROM_NO buffer is present.
		// Return TRUE  : device verified present
		//        FALSE : device not present
		//
   		// keep a backup copy of the current state
   		$owDeviceAddress_0_backup = $this->GetBuffer("owDeviceAddress_0");
		$owDeviceAddress_1_backup = $this->GetBuffer("owDeviceAddress_1");
   		$ld_backup = $this->GetBuffer("owLastDiscrepancy");
   		$ldf_backup = $this->GetBuffer("owLastDevice");
   		// set search to find the same device
   		$this->SetBuffer("owLastDiscrepancy", 64);
   		$this->SetBuffer("owLastDevice", 0);
   		if ($this->OWSearch(0))
   		{
      			// check if same device found
      			$Result = 1;
      			If (($owDeviceAddress_0_backup <> $this->GetBuffer("owDeviceAddress_0")) AND ($owDeviceAddress_1_backup <> $this->GetBuffer("owDeviceAddress_1"))) { 
            			$Result = 0;
            			//break;
      			}
   		}
   		else {
     			$Result = 0;
   			// restore the search state 
   			$this->SetBuffer("owDeviceAddress_0", $owDeviceAddress_0_backup);
		 	$this->SetBuffer("owDeviceAddress_1", $owDeviceAddress_1_backup);
   			$this->SetBuffer("owLastDiscrepancy", $ld_backup);
   		 	$this->SetBuffer("owLastDevice", $ldf_backup);
		}
	// return the result of the verify
	return $Result;
	}
	
	private function OWRead_2438() 
	{
    		$data = Array();
		$Celsius = -99;
		$Voltage = -99;
		$Current = -99;
    		for($i = 0; $i <= 6; $i++) { //we only need 6 of the bytes
        		$data[$i] = $this->OWReadByte();
        		//server.log(format("read byte: %.2X", data[i]));
    		}
 
		// $data[0] = Status
		// $data[1] = Temperatur LSB
		// $data[2] = Temperatur MSB
		// $data[3] = Voltage LSB
		// $data[4] = Voltage MSB
		// $data[5] = Current LSB
		// $data[6] = Current MSB
		
		// Temperatur ermitteln
    		$raw = ($data[2] << 8) | $data[1];
    		$SignBit = $raw & 0x8000;  // test most significant bit
    		
		if ($SignBit) {
			$raw = ($raw ^ 0xffff) + 1;
		} // negative, 2's compliment
		
		$raw = $raw >> 3; 
		
		$Celsius = $raw / 32.0;
		if ($SignBit) {
			$Celsius = $Celsius * (-1);
		}
		
		// Spannung ermitteln
		$raw = ($data[4] << 8) | $data[3];
		$raw = $raw & 0x3FF;
		
		$Voltage = $raw * 0.01;
		
		// Strom ermitteln
		$raw = ($data[6] << 8) | $data[5];
		$raw = $raw & 0x3FF;
		$SignBit = $raw & 0x8000;  // test most significant bit
		
		$Current = $raw * 0.2441;
		if ($SignBit) {
			$Current = $Current * (-1);
		}
		//server.log(format("Temperature = %.1f °C", celsius));
		$SerialNumber = sprintf("%X", $this->GetBuffer("owDeviceAddress_0")).sprintf("%X", $this->GetBuffer("owDeviceAddress_1"));
		$this->SendDebug("OWRead_2438", "OneWire Device Address = ".$SerialNumber." Temperatur = ".$Celsius." Spannung = ".$Voltage." Strom = ".$Current, 0);
	return array($Celsius, $Voltage, $Current);
	}
	
	private function IR_Carrier($gpio, $frequency, $micros, $dutycycle)
	{
		// Generate cycles of carrier on gpio with frequency and dutycycle.
		$wf = array();
		$cycle = 1000000 / $frequency;
		$cycles = intval(round($micros/$cycle));
		$on = intval(round($cycle * $dutycycle));
		$sofar = 0;
		for ($c = 0; $c <= $cycles; $c++) {
			$target = intval(round(($c+1) * $cycle));
			$sofar = $sofar + $on;
			$off = $target - $sofar;
			$sofar = $sofar + $off;
			array_push($wf, 1 << $gpio, 0, $on);
			array_push($wf, 0, 1 << $gpio, $off);
		}
		Return $wf;
	}
	
}
?>
