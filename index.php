<?php

include("receivemail.class.php"); 
// Creating a object of reciveMail Class 
$obj= new ReceiveMail('imap.gmail.com','mehedi@mehedipata.com','hellomypassword','imap','993',true,false); 

//Connect to the Mail Box 
$obj->connect(); //If connection fails give error message and exit 

if($obj->is_connected())
{
	// Get Total Number of Unread Email in mail box 
	$tot = $obj->get_total_emails(); //Total Mails in Inbox Return integer value 

	echo "Total Mails:: ".$tot."<br>"; 
	
	//This function will only work with IMAP.. If it is POP3 then you have to use "get_total_emails()".
	$unread = $obj->get_unread_emails();
	
	if(!$unread)
	{
		echo "No Unread email found.<br>"; 
	}
	else
	{
		echo "Total Unread E-Mails:: ".count($unread)."<br>"; 
		
		//Displaying all unread emails.
		for($i=0; $i<count($unread); $i++) 
		{ 
			$eml_num = $unread[$i]; 
			//Return all email header information such as Subject, Date, To, CC, From, ReplyTo. It also return Serialise object from the IMAP for detail use.
			$head = $obj->get_email_header($eml_num);
			echo "<br>"; 
				echo "<pre>";
					print_r($head);
				echo "</pre>";
			echo "<br>*******************************************************************************************<BR>"; 
			//The below function return email body.. If you want Text body from HTML formated email then pass second parameter i.e. $obj->get_email_body($eml_num,'text');
			echo $obj->get_email_body($eml_num); 
			
			//The below function will store attachment at the path passed in second argument and return Array of file names received.	
			$arrFiles=$obj->get_attachments($eml_num,"./"); 
			if($arrFiles)
			{
				foreach($arrFiles as $key=>$value) 
				{
					echo ($value=="")?"":"Atteched File :: ".$value."<br>"; 
				}
				echo "<br>------------------------------------------------------------------------------------------<BR>"; 
			}
			// The below function will mark the email as Read in the mail box but commented in example site...
			//$obj->markas_read_email($eml_num);
			
			// The below function will delete the email from mail box but commented in example for accidental deletion...
			//$obj->delete_email($eml_num); 
		} 
	}
}
$obj->close_mailbox(); //Close Mail Box 

?> 