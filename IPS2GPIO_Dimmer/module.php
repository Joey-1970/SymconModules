<?
    // Klassendefinition
    class IPS2GPIO_Dimmer extends IPSModule 
    {
         // Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            // Diese Zeile nicht löschen.
            parent::Create();
           
            $this->RegisterPropertyInteger("Pin", 2);
 	    $this->ForceParent("{ED89906D-5B78-4D47-AB62-0BDCEB9AD330}");	
        }
 
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            // Diese Zeile nicht löschen
            parent::ApplyChanges();
           
        }




	
    }
?>