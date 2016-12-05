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

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();
		
		$this->RegisterVariableBoolean("Power", "Power", "~Switch", 10);
		$this->EnableAction("Power");
		$this->RegisterVariableString("Modus", "Modus", "", 20);
		$this->DisableAction("Modus");
		$this->RegisterVariableInteger("Chapter", "Chapter", "", 30);
		$this->DisableAction("Chapter");
		//$this->RegisterVariableInteger("Time", "Time", "~UnixTimestampTime", 40);
		$this->RegisterVariableString("Time", "Time", "", 40);
		$this->DisableAction("Time");
		$this->RegisterVariableString("StatusRequest", "StatusRequest", "", 50);
		$this->DisableAction("StatusRequest");
		$this->RegisterVariableInteger("Track", "Track", "", 60);
		$this->DisableAction("Track");
		$this->RegisterVariableString("DiscLoaded", "DiscLoaded", "", 70);
		$this->DisableAction("DiscLoaded");
		$this->RegisterVariableString("Application", "Application", "", 80);
		$this->DisableAction("Application");
		$this->RegisterVariableString("Information", "Information", "", 90);
		$this->DisableAction("Information");
		
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
				$this->SetStatus(102);
			}
			else {
				$this->SetStatus(104);
			}	   
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
			$ApplicationText = "unbekannt";
		}
	return $ApplicationText;
	}
	
	private function GetInformation(Int $InformationNumber)
	{
		// substr($data, 1, 1)
		$Information = array(0 => "Bluray", 1 => "DVD", 2 => "CD");
		If (array_key_exists($InformationNumber, $Information)) {
			$ApplicationText = $Information[$InformationNumber];
		}
		else {
			$InformationText = "keine Disc";
		}
	return $InformationText;
	}

}

?>
