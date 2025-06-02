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
$message="";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nom'], $_POST['prenom'], $_POST['sexe'], $_POST['date_naissance'], $_POST['telephone'], $_POST['email'], $_POST['adresse'])) {
    //nouveau enregistrement d'un patient
    $stmt = $conn->prepare("INSERT INTO patients (nom, prenom,sexe, date_naissance, telephone, email, adresse) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "sssssss",
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
    if ($stmt->execute()) {
        $message = "Patient enregistré avec succès !";
    } else {
         $message = "Erreur" . $conn->error;
    }

    $stmt->close();

} else if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['supprimer_id'])) {
    //supprimer un patient (tous les enregistrement qui ont une relaton seront aussi supprimer)
    $idsup = intval($_POST['supprimer_id']);
    $qsup = "DELETE FROM patients WHERE id = ?";
    $stmt = $conn->prepare($qsup);
    $stmt->bind_param("i", $idsup);

    if ($stmt->execute()) {
        $message = "Patient supprimé avec succès !";
    } else {
         $message = "Erreur" . $conn->error;
    }

    $stmt->close();
}


if ($_GET['query']) {
    
$search = isset($_GET['query']) ? '%' . $conn->real_escape_string($_GET['query']) . '%' : '%';
$stmt = $conn->prepare("SELECT p.* FROM patients p
                        INNER JOIN medecin_patient mp ON p.id = mp.patient_id
                        
                        WHERE mp.medecin_id = ? AND (p.nom LIKE ? OR p.prenom LIKE ?)");
$stmt->bind_param("iss", $medecin_id, $search, $search);
$stmt->execute();
$result = $stmt->get_result();

}
else
{
$stmt = $conn->prepare("
SELECT  patients.*  
FROM patients
JOIN medecin_patient ON patients.id =  medecin_patient.patient_id
WHERE  medecin_patient.medecin_id = ? ORDER BY date_creation DESC
");
$stmt->bind_param("i", $medecin_id);
$stmt->execute();
$result = $stmt->get_result();
}


?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <title>Table des patients</title>
    <style>
        h2 {
            margin-top: 40px;
            font-size: 40px;
            text-align: center;
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;
            color: rgb(191, 58, 107);

        }

        @font-face {
            font-family: myfont;
            src: url(res/LobsterTwo-BoldItalic.ttf);
        }

        header {
            font-weight: bold;
            height: 70px;
            width: 100%;
            top: 0;
            z-index: 99;
            position: fixed;
            background-color: rgb(221, 239, 245);
            color: rgb(8, 15, 81);
            font-size: large;
            padding-right: 3px;
            padding-top: 8px;
            display: flex;
            justify-content: flex-end;
            align-items: center;/ margin-top: none;
            border: 1px solid rgb(191, 58, 107);
            margin-left: 0;
            float: left;
        }

        .logo {
            display: flex;
            align-items: center;
            margin: 0;
        }

        .div3 {
            display: flex;
        }

        .logopic {
            width: 90px;
        }

        .nom {
            font-size: 30px;
            display: flex;
            gap: 5px;
            font-family: myfont;
            color: rgb(76, 163, 210);
        }

        .W {
            color: rgb(191, 58, 107);
            font-size: 35px;
        }

        .M {
            color: rgb(191, 58, 107);
            font-size: 35px;
        }

        h3 {
            margin-top: 10%;
        }

        body {
            font-family: "Ubuntu Mono", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
           
            background: rgb(255, 255, 255);
            margin: 0;
            background: radial-gradient(circle, rgb(245, 233, 238) 0%, rgb(248, 221, 232) 38%, rgb(237, 243, 251) 98%);
        }

        #div2 {
            top: 10dvh;
            position: relative;
        }

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
          height: 80%;
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
    margin-left:25dvw;
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
    margin-top: -15px;
  }

  .choix {
    display: flex;
   
    margin: 0 0%;
    margin-left: 26dvw;
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
            color: rgb(255, 255, 255);
            font-size: x-large;
            position: absolute;
            left: 5%;
            top: 2dvh;
        }

        .barech {
            /*top: 20px;
            display: flex;
            gap: 5px;
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            top: 18px;
            z-index: 5;*/
            display: flex;
    gap:5px;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: 20px;
    z-index: 5;
        }

        .barech button {
            /*border-radius: 50%;
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
            margin-right: 2dvw;*/
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
            /*width: 40dvw;
            height: 4dvh;
            align-items: center;
            border: 0px solid;
            border-radius: 20px;
            transition: width 0.3s ease, border-radius 0.3s ease;
            overflow: hidden;
            padding: 5px;
            white-space: nowrap;*/
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
            /*position: absolute;
    right: 0;
    top: 2.5dvh;  */
            display: flex;
            right: 0;
            margin: 1px;
        }

        #add-form {
            display: none;
        }

        #aj-form {
            display: none;

        }

        #edit-form {
            display: none;

        }


        button {
            padding: 8px;
            cursor: pointer;
        }

        table {

            transition: all 0.5s ease-in-out;
            color: white;
            text-align: center;
            padding-top: 50px;
            /* border-radius: 10px; */
            border: 3px solid rgb(255, 255, 255);
            transform-style: preserve-3d;
            /*background: linear-gradient(135deg, #0000 18.75%, rgb(247, 194, 194) 0 31.25%, #0000 0),
      repeating-linear-gradient(45deg,rgb(247, 194, 194) -6.25% 6.25%,rgb(255, 224, 224) 0 18.75%);
      */
            /*background: linear-gradient(135deg,#0000 18.75%,#f3f3f3 0 31.25%,#0000 0),
      repeating-linear-gradient(45deg,#f3f3f3 -6.25% 6.25%,#ffffff 0 18.75%);*/

            /*background-size: 60px 60px;
  background-position: 0 0, 0 0;*/
            background-color: white;
            overflow-y: scroll;

            /*box-shadow: rgba(0, 0, 0, 0.3) 0px 30px 30px -10px;*/
            width: 80%;
            /*margin: 5% 22.9dvw;*/
            margin: 40px;
            margin-left: 113px;
            border-collapse: separate;
            border-spacing: 20px;
            border: 1px solid rgb(244, 106, 182);
            margin-top: 50px;
        }

        #div1 {
            width: 80%;
            position: relative;
            margin: 30px;
            margin-left: 130px;
            margin-top: 150px;
            border: 1px solid rgb(100, 197, 230);
            background-color: rgb(221, 239, 245);
        }

        .wrapper {}

        th {
            font-family: 'Franklin Gothic Medium', 'Arial Narrow', Arial, sans-serif;

            font-size: 16px;
            white-space: nowrap;
            font-weight: 900;
            /*color: rgb(4, 193, 250);*/
            /*background-color:rgb(4, 193, 250);*/
            background-color: rgb(108, 217, 241);
            color: white;

            border: 1px solid rgb(7, 185, 255);

            /* border-radius: 10px; */
            /*padding: 15px 5px;*/
            transform: translateY(10px);
            border-radius: 8px;
            padding: 15px 10px;
            height: 30px;

            /*box-shadow: rgba(35, 35, 184, 0.2) 0px 17px 10px -10px;*/

        }

        td {
            font-size: 22px;
            /*background: rgba(4, 193, 250, 0.732);*/
            color: rgba(22, 45, 106, 0.73);

            font-weight: 900;
            transition: all 0.5s ease-in-out;
            padding: 5px;
            background-color: rgba(233, 236, 243, 0.73);
        }

        table button {
            border: 0px;
            cursor: pointer;
            margin-top: 1rem;
            display: inline-block;
            font-weight: 900;
            font-size: 12px;
            text-transform: uppercase;

            width: 100%;
            /* border-radius: 5px; */
            background: white;
            padding: 0.5rem 0.7rem;
            transform: scale(100%, 100%) translate3d(0, 0px, 0);
            transition: all 0.5s ease-in-out;
        }

        .detail {
            color: rgb(7, 185, 255);
            font-size:15px;
        }

        .supprimer {
            color: rgb(191, 58, 107);
            font-size:15px;
        }

        table button:hover {

            transform: scale(110%, 110%) translate3d(0px, -5px, 0px);

        }

        /*.card:hover {
  background-position: -100px 100px, -100px 100px;
  transform: rotate3d(0.5, 1, 0, 30deg);
}*/





        .extend {
            display: inline-flex;
            align-items: center;
            border: 0px solid;
            border-radius: 50%;
            cursor: pointer;
            transition: width 0.3s ease, border-radius 0.3s ease;
            width: 50px;
            overflow: hidden;
            float: right;
            white-space: nowrap;
            background-color: rgb(221, 239, 245);
            padding: 0 10px;

        }

        .extend2 {
            display: inline-flex;
            align-items: center;
            border: 0px solid;
            border-radius: 50%;
            cursor: pointer;
            transition: width 0.3s ease, border-radius 0.3s ease;
            width: 50px;
            overflow: hidden;
            float: right;
            white-space: nowrap;
            background-color: rgb(221, 239, 245);
            padding: 0 10px;

        }

        .extend svg {
            flex-shrink: 0;
            width: 30px;
            height: 30px;
            margin-right: 2px;
            background-color: rgb(221, 239, 245);
            vertical-align: middle;
        }

        .extend2 svg {
            flex-shrink: 0;
            width: 30px;
            height: 30px;
            margin-right: 2px;
            background-color: rgb(221, 239, 245);
            vertical-align: middle;
        }

        .extend span {
            opacity: 0;
            transition: opacity 0.3s ease;
            font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
            color: rgb(76, 163, 210);
            font-size: 18px;
            background-color: rgb(221, 239, 245);
            height: 100%;
            align-items: center;
            display: flex;
        }

        .extend2 span {
            opacity: 0;
            transition: opacity 0.3s ease;
            font-family: Impact, Haettenschweiler, 'Arial Narrow Bold', sans-serif;
            color: rgb(76, 163, 210);
            font-size: 18px;
            background-color: rgb(221, 239, 245);
            height: 100%;
            align-items: center;
            display: flex;
        }

        /*.extend:hover {
    border-radius: 20px;
    height:30px;
    background-color: rgb(221, 239, 245);
}*/
        .extend:hover {
            /*width: 120px; /* Adjust width to fit your text */
            /*border-radius: 20px;*/
            width: 160px;
            /* Adjust width to fit your text */
            border-radius: 20px;
            height: 30px;
            background-color: rgb(221, 239, 245);
        }

        .extend2:hover {
            width: 100px;
            /* Adjust width to fit your text */
            border-radius: 20px;
            height: 30px;
            background-color: rgb(221, 239, 245);
        }

        .extend:hover span {
            opacity: 1;
        }

        .extend2:hover span {
            opacity: 1;
        }

        .global {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            float: left;
        }

        .parent {
            width: 80%;
            margin: 5% -25%;
            height: 40%;
            padding: 20px;
            perspective: 1000px;
        }

        .card {
            padding-top: 50px;
            /* border-radius: 10px; */
            border: 3px solid rgb(255, 255, 255);
            transform-style: preserve-3d;
            background: linear-gradient(135deg, #0000 18.75%, #f3f3f3 0 31.25%, #0000 0),
                repeating-linear-gradient(45deg, #f3f3f3 -6.25% 6.25%, #ffffff 0 18.75%);
            background-size: 60px 60px;
            background-position: 0 0, 0 0;
            background-color: #f0f0f0;
            width: 100%;

            /*box-shadow: rgba(0, 0, 0, 0.3) 0px 30px 30px -10px;*/
            transition: all 0.5s ease-in-out;
        }



        .content-box {
            height: 100%;

            background: rgba(4, 193, 250, 0.732);
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
            font-size: 12px;
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
            font-size: 9px;
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
            color: rgb(4, 193, 250);
            font-size: 15px;
            font-weight: 900;
        }

        .date-box .date {
            font-size: 15px;
            font-weight: 900;
            color: rgb(4, 193, 250);
        }

        .btn {
            /*display:flex;
  float:right;
  width: 70px;
  margin-right:230px;*/
            display:flex;
  width: 70px;
  margin-left:75dvh;
  margin-top:6dvh;
        }

        .annuler {
            /*width: 95px;
height:32px;
border: 2px solid rgb(191, 58, 107); 
color:rgb(191, 58, 107);
font-weight:bold;*/
            width: 95px;
            height: 40px;
            border: 2px solid rgb(191, 58, 107);
            color: rgb(191, 58, 107);
            font-weight: bold;
            cursor:pointer;
           
        }

        .annuler:hover {
            background-color: rgb(191, 58, 107);
            color: white;
            transition: 0.3s;
        }

        .enregistrer {
            /*width: 95px;
  height:32px;
  border: 2px solid rgb(191, 58, 107); 
  color:rgb(191, 58, 107);
  font-weight:bold;*/
            width: 95px;
            height: 40px;
            border: 2px solid rgb(191, 58, 107);
            color: rgb(191, 58, 107);
            font-weight: bold;
            cursor:pointer;
        }

        .enregistrer:hover {
            background-color: rgb(191, 58, 107);
            color: white;
            transition: 0.3s;
        }

        input {
            border-radius: 150px;
            height: 20px;
            border: none;
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
/* From Uiverse.io by Nawsome */ 
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
a{
    text-decoration:none;
}
#confirmationBox{
    height:20%;
    width:20%;
    top:35%;
    left:40%;
    display: block;
}
#confirmationBox *{
    position: relative;
}
#confirmationBox p{
    margin-left: 10%;
    margin-top: 10%;
      font-family: "Ubuntu Mono", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
      font-weight: bold;
      text-align: center;
}
#confirmationBox div{
   display:flex;
   position: relative;
    text-align: center;
   margin-left: 7.5%;
    margin-top: 10%;
}
#message{
    height:20%;
    width:20%;
    top:35%;
    left:40%;
    display: block;
}
#message *{
    position: relative;
}
#message p{
    margin-left: 10%;
    margin-top: 10%;
      font-family: "Ubuntu Mono", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
      font-weight: bold;
      text-align: center;
}
#message div{
   display:flex;
   position: relative;
    text-align: center;
   margin-left: 32%;
   margin-right: 50%;
    margin-top: 12%;
}
    </style>
