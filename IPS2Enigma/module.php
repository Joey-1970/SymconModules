<?
    // Klassendefinition
    class IPS2Enigma extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
           	$this->RegisterPropertyBoolean("Open", false);
	    	$this->RegisterPropertyString("User", "User");
	    	$this->RegisterPropertyString("Password", "Passwort");
		$this->RegisterPropertyString("IPAddress", "127.0.0.1");
		$this->RegisterPropertyInteger("DataUpdate", 15);
		$this->RegisterPropertyBoolean("HDD_Data", false);
		$this->RegisterPropertyBoolean("Movielist_Data", false);
		$this->RegisterPropertyBoolean("Enigma2_Data", false);
		$this->RegisterPropertyBoolean("Signal_Data", false);
		$this->RegisterPropertyBoolean("Network_Data", false);
		$this->RegisterPropertyBoolean("RC_Data", false);
		$this->RegisterPropertyInteger("BouquetsNumber", 0);
		$this->RegisterPropertyBoolean("EPGnow_Data", false);
		$this->RegisterPropertyBoolean("EPGnext_Data", false);
		$this->RegisterPropertyInteger("EPGUpdate", 60);
		$this->RegisterPropertyBoolean("EPGlist_Data", false);
		$this->RegisterPropertyBoolean("EPGlistSRef_Data", false);
		$this->RegisterPropertyInteger("PiconSource", 0);
		$this->RegisterPropertyInteger("ScreenshotUpdate", 30);
		$this->RegisterPropertyInteger("Screenshot", 640);
		$this->RegisterTimer("DataUpdate", 0, 'Enigma_Get_DataUpdate($_IPS["TARGET"]);');
		$this->RegisterTimer("EPGUpdate", 0, 'Enigma_Get_EPGUpdate($_IPS["TARGET"]);');
		$this->RegisterTimer("ScreenshotUpdate", 0, 'Enigma_GetScreenshot($_IPS["TARGET"]);');
	}
        
	// Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
		// Diese Zeile nicht löschen
		parent::ApplyChanges();
		
		// Profil anlegen
		$this->RegisterProfileInteger("time.min", "Clock", "", " min", 0, 1000000, 1);
		$this->RegisterProfileInteger("snr.db", "Intensity", "", " db", 0, 1000000, 1);
		$this->RegisterProfileInteger("gigabyte.GB", "Gauge", "", " GB", 0, 1000000, 1);
		$this->RegisterMediaObject("Screenshot", 1, $this->InstanceID, 1000, true, "Screenshot.jpg");
		$this->RegisterHook("/hook/IPS2Enigma");

		$this->SetBuffer("FirstUpdate", "false");
		
		$this->RegisterVariableInteger("PiconUpdate", "Picon Update", "~UnixTimestamp", 1500);
		$this->DisableAction("PiconUpdate");
		IPS_SetHidden($this->GetIDForIdent("PiconUpdate"), true);
		
		//Status-Variablen anlegen
		If ($this->ReadPropertyBoolean("Enigma2_Data") == true) {
			$this->RegisterVariableString("e2oeversion", "E2 OE-Version", "", 10);
			$this->DisableAction("e2oeversion");
			$this->RegisterVariableString("e2enigmaversion", "E2 Version", "", 20);
			$this->DisableAction("e2enigmaversion");
			$this->RegisterVariableString("e2distroversion", "E2 Distro-Version", "", 30);
			$this->DisableAction("e2distroversion");
			$this->RegisterVariableString("e2imageversion", "E2 Image-Version", "", 40);
			$this->DisableAction("e2imageversion");
			$this->RegisterVariableString("e2webifversion", "E2 WebIf-Version", "", 50);
			$this->DisableAction("e2webifversion");
		}
		$this->RegisterVariableString("e2devicename", "Model", "", 60);
		$this->DisableAction("e2devicename");
		$this->RegisterVariableString("e2tunerinfo", "Tuner Information", "~HTMLBox", 65);
		$this->DisableAction("e2tunerinfo");
		
		If ($this->ReadPropertyBoolean("Network_Data") == true) {
			$this->RegisterVariableString("e2lanmac", "MAC", "", 70);
			$this->DisableAction("e2lanmac");
			$this->RegisterVariableBoolean("e2landhcp", "DHCP", "", 71);
			$this->DisableAction("e2landhcp");
			$this->RegisterVariableString("e2lanip", "IP", "", 72);
			$this->DisableAction("e2lanip");
			$this->RegisterVariableString("e2lanmask", "Mask", "", 73);
			$this->DisableAction("e2lanmask");
			$this->RegisterVariableString("e2langw", "Gateway", "", 74);
			$this->DisableAction("e2langw");
		}

		If ($this->ReadPropertyBoolean("HDD_Data") == true) {
			$this->RegisterVariableString("e2hddinfo_model", "HDD Model", "", 80);
			$this->DisableAction("e2hddinfo_model");
			$this->RegisterVariableInteger("e2hddinfo_capacity", "HDD Capacity", "gigabyte.GB", 90);
			$this->DisableAction("e2hddinfo_capacity");
			$this->RegisterVariableInteger("e2hddinfo_free", "HDD Free", "gigabyte.GB", 95);
			$this->DisableAction("e2hddinfo_free");
		}
		
		$this->RegisterVariableBoolean("powerstate", "Powerstate", "~Switch", 100);
		$this->EnableAction("powerstate");
		
		$this->RegisterVariableString("e2servicename", "Service Name", "", 110);
		$this->DisableAction("e2servicename");
		
		If ($this->ReadPropertyBoolean("EPGnow_Data") == true) {
			$this->RegisterVariableString("e2eventtitle", "Event Title", "", 120);
			$this->DisableAction("e2eventtitle");
			$this->RegisterVariableString("e2eventdescription", "Event Description", "", 125);
			$this->DisableAction("e2eventdescription");
			$this->RegisterVariableString("e2eventdescriptionextended", "Event Description Extended", "", 130);
			$this->DisableAction("e2eventdescriptionextended");
			$this->RegisterVariableInteger("e2eventstart", "Event Start", "~UnixTimestampTime", 140);
			$this->DisableAction("e2eventstart");
			$this->RegisterVariableInteger("e2eventend", "Event End", "~UnixTimestampTime", 150);
			$this->DisableAction("e2eventend");
			$this->RegisterVariableInteger("e2eventduration", "Event Duration", "time.min", 160);		
			$this->DisableAction("e2eventduration");
			$this->RegisterVariableInteger("e2eventpast", "Event Past", "time.min", 170);
			$this->DisableAction("e2eventpast");
			$this->RegisterVariableInteger("e2eventleft", "Event Left", "time.min", 180);
			$this->DisableAction("e2eventleft");
			$this->RegisterVariableInteger("e2eventprogress", "Event Progress", "~Intensity.100", 190);
			$this->DisableAction("e2eventprogress");
		}
		
		If ($this->ReadPropertyBoolean("EPGnext_Data") == true) {
			$this->RegisterVariableString("e2nexteventtitle", "Next Event Title", "", 200);
			$this->DisableAction("e2nexteventtitle");
			$this->RegisterVariableString("e2nexteventdescription", "Next Event Description", "", 210);
			$this->DisableAction("e2nexteventdescription");
			$this->RegisterVariableString("e2nexteventdescriptionextended", "Next Event Description Extended", "", 220);
			$this->DisableAction("e2nexteventdescriptionextended");
			$this->RegisterVariableInteger("e2nexteventstart", "Next Event Start", "~UnixTimestampTime", 230);
			$this->DisableAction("e2nexteventstart");
			$this->RegisterVariableInteger("e2nexteventend", "Next Event End", "~UnixTimestampTime", 240);
			$this->DisableAction("e2nexteventend");
			$this->RegisterVariableInteger("e2nexteventduration", "Next Event Duration", "time.min", 250);
			$this->DisableAction("e2nexteventduration");		
		}
		
		If (($this->ReadPropertyBoolean("EPGnow_Data") == true) OR ($this->ReadPropertyBoolean("EPGnext_Data") == true)) {
			$this->RegisterVariableString("e2epgHTML", "EPG", "~HTMLBox", 257);
			$this->DisableAction("e2epgHTML");
		}
		
		If ($this->ReadPropertyBoolean("Movielist_Data") == true) {
			$this->RegisterVariableString("e2movielist", "Aufzeichnungen", "~HTMLBox", 260);
			$this->DisableAction("e2movielist");
		}
		
		If ($this->ReadPropertyBoolean("Signal_Data") == true) {
			$this->RegisterVariableInteger("e2snrdb", "Signal-to-Noise Ratio (dB)", "snr.db", 300);
			$this->DisableAction("e2snrdb");
			$this->RegisterVariableInteger("e2snr", "Signal-to-Noise Ratio", "~Intensity.100", 310);
			$this->DisableAction("e2snr");
			$this->RegisterVariableInteger("e2ber", "Bit error rate", "", 320);
			$this->DisableAction("e2ber");
			$this->RegisterVariableInteger("e2agc", "Automatic Gain Control", "~Intensity.100", 330);
			$this->DisableAction("e2agc");
		}
		
		//$this->RegisterVariableString("e2stream", "Stream-Video", "~HTMLBox", 900);
		//$this->DisableAction("e2stream");
		
		If ($this->ReadPropertyBoolean("RC_Data") == true) {
			$this->RegisterVariableBoolean("rc_power", "Power", "~Switch", 500);
			$this->EnableAction("rc_power");
			$this->RegisterVariableBoolean("rc_mute", "Mute", "~Switch", 505);
			$this->EnableAction("rc_mute");
			$this->RegisterVariableBoolean("rc_vol_up", "Volume up", "~Switch", 510);
			$this->EnableAction("rc_vol_up");
			$this->RegisterVariableBoolean("rc_vol_down", "Volume down", "~Switch", 520);
			$this->EnableAction("rc_vol_down");
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
			$this->RegisterVariableBoolean("rc_previous", "Previous", "~Switch", 640);
			$this->EnableAction("rc_previous");
			$this->RegisterVariableBoolean("rc_next", "Next", "~Switch", 650);
			$this->EnableAction("rc_next");
			$this->RegisterVariableBoolean("rc_bouquet_up", "Bouquet up", "~Switch", 660);
			$this->EnableAction("rc_bouquet_up");
			$this->RegisterVariableBoolean("rc_bouquet_down", "Bouquet down", "~Switch", 670);
			$this->EnableAction("rc_bouquet_down");
			$this->RegisterVariableBoolean("rc_red", "Red", "~Switch", 680);
			$this->EnableAction("rc_red");
			$this->RegisterVariableBoolean("rc_green", "Green", "~Switch", 690);
			$this->EnableAction("rc_green");
			$this->RegisterVariableBoolean("rc_yellow", "Yellow", "~Switch", 700);
			$this->EnableAction("rc_yellow");
			$this->RegisterVariableBoolean("rc_blue", "Blue", "~Switch", 710);
			$this->EnableAction("rc_blue");
			$this->RegisterVariableBoolean("rc_up", "Up", "~Switch", 720);
			$this->EnableAction("rc_up");
			$this->RegisterVariableBoolean("rc_down", "Down", "~Switch", 730);
			$this->EnableAction("rc_down");
			$this->RegisterVariableBoolean("rc_left", "Left", "~Switch", 740);
			$this->EnableAction("rc_left");
			$this->RegisterVariableBoolean("rc_right", "Right", "~Switch", 750);
			$this->EnableAction("rc_right");
			$this->RegisterVariableBoolean("rc_audio", "Audio", "~Switch", 760);
			$this->EnableAction("rc_audio");
			$this->RegisterVariableBoolean("rc_video", "Video", "~Switch", 770);
			$this->EnableAction("rc_video");
			$this->RegisterVariableBoolean("rc_lame", "Lame", "~Switch", 780);
			$this->EnableAction("rc_lame");
			$this->RegisterVariableBoolean("rc_info", "Info", "~Switch", 790);
			$this->EnableAction("rc_info");
			$this->RegisterVariableBoolean("rc_menu", "Menu", "~Switch", 800);
			$this->EnableAction("rc_menu");
			$this->RegisterVariableBoolean("rc_ok", "OK", "~Switch", 810);
			$this->EnableAction("rc_ok");
			$this->RegisterVariableBoolean("rc_menu", "Menu", "~Switch", 800);
			$this->EnableAction("rc_menu");
			$this->RegisterVariableBoolean("rc_ok", "OK", "~Switch", 810);
			$this->EnableAction("rc_ok");
			$this->RegisterVariableBoolean("rc_tv", "TV", "~Switch", 820);
			$this->EnableAction("rc_tv");
			$this->RegisterVariableBoolean("rc_radio", "Radio", "~Switch", 830);
			$this->EnableAction("rc_radio");
			$this->RegisterVariableBoolean("rc_help", "Help", "~Switch", 840);
			$this->EnableAction("rc_help");
			$this->RegisterVariableBoolean("rc_text", "Text", "~Switch", 850);
			$this->EnableAction("rc_text");
			$this->RegisterVariableBoolean("rc_exit", "Exit", "~Switch", 860);
			$this->EnableAction("rc_exit");
			$this->RegisterVariableBoolean("rc_rewind", "Rewind", "~Switch", 870);
			$this->EnableAction("rc_rewind");
			$this->RegisterVariableBoolean("rc_play", "Play", "~Switch", 880);
			$this->EnableAction("rc_play");
			$this->RegisterVariableBoolean("rc_pause", "Pause", "~Switch", 890);
			$this->EnableAction("rc_pause");
			$this->RegisterVariableBoolean("rc_forward", "Forward", "~Switch", 900);
			$this->EnableAction("rc_forward");
			$this->RegisterVariableBoolean("rc_stop", "Stop", "~Switch", 910);
			$this->EnableAction("rc_stop");
			$this->RegisterVariableBoolean("rc_record", "Record", "~Switch", 920);
			$this->EnableAction("rc_record");

		}
		
		If ($this->ReadPropertyBoolean("EPGlist_Data") == true) {
			$this->RegisterVariableString("e2epglistHTML", "EPG Liste", "~HTMLBox", 950);
			$this->DisableAction("e2epglistHTML");
		}
		
		If ($this->ReadPropertyBoolean("EPGlistSRef_Data") == true) {
			$this->RegisterVariableString("e2epglistSRefHTML", "EPG Liste Sender", "~HTMLBox", 950);
			$this->DisableAction("e2epglistSRefHTML");
		}
		
		If ($this->ReadPropertyInteger("PiconSource") == 0) {
			$this->Get_Picons();
		}
		elseif ($this->ReadPropertyInteger("PiconSource") == 1) {
			$this->Get_Picons_Enigma();
		}
		
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$this->Get_BasicData();
			$this->SetTimerInterval("DataUpdate", ($this->ReadPropertyInteger("DataUpdate") * 1000));
			$this->SetTimerInterval("EPGUpdate", ($this->ReadPropertyInteger("EPGUpdate") * 1000));
			$this->SetTimerInterval("ScreenshotUpdate", ($this->ReadPropertyInteger("ScreenshotUpdate") * 1000));
			$this->Get_Powerstate();
			$this->GetScreenshot();
			$this->SetStatus(102);
		}
		else {
			$this->SetStatus(104);
		}
        }
	
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
			case "rc_mute":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 113 Key "mute"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=113"));
				}
				break;
			case "rc_vol_up":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 115 Key "volume up"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=115"));
				}
				break;
			case "rc_vol_down":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 114 Key "volume down"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=114"));
				}
				break;
			case "rc_power":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
					// 116 Key "Power""
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=116"));
					$this->Get_EPGUpdate();
				}
				break;
			case "rc_1":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 2   Key "1"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=2"));
				}
				break;
			case "rc_2":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 3   Key "2"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=3"));
				}
				break;
			case "rc_3":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 4   Key "3"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=4"));
				}
				break;
			case "rc_4":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 5   Key "4"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=5"));
				}
				break;
			case "rc_5":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 6   Key "5"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=6"));
				}
				break;
			case "rc_6":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 7   Key "6"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=7"));
				}
				break;
			case "rc_7":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 8   Key "7"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=8"));
				}
				break;
			case "rc_8":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 9   Key "8"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=9"));
				}
				break;
			case "rc_9":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 10  Key "9"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=10"));
				}
				break;
			case "rc_0":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 11  Key "0"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=11"));
				}
				break;
			case "rc_previous":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 412 Key "previous"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=412"));
				}
				break;
			case "rc_next":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 407 Key "next"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=407"));
				}
				break;
			case "rc_bouquet_up":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 402 Key "bouquet up"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=402"));
				}
				break;
			case "rc_bouquet_down":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 403 Key "bouquet down"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=403"));
				}
				break;
			case "rc_red":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 398 Key "red"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=398"));
				}
				break;
			case "rc_green":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 399 Key "green"	
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=399"));
				}
				break;
			case "rc_yellow":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 400 Key "yellow"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=400"));
				}
				break;
			case "rc_blue":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 401 Key "blue"	
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=401"));
				}
				break;
			case "rc_up":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 103 Key "up"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=103"));
				}
				break;
			case "rc_down":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 108 Key "down"	
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=108"));
				}
				break;
			case "rc_left":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 105 Key "left"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=105"));
				}
				break;
			case "rc_right":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 106 Key "right"	
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=106"));
				}
				break;
			case "rc_audio":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 392 Key "audio"
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=392"));
				}
				break;
			case "rc_video":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 393 Key "video"		
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=393"));
				}
				break;
			case "rc_lame":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 174 Key "lame"		
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=174"));
				}
				break;
			case "rc_info":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 358 Key "info"		
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=358"));
				}
				break;
			case "rc_menu":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 139 Key "menu"			
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=139"));
				}
				break;
			case "rc_ok":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 352 Key "OK"			
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=352"));
				}
				break;
			case "rc_tv":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 377 Key "tv"			
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=377"));
				}
				break;
			case "rc_radio":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 385 Key "radio"			
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=385"));
				}
				break;
			case "rc_text":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 388 Key "text"			
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=388"));
				}
				break;
			case "rc_help":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 138 Key "help"			
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=138"));
				}
				break;
			case "rc_exit":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 1 Key "exit"			
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=1"));
				}
				break;
			case "rc_rewind":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 168 Key "rewind"		
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=168"));
				}
				break;
			case "rc_play":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 207 Key "play"		
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=207"));
				}
				break;
			case "rc_pause":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 119 Key "pause"		
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=119"));
				}
				break;
			case "rc_forward":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 208 Key "forward"		
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=208"));
				}
				break;
			case "rc_stop":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 128 Key "stop" 		
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=128"));
				}
				break;
			case "rc_record":
			    	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// 167 Key "record"		
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=167"));
				}
				break;
			default:
			    throw new Exception("Invalid Ident");
	    	}
	}
	
	// Beginn der Funktionen
	public function Get_DataUpdate()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
			$this->SetBuffer("FirstUpdate", "false");
			//IPS_LogMessage("IPS2Enigma","TV-Daten ermitteln");
			// das aktuelle Programm
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/subservices"));
       			SetValueString($this->GetIDForIdent("e2servicename"), (string)$xmlResult->e2service->e2servicename);
			$e2servicereference = (string)$xmlResult->e2service->e2servicereference;
			$e2servicename = (string)$xmlResult->e2service->e2servicename;	
			
			If ($this->ReadPropertyBoolean("Movielist_Data") == true) {
				$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/movielist"));
				$table = '<style type="text/css">';
				$table .= '<link rel="stylesheet" href="./.../webfront.css">';
				$table .= "</style>";
				$table .= '<table class="tg">';
				$table .= "<tr>";
				$table .= '<th class="tg-kv4b">Titel</th>';
				$table .= '<th class="tg-kv4b">Kurzbeschreibung<br></th>';
				$table .= '<th class="tg-kv4b">Langbeschreibung<br></th>';
				$table .= '<th class="tg-kv4b">Quelle</th>';
				$table .= '<th class="tg-kv4b">Länge</th>';
				$table .= '</tr>';
				for ($i = 0; $i <= count($xmlResult) - 1; $i++) {
					$table .= '<tr>';
					$table .= '<td class="tg-611x">'.$xmlResult->e2movie[$i]->e2title.'</td>';
					$table .= '<td class="tg-611x">'.$xmlResult->e2movie[$i]->e2description.'</td>';
					$table .= '<td class="tg-611x">'.$xmlResult->e2movie[$i]->e2descriptionextended.'</td>';
					$table .= '<td class="tg-611x">'.$xmlResult->e2movie[$i]->e2servicename.'</td>';
					$table .= '<td class="tg-611x">'.$xmlResult->e2movie[$i]->e2length.'</td>';
					$table .= '</tr>';
				}
				$table .= '</table>';
				SetValueString($this->GetIDForIdent("e2movielist") , $table);	
			}
			
			If ($this->ReadPropertyBoolean("Signal_Data") == true) {
				// Empfangsstärke ermitteln
				$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/signal?"));
				SetValueInteger($this->GetIDForIdent("e2snrdb"), (int)$xmlResult->e2snrdb);
				SetValueInteger($this->GetIDForIdent("e2snr"), (int)$xmlResult->e2snr);
				SetValueInteger($this->GetIDForIdent("e2ber"), (int)$xmlResult->e2ber);
				SetValueInteger($this->GetIDForIdent("e2agc"), (int)$xmlResult->e2acg);
			}
			If ($this->ReadPropertyBoolean("HDD_Data") == true) {
				// Festplattendaten
				$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/about"));
				If (substr($xmlResult->e2about->e2hddinfo->capacity, -2) == "GB") {
					SetValueInteger($this->GetIDForIdent("e2hddinfo_capacity"), (int)$xmlResult->e2about->e2hddinfo->capacity);
				}
				else {
					SetValueInteger($this->GetIDForIdent("e2hddinfo_capacity"), (int)$xmlResult->e2about->e2hddinfo->capacity * 1000);
				}
				If (substr($xmlResult->e2about->e2hddinfo->free, -2) == "GB") {	
					SetValueInteger($this->GetIDForIdent("e2hddinfo_free"), (int)$xmlResult->e2about->e2hddinfo->free);
				}
				else {
					SetValueInteger($this->GetIDForIdent("e2hddinfo_free"), (int)$xmlResult->e2about->e2hddinfo->free * 1000);
				}
			}
			
			//SetValueString($this->GetIDForIdent("e2stream"), "<video width="320" height="240" controls> <source src="http://".$this->ReadPropertyString("IPAddress")."/web/stream.m3u?ref=".$e2servicereference." type="video/mp4"> </video>");
			//"http://".$this->ReadPropertyString("IPAddress")."/web/stream.m3u?ref=".$e2servicereference
		}
		else {
			if ($this->GetBuffer("FirstUpdate") == "false") {
				SetValueString($this->GetIDForIdent("e2servicename"), "N/A");
				If ($this->ReadPropertyBoolean("EPGnow_Data") == true) {
					SetValueString($this->GetIDForIdent("e2eventtitle"), "N/A");
					SetValueString($this->GetIDForIdent("e2eventdescription"), "N/A");
					SetValueString($this->GetIDForIdent("e2eventdescriptionextended"), "N/A");
					SetValueInteger($this->GetIDForIdent("e2eventstart"), 0);
					SetValueInteger($this->GetIDForIdent("e2eventend"), 0);
					SetValueInteger($this->GetIDForIdent("e2eventduration"), 0);
					SetValueInteger($this->GetIDForIdent("e2eventpast"), 0);
					SetValueInteger($this->GetIDForIdent("e2eventleft"), 0);
					SetValueInteger($this->GetIDForIdent("e2eventprogress"), 0);
					SetValueString($this->GetIDForIdent("e2eventHTML"), "");
				}
				If ($this->ReadPropertyBoolean("EPGnext_Data") == true) {
					SetValueString($this->GetIDForIdent("e2nexteventtitle"), "N/A");
					SetValueString($this->GetIDForIdent("e2nexteventdescription"), "N/A");
					SetValueString($this->GetIDForIdent("e2nexteventdescriptionextended"), "N/A");
					SetValueInteger($this->GetIDForIdent("e2nexteventstart"), 0);
					SetValueInteger($this->GetIDForIdent("e2nexteventend"), 0);
					SetValueInteger($this->GetIDForIdent("e2nexteventduration"), 0);
					SetValueString($this->GetIDForIdent("e2nexteventHTML"), "");
				}

				If ($this->ReadPropertyBoolean("Signal_Data") == true) {
					SetValueInteger($this->GetIDForIdent("e2snrdb"), 0);
					SetValueInteger($this->GetIDForIdent("e2snr"), 0);
					SetValueInteger($this->GetIDForIdent("e2ber"), 0);
					SetValueInteger($this->GetIDForIdent("e2agc"), 0);
				}
				$this->SetBuffer("FirstUpdate", "true");
			}
		}
	}
	
	public function Get_EPGUpdate()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/getservices"));
			$bouquet = (string)$xmlResult->e2service[$this->ReadPropertyInteger("BouquetsNumber")]->e2servicereference;
			
			If ($this->ReadPropertyBoolean("EPGlist_Data") == true) {
				$Servicereference = Array();
				$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/epgnownext?bRef=".urlencode($bouquet)));
				$table = '<style type="text/css">';
				$table .= '<link rel="stylesheet" href="./.../webfront.css">';
				$table .= "</style>";
				$table .= '<table class="tg">';
				$table .= "<tr>";
				$table .= '<th class="tg-kv4b">Sender</th>';
				$table .= '<th class="tg-kv4b">Beginn<br></th>';
				$table .= '<th class="tg-kv4b">Titel</th>';
				$table .= '<th class="tg-kv4b">Kurzbeschreibung<br></th>';
				$table .= '<th class="tg-kv4b">Dauer<br></th>';
				$table .= '<colgroup>'; 
				$table .= '<col width="120">'; 
				$table .= '<col width="100">'; 
				$table .= '</colgroup>';
				$table .= '</tr>';
				for ($i = 0; $i <= count($xmlResult) - 1; $i=$i+2) {
					$Servicereference[$i/2] = (string)$xmlResult->e2event[$i]->e2eventservicereference;
					$table .= '<tr>';
					$table .= '<td rowspan="2" class="tg-611x"><img src='.$this->Get_Filename((string)$xmlResult->e2event[$i]->e2eventservicereference).' alt='.(string)$xmlResult->e2event[$i]->e2eventservicename.' 
						onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/IPS2Enigma?Index='.($i/2).'&Source=EPGlist_Data_A\' })"></td>';
					$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event[$i]->e2eventstart).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event[$i]->e2eventtitle).'</td>';
					$table .= '<td class="tg-611x" onclick="window.xhrGet=function xhrGet(o) {var HTTP = new XMLHttpRequest();HTTP.open(\'GET\',o.url,true);HTTP.send();};window.xhrGet({ url: \'hook/IPS2Enigma?Index='.($i/2).'&Source=EPGlist_Data_D\' })">'.utf8_decode($xmlResult->e2event[$i]->e2eventdescription).'</td>';			
					$table .= '<td class="tg-611x">'.round((int)$xmlResult->e2event[$i]->e2eventduration / 60).' min'.'</td>';
					$table .= '</tr>';
					$table .= '<tr>';
					$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event[$i+1]->e2eventstart).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event[$i+1]->e2eventtitle).'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event[$i+1]->e2eventdescription).'</td>';
					$table .= '<td class="tg-611x">'.round((int)$xmlResult->e2event[$i+1]->e2eventduration / 60).' min'.'</td>';
					$table .= '</tr>';
				}
				$table .= '</table>';
				SetValueString($this->GetIDForIdent("e2epglistHTML"), $table);
				$this->SetBuffer("Servicereference", serialize($Servicereference));
			}
			
			If (GetValueBoolean($this->GetIDForIdent("powerstate")) == true) {
				$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/subservices"));
				$e2servicereference = (string)$xmlResult->e2service->e2servicereference;
				$e2servicename = (string)$xmlResult->e2service->e2servicename;
				
				If (($this->ReadPropertyBoolean("EPGlistSRef_Data") == true) ) {
					//$sender = urlencode($sender);
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/epgservice?sRef=".$e2servicereference));
					$ValueCount = count($xmlResult) - 2;
					$table = '<style type="text/css">';
					$table .= '<link rel="stylesheet" href="./.../webfront.css">';
					$table .= "</style>";
					$table .= '<table class="tg">';
					$table .= "<tr>";
					$table .= '<th class="tg-kv4b">Sender</th>';
					$table .= '<th class="tg-kv4b">Beginn<br></th>';
					$table .= '<th class="tg-kv4b">Titel</th>';
					$table .= '<th class="tg-kv4b">Kurzbeschreibung<br></th>';
					$table .= '<th class="tg-kv4b">Dauer<br></th>';
					$table .= '<colgroup>'; 
					$table .= '<col width="120">'; 
					$table .= '<col width="100">'; 
					$table .= '</colgroup>';
					$table .= '</tr>';
					$table .= '<tr>';
					$table .= '<td class="tg-611x"><img src='.$this->Get_Filename((string)$xmlResult->e2event[0]->e2eventservicereference).' alt='.(string)$xmlResult->e2event[0]->e2eventservicename.'></td>';
					$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event[0]->e2eventstart).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event[0]->e2eventtitle).'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event[0]->e2eventdescription).'</td>';			
					$table .= '<td class="tg-611x">'.round((int)$xmlResult->e2event[0]->e2eventduration / 60).' min'.'</td>';
					$table .= '</tr>';
					for ($i = 1; $i <= Min(count($xmlResult) - 1, 15); $i++) {
						$table .= '<tr>';
						$table .= '<td class="tg-611x"></td>';
						//$table .= '<td rowspan='.$ValueCount.' class="tg-611x"><img src='.$this->Get_Filename((string)$xmlResult->e2event[$i]->e2eventservicereference).' alt='.(string)$xmlResult->e2event[$i]->e2eventservicename.'></td>';
						$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event[$i]->e2eventstart).' Uhr'.'</td>';
						$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event[$i]->e2eventtitle).'</td>';
						$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event[$i]->e2eventdescription).'</td>';			
						$table .= '<td class="tg-611x">'.round((int)$xmlResult->e2event[$i]->e2eventduration / 60).' min'.'</td>';
						$table .= '</tr>';				
					}
					$table .= '</table>';
					SetValueString($this->GetIDForIdent("e2epglistSRefHTML"), $table);
				}
			
				If (($this->ReadPropertyBoolean("EPGnow_Data") == true) AND ($this->ReadPropertyBoolean("EPGnext_Data") == false) AND (substr($e2servicereference, 0, 20) <> "1:0:0:0:0:0:0:0:0:0:")) {
					// das aktuelle Ereignis
					$xmlResult =  new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/epgservicenow?sRef=".$e2servicereference));
					SetValueString($this->GetIDForIdent("e2eventtitle"), (string)utf8_decode($xmlResult->e2event->e2eventtitle));
					SetValueString($this->GetIDForIdent("e2eventdescription"), (string)utf8_decode($xmlResult->e2event->e2eventdescription));
					SetValueString($this->GetIDForIdent("e2eventdescriptionextended"), (string)utf8_decode($xmlResult->e2event->e2eventdescriptionextended));
					SetValueInteger($this->GetIDForIdent("e2eventstart"), (int)$xmlResult->e2event->e2eventstart);
					SetValueInteger($this->GetIDForIdent("e2eventend"), (int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration);
					SetValueInteger($this->GetIDForIdent("e2eventduration"), round((int)$xmlResult->e2event->e2eventduration / 60) );
					SetValueInteger($this->GetIDForIdent("e2eventpast"), round( (int)time() - (int)$xmlResult->e2event->e2eventstart) / 60 );
					SetValueInteger($this->GetIDForIdent("e2eventleft"), round(((int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration - (int)time()) / 60 ));
					SetValueInteger($this->GetIDForIdent("e2eventprogress"), GetValueInteger($this->GetIDForIdent("e2eventpast")) / GetValueInteger($this->GetIDForIdent("e2eventduration")) * 100);
					$table = '<style type="text/css">';
					$table .= '<link rel="stylesheet" href="./.../webfront.css">';
					$table .= "</style>";
					$table .= '<table class="tg">';
					$table .= "<tr>";
					$table .= '<th class="tg-kv4b">Sender</th>';
					$table .= '<th class="tg-kv4b">Titel</th>';
					$table .= '<th class="tg-kv4b">Kurzbeschreibung<br></th>';
					$table .= '<th class="tg-kv4b">Langbeschreibung<br></th>';
					$table .= '<th class="tg-kv4b">Beginn<br></th>';
					$table .= '<th class="tg-kv4b">Ende<br></th>';
					$table .= '<th class="tg-kv4b">Dauer<br></th>';
					$table .= '</tr>';
					$table .= '<tr>';
					$table .= '<td class="tg-611x"><img src='.$this->Get_Filename($e2servicereference).' alt='.$e2servicename.'></td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventtitle).'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventdescription).'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventdescriptionextended).'</td>';
					$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event->e2eventstart).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.round((int)$xmlResult->e2event->e2eventduration / 60).' min'.'</td>';
					$table .= '</tr>';
					$table .= '</table>';
					SetValueString($this->GetIDForIdent("e2epgHTML"), $table);
				}

				If (($this->ReadPropertyBoolean("EPGnow_Data") == false) AND ($this->ReadPropertyBoolean("EPGnext_Data") == true) AND (substr($e2servicereference, 0, 20) <> "1:0:0:0:0:0:0:0:0:0:")) {
					// das folgende Ereignis
					$xmlResult =  new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/epgservicenext?sRef=".$e2servicereference));
					SetValueString($this->GetIDForIdent("e2nexteventtitle"), (string)utf8_decode($xmlResult->e2event->e2eventtitle));
					SetValueString($this->GetIDForIdent("e2nexteventdescription"), (string)utf8_decode($xmlResult->e2event->e2eventdescription));
					SetValueString($this->GetIDForIdent("e2nexteventdescriptionextended"), (string)utf8_decode($xmlResult->e2event->e2eventdescriptionextended));
					SetValueInteger($this->GetIDForIdent("e2nexteventstart"), (int)$xmlResult->e2event->e2eventstart);
					SetValueInteger($this->GetIDForIdent("e2nexteventend"), (int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration);
					SetValueInteger($this->GetIDForIdent("e2nexteventduration"), round((int)$xmlResult->e2event->e2eventduration / 60) );
					$table = '<style type="text/css">';
					$table .= '<link rel="stylesheet" href="./.../webfront.css">';
					$table .= "</style>";
					$table .= '<table class="tg">';
					$table .= "<tr>";
					$table .= '<th class="tg-kv4b">Sender</th>';
					$table .= '<th class="tg-kv4b">Titel</th>';
					$table .= '<th class="tg-kv4b">Kurzbeschreibung<br></th>';
					$table .= '<th class="tg-kv4b">Langbeschreibung<br></th>';
					$table .= '<th class="tg-kv4b">Beginn<br></th>';
					$table .= '<th class="tg-kv4b">Ende<br></th>';
					$table .= '<th class="tg-kv4b">Dauer<br></th>';
					$table .= '</tr>';
					$table .= '<tr>';
					$table .= '<td class="tg-611x"><img src='.$this->Get_Filename($e2servicereference).' alt='.$e2servicename.'></td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventtitle).'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventdescription).'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventdescriptionextended).'</td>';
					$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event->e2eventstart).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.round((int)$xmlResult->e2event->e2eventduration / 60).' min'.'</td>';
					$table .= '</tr>';
					$table .= '</table>';
					SetValueString($this->GetIDForIdent("e2epgHTML"), $table);
				}

				If (($this->ReadPropertyBoolean("EPGnow_Data") == true) AND ($this->ReadPropertyBoolean("EPGnext_Data") == true) AND (substr($e2servicereference, 0, 20) <> "1:0:0:0:0:0:0:0:0:0:")) {
					// das aktuelle Ereignis
					$xmlResult =  new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/epgservicenow?sRef=".$e2servicereference));
					SetValueString($this->GetIDForIdent("e2eventtitle"), (string)utf8_decode($xmlResult->e2event->e2eventtitle));
					SetValueString($this->GetIDForIdent("e2eventdescription"), (string)utf8_decode($xmlResult->e2event->e2eventdescription));
					SetValueString($this->GetIDForIdent("e2eventdescriptionextended"), (string)utf8_decode($xmlResult->e2event->e2eventdescriptionextended));
					SetValueInteger($this->GetIDForIdent("e2eventstart"), (int)$xmlResult->e2event->e2eventstart);
					SetValueInteger($this->GetIDForIdent("e2eventend"), (int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration);
					SetValueInteger($this->GetIDForIdent("e2eventduration"), round((int)$xmlResult->e2event->e2eventduration / 60) );
					SetValueInteger($this->GetIDForIdent("e2eventpast"), round( (int)time() - (int)$xmlResult->e2event->e2eventstart) / 60 );
					SetValueInteger($this->GetIDForIdent("e2eventleft"), round(((int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration - (int)time()) / 60 ));
					SetValueInteger($this->GetIDForIdent("e2eventprogress"), GetValueInteger($this->GetIDForIdent("e2eventpast")) / GetValueInteger($this->GetIDForIdent("e2eventduration")) * 100);
					// das folgende Ereignis
					$xmlResult_2 =  new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/epgservicenext?sRef=".$e2servicereference));
					SetValueString($this->GetIDForIdent("e2nexteventtitle"), (string)utf8_decode($xmlResult_2->e2event->e2eventtitle));
					SetValueString($this->GetIDForIdent("e2nexteventdescription"), (string)utf8_decode($xmlResult_2->e2event->e2eventdescription));
					SetValueString($this->GetIDForIdent("e2nexteventdescriptionextended"), (string)utf8_decode($xmlResult_2->e2event->e2eventdescriptionextended));
					SetValueInteger($this->GetIDForIdent("e2nexteventstart"), (int)$xmlResult_2->e2event->e2eventstart);
					SetValueInteger($this->GetIDForIdent("e2nexteventend"), (int)$xmlResult_2->e2event->e2eventstart + (int)$xmlResult_2->e2event->e2eventduration);
					SetValueInteger($this->GetIDForIdent("e2nexteventduration"), round((int)$xmlResult_2->e2event->e2eventduration / 60) );

					$table = '<style type="text/css">';
					$table .= '<link rel="stylesheet" href="./.../webfront.css">';
					$table .= "</style>";
					$table .= '<table class="tg">';
					$table .= "<tr>";
					$table .= '<th class="tg-kv4b">Sender</th>';
					$table .= '<th class="tg-kv4b">Beginn<br></th>';
					$table .= '<th class="tg-kv4b">Titel</th>';
					$table .= '<th class="tg-kv4b">Kurzbeschreibung<br></th>';
					$table .= '<th class="tg-kv4b">Langbeschreibung<br></th>';
					//$table .= '<th class="tg-kv4b">Ende<br></th>';
					$table .= '<th class="tg-kv4b">Dauer<br></th>';
					$table .= '<colgroup>'; 
					$table .= '<col width="120">'; 
					$table .= '<col width="100">'; 
					$table .= '</colgroup>';
					$table .= '</tr>';
					$table .= '<tr>';
					$table .= '<td class="tg-611x"><img src='.$this->Get_Filename($e2servicereference).' alt='.$e2servicename.'></td>';
					$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event->e2eventstart).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventtitle).'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventdescription).'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventdescriptionextended).'</td>';
					//$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.round((int)$xmlResult->e2event->e2eventduration / 60).' min'.'</td>';
					$table .= '</tr>';
					$table .= '<tr>';
					$table .= '<td class="tg-611x"></td>';
					$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult_2->e2event->e2eventstart).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult_2->e2event->e2eventtitle).'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult_2->e2event->e2eventdescription).'</td>';
					$table .= '<td class="tg-611x">'.utf8_decode($xmlResult_2->e2event->e2eventdescriptionextended).'</td>';
					//$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult_2->e2event->e2eventstart + (int)$xmlResult_2->e2event->e2eventduration).' Uhr'.'</td>';
					$table .= '<td class="tg-611x">'.round((int)$xmlResult_2->e2event->e2eventduration / 60).' min'.'</td>';
					$table .= '</tr>';
					$table .= '</table>';
					SetValueString($this->GetIDForIdent("e2epgHTML"), $table);
				}
			}
			else {
				If (($this->ReadPropertyBoolean("EPGnow_Data") == true) OR ($this->ReadPropertyBoolean("EPGnext_Data") == true)) {
					SetValueString($this->GetIDForIdent("e2epgHTML"), "nicht verfügbar");
				}
				If (($this->ReadPropertyBoolean("EPGlistSRef_Data") == true) ) {
					SetValueString($this->GetIDForIdent("e2epglistSRefHTML"), "nicht verfügbar");
				}
			}
		}
	}
	
	// Ermittlung der Basisdaten
	private function Get_BasicData()
	{
		$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/deviceinfo"));
		If ($this->ReadPropertyBoolean("Enigma2_Data") == true) {
			SetValueString($this->GetIDForIdent("e2oeversion"), (string)$xmlResult->e2oeversion);
			SetValueString($this->GetIDForIdent("e2enigmaversion"), (string)$xmlResult->e2enigmaversion);
			SetValueString($this->GetIDForIdent("e2distroversion"), (string)$xmlResult->e2distroversion);
			SetValueString($this->GetIDForIdent("e2imageversion"), (string)$xmlResult->e2imageversion);
			SetValueString($this->GetIDForIdent("e2webifversion"), (string)$xmlResult->e2webifversion);
		}
		SetValueString($this->GetIDForIdent("e2devicename"), (string)$xmlResult->e2devicename);
		$table = '<style type="text/css">';
		$table .= '<link rel="stylesheet" href="./.../webfront.css">';
		$table .= "</style>";
		$table .= '<table class="tg">';
		$table .= "<tr>";
		$table .= '<th class="tg-kv4b">Name</th>';
		$table .= '<th class="tg-kv4b">Typ<br></th>';
		$table .= '</tr>';
		for ($i = 0; $i <= count($xmlResult->e2frontends->e2frontend) - 1; $i++) {
			$table .= '<tr>';
			$table .= '<td class="tg-611x">'.$xmlResult->e2frontends->e2frontend[$i]->e2name.'</td>';
			$table .= '<td class="tg-611x">'.$xmlResult->e2frontends->e2frontend[$i]->e2model.'</td>';
			$table .= '</tr>';
		}
		$table .= '</table>';
		SetValueString($this->GetIDForIdent("e2tunerinfo"), $table);
		
		If ($this->ReadPropertyBoolean("Network_Data") == true) {
			SetValueString($this->GetIDForIdent("e2lanmac"), (string)$xmlResult->e2network->e2lanmac);
			SetValueBoolean($this->GetIDForIdent("e2landhcp"), (bool)$xmlResult->e2network->e2landhcp);
			SetValueString($this->GetIDForIdent("e2lanip"), (string)$xmlResult->e2network->e2lanip);
			SetValueString($this->GetIDForIdent("e2lanmask"), (string)$xmlResult->e2network->e2lanmask);
			SetValueString($this->GetIDForIdent("e2langw"), (string)$xmlResult->e2network->e2langw);
		}
		
		If ($this->ReadPropertyBoolean("HDD_Data") == true) {
			SetValueString($this->GetIDForIdent("e2hddinfo_model"), (string)$xmlResult->e2hdds->e2hdd->e2model);
		}
	}
	
	private function Get_Powerstate()
	{
		$result = false;
		//$xmlResult = simplexml_load_file("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate");
		$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate"));
		//$wert = $xml->e2instandby;

		If(strpos((string)$xmlResult->e2instandby, "false")!== false) {
			// Bei "false" ist die Box eingeschaltet
			SetValueBoolean($this->GetIDForIdent("powerstate"), true);
			$result = true;
		}
		else {
			SetValueBoolean($this->GetIDForIdent("powerstate"), false);
			$result = false;
		}
	return $result;
	}

	public function ToggleStandby()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=0"));
		}
	}
	/*
	0 = Toogle Standby
	1 = Deepstandby
	2 = Reboot
	3 = Restart Enigma2
	4 = Wakeup from Standby
	5 = Standby
    	*/

	public function DeepStandby()
	{
	      If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=1"));
	      }
	}
	
	public function Standby()
	{
	       If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$xmlResult = new SimpleXMLElement(file_get_contents("http:///".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=5"));
	       }
	}			       
	
	public function WakeUpStandby()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=4"));
		}
	}
				       
	public function Reboot()
	{
	   	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) { 
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=2"));
		}
	}
	
	public function RestartEnigma()
	{
	      	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=3"));
		}
	}		       

	public function GetCurrentServiceName()
	{
		$result = "";
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
		       $xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/subservices"));
		       $result = (string)$xmlResult->e2service[0]->e2servicename;
		}
	return $result;
	}

	public function GetCurrentServiceReference()
	{
		$result = "";
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
	      		$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/subservices"));
	      		$result =  (string)$xmlResult->e2service[0]->e2servicereference;
		}
	return $result;
	}
    	
	public function WriteMessage(string $message, int $time)
	{
	   	$result = false;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
		       $message = urlencode($message);
		       $xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/message?text=".$message."&type=2&timeout=".$time));
		       if ($xmlResult->e2state == "True") {
		       		$result = true;
			}
		}
	return $result;
	}   
	
	public function WriteInfoMessage(string $message,int $time)
	{
	   	$result = false;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
		       $message = urlencode($message);
		       $xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/message?text=$message&type=1&timeout=$time"));
		       if ($xmlResult->e2state == "True") {
		       		$result = true;
			}
		}
	return $result;
	}  
	
	public function WriteAttentionMessage(string $message,int $time)
	{
	   	$result = false;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
		       $message = urlencode($message);
		       $xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/message?text=$message&type=3&timeout=$time"));
		       if ($xmlResult->e2state == "True") {
		       		$result = true;
			}
		}
	return $result;
	}
	
	public function Zap(string $servicereference)
	{
	   	$result = false;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$servicereference = urlencode($servicereference);
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/zap?sRef=".$servicereference));
		}
	}    
	    
	public function MoviePlay(string $servicereference)
	{
	   	$result = false;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$servicereference = urlencode($servicereference);
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/zap?sRef=".$servicereference));
		}
	}    
	
	public function ToggleMute()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			// 113 Key "mute"
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=113"));
		}
	}    
	
	public function VolUp()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			// 115 Key "volume up"
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=115"));
		}
	}        
	
	public function VolDown()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			// 114 Key "volume down"
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/remotecontrol?command=114"));
		}
	}          
	    
	public function GetScreenshot()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$Content = file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/grab?format=jpg&r=".$this->ReadPropertyInteger("Screenshot"));
			IPS_SetMediaContent($this->GetIDForIdent("Screenshot"), base64_encode($Content));  //Bild Base64 codieren und ablegen
			IPS_SendMediaEvent($this->GetIDForIdent("Screenshot")); //aktualisieren
		}
	} 
	
	private function RegisterMediaObject($Name, $Typ, $Parent, $Position, $Cached, $Filename)
	{
		if (!IPS_MediaExists($this->GetIDForIdent($Name))) {
			 // Image im MedienPool anlegen
			$MediaID = IPS_CreateMedia($Typ); 
			// Medienobjekt einsortieren unter Kategorie $catid
			IPS_SetParent($MediaID, $Parent);
			IPS_SetIdent ($MediaID, $Name);
			IPS_SetName($MediaID, $Name);
			IPS_SetPosition($MediaID, $Position);
                    	IPS_SetMediaCached($MediaID, $Cached);
			$ImageFile = IPS_GetKernelDir()."media".DIRECTORY_SEPARATOR.$Filename;  // Image-Datei
			IPS_SetMediaFile($MediaID, $ImageFile, false);    // Image im MedienPool mit Image-Datei verbinden
		}  
	}     
	    
	private function ConnectionTest()
	{
	      $result = false;
	      If (Sys_Ping($this->ReadPropertyString("IPAddress"), 2000)) {
			//IPS_LogMessage("IPS2Enigma Netzanbindung","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert");
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 80, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("IPS2Enigma Netzanbindung","Port ist geschlossen!");				
	   			}
	   			else {
	   				fclose($status);
					//IPS_LogMessage("IPS2Enigma Netzanbindung","Port ist geöffnet");
					$result = true;
					$this->SetStatus(102);
	   			}
		}
		else {
			IPS_LogMessage("IPS2Enigma","IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			$this->SetStatus(104);
		}
	return $result;
	}
	    
	private function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize)
	{
	        if (!IPS_VariableProfileExists($Name))
	        {
	            IPS_CreateVariableProfile($Name, 1);
	        }
	        else
	        {
	            $profile = IPS_GetVariableProfile($Name);
	            if ($profile['ProfileType'] != 1)
	                throw new Exception("Variable profile type does not match for profile " . $Name);
	        }
	        IPS_SetVariableProfileIcon($Name, $Icon);
	        IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
	        IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
	}
	
	public function GetServiceInformation()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/getservices"));
			If (count($xmlResult->e2service) == 0) {
				$Result = "Es wurde nur kein Bouquet gefunden, bitte auf dem Receiver mindestens eines einrichten";
			}
			elseif (count($xmlResult->e2service) == 1) {
				$Result = "Es wurde nur ein Bouquet gefunden, die Einstellung muss daher 0 sein. (Aktuell: ".$this->ReadPropertyInteger("BouquetsNumber").")"; 
			}
			elseif (count($xmlResult->e2service) > 1) {
				$Result = "Es wurde folgende Bouquets gefunden:".chr(13);
				for ($i = 1; $i <= count($xmlResult->e2service) - 1; $i++) {
					$Result .= "Auswahl: ".$i." Bouquet: ".$xmlResult->e2service[$i]->e2servicename.chr(13);
				}
				$Result .= "Bitte die  Auswahl in das Feld Bouquet-Nummer eintragen. (Aktuell: ".$this->ReadPropertyInteger("BouquetsNumber").")";
			}
		}
	return $Result;
	}
	    
	private function RegisterHook($WebHook) 
	{ 
		$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}"); 
		if(sizeof($ids) > 0) { 
			$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true); 
			$found = false; 
			foreach($hooks as $index => $hook) { 
				if($hook['Hook'] == $WebHook) { 
					if($hook['TargetID'] == $this->InstanceID) 
						return; 
					$hooks[$index]['TargetID'] = $this->InstanceID; 
					$found = true; 
				} 
			} 
			if(!$found) { 
				$hooks[] = Array("Hook" => $WebHook, "TargetID" => $this->InstanceID); 
			} 
			IPS_SetProperty($ids[0], "Hooks", json_encode($hooks)); 
			IPS_ApplyChanges($ids[0]); 
		} 
	} 
	
	protected function ProcessHookData() 
	{		
		if ((isset($_GET["Source"]) ) AND (isset($_GET["Index"])) ){
			
			$Source = $_GET["Source"];
			$Index = $_GET["Index"];
			switch($Source) {
			case "EPGlist_Data_A":
			    	IPS_LogMessage("IPS2Enigma","WebHookData - Source: ".$Source." Index: ".$Index);
				If (($this->ReadPropertyBoolean("Open") == true) AND ($this->Get_Powerstate() == true)) {
					// Spalte A					
					$Servicereference = Array();
					$Servicereference = unserialize($this->GetBuffer("Servicereference"));
					IPS_LogMessage("IPS2Enigma","WebHookData - Servicereference: ".$Servicereference[$Index]);
					$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/web/zap?sRef".$Servicereference[$Index]));
				}
				break;
			case "EPGlist_Data_D":
			    	IPS_LogMessage("IPS2Enigma","WebHookData - Source: ".$Source." Index: ".$Index);
				break;
			}
			
		}

	}
	    
	private function Get_Filename(string $sRef)
	{
		// aus der Service Referenz den Dateinamen des Picons generieren
		// Doppelpunkte durch Unterstriche ersetzen
		$Filename = str_replace(":", "_", $sRef);
		// das letzte Zeichen entfernen
		$Filename = substr($Filename, 0, -1);
		// .png anhängen
 		If ($this->ReadPropertyInteger("PiconSource") == 0) {
			$Filename = "user".DIRECTORY_SEPARATOR."Picons".DIRECTORY_SEPARATOR.$Filename.".png";
		}
		elseif ($this->ReadPropertyInteger("PiconSource") == 1) {
			$Filename = "user".DIRECTORY_SEPARATOR."Picons_Enigma".DIRECTORY_SEPARATOR.$Filename.".png";
		}
	return $Filename;
	}
	    
	private function SSH_Connect(String $Command)
	{
	        If (($this->ReadPropertyBoolean("Open") == true) ) {
			set_include_path(__DIR__);
			require_once (__DIR__ . '/Net/SSH2.php');
			$ssh = new Net_SSH2($this->ReadPropertyString("IPAddress"));
			$login = @$ssh->login($this->ReadPropertyString("User"), $this->ReadPropertyString("Password"));
			if ($login == false)
			{
			    IPS_LogMessage("IPS2Enigma","SSH-Connect: Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert nicht!");
			    return false;
			}
			$Result = ""; //$ssh->exec($Command);
			$ssh->disconnect();
		}
		else {
			$result = "";
		}
	
        return $Result;
	}
	
	public function Get_Picons_Enigma()
	{
	        If (($this->ReadPropertyBoolean("Open") == true) ) {
			// Prüfen, ob das Verzeichnis schon existiert
			$WebfrontPath = IPS_GetKernelDir()."webfront".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR."Picons_Enigma";
			$SourcePath = "/usr/share/enigma2/picon";
			if (file_exists($WebfrontPath)) {
			    	// Das Verzeichnis existiert bereits
			} else {
			    	//Das Verzeichnis existiert nicht
				$result = mkdir($WebfrontPath);
				If (!$result) {
					IPS_LogMessage("IPS2Enigma","Fehler bei der Verzeichniserstellung!");
				}
			}
			
			$ftp_server = $this->ReadPropertyString("IPAddress");
			$ftp_user_name = $this->ReadPropertyString("User");
			$ftp_user_pass = $this->ReadPropertyString("Password");

			// set up basic connection
			$conn_id = ftp_connect($ftp_server);

			// login with username and password
			$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

			If ($login_result == true) {
				ftp_chdir($conn_id, $SourcePath);
				// die Dateien in diesem Verzeichnis ermitteln
				$contents = ftp_nlist($conn_id, ".");
				for ($i = 0; $i <= count($contents) - 1; $i++) {
					$result = ftp_get ($conn_id, $WebfrontPath.DIRECTORY_SEPARATOR.$contents[$i], $SourcePath.DIRECTORY_SEPARATOR.$contents[$i], FTP_BINARY);
					If (!$result) {
						IPS_LogMessage("IPS2Enigma","Fehler beim Kopieren der Datei ".$contents[$i]."!");
					}
						
				}
				$result = true;
			}
			else {
				IPS_LogMessage("IPS2Enigma","Fehler bei der Verbindung!");
				$result = false;
			}
			// close the connection
			ftp_close($conn_id); 
		}
		else {
			$result = false;
		}
	
        return $result;
	}   
	    
	private function Get_Picons()
	{
		// Quelldatei
		$FileName = IPS_GetKernelDir()."modules".DIRECTORY_SEPARATOR."SymconModules".DIRECTORY_SEPARATOR."IPS2Enigma".DIRECTORY_SEPARATOR."Picons".DIRECTORY_SEPARATOR."Picons.zip";
		// Zielpfad
		$WebfrontPath = IPS_GetKernelDir()."webfront".DIRECTORY_SEPARATOR."user".DIRECTORY_SEPARATOR;  

		if (file_exists($FileName)) {
			// Prüfen, ob die Datei neuer ist, als die bisher installierte
			If (filemtime($FileName) > GetValueInteger($this->GetIDForIdent("PiconUpdate"))) {
				$zip = new ZipArchive;
				if ($zip->open($FileName) === TRUE) {
				$zip->extractTo($WebfrontPath);
				$zip->close();
					// Neues Erstellungsdatum der Datei sichern
					SetValueInteger($this->GetIDForIdent("PiconUpdate"), filemtime($FileName));
					IPS_LogMessage("IPS2Enigma","Picon Update erfolgreich");
				} 
				else {
					IPS_LogMessage("IPS2Enigma","Picon Update nicht erfolgreich!");
				}
			}
		}		
	}
				       
