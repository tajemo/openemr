<?php  
  require_once("../globals.php"); 
  require_once("$srcdir/acl.inc");
  require_once("$srcdir/api.inc"); 
  
  
  $thisauth = acl_check('patients', 'docs');
  
  if(!$thisauth) die(xlt("you do not have suffient privilages to access this page"));
  
  
  $fromLinkFile = true;
  $_GET['msgno'] = $_POST['msgno'];    
  $_GET['fileName'] = $_POST['file']; 
  
//   print_r($_POST);  
//   die;
// [OE_SITE_DIR]
// [include_root] 

/* [msgno] => 1 [file] => site_logo.JPG [mailBox] => INBOX [loopID] => 1 [patID] => 3 [fileName] => site_logo.JPG [category] => 3 [description] */
        
        $webAddress = ($_SERVER['HTTPS'] == 'on' ? 'https' : 'http').'://';
        if ($_SERVER["SERVER_PORT"] != "80") {
          $webAddress .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].'/interface/fetchDocsFromEmail/';
         } else {
          $webAddress .= $_SERVER["SERVER_NAME"].'/interface/fetchDocsFromEmail/';
         } 
         
        $patID = trim($_POST['patID']);
        $fileName = trim($_POST['fileName']);   
        $category = trim($_POST['category']);     
        $description = trim($_POST['description']);  
        $file =  trim($_POST['file']);
         
         
        require_once("fetchAttachment.php");   
        
        
         
        $saveToFileName =  $fileName;
        $withoutExt = preg_replace("/\\.[^.\\s]{3,4}$/", "", $saveToFileName);   
        $fileExtension = strtolower(end(explode('.', $fileName)));

        $i = 1;
        while(is_file($OE_SITE_DIR.'/documents/'.$patID.'/'.$saveToFileName)){
           $saveToFileName = $withoutExt.$i.'.'.$fileExtension;
           $i++;
        }   
        
/////////////////////////////////////////////
//
////////// FOR TESTING
//
///////////////////////////////////////////////
//         $saveToFileName = 'file.jpg';
//         $rawFile = file_get_contents($OE_SITE_DIR.'/documents/'.$patID.'/'.$saveToFileName); 
/////////////////////////////////////////////
//
////////// END FOR TESTING
//
///////////////////////////////////////////////
        
        $localFile = $OE_SITE_DIR.'/documents/'.$patID.'/'.$saveToFileName;   
         
        
        if (!is_dir($OE_SITE_DIR.'/documents/'.$patID)) {
          // dir doesn't exist, make it             
          mkdir($OE_SITE_DIR.'/documents/'.$patID);
        } 
        file_put_contents($localFile, $rawFile); 
        
        $fileHash = sha1_file($localFile); 
        
        
        
        if (function_exists('finfo_file')) {
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          $type = finfo_file($finfo, $localFile);
          finfo_close($finfo);
        }else{
          $type = mime_content_type($localFile);
        } 
         
        
        $fileSize = filesize($localFile);
        $fileName = 'file:///'.$OE_SITE_DIR.'/documents/'.$patID.'/'.$saveToFileName;
         
        $docID = generate_id();   
        // insert the categories_to_documents link
        sqlInsert("INSERT INTO categories_to_documents (category_id, document_id)VALUES(?, ?);",array($category,$docID));  
        
        // insert the document 
        $args = array($docID,$fileSize,$fileName,$type,$_SESSION['authUserID'],$patID,$fileHash,$category);
        $insertSQL = "INSERT INTO documents 
                      (id, type, size, date, url, mimetype, pages, owner, revision, foreign_id, docdate, hash, list_id)
                      VALUES
                      (?, 'file_url',?,NOW(),?,?,'1',?,NOW(),?,NOW(),?,?);
                      ";  
                      
        sqlInsert($insertSQL,$args);
        
          // insert the note
          if($description != ''){
            $noteID = generate_id ();
            sqlInsert("INSERT INTO notes (id, foreign_id, note, owner, date, revision)VALUES(?, ?, ?, '', NOW(), NOW());",array($noteID,$docID,$description));  
        
          }
        echo $saveToFileName;
                      
//                                
//         echo $issues_options;//$OE_SITE_DIR.'/documents/'.$patID.'/'.$saveToFileName;
   
?>