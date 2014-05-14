<?php 
  $fake_register_globals=false;
  $sanitize_all_escapes=true;
  require_once("../globals.php"); 
  require_once("$srcdir/acl.inc");
  require_once("$srcdir/api.inc");
  
  $thisauth = acl_check('patients', 'docs');
  
  if(!$thisauth) die(xlt("you do not have suffient privilages to access this page"));
  
  if(!function_exists ('imap_open')) die(xlt("this server needs imap setup, go to http://au2.php.net/manual/en/book.imap.php for more information"));
////////////////////////////////////////
//    
////// GLOBALS OR USER SPECIFIC
//
////////////////////////////////////////////
  $host = $imap_mail_host;
  $port = $imap_mail_port;
  if($port == '993') $port = $port.'/ssl';
  $user = $imap_mail_user;
  $thisEmail = $imap_mail_email;
  $password = $imap_mail_pass; 
  
//   echo '{'.$host.':'.$port.'}'.$mailbox;
  
  
        
  $mailbox = 'INBOX';
  if(trim($_GET['folder']) == 'DOCUMENT_ARCHIVES'){
    $mailbox = 'INBOX.DOCUMENT_ARCHIVES';
  }
  
  $undoMoveMailBox = ($mailbox == 'INBOX' ? 'INBOX.DOCUMENT_ARCHIVES' : 'INBOX');
  
//   echo $mailbox;
  
  $mbox = imap_open('{'.$host.':'.$port.'}'.$mailbox , $user , $password);
  
  $pageURL = 'http';
     if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
     $pageURL .= "://";
     if ($_SERVER["SERVER_PORT"] != "80") {
      $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
     } else {
      $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
     }
     
     
     $cats = array();
      $sql  = "SELECT * FROM categories WHERE parent != 0 ORDER BY name asc";
      $catsRes = sqlStatement($sql); 
      while($catsRow = sqlFetchArray($catsRes)){  
        $cats[] = $catsRow;
      }
?>

<html>
<head>
<?php html_header_show(); ?>
<link rel="stylesheet" href="<?php echo $css_header;?>" type="text/css">                               
    <link rel="stylesheet" href="../../library/css/tableSorter/style.css" type="text/css">       
    <link rel="stylesheet" href="../../library/css/tableSorter/theme.blue.css" type="text/css"> 
    <link class="ui-theme" rel="stylesheet" href="../../library/css/tableSorter/theme.jui.css">
  	<link class="theme" rel="stylesheet" href="../../library/css/tableSorter/theme.default.css">    
    <script type="text/javascript" src="../../library/js/jquery-1.4.3.min.js"></script> 
    <script type="text/javascript" src="../../library/js/common.js"></script> 
    <script type="text/javascript" src="../../library/js/fancybox/jquery.fancybox-1.2.6.js"></script>
    <link rel="stylesheet" type="text/css" href="../../library/js/fancybox/jquery.fancybox-1.2.6.css" media="screen" />
    
