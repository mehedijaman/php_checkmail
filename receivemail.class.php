<?php 

class ReceiveMail 
{ 
   private $protocol= 'imap'; 
	private $hostname= 'imap.gmail.com';
	private $port = 993;
	private $username= 'me@mehedipata.com';
	private $password= 'hellomypassword';
	private $ssl = true;
	private $novalidate = false;
	
    protected $marubox=''; 
    protected $is_connected = false;
	protected $error_msg = array();
	
	public function __construct($host=null,$user=null,$pass=null,$protocol=null,$port=null,$ssl=null,$novalidate=null)
	{
		$this->hostname = (is_null($host) ? $this->hostname : $host);
		$this->username = (is_null($user) ? $this->username : $user);
		$this->password = (is_null($pass) ? $this->password : $pass);
		$this->protocol = (is_null($protocol) ? $this->protocol : $protocol);
		$this->port = (is_null($port) ? $this->port : $port);
		$this->ssl = (is_null($ssl) ? $this->ssl : $ssl);
		$this->novalidate = (is_null($novalidate) ? $this->novalidate : $novalidate);
	}
     
    public function connect() //Connect To the Mail Box 
    { 
		$con = '{'.$this->hostname.':'.$this->port.'/'.$this->protocol.($this->ssl?'/ssl':'').($this->novalidate?'/novalidate-cert':'').'}INBOX';
        $this->marubox=@imap_open($con,$this->username,$this->password) or die('Can not connect to '.$this->hostname.' on port '.$this->port.': '.@imap_last_error()); 
        
        if($this->marubox) 
        { 
			$this->is_connected = true;
			return true;
		}
		return false;
    } 
	public function is_connected()
	{
		return $this->is_connected;
	}
    public function get_email_header($mid=null) // Get Header info 
    { 
        if(!$this->is_connected || is_null($mid)) 
            return false; 

        $mail_header=imap_headerinfo($this->marubox,$mid);
		//echo imap_fetchheader($this->marubox,$mid);
        $sender=$mail_header->from[0]; 
        $sender_replyto=$mail_header->reply_to[0]; 
		$mail_details = array();
        if(strtolower($sender->mailbox)!='mailer-daemon' && strtolower($sender->mailbox)!='postmaster') 
        { 
            $mail_details=array( 
				'datetime'=>date("Y-m-d H:i:s",$mail_header->udate),
				'from'=>strtolower($sender->mailbox).'@'.$sender->host, 
				'fromName'=>@$sender->personal, 
				'replyTo'=>strtolower($sender_replyto->mailbox).'@'.$sender_replyto->host, 
				'replyToName'=>@$sender_replyto->personal, 
				'subject'=>iconv_mime_decode($mail_header->subject,0, "utf-8"),
				'to'=>strtolower($mail_header->toaddress),
				'full_header'=>serialize($mail_header)
			); 
        } 
        return $mail_details; 
    } 
	public function get_unread_emails()
	{
		if(strtolower($this->protocol) != 'imap') {
			echo "Warning: The function get_unread_emails will not work on '".$this->protocol."' Protocol";
			return false;
		}
		if(!$this->is_connected) 
            return false;
		
		$result = imap_search($this->marubox, 'UNSEEN');
		return $result;
	}
	public function get_total_emails()
    { 
        if(!$this->is_connected) 
            return false; 

		return imap_num_msg($this->marubox); 
    } 
    public function get_email_body($mid=null,$format='html') 
    { 
        if(!$this->is_connected || is_null($mid)) 
            return false; 
		
		$body = "";
		
		if(strtolower($format) == 'html')
			$body = $this->get_part($this->marubox, $mid, "TEXT/HTML"); 
        
		if ($body == "") 
            $body = $this->get_part($this->marubox, $mid, "TEXT/PLAIN"); 
        if ($body == "") { 
            return ""; 
        } 
        return $body; 
    } 
	public function get_attachments($mid=null,$path='./',$prefix='') 
    { 
		$attachments = array();
        if(!$this->is_connected || is_null($mid)) 
            return false; 

        $struct = imap_fetchstructure($this->marubox,$mid); 
        if($struct->parts) 
        { 
            foreach($struct->parts as $key => $value) 
            { 
                $enc=$struct->parts[$key]->encoding; 
                if($struct->parts[$key]->ifdparameters) 
                { 
					$name = 'UNKNOWN';
					for($i=0;$i<count($struct->parts[$key]->dparameters);$i++) {
						if($struct->parts[$key]->dparameters[$i]->attribute == 'FILENAME')
							$name=$struct->parts[$key]->dparameters[$i]->value; 
					}
                    $message = imap_fetchbody($this->marubox,$mid,$key+1); 
                    if ($enc == 0) 
                        $message = imap_8bit($message); 
                    if ($enc == 1) 
                        $message = imap_8bit ($message); 
                    if ($enc == 2) 
                        $message = imap_binary ($message); 
                    if ($enc == 3) 
                        $message = imap_base64 ($message); 
                    if ($enc == 4) 
                        $message = quoted_printable_decode($message); 
                    if ($enc == 5) 
                        $message = $message; 
                    /*
                    Strip all characters but letters, numbers, and whitespace:
					If you want to strip all characters from your string other than letters, 
                    numbers, and whitespace, this regular expression will do the trick:
                    $res = preg_replace("/[^a-zA-Z0-9s]/", "", $string);
                    */
                    $fileName_1 = $prefix.$name;
                    $fileName=preg_replace("/[^a-zA-Z0-9\s\-\_\.]/", "", $fileName_1);
                    
					$fp=fopen($path.$fileName,"w"); 
                    fwrite($fp,$message); 
                    fclose($fp); 
                    
					array_push($attachments,$fileName);
                } 
                // Support for embedded attachments starts here 
                if(isset($struct->parts[$key]->parts)) 
                { 
                    foreach($struct->parts[$key]->parts as $keyb => $valueb) 
                    { 
                        $enc=$struct->parts[$key]->parts[$keyb]->encoding; 
                        if($struct->parts[$key]->parts[$keyb]->ifdparameters) 
                        { 
							$name = 'UNKNOWN';
							for($i=0;$i<count($struct->parts[$key]->parts[$keyb]->dparameters);$i++) {
								if($struct->parts[$key]->parts[$keyb]->dparameters[$i]->attribute == 'FILENAME')
									$name=$struct->parts[$key]->parts[$keyb]->dparameters[$i]->value; 
							}
                            $partnro = ($key+1).".".($keyb+1); 
                            $message = imap_fetchbody($this->marubox,$mid,$partnro); 
                            if ($enc == 0) 
                                   $message = imap_8bit($message); 
                            if ($enc == 1) 
                                   $message = imap_8bit ($message); 
                            if ($enc == 2) 
                                   $message = imap_binary ($message); 
                            if ($enc == 3) 
                                   $message = imap_base64 ($message); 
                            if ($enc == 4) 
                                   $message = quoted_printable_decode($message); 
                            if ($enc == 5) 
                                   $message = $message; 
                            
							$fileName_1 = $prefix.$name;
							$fileName=preg_replace("/[^a-zA-Z0-9\s\-\_\.]/", "", $fileName_1);
							$fp=fopen($path.$fileName,"w"); 
                            fwrite($fp,$message); 
                            fclose($fp); 
                            
							array_push($attachments,$fileName);
                        } 
                    } 
                } 
            } 
        } 
        return $attachments; 
    } 
    public function delete_email($mid=null) 
    { 
        if(!$this->is_connected || is_null($mid)) 
            return false; 
    
        imap_delete($this->marubox,$mid); 
    } 
	public function markas_read_email($mid=null)
	{
		if(strtolower($this->protocol) != 'imap') {
			echo "Warning: The function markas_read_email will not work on '".$this->protocol."' Protocol";
		}
		if(!$this->is_connected || is_null($mid)) 
            return false; 
		
		$status = imap_setflag_full($this->marubox, $mid, "\Seen");
		return $status;
	}
    public function close_mailbox() 
    { 
        if(!$this->is_connected) 
            return false; 
		
		imap_expunge($this->marubox);
        imap_close($this->marubox,CL_EXPUNGE); 
    } 
    private function get_mime_type(&$structure) //Get Mime type Internal Private Use 
    { 
        $primary_mime_type = array("TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"); 
        
        if($structure->subtype) { 
            return $primary_mime_type[(int) $structure->type] . '/' . $structure->subtype; 
        } 
        return "TEXT/PLAIN"; 
    } 
    private function get_part($stream, $msg_number, $mime_type, $structure = false, $part_number = false) //Get Part Of Message Internal Private Use 
    { 
        if(!$structure) { 
            $structure = imap_fetchstructure($stream, $msg_number); 
        } 
        if($structure) { 
            if($mime_type == $this->get_mime_type($structure)) 
            { 
                if(!$part_number) 
                { 
                    $part_number = "1"; 
                } 
                $text = imap_fetchbody($stream, $msg_number, $part_number); 
				
				if($structure->encoding == 1) {
					return imap_utf8($text);
				}
                else if($structure->encoding == 3) 
                { 
                    return imap_base64($text); 
                } 
                else if($structure->encoding == 4) 
                { 
                    return imap_qprint($text); 
                } 
                else 
                { 
                    return $text; 
                } 
            } 
            if($structure->type == 1) /* multipart */ 
            { 
                while(list($index, $sub_structure) = each($structure->parts)) 
                { 
					$prefix='';
                    if($part_number) 
                    { 
                        $prefix = $part_number . '.'; 
                    } 
                    $data = $this->get_part($stream, $msg_number, $mime_type, $sub_structure, $prefix . ($index + 1)); 
                    if($data) 
                    { 
                        return $data; 
                    } 
                } 
            } 
        } 
        return false; 
    } 
} 
?>