/*	    
//*************************************************************************************************************
// Schreibt eine Message auf den Bildschirm die man mit ja oder nein beantworten muss
// man sollte die Frage immer so stellen, das nein als aktive Antwort ausgewertet wird,
// da in allen anderen Fällen 0 oder -1  gemeldet wird
// return
// -1  wenn keine erfolgreiche Verbindung
// 0 wenn mit ja oder garnicht geantwortet wurde
// 1 wenn mit nein geantwortet
function ENIGMA2_GetAnswerFromMessage($ipadr,$message = "",$time=5)
{
    $type = 0;
    $result = -1;
   if (ENIGMA2_GetAvailable( $ipadr ))
    {
       $message = urlencode($message);
       $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/message?text=$message&type=$type&timeout=$time"));
      if ($xmlResult->e2state == "True")
      {
         sleep($time);
         $result = -1;
         $xmlResult =  new SimpleXMLElement(file_get_contents("http://$ipadr/web/messageanswer?getanswer=now"));
            if ($xmlResult->e2statetext == "Answer is NO!")
          {
              $result = 1;
          }
          else
          {
             $result = 0;
          }
        }    }
   else
    {
       $result = -1;
    }
return $result;
}

//*************************************************************************************************************
// Prüft ob die Box gerade aufnimmt
function ENIGMA2_RecordStatus($ipadr)
{
   $result = false;
echo "test";
	if (ENIGMA2_GetAvailable( $ipadr ))
    	{
		$xml = simplexml_load_file("http://$ipadr/web/recordnow?.xml");
echo $xml;
		$wert = $xml->e2state;
		echo $wert;
		if(strpos($wert,"false")!== false)
			{
			$result = true; // Bei "false" ist die Box eingeschaltet
			}
		else
			{
			$result = false;
			}
		}
		else
		   {
		   Echo "Box nicht erreichbar";
		   }
return $result;
}


*/
}
?>
