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
        }
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
		// Diese Zeile nicht löschen
		parent::ApplyChanges();
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
		$this->RegisterVariableString("e2model", "E2 Model", "", 60);
		$this->DisableAction("e2model");
		$this->RegisterVariableString("e2lanmac", "E2 Lan-MAC", "", 70);
		$this->DisableAction("e2lanmac");
		
		
		$this->GetBasicData();
		

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
	
	// Ermittlung der Basisdaten
	private function GetBasicData()
	{
		$xmlResult = new SimpleXMLElement(file_get_contents("http://".$this->ReadPropertyString("IPAddress")."/web/about"));
		SetValueString($this->GetIDForIdent("e2oeversion"), $xmlResult->e2about->e2oeversion);
		SetValueString($this->GetIDForIdent("e2enigmaversion"), $xmlResult->e2about->e2enigmaversion);
		SetValueString($this->GetIDForIdent("e2distroversion"), $xmlResult->e2about->e2distroversion);
		SetValueString($this->GetIDForIdent("e2imageversion"), $xmlResult->e2about->e2imageversion);
		SetValueString($this->GetIDForIdent("e2webifversion"), $xmlResult->e2about->e2webifversion);
		SetValueString($this->GetIDForIdent("e2model"), $xmlResult->e2about->e2model);
		SetValueString($this->GetIDForIdent("e2lanmac"), $xmlResult->e2about->e2lanmac);
	return;
	}
	
	// Toggelt den Status
	public function Toggle_Status()
	{
	
	return;
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
// Toggelt den Standby-Modus
function ENIGMA2_ToggleStandby($ipadr)
{
    $powerstate = 0;
    $result = -1;

   if (ENIGMA2_Ping($ipadr))
    {
       $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/powerstate?newstate=$powerstate"));
        //print_r ($xmlResult);
        if ($xmlResult->e2instandby == 'true')
        {
           $result = 1;
        }
        else
        {
            $result = 0;
        }
   }

return $result;
}

0 = Toogle Standby
1 = Deepstandby
2 = Reboot
3 = Restart Enigma2
4 = Wakeup from Standby
5 = Standby


//*************************************************************************************************************
// Setzt das Gerät in DeepStandby
function ENIGMA2_DeepStandby($ipadr)
{
   $powerstate = 1;
   $result = false;

   if (ENIGMA2_GetAvailable( $ipadr ))
   	{
      $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/powerstate?newstate=$powerstate"));
      //print_r($xmlResult);
      $result = (Boolean)$xmlResult->e2instandby;
   	}
return $result;
}

//*************************************************************************************************************
// Setzt das Gerät in den Standby-Modus
function ENIGMA2_Standby($ipadr)
{
    $powerstate = 5;
    $result = -1;

   if (ENIGMA2_GetAvailable( $ipadr ))
    {
       $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/powerstate?newstate=$powerstate"));
        print_r ($xmlResult);
        if ($xmlResult->e2instandby == 'true')
        {
           $result = 1;
        }
        else
        {
            $result = 0;
        }
   }

return $result;
}
//*************************************************************************************************************
// Weckt das Gerät aus dem Standby-Modus
function ENIGMA2_WakeUpStandby($ipadr)
{
   $powerstate = 4;
   $result = false;

   if (ENIGMA2_Ping($ipadr))
    	{
		$xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/powerstate?newstate=$powerstate"));
      //print_r($xmlResult);
      $result = (Boolean)$xmlResult->e2instandby;
   	}
return $result;
}

//*************************************************************************************************************
// Rebootes das Gerät
function ENIGMA2_Reboot($ipadr)
{
   $powerstate = 2;
   $result = false;

   if (ENIGMA2_GetAvailable( $ipadr ))
    	{
      $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/powerstate?newstate=$powerstate"));
      $result = (Boolean)$xmlResult->e2instandby;
   	}
return $result;
}

//*************************************************************************************************************
// Rebootet das Gerät
function ENIGMA2_RestartEnigma($ipadr)
{
   $powerstate = 3;
   $result = false;

   if (ENIGMA2_GetAvailable( $ipadr ))
    	{
      $xmlResult = new SimpleXMLElement(file_get_contents("http://$ipadr/web/powerstate?newstate=$powerstate"));
      $result = (Boolean)$xmlResult->e2instandby;
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
        }
    }
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
