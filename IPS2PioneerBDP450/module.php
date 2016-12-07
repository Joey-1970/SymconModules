<?
class IPS2PioneerBDP450 extends IPSModule
{
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
           	$this->RequireParent("{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}");
		$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyString("IPAddress", "127.0.0.1");
		$this->RegisterPropertyInteger("DataUpdate", 15);
		$this->RegisterTimer("DataUpdate", 0, 'I2BDP_Get_DataUpdate($_IPS["TARGET"]);');
		$this->RegisterPropertyBoolean("RC_Data", false);
		
        return;
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		$this->SetBuffer("LastCommand", "");
		$this->SetBuffer("LastCommandTimestamp", 0);
		$this->SetBuffer("LastResponseTimestamp", 0);
		
		$this->RegisterVariableString("PlayerModel", "PlayerModel", "", 5);
		$this->DisableAction("PlayerModel");
		$this->RegisterVariableString("PlayerFirmware", "PlayerFirmware", "", 7);
		$this->DisableAction("PlayerFirmware");
		
		$this->RegisterVariableBoolean("Power", "Power", "~Switch", 10);
		$this->EnableAction("Power");
		$this->RegisterVariableString("Modus", "Modus", "", 20);
		$this->DisableAction("Modus");
		$this->RegisterVariableInteger("Chapter", "Chapter", "", 30);
		$this->DisableAction("Chapter");
		
		//$this->RegisterVariableInteger("Time", "Time", "~UnixTimestampTime", 40);
		$this->RegisterVariableString("Time", "Time", "", 40);
		$this->DisableAction("Time");
		//$this->RegisterVariableString("StatusRequest", "StatusRequest", "", 50);
		//$this->DisableAction("StatusRequest");
		$this->RegisterVariableInteger("Track", "Track", "", 60);
		$this->DisableAction("Track");
		$this->RegisterVariableString("DiscLoaded", "DiscLoaded", "", 70);
		$this->DisableAction("DiscLoaded");
		$this->RegisterVariableString("Application", "Application", "", 80);
		$this->DisableAction("Application");
		$this->RegisterVariableString("Information", "Information", "", 90);
		$this->DisableAction("Information");
		
