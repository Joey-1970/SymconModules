private function RemoteAccessData()
{
   $result = true;
   exec('sudo cat /root/.symcon', $ResultArray);
   If (strpos($ResultArray[0], "Licensee=") === false)
		{
		$result = false;
		}
	else
		{
      $User = substr(strstr($ResultArray[0], "="),1);
      }
If (strpos($ResultArray[(count($ResultArray))-1], "Password=") === false)
		{
		$result = false;
		}
	else
		{
      $Pass = base64_decode(substr(strstr($ResultArray[(count($ResultArray))-1], "="),1));
      }
return array($result, $User, $Pass);
}
