<?
    // Klassendefinition
    class IPS2GPIO_Dimmer extends IPSModule 
    {
 
        // Der Konstruktor des Moduls
        // Überschreibt den Standard Kontruktor von IPS
        public function __construct($InstanceID) 
        {
            // Diese Zeile nicht löschen
            parent::__construct($InstanceID);
 
            // Selbsterstellter Code
        }
 
        // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
           
            //$this->RegisterPropertyInteger("Pin", 2);
 	    $this->ForceParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");	
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
            
            //Connect to available splitter or create a new one
	   $this->ConnectParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");
        }




	
    }
?>



