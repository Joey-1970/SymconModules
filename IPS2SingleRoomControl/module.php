<?
// Klassendefinition
class IPS2SingleRommControl extends IPSModule 
{
    	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyBoolean("Open", false);
	    	
		
        return;
	}

	public function ApplyChanges()
	{
		//Never delete this line!
		parent::ApplyChanges();

	return;
	}

}
?>   
