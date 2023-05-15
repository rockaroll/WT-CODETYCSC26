<?php 
$start ="http://localhost/Assignments/Course_Project/test.html";
$already_crawled=array();
$crawling=array();
function establish_connection(){
    $servername="localhost:3306";
    $username="root";
    $password="";
    $conn=new PDO("mysql:host=$servername;dbname=searchdata",$username,$password);
    
    return $conn;
}
function get_details($url){
    global $title;
    try{
    $options = array('http'=>array('method'=>"GET",'headers'=>"User-Agent: WCrawl/0.1\n"));
    $context = stream_context_create($options);
    $doc = new DOMDocument();
  
    @$doc->loadHTML(@file_get_contents($url,false,$context));
   $title = $doc->getElementsByTagName("title");
    $title =$title->item(0)->nodeValue;
   
    // if($title->item(0)->nodeValue != NULL and !empty($title->item(0)->nodeValue)){
    //     $title = $title->item(0)->nodeValue;
    // }else{
    //     $title = "No title";
    // }
    
    $description = "";
    $keywords="";
    $metas= $doc->getElementsByTagName("meta");
    // for ($i=0;$i<$metas->length;$i++){
    //     $meta = $metas->item($i);
    //     if($meta->getAttribute("name")==strtolower("description")){
    //         $description=$meta->getAttribute("content");
            
    //   }    
    // }
    foreach($metas as $meta){
        if($meta->getAttribute("name")==strtolower("description")){
             $description=$meta->getAttribute("content");
        }
        if($meta->getAttribute("name")==strtolower("keywords")){
            $keywords=$meta->getAttribute("content");
       }
    }
    // echo $keywords;
    // $e=establish_connection();
    // insrt($e,$title,$description,$keywords,$url);
    return '{"Title":"'.$title.'","Description":"'.str_replace("\n","",$description).'","Keywords":"'.$keywords.'","URL":"'.$url.'"}';
    

}
catch(Error $c){
    if($title==""){
        return '{"Title":"","Description":"","Keywords":"","URL":""}';
    }
}
}
global $e;
$e=establish_connection();
function follow_links($url){
    try{
    global $already_crawled;
    global $crawling;
    global $doc;
    $options = array('http'=>array('method'=>"GET",'headers'=>"User-Agent: WCrawl/0.1\n"));
    $context = stream_context_create($options);
    $doc = new DOMDocument();
  
    @$doc->loadHTML(@file_get_contents($url,false,$context));
    $linklist=$doc->getElementsByTagName("a"); 
    foreach ($linklist as $link){
       $l = $link->getAttribute("href");

       if(substr($l,0,1) == "/" && substr($l,0,2) != "//"){
           $l=parse_url($url)["scheme"]."://".parse_url($url)["host"].$l;
       }
       else  if(substr($l,0,2) == "//"){
        $l=parse_url($url)["scheme"].":".$l;
    }
    else if(substr($l,0,2) == "./"){
        $l=parse_url($url)["scheme"]."://".parse_url($url)["host"].dirname(parse_url($url)["path"]).substr($l,1);
    }
    else if(substr($l,0,1) == "#"){
        $l=parse_url($url)["scheme"]."://".parse_url($url)["host"].parse_url($url)["path"].$l;
     }
     else if(substr($l,0,1) == "../"){
        $l=parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
     }
     else if(substr($l,0,11) == "javascript:"){
        continue;
     }
     else if(substr($l,0,5)!="https" && substr($l,0,4)!="http"){
        $l = parse_url($url)["scheme"]."://".parse_url($url)["host"]."/".$l;
     }
     if (!in_array($l,$already_crawled)){
       $already_crawled[] = $l;
       $crawling[]=$l;
       $details= json_decode(get_details($l));
    //   print_r($details)."\n";
       $e=establish_connection();
       echo $details->URL." \n";
       $rows = $e->query("SELECT *FROM `searchresultsa` where url='".$details->URL."'");
       $rows = $rows->fetchColumn();
      
       $params =array(':title' => $details->Title,':description' => $details->Description,':keywords' => $details->Keywords,':url' => $details->URL);
       if($rows>0){
        if(!is_null($params[':title'])  && $params[':title']!=''  ){
            $result= $e->prepare("UPDATE `searchresultsa` SET Title=:title , Description=:description , Keywords=:keywords , Url=:url  WHERE Url=:url");
            $result = $result->execute($params);
          }
       }
       else {
        if(!is_null($params[':title'])  && $params[':title']!='' ){
          $result= $e->prepare("INSERT INTO `searchresultsa` values( '', :title , :description , :keywords , :url )");
          $result = $result->execute($params);
        }
        else if(is_null($params[':title'])  || $params[':title']==''||is_null($params[':url'])  || $params[':url']==''||is_null($params[':description'])  || $params[':description']==''||is_null($params[':keywords'])  || $params[':keywords']==''){
            print_r($details)."\n";
         }
       }

    //  echo get_details($l)."\n";
    // echo $l."\n";
    }
    }
    array_shift($crawling);
     foreach ($crawling as $site){
         follow_links($site);
    }
}
catch(Error $e){
    if( @$doc->loadHTML(@file_get_contents($url,false,$context))==null){
        return '{"Title":"","Description":"","Keywords":"","URL":""},';
    }
    else if(is_null($params[':title'])  || $params[':title']=='' || is_null($params[':url'])  || $params[':url']==''){
        return '{"Title":"","Description":"","Keywords":"","URL":""},';
    }
}
}
//  foreach ($crawling as $site){
//      follow_links($site);
//  }

 follow_links($start);

// print_r($already_crawled);
?> 