<?
    // Klassendefinition
    class IPS2Enigma extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
           	$this->RegisterPropertyBoolean("Open", 0);
	    	$this->RegisterPropertyString("IPAddress", "127.0.0.1");
		$this->RegisterPropertyInteger("DataUpdate", 15);
		$this->RegisterPropertyBoolean("HDD_Data", false);
		$this->RegisterPropertyBoolean("EPGnow_Data", false);
		$this->RegisterPropertyBoolean("EPGnext_Data", false);
		$this->RegisterPropertyBoolean("Movielist_Data", false);
		$this->RegisterPropertyBoolean("Enigma2_Data", false);
		$this->RegisterPropertyBoolean("Signal_Data", false);
		$this->RegisterPropertyBoolean("Network_Data", false);
		$this->RegisterPropertyBoolean("RC_Data", false);
		$this->RegisterPropertyInteger("EPGUpdate", 60);
		$this->RegisterTimer("DataUpdate", 0, 'Enigma_Get_DataUpdate($_IPS["TARGET"]);');
		$this->RegisterTimer("EPGUpdate", 0, 'Enigma_Get_EPGUpdate($_IPS["TARGET"]);');
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
		$this->RegisterVariableString("e2model", "Model", "", 60);
		$this->DisableAction("e2model");
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
			$this->RegisterVariableString("e2eventHTML", "Event", "~HTMLBox", 195);
			$this->DisableAction("e2eventHTML");
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
			$this->RegisterVariableString("e2nexteventHTML", "Next Event", "~HTMLBox", 255);
			$this->DisableAction("e2nexteventHTML");
		}
		
		If (($this->ReadPropertyBoolean("EPGnow_Data") == true) AND ($this->ReadPropertyBoolean("EPGnext_Data") == true)) {
			$this->RegisterVariableString("e2nownexteventHTML", "Now Next Event", "~HTMLBox", 257);
			$this->DisableAction("e2nownexteventHTML");
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
	
		$this->Get_Picons();
		
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$this->Get_BasicData();
			$this->SetTimerInterval("DataUpdate", ($this->ReadPropertyInteger("DataUpdate") * 1000));
			$this->SetTimerInterval("EPGUpdate", ($this->ReadPropertyInteger("EPGUpdate") * 1000));
			$this->Get_Powerstate();
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
				SetValueString($this->GetIDForIdent("e2eventHTML"), $table);
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
				SetValueString($this->GetIDForIdent("e2nexteventHTML"), $table);
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
				$table .= '<th class="tg-kv4b">Titel</th>';
				$table .= '<th class="tg-kv4b">Kurzbeschreibung<br></th>';
				$table .= '<th class="tg-kv4b">Langbeschreibung<br></th>';
				$table .= '<th class="tg-kv4b">Beginn<br></th>';
				$table .= '<th class="tg-kv4b">Ende<br></th>';
				$table .= '<th class="tg-kv4b">Dauer<br></th>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td rowspan="2" class="tg-611x"><img src='.$this->Get_Filename($e2servicereference).' alt='.$e2servicename.'></td>';
				$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventtitle).'</td>';
				$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventdescription).'</td>';
				$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventdescriptionextended).'</td>';
				$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event->e2eventstart).' Uhr'.'</td>';
				$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration).' Uhr'.'</td>';
				$table .= '<td class="tg-611x">'.round((int)$xmlResult->e2event->e2eventduration / 60).' min'.'</td>';
				$table .= '</tr>';
				$table .= '<tr>';
				$table .= '<td class="tg-611x">'.utf8_decode($xmlResult_2->e2event->e2eventtitle).'</td>';
				$table .= '<td class="tg-611x">'.utf8_decode($xmlResult_2->e2event->e2eventdescription).'</td>';
				$table .= '<td class="tg-611x">'.utf8_decode($xmlResult_2->e2event->e2eventdescriptionextended).'</td>';
				$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult_2->e2event->e2eventstart).' Uhr'.'</td>';
				$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult_2->e2event->e2eventstart + (int)$xmlResult_2->e2event->e2eventduration).' Uhr'.'</td>';
				$table .= '<td class="tg-611x">'.round((int)$xmlResult_2->e2event->e2eventduration / 60).' min'.'</td>';
				$table .= '</tr>';
				$table .= '</table>';
				SetValueString($this->GetIDForIdent("e2nownexteventHTML"), $table);
			}
			
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
				SetValueInteger($this->GetIDForIdent("e2hddinfo_capacity"), (int)$xmlResult->e2about->e2hddinfo->capacity);
				SetValueInteger($this->GetIDForIdent("e2hddinfo_free"), (int)$xmlResult->e2about->e2hddinfo->free);
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
	return;
	}
	
	public function Get_EPGUpdate()
	{
		$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/getservices"));
		$bouquet = (string)$xmlResult->e2service->e2servicereference;
		$xmlResult = new SimpleXMLElement(file_get_contents('http://192.168.178.20/web/epgnownext?bRef='.urlencode($bouquet)));
		$table = '<style type="text/css">';
		$table .= '<link rel="stylesheet" href="./.../webfront.css">';
		$table .= "</style>";
		$table .= '<table class="tg">';
		$table .= "<tr>";
		$table .= '<th class="tg-kv4b">Sender</th>';
		$table .= '<th class="tg-kv4b">Titel</th>';
		$table .= '<th class="tg-kv4b">Kurzbeschreibung<br></th>';
		//$table .= '<th class="tg-kv4b">Langbeschreibung<br></th>';
		$table .= '<th class="tg-kv4b">Beginn<br></th>';
		$table .= '<th class="tg-kv4b">Ende<br></th>';
		$table .= '<th class="tg-kv4b">Dauer<br></th>';
		$table .= '</tr>';
		for ($i = 0; $i <= count($xmlResult) - 1; $i++) {
			$table .= '<tr>';
			$table .= '<td rowspan="2" class="tg-611x"><img src='.$this->Get_Filename($e2servicereference).' alt='.$e2servicename.'></td>';
			$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventtitle).'</td>';
			$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventdescription).'</td>';
			//$table .= '<td class="tg-611x">'.utf8_decode($xmlResult->e2event->e2eventdescriptionextended).'</td>';
			$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event->e2eventstart).' Uhr'.'</td>';
			$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration).' Uhr'.'</td>';
			$table .= '<td class="tg-611x">'.round((int)$xmlResult->e2event->e2eventduration / 60).' min'.'</td>';
			$table .= '</tr>';
			$table .= '<tr>';
			$table .= '<td class="tg-611x">'.utf8_decode($xmlResult_2->e2event->e2eventtitle).'</td>';
			$table .= '<td class="tg-611x">'.utf8_decode($xmlResult_2->e2event->e2eventdescription).'</td>';
			//$table .= '<td class="tg-611x">'.utf8_decode($xmlResult_2->e2event->e2eventdescriptionextended).'</td>';
			$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult_2->e2event->e2eventstart).' Uhr'.'</td>';
			$table .= '<td class="tg-611x">'.date("H:i", (int)$xmlResult_2->e2event->e2eventstart + (int)$xmlResult_2->e2event->e2eventduration).' Uhr'.'</td>';
			$table .= '<td class="tg-611x">'.round((int)$xmlResult_2->e2event->e2eventduration / 60).' min'.'</td>';
			$table .= '</tr>';
		}
		$table .= '</table>';
		//SetValueString($this->GetIDForIdent("e2nownexteventHTML"), $table);
		
	
	return;
	}
	// Ermittlung der Basisdaten
	private function Get_BasicData()
	{
		$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/about"));
		If ($this->ReadPropertyBoolean("Enigma2_Data") == true) {
			SetValueString($this->GetIDForIdent("e2oeversion"), (string)$xmlResult->e2about->e2oeversion);
			SetValueString($this->GetIDForIdent("e2enigmaversion"), (string)$xmlResult->e2about->e2enigmaversion);
			SetValueString($this->GetIDForIdent("e2distroversion"), (string)$xmlResult->e2about->e2distroversion);
			SetValueString($this->GetIDForIdent("e2imageversion"), (string)$xmlResult->e2about->e2imageversion);
			SetValueString($this->GetIDForIdent("e2webifversion"), (string)$xmlResult->e2about->e2webifversion);
		}
		SetValueString($this->GetIDForIdent("e2model"), (string)$xmlResult->e2about->e2model);
		$table = '<style type="text/css">';
		$table .= '<link rel="stylesheet" href="./.../webfront.css">';
		$table .= "</style>";
		$table .= '<table class="tg">';
		$table .= "<tr>";
		$table .= '<th class="tg-kv4b">Name</th>';
		$table .= '<th class="tg-kv4b">Typ<br></th>';
		$table .= '</tr>';
		for ($i = 0; $i <= count($xmlResult->e2about->e2tunerinfo->e2nim) - 1; $i++) {
			$table .= '<tr>';
			$table .= '<td class="tg-611x">'.$xmlResult->e2about->e2tunerinfo->e2nim[0]->name.'</td>';
			$table .= '<td class="tg-611x">'.$xmlResult->e2about->e2tunerinfo->e2nim[0]->type.'</td>';
			$table .= '</tr>';
		}
		$table .= '</table>';
		SetValueString($this->GetIDForIdent("e2tunerinfo"), $table);
		If ($this->ReadPropertyBoolean("Network_Data") == true) {
			SetValueString($this->GetIDForIdent("e2lanmac"), (string)$xmlResult->e2about->e2lanmac);
			SetValueBoolean($this->GetIDForIdent("e2landhcp"), (bool)$xmlResult->e2about->e2landhcp);
			SetValueString($this->GetIDForIdent("e2lanip"), (string)$xmlResult->e2about->e2lanip);
			SetValueString($this->GetIDForIdent("e2lanmask"), (string)$xmlResult->e2about->e2lanmask);
			SetValueString($this->GetIDForIdent("e2langw"), (string)$xmlResult->e2about->e2langw);
		}
		If ($this->ReadPropertyBoolean("HDD_Data") == true) {
			SetValueString($this->GetIDForIdent("e2hddinfo_model"), (string)$xmlResult->e2about->e2hddinfo->model);
		}
	return;
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
		$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=0"));
	return;
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
	return;
	}
	
	public function Standby()
	{
	       If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$xmlResult = new SimpleXMLElement(file_get_contents("http:///".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=5"));
	       }
	return;
	}			       
	
	public function WakeUpStandby()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=4"));
		}
	return;
	}
				       
	public function Reboot()
	{
	   	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) { 
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=2"));
		}
	return;
	}
	
	public function RestartEnigma()
	{
	      	If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=3"));
		}
	return;
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
		       $xmlResult = new SimpleXMLElement(file_get_contents("http:/".$this->ReadPropertyString("IPAddress")."/web/message?text=$message&type=2&timeout=$time"));
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
		       $xmlResult = new SimpleXMLElement(file_get_contents("http:/".$this->ReadPropertyString("IPAddress")."/web/message?text=$message&type=1&timeout=$time"));
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
		       $xmlResult = new SimpleXMLElement(file_get_contents("http:/".$this->ReadPropertyString("IPAddress")."/web/message?text=$message&type=3&timeout=$time"));
		       if ($xmlResult->e2state == "True") {
		       		$result = true;
			}
		}
	return $result;
	}
	
	public function MoviePlay(string $servicereference)
	{
	   	$result = false;
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$servicereference = urlencode($servicereference);
			$xmlResult = new SimpleXMLElement(file_get_contents("http://192.168.178.20/web/zap?sRef=".$servicereference));
		}
	return;
	}    
	  /*
    [e2movie] => Array
        (
            [0] => SimpleXMLElement Object
                (
                    [e2servicereference] => 1:0:0:0:0:0:0:0:0:0:/media/hdd/movie/20161127 2010 - Das Erste HD - Polizeiruf 110_ Sumpfgebiete.ts
                    [e2title] => Polizeiruf 110: Sumpfgebiete
                    [e2description] => Fernsehfilm Deutschland 2016
                    [e2descriptionextended] => Kriminalhauptkommissar Hanns von Meuffels sieht sich mit einem Fall aus seiner Vergangenheit konfrontiert: Nach fünf Jahren in der geschlossenen Psychiatrie wird Julia Wendt entlassen. In vier Wochen soll das Wiederaufnahmeverfahren ihres Falles beginnen. Julia Wendt fühlt sich beschattet, bedroht und sucht Hilfe bei Hanns von Meuffels, dem Mann, der sie damals wegen eines Brandanschlags auf ihren Ehemann verhaftet hatte und vor Gericht gegen sie aussagte.Produziert in HD
                    [e2servicename] => Das Erste HD
                    [e2time] => 1480273800
                    [e2length] => 99:57
                    [e2tags] => SimpleXMLElement Object
	  */
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
	
	private function Get_Filename(string $sRef)
	{
		// aus der Service Referenz den Dateinamen des Picons generieren
		// Doppelpunkte durch Unterstriche ersetzen
		$Filename = str_replace(":", "_", $sRef);
		// das letzte Zeichen entfernen
		$Filename = substr($Filename, 0, -1);
		// .png anhängen
 		$Filename = "user".DIRECTORY_SEPARATOR."Picons".DIRECTORY_SEPARATOR.$Filename.".png";
	return $Filename;
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
	return;
	}
				       
				       
	    /*	    

//*************************************************************************************************************
// Schaltet auf den angeforderten Sender um
function ENIGMA2_Zap($ipadr,$sender = "")
{
   if (ENIGMA2_GetAvailable( $ipadr ))
    	{
      $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/zap?sRef=$sender"));
   	}
return;
}

//*************************************************************************************************************
// Liefert ein Array mit den Namen der Bouquets wenn $bouquet = ""
// liefert ein Array mit den Namen der Sender eines Bouquet  wenn $bouquet ungleich ""
// keys e2servicereference
// keys e2servicename
function ENIGMA2_GetServiceBouquetsOrServices($ipadr,$bouquet = "")
{
   if (ENIGMA2_GetAvailable( $ipadr ))
    	{
      if ($bouquet == "" )
      	{
         $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/getservices"));
       	}
      else
		 	{
         $bouquet = urlencode($bouquet);
         $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/getservices?sRef=$bouquet"));
       	}
   	}
   else
    	{
      $xmlResult[] = "";
    	}
return $xmlResult;
}

//*************************************************************************************************************
// Ermittelt die EPG-Daten eines definierten Senders
function ENIGMA2_EPG($ipadr, $sender = "")
{
   $xmlResult[] = "";
   $sender = urlencode($sender);
   $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/epgservice?sRef=$sender"));
return $xmlResult;
}

//*************************************************************************************************************
// Ermittelt alle EPG-Daten des aktuellen Zeitpunktes
function ENIGMA2_EPGnow($ipadr, $bouquet = "")
{
$xmlResult[] = "";
If (ENIGMA2_GetAvailable( $ipadr ))
   {
   $xmlResult[] = "";
   $bouquet = urlencode($bouquet);
   //http://192.168.178.39/web/epgnow?bRef=1:7:1:0:0:0:0:0:0:0:FROM BOUQUET "userbouquet.mein_tv.tv" ORDER BY bouquet
	$xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/epgnow?bRef=$bouquet"));
	}
return $xmlResult;
}

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
