<?php           

 
  
 ini_set("gd.jpeg_ignore_warning", 1);
if(!isset($fromLinkFile)){
// phpinfo();
// die;
//  ini_set("display_errors","1");
  $fake_register_globals=false;
  $sanitize_all_escapes=true;
  require_once("../globals.php"); 
  require_once("$srcdir/acl.inc");
  require_once("$srcdir/api.inc");
  
  $thisauth = acl_check('patients', 'docs');
  
  if(!$thisauth) die(xlt("you do not have suffient privilages to access this page"));
}
  
////////////////////////////////////////
//    
////// GLOBALS OR USER SPECIFIC
//
////////////////////////////////////////////
  $host = $imap_mail_host;
  $port = $imap_mail_port;
  $user = $imap_mail_user; 
  $password = $imap_mail_pass;  
  
  $mailbox = 'INBOX';
  if(trim($_GET['mailbox']) == 'DOCUMENT_ARCHIVES'){
    $mailbox = 'INBOX.DOCUMENT_ARCHIVES';
  } 
  $mbox = imap_open('{'.$host.':'.$port.'}'.$mailbox , $user , $password);
  
//   print_r($mbox); die;
   
  if(!isset($_GET['msgno']) || !isset($_GET['fileName'])) die("ERROR!");
  
  $messageNumber = intval($_GET['msgno']);     
  $fileName = trim($_GET['fileName']);  
  
  $charset = '';
  $htmlmsg = '';
  $plainmsg = '';
  $attachments = ''; 
  
  
    $sExtension = strtolower(end(explode('.', $fileName)));
    
    if(isset($fromLinkFile)){
      $msg = getmsg($mbox,$messageNumber);
      header("Content-type: image/$sExtension");
      $rawFile = $attachments[$fileName];
    }
    elseif($sExtension == 'jpg' || $sExtension == 'jpeg' || $sExtension == 'png' || $sExtension == 'gif'){ 
      $msg = getmsg($mbox,$messageNumber);
      header("Content-type: image/$sExtension");
      echo $attachments[$fileName];
    }else{
      $msg = getmsg($mbox,$messageNumber);   
      $file_info = new finfo(FILEINFO_MIME);  // object oriented approach!
      $mime_type = $file_info->buffer($attachments[$fileName]);  // e.g. gives "image/jpeg"  
      header("Content-type: $mime_type");
      echo $attachments[$fileName]; 
    }


/////////////////////////////////
///
/////////   function getmsg
///
////////////////////////////////// 
function getmsg($mbox,$mid) {
    // input $mbox = IMAP stream, $mid = message id
    // output all the following:
    global $charset,$htmlmsg,$plainmsg,$attachments;
    
    $htmlmsg = $plainmsg = $charset = '';
    $attachments = array();

    // HEADER
    $h = imap_header($mbox,$mid);
    // add code here to get date, from, to, cc, subject...

    // BODY
    $s = imap_fetchstructure($mbox,$mid);
    if (!$s->parts)  // simple
        getpart($mbox,$mid,$s,0);  // pass 0 as part-number
    else {  // multipart: cycle through each part
        foreach ($s->parts as $partno0=>$p)
            getpart($mbox,$mid,$p,$partno0+1);
    }
     
}    
/////////////////////////////////
///
/////////   end function getmsg
///
////////////////////////////////// 
     
     
          
/////////////////////////////////
///
/////////   function getpart
///
////////////////////////////////// 
function getpart($mbox,$mid,$p,$partno) {
    // $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
    global $htmlmsg,$plainmsg,$charset,$attachments;

    // DECODE DATA
    $data = ($partno)?
        imap_fetchbody($mbox,$mid,$partno):  // multipart
        imap_body($mbox,$mid);  // simple
    // Any part may be encoded, even plain text messages, so check everything.
    if ($p->encoding==4)
        $data = quoted_printable_decode($data);
    elseif ($p->encoding==3)
        $data = base64_decode($data);

    // PARAMETERS
    // get all parameters, like charset, filenames of attachments, etc.
    $params = array();
    if (isset($p->parameters)){
        foreach ($p->parameters as $x){
            $params[strtolower($x->attribute)] = $x->value;
        }
    }
    
    if (isset($p->dparameters)){
        foreach ($p->dparameters as $x){
            $params[strtolower($x->attribute)] = $x->value;
        }
    }

    // ATTACHMENT
    // Any part with a filename is an attachment,
    // so an attached text file (type 0) is not mistaken as the message.
    if (isset($params['filename']) || isset($params['name'])) {
        // filename may be given as 'Filename' or 'Name' or both
        $filename = ($params['filename'])? $params['filename'] : $params['name'];
        // filename may be encoded, so see imap_mime_header_decode()
        $attachments[$filename] = $data;  // this is a problem if two files have same name
    }

    // TEXT
    if ($p->type==0 && $data) {
        // Messages may be split in different parts because of inline attachments,
        // so append parts together with blank row.
        if (strtolower($p->subtype)=='plain'){
            $plainmsg .= trim($data)."\n\n";
        }
        else{
            $htmlmsg.= $data."<br><br>";
        }
        $charset = $params['charset'];  // assume all parts are same charset
    }

    // EMBEDDED MESSAGE
    // Many bounce notifications embed the original message as type 2,
    // but AOL uses type 1 (multipart), which is not handled here.
    // There are no PHP functions to parse embedded messages,
    // so this just appends the raw source to the main message.
    elseif ($p->type==2 && $data) {
        $plainmsg .= $data."\n\n";
    }

    // SUBPART RECURSION
    if (isset($p->parts)) {
        foreach ($p->parts as $partno0=>$p2)
            getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.
    }
}  
/////////////////////////////////
///
/////////   end function getpart
///
//////////////////////////////////



function sendAttachmentIcon(){
     
//      $sExtension = strtolower(end(explode('.', $fileName)));
     $pageURL = 'http';
     if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
     $pageURL .= "://";
     if ($_SERVER["SERVER_PORT"] != "80") {
      $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
     } else {
      $pageURL .= $_SERVER["SERVER_NAME"];
     }  
     $pageURL .= '/interface/fetchDocsFromEmail/attachmentIcon.jpg'; 
//        
    header("Content-type: image/jpeg");
    
    echo file_get_contents($pageURL);

}
?>