<script type="text/javascript" src="../../library/dialog.js"></script> 
<script type="text/javascript">
  var xhr; 
  
  function linkSelected(){   
  $("#feedbackDiv").html(""); 
   $("#uploadNotifier div h1").show(); 
   $("#uploadNotifier").show();
   $("#uploadNotifier div h1").show();      
   filesToLoad = $(".multiLink:checked").length+1;
   loopNumber = 1; 
   $(".textAreaHolder").each(function(){   
        loopID = $(this).attr("loopID"); 
        if($("#checkMe_"+loopID).is(":checked")){  
          patID = $("#new_patient_id_"+loopID).val();
          fileName = $("#fileName_"+loopID).val();   
          category = $("#category_"+loopID).val();     
          description = $("#description_"+loopID).val();  
          file =  $("#checkMe_"+loopID).attr("file");
          mailBox = $("#checkMe_"+loopID).attr("mailBox");
          msgno = $("#checkMe_"+loopID).attr("msgno");
          
          $.post("linkFile.php",{msgno:msgno,file:file,mailBox:mailBox,loopID:loopID,patID:patID,fileName:fileName,category:category,description:description,file:file,}, 
            function(data) { 
//             alert(data);
              $("#feedbackDiv").append("<br />File # "+loopNumber+" ("+data+") Uploaded<br />");
                  loopNumber++; 
                  if(filesToLoad == loopNumber){
                    $("#uploadNotifier div h1").hide();
                    $("#feedbackDiv").append("<br /><H1>UPLOADS COMPLETE</H1>");
                  } 
            });
        }
        
        
        
        
   })     
   
     return false;
  }
  
  function undoArchive(uid,msgno){
    
    thisRow = $("#undoArch_"+uid).parent("td").parent("tr");
    origHTML = thisRow.attr("origHTML");
    thisRow.html("<td colspan='6'><h1 style='text-align:center'>Undoing ... <img src='../pic/ajax-loader.gif' /></h1></td>");  
    $.post("moveMail.php",{mailBox:"<?php echo $undoMoveMailBox ?>",uid:uid,msgno:msgno}, 
      function(data) {  
//         alert(data);
        thisRow.html(origHTML) 
      });
    
    
    return false;
  
  }
  
  $(document).ready(function(){  
            
            
            $("#switchFolder").click(function(){
               var goTo; 
               if($(this).attr("currentFolder") == "INBOX"){
                  goTo = 'DOCUMENT_ARCHIVES';
               }else{
                  goTo = 'INBOX';
               }
               window.location.href = '?folder='+goTo ;
               return false;
            })
            
            
            $(".archiver").live("click",function(){
              uid = $(this).attr("uid");           
              msgno = $(this).attr("msgno");
              thisRow = $(this).parent("td").parent("tr");
              thisRow.removeClass("emailLink"); 
              thisRow.attr("origHTML", thisRow.html());   
              thisRow.html("<td colspan='6'><h1 style='text-align:center'>Moving ... <img src='../pic/ajax-loader.gif' /></h1></td>");  
               $.post("moveMail.php",{mailBox:"<?php echo $mailbox ?>",uid:uid,msgno:msgno}, 
                function(data) {  
//                   alert(data);
                  thisRow.html("<td colspan='4'><h1 style='text-align:center'><!--"+data+"-->Complete</h1></td><td colspan='2'><button style='height:100%; width:100%' id='undoArch_"+uid+"' uid='"+uid+"' onclick='undoArchive("+uid+","+msgno+")'>Click To Undo</button>")
                  
                });
                         
//               alert(uid);
              return false;
            })
  
            $("#closeUploadNotifier").click(function(){
              $("#uploadNotifier").slideUp();
            })
  
            setWidth = $("body").width() - 25;   
            setHeight = $("body").height() - 25;
             
            
//             enable_modals()       
//             $(".rx_modal").fancybox( {
//                                     'overlayOpacity' : 0.0,
//                                     'showCloseButton' : true, 
//                                     'centerOnScroll' : false,
//                                     'transitionIn' : 'elastic',
//                                     'autoDimensions' : false,
//                                     'width' : 600,
//                                     'height' : 400 
//                                 });
  
  
              var timer; 
        		  timer = setInterval(function(){  
//                 if($(".patID").length > 0){
                  anyValuesSet = false;
                  $(".patID").each(function(index, value){
                     origVal = $(this).attr("compareVal");
                     currentVal = $(this).val();
                     if($(this).is(":visible") && currentVal != ""){
                        anyValuesSet = true;
                     }
                     if(origVal != currentVal){
                          $(this).attr("compareVal",$(this).val());
                          $(this).trigger("change")
                     }                     
                  })
                  if(anyValuesSet){
                    $("#multiLinkButton").removeAttr("disabled")
                  }else{
                    $("#multiLinkButton").attr("disabled","disabled")
                  }
//                 }
                //compareVal="" 
          		}, 500);
              
    
    $(".patID").live("change",function(){  
//       alert("Fsdfsd")
        newID = $(this).val();
        loopID = $(this).attr("loopID");
        patName = $("#new_patient_name_"+loopID).val();
//         alert(patName);
      $(".patID").each(function(){
        if($(this).is(":visible")){
          if($(this).val().trim() == ''){
            $(this).val(newID)
            thisLoopID = $(this).attr("loopID");
            $("#new_patient_name_"+thisLoopID).val(patName);
          }  
        }else{
             
        }
      })
    })
    
     
 
    
    
    $("#selectAll").click(function(){
      $(".multiLink").each(function(){
        if($(this).is(":checked")){
         // do nothing 
        }else{
          $(this).trigger("click");
        }
      }) 
    })  
    
    $(".multiLink").live("click",function(){
      // check if it checked, if it is then allow description
      var thisCheckbox = $(this); 
      if (thisCheckbox.is(':checked')){
        var div1Width = thisCheckbox.parent().width();  
        var div2Width = thisCheckbox.parent().parent().width();
        var textareaNewWidth = div2Width - div1Width - 20;       
        thisCheckbox.parent().parent().children("div.textAreaHolder").children(".patID").show();   
        thisCheckbox.parent().parent().children("div.textAreaHolder").css("width",textareaNewWidth); 
//         thisCheckbox.parent().parent().children("div.makeMeClear").css("clear","both");               
        thisCheckbox.parent().parent().children("div.textAreaHolder").slideDown("slow"); 
      }else{                                                                   
        thisCheckbox.parent().parent().children("div.textAreaHolder").children(".patID").hide();  
        thisCheckbox.parent().parent().children("div.textAreaHolder").slideUp("slow");   
//         thisCheckbox.parent().parent().children("div.makeMeClear").css("clear","none");        
      }
    })                     
    $(".emailLink").live("click",function(){
      $(".emailLink td").css("background-color","white");   
      $(this).children("td").css("background-color","rgb(198,198,198)");
      typeof(lastGet) != "undefined" && variable !== null
      if(typeof(lastGet) != "undefined" && variable !== null) {
          lastGet.abort();
      } 
//       var url = "<?php echo $pageURL ?>"+$(this).attr("url");
      var url = $(this).attr("url");
       
      $("#emailContentHolder").html("<br /><p style='text-align:center; padding:50px;'>FETCHING EMAIL CONTENT ... <br /><img src='../pic/ajax-loader.gif' /></p>")
   
//       alert(url);
      if(xhr && xhr.readystate != 4){
        xhr.abort();
      }
      xhr = $.get(url, function(data) {
         $("#emailContentHolder").html(data);
      });
    })
    
    // interface/main/calendar/find_patient_popup.php
  })
  
   
