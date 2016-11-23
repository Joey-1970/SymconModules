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
		$this->RegisterTimer("DataUpdate", 0, 'Enigma_Get_DataUpdate($_IPS["TARGET"]);');
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
		
		//Status-Variablen anlegen
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
		$this->RegisterVariableString("e2model", "Model", "", 60);
		$this->DisableAction("e2model");
		$this->RegisterVariableString("e2lanmac", "LAN-MAC", "", 70);
		$this->DisableAction("e2lanmac");
		$this->RegisterVariableString("e2hddinfo_model", "HDD Model", "", 80);
		$this->DisableAction("e2hddinfo_model");
		$this->RegisterVariableInteger("e2hddinfo_capacity", "HDD Capacity", "gigabyte.GB", 90);
		$this->DisableAction("e2hddinfo_capacity");
		$this->RegisterVariableInteger("e2hddinfo_free", "HDD Free", "gigabyte.GB", 95);
		$this->DisableAction("e2hddinfo_free");
		
		$this->RegisterVariableBoolean("powerstate", "Powerstate", "~Switch", 100);
		$this->EnableAction("powerstate");
		
		$this->RegisterVariableString("e2servicename", "Service Name", "", 110);
		$this->DisableAction("e2servicename");
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
		
		$this->RegisterVariableInteger("e2snrdb", "Signal-to-Noise Ratio (dB)", "snr.db", 300);
		$this->DisableAction("e2snrdb");
		$this->RegisterVariableInteger("e2snr", "Signal-to-Noise Ratio", "~Intensity.100", 310);
		$this->DisableAction("e2snr");
		$this->RegisterVariableInteger("e2ber", "Bit error rate", "", 320);
		$this->DisableAction("e2ber");
		$this->RegisterVariableInteger("e2agc", "Automatic Gain Control", "~Intensity.100", 330);
		$this->DisableAction("e2agc");
		
		If (($this->ReadPropertyString("Open") == true) AND ($this->ConnectionTest() == true)) {
			$this->Get_BasicData();
			$this->SetTimerInterval("DataUpdate", ($this->ReadPropertyInteger("DataUpdate") * 1000));
			$this->Get_Powerstate();
		}
		

        }
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Status":
	            $this->Set_Status($Value);
	            //Neuen Wert in die Statusvariable schreiben
	            SetValue($this->GetIDForIdent($Ident), $Value);
	            break;
	        default:
	            throw new Exception("Invalid Ident");
	    }
	}
	

	// Beginn der Funktionen
	public function Get_DataUpdate()
	{
		If (($this->ReadPropertyString("Open") == true) AND ($this->Get_Powerstate() == true)) {
			//IPS_LogMessage("IPS2Enigma","TV-Daten ermitteln");
			// das aktuelle Programm
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/subservices"));
       			SetValueString($this->GetIDForIdent("e2servicename"), (string)$xmlResult->e2service[0]->e2servicename);
			$e2servicereference = (string)$xmlResult->e2service[0]->e2servicereference;
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
			//SetValueInteger($this->GetIDForIdent("e2eventprogress"), round( ( (int)time() - (int)$xmlResult->e2event->e2eventstart) / 60) / (int)$xmlResult->e2event->e2eventduration / 60  );
			SetValueInteger($this->GetIDForIdent("e2eventprogress"), GetValueInteger($this->GetIDForIdent("e2eventpast")) / GetValueInteger($this->GetIDForIdent("e2eventduration")) * 100);
			// das folgende Ereignis
			$xmlResult =  new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/epgservicenext?sRef=".$e2servicereference));
			SetValueString($this->GetIDForIdent("e2nexteventtitle"), (string)utf8_decode($xmlResult->e2event->e2eventtitle));
      			SetValueString($this->GetIDForIdent("e2nexteventdescription"), (string)utf8_decode($xmlResult->e2event->e2eventdescription));
			SetValueString($this->GetIDForIdent("e2nexteventdescriptionextended"), (string)utf8_decode($xmlResult->e2event->e2eventdescriptionextended));
      			SetValueInteger($this->GetIDForIdent("e2nexteventstart"), (int)$xmlResult->e2event->e2eventstart);
			SetValueInteger($this->GetIDForIdent("e2nexteventend"), (int)$xmlResult->e2event->e2eventstart + (int)$xmlResult->e2event->e2eventduration);
			SetValueInteger($this->GetIDForIdent("e2nexteventduration"), round((int)$xmlResult->e2event->e2eventduration / 60) );
			// Empfangsstärke ermitteln
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/signal?"));
			SetValueInteger($this->GetIDForIdent("e2snrdb"), (int)$xmlResult->e2snrdb);
			SetValueInteger($this->GetIDForIdent("e2snr"), (int)$xmlResult->e2snr);
			SetValueInteger($this->GetIDForIdent("e2ber"), (int)$xmlResult->e2ber);
			SetValueInteger($this->GetIDForIdent("e2agc"), (int)$xmlResult->e2acg);
			// Festplattendaten
			$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/about"));
			SetValueInteger($this->GetIDForIdent("e2hddinfo_capacity"), (int)$xmlResult->e2about->e2hddinfo->capacity);
			SetValueInteger($this->GetIDForIdent("e2hddinfo_free"), (int)$xmlResult->e2about->e2hddinfo->free);
		}
		else {
			SetValueString($this->GetIDForIdent("e2servicename"), "N/A");
			SetValueString($this->GetIDForIdent("e2eventtitle"), "N/A");
			SetValueString($this->GetIDForIdent("e2eventdescription"), "N/A");
			SetValueString($this->GetIDForIdent("e2eventdescriptionextended"), "N/A");
			//SetValueString($this->GetIDForIdent("e2eventstart"), "N/A");
			//SetValueString($this->GetIDForIdent("e2eventtime"), "N/A");
			//SetValueString($this->GetIDForIdent("e2eventstart"), "N/A");
			//SetValueString($this->GetIDForIdent("e2eventduration"), "N/A");
			//SetValueString($this->GetIDForIdent("e2eventleft"), "N/A");
			SetValueInteger($this->GetIDForIdent("e2eventprogress"), 0);
		}
	}
	// Ermittlung der Basisdaten
	private function Get_BasicData()
	{
		$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/about"));
		SetValueString($this->GetIDForIdent("e2oeversion"), (string)$xmlResult->e2about->e2oeversion);
		SetValueString($this->GetIDForIdent("e2enigmaversion"), (string)$xmlResult->e2about->e2enigmaversion);
		SetValueString($this->GetIDForIdent("e2distroversion"), (string)$xmlResult->e2about->e2distroversion);
		SetValueString($this->GetIDForIdent("e2imageversion"), (string)$xmlResult->e2about->e2imageversion);
		SetValueString($this->GetIDForIdent("e2webifversion"), (string)$xmlResult->e2about->e2webifversion);
		SetValueString($this->GetIDForIdent("e2model"), (string)$xmlResult->e2about->e2model);
		SetValueString($this->GetIDForIdent("e2lanmac"), (string)$xmlResult->e2about->e2lanmac);
		SetValueString($this->GetIDForIdent("e2hddinfo_model"), (string)$xmlResult->e2about->e2hddinfo->model);
	return;
	}
	
	private function Get_Powerstate()
	{
		$result = false;
		$xml = simplexml_load_file("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate");
		$wert = $xml->e2instandby;

		If(strpos($wert,"false")!== false) {
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
	      $xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=1"));
	return;
	}
	
	public function Standby()
	{
	       $xmlResult = new SimpleXMLElement(file_get_contents("http:///".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=5"));
	return;
	}			       
	
	public function WakeUpStandby()
	{
		$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=4"));
	return;
	}
				       
	public function Reboot()
	{
	   	 $xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=2"));
	return;
	}
	
	public function RestartEnigma()
	{
	      $xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/powerstate?newstate=3"));
	return;
	}		       
				       
	private function ConnectionTest()
	{
	      $result = false;
	      If (Sys_Ping($this->ReadPropertyString("IPAddress"), 2000)) {
			IPS_LogMessage("IPS2Enigma Netzanbindung","Angegebene IP ".$this->ReadPropertyString("IPAddress")." reagiert");
			$status = @fsockopen($this->ReadPropertyString("IPAddress"), 80, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("IPS2Enigma Netzanbindung","Port ist geschlossen!");				
	   			}
	   			else {
	   				fclose($status);
					IPS_LogMessage("IPS2Enigma Netzanbindung","Port ist geöffnet");
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
				       
				       
	    /*	    
//*************************************************************************************************************
// Prüft über Ping ob Gerät erreichbar
function ENIGMA2_GetAvailable($ipadr)
{
   $result = false;

	if ($ipadr > "" )
    	{
		If ((Boolean) ENIGMA2_Ping($ipadr))
		   {
		   $result = ENIGMA2_PowerstateStatus($ipadr);
		   }
		}
return $result;
}

//*************************************************************************************************************
// Prüft über Ping ob Gerät erreichbar
function ENIGMA2_Ping($ipadr)
{
   $result = false;

   // Sys-Ping funktioniert nur ohne Port-Angabe, daher muss sie falls vorhanden herausgetrennt werden
		$ippart = explode(":", $ipadr);
		$tmpipadr = $ippart[0];

	if ($tmpipadr > "" )
    	{
		If ((Boolean) Sys_Ping($tmpipadr, 1000))
		   {
		   $result = true;
		   }
		}
return $result;
}


//*************************************************************************************************************
// Prüft ob die Box eingeschaltet ist
function ENIGMA2_PowerstateStatus($ipadr)
{
   $result = true;

	$xml = simplexml_load_file("http://$ipadr/web/powerstate?.xml");
	$wert = $xml->e2instandby;
	
If(strpos($wert,"false")!== false)
		{
		$result = true; // Bei "false" ist die Box eingeschaltet
		}
	else
		{
		$result = false;
		}

return $result;
}



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
// Liefert den Namen des aktuellen Senders
function ENIGMA2_GetCurrentServiceName($ipadr)
{
	$result = "";

   if (ENIGMA2_GetAvailable( $ipadr ))
    {
       $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/subservices"));
       $result = $xmlResult->e2service[0]->e2servicename;
   }

return $result;
}
//*************************************************************************************************************
// Liefert den Namen der aktuellen Servicereferenz
function ENIGMA2_GetCurrentServiceReference($ipadr)
{
	$result = "";
	if (ENIGMA2_GetAvailable( $ipadr ))
   	{
      $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/subservices"));
      $result = $xmlResult->e2service[0]->e2servicereference;
   	}
return $result;
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
// Schreibt eine Infomessage auf den Bildschirm
function ENIGMA2_WriteInfoMessage($ipadr,$message = "",$time=5)
{
    $type = 1;
    $result = false;
   if (ENIGMA2_GetAvailable( $ipadr ))
    {
       $message = urlencode($message);
       $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/message?text=$message&type=$type&timeout=$time"));
      if ($xmlResult->e2state == "True")
      {
         $result = true;
        }
    }
   else
    {
       $result = false;
    }
return $result;
}

//*************************************************************************************************************
// Schreibt eine Errormessage auf den Bildschirm
function ENIGMA2_WriteErrorMessage($ipadr,$message = "",$time=5)
{
    $type = 3;
    $result = false;
   if (ENIGMA2_GetAvailable( $ipadr ))
    {
       $message = urlencode($message);
       $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/message?text=$message&type=$type&timeout=$time"));
      if ($xmlResult->e2state == "True")
      {
         $result = true;
        }
    }
   else
    {
       $result = false;
    }
return $result;
}

//*************************************************************************************************************
// Schreibt eine Message auf den Bildschirm
function ENIGMA2_WriteMessage($ipadr,$message = "",$time=5)
{
    $type = 2;
    $result = false;
   if (ENIGMA2_GetAvailable( $ipadr ))
    {
       $message = urlencode($message);
       $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/message?text=$message&type=$type&timeout=$time"));
      if ($xmlResult->e2state == "True")
      {
         $result = true;
        }
    }
   else
    {
       $result = false;
    }
return $result;
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
// Ermittelt den aktuellen Film
function ENIGMA2_GetCurrentFilm($ipadr)
{
   if (ENIGMA2_GetAvailable($ipadr))
   	{
      $xmlResult =  new SimpleXMLElement(file_get_contents("http://$ipadr/web/subservices"));
      $reference = $xmlResult->e2service->e2servicereference;
      $name = utf8_decode($xmlResult->e2service->e2servicename);
      $xmlResult =  new SimpleXMLElement(file_get_contents("http://$ipadr/web/epgservice?sRef=$reference"));
      $title = utf8_decode($xmlResult->e2event->e2eventtitle);
      $description = utf8_decode($xmlResult->e2event->e2eventdescriptionextended);
      $startsec = $xmlResult->e2event->e2eventstart;
      $duration = $xmlResult->e2event->e2eventduration;
      $currenttime = time();
      if ((int)$startsec >= time() - 36000)
        	{
         $start = date("H:i",(int)$startsec) .' Uhr';
         $vorbei = round(((int)$currenttime - (int)$startsec) / 60 ).' Minuten';
        	}
      else
        	{
         $start = "N/A";
         $vorbei = "N/A";
        	}
        if (((int)$duration > 0) and ((int)$startsec >= time() - 36000))
            $ende = date("H:i",(int)$startsec + (int)$duration) .' Uhr';
        else
           $ende = "N/A";
        if ((int)$duration > 0)
            $dauer = round((int)$duration / 60).' Minuten';
        else
           $dauer = "N/A";
        if (((int)$currenttime > time() - 1800) and ((int)$currenttime < time() + 1800) and ((int)$startsec >= time() - 36000) and ((int)$duration > 0))
            $verbl = round(((int)$startsec + (int)$duration - (int)$currenttime) / 60 ).' Minuten';
        else
           $verbl = "N/A";
			If (round((int)$duration / 60) > 0)
			   {
				$Fortschritt = (int)(round(((int)$currenttime - (int)$startsec) / 60 ) / round((int)$duration / 60) * 100) ;
				}
			else
			   {
			   $Fortschritt = 0;
			   }
			$Filminformation = "Titel      : $title\nStart      : $start - Ende       : $ende\nDauer      : $dauer - Vergangen  : $vorbei - Verbleiben : $verbl\nDetails    : $description";
			return array($Filminformation, $Fortschritt);
	}
   else
   return 'Box nicht erreichbar!';
}

//*************************************************************************************************************
// Sendet Remote-Control-Befehle
function ENIGMA2_RemoteControl($ipadr, $command=0)
{
    $result = "nicht Erfolgreich";

   if (ENIGMA2_GetAvailable( $ipadr ))
    {
       $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/remotecontrol?command=$command"));
        //print_r ($xmlResult);
        if ($xmlResult->e2result == True)
        {
           $result = "Erfolgreich";
        }
        else
        {
            $result = "nicht Erfolgreich";
        }
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

//*************************************************************************************************************
// Prüft die Signalstärke des Senders
function ENIGMA2_SignalStatus($ipadr)
{
	if (ENIGMA2_GetAvailable( $ipadr ))
    	{
		$xml = simplexml_load_file("http://$ipadr/web/signal?.xml");
		$snrdb = (int)$xml->e2snrdb;
		$snr = (int)$xml->e2snr;
		$ber = (int)$xml->e2ber;
		$acg = (int)$xml->e2acg;
		}
	else
		{
		$snrdb = 0;
		$snr = 0;
		$ber = 0;
		$acg = 0;
		}

return array($snrdb, $snr, $ber, $acg);
}
	    */
}
?>
