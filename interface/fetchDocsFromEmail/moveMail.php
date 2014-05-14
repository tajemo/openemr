<?php  
  require_once("../globals.php"); 
  require_once("$srcdir/acl.inc");
  require_once("$srcdir/api.inc"); 
  
////////////////////////////////////////
//    
////// GLOBALS OR USER SPECIFIC
//
////////////////////////////////////////////
//   $_POST = $_GET;
  $host = $imap_mail_host;
  $port = $imap_mail_port;
  $user = $imap_mail_user;
  $thisEmail = $imap_mail_email;
  $password = $imap_mail_pass;  
  
  
  $mailbox = trim($_POST['mailBox']);   
  
  $mbox = imap_open('{'.$host.':'.$port.'}'.$mailbox , $user , $password)
                  or die("can't connect: " . imap_last_error());
  $check = imap_check($mbox);
  $overviews = imap_fetch_overview($mbox,"1:$check->Nmsgs");
  

 
              
  $uid = trim($_POST['uid']);
  foreach($overviews as $overV){
//     print_r($overV);echo '<br />'.$overV->uid.'<hr />';
    if($overV->uid == $uid){
       $msgNo = $overV->msgno;
//        echo $i.'<hr />';
       break;
    } 
  }   
//   $msgNo = trim($_POST['msgno']);  
  $moveTo = ($_POST['mailBox'] == 'INBOX.DOCUMENT_ARCHIVES' ? 'INBOX' : 'INBOX.DOCUMENT_ARCHIVES');
  
//   echo $moveTo;
//   echo $mailbox.' move to : '.$moveTo.'<br /><br />';
  
//   print_r($_POST);

  
  imap_mail_move ( $mbox , $msgNo , $moveTo );
  imap_close($mbox,CL_EXPUNGE);  
//   
// echo '<br /><br />'.imap_last_error().'<br /><br />';
?>