</script> 
<style type="text/css">
  .emailLink td{background-color:white}
  .emailLink:hover td{background-color:rgb(198,198,198) !important}
  .emailLink{cursor:pointer}    
</style>  
<body style="padding:20px;">

<?php 
$list = imap_getmailboxes($mbox, '{'.$host.':'.$port.'}', "*");
// print_r($list);
$hasArchiveFolder = false;
    foreach ($list as $key => $val) {                       
        $folderName = str_replace('{'.$host.':'.$port.'}', '', imap_utf7_decode($val->name));   
        if($folderName == 'INBOX.DOCUMENT_ARCHIVES'){
          $hasArchiveFolder = true;
        }
    }  
    if(!$hasArchiveFolder){
      // create DOCUMENT_ARCHIVES folder
      imap_createmailbox($mbox, imap_utf7_encode("{imap.example.org}INBOX.DOCUMENT_ARCHIVES"));
    }  

$check = imap_check($mbox); 
$page = (isset($_GET['pagenum']) && intval($_GET['pagenum']) > 1 ? $_GET['pagenum'] : 1);

// echo $page;

// echo ' -> '.$check->Nmsgs.'<br />';

$display = 3;
$end = ($page * $display);
$start = 1+$end - $display; 
$end = ($end > $check->Nmsgs ? $check->Nmsgs : $end);
// echo $start.' | '.$end; 
$overviews = imap_fetch_overview($mbox,"1:$check->Nmsgs");

// echo '{'.$host.':'.$port.'}'.$mailbox.','.$user.','.$password.'<hr />';
// print_r($overviews);

