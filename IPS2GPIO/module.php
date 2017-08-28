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
		if ($this->Socket)
		    	socket_close($this->Socket);
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
		$this->RegisterPropertyInteger("I2C0", 0);
		$this->RegisterPropertyBoolean("1Wire_Used", false);
		$this->RegisterPropertyString("Raspi_Config", "");
		$this->RegisterPropertyString("I2C_Devices", "");
		$this->RegisterPropertyBoolean("Multiplexer", false);
	    	$this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
		$PinNotify = array();
		$this->SetBuffer("PinNotify", serialize($PinNotify));
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
		$arrayElements[] = array("type" => "Label", "label" => "Auswahl der erforderlichen Schnittstellen:");
		$arrayElements[] = array("type" => "CheckBox", "name" => "1Wire_Used", "caption" => "1-Wire");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arrayElements[] = array("type" => "Label", "label" => "Detaillierung der genutzten I²C-Schnittstelle:");
		$arrayOptions = array();
		$arrayOptions[] = array("label" => "Kein MUX", "value" => 0);
		$arrayOptions[] = array("label" => "TCA9548a Adr. 112/0x70", "value" => 1);
		$arrayOptions[] = array("label" => "PCA9542 Adr. 112/0x70", "value" => 2);
		$arrayElements[] = array("type" => "Select", "name" => "MUX", "caption" => "MUX-Auswahl", "options" => $arrayOptions );
		$arrayOptions = array();
		$arrayElements[] = array("type" => "Label", "label" => "Nutzung der I²C-Schnittstelle 0:");
		$arrayOptions[] = array("label" => "Nein", "value" => 0);
		$arrayOptions[] = array("label" => "Ja", "value" => 1);
		$arrayElements[] = array("type" => "Select", "name" => "I2C0", "caption" => "I²C 0", "options" => $arrayOptions );
		//$arrayElements[] = array("type" => "CheckBox", "name" => "I2C0", "caption" => "I²C-Schnittstelle 0");
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
		$arrayColumns = array();
		$arrayColumns[] = array("label" => "Typ", "name" => "DeviceTyp", "width" => "120px", "add" => "");
		$arrayColumns[] = array("label" => "Adresse", "name" => "DeviceAddress", "width" => "60px", "add" => "");
		$arrayColumns[] = array("label" => "Bus", "name" => "DeviceBus", "width" => "60px", "add" => "");
		$arrayColumns[] = array("label" => "Instanz ID", "name" => "InstanceID", "width" => "70px", "add" => "");
		$arrayColumns[] = array("label" => "Status", "name" => "DeviceStatus", "width" => "auto", "add" => "");		
		
		If (($this->ConnectionTest()) AND ($this->ReadPropertyBoolean("Open") == true))  {
			// I²C-Devices einlesen und in das Values-Array kopieren
			$DeviceArray = array();
			$DeviceArray = unserialize($this->SearchI2CDevices());
			$arrayValues = array();
			If (count($DeviceArray , COUNT_RECURSIVE) >= 4) {
				for ($i = 0; $i < Count($DeviceArray); $i++) {
					$arrayValues[] = array("DeviceTyp" => $DeviceArray[$i][0], "DeviceAddress" => $DeviceArray[$i][1], "DeviceBus" => $DeviceArray[$i][2], "InstanceID" => $DeviceArray[$i][3], "DeviceStatus" => $DeviceArray[$i][4], "rowColor" => $DeviceArray[$i][5]);
				}
				$arrayElements[] = array("type" => "List", "name" => "I2C_Devices", "caption" => "I²C-Devices", "rowCount" => 5, "add" => false, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);
			}
			else {
				$arrayElements[] = array("type" => "Label", "label" => "Es wurden keine I²C-Devices gefunden.");
			}
			$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
			$arrayElements[] = array("type" => "Label", "label" => "Führt einen Restart des PIGPIO aus:");
			$arrayElements[] = array("type" => "Button", "label" => "PIGPIO Restart", "onClick" => 'I2G_PIGPIOD_Restart($id);');
		}
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
				
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
			$this->RegisterVariableString("Hardware", "Hardware", "", 10);
			$this->DisableAction("Hardware");
			IPS_SetHidden($this->GetIDForIdent("Hardware"), true);
			
			$this->RegisterVariableInteger("SoftwareVersion", "SoftwareVersion", "", 20);
			$this->DisableAction("SoftwareVersion");
			IPS_SetHidden($this->GetIDForIdent("SoftwareVersion"), true);
			
			$this->RegisterVariableInteger("LastKeepAlive", "Letztes Keep Alive", "~UnixTimestamp", 30);
			$this->DisableAction("LastKeepAlive");
			IPS_SetHidden($this->GetIDForIdent("LastKeepAlive"), false);
						
			$this->SetBuffer("HardwareRev", 0);
			$Typ = array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27);
			$this->SetBuffer("PinPossible", serialize($Typ));
			$this->SetBuffer("PinI2C", "");
			$this->SetBuffer("I2CSearch", 0);
			$this->SetBuffer("I2C_0_Configured", 0);
			$this->SetBuffer("I2C_1_Configured", 0);
			$this->SetBuffer("Serial_Configured", 0);
			$this->SetBuffer("1Wire_Configured", 0);
			$this->SetBuffer("SerialNotify", 0);
			$this->SetBuffer("Default_I2C_Bus", 1);
			$this->SetBuffer("Default_Serial_Bus", 0);
			$this->SetBuffer("MUX_Handle", -1);
			$this->SetBuffer("NotifyCounter", -1);
			$PinNotify = array();
			$this->SetBuffer("PinNotify", serialize($PinNotify));
			
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
				    IPS_ApplyChanges($ParentID);
				}
			}
	
			If (($this->ConnectionTest()) AND ($this->ReadPropertyBoolean("Open") == true))  {
				$this->SendDebug("ApplyChangges", "Starte Vorbereitung", 0);
				$this->CheckConfig();
				// Hardware und Softwareversion feststellen
				$this->CommandClientSocket(pack("L*", 17, 0, 0, 0).pack("L*", 26, 0, 0, 0), 32);
				
				// I2C-Handle zurücksetzen
				$this->ResetI2CHandle(0);
				
				// Serial-Handle zurücksetzen
				$this->ResetSerialHandle();
				
				// MUX einrichten
				If ($this->ReadPropertyInteger("MUX") > 0) {
					$MUX_Handle = $Handle = $this->CommandClientSocket(pack("L*", 54, 1, 112, 4, 0), 16);
					$this->SetBuffer("MUX_Handle", $MUX_Handle);
					$this->SendDebug("MUX Handle", $MUX_Handle, 0);
					If ($MUX_Handle >= 0) {
						// MUX setzen
						$this->SetMUX(0);
					}
				}
			
				$I2C_DeviceHandle = array();
				$this->SetBuffer("I2C_Handle", serialize($I2C_DeviceHandle));
				
				// Notify Handle zurücksetzen falls gesetzt
				If ($this->GetBuffer("Handle") >= 0) {
					// Handle löschen
					//$this->ClientSocket(pack("LLLL", 21, $this->GetBuffer("Handle");, 0, 0));
				}
				// Notify Starten
				$this->SetBuffer("Handle", -1);
				$this->SetBuffer("NotifyCounter", 0);
				
				$Handle = $this->ClientSocket(pack("L*", 99, 0, 0, 0));
				$this->SetBuffer("Handle", $Handle);
				If ($Handle >= 0) {
					$this->ClientSocket(pack("L*", 19, $Handle, $this->CalcBitmask(), 0));
				}
				
				$this->Get_PinUpdate();
				
				$this->SetStatus(102);
				
			}
			else {
				$this->SetStatus(104);
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
				IPS_LogMessage("IPS2GPIO MessageSink", "Instanz  ".$SenderID." wurde getrennt");
				break;	
			case 10505:
				If ($Data[0] == 102) {
					$this->ApplyChanges();
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
		    case "gpio_destroy":
		    	// Löschen einer GPIO-Belegung
		    	// aus der Liste der genutzten GPIO
		    	$PinUsed = unserialize($this->GetBuffer("PinUsed"));
		    	IPS_LogMessage("IPS2GPIO GPIO Destroy: ",$data->Pin);
		    	if (in_array($data->Pin, $PinUsed)) {
		    		IPS_LogMessage("IPS2GPIO GPIO Destroy: ","Pin in PinUsed");
		    		array_splice($PinUsed, $data->Pin, 1);	
		    	}
			$this->SetBuffer("PinUsed", serialize($PinUsed));
		        // aus der Liste der Notify-GPIO
			$PinNotify = array();
		        $PinNotify = unserialize($this->GetBuffer("PinNotify"));
		        if (in_array($data->Pin, $PinNotify)) {
		    		IPS_LogMessage("IPS2GPIO GPIO Destroy: ","Pin in PinNotify");
		    		array_splice($PinNotify, $data->Pin, 1);
				$this->SetBuffer("PinNotify", serialize($PinNotify));
		    		If ($this->GetBuffer("Handle") >= 0) {
			           	// Notify neu setzen
			           	$this->CommandClientSocket(pack("L*", 19, $this->GetBuffer("Handle"), $this->CalcBitmask(), 0), 16);
				}
		    	}
		        break;
		case "set_PWM_dutycycle":
		    	// Dimmt einen Pin
		    	If ($data->Pin >= 0) {
		        	//IPS_LogMessage("IPS2GPIO Set Intensity : ",$data->Pin." , ".$data->Value);
		        	$Result = $this->CommandClientSocket(pack("L*", 5, $data->Pin, $data->Value, 0), 16);
		        }
		        break;
		case "get_PWM_dutycycle":
		    	// Dimmt einen Pin
		    	If ($data->Pin >= 0) {
		        	//IPS_LogMessage("IPS2GPIO Set Intensity : ",$data->Pin." , ".$data->Value);
				$Result = $this->CommandClientSocket(pack("L*", 83, $data->Pin, 0, 0), 16);
		        }
		        break;
		case "set_PWM_dutycycle_RGB":
		    	// Setzt die RGB-Farben
		    	If (($data->Pin_R >= 0) AND ($data->Pin_G >= 0) AND ($data->Pin_B >= 0)) {
		        	//IPS_LogMessage("IPS2GPIO Set Intensity RGB : ",$data->Pin_R." , ".$data->Value_R." ".$data->Pin_G." , ".$data->Value_G." ".$data->Pin_B." , ".$data->Value_B);  
		        	$this->CommandClientSocket(pack("LLLL", 5, $data->Pin_R, $data->Value_R, 0).pack("LLLL", 5, $data->Pin_G, $data->Value_G, 0).pack("LLLL", 5, $data->Pin_B, $data->Value_B, 0), 48);
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
		   	If ($data->Pin >= 0) {
				// Prüfen, ob der gewählte GPIO bei dem Modell überhaupt vorhanden ist
				$PinPossible = array();
				$PinPossible = unserialize($this->GetBuffer("PinPossible"));
				if (in_array($data->Pin, $PinPossible)) {
			    		//IPS_LogMessage("IPS2GPIO Pin: ","Gewählter Pin ist bei diesem Modell verfügbar");
					$this->SendDebug("set_usedpin", "Gewaehlter Pin ".$data->Pin." ist bei diesem Modell verfuegbar", 0);
			    		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>102, "HardwareRev"=>$this->GetBuffer("HardwareRev") )));
				}
				else {
					$this->SendDebug("set_usedpin", "Gewaehlter Pin ".$data->Pin." ist bei diesem Modell nicht verfuegbar!", 0);
					IPS_LogMessage("IPS2GPIO Pin: ","Gewählter Pin ".$data->Pin." ist bei diesem Modell nicht verfügbar!");
					$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>201, "HardwareRev"=>$this->GetBuffer("HardwareRev") )));
				}
				// Erstellt ein Array für alle Pins die genutzt werden 	
				$PinUsed = array();
				$PinUsed = unserialize($this->GetBuffer("PinUsed"));
				// Prüft, ob der ausgeählte Pin schon einmal genutzt wird
			        If (array_key_exists($data->Pin, $PinUsed)) {
			        	If (($PinUsed[$data->Pin] <> $data->InstanceID) AND ($PinUsed[$data->Pin] <> 99999)) {
			        		IPS_LogMessage("IPS2GPIO Pin", "Achtung: Pin ".$data->Pin." wird mehrfach genutzt!");
						$this->SendDebug("set_usedpin", "Achtung: Pin ".$data->Pin." wird mehrfach genutzt!", 0);
			        		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"status", "Pin"=>$data->Pin, "Status"=>200, "HardwareRev"=>$this->GetBuffer("HardwareRev")) ));
			        	}	
			        }
			        $PinUsed[$data->Pin] = $data->InstanceID;
			        // Messages einrichten
			        $this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
		        	$this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
			        // Erstellt ein Array für alle Pins für die die Notifikation erforderlich ist
			        If ($data->Notify == true) {
					$PinNotify = array();
			        	$PinNotify = unserialize($this->GetBuffer("PinNotify"));
				        if (in_array($data->Pin, $PinNotify) == false) {
						$PinNotify[] = $data->Pin;
						$this->SendDebug("set_usedpin", "Gewaehlter Pin ".$data->Pin." wurde dem Notify hinzugefuegt", 0);
					}
					$this->SetBuffer("PinNotify", serialize($PinNotify));
					// startet das Notify neu
					$this->SetBuffer("NotifyCounter", 0);
					$this->CommandClientSocket(pack("L*", 19, $this->GetBuffer("Handle"), $this->CalcBitmask(), 0), 16);
					// Setzt den Glitch Filter
					//IPS_LogMessage("IPS2GPIO SetGlitchFilter Parameter",$data->Pin." , ".$data->GlitchFilter);
					$this->CommandClientSocket(pack("L*", 97, $data->Pin, $data->GlitchFilter, 0), 16);
			        }
			        // Pin in den entsprechenden R/W-Mode setzen, ggf. gleichzeitig Pull-Up/Down setzen
				If ($data->Modus == 0) {
					// R/W-Mode und Pull Up/Down Widerstände für den Pin setzen
					//IPS_LogMessage("IPS2GPIO Set Pull Up/Down",$data->Pin." , ".$data->Resistance);
					//IPS_LogMessage("IPS2GPIO SetMode",$data->Pin." , ".$data->Modus);
			        	$this->CommandClientSocket(pack("LLLL", 0, $data->Pin, 0, 0).pack("LLLL", 2, $data->Pin, $data->Resistance, 0), 32);
				}
				else {
					// R/W-Mode setzen
					//IPS_LogMessage("IPS2GPIO SetMode",$data->Pin." , ".$data->Modus);
					$this->CommandClientSocket(pack("LLLL", 0, $data->Pin, $data->Modus, 0), 16);
					$this->SetBuffer("PinUsed", serialize($PinUsed));
				}
		   	}
		        break;
		case "get_pinupdate":
		   	$this->Get_PinUpdate();
		   	break;
		case "get_GPIO":
		   	$PinPossible = array();
			$PinPossible = unserialize($this->GetBuffer("PinPossible"));
			$PinUsed = array();
		   	$PinUsed = unserialize($this->GetBuffer("PinUsed"));
			$PinFreeArray = array();
		   	$PinFreeArray = array_diff($PinPossible, $PinUsed);
			$arrayGPIO = array();
			$arrayGPIO[-1] = "undefiniert";
			foreach($PinFreeArray AS $Value) {
				$arrayGPIO[$Value] = "GPIO".(sprintf("%'.02d", $Value));
			}
		   	return serialize($arrayGPIO);
			break;

		// I2C Kommunikation
		case "set_used_i2c":
			// Konfiguration für I²C Bus 0 - GPIO 28/29 an P5
			If (($this->GetBuffer("I2C_0_Configured") == 0) AND (intval($data->DeviceBus) == 0)) {
				$PinUsed = array();
				// Reservieren der Schnittstellen für I²C
				$this->CommandClientSocket(pack("LLLL", 0, 28, 4, 0).pack("LLLL", 0, 29, 4, 0), 32);
				// Sichern der Einstellungen
				$this->SetBuffer("I2C_0_Configured", 1);
				$this->SendDebug("Set Used I2C", "Mode der GPIO fuer I2C Bus 0 gesetzt", 0);
			}
			// Konfiguration für I²C Bus 1 (Default) - GPIO 0/1 bzw. 2/3 an P1
			If (($this->GetBuffer("I2C_1_Configured") == 0) AND (intval($data->DeviceBus) == 1)) {
				$PinUsed = array();
				$PinUsed = $this->GetBuffer("PinUsed");
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
				$this->SendDebug("Set Used I2C", "Mode der GPIO fuer I2C Bus 1 gesetzt", 0);
			}
			
		   	// die genutzten Device Adressen anlegen
		   	$I2C_DeviceHandle = unserialize($this->GetBuffer("I2C_Handle"));
		   	// Bei Bus 1 Addition von 128
			$I2C_DeviceHandle[($data->DeviceBus << 7) + $data->DeviceAddress] = -1;
		   	// Messages einrichten
			$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
		        $this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
		   	// Handle ermitteln
		   	$Handle = $this->CommandClientSocket(pack("L*", 54, $data->DeviceBus, $data->DeviceAddress, 4, 0), 16);	
		   	$this->SendDebug("Set Used I2C", "Handle fuer Device-Adresse ".$data->DeviceAddress." an Bus ".($data->DeviceBus).": ".$Handle, 0);
			$I2C_DeviceHandle[($data->DeviceBus << 7) + $data->DeviceAddress] = $Handle;
			// genutzte Device-Ident mit Handle sichern
			$this->SetBuffer("I2C_Handle", serialize($I2C_DeviceHandle));	
			// Testweise lesen
			If ($Handle >= 0) {
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
					$DevicePorts[$i] = "MUX I²C-Bus ".($i -3);
				}
			}
			elseif ($this->ReadPropertyInteger("MUX") == 2) {
				// PCA9542
				for ($i = 3; $i <= 4; $i++) {
					$DevicePorts[$i] = "MUX I²C-Bus ".($i -3);
				}
			}
		   	$Result = serialize($DevicePorts);
		   	break;
		 case "i2c_read_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Byte Parameter: ",$data->Handle." , ".$data->Register); 
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break;
		 case "i2c_read_bytes":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Bytes",$this->GetI2C_DeviceHandle($data->DeviceAddress)." , ".$data->Register." , ".$data->Count);  	
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 56, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Count, 0), 16 + ($data->Count));
		   	}
			break;  
		
		case "i2c_read_word":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Word Parameter : ","DeviceAdresse: ".$data->DeviceAddress.", Handle: ".$this->GetI2C_DeviceHandle($data->DeviceAddress)." ,Register: ".$data->Register);
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 63, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break; 
		   case "i2c_read_block_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Read Block Byte Parameter : ",$data->Handle." , ".$data->Register." , ".$data->Count);  	
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;
		   case "i2c_write_byte":
		   	//IPS_LogMessage("IPS2GPIO I2C Write Byte : ",$data->Handle." , ".$data->Register." , ".$data->Value);  	
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
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
		   	//IPS_LogMessage("IPS2GPIO I2C Write Byte Handle: ","DeviceAdresse: ".$data->DeviceAddress.", Handle: ".$this->GetI2C_DeviceHandle($data->DeviceAddress).", Wert: ".$data->Value);  	
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$this->CommandClientSocket(pack("L*", 60, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Value, 0), 16);
		   	}
		   	break;	
		case "i2c_PCF8574_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 59, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), 0, 0), 16);
		   	}
		   	break;	 
		case "i2c_PCF8574_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Value, 0), 16);
		   	}
		   	break;	
		case "i2c_AS3935_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 56, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Count, 0), 16 + ($data->Count));
		   	}
			break;
		case "i2c_AS3935_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		case "i2c_MCP3424_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Value, 0), 16);
		   	}
		   	break;	
		case "i2c_MCP3424_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 56, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Count, 0), 16 + ($data->Count));
		   	}
			break;  
		case "i2c_BH1750_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 60, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Value, 0), 16);
		   	}
		   	break;
		case "i2c_BH1750_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 63, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break; 
		case "i2c_BME280_write":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 62, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Value), 16);
		   	}
		   	break;
		 case "i2c_BME280_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 61, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 0), 16);
		   	}
		   	break;
		case "i2c_BME280_read_block":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 67, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Register, 4, $data->Count), 16 + ($data->Count));
		   	}
			break;
		case "i2c_iAQ_read":
		   	If ($this->GetI2C_DeviceHandle(intval($data->DeviceIdent)) >= 0) {
		   		$Result = $this->CommandClientSocket(pack("L*", 56, $this->GetI2C_DeviceHandle(intval($data->DeviceIdent)), $data->Count, 0), 16 + ($data->Count));
		   	}
			break;    	
		   
		   // Serielle Kommunikation
		case "get_handle_serial":
	   		If ($this->GetBuffer("Serial_Configured") == 0) {
				$PinUsed = array();
				$PinUsed = $this->GetBuffer("PinUsed");
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
			}
				
			
	   		//$this->CommandClientSocket(pack("L*", 76, $data->Baud, 0, strlen($data->Device)).$data->Device.pack("L*", 19, $this->GetBuffer("Handle"), $this->CalcBitmask(), 0), 32);
			$SerialHandle = $this->CommandClientSocket(pack("L*", 76, $data->Baud, 0, strlen($data->Device)).$data->Device, 16);
			
			$this->SetBuffer("Serial_Handle", $SerialHandle);
			$this->SendDebug("Serial_Handle", $SerialHandle, 0);
				
			// den Notify für den RxD-Pin einschalten
			$PinNotify = array();
			$PinNotify = unserialize($this->GetBuffer("PinNotify"));
			$PinNotify[] = 15;
			$this->SetBuffer("PinNotify", serialize($PinNotify));
			$this->CommandClientSocket(pack("L*", 19, $this->GetBuffer("Handle"), $this->CalcBitmask(), 0), 16);
				
			// Messages einrichten
			$this->RegisterMessage($data->InstanceID, 11101); // Instanz wurde verbunden (InstanceID vom Parent)
		        $this->RegisterMessage($data->InstanceID, 11102); // Instanz wurde getrennt (InstanceID vom Parent)
			// WatchDog setzen
			//$this->ClientSocket(pack("L*", 9, 15, 500, 0), 16);
	   		break;
		   case "write_bytes_serial":
		   	$Command = utf8_decode($data->Command);
		   	//IPS_LogMessage("IPS2GPIO Write Bytes Serial", "Handle: ".GetValueInteger($this->GetIDForIdent("Serial_Handle"))." Command: ".$Command);
		   	$this->CommandClientSocket(pack("L*", 81, $this->GetBuffer("Serial_Handle"), 0, strlen($Command)).$Command, 16);
		   	break;
		   case "check_bytes_serial":
		   	//IPS_LogMessage("IPS2GPIO Check Bytes Serial", "Handle: ".GetValueInteger($this->GetIDForIdent("Serial_Handle")));
		   	$this->CommandClientSocket(pack("L*", 82, $this->GetBuffer("Serial_Handle"), 0, 0), 16);
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
				$PinUsed = $this->GetBuffer("PinUsed");
				$this->CommandClientSocket(pack("L*", 0, 4, 1, 0), 16);
				$PinUsed[4] = 99999; 
				$this->SetBuffer("PinUsed", serialize($PinUsed));
				$this->SetBuffer("1Wire_Configured", 1);
				$this->SendDebug("Get Serial Handle", "Mode der GPIO fuer 1Wire gesetzt", 0);
			}
			$Result = $this->GetOneWireDevices();
			$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_1wire_devices", "InstanceID" => $data->InstanceID, "Result"=>utf8_encode($Result) )));
			break;
		case "get_1W_data":
			$Result = $this->SSH_Connect_Array($data->Command);
			//IPS_LogMessage("IPS2GPIO 1-Wire-Data", $Result );
			$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_1wire_data", "InstanceID" => $data->InstanceID, "Result"=>utf8_encode($Result) )));
			break;
		
		}
	return $Result;
	}
	
	public function ReceiveData($JSONString) {
 	    	$CmdPossible = array(19, 21, 76, 81, 99, 115, 116);
 	    	$RDlen = array(16, 32);	
 	    	// Empfangene Daten vom I/O
	    	$Data = json_decode($JSONString);
	    	$Message = utf8_decode($Data->Buffer);
	    	$MessageLen = strlen($Message);
	    	$MessageArray = unpack("L*", $Message);
		$Command = $MessageArray[1];
		$SerialRead = false;
	    	
		
		 // Analyse der eingegangenen Daten
		 for ($i = 1; $i <= Count($MessageArray); $i++) {
			$this->SendDebug("Datenanalyse", "i: ".$i." Laenge: ".$MessageLen." SeqNo: ".($MessageArray[$i] & 65535)." Counter: ".$this->GetBuffer("NotifyCounter"), 0);
			 
			If (($MessageLen == 12) OR (($MessageArray[$i] & 65535) == $this->GetBuffer("NotifyCounter"))) {
				// Struktur:
				// H seqno: starts at 0 each time the handle is opened and then increments by one for each report.
				// H flags: three flags are defined, PI_NTFY_FLAGS_WDOG, PI_NTFY_FLAGS_ALIVE, and PI_NTFY_FLAGS_EVENT. 
					//If bit 5 is set (PI_NTFY_FLAGS_WDOG) then bits 0-4 of the flags indicate a GPIO which has had a watchdog timeout. 
					//If bit 6 is set (PI_NTFY_FLAGS_ALIVE) this indicates a keep alive signal on the pipe/socket and is sent once a minute in the absence of other notification activity. 
					//If bit 7 is set (PI_NTFY_FLAGS_EVENT) then bits 0-4 of the flags indicate an event which has been triggered. 
				// I tick: the number of microseconds since system boot. It wraps around after 1h12m. 
				// I level: indicates the level of each GPIO. If bit 1<<x is set then GPIO x is high. 
				if (array_key_exists($i + 2, $MessageArray)) {
					$SeqNo = $MessageArray[$i] & 65535;
					$Flags = $MessageArray[$i] >> 16;
					$Event = (int)boolval($Flags & 128);
					$KeepAlive = (int)boolval($Flags & 64);
					$WatchDog = (int)boolval($Flags & 32);
					$Tick = $MessageArray[$i + 1];
					$Level = $MessageArray[$i + 2];
					If ($KeepAlive == 1) {
						// es handelt sich um ein Event
						$this->SendDebug("Datenanalyse", "Event: KeepAlive", 0);
						SetValueInteger($this->GetIDForIdent("LastKeepAlive"), time() );
					}
					elseif ($WatchDog == 1) {
						$Bitvalue_15 = boolval($Level & pow(2, 15));
						$this->SendDebug("Datenanalyse", "Event: WatchDog - Bit 15 (RS232): ".(int)$Bitvalue_15, 0);	
						$Data = $this->CommandClientSocket(pack("L*", 82, $this->GetBuffer("Serial_Handle"), 0, 0), 16);
						If ($Data > 0) {
							$this->CommandClientSocket(pack("L*", 80, $this->GetBuffer("Serial_Handle"), $Data, 0), 16 + $Data);
						}
					}
					else {
						$PinNotify = array();
						$PinNotify = unserialize($this->GetBuffer("PinNotify"));
						// Werte durchlaufen
						If ($this->GetBuffer("Serial_Configured") == 0) {
							for ($j = 0; $j < Count($PinNotify); $j++) {
								$Bitvalue = boolval($Level & (1<<$PinNotify[$j]));
								$this->SendDebug("Datenanalyse", "Event: Interrupt - Bit ".$PinNotify[$j]." Wert: ".(int)$Bitvalue, 0);
								$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"notify", "Pin" => $PinNotify[$j], "Value"=> $Bitvalue, "Timestamp"=> $Tick)));
							}
						}
						else {
							for ($j = 0; $j < Count($PinNotify); $j++) {
								If ($PinNotify[$j] <> 15) {
									$Bitvalue = boolval($Level & (1<<$PinNotify[$j]));
									$this->SendDebug("Datenanalyse", "Event: Interrupt - Bit ".$PinNotify[$j]." Wert: ".(int)$Bitvalue, 0);
									$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"notify", "Pin" => $PinNotify[$j], "Value"=> $Bitvalue, "Timestamp"=> $Tick)));
								}
								/*
								else {
									If ($SerialRead == false) {
										// Wert von Pin 15
										
										$Bitvalue_15 = boolval($Level & pow(2, 15));
										$this->SendDebug("Datenanalyse", "Event: Interrupt - Bit 15 (RS232): ".(int)$Bitvalue_15, 0);	
										$SerialRead = true;
										IPS_Sleep(100);
										$Data = $this->CommandClientSocket(pack("L*", 82, $this->GetBuffer("Serial_Handle"), 0, 0), 16);
										If ($Data > 0) {
											$this->CommandClientSocket(pack("L*", 80, $this->GetBuffer("Serial_Handle"), $Data, 0), 16 + $Data);
										}
										
									}
								}
								*/
							}
						}	
					}
					$this->SetBuffer("NotifyCounter", $SeqNo + 1);
					$i = $i + 2;
				}
			}
			else {
				if (array_key_exists($i + 3, $MessageArray)) {
					$this->SendDebug("Datenanalyse", "Kommando: ".$MessageArray[$i], 0);
					$this->ClientResponse(pack("L*", $MessageArray[$i], $MessageArray[$i + 1], $MessageArray[$i + 2], $MessageArray[$i + 3]));
					$i = $i + 3;
				}
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
  
	// Aktualisierung der genutzten Pins und der Notifikation
	private function Get_PinUpdate()
	{
		// Pins ermitteln für die ein Notify erforderlich ist
		$PinNotify = array();
		$this->SetBuffer("PinNotify", serialize($PinNotify));
		// Notify zurücksetzen	
		If ($this->GetBuffer("Handle") >= 0) {
	           	$this->CommandClientSocket(pack("L*", 19, $this->GetBuffer("Handle"), $this->CalcBitmask(), 0), 16);
		}
		
		// Ermitteln der genutzten I2C-Adressen
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_used_i2c")));
		// Ermitteln der sonstigen Seriellen Schnittstellen-Daten
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_serial")));
		// Ermitteln der sonstigen genutzen GPIO
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_usedpin")));
		// Start-trigger für andere Instanzen (BT, RPi)
		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"get_start_trigger")));
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
			
			if (IPS_SemaphoreEnter("CommandClientSocket", 5))
			{
				if (!$this->Socket)
				{
					// Socket erstellen
					if(!($this->Socket = socket_create(AF_INET, SOCK_STREAM, 0))) {
						$errorcode = socket_last_error();
						$errormsg = socket_strerror($errorcode);
						//IPS_LogMessage("GeCoS_IO Socket", "Fehler beim Erstellen ".$errorcode." ".$errormsg);
						//$this->SendDebug("CommandClientSocket", "Fehler beim Erstellen ".$errorcode." ".$errormsg, 0);
						return;
					}
					// Timeout setzen
					socket_set_option($this->Socket, SOL_SOCKET, SO_RCVTIMEO, array("sec"=>2, "usec"=>0));
					// Verbindung aufbauen
					if(!(socket_connect($this->Socket, $this->ReadPropertyString("IPAddress"), 8888))) {
						$errorcode = socket_last_error();
						$errormsg = socket_strerror($errorcode);
						//IPS_LogMessage("GeCoS_IO Socket", "Fehler beim Verbindungsaufbaus ".$errorcode." ".$errormsg);
						//$this->SendDebug("CommandClientSocket", "Fehler beim Verbindungsaufbaus ".$errorcode." ".$errormsg, 0);
						return;
					}
					
					
					if (!$this->Socket) {
						IPS_LogMessage("IPS2GPIO Socket", "Fehler beim Verbindungsaufbau ".$errno." ".$errstr);
						$this->SendDebug("CommandClientSocket", "Fehler beim Verbindungsaufbau ".$errno." ".$errstr, 0);
						// Testballon an IPS-ClientSocket
						$this->ClientSocket(pack("L*", 17, 0, 0, 0));						
						$this->SetStatus(201);
						IPS_SemaphoreLeave("CommandClientSocket");
						return $Result;
					}
				}
				
				// Message senden
				if( ! socket_send ($this->Socket, $message, strlen($message), 0))
				{
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("IPS2GPIO Socket", "Fehler beim beim Senden ".$errorcode." ".$errormsg);
					return;
				}
				//Now receive reply from server
				if(socket_recv ($this->Socket, $buf, $ResponseLen, MSG_WAITALL ) === FALSE) {
					$errorcode = socket_last_error();
					$errormsg = socket_strerror($errorcode);
					IPS_LogMessage("IPS2GPIO Socket", "Fehler beim beim Empfangen ".$errorcode." ".$errormsg);
					return;
				}
				// Anfragen mit variabler Rückgabelänge
				$CmdVarLen = array(56, 67, 70, 73, 75, 80, 88, 91, 92, 106, 109);
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
				IPS_SemaphoreLeave("CommandClientSocket");
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
		        	If ($response[4] == 0) {
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
		            	$Typ[0] = array(0, 1, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 21, 22, 23, 24, 25);	
           			$Typ[1] = array(2, 3, 4, 7, 8, 9, 10, 11, 14, 15, 17, 18, 22, 23, 24, 25, 27);
           			$Typ[2] = array(2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27);
           			
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
					If ($response[4] < 64 ) {
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
		         case "37":
           			If ($response[4] >= 0) {
           				$Result = true;
           			}
           			else {
           				$Result = false;
           			}
		            	break;
			case "54":
		        	If ($response[4] >= 0 ) {
           				If ($this->GetBuffer("I2CSearch") == 0) {
						//IPS_LogMessage("IPS2GPIO I2C Handle",$response[4]." für Device ".$response[3]);
						//$I2C_DeviceHandle = unserialize($this->GetBuffer("I2C_Handle"));
						// Hier wird der ermittelte Handle der DiviceAdresse/Bus hinzugefügt
						$I2C_DeviceHandle[($response[2] << 7) + $response[3]] = $response[4];

						//$I2C_DeviceHandle[$response[3]] = $response[4];
						//$this->SetBuffer("I2C_Handle", serialize($I2C_DeviceHandle));
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
					$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_byte_block", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Count" => $response[4], "ByteArray" => $ByteArray)));
				}
		            	else {
           				IPS_LogMessage("IPS2GPIO I2C Read Bytes","Handle: ".$response[2]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
				break; 
			case "59":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO I2C Read Byte Handle","Handle: ".$response[2]." Value: ".$response[4]);
		            		If ($this->GetBuffer("I2CSearch") == 0) {
						$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Value" => $response[4])));
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
		            		$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_i2c_data", "DeviceIdent" => $this->GetI2C_HandleDevice($response[2]), "Register" => $response[3], "Value" => $response[4])));
		            	}
		            	else {
					IPS_LogMessage("IPS2GPIO I2C Read Byte","Handle: ".$response[2]." Register: ".$response[3]." Fehlermeldung: ".$this->GetErrorText(abs($response[4])));	
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
	           				$this->SendDataToChildren(json_encode(Array("DataID" => "{8D44CA24-3B35-4918-9CBD-85A28C0C8917}", "Function"=>"set_serial_data", "Value"=>utf8_encode(substr($Message, -($response[4]))) )));
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
		        case "97":
           			If ($response[4] >= 0) {
           				//IPS_LogMessage("IPS2GPIO GlitchFilter","gesetzt");
           			}
           			else {
           				IPS_LogMessage("IPS2GPIO GlitchFilter","Fehlermeldung: ".$this->GetErrorText(abs($response[4])));
           			}
         
		            	break;
		        case "99":
           			If ($response[4] >= 0 ) {
           				$this->SendDebug("Handle", $response[4], 0);
					
					//$this->SetBuffer("Handle", $response[4]);
           				//$this->ClientSocket(pack("L*", 19, $response[4], $this->CalcBitmask(), 0));
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
	
	public function PIGPIOD_Restart()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			// Verbindung trennen
			IPS_SetProperty($this->GetParentID(), "Open", false);
			IPS_ApplyChanges($this->GetParentID());
			// PIGPIO beenden und neu starten
			$this->SSH_Connect("sudo killall pigpiod");
			// Wartezeit
			IPS_Sleep(2000);
			$this->SSH_Connect("sudo pigpiod");
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
		$PinNotify = unserialize($this->GetBuffer("PinNotify"));
		$this->SetBuffer("NotifyCounter", 0);
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
			//IPS_LogMessage("IPS2GPIO Netzanbindung","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert");
		      	$this->SendDebug("Netzanbindung", "Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert", 0);
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geschlossen!");
					$this->SendDebug("Netzanbindung", "Port ist geschlossen!", 0);
					// Versuchen PIGPIO zu starten
					IPS_LogMessage("IPS2GPIO Netzanbindung: ","Versuche PIGPIO per SSH zu starten...");
					$this->SendDebug("Netzanbindung", "Versuche PIGPIO per SSH zu starten...", 0);
					$this->SSH_Connect("sudo pigpiod");
					$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8888, $errno, $errstr, 10);
					if (!$status) {
						IPS_LogMessage("IPS2GPIO Netzanbindung: ","Port ist geschlossen!");
						$this->SendDebug("Netzanbindung", "Port ist geschlossen!", 0);
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
			$this->SetStatus(104);
		}
	return $result;
	}
	
	private function SetMUX($Port)
	{
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
		
		$this->SetBuffer("MUX_Channel", $Port);
		$MUX_Handle = $this->GetBuffer("MUX_Handle");
		If ($Port == 1) {
			$this->CommandClientSocket(pack("L*", 60, $MUX_Handle, 0, 0), 16);
		}
		else {
			$this->CommandClientSocket(pack("L*", 60, $MUX_Handle, $Port, 0), 16);
		}
	return;
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
				IPS_LogMessage("IPS2GPIO_IO CheckConfig","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			    	$Result = "";
				return serialize($arrayCheckConfig);
			}
			
			// I²C Schnittstelle
			$PathConfig = "/boot/config.txt";
			// Prüfen, ob die Datei existiert
			if (!$sftp->file_exists($PathConfig)) {
				$this->SendDebug("CheckConfig", $PathConfig." nicht gefunden!", 0);
				IPS_LogMessage("IPS2GPIO_IO CheckConfig", $PathConfig." nicht gefunden!");
			}
			else {
				$FileContentConfig = $sftp->get($PathConfig);
				// Prüfen ob I2C aktiviert ist
				$Pattern = "/(?:\r\n|\n|\r)(\s*)(device_tree_param|dtparam)=([^,]*,)*i2c(_arm)?(=(on|true|yes|1))(\s*)($:\r\n|\n|\r)/";
				if (preg_match($Pattern, $FileContentConfig)) {
					$this->SendDebug("CheckConfig", "I2C ist aktiviert", 0);
					$arrayCheckConfig["I2C"]["Status"] = "aktiviert";
					$arrayCheckConfig["I2C"]["Color"] = "#00FF00";
				} else {
					$this->SendDebug("CheckConfig", "I2C ist deaktiviert!", 0);
					IPS_LogMessage("IPS2GPIO_IO CheckConfig", "I2C ist deaktiviert!");
					$arrayCheckConfig["I2C"]["Status"] = "deaktiviert";
					$arrayCheckConfig["I2C"]["Color"] = "#FF0000";
				}
				// Prüfen ob 1-Wie-Server aktiviert ist
				$Pattern = "/(?:\r\n|\n|\r)(\s*)(dtoverlay)(=(w1-gpio))(\s*)($:\r\n|\n|\r)/";
				if (preg_match($Pattern, $FileContentConfig)) {
					$this->SendDebug("CheckConfig", "1-Wire-Server ist aktiviert", 0);
					$arrayCheckConfig["1-Wire-Server"]["Status"] = "aktiviert";
					$arrayCheckConfig["1-Wire-Server"]["Color"] = "#00FF00";			
				} else {
					$this->SendDebug("CheckConfig", "1-Wire-Server ist deaktiviert!", 0);
					IPS_LogMessage("IPS2GPIO_IO CheckConfig", "1-Wire-Server ist deaktiviert!");
					$arrayCheckConfig["1-Wire-Server"]["Status"] = "deaktiviert";
					$arrayCheckConfig["1-Wire-Server"]["Color"] = "#FF0000";
				}
				// Prüfen ob die serielle Schnittstelle aktiviert ist
				$Pattern = "/(?:\r\n|\n|\r)(\s*)(enable_uart)(=(on|true|yes|1))(\s*)($:\r\n|\n|\r)/";
				if (preg_match($Pattern, $FileContentConfig)) {
					$this->SendDebug("CheckConfig", "Serielle Schnittstelle ist aktiviert", 0);
					$arrayCheckConfig["Serielle Schnittstelle"]["Status"] = "aktiviert";
					$arrayCheckConfig["Serielle Schnittstelle"]["Color"] = "#00FF00";			
				} else {
					$this->SendDebug("CheckConfig", "Serielle Schnittstelle ist deaktiviert!", 0);
					IPS_LogMessage("IPS2GPIO_IO CheckConfig", "Serielle Schnittstelle ist deaktiviert!");
					$arrayCheckConfig["Serielle Schnittstelle"]["Status"] = "deaktiviert";
					$arrayCheckConfig["Serielle Schnittstelle"]["Color"] = "#FF0000";
				}
				
			}
			
			//Serielle Schnittstelle
			$PathCmdline = "/boot/cmdline.txt";
			// Prüfen, ob die Datei existiert
			if (!$sftp->file_exists($PathCmdline)) {
				$this->SendDebug("CheckConfig", $PathCmdline." nicht gefunden!", 0);
				IPS_LogMessage("GeCoS_IO CheckConfig", $PathCmdline." nicht gefunden!");
			}
			else {
				$FileContentCmdline = $sftp->get($PathCmdline);
				// Prüfen ob die Shell der serielle Schnittstelle aktiviert ist
				$Pattern = "/console=(serial0|ttyAMA(0|1)|tty(0|1))/";
				if (preg_match($Pattern, $FileContentCmdline)) {
					$this->SendDebug("CheckConfig", "Shell-Zugriff auf serieller Schnittstelle ist deaktiviert", 0);
					$arrayCheckConfig["Shell Zugriff"]["Status"] = "deaktiviert";
					$arrayCheckConfig["Shell Zugriff"]["Color"] = "#00FF00";
				} else {
					$this->SendDebug("CheckConfig", "Shell-Zugriff auf serieller Schnittstelle ist aktiviert!", 0);
					IPS_LogMessage("GeCoS_IO CheckConfig", "Shell-Zugriff auf serieller Schnittstelle ist aktiviert!");
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
				IPS_LogMessage("GeCoS_IO CheckConfig", "PIGPIO-Server ist deaktiviert!");
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
		// AS3935
		for ($i = 3; $i <= 4; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "AS3935";
		}
		// PCF8574
		for ($i = 32; $i <= 34; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8574";
		}
		// BH1750
		$SearchArray[] = 35;
		$DeviceName[] = "BH1750";
		// PCF8574
		for ($i = 36; $i <= 39; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8574";
		}
		// PCF8574
		for ($i = 56; $i <= 63; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8574";
		}
		// PCF8591
		for ($i = 72; $i <= 79; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "PCF8591";
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
		// MCP3424
		for ($i = 104; $i <= 110; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "MCP3424";
		}
		// MUX
		$SearchArray[] = 112;
		$DeviceName[] = "MUX";
		// BME280
		for ($i = 118; $i <= 119; $i++) {
			$SearchArray[] = $i;
			$DeviceName[] = "BME280";
		}					
		$k = 0;
		
		$this->SetBuffer("I2CSearch", 1);
		for ($j = 1; $j <= 1; $j++) {

			for ($i = 0; $i < count($SearchArray); $i++) {
				
				// Handle ermitteln
				$Handle = $this->CommandClientSocket(pack("L*", 54, $j, $SearchArray[$i], 4, 0), 16);
				//$this->SendDebug("SearchI2CDevices", "Device prüfen auf Bus: ".$j." Adresse: ".$i, 0);

				if ($Handle >= 0) {
					// Testweise lesen
					$Result = $this->CommandClientSocket(pack("L*", 59, $Handle, 0, 0), 16);
					//$this->SendDebug("SearchI2CDevices", "Device lesen auf Bus: ".$j." Adresse: ".$i, 0);

					If ($Result >= 0) {
						$this->SendDebug("SearchI2CDevices", "Device gefunden auf Bus: ".$j." Adresse: ".$SearchArray[$i]." Ergebnis des Test-Lesen: ".$Result, 0);
						$DeviceArray[$k][0] = $DeviceName[$i];
						$DeviceArray[$k][1] = $SearchArray[$i];
						$DeviceArray[$k][2] = $j;
						$DeviceArray[$k][3] = 0;
						$DeviceArray[$k][4] = "OK";
						// Farbe gelb für erreichbare aber nicht registrierte Instanzen
						$DeviceArray[$k][5] = "#FFFF00";
						$k = $k + 1;
						//$this->SendDebug("SearchI2CDevices", "Ergebnis: ".$DeviceName[$i]." DeviceAddresse: ".$SearchArray[$i]." an Bus: ".($j - 4), 0);
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
			10494082 => "Rev.a02082 3 Model B PCB-Rev. 1.2 1GB Sony", 10625154 => "Rev.a22082 3 Model B PCB-Rev. 1.2 1GB Embest", 44044353 => "Rev.2a01041 2 Model B PCB-Rev. 1.1 1GB Sony (overvoltage)");
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
}
?>