		If ($this->ReadPropertyBoolean("RC_Data") == true) {
			$this->RegisterVariableBoolean("rc_POWER ", "POWER ", "~Switch", 500);
			$this->EnableAction("rc_power");
			$this->RegisterVariableBoolean("rc_CONTINUED", "CONTINUED", "~Switch", 505);
			$this->EnableAction("rc_CONTINUED");
			$this->RegisterVariableBoolean("rc_OPEN_CLOSE", "OPEN/CLOSE", "~Switch", 510);
			$this->EnableAction("rc_OPEN_CLOSE");
			$this->RegisterVariableBoolean("rc_AUDIO", "AUDIO", "~Switch", 520);
			$this->EnableAction("rc_AUDIO");
			$this->RegisterVariableBoolean("rc_1", "1", "~Switch", 530);
			$this->EnableAction("rc_1");
			$this->RegisterVariableBoolean("rc_2", "2", "~Switch", 540);
			$this->EnableAction("rc_2");
			$this->RegisterVariableBoolean("rc_3", "3", "~Switch", 550);
			$this->EnableAction("rc_3");
			$this->RegisterVariableBoolean("rc_4", "4", "~Switch", 560);
			$this->EnableAction("rc_4");
			$this->RegisterVariableBoolean("rc_5", "5", "~Switch", 570);
			$this->EnableAction("rc_5");
			$this->RegisterVariableBoolean("rc_6", "6", "~Switch", 580);
			$this->EnableAction("rc_6");
			$this->RegisterVariableBoolean("rc_7", "7", "~Switch", 590);
			$this->EnableAction("rc_7");
			$this->RegisterVariableBoolean("rc_8", "8", "~Switch", 600);
			$this->EnableAction("rc_8");
			$this->RegisterVariableBoolean("rc_9", "9", "~Switch", 610);
			$this->EnableAction("rc_9");
			$this->RegisterVariableBoolean("rc_0", "0", "~Switch", 620);
			$this->EnableAction("rc_0");
			$this->RegisterVariableBoolean("rc_SUBTITLE", "SUBTITLE", "~Switch", 640);
			$this->EnableAction("rc_SUBTITLE");
			$this->RegisterVariableBoolean("rc_ANGLE", "ANGLE", "~Switch", 650);
			$this->EnableAction("rc_ANGLE");
			$this->RegisterVariableBoolean("rc_FL_DIMMER", "FL DIMMER", "~Switch", 660);
			$this->EnableAction("rc_FL_DIMMER");
			$this->RegisterVariableBoolean("rc_CD_SACD", "CD/SACD", "~Switch", 670);
			$this->EnableAction("rc_CD_SACD");
			$this->RegisterVariableBoolean("rc_HDMI", "HDMI", "~Switch", 680);
			$this->EnableAction("rc_HDMI");
			$this->RegisterVariableBoolean("rc_TOP_MENU", "TOP MENU", "~Switch", 690);
			$this->EnableAction("rc_TOP_MENU");
			$this->RegisterVariableBoolean("rc_FUNCTION", "FUNCTION", "~Switch", 700);
			$this->EnableAction("rc_FUNCTION");
			$this->RegisterVariableBoolean("rc_EXIT", "EXIT", "~Switch", 710);
			$this->EnableAction("rc_EXIT");
			$this->RegisterVariableBoolean("rc_HOME_MEDIA_GALLERY", "HOME MEDIA GALLERY", "~Switch", 720);
			$this->EnableAction("rc_HOME_MEDIA_GALLERY");
			$this->RegisterVariableBoolean("rc_POPUP_MENU", "POPUP MENU", "~Switch", 730);
			$this->EnableAction("rc_POPUP_MENU");
			$this->RegisterVariableBoolean("rc_UP", "UP", "~Switch", 740);
			$this->EnableAction("rc_UP");
			$this->RegisterVariableBoolean("rc_LEFT", "LEFT", "~Switch", 750);
			$this->EnableAction("rc_LEFT");
			$this->RegisterVariableBoolean("rc_ENTER", "ENTER", "~Switch", 760);
			$this->EnableAction("rc_ENTER");
			$this->RegisterVariableBoolean("rc_RIGHT", "RIGHT", "~Switch", 770);
			$this->EnableAction("rc_RIGHT");
			$this->RegisterVariableBoolean("rc_DOWN", "DOWN", "~Switch", 780);
			$this->EnableAction("rc_DOWN");
			$this->RegisterVariableBoolean("rc_HOME_MENU", "HOME MENU", "~Switch", 790);
			$this->EnableAction("rc_HOME_MENU");
			$this->RegisterVariableBoolean("rc_RETURN", "RETURN", "~Switch", 800);
			$this->EnableAction("rc_RETURN");
			$this->RegisterVariableBoolean("rc_COLOR_1", "COLOR 1 (PROGRAM)", "~Switch", 810);
			$this->EnableAction("rc_COLOR_1");
			$this->RegisterVariableBoolean("rc_COLOR_2", "COLOR 2 (BOOKMARK)", "~Switch", 800);
			$this->EnableAction("rc_COLOR_2");
			$this->RegisterVariableBoolean("rc_COLOR_3", "COLOR 3（ZOOM）", "~Switch", 810);
			$this->EnableAction("rc_COLOR_3");
			$this->RegisterVariableBoolean("rc_COLOR_4", "COLOR 4（INDEX）", "~Switch", 820);
			$this->EnableAction("rc_COLOR_4");
			$this->RegisterVariableBoolean("rc_REV_SCAN", "REV SCAN", "~Switch", 830);
			$this->EnableAction("rc_REV_SCAN");
			$this->RegisterVariableBoolean("rc_PLAY", "PLAY", "~Switch", 840);
			$this->EnableAction("rc_PLAY");
			$this->RegisterVariableBoolean("rc_FWD_SCAN", "FWD SCAN", "~Switch", 850);
			$this->EnableAction("rc_FWD_SCAN");
			$this->RegisterVariableBoolean("rc_PREV_STEP_SLOW", "PREV/STEP/SLOW", "~Switch", 860);
			$this->EnableAction("rc_PREV_STEP_SLOW");
			$this->RegisterVariableBoolean("rc_PAUSE", "PAUSE", "~Switch", 870);
			$this->EnableAction("rc_PAUSE");
			$this->RegisterVariableBoolean("rc_STOP", "STOP", "~Switch", 880);
			$this->EnableAction("rc_STOP");
			$this->RegisterVariableBoolean("rc_NEXT_STEP_SLOW", "NEXT/STEP/SLOW", "~Switch", 890);
			$this->EnableAction("rc_NEXT_STEP_SLOW");
			$this->RegisterVariableBoolean("rc_2nd_VIDEO", "2nd VIDEO", "~Switch", 900);
			$this->EnableAction("rc_2nd_VIDEO");
			$this->RegisterVariableBoolean("rc_2nd_AUDIO", "2nd AUDIO", "~Switch", 910);
			$this->EnableAction("rc_2nd_AUDIO");
			$this->RegisterVariableBoolean("rc_A_B", "A-B", "~Switch", 920);
			$this->EnableAction("rc_A_B");
			$this->RegisterVariableBoolean("rc_CLEAR", "CLEAR", "~Switch", 925);
			$this->EnableAction("rc_CLEAR");
			$this->RegisterVariableBoolean("rc_REPEAT", "REPEAT", "~Switch", 930);
			$this->EnableAction("rc_REPEAT");
			$this->RegisterVariableBoolean("rc_DISPLAY", "DISPLAY", "~Switch", 935);
			$this->EnableAction("rc_DISPLAY");
			$this->RegisterVariableBoolean("rc_KEYLOCK", "KEYLOCK", "~Switch", 940);
			$this->EnableAction("rc_KEYLOCK");
			$this->RegisterVariableBoolean("rc_REPLAY", "REPLAY", "~Switch", 945);
			$this->EnableAction("rc_REPLAY");
			$this->RegisterVariableBoolean("rc_SKIP_SEACH", "SKIP SEACH", "~Switch", 950);
			$this->EnableAction("rc_SKIP_SEACH");
			$this->RegisterVariableBoolean("rc_NET_FLIX", "NET FLIX", "~Switch", 955);
			$this->EnableAction("rc_NET_FLIX");
		}
		
