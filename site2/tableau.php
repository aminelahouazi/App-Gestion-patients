<?php

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "webmed";
session_start(); 
$conn = new mysqli($host, $user, $pass, $dbname);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$medecin_id = $_SESSION['medecin_id'];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nom'], $_POST['prenom'],$_POST['sexe'], $_POST['date_naissance'],$_POST['telephone'], $_POST['email'], $_POST['adresse'])) {
//nouveau enregistrement d'un patient
$stmt = $conn->prepare("INSERT INTO patients (nom, prenom,sexe, date_naissance, telephone, email, adresse) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss",
$_POST['nom'],
$_POST['prenom'],
$_POST['sexe'],
$_POST['date_naissance'],
$_POST['telephone'],
$_POST['email'],
$_POST['adresse']
);
$stmt->execute();
$patient_id = $conn->insert_id;
$stmt->close();

//relation entre medecin et patient
$stmt = $conn->prepare("INSERT IGNORE INTO medecin_patient (medecin_id, patient_id) VALUES (?, ?)");
$stmt->bind_param("ii", $medecin_id, $patient_id);
$stmt->execute();
$stmt->close();

}
else if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['supprimer_id'])) {
//supprimer un patient (tous les enregistrement qui ont une relaton seront aussi supprimer)
    $idsup = intval($_POST['supprimer_id']);
    $qsup = "DELETE FROM patients WHERE id = ?";
    $stmt = $conn->prepare($qsup);
    $stmt->bind_param("i", $idsup);

    if ($stmt->execute()) {
        echo "";
    } else {
        echo "" . $conn->error;
    }

    $stmt->close();
} 


