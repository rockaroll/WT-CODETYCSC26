<?php 
function establish_connection(){
    $servername = "localhost:3306";
    $username = "root";
    $password = "";
    $conn = new PDO("mysql:host=$servername;dbname=searchdata", $username, $password);
   
    return $conn;
}

$e = establish_connection();

$search = $_POST['q'];
echo "<pre>";
$searche = explode(" ", $search);

$x = 0;
$construct = "";
$params = array();
foreach($searche as $term){
    $x++;
    if($x == 1){
        $construct .= "(Title LIKE CONCAT('%',:search$x,'%') OR Description LIKE CONCAT('%',:search$x,'%') OR Keywords LIKE CONCAT('%',:search$x,'%'))";
    }
    else{
        $construct .= " AND (Title LIKE CONCAT('%',:search$x,'%') OR Description LIKE CONCAT('%',:search$x,'%') OR Keywords LIKE CONCAT('%',:search$x,'%'))";
    }
    $params[":search$x"] =$term;
}

$results = $e->prepare("SELECT * FROM `searchresults` WHERE $construct");
$results->execute($params);

if($results->rowCount() == 0){
    echo "0 Results found! <hr />";
}
else{
    echo $results->rowCount(). " results found! <hr />";
}
foreach($results->fetchAll() as $result){
    echo $result["Title"]."<br />";
    if($result["Description"]=""){
        echo "No description Available"."<br />";
    }
    else{
        echo $result["Description"]."<br />";
    }
    
    echo "<a href=".$result["Url"].">".$result["Url"]."</a>"."<br />";
    echo "<hr />";
}
// print_r($results->fetchAll());
?>