if(isset($_GET['uid']) && isset($_GET['msgno']) && isset($_GET['i'])){
  $uid = intval($_GET['uid']); 
  
  $i = 0;
  foreach($overviews as $overV){
//     print_r($overV);echo '<br />'.$overV->uid.'<hr />';
    if($overV->uid == $uid){
       $messageNumber = $overV->msgno;
//        echo $i.'<hr />';
       break;
    }
    $i++;
  }   
  
//   echo $i.'<hr />';
  
//   print_r($overviews[$i]);
  
  $subject = $overviews[$i]->subject;     
  $from = $overviews[$i]->from;            
  $date = $overviews[$i]->date;
  echo '<h4>'.xlt('From').' : '.$from.'<br />'.xlt('Subject').' : '.$subject.'<br />'.xlt('Date').' : '.$date.'</h4>';
  $charset = '';
  $htmlmsg = '';
  $plainmsg = '';
  $attachments = '';
  $msg = getmsg($mbox,$messageNumber);
  echo $htmlmsg;
  if(!empty($attachments)){ 
    echo '<blockquote>'; 
    echo '<br />'.xlt('ATTACHMENTS').' : <br />';
    echo '<form name="patient_links">'; 
    
    $loopID = 0;
    foreach($attachments as $fileName=>$encodedFile){
      $loopID++;
      $attachmentExtension = strtolower(end(explode('.', $fileName)));
      
      echo '
          <div id="containerDiv"> 
            <div class="makeMeClear" style="clear:both"> </div>
            <div style="float:left; text-align:center; width:120px;">
              
              <a class="iframe rx_modal" href="fetchAttachment.php?msgno='.$messageNumber.'&fileName='.$fileName.'&noThumb&mailbox='.$mailbox.'" target="_attachment">';
        
              if($attachmentExtension == 'jpg' || $attachmentExtension == 'jpeg' || $attachmentExtension == 'png' || $attachmentExtension == 'gif'){
                echo '<img style="width:100px" src="fetchAttachment.php?msgno='.$messageNumber.'&fileName='.$fileName.'&mailbox='.$mailbox.'" />';
              }else{
                echo '<img src="attachmentIcon.jpg" />';
              }
       
       $categories = '';
       foreach($cats as $cat){                        
        $categories .= '<option value="'.$cat['id'].'" '.($cat['name'] == 'Medical Record' ? 'selected="selected" ' : '').'>'.$cat['name'].'</option>';
       }
                  
       
       echo  '</a> 
              <br /><label for="checkMe_'.$loopID.'">'.$fileName.'</label><br />
              <input type="checkbox" id="checkMe_'.$loopID.'" class="multiLink" msgno="'.$messageNumber.'" file="'.$fileName.'" mailBox="'.$mailbox.'" />
              </div>
            <div loopID="'.$loopID.'" class="textAreaHolder" style="display:none; width:0; margin-top:5px; float:left; text-align:center;">
              
              Patient : 
              <input readonly compareVal="" loopId="'.$loopID.'" id="new_patient_name_'.$loopID.'" name="new_patient_name_'.$loopID.'"  class="patName" />
              
              <input compareVal="" loopId="'.$loopID.'" name="new_patient_id_'.$loopID.'" id="new_patient_id_'.$loopID.'" class="patID" size="4" type="text" />
              
              <!--<a href="javascript:{}" onclick="top.restoreSession();var URL=\'../../controller.php?patient_finder&find&form_id=patient_finder[&quot;new_patient_id_'.$loopID.'&quot;]&form_name=patient_finder[&quot;new_patient_name_'.$loopID.'&quot;]\'; window.open(URL, \'document_move\', \'toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=450,height=400,left=425,top=250\');">-->
              <a href="javascript:{}" onclick="top.restoreSession();var URL=\'../../controller.php?patient_finder&amp;find&amp;form_id=patient_links%5B%27new_patient_id_'.$loopID.'%27%5D&amp;form_name=patient_links%5B%27new_patient_name_'.$loopID.'%27%5D\'; window.open(URL, \'document_move\', \'toolbar=0,scrollbars=1,location=0,statusbar=1,menubar=0,resizable=1,width=450,height=400,left=425,top=250\');">
              <img src="../../images/stock_search-16.png" border="0">
              </a><br />
              <br />
              Filename : <input id="fileName_'.$loopID.'" class="fileName" value="'.$fileName.'"/>
              <br /><br />
              Category : <select id="category_'.$loopID.'" class="category">'.$categories.'</select>
              <br /><br />
              Notes :<br />     
              <textarea id="description_'.$loopID.'" class="descriptionTA" style="width:100%; height:150px;">'.$subject.PHP_EOL.'-----------'.PHP_EOL.$plainmsg.'</textarea>
            </div>  
            <div class="makeMeClear" style="clear:both"> </div> 
          </div>
          ';
    } 
    echo '</form></blockquote><div style="clear:both">&nbsp;</div>';
    if(count($attachments) > 1){
      echo '<button id="selectAll">'.xlt('Select All').'</button><br /><br />';      
    }
      echo '<button id="multiLinkButton" onclick="linkSelected(); return false;" >'.xlt('Link Selected Attachments').'</button>'; 
       
  }
  
  echo '<hr />';
  die;
} 