if (isset($_GET['patientrch'])) {
    $id = intval($_GET['doc_id']);
    $stmt = $conn->prepare("SELECT filename, filetype, filedata FROM documents WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($filename, $filetype, $filedata);
        $stmt->fetch();
        header("Content-Type: $filetype");
        header("Content-Disposition: attachment; filename=\"$filename\"");
        echo $filedata;
    } else {
        echo "Fichier introuvable.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patientrch'])) {
    $search = htmlspecialchars(strip_tags($_POST['patientrch']));
    
    $stmt = $conn->prepare("SELECT * FROM `$user_table` WHERE nom LIKE ? OR prenom LIKE ?");
    $search_param = '%' . $search . '%';
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();


}

$stmt = $conn->prepare("
SELECT DISTINCT rendez_vous.date_rdv, patients.*  
FROM rendez_vous
JOIN patients ON patients.id = rendez_vous.patient_id
WHERE rendez_vous.medecin_id = ? 
  AND rendez_vous.date_rdv = ?
");
date_default_timezone_set('UTC');
$auj = date("Y-m-d");
$stmt->bind_param("is", $medecin_id, $auj);
$stmt->execute();
$result = $stmt->get_result();


?>

<!DOCTYPE html>
<html lang="fr">
<head>

    <meta charset="UTF-8">
    <title>Tableau de bord</title>
    <style>
        @font-face {
        font-family: myfont;
        src: url(res/LobsterTwo-BoldItalic.ttf);
    }
        header{
            font-weight: bold;
        /*background-color:rgb(8, 15, 81);*/
        height:70px;
        width: 100%;
        top: 0;
        position: fixed;
        background-color:rgb(221, 239, 245);
        color:rgb(8, 15, 81);
        font-size:large;
        padding-right:3px;
        padding-top:8px;
        display: flex; /* Utilisation de flexbox */
        justify-content: flex-end; /* Aligne tous les éléments à droite */
        align-items: center; /
        margin-top:none;
        border:1px solid rgb(191, 58, 107);
        margin-left:0;
        float:left;
        }
        .logo{
        display: flex;
        align-items:center;
        margin: 0;
        }
        .div3{
        display: flex;
        }
        .logopic{
        width: 90px;
        }
        .nom{
        font-size:30px;
        display:flex;
        gap:5px;
        font-family: myfont;
        color:rgb(76, 163, 210);
        }
        .W{
        color:rgb(191, 58, 107);
        font-size:35px;
        }
        .M{
        color:rgb(191, 58, 107);
        font-size:35px;
        }
        h3{
            margin-top: 10%;
        }
      


body { 
    font-family: "Ubuntu Mono", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;         
          
            display: flex;
             background:  rgb(255, 255, 255);
             margin:0;
             background: radial-gradient(circle, rgb(245, 233, 238) 0%, rgb(248, 203, 221) 38%, rgb(214, 226, 241) 98%);
            }
            
         #div1 , #div2{
            top: 10dvh;
            position: relative;
            
         }
         #div2{
            width: 30%;
            display: block;
         }
         #div1{
            width:60%;
         }
         
       
   /*.form1 {
    height: 75%;
    width: 56%;
    margin-top: 3px;
    border: 2px solid;
    background-color: rgb(221, 239, 245);
    border: 2px solid rgb(191, 58, 107);
    z-index: 99;
    width: 56%;
    border-radius: 15px;
    display: none;
    box-shadow: rgba(255, 0, 0, 0.3) 0px 17px 25px 5px;
    position: fixed;
    left: 25%;
    color: rgb(8, 15, 81);
    top: 25px;
    
  }*/
  .form1 {
          /*z-index: 99;
          height: 85%; 
          width: 56%; 
          top:8dvh;
          display: none; 
          border: 2px solid rgb(191, 58, 107);
          position: fixed;
          left: 25%;
          box-shadow: rgba(255, 0, 0, 0.3) 0px 17px 25px 5px;
          /*background:rgb(108, 195, 241);*
          background-color:rgb(221, 239, 245);
          color: rgb(8, 15, 81);
          border-radius:15px;*/
          height: 75%;
    width: 56%;
    margin-top: 3px;
    border: 2px solid;
    background-color: rgb(221, 239, 245);
    border: 2px solid rgb(191, 58, 107);
    z-index: 99;
    width: 56%;
    border-radius: 15px;
    display: none;
    box-shadow: rgba(255, 0, 0, 0.3) 0px 17px 25px 5px;
    position: fixed;
    left: 25%;
    color: rgb(8, 15, 81);
    top: 25px;
  }
  #nom{
    margin-left:400px;
  }
  #prenom{
    margin-left:400px;
  }
  #date_naissance{
    margin-left:400px;
  }
  #email{
    margin-left:400px;
  }
  #telephone{
    margin-left:361px;
  }
  #adresse{
    margin-left:400px;
  }

  /*.form1 input {
    font-weight: bold;
    border: none;

  }*/
  .form1 input {
    border: 2px solid rgb(191, 58, 107);
    height:29px;
     border-radius: 10px;
     font-weight:bold;
  }
  .form1 input:hover{
    background-color: rgb(252, 178, 212);
  }

  .form1 h3 {

    margin: 0% 35%;
    /*margin-bottom: 5%;*/
    margin-bottom: 8%;
    margin-top: 2%;
    margin-right: 20px;
    color: rgb(191, 58, 107);
    font-size: 22px;
  }
  .form1 hr{
      border: 2px solid rgb(191, 58, 107);
width: 90%;
  margin: 0 5%;
   
  }
.form1 section:not(.choix,.btndoc) {
    display: flex;
 margin: 0;
  
  }
  .form1 h4 {

 
    width: 30%;
    color: rgb(191, 58, 107);
    font-size: 14px;
   word-wrap: break-word;

  }
  .form1 p {
   width: 15em;
   word-wrap: break-word;
    margin: 0;
   
  }

  .form1 a {
    margin: 0;
   width: 5%;
    margin-left: 20px;
  }

  .form1 label {

    position: absolute;
    margin: 0px 20%;
    margin-bottom: 2rem;
    /*position: absolute;
    margin: 0px 20%;
    margin-bottom: 2rem;*/

  }

  .form1 .x {
    margin: 2% 80%;
    cursor: pointer;
    margin-bottom: 2rem;
  }

  .form1 * {
    display: block;
    width: 30%;
    margin: 10px 45%;
    margin-bottom: 30px;
    margin-top: -10px;
  }

  .choix {
    display: flex;
   
    margin: 0 0%;
    margin-left: 375px;
    margin-top: 20px;
  
  }

  .choix label {
    position: relative;
    margin: 0px;
    

  }

  .choix input {
    margin: 0%;
   
    margin-bottom: 20px;

   
  }
