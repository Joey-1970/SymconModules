<?
class IPS2GPIO_IO extends IPSModule
{
  // Der Konstruktor des Moduls
  // Überschreibt den Standard Kontruktor von IPS
  public function __construct($InstanceID) 
  {
      // Diese Zeile nicht löschen
      parent::__construct($InstanceID);
 
            // Selbsterstellter Code
  }
  
  public function Create() 
  {
    // Diese Zeile nicht entfernen
    parent::Create();
 
    // Modul-Eigenschaftserstellung
    $this->RegisterPropertyString("IPAddress", "127.0.0.1");
    $this->RegisterPropertyBoolean("Open", false);
    $this->RegisterPropertyInteger("Model", 0);
  }
  
  public function ApplyChanges()
  {
    //Never delete this line!
    parent::ApplyChanges();
    //$this->RegisterVariableBoolean("Status", "Status", "~Switch");
    //$this->RegisterEventCyclic("UpdateTimer", "Automatische Aktualisierung", 15);
  }
  
  private function ConnectionTest()
  {
      $IP = "paule-25.selfhost.bz";
      $port = 8888;
      $AktuellerPCStatus = False;

      If (GetValueBoolean(18029 /*[Fritz!Box\DSL\DSL\Status]*/ )== true)
	    {
	        $AktuellerPCStatus = Sys_Ping($IP, 2000);

	        If ($AktuellerPCStatus == true)
		      {
		          $status = @fsockopen($IP, $port, $errno, $errstr, 10);

		          if (!$status)
   		        {
      	          //SetValueBoolean(45369 /*[LAN\TV Server\TV Server Status\HD+ Server]*/ , false);
			            //echo "Fehler";
   		        }
   		        else
   		        {
   		            fclose($status);
      	          //SetValueBoolean(45369 /*[LAN\TV Server\TV Server Status\HD+ Server]*/ , true);
			            //echo "Ok";
   		        }
		      }
		      else
		      {
		            //SetValueBoolean(45369 /*[LAN\TV Server\TV Server Status\HD+ Server]*/ , false);
		      }
      }
  }
?>
