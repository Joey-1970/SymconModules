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
		
        return;
	}

	
	
}


/*


Port 8102
IP 192.168.178.36

// Daten aus dem Cutter der Registervariable
 	$data=$_IPS['VALUE'];

   // Sehen was ankommt
	SetValueString(40047 /*[Pioneer BDP450\AV_REGISTER\Response]*/ , $data);
	// Das letzte abgegebene Kommando
	$LastCommand = GetValueString(IPS_GetObjectIDByName("LastCommand", IPS_GetParent($_IPS['SELF'])));

	If ($LastCommand == "?P")
	   {
		If (substr($data, 0, 2) == "P0")
		   {
		   // Gerät ist eingeschaltet
		   SetValueBoolean(IPS_GetObjectIDByName ( "Power", IPS_GetParent($_IPS['SELF'])), true);
			If ($data == "P00")
			   {
			   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "Tray opening completed");
			   }
			elseIf ($data == "P01")
			   {
			   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "Tray closing completed");
			   }
         elseIf ($data == "P02")
			   {
			   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "Disc Information loading");
			   }
         elseIf ($data == "P03")
			   {
			   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "Tray opening");
			   }
       	elseIf ($data == "P04")
			   {
			   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "Play");
			   }
			elseIf ($data == "P05")
			   {
			   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "Still");
			   }
			elseIf ($data == "P06")
			   {
			   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "Pause");
			   }
         elseIf ($data == "P07")
			   {
			   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "Searching");
			   }
			elseIf ($data == "P08")
			   {
			   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "Forward/reverse scanning");
			   }
			elseIf ($data == "P09")
			   {
			   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "Forward/reverse slow play");
			   }
			}
		else if ($data == "E04")
		   {
		   // Gerät ist ausgeschaltet
		   SetValueBoolean(IPS_GetObjectIDByName ( "Power", IPS_GetParent($_IPS['SELF'])), false);
		   SetValueString(IPS_GetObjectIDByName ( "Modus", IPS_GetParent($_IPS['SELF'])), "");
		   SetValueInteger(IPS_GetObjectIDByName ("Chapter", IPS_GetParent($_IPS['SELF'])), 0);
		   SetValueString(IPS_GetObjectIDByName ("Time", IPS_GetParent($_IPS['SELF'])), "--:--:--");
		   SetValueString(IPS_GetObjectIDByName ("StatusRequest", IPS_GetParent($_IPS['SELF'])), "");
		   SetValueInteger(IPS_GetObjectIDByName ("Track", IPS_GetParent($_IPS['SELF'])), 0);
		   SetValueString(IPS_GetObjectIDByName ("DiscLoaded", IPS_GetParent($_IPS['SELF'])), "");
		   SetValueString(IPS_GetObjectIDByName ("Application", IPS_GetParent($_IPS['SELF'])), "");
		   SetValueString(IPS_GetObjectIDByName ("Information", IPS_GetParent($_IPS['SELF'])), "");
		   }
		}
	elseIf ($LastCommand == "?C")
	   {
			// Ermittlung des Chapters
			SetValueInteger(IPS_GetObjectIDByName ("Chapter", IPS_GetParent($_IPS['SELF'])), (int)($data));
		}
	elseIf ($LastCommand == "?T")
	   {
		If ($data == "E04")
		   {
			// Ermittlung der Spielzeit
			SetValueString(IPS_GetObjectIDByName ("Time", IPS_GetParent($_IPS['SELF'])), "--:--:--");
			}
			else
			{
			$data = str_pad($data, 6 ,'0', STR_PAD_LEFT);
			SetValueString(IPS_GetObjectIDByName ("Time", IPS_GetParent($_IPS['SELF'])), (substr($data,0,2).":".substr($data,2,2).":".substr($data,4,2)));
			}
		}
	elseIf (($LastCommand == "?V") or ($LastCommand == "?J") or ($LastCommand == "?K"))
	   {
		// Abfrage des Status
		SetValueString(IPS_GetObjectIDByName ("StatusRequest", IPS_GetParent($_IPS['SELF'])), $data);
		}
	elseIf ($LastCommand == "?R")
	   {
		If ($data == "E04")
		   {
			// Abfrage des Tracks
			SetValueInteger(IPS_GetObjectIDByName ("Track", IPS_GetParent($_IPS['SELF'])), 0);
			}
			else
			{
			SetValueInteger(IPS_GetObjectIDByName ("Track", IPS_GetParent($_IPS['SELF'])), (int)$data);
			}
		}
   elseIf ($LastCommand == "?D")
	   {
		If (substr($data, 0,1) == "0")
		   {
         SetValueString(IPS_GetObjectIDByName ("DiscLoaded", IPS_GetParent($_IPS['SELF'])), "None");
			}
		elseIf (substr($data, 0,1) == "1")
		   {
         SetValueString(IPS_GetObjectIDByName ("DiscLoaded", IPS_GetParent($_IPS['SELF'])), "Yes");
			}
		elseIf (substr($data, 0,1) == "x")
		   {
         SetValueString(IPS_GetObjectIDByName ("DiscLoaded", IPS_GetParent($_IPS['SELF'])), "Unknown");
			}
		If (substr($data, 1, 1) == "0")
		   {
			// Abfrage des Mediums
			SetValueString(IPS_GetObjectIDByName ("Information", IPS_GetParent($_IPS['SELF'])), "Bluray");
			}
      elseIf (substr($data, 1, 1) == "1")
		   {
		   SetValueString(IPS_GetObjectIDByName ("Information", IPS_GetParent($_IPS['SELF'])), "DVD");
			}
		elseIf (substr($data, 1, 1) == "2")
		   {
		   SetValueString(IPS_GetObjectIDByName ("Information", IPS_GetParent($_IPS['SELF'])), "CD");
			}
		elseIf (substr($data, 1, 1) == "x")
		   {
		   SetValueString(IPS_GetObjectIDByName ("Information", IPS_GetParent($_IPS['SELF'])), "Keine Disk");
			}

		If (substr($data, 2, 1) == "0")
		   {
		   SetValueString(IPS_GetObjectIDByName ("Application", IPS_GetParent($_IPS['SELF'])), "BDMV");
			}
		elseIf (substr($data, 2, 1) == "1")
		   {
		   SetValueString(IPS_GetObjectIDByName ("Application", IPS_GetParent($_IPS['SELF'])), "BDAV");
			}
		elseIf (substr($data, 2, 1) == "2")
		   {
		   SetValueString(IPS_GetObjectIDByName ("Application", IPS_GetParent($_IPS['SELF'])), "DVD-Video");
			}
		elseIf (substr($data, 2, 1) == "3")
		   {
		   SetValueString(IPS_GetObjectIDByName ("Application", IPS_GetParent($_IPS['SELF'])), "DVD VR");
			}
		elseIf (substr($data, 2, 1) == "4")
		   {
		   SetValueString(IPS_GetObjectIDByName ("Application", IPS_GetParent($_IPS['SELF'])), "CD-DA");
			}
		elseIf (substr($data, 2, 1) == "5")
		   {
		   SetValueString(IPS_GetObjectIDByName ("Application", IPS_GetParent($_IPS['SELF'])), "DTS-CD");
			}
		elseIf (substr($data, 2, 1) == "x")
		   {
		   SetValueString(IPS_GetObjectIDByName ("Application", IPS_GetParent($_IPS['SELF'])), "Unknown");
			}
		}
*/
?>