.titre { 
    color:rgb(255, 255, 255);
    font-size: x-large;
    position: absolute;
    left: 5%;
    top: 2dvh;}
.barech {  
    /*position: absolute; 
    left: 25%;
    top: 2.5dvh; */
    display: flex;
    gap:5px;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: 20px;
    z-index: 5;
}
.barech button{
  
    border-radius: 50%;
    align-items: center;
    color: black;
    border: 0px solid;
    border-radius: 20px;
    transition: width 0.3s ease, border-radius 0.3s ease;
    width: 30px;
    height: 30px;
    overflow: hidden;
    padding: 5px;
    white-space: nowrap;
    margin-right: 2dvw;
}
.barech input {
  
    width:40dvw;
     height: 4dvh; 
    align-items: center;
    border: 0px solid;
    border-radius: 20px;
    transition: width 0.3s ease, border-radius 0.3s ease;
    overflow: hidden;
    padding: 5px;
    white-space: nowrap;
}
.buttons { 
  
    display:flex;
     right: 0;
     margin:1px;  
    }
       #add-form{
        display: none; 
       }
       #aj-form{
        display: none;
        
       }
       #edit-form{
        display: none;
        
       }
       
        
button { padding: 8px; cursor: pointer; }
.container-scroll {
    
    transition: all 0.5s ease-in-out;
    font-size: 16px;
    white-space: nowrap;
    font-weight: 900;
  color: rgb(255, 255, 255);
    text-align: center;
    padding-top: 50px;
  /* border-radius: 10px; */
  border: 3px solid rgb(255, 255, 255);
  background-size: 60px 60px;
  background-position: 0 0, 0 0;
  /*background-color: #f0f0f0;*/
  background-color:rgb(226, 94, 143);
  box-shadow: rgba(0, 0, 0, 0.3) 0px 30px 30px -10px;
   max-height: 60%;
   min-height: 50%;
  overflow-y: visible;
  overflow-x: hidden;
  border: 3px solid white;
 
  width: 60%; margin: 5% 25%;
  

}
.rows{
  height: 25%;
  width: 58.3%;
  z-index: 1;
  display: block;
 position: absolute;
  top:10.8%;
  
  background-color:rgb(226, 94, 143);
  color:rgb(226, 94, 143);
  
}

.scroll-header, .scroll-row {
  display: grid;
  grid-template-columns: 1fr 1fr 0.5fr;
  width: inherit;
 
 
}

.scroll-header {
    background: rgb(45, 200, 247);
 padding-top: 60px;
  position: sticky;
  border: 1px solid white;
  top: 0;
  z-index: 2;
  font-size: 20px;
  width: 100%;
}
.scroll-row{
  z-index: 0;
   padding: 0;
    width: 100%;
    background: rgba(4, 193, 250, 0.732);

}
.scroll-row div{
  z-index: 0;

    padding: 15px;
    border: 1px solid white;
   
}
.scroll-header div{
    padding: 15px;

    border: 1px solid white;
    border-bottom: 0px;
    border-top: 0px;
}

