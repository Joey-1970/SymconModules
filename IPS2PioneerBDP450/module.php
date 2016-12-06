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
	    	$Data = json_decode($JSONString);
		$Message = utf8_decode($Data->Buffer);
		// Entfernen der Steuerzeichen
		$Message = trim($Message, "\x00..\x1F");
		IPS_LogMessage("IPS2PioneerBDP450","Client Response 2: ".$Message);
	return;
	}
	
	public function Get_DataUpdate()
	{
		// Power-Status abfragen
		//$this->CommandClientSocket("?P", 3);
		$this->ClientSocket("?P".chr(13));
	return;
	}
	
	private function ClientSocket(String $message)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$res = $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => utf8_encode($message))));  
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
	
	private function GetParentID()
	{
		$ParentID = (IPS_GetInstance($this->InstanceID)['ConnectionID']);  
	return $ParentID;
	}

}

?>