</head>

<body>
<div id="confirmationBox" class="form1" style="display: none;">
  
    <p>Êtes-vous sûr de vouloir supprimer ce patient ?</p>
    <div>
        <button class="annuler" id="confirmYes">Oui</button>
    <button class="annuler" id="confirmNo">Non</button>
</div>
</div>
    
<div id="message" class="form1" style="display:none;">
  
    <p id="msgp"></p>
    <div>
        <button class="annuler" id="confirmYesmsg">Ok</button>
    
    </div>
 
</div>

    

    <header>
        <div class="global">
            <div class="logo">
                <div>
                    <img src="img/logo.png" class="logopic">
                </div>
                <div class="nom">
                    <div class="div3">
                        <div class="W">W</div>eb
                    </div>
                    <div class="div3">
                        <div class="M">M</div>edical
                    </div>
                </div>
            </div>
            <div class="icones">
                <div class="barech">
                    <form method="GET" action="patients.php" class="patient-search-form">
                        <input type="text" name="query" placeholder="Rechercher un patient...">
                        <button type="submit">
                            <svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                                fill="#000000">
                                <path
                                    d="M784-120 532-372q-30 24-69 38t-83 14q-109 0-184.5-75.5T120-580q0-109 75.5-184.5T380-840q109 0 184.5 75.5T640-580q0 44-14 83t-38 69l252 252-56 56ZM380-400q75 0 127.5-52.5T560-580q0-75-52.5-127.5T380-760q-75 0-127.5 52.5T200-580q0 75 52.5 127.5T380-400Z" />
                            </svg>
                        </button>
                    </form>
                </div>
                <div class="buttons">
                      <button class ="extend" onclick="showtab('aj-form')">
                        <!--<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewbox="0 -960 960 960" width="24px" fill="#000000"><path d="M440-280h80v-160h160v-80H520v-160h-80v160H280v80h160v160Zm40 200q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/></svg>-->
                        <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="rgb(191, 58, 107)" font-weight="bolder"><path d="M730-400v-130H600v-60h130v-130h60v130h130v60H790v130h-60Zm-370-81q-66 0-108-42t-42-108q0-66 42-108t108-42q66 0 108 42t42 108q0 66-42 108t-108 42ZM40-160v-94q0-35 17.5-63.5T108-360q75-33 133.34-46.5t118.5-13.5Q420-420 478-406.5T611-360q33 15 51 43t18 63v94H40Zm60-60h520v-34q0-16-9-30.5T587-306q-71-33-120-43.5T360-360q-58 0-107.5 10.5T132-306q-15 7-23.5 21.5T100-254v34Zm260-321q39 0 64.5-25.5T450-631q0-39-25.5-64.5T360-721q-39 0-64.5 25.5T270-631q0 39 25.5 64.5T360-541Zm0-90Zm0 411Z"/></svg>
                        <span>Ajouter Patient</span>
                    </button>
                  
                    <button class="extend2"  onclick="window.location.href='tableau.php'" >
                        <!--<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="rgb(191, 58, 107)" font-weight="bolder"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg>-->
                      
                            <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px"
                                fill="rgb(191, 58, 107)" font-weight="bolder">
                                <path
                                    d="M780-200v-156q0-60-39-99t-99-39H236l163 163-43 43-236-236 236-236 43 43-163 163h406q85 0 141.5 56.5T840-356v156h-60Z" />
                            </svg>
                      
                     
                            <span>Retour</span>
                    </button>
                </div>
            </div>
        </div>
    </header>


   


    <form method="POST" class="form1" id="aj-form" enctype="multipart/form-data">

        <svg class="x" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
            fill="#000000">
            <path
                d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z" />
        </svg>
        <h3>Ajouter un patient</h3>

        <label for="nom">Nom:</label>
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
        <input type="text" id="telephone" name="telephone" placeholder="Entrer numéro de téléphone ex:(+213...)"
            pattern="\+213[0-9]{9}" required>


        <label for="adresse">Adresse:</label>
        <input type="text" name="adresse" placeholder="Entrer Adresse">

        <form class="form1" id="aj-form">
            <div class="btn">
                <button type="button" onclick="fermerFormulaire()" class="annuler">Annuler</button>
                <button type="submit" class="enregistrer">Enregister</button>
            </div>
        </form>
        <script>
            function fermerFormulaire() {
                document.getElementById("aj-form").style.display = "none";
            }
        </script>
    </form>




    <form method="POST" class="form1" id="add-form" enctype="multipart/form-data">
        <p class="x">X</p>
        <h3>Ajouter un document</h3>
        <input type="number" id="add-id" name="add_id" value="<?php echo $row['id']; ?>">

        <label for="nomfichier">Nom du Document:</label>
        <input type="text" id="nomfichier" placeholder="Entrer nom" name="nomfichier" required>
        <label for="fileToUpload">Document:</label>
        <input type="file" name="fileToUpload" id="fileToUpload" accept="image/*">
        <button type="submit" onclick="close()">Effectuer</button>
    </form>



    </div>
    <div id="div1">
        <h2>Liste des patients</h2>
        
   <?php if ($result !== null && $result->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Date de Naissance</th>
                <th>Date d'ajout</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['nom']) ?></td>
                    <td><?= htmlspecialchars($row['prenom']) ?></td>
                    <td><?= htmlspecialchars($row['date_naissance']) ?></td>
                    <td><?= htmlspecialchars($row['date_creation']) ?></td>
                    <td>
                        <form action="patient.php" method="get" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="detail">Détails</button>
                        </form>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="supprimer_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="supprimer">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <table>
        <tr>
            <th>Attention</th>
        </tr>
        <tr>
            <td>
                <?php if (!empty($_GET['query'])): ?>
                    Aucun patient correspondant n’a été trouvé
                <?php else: ?>
                    Aucun patient est enregistré
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td style="display: flex;">
               
                    <button style="width:25%; margin-left: 20%; color: rgb(191, 58, 107);" onclick="document.getElementById('tous').submit();">
                        Tous Les Patients
                    </button>
                
                <button style="width:25%; margin-left: 10%; color: rgb(76, 163, 210);" onclick="showtab('aj-form')">
                    <span><?= !empty($_GET['query']) ? "Ajouter ce patient?" : "Ajouter un patient?" ?></span>
                </button>
                <form id="tous" action="patients.php" method="get" style="display: inline;">
                    <input type="hidden" name="id" value="">
                </form>
            </td>
        </tr>
    </table>
<?php endif; ?>

    </div>

<script>
   
 
let formToSubmit = null;

document.querySelectorAll('.supprimer').forEach(button => {
  button.addEventListener('click', function (e) {
    e.preventDefault();
    formToSubmit = this.closest('form');
    showtab('confirmationBox');
  });
});

document.querySelectorAll('.enregistrer').forEach(button => {
  const aj = document.getElementById("aj-form");
  const msg = document.getElementById("msgp");

  button.addEventListener('click', function (e) {
    

  if (!aj.checkValidity()) {
  
  return;
}

msgp.innerHTML = "Patient enregistré avec succès !"
e.preventDefault();

showtab('message');
  });
});


document.getElementById('confirmYesmsg').addEventListener('click', () => {
  const aj = document.getElementById("aj-form");
    if(aj){
  aj.submit(); 
}

});

document.getElementById('confirmYes').addEventListener('click', () => {
 
  if (formToSubmit){

  formToSubmit.submit();
   
  }
});

document.getElementById('confirmNo').addEventListener('click', () => {
 
  formToSubmit = null;
});

</script>
  <script>let currentlyVisibleElement = null;

  function showtab(elementId) {

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

    <script>

         function loading() {
    var loadingScreen = document.querySelector(".loadingScreen");
    window.addEventListener('load', function() {
        setTimeout(function() {loadingScreen.style.opacity = '0'}, 0);
       setTimeout(function() {loadingScreen.style.display = 'none'}, 0);

    });
}
        loading();
        </script>

    <script>

        document.addEventListener("DOMContentLoaded", function () {
            const telInput = document.getElementById("telephone");


            telInput.addEventListener("focus", function () {
                if (!telInput.value.startsWith("+213")) {
                    telInput.value = "+213";
                }
            });


            telInput.addEventListener("input", function () {
                if (!telInput.value.startsWith("+213")) {
                    telInput.value = "+213";
                }


                let digits = telInput.value.slice(4).replace(/\D/g, "");


                digits = digits.slice(0, 9);

                telInput.value = "+213" + digits;
            });
        });


        document.querySelector("#logout").addEventListener("click", function (event) {
            event.preventDefault();
            fetch("logout.php")
                .then(() => window.location.href = "index.html");
        });
        document.querySelectorAll("input[type='number']").forEach(input => {
            input.addEventListener("input", function () {
                if (this.value < 0) this.value = 0;
                if (this.value > 100) this.value = 100;
            });
        });

    </script>

</body>

</html>

<?php
$conn->close();
?>