.scroll-title {
    /*color: rgb(4, 193, 250);*/
    color:rgb(191, 58, 107);
  font-size: 15px;
  font-weight: 900;
  background: white;
  border: 1px solid rgb(7, 185, 255);
  /* border-radius: 10px; */
  padding: 10px;
  transform: translate3d(0px, 0px, 80px);
  box-shadow: rgba(100, 100, 111, 0.2) 0px 17px 10px -10px;
position: absolute;
z-index: 20;
margin: 7% 70%;
height: 45px;
}
.container-scroll button{
    border: 0px;
    cursor: pointer;
  
  display: inline-block;
  font-weight: 900;
  font-size: 9px;
  text-transform: uppercase;
  color: rgb(7, 185, 255);
  width: 100%;
  /* border-radius: 5px; */
  background: white;
  padding: 0.5rem 0.7rem;
  transform: scale(100%,100%) translate3d(0,0px,0);
  transition: all 0.5s ease-in-out;
}
.container-scroll button:hover {
 
  transform: scale(110%,110%) translate3d(0px,-5px,0px);

}
table { 
    
    transition: all 0.5s ease-in-out;
    color: white;
    text-align: center;
   
    padding-top: 50px;
  /* border-radius: 10px; */
  border: 3px solid rgb(255, 255, 255);
  transform-style: preserve-3d;
  
  background-size: 60px 60px;
  background-position: 0 0, 0 0;
  /*background-color: #f0f0f0;*/
  height: 300px; 
  
 
  /*box-shadow: rgba(0, 0, 0, 0.3) 0px 30px 30px -10px;*/
   width: 60%; margin: 5% 25%;
}
.wrapper{
   
}
th{
    font-size: 16px;
    white-space: nowrap;
    font-weight: 900;
  color: rgb(255, 255, 255);
    
  border: 1px solid rgb(251, 251, 252);
  
  
  background: rgba(4, 193, 250, 0.732);

  /* border-radius: 10px; */
  padding: 15px 5px;
  transform: translateY(10px);
 

}
td{
    font-size: medium;
    background: rgba(4, 193, 250, 0.732);
    

    font-weight: 900;
    transition: all 0.5s ease-in-out;
    padding: 5px;
}

table button{
    border: 0px;
    cursor: pointer;
  margin-top: 1rem;
  display: inline-block;
  font-weight: 900;
  font-size: 9px;
  text-transform: uppercase;
  color: rgb(7, 185, 255);
  width: 100%;
  /* border-radius: 5px; */
  background: white;
  padding: 0.5rem 0.7rem;
  transform: scale(100%,100%) translate3d(0,0px,0);
  transition: all 0.5s ease-in-out;
}
table button:hover {
 
  transform: scale(110%,110%) translate3d(0px,-5px,0px);

}
.titretable th{
    color: rgba(7, 185, 255);
  background: rgb(255, 255, 255);
border: 1px solid rgba(7, 185, 255);
position: absolute;
z-index: 20;
margin: -13.5% -20%;
height: 45px;

}

.extend {
    
    display: inline-flex;
    align-items: center;
    border: 0px solid;
    border-radius: 50%;
    cursor: pointer;
    transition: width 0.3s ease, border-radius 0.3s ease;
    width: 50px;
    overflow: hidden;
    float:right;
    white-space: nowrap;
    background-color: rgb(221, 239, 245);
    padding: 0 10px;
 
}

.extend svg {
    /*flex-shrink: 0;
    width: 20px;
    height: 20px;
    margin-right: 2px;*/
    flex-shrink: 0;
    width: 30px;
    height: 30px;
    margin-right: 2px;
    background-color: rgb(221, 239, 245);
    vertical-align: middle;
}

.extend span {
    /*opacity: 0;
    transition: opacity 0.3s ease;
   
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    margin-left: 2px;*/
    opacity: 0;
    transition: opacity 0.3s ease;
    font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
    color:rgb(76, 163, 210);
    font-size:18px;
    background-color: rgb(221, 239, 245);
    height:100%;
    align-items:center;
    display: flex;
}

.extend:hover {
    /*width: 120px; /* Adjust width to fit your text */
    /*border-radius: 20px;*/
    width: 160px; /* Adjust width to fit your text */
    border-radius: 20px;
    height:30px;
    background-color: rgb(221, 239, 245);
}

.extend:hover span {
    opacity: 1;
}

.parent {
    width: 80%; margin: 5% -25%;
  height: 40%;
  padding: 20px;
  perspective: 1000px;
}