		If (IPS_GetKernelRunlevel() == 10103) {
			$ParentID = $this->GetParentID();
			If ($ParentID > 0) {
				If (IPS_GetProperty($ParentID, 'Host') <> $this->ReadPropertyString('IPAddress')) {
		                	IPS_SetProperty($ParentID, 'Host', $this->ReadPropertyString('IPAddress'));
				}
				If (IPS_GetProperty($ParentID, 'Port') <> 8102) {
		                	IPS_SetProperty($ParentID, 'Port', 8102);
				}
			}
			
			
			If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
				$this->SetTimerInterval("DataUpdate", ($this->ReadPropertyInteger("DataUpdate") * 1000));
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}	   
		}
	return;
	}
	
	public function ReceiveData($JSONString) {
 	    	// Empfangene Daten vom I/O
	    	$this->SetBuffer("LastResponseTimestamp", time());
		$Data = json_decode($JSONString);
		$Message = utf8_decode($Data->Buffer);
		// Entfernen der Steuerzeichen
		$Message = trim($Message, "\x00..\x1F");
		$LastCommand = trim($this->GetBuffer("LastCommand"), "\x00..\x1F");
		//IPS_LogMessage("IPS2PioneerBDP450","LastCommand: ".$this->GetBuffer("LastCommand")."Client Response: ".$Message);
		
		switch($LastCommand) {
			case "?P":
				If (($Message == "E04") AND (GetValueBoolean($this->GetIDForIdent("Power")) == true)) {
					// Gerät ist ausgeschaltet
					SetValueBoolean($this->GetIDForIdent("Power"), false);
					SetValueString($this->GetIDForIdent("Modus"), "");
					SetValueInteger($this->GetIDForIdent("Chapter"), 0);
					SetValueString($this->GetIDForIdent("Time"), "--:--:--");
					//SetValueString($this->GetIDForIdent("StatusRequest"), "");
					SetValueInteger($this->GetIDForIdent("Track"), 0);
					SetValueString($this->GetIDForIdent("DiscLoaded"), "");
					SetValueString($this->GetIDForIdent("Application"), "");
					SetValueString($this->GetIDForIdent("Information"), "");
				}
				else {
					// Gerät ist eingeschaltet
					SetValueBoolean($this->GetIDForIdent("Power"), true);
					If (GetValueString($this->GetIDForIdent("PlayerModel")) == "") {
						$this->Get_BasicData();
					}
					else {
						SetValueString($this->GetIDForIdent("Modus"), $this->GetModus((int)substr($Message, 1, 2)));
						// Prüfen ob eine Disk im Laufwerk ist
						$this->ClientSocket("?D".chr(13));
						$this->ResponseWait();
					}
				}
				break;
			case "?D":
				If (substr($Message, 0, 1) == "x") {
					SetValueString($this->GetIDForIdent("DiscLoaded"), "Unknown");
				}
				elseif (substr($Message, 0, 1) == "0") {
					SetValueString($this->GetIDForIdent("DiscLoaded"), "None");
				}
				elseif (substr($Message, 0, 1) == "1") {
					SetValueString($this->GetIDForIdent("DiscLoaded"), "Yes");
					// Abfrage des Mediums
					If (substr($Message, 1, 1) == "x") {
						SetValueString($this->GetIDForIdent("Information"),"No Disc");
						$this->SetBuffer("Information", 3);
					}
					else {
						SetValueString($this->GetIDForIdent("Information"), $this->GetInformation((int)substr($Message, 1, 1)));
						$this->SetBuffer("Information", (int)substr($Message, 1, 1));
					}
					// Abfrage der Anwendung
					If (substr($Message, 2, 1) == "x") {
						SetValueString($this->GetIDForIdent("Application"),"Unknown");
					}
					else {
						SetValueString($this->GetIDForIdent("Application"), $this->GetApplication((int)substr($Message, 2, 1)));
					}
					//IPS_LogMessage("IPS2PioneerBDP450","Information: ".$this->GetBuffer("Information"));
					
					If ( (int)$this->GetBuffer("Information") <> 3) {
						// Abfrage des Chapters
						$this->ClientSocket("?C".chr(13));
						$this->ResponseWait();
					}
				}
				break;
			case "?C":
				SetValueInteger($this->GetIDForIdent("Chapter"), (int)$Message);
				// Titel/Track Nummer
				$this->ClientSocket("?R".chr(13));
				$this->ResponseWait();
				break;
			
			case "?R":
				SetValueInteger($this->GetIDForIdent("Track"), (int)$Message);
					// Abfrage der Zeit
					$this->ClientSocket("?T".chr(13));
					$this->ResponseWait();
					/*
					If ((int)$this->GetBuffer("Information") == 0) {
						// Bei Bluray
						$this->ClientSocket("?I".chr(13));
						$this->ResponseWait();
					}
					elseif ((int)$this->GetBuffer("Information") == 1) {
						// Bei DVD
						$this->ClientSocket("?V".chr(13));
						$this->ResponseWait();
					}
					elseif ((int)$this->GetBuffer("Information") == 2) {
						// Bei CD
						$this->ClientSocket("?K".chr(13));
						$this->ResponseWait();
					}
					*/
				break;
			case "?T":
				$Message = str_pad((string)$Message, 6 ,'0', STR_PAD_LEFT);
				SetValueString($this->GetIDForIdent("Time"), substr($Message, 0, 2).":".substr($Message, 2, 2).":".substr($Message, 4, 2));
				break;
			case "?V":
				//SetValueString($this->GetIDForIdent("StatusRequest"), (string)$Message);	
				break;
			case "?I":
				//SetValueString($this->GetIDForIdent("StatusRequest"), (string)$Message);	
				break;
			case "?K":
				//SetValueString($this->GetIDForIdent("StatusRequest"), (string)$Message);	
				break;
			case "?L":
				SetValueString($this->GetIDForIdent("PlayerModel"), (string)$Message);
				// Firmware abfragen
				$this->ClientSocket("?Z".chr(13));
				$this->ResponseWait();
				break;
			case "?Z":
				SetValueString($this->GetIDForIdent("PlayerFirmware"), (string)$Message);
				// Erste Abfrage der Daten
				$this->ClientSocket("?P".chr(13));
				$this->ResponseWait();
				break;
		}
	return;
	}
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
			case "rc_POWER":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFBC/RU".chr(13));				
				}
				break;
			case "rc_CONTINUED":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFAA/RU".chr(13));				
				}
				break;
			case "rc_OPEN_CLOSE":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFB6/RU".chr(13));				
				}
				break;
			case "rc_AUDIO":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFBE/RU".chr(13));				
				}
				break;
			case "rc_SUBTITLE":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF36/RU".chr(13));				
				}
				break;
			case "rc_ANGLE":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFB5/RU".chr(13));				
				}
				break;
			case "rc_FL_DIMMER":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFF9/RU".chr(13));				
				}
				break;
			case "rc_CD_SACD":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF2A/RU".chr(13));				
				}
				break;
			case "rc_HDMI":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFF8/RU".chr(13));				
				}
				break;
			case "rc_TOP_MENU":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFB4/RU".chr(13));				
				}
				break;
			case "rc_FUNCTION":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFB3/RU".chr(13));				
				}
				break;
			case "rc_EXIT":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF20/RU".chr(13));				
				}
				break;
			case "rc_HOME_MEDIA_GALLERY":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFF7/RU".chr(13));				
				}
				break;
			case "rc_POPUP_MENU":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFB9/RU".chr(13));				
				}
				break;
			case "rc_UP":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A184FFFF/RU".chr(13));				
				}
				break;
			case "rc_LEFT":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A187FFFF/RU".chr(13));				
				}
				break;	
			case "rc_ENTER":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFEF/RU".chr(13));				
				}
				break;
			case "rc_RIGHT":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A186FFFF/RU".chr(13));				
				}
				break;		
			case "rc_DOWN":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A185FFFF/RU".chr(13));				
				}
				break;
			case "rc_HOME_MENU":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFB0/RU".chr(13));				
				}
				break;	
			case "rc_RETURN":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFF4/RU".chr(13));				
				}
				break;
			case "rc_COLOR_1":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF60/RU".chr(13));				
				}
				break;	
			case "rc_COLOR_2":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF61/RU".chr(13));				
				}
				break;
			case "rc_COLOR_3":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF62/RU".chr(13));				
				}
				break;		
			case "rc_COLOR_4":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF63/RU".chr(13));				
				}
				break;
			case "rc_REV_SCAN":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFEA/RU".chr(13));				
				}
				break;	
			case "rc_PLAY":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF39/RU".chr(13));				
				}
				break;
			case "rc_FWD_SCAN":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFE9/RU".chr(13));				
				}
				break;	
			case "rc_PREV_STEP_SLOW":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF3E/RU".chr(13));				
				}
				break;
			case "rc_PAUSE":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF3A/RU".chr(13));				
				}
				break;
			case "rc_STOP":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF38/RU".chr(13));				
				}
				break;
			case "rc_NEXT_STEP_SLOW":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF3D/RU".chr(13));				
				}
				break;	
			case "rc_1":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFA1/RU".chr(13));				
				}
				break;
			case "rc_2":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFA2/RU".chr(13));				
				}
				break;
			case "rc_3":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFA3/RU".chr(13));				
				}
				break;
			case "rc_4":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFA4/RU".chr(13));				
				}
				break;
			case "rc_5":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFA5/RU".chr(13));				
				}
				break;
			case "rc_6":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFA6/RU".chr(13));				
				}
				break;
			case "rc_7":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFA7/RU".chr(13));				
				}
				break;
			case "rc_8":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFA8/RU".chr(13));				
				}
				break;
			case "rc_9":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFA9/RU".chr(13));				
				}
				break;
			case "rc_0":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFA0/RU".chr(13));				
				}
				break;
			case "rc_2nd_VIDEO":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFBF/RU".chr(13));				
				}
				break;
			case "rc_2nd_AUDIO":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFBD/RU".chr(13));				
				}
				break;
			case "rc_A_B":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFE4/RU".chr(13));				
				}
				break;
			case "rc_CLEAR":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFE5/RU".chr(13));				
				}
				break;	
			case "rc_REPEAT":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFE8/RU".chr(13));				
				}
				break;
			case "rc_DISPLAY":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AFE3/RU".chr(13));				
				}
				break;
			case "rc_KEYLOCK":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF22/RU".chr(13));				
				}
				break;
			case "rc_REPLAY":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF24/RU".chr(13));				
				}
				break;	
			case "rc_SKIP_SEACH":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF25/RU".chr(13));				
				}
				break;
			case "rc_NET_FLIX":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
					$this->ClientSocket("/A181AF6A/RU".chr(13));				
				}
				break;	
			default:
			    throw new Exception("Invalid Ident");
	    	}
	return;
	}
	
	public function Get_DataUpdate()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			// Power-Status abfragen
			$this->ClientSocket("?P".chr(13));
			$this->ResponseWait();
		}
	return;
	}
	
	private function Get_BasicData()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("?L".chr(13));
			$this->ResponseWait();
		}
	return;	
	}
	
	private function ClientSocket(String $message)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$this->SetBuffer("LastCommand", $message);
			$this->SetBuffer("LastCommandTimestamp", time());
			$res = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => utf8_encode($message))));  
		}
	return;	
	}
	
	public function PowerOn()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("PN".chr(13));
		}
	return;	
	}
	
	public function PowerOff()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("PF".chr(13));
		}
	return;	
	}
	
	public function Open()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("OP".chr(13));
		}
	return;	
	}
	
	public function Close()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("CO".chr(13));
		}
	return;	
	}
	
	public function Stop()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("99RJ".chr(13));
		}
	return;	
	}
	
	public function Play()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("PL".chr(13));
		}
	return;	
	}
	
	public function Still()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("ST".chr(13));
		}
	return;	
	}
	
	public function StepForward()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("SF".chr(13));
		}
	return;	
	}
	
	public function StepReverse()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("SR".chr(13));
		}
	return;	
	}
	
	public function StopScan()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("NS".chr(13));
		}
	return;	
	}
	
	public function ScanForward()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("NF".chr(13));
		}
	return;	
	}
	
	public function ScanReverse()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->GetParentStatus() == 102)) {
			$this->ClientSocket("NR".chr(13));
		}
	return;	
	}
	
	private function ConnectionTest()
	{
	      $result = false;
	      If (Sys_Ping($this->ReadPropertyString("IPAddress"), 2000)) {
			//IPS_LogMessage("IPS2PioneerBDP450","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert");
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 8102, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("IPS2PioneerBDP450","Port ist geschlossen!");				
	   			}
	   			else {
	   				fclose($status);
					//IPS_LogMessage("IPS2PioneerBDP450","Port ist geöffnet");
					$result = true;
					$this->SetStatus(102);
	   			}
		}
		else {
			IPS_LogMessage("IPS2PioneerBDP450","IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			$this->SetStatus(104);
		}
	return $result;
	}
	
	private function GetApplication(Int $ApplicationNumber)
	{
		// substr($data, 2, 1)
		$Application = array(0 => "BDMV", 1 => "BDAV", 2 => "DVD-Video", 3 => "DVD VR", 4 => "CD-DA", 5 => "DTS-CD");
		If (array_key_exists($ApplicationNumber, $Application)) {
			$ApplicationText = $Application[$ApplicationNumber];
		}
		else {
			$ApplicationText = "unknown";
		}
	return $ApplicationText;
	}
	
	private function GetInformation(Int $InformationNumber)
	{
		// substr($data, 1, 1)
		$Information = array(0 => "Bluray", 1 => "DVD", 2 => "CD");
		If (array_key_exists($InformationNumber, $Information)) {
			$InformationText = $Information[$InformationNumber];
		}
		else {
			$InformationText = "no Disc";
		}
	return $InformationText;
	}
	
	private function GetModus(Int $ModusNumber)
	{
		// substr($data, 1, 1)
		$Modus = array(0 => "Tray opening completed", 1 => "Tray closing completed", 2 => "Disc Information loading", 3 => "Tray opening", 4 => "Play", 5 => "Still",
			      6 => "Pause", 7 => "Searching", 8 => "Forward/reverse scanning", 9 => "Forward/reverse slow play");
		If (array_key_exists($ModusNumber, $Modus)) {
			$ModusText = $Modus[$ModusNumber];
		}
		else {
			$ModusText = "unknown";
		}
	return $ModusText;
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
	
	private function ResponseWait()
		{
			 $i = 0;
			 do {
		    		IPS_Sleep(25);
				if ($i > 20)
				    {
					break;
				    }
				 $i++;
			} while ($this->GetBuffer("LastResponseTimestamp") <= $this->GetBuffer("LastCommandTimestamp"));
	      IPS_Sleep(25);
	return;
	}

}

?>