// print_r($overviews); 
// if($check->Nmsgs > $display){
//   echo 'more pages';
// }
echo '<h1>'.xlt(ucwords(strtolower(str_replace('_',' ',str_replace('INBOX',' ',$mailbox)))).' for').' '.$thisEmail.'</h1>';
echo '<button id="switchFolder" currentFolder="'.$mailbox.'">'.xlt('Show').' '.($mailbox == "INBOX" ? xlt('Document Archives') : xlt('Inbox')).'</button><br />';
echo '<table class="tablesorter" cellpadding="10" border="2" style="width:50%; float:left;">';
echo "<thead><tr><th></th><th><img src='attachmentIcon.png' width='16px'/></th><th>".xlt('From')."</th><th>".xlt('Subject')."<th>".xlt('Date')."</th><th>".xlt('Delete')."</th></tr></thead><tbody>";

 
 $i = 0;
 $sortedOverviews = array();
foreach($overviews as $data){
//    = $i;
  $sortedOverviews[$data->udate] = $data;  
//   $sortedOverviews[$data->udate]["i_index"] = $i; 
    
}   
//  krsort($sortedOverviews);    


 
 
foreach($sortedOverviews as $data){
   
//   print_r($data);
//   echo '<hr />'; 
  $subject = $data->subject;     
  $from = $data->from;            
  $date = $data->date;
  $uid = $data->uid;      
  $msgno = $data->msgno;
  $s = imap_fetchstructure($mbox,$msgno);     
//     echo $subject.'<br /><br />';print_r($s); 
  $numberOfAttachments = 0;
  foreach ($s->parts as $partno0=>$p){
//     echo '<br />'.$partno0.':<br /><blockquote>';
//     print_r($p);
//     echo '</blockquote>';
    $params = array();
    if (isset($p->parameters)){
        foreach ($p->parameters as $x){
            $params[strtolower($x->attribute)] = $x->value;
//             echo '<br /><br />parameters:<br /><br />'.$x->attribute.' = '.$x->value;
        }
    }
    
    if (isset($p->dparameters)){
        foreach ($p->dparameters as $x){
            $params[strtolower($x->attribute)] = $x->value; 
//             echo '<br /><br />dparameters:<br /><br />'.$x->attribute.' = '.$x->value;
        }
    }
    
    if(isset($p->parts)){
       foreach ($p->parts as $partno0=>$q){
          if (isset($q->parameters)){
            foreach ($q->parameters as $z){
                $params[strtolower($z->attribute)] = $z->value;
//                 echo '<br /><br />parameters:<br /><br />'.$z->attribute.' = '.$z->value;
            }
          }
       }
    }  
    if (isset($params['filename']) || isset($params['name'])) {
      $numberOfAttachments ++;
    } 
  } 
//   echo '<hr />';      
  $url = "?uid=$uid&msgno=$msgno&i=$i";
//   echo $url.'<br />';
//   print_r($data); echo '<hr />';
  $readIcon = ($data->seen ? 'read.png' : 'unread.png');
  
  echo "<tr title='".xla('click to open this email')."' class='emailLink' url='$url'>
          <td class='emailLink'><img src='$readIcon' /></td>
          <td>$numberOfAttachments</td>
          <td>$from</td>
          <td>$subject</td>               
          <td>".date("d/m/Y",strtotime($date))."</td>
          <td><button class='archiver' msgno='$msgno' uid='$uid'>".($mailbox == "INBOX" ? xlt("Archive") : xlt("Un-archive"))."</button></td></tr>"; 
 $i++;
}  
echo '</tbody></table>';    


echo '<div id="emailContentHolder" style="float:right; width:49%"></div>';



?>
<div id="uploadNotifier" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background-color:grey; background-color:rgba(0,0,0,0.5);">
  <div style="width:50%; height:50%; margin:15% auto; background-color:white; border-radius:5px; text-align:center; overflow:scroll">
     <h1 style="text-align:center; padding-top:15%">
      <?php echo xlt('Uploading Files') ?>...
      <br /><br /><br /><img src='../pic/ajax-loader.gif' />
    </h1>
    <div id="feedbackDiv"></div>
    <br /><br />
    <button id="closeUploadNotifier"><?php echo xlt('Close') ?></button>
  </div>
</div>
</body>

<?php




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
?>