.card {
  padding-top: 50px;
  /* border-radius: 10px; */
  border: 3px solid rgb(255, 255, 255);
  transform-style: preserve-3d;
  /*background: linear-gradient(135deg,#0000 18.75%,#f3f3f3 0 31.25%,#0000 0),
      repeating-linear-gradient(45deg,#f3f3f3 -6.25% 6.25%,#ffffff 0 18.75%);*/

  background-size: 60px 60px;
  background-position: 0 0, 0 0;
  /*background-color: #f0f0f0;*/
  background-color:rgb(226, 94, 143);
  width: 100%;
 
  box-shadow: rgba(0, 0, 0, 0.3) 0px 30px 30px -10px;
  transition: all 0.5s ease-in-out;
}

.global{
    display: flex;
    justify-content: space-between; /* répartit bien le logo à gauche, les icônes à droite */
    align-items: center;
    width: 100%;
    float:left;
}

.content-box {
  height: 100%;
  background: rgb(45, 200, 247);
  /*background: rgba(38, 191, 238, 0.94)*/
  /*background:rgb(76, 163, 210);*/
  /* border-radius: 10px 100px 10px 10px; */
  transition: all 0.5s ease-in-out;
  padding: 60px 25px 25px 25px;
  transform-style: preserve-3d;
}

.content-box .card-title {
  display: inline-block;
  color: white;
  font-size: 25px;
  font-weight: 900;
  transition: all 0.5s ease-in-out;
  transform: translate3d(0px, 0px, 50px);
}

.content-box .card-title:hover {
  transform: translate3d(0px, 0px, 60px);
}

.content-box .card-content {
  margin-top: 10px;
  font-size: 15px;
  font-weight: 700;
  color: #f2f2f2;
  transition: all 0.5s ease-in-out;
  transform: translate3d(0px, 0px, 30px);
}

.content-box .card-content:hover {
  transform: translate3d(0px, 0px, 60px);
}

.content-box .see-more {
  cursor: pointer;
  margin-top: 1rem;
  display: inline-block;
  font-weight: 900;
  font-size: 11px;
  text-transform: uppercase;
  color: rgb(7, 185, 255);
  /* border-radius: 5px; */
  background: white;
  padding: 0.5rem 0.7rem;
  transition: all 0.5s ease-in-out;
  transform: translate3d(0px, 0px, 20px);
}

.content-box .see-more:hover {
  transform: translate3d(0px, 0px, 60px);
}

.date-box {
  position: absolute;
  top: 30px;
  right: 30px;
  height: 20%;
  width: 60px;
  background: white;
  border: 1px solid rgb(7, 185, 255);
  /* border-radius: 10px; */
  padding: 10px;
  transform: translate3d(0px, 0px, 80px);
  box-shadow: rgba(100, 100, 111, 0.2) 0px 17px 10px -10px;
}

.date-box span {
  display: block;
  text-align: center;
}

.date-box .month {
  /*color: rgb(4, 193, 250);*/
  color:rgb(191, 58, 107);
  font-size: 15px;
  font-weight: 900;
}

.date-box .date {
  font-size: 15px;
  font-weight: 900;
  /*color: rgb(4, 193, 250);*/
  color:rgb(191, 58, 107);
}
.btn{
  display:flex;
  width: 70px;
  margin-left:62dvh;
  margin-top:6dvh;
}
.annuler{
width: 95px;
height:40px;
border: 2px solid rgb(191, 58, 107); 
color:rgb(191, 58, 107);
font-weight:bold;
cursor:pointer;
}
.annuler:hover {
  background-color:rgb(191, 58, 107);
  color:white;
  transition:0.3s;
}
.enregistrer{
  width: 95px;
  height:40px;
  border: 2px solid rgb(191, 58, 107); 
  color:rgb(191, 58, 107);
  font-weight:bold;
  cursor:pointer
}
.enregistrer:hover{
  background-color:rgb(191, 58, 107);
  color:white;
  transition:0.3s;
}
input{
border-radius:150px;
  height:20px;
  border:none;
}
.loadingScreen {
  position: fixed;
  z-index: 100;
  width: 100vw;
  height: 100vh;
  top: 0;
  left:0;
  background-color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 1;
  transition:  all 0.5s ease-in-out;
}

.loader {
  position: absolute;
  top: 50%;
  left: 50%;
  z-index: 10;
  width: 160px;
  height: 100px;
  margin-left: -80px;
  margin-top: -50px;
  border-radius: 5px;
  background:rgb(224, 69, 115);
  animation: dot1_ 3s cubic-bezier(0.55,0.3,0.24,0.99) infinite;
}

.loader:nth-child(2) {
  z-index: 11;
  width: 150px;
  height: 90px;
  margin-top: -45px;
  margin-left: -75px;
  border-radius: 3px;
  background:rgb(55, 169, 213);
  animation-name: dot2_;
}

.loader:nth-child(3) {
  z-index: 12;
  width: 40px;
  height: 20px;
  margin-top: 50px;
  margin-left: -20px;
  border-radius: 0 0 5px 5px;
  background:rgb(37, 166, 216);
  animation-name: dot3_;
}

@keyframes dot1_ {
  3%,97% {
    width: 160px;
    height: 100px;
    margin-top: -50px;
    margin-left: -80px;
  }

  30%,36% {
    width: 80px;
    height: 120px;
    margin-top: -60px;
    margin-left: -40px;
  }

  63%,69% {
    width: 40px;
    height: 80px;
    margin-top: -40px;
    margin-left: -20px;
  }
}

@keyframes dot2_ {
  3%,97% {
    height: 90px;
    width: 150px;
    margin-left: -75px;
    margin-top: -45px;
  }

  30%,36% {
    width: 70px;
    height: 96px;
    margin-left: -35px;
    margin-top: -48px;
  }

  63%,69% {
    width: 32px;
    height: 60px;
    margin-left: -16px;
    margin-top: -30px;
  }
}

@keyframes dot3_ {
  3%,97% {
    height: 20px;
    width: 40px;
    margin-left: -20px;
    margin-top: 50px;
  }

  30%,36% {
    width: 8px;
    height: 8px;
    margin-left: -5px;
    margin-top: 49px;
    border-radius: 8px;
  }

  63%,69% {
    width: 16px;
    height: 4px;
    margin-left: -8px;
    margin-top: -37px;
    border-radius: 10px;
  }
}
    </style>
</head>
<body>
   

    <header>
        <div class="global">
            <div class="logo">
                <div>
                    <img src="img/logo.png" class="logopic">
                </div>
                <div class="nom">
                    <div class="div3"><div class="W">W</div>eb</div>  
                    <div class="div3"><div class="M">M</div>edical</div>
                </div>
            </div>
            <div class="icones">
                <div class="barech">
                    <form method="GET" action="patients.php" class="patient-search-form">
                        <input type="text" name="query" placeholder="Rechercher un patient...">
                        <button type="submit" class="rech">🔍</button>
                    </form>
                </div>
                <div class="buttons">
                   
                    <button class ="extend" id="logout">
                        <!--<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg>-->
                        <!--<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="rgb(191, 58, 107)"><path d="M523-523Zm-86 86Zm43 297q57 0 111.5-18.5T691-212q-47-33-100.5-53.5T480-286q-57 0-110.5 20.5T269-212q45 35 99.5 53.5T480-140Zm86-339-43-43q17-11 25.5-28t8.5-37q0-32-22.5-54.5T480-664q-20 0-37 8.5T415-630l-43-43q20-25 48-38t60-13q57 0 97 40t40 97q0 32-13 60t-38 48Zm236 236-41-41q29-44 44-93.5T820-480q0-142-99-241t-241-99q-53 0-102.5 15T285-760l-42-42q52-38 112.5-58T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 64-20 124.5T802-243ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-64 19.5-124.5T157-716L26-848l43-43L876-84l-43 43-634-633q-30 43-44.5 92.5T140-480q0 62 21.5 119.5T222-255q58-40 123-65.5T480-346q46 0 89.5 11t85.5 31l107 107q-57 57-129.5 87T480-80Z"/></svg>-->
                        <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="rgb(191, 58, 107)" font-weight="bolder"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg>
                        <span>Se deconnecter</span>
                    </button>
                </div>
            </div>
        </div>
    </header>
   
    
    <div class="doc" id="doc-container" style="display: none;"></div>

 
     
     <form method="POST" class="form1" id="aj-form" enctype="multipart/form-data">
        
        <svg class="x" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z"/></svg>
        <h3>Ajouter un patient</h3>
        
        <label for="nom" class="name">Nom:</label> 
        <input type="text" name="nom" placeholder="Entrer nom" required>
        
        <label for="prenom">Prénom:</label>
        <input type="text" name="prenom" placeholder="Entrer prénom" required>
        
        <label>Sexe :</label>
        <section class="choix">
<label for="homme">Homme</label>

<input type="radio" id="homme" name="sexe" value="Homme" required>

<label for="femme">Femme</label>

<input type="radio" id="femme" name="sexe" value="Femme">


</section>
        




        <label for="date_naissance">Date de naissance:</label>
        <input type="date" name="date_naissance" placeholder="Entrer date de naissance" required>
        <label for="email">Email:</label>
        <input type="email" name="email" placeholder="Entrer email" required>
        <label for="telephone">Numéro de téléphone:</label>
        <input type="text" id="telephone" name="telephone" placeholder="Entrer numéro de téléphone ex:(+213...)"  pattern="\+213[0-9]{9}"required>

        <label for="adresse">Adresse:</label>
        <input type="text" name="adresse" placeholder="Entrer Adresse">
        <form class="form1" id="aj-form">
          <div class="btn">
          <button type="button" onclick="fermerFormulaire()" class="annuler">Annuler</button>
          <button type="submit" class="enregistrer" >Enregister</button>
          </div>
        </form>
        <script>
            function fermerFormulaire() {
                document.getElementById("aj-form").style.display = "none";
            }
        </script>
     </form>
   
   
       
       
        <form method="POST"class="form1" id="add-form" enctype="multipart/form-data">
        <p class="x">X</p>
        <h3>Ajouter un document</h3>
        <input type="number" id="add-id" name="add_id" value="<?php echo $row['id']; ?>">
    
        <label for="nomfichier">Nom du Document:</label> 
        <input type="text" id="nomfichier"  placeholder="Entrer nom"name="nomfichier"  required>
        <label for="fileToUpload">Document:</label> 
        <input type="file" name="fileToUpload" id="fileToUpload" accept="image/*">
        <button type="submit" onclick="close()">Effectuer</button>
        </form>
    
   
        </div>
<div id="div1">
    
<div class="scroll-title">Rendez-vous <br>d'aujourd'hui</div>

<div class="container-scroll">
  <div class="scroll-header">
    <div>Nom</div>
    <div>Prénom</div>
    <div>Actions</div>
  </div>

  <div class="rows">
    a
  </div>
  <?php while ($row = $result->fetch_assoc()) { ?>
    <div class="scroll-row">
      <div><?= htmlspecialchars($row['nom']) ?></div>
      <div><?= htmlspecialchars($row['prenom']) ?></div>
      <div>
        <form action="patient.php" method="get" style="display: inline;">
          <input type="hidden" name="id" value="<?= $row['id'] ?>">
          <button type="submit">Détails</button>
        </form>
      </div>
    </div>
  <?php } ?>

</div>
    </div>
    <div id="div2">
    <div class="parent">
  <div class="card">
      <div class="content-box">
          <span class="card-title">
            <?php  
            if (date("H") >= 4 && date("H") < 12) {
                $slt = "Bonjour";
            } else {
                $slt = "Bonsoir";
            }
            $stmt= $conn->prepare("SELECT nom FROM medecins WHERE id=?");
            $stmt->bind_param("i", $medecin_id);
            $stmt->execute();
            $stmt->store_result();
            $stmt->bind_result($medn);
            $stmt->fetch(); 
            echo $slt." Dr.".$medn." !";
          ?></span>
          <p class="card-content">
          Comment vas-tu aujourd'hui ?
          </p>
          <span class="see-more" onclick="document.getElementById('tous').submit();">Tous Les Patients</span>
          
              <form id="latest-patient-form" action="patient.php" method="get" style="display: inline;">
                <?php $resultlt = $conn->query("SELECT id FROM patients ORDER BY id DESC LIMIT 1");
                        $late = $resultlt->fetch_assoc();?>
              <input type="hidden" name="id" value="<?= $late['id'] ?>">
            </form>
            
            <form id="tous" action="patients.php" method="" style="display: inline;">
              <input type="hidden" name="id" value="">
            </form>

          
      </div>
      <div class="date-box">
          <span class="month"><?php
          setlocale (LC_TIME, 'fr_FR.utf8','fra');
          echo date("M d");?></span>
          <?php
date_default_timezone_set("Europe/London"); // Set your timezone
$serverTime = date("H:i:s");
?>
          <span class="date" id="clock"><?=$serverTime?></span>
      </div>
  </div>
</div>
    
   
<script>
     function loading() {
    var loadingScreen = document.querySelector(".loadingScreen");
    window.addEventListener('load', function() {
        setTimeout(function() {loadingScreen.style.opacity = '0'}, 1500);
       setTimeout(function() {loadingScreen.style.display = 'none'}, 2000);

    });
}
loading();
function updateClock() {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    document.getElementById('clock').innerText = hours + ":" + minutes+":"+seconds;
}

setInterval(updateClock, 1000);
updateClock();

document.addEventListener("DOMContentLoaded", function() {
    const telInput = document.getElementById("telephone");

    
    telInput.addEventListener("focus", function() {
        if (!telInput.value.startsWith("+213")) {
            telInput.value = "+213";
        }
    });

    
    telInput.addEventListener("input", function() {
        if (!telInput.value.startsWith("+213")) {
            telInput.value = "+213";
        }

        
        let digits = telInput.value.slice(4).replace(/\D/g, "");

        
        digits = digits.slice(0, 9);

        telInput.value = "+213" + digits;
    });
});


        document.querySelector("#logout").addEventListener("click", function(event) {
            event.preventDefault();
            fetch("logout.php")
                .then(() => window.location.href = "index.html");
        });
        document.querySelectorAll("input[type='number']").forEach(input => {
    input.addEventListener("input", function() {
        if (this.value < 0) this.value = 0;
        if (this.value > 100) this.value = 100;
    });
});

  

let currentlyVisibleElement = null;

  function showtab2(elementId) {

    if (currentlyVisibleElement && currentlyVisibleElement !== elementId) {
      document.getElementById(currentlyVisibleElement).style.display = "none";
    }


    document.getElementById(elementId).style.display = "block";
   

    


    currentlyVisibleElement = elementId;

   const targetElement = document.getElementById(elementId);
const bodyChildren = Array.from(document.body.children);

for (const el of bodyChildren) {
    // Skip the target element and its descendants
    if (!targetElement.contains(el) && el !== targetElement && !el.contains(targetElement)) {
        el.style.filter = "blur(5px)";
    } else {
        el.style.filter = "none";
    }
}
    document.body.style.overflow = "hidden";

  }
  document.querySelectorAll(".x").forEach(button => {
    button.addEventListener("click", function () {
      button.closest(".form1").style.display = "none";
      document.body.style.overflow = "";
       const allElements = document.body.getElementsByTagName("*");
        for (let i = 0; i < allElements.length; i++) {
            allElements[i].style.filter = "none";
        }
    });
  });
  document.querySelectorAll(".annuler").forEach(button => {
    button.addEventListener("click", function () {
      button.closest(".form1").style.display = "none";
      document.body.style.overflow = "";
       const allElements = document.body.getElementsByTagName("*");
        for (let i = 0; i < allElements.length; i++) {
            allElements[i].style.filter = "none";
        }
    });
  });
    </script>

</body>
</html>

<?php
$conn->close();
?>
