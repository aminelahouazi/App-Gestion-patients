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


$patient_id = $_GET['id'];
$patient_id = intval($patient_id);

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nom'], $_POST['prenom'], $_POST['date_naissance'], $_POST['telephone'], $_POST['email'], $_POST['adresse'])) {
  //modifier l'enregistrement d'un patient
  $stmt = $conn->prepare("UPDATE patients SET nom = ?, prenom = ?,sexe=?, date_naissance = ?, telephone = ?, email = ?, adresse = ? WHERE id = ?");
  $stmt->bind_param(
    "sssssssi",
    $_POST['nom'],
    $_POST['prenom'],
    $_POST['sexe'],
    $_POST['date_naissance'],
    $_POST['telephone'],
    $_POST['email'],
    $_POST['adresse'],
    $patient_id
  );
  $stmt->execute();
  $stmt->close();


}
// Ajouter une maladie
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['nom_maladie'], $_POST['description'])) {
  $stmt = $conn->prepare("INSERT INTO maladies (patient_id, medecin_id, nom_maladie, description) VALUES (?, ?, ?, ?)");
  $stmt->bind_param("iiss", $patient_id, $medecin_id, $_POST['nom_maladie'], $_POST['description']);
  $stmt->execute();
  $stmt->close();
}

// Supprimer une maladie
if (isset($_GET['delete_maladie'])) {
  $id_maladie = intval($_GET['delete_maladie']);
  $stmt = $conn->prepare("DELETE FROM maladies WHERE id = ?");
  $stmt->bind_param("i", $id_maladie);
  $stmt->execute();
  $stmt->close();
  header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $patient_id); 
  exit;
}
// Ajouter rendez-vous si formulaire soumis
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ajouter_rdv'], $_POST['date_rdv'])) {
  $date_rdv = $_POST['date_rdv'];
  $stmt = $conn->prepare("INSERT INTO rendez_vous (patient_id, medecin_id, date_rdv) VALUES (?, ?, ?)");
  $stmt->bind_param("iis", $patient_id, $medecin_id, $date_rdv);
  $stmt->execute();
  $stmt->close();
  header("Location: " . $_SERVER['REQUEST_URI']);
  exit;
}

// Supprimer rendez-vous
if (isset($_GET['del_rdv'])) {
  $rdv_id = $_GET['del_rdv'];
  $stmt = $conn->prepare("DELETE FROM rendez_vous WHERE date_rdv = ? AND medecin_id = ? AND patient_id = ? ");
  $stmt->bind_param("sii", $rdv_id, $medecin_id, $patient_id);
  $stmt->execute();
  $stmt->close();
  header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $patient_id);
  exit;
}
if (isset($_GET['delete_doc'])) {
  $doc_id = intval($_GET['delete_doc']);

  
  $stmt = $conn->prepare("SELECT filepath FROM documents WHERE id = ? AND medecin_id = ? AND patient_id = ?");
  $stmt->bind_param("iii", $doc_id, $medecin_id, $patient_id);
  $stmt->execute();
  $stmt->bind_result($filePath);
  $stmt->fetch();
  $stmt->close();

  if (!empty($filePath) && file_exists($filePath)) {
    unlink($filePath);
  }

  $stmt = $conn->prepare("DELETE FROM documents WHERE id = ? AND medecin_id = ? AND patient_id = ?");
  $stmt->bind_param("iii", $doc_id, $medecin_id, $patient_id);
  $stmt->execute();
  $stmt->close();

  header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $patient_id);
  exit;
}
if (
  $_SERVER["REQUEST_METHOD"] === "POST" &&
  isset($_POST['add_id'], $_POST['nomfichier'], $_POST['typedoc'], $_FILES["fileToUpload"]) &&
  $_FILES["fileToUpload"]["error"] === 0
) {
  $patient_id = intval($_POST['add_id']);
  $filename = $_POST['nomfichier'];
  $filetype = $_POST['typedoc'];


  if (!$medecin_id) {
    die("Erreur : médecin non connecté.");
  }

 
  $originalName = basename($_FILES["fileToUpload"]["name"]);
  $extension = pathinfo($originalName, PATHINFO_EXTENSION);
  if ($extension == "pdf") {
    $uniqueName = uniqid("doc_", true) . "." . "html";
  } else {
    $uniqueName = uniqid("doc_", true) . "." . $extension;
  }

  $uploadDir = "documents/";
  $uploadPath = $uploadDir . $uniqueName;

  
  if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
  }

  if (!is_writable($uploadDir)) {
    die("Erreur : le dossier 'documents/' n'est pas accessible en écriture.");
  }


  if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $uploadPath)) {
    $stmt = $conn->prepare("INSERT INTO documents (patient_id, medecin_id, filename, filetype, filepath) VALUES (?, ?, ?, ?, ?)");
    if (!$stmt) {
      die("Erreur de préparation : " . $conn->error);
    }
    $stmt->bind_param("iisss", $patient_id, $medecin_id, $filename, $filetype, $uploadPath);
    if (!$stmt->execute()) {
      die("Erreur à l'insertion : " . $stmt->error);
    }
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $patient_id);
    exit;
  } else {
    echo "Erreur lors du téléchargement du fichier.";
    echo "<br>Temp file: " . $_FILES["fileToUpload"]["tmp_name"];
    echo "<br>Destination: " . $uploadPath;
    var_dump(error_get_last());
  }
}





// récupérer les infos du patient
$stmt = $conn->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();


if (!$patient) {
  echo "Patient introuvable.";
  exit;
}
$stmt->close();

// Afficher tous les rendez-vous
$rdvs = [];
$stmt = $conn->prepare("SELECT * FROM rendez_vous WHERE patient_id = ? AND medecin_id = ? GROUP BY date_rdv  ORDER BY date_rdv DESC");
$stmt->bind_param("ii", $patient_id, $medecin_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $rdvs[] = $row;
}
$stmt->close();

// récupérer les maladies du patient
$maladies = [];
$stmt = $conn->prepare("SELECT * FROM maladies WHERE patient_id = ? ORDER BY date_ajout DESC");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $maladies[] = $row;
}
$stmt->close();



function getDocumentsByType($conn, $patient_id, $medecin_id, $type)
{
  $stmt = $conn->prepare("SELECT * FROM documents WHERE patient_id = ? AND medecin_id = ? AND filetype = ? ORDER BY uploaded_at DESC");
  $stmt->bind_param("iis", $patient_id, $medecin_id, $type);
  $stmt->execute();
  $result = $stmt->get_result();
  $docs = [];
  while ($row = $result->fetch_assoc()) {
    $docs[] = $row;
  }
  $stmt->close();
  return $docs;
}

$ord = getDocumentsByType($conn, $patient_id, $medecin_id, "Ordonnance");
$ana = getDocumentsByType($conn, $patient_id, $medecin_id, "Analyses");
$autre = getDocumentsByType($conn, $patient_id, $medecin_id, "Autre")


  ?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <title>Dossier de <?= htmlspecialchars($patient['prenom']) ?> <?= htmlspecialchars($patient['nom']) ?></title>
</head>
<style>
 body {
    font-family: "Ubuntu Mono", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;    
    margin: 0;
    color: rgb(8, 15, 81);
    background: radial-gradient(circle, rgba(255, 212, 230, 1) 0%, rgba(254, 213, 231, 1) 38%, rgb(231, 239, 250) 98%);

  }

  #div1,
  #div2 {
    width: 75%;
  }

  .form1 input {
    height: 30px;
    border-radius: 10px;
    border: none;
    margin-bottom: 2rem;
    /**/
  }

  textarea {
    border: none;
    height: 50px;
    border-radius: 10px;
    margin-bottom: 2rem;
    margin-left: 250px;
    border: 2px solid rgb(191, 58, 107);
  }

  textarea:hover {
    background-color: rgb(252, 178, 212);
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
    z-index: 98;
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

  .barech {
    display: flex;
    gap: 5px;
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    top: 18px;
    z-index: 5;
  }

  .barech button {
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
    width: 40dvw;
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
    display: flex;
    right: 0;
    margin: 1px;
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

  .extend:hover {

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

  .doc {
    height: 75%;
    width: 50%;
    margin-top: 20px;
    display: none;
    border: 2px solid;
    background-color: white;
    position: fixed;
    left: 25%;
    top: 7%;
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

  .form1 input {
    font-weight: bold;
    border: none;

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

  .form1 input {
    border: 2px solid rgb(191, 58, 107);
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

 
    width: 5em;
    max-width: 5em;min-width: 5em;
    color: rgb(191, 58, 107);
    font-size: 24px;
   word-wrap: break-word;
  margin: 0;
    margin-left: 20%;
  }
  .form1 p {
   width: 15em;
   max-width: 15em;
   min-width: 15em;
   font-size: 18px;
   word-wrap: break-word;
    margin: 0;
    margin-top: 3%;
    text-align: left;
   
  }
  #mlist a{
margin-inline: 18em;
margin-inline-start: 18em;
margin-inline-end: 18em;
  }
   #dlist a{
margin-inline: 18em;
margin-inline-start: 18em;
margin-inline-end: 18em;
  }

  .form1 a {
    margin: 0;
    position: relative;
   width: 5%;
  
  }

  .form1 label {

    position: absolute;
    margin: 0px 20%;
    margin-bottom: 2rem;

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
    margin-bottom: 2%;
    margin-top: -10px;
  }

  .choix {
    display: flex;
   
    margin: 0;
    margin-left: 410px;
    margin-top: 20px;
    margin-bottom: 20px;
  
  }

  .choix label {
    position: relative;
    margin: 0px;
    

  }
  .choix input {
    margin: 0%;
 

   
  }

  #rlist{
    height: 90%;
    text-align: center;
  }
  #rlist h5{
   width: 15em;
   font-size: 24px;
   display: inline-block;
   
    white-space: nowrap;
    margin-left: 25%;
  }
  #rlist a{
   margin-left: 175%;
  }

  
  #choixdoc {
   display: grid;
   position: relative;
   margin-left: 50%;
  }

  #choixdoc p {
  display: flex;
  }

  #choixdoc input {
   height:20px;
    width: 20px;
    margin: 10px 0 5px 25px;

   
  }
  .radio-option {
    display: flex;
    align-items: center;
    gap: 1px;
  }

  .drop-area {
    border: 2px dashed #aaa;
    padding: 20px;
    text-align: center;
    color: #666;
    cursor: pointer;
    margin-bottom: 15px;
    transition: border-color 0.3s;
  }

  .drop-area.dragover {
    border-color: #3498db;
    background-color: #f0f8ff;
  }

  .drop-area p {
    margin: 0 15%;
    font-size: 14px;
    width: 75%;
  }

  #ajout-maladie-form {
    /*display: none;
    background-color: rgb(221, 239, 245);
    border: 2px solid rgb(191, 58, 107);

    width: 56%;
    border-radius: 15px;*/
    display: none;
      background-color:rgb(221, 239, 245);
      border: 2px solid rgb(191, 58, 107); 
      z-index: 99;
      width: 56%; 
      height:55%;
      border-radius:15px;

  }

  #ajout-maladie-form input:hover {
    background-color: rgb(252, 178, 212);
  }

  #traitement-form {
    /*display: none;
    background-color: rgb(221, 239, 245);
    border: 2px solid rgb(191, 58, 107);

    width: 56%;
    border-radius: 15px;
    position: fixed;*/
    display: none;
      background-color:rgb(221, 239, 245);
      border: 2px solid rgb(191, 58, 107); 
      z-index: 99;
      width: 56%;
      border-radius:15px;
      position:fixed;
  }

  #traitement-form input:hover {
    background-color: rgb(252, 178, 212);
  }

  #rdv-form {
    /*display: none;
    background-color: rgb(221, 239, 245);
    border: 2px solid rgb(191, 58, 107);
    height: 45%;
    width: 56%;
    position: fixed;

    border-radius: 15px;*/
    display: none;
      background-color:rgb(221, 239, 245);
      border: 2px solid rgb(191, 58, 107); 
      height:50%;
      width: 56%; 
      position:fixed;
      z-index: 99;
      border-radius:15px;
  }

  #rdv-form input:hover {
    background-color: rgb(252, 178, 212);
  }

  #edit-form {

    /*height: 90%;
    width: 56%;
    top: 6dvh;
    display: none;
    border: 2px solid rgb(191, 58, 107);
    position: fixed;
    left: 25%;
    background-color: rgb(221, 239, 245);
    color: rgb(8, 15, 81);
    border-radius: 15px;*/
    z-index: 99;
          height: 90%; 
          width: 56%; 
          top:4dvh;
          display: none; 
          border: 2px solid rgb(191, 58, 107);
          position: fixed;
          left: 25%;
          box-shadow: rgba(255, 0, 0, 0.3) 0px 17px 25px 5px;
          /*background:rgb(108, 195, 241);*/
          background-color:rgb(221, 239, 245);
          color: rgb(8, 15, 81);
          border-radius:15px;
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
    margin-left:400px;
  }
  #adresse{
    margin-left:400px;
  }
  #edit-form h3 {
    /*margin-bottom: 5%;*/
    margin-bottom: 8%;
    margin-top: 2%;
    color: rgb(191, 58, 107);
    font-size: 20px;
  }

  #edit-form input:hover {
    background-color: rgb(252, 178, 212);
  }

  #add-form {
    display: none;
    z-index: 99;
    height: 85%;
    width: 56%;
    top: 8dvh;
    border: 2px solid rgb(191, 58, 107);
    position: fixed;
    left: 25%;
    box-shadow: rgba(255, 0, 0, 0.3) 0px 17px 25px 5px;
    background-color: rgb(221, 239, 245);
    color: rgb(8, 15, 81);
    border-radius:15px;
  }
  #add-form input:hover {
    background-color: rgb(252, 178, 212);
  }

   #doc-form {

    height: 90%;
    width: 56%;
    top: 6dvh;
    display: none;
    border: 2px solid rgb(191, 58, 107);
    position: fixed;
    left: 25%;
    font-family: "Ubuntu Mono", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;    
    /*background:rgb(108, 195, 241);*/
    background-color: rgb(221, 239, 245);
    color: rgb(8, 15, 81);
    border-radius:15px;
  }
   #mlist a{
margin-inline: 18em;
margin-inline-start: 18em;
margin-inline-end: 18em;
  }
  
   #doc-form button{
    width: 270px;
    color: rgb(8, 15, 81);
    border: 2px solid rgb(8, 15, 81);
    margin: 0 25%;
    margin-bottom: 20px;
    font-size: large;
    font-family: "Ubuntu Mono", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;    
    cursor: pointer;
    font-weight: bold;
   border-radius: 7px;
  }
   .sup{
    text-align: center;
     min-width: 8.5em;
    width: 8.5em;
    max-width: 8.5em;
    color: rgb(8, 15, 81);
    border: 2px solid rgb(8, 15, 81);
     font-size: large;
    font-family: "Ubuntu Mono", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;    
    cursor: pointer;
    font-weight: bold;
   border-radius: 7px;
   max-height: 2em;
    min-height: 2em;
  display: inline-block;
   
    white-space: nowrap;
  }
    
  
  #doc-form .supelem{
   position: relative;
   margin-inline: 13em;
margin-inline-start: 13em;
margin-inline-end: 13em;
  }
   #doc-form h4{
    min-width: 15em;
    width: 15em;
    max-width: 15em;
    margin-inline: 2em;

    font-size: large;
 
   
  }
  .btndoc{
    display: flex;
    margin: 0% 13%;
  }


  #listord {
    display: none;


  }

  #listana {
    display: none;

  }

  #listaut {
    display: none;

  }

  #listord,
  #listord,
  #listaut {
    margin: 0 15%;
  }

 

  label {
    font-size: 20px;
    margin-bottom: 2rem;
  }

  #rdv-form {
    display: none;
  }

  a {
    text-decoration: none;
    color: white;
  }


  table {
    width: 100%;
    border-collapse: collapse;
  }

  td {
    padding: 10px;
    border: 1px solid #ccc;
  }

  .btn1 {
    display: flex;
    float: right;
    width: 70px;
    margin-right: 230px;
  }

  .btntraitement {
    display: flex;
    width: 100px;
    margin-left: 500px;
    margin-top: 40px;
  }

  .btnrdv {
    /*display: flex;
    width: 100px;
    margin-left: 520px;
    margin-top: 40px;*/
    display:flex;
  width: 100px;/*520*/
  margin-left:61dvh;
  margin-top:80px;
  }

  .btnmaladie {

    display: flex;
    width: 100px;
    margin-top: 10dvh;
    margin-left: 60dvh;/*500*/

  }

  .btnmodifier {
    /*display: flex;
    width: 100px;
    margin-left: 500px;
    margin-top: 40px;*/
    display:flex;
  width: 100px;
  margin-left: 60dvh;
  margin-top:85px;


  }

  .btndocument {
    display: flex;
    width: 100px;
    margin-left: 60dvh;
    margin-top: 70px;

  }

  .btn3 {
    display: flex;
    float: right;
    width: 70px;
    margin-right: 230px;
  }

  .annuler {
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

  .effectuer {
    border: 2px solid rgb(191, 58, 107);
    color: rgb(191, 58, 107);
    font-weight: bold;
    height: 40px;
    width: 95px;
    cursor:pointer;
  }

  .effectuer:hover {
    background-color: rgb(191, 58, 107);
    color: white;
    transition: 0.3s;
  }

  td {
    width: 50%;
  }

  table {
    color: rgb(8, 15, 81);
    table-layout: fixed;
    height: 400px;
  }

  .ajouter {
    border: 2px solid rgb(191, 58, 107);
    color: rgb(58, 120, 191);
    font-weight: bold;
    height: 30px;
  }


  .edit {
    display: flex;
    margin-left: 350px;
    gap: 10px;
  }

  p {
    margin-left: 50px;
    font-size: 20px;
  }

  h2 {
    margin-left: 30px;
    font-size: 26px;
    font-family: 'Courier New', Courier, monospace;
    color: rgb(59, 129, 204);
  }

  ul {
    border: 3px solid rgb(191, 58, 107);
  }

  .total {
    background-color: rgb(228, 234, 241);
    margin: 90px;
    display: block;
    margin-top: 20px;
  }

  .tableau {
    width: 100%;
    margin-top: 20px;

  }

  .texte {
    width: 100%;
    display: flex;
  }

  .pic {
    width: 40%;
  }

  .infos {
    width: 60%;
  }

  .texte p {
    font-size: 23px;
  }

  .texte img {
    width: 300px;
    height: 300px;
    margin-left: 20px;
    margin-top: 20px;
  }

  .add {
    position: relative;
    width: 150px;
    height: 40px;
    cursor: pointer;
    display: flex;
    align-items: center;
    border: 1px solid rgb(186, 44, 115);
    background-color: rgb(239, 104, 172);
    color: white;

  }

  .add,
  .button__icon,
  .button__text {
    transition: all 0.3s;
  }

  .add.button__text {
    transform: translateX(30px);
    color: #fff;
    font-weight: 600;
  }

  .add .button__icon {
    position: absolute;
    transform: translateX(109px);
    height: 100%;
    width: 39px;
    background-color: rgb(240, 117, 187);
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .add .svg {
    width: 30px;
    stroke: #fff;
  }

  .add:hover {
    background: rgb(248, 157, 232);
  }

  .add:hover .button__text {
    color: transparent;
  }

  .add:hover .button__icon {
    width: 148px;
    transform: translateX(0);
  }

  .add:active .button__icon {
    background-color: #2e8644;
  }

  .add:active {
    border: 1px solid #2e8644;
  }

  .show {
    width: 150px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: flex-start;
    border: none;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.164);
    cursor: pointer;

  }

  .text {
    width: 65%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    background-color: rgb(53, 173, 233);
  }

  .svgIcon {
    width: 35%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    background-color: rgb(30, 142, 197);

  }

  .show:hover .text {
    background-color: rgb(24, 154, 220);
  }

  .show:hover .svgIcon {
    background-color: rgb(12, 114, 165);
  }

  /* From Uiverse.io by Madflows */
  .button {
    position: relative;
    overflow: hidden;
    height: 3rem;
    padding: 0 2rem;
    border-radius: 1.5rem;
    background: rgb(105, 188, 230);
    background-size: 400%;
    color: #fff;
    border: none;
    cursor: pointer;
  }

  .button:hover::before {
    transform: scaleX(1);
  }

  .button-content {
    position: relative;
    z-index: 1;
  }

  .button::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    transform: scaleX(0);
    transform-origin: 0 50%;
    width: 100%;
    height: inherit;
    border-radius: inherit;
    background: linear-gradient(82.3deg,
        rgb(249, 110, 212) 10.8%,
        rgb(246, 85, 171) 94.3%);
    transition: all 0.475s;
  }

  .buttonf {
    position: relative;
    overflow: hidden;
    height: 3rem;
    width: 6rem;
    padding: 0 2rem;
    border-radius: 1.5rem;
    background: rgb(105, 188, 230);
    background-size: 400%;
    color: #fff;
    border: none;
    cursor: pointer;
  }

  .buttonf:hover::before {
    transform: scaleX(1);
  }

  .buttonf-content {
    position: relative;
    z-index: 1;
  }

  .buttonf::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    transform: scaleX(0);
    transform-origin: 0 50%;
    width: 100%;
    height: inherit;
    border-radius: inherit;
    background: linear-gradient(82.3deg,
        rgb(249, 110, 212) 10.8%,
        rgb(246, 85, 171) 94.3%);
    transition: all 0.475s;
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
tr{
  height:230px;
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
    margin-top: 5%;
      font-family: "Ubuntu Mono", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
      font-weight: bold;
      text-align: center;
      font-size: medium;

}
#confirmationBox div{
   display:flex;
   position: relative;
    text-align: center;
   margin-left: 10%;
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
      font-size: medium;
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

<body>
  <div id="confirmationBox" class="form1" style="display: none;">
  
    <p>Êtes-vous sûr de vouloir supprimer cet element ?</p>
    <div>
        <button class="annuler" id="confirmYes">Oui</button>
    <button class="annuler" id="confirmNo">Non</button>
</div>
</div>
    
<div id="message" class="form1" style="display:none;">
  
    <p>Element enregistré avec succès !</p>
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

        <div class="buttons">
          
          <button class="extend2" id="logout" onclick="window.location.href='patients.php'">
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
  </header><br><br><br><br><br><br>

  <div class="form1" id="doc-form">
    <svg class="x" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
      fill="#000000">
      <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z" />
    </svg>
    <section class="btndoc">
      <button onclick="document.getElementById('listord').style.display = 'block';
                 document.getElementById('listana').style.display = 'none';
                 document.getElementById('listaut').style.display = 'none';">Ordonnances</button>
      <button onclick="document.getElementById('listana').style.display = 'block';
                 document.getElementById('listord').style.display = 'none';
                 document.getElementById('listaut').style.display = 'none';">Analyses</button>
      <button onclick="document.getElementById('listaut').style.display = 'block';
                 document.getElementById('listana').style.display = 'none';
                 document.getElementById('listord').style.display = 'none';">Autre</button>

    </section>
    
      <span style="width:100%; max-height: 70%; max-width: 90%; overflow-y: auto;overflow-x:hidden; margin: 0; margin-left: 30px; margin-bottom: 10px;" id="listord">
                <hr>
              <?php foreach ($ord as $or): ?>
               
                <br>
                <section>
                <h4><?= htmlspecialchars($or['filename']) ?>:</h4>
          <a class="sup" href="voir_document.php?file=<?= htmlspecialchars($or['filepath']) ?>" target="_blank">Voir le document</a>
                   <a class="supelem" href="?id=<?= $patient_id ?>&delete_doc=<?= $or['id'] ?>"
                    style=" color:black;">✖</a>
                  </section>
                  <br>
                <hr>
              <?php endforeach; ?>
              <br>
      </span>
      <span style="width:100%; max-height: 70%; max-width: 90%; overflow-y: auto;overflow-x:hidden; margin: 0; margin-left: 30px; margin-bottom: 10px;" id="listana">
                <hr>
              <?php foreach ($ana as $an): ?>
               
                <br>
                <section>
                <h4><?= htmlspecialchars($an['filename']) ?>:</h4>
          <a class="sup" href="voir_document.php?file=<?= htmlspecialchars($an['filepath']) ?>" target="_blank">Voir le document</a>
                     <a class="supelem" href="?id=<?= $patient_id ?>&delete_doc=<?= $an['id'] ?>"
                    style=" color:black;">✖</a>
                  </section>
                  <br>
                <hr>
              <?php endforeach; ?>
              <br>
      </span>
       <span style="width:100%; max-height: 70%; max-width: 90%; overflow-y: auto;overflow-x:hidden; margin: 0; margin-left: 30px; margin-bottom: 10px;" id="listaut">
                <hr>
              <?php foreach ($autre as $autr): ?>
               
                <br>
                <section>
                <h4><?= htmlspecialchars($autr['filename']) ?>:</h4>
          <a class="sup" href="voir_document.php?file=<?= htmlspecialchars($autr['filepath']) ?>" target="_blank">Voir le document</a>
                     <a class="supelem" href="?id=<?= $patient_id ?>&delete_doc=<?= $autr['id'] ?>"
                    style=" color:black;">✖</a>
                  </section>
                  <br>
                <hr>
              <?php endforeach; ?>
              <br>
      </span>
  </div>

  <form method="POST" class="form1" id="rdv-form">
    <svg class="x" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
      fill="#000000">
      <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z" />
    </svg>
    <h3>Ajouter un rendez-vous</h3>
    <label for="date_rdv">Date du rendez-vous:</label>
    <input type="date" id="date_rdv" name="date_rdv" required>
    <div class="btnrdv">
      <button type="button" onclick="fermerfr()" class="annuler">Annuler</button>
      <button type="submit" class="effectuer" name="ajouter_rdv">Ajouter</button>
    </div>
  </form>
  <script>
    function fermerfr() {
      document.getElementById("rdv-form").style.display = "none";
    }
  </script>

  

  <form method="POST" class="form1" id="ajout-maladie-form">
    <svg class="x" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
      fill="#000000">
      <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z" />
    </svg>
    <h3>Ajouter une pathologie</h3>

    <label for="nom_maladie">Nom de la maladie:</label>
    <input type="text" id="nom_maladie" name="nom_maladie" required>

    <label for="description">Description:</label>
    <textarea id="description" name="description" required></textarea>

    <div class="btnmaladie">
      <button type="button" onclick="fermer()" class="annuler">Annuler</button>
      <button type="submit" class="effectuer">Ajouter</button>
    </div>
  </form>
  <script>
    function fermer() {
      document.getElementById("ajout-maladie-form").style.display = "none";
    }
  </script>

  <form method="POST" class="form1" id="edit-form">

    <svg class="x" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
      fill="#000000">
      <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z" />
    </svg>
    <h3>Modifier les informations </h3>

    <input type="hidden" id="id">

    <label for="nom">Nom:</label>
    <input type="text" id="nom" name="nom" placeholder="Entrer nom" required>

    <label for="prenom">Prénom:</label>
    <input type="text" id="prenom" name="prenom" placeholder="Entrer prénom" required>

    <label>Sexe:</label>
    <section class="choix">
      <label for="homme">Homme</label>

      <input type="radio" id="homme" name="sexe" value="Homme" required>

      <label for="femme">Femme</label>

      <input type="radio" id="femme" name="sexe" value="Femme">

    </section>

    <label for="date_naissance">Date de naissance:</label>
    <input type="date" id="date_naissance" name="date_naissance" placeholder="Entrer date de naissance" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" placeholder="Entrer email" required>

    <label for="telephone">Numéro de téléphone:</label>
    <input type="text" id="telephone" name="telephone" placeholder="Entrer numéro de téléphone ex:(+213...)"
      pattern="\+213[0-9]{9}" required>

    <label for="adresse">Adresse:</label>
    <input type="text" id="adresse" name="adresse" placeholder="Entrer Adresse">

    <div class="btnmodifier">
      <button type="button" onclick="fermerFormulaire()" class="annuler">Annuler</button>
      <button type="submit" class="effectuer">Effectuer</button>
    </div>

  </form>
  <script>
    function fermerFormulaire() {
      document.getElementById("edit-form").style.display = "none";
    }
  </script>


  <form method="POST" class="form1" id="add-form" enctype="multipart/form-data">
    <svg class="x" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
      fill="#000000">
      <path d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z" />
    </svg>
    <h3>Ajouter un document</h3>
    <input type="hidden" id="add-id" name="add_id" value="<?php echo $patient['id']; ?>">

    <label for="nomfichier">Nom du document:</label>
    <input type="text" id="nomfichier" placeholder="Entrer nom" name="nomfichier" required>

    <label>Type du document:</label>

    <section class="choix" id="choixdoc">

      <p>Ordonnance
      <input type="radio" id="Ordonnance" name="typedoc" value="Ordonnance" required> </p>

      <p>Analyses&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
      <input type="radio" id="Analyses" name="typedoc" value="Analyses"></p>

      <p>Autre&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
     <input type="radio" id="Autre" name="typedoc" value="Autre"></p>


    </section><br>

    <label for="fileToUpload">Document:</label>
    <div class="drop-area" id="drop-area">

      <p>Déposez un fichier ici ou cliquez pour choisir un fichier</p>
      <input type="file" name="fileToUpload" id="fileToUpload" style="display:none;">
    </div>
    <div class="btndocument">
      <button type="button" onclick="fer()" class="annuler">Annuler</button>
      <button type="submit" class="effectuer" onclick="close()">Effectuer</button>
    </div>
  </form>
  <form id="mlist" class="form1" style="max-height: 75¨%;">
            
              <svg class="x" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
                fill="#000000">
                <path
                  d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z" />
              </svg>
  <h3>Liste des pathologies</h3>
              <span style="width:100%; max-height: 70%; max-width: 90%; overflow-y: auto;overflow-x:hidden; margin: 0; margin-left: 30px; margin-bottom: 10px;">
                <hr>
              <?php foreach ($maladies as $maladie): ?>
               
                <br>
                <section>
                <h4><?= htmlspecialchars($maladie['nom_maladie']) ?>:</h4>
                  <p><?= htmlspecialchars($maladie['description']) ?></p>
                  <a class="supelem" href="?id=<?= $patient_id ?>&delete_maladie=<?= $maladie['id'] ?>"
                    style=" color:black;">✖</a>
                  </section>
                  <br>
                <hr>
              <?php endforeach; ?>
              <br>
              </span>
            </form>
 
  <form id="rlist" class="form1" style="max-height: 75%;">
  <svg class="x" xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px"
    fill="#000000">
    <path
      d="m256-200-56-56 224-224-224-224 56-56 224 224 224-224 56 56-224 224 224 224-56 56-224-224-224 224Z" />
  </svg>
  <h3>Liste des rendez-vous</h3>
   <span style="width:100%; max-height: 67%; max-width: 90%; overflow-y: auto;overflow-x:hidden; margin: 0; margin-left: 30px; margin-bottom: 10px;">
                <hr>
            <?php foreach ($rdvs as $rdv): ?>
               
                <br>
                <section>
                <h5><?= htmlspecialchars($rdv['date_rdv']) ?>:</h3>
                  <a class="supelem" href="?id=<?= $patient_id ?>&del_rdv=<?= $rdv['date_rdv'] ?>" style="color:black;">✖</a>
                  </section>
                  <br>
                <hr>
              <?php endforeach;?>
              <br>
              </span>

</form>

  <!--partie visible-->
  <div class="total">
    <div class="texte">
      <div class="pic">
        <?php
        $femme = "img/femme.jpg";
        $homme = "img/homme.jpg";

        if ($patient['sexe'] === 'Femme') {
          echo "<img src='$femme' alt='femme.jpg'>";
        } else {
          echo "<img src='$homme' alt='homme.jpg'>";
        }
        ?>
      </div>
      <div class="infos">
        <h2>
          <?php if ($patient['sexe'] === 'Femme'): ?>
            Dossier de Mme.   <?= htmlspecialchars($patient['nom']) ?> <?= htmlspecialchars($patient['prenom']) ?>
          <?php else: ?>
            Dossier de Mr.   <?= htmlspecialchars($patient['nom']) ?> <?= htmlspecialchars($patient['prenom']) ?>
          <?php endif; ?>
        </h2>

        <p><strong>Sexe :</strong> <?= $patient['sexe'] ?></p>
        <p><strong>Date de naissance :</strong> <?= $patient['date_naissance'] ?></p>
        <p><strong>Téléphone :</strong> <?= htmlspecialchars($patient['telephone']) ?></p>
        <p><strong>Email :</strong> <?= htmlspecialchars($patient['email']) ?></p>
        <p><strong>Adresse :</strong> <?= htmlspecialchars($patient['adresse']) ?></p>
        <br><br>
        <div class="edit">
          <button class="button" onclick="window.location.href='patients.php'">
          
              <span class="button-content">Retour</span>
         
          </button>

          <button class="button" id="edit-btn" onclick="showtab2('edit-form')" data-id="<?php echo $patient['id']; ?>"
            data-nom="<?php echo htmlspecialchars($patient['nom']); ?>"
            data-prenom="<?php echo htmlspecialchars($patient['prenom']); ?>"
            data-sexe="<?php echo htmlspecialchars($patient['sexe']); ?>"
            data-date_naissance="<?php echo htmlspecialchars($patient['date_naissance']); ?>"
            data-email="<?php echo htmlspecialchars($patient['email']); ?>"
            data-telephone="<?php echo htmlspecialchars($patient['telephone']); ?>"
            data-adresse="<?php echo htmlspecialchars($patient['adresse']); ?>">
            <span class="button-content">Modifier</span>
          </button>
        </div>
      </div>
    </div>

    <div class="tableau">
      <table>
        <tr class="tr1">
          <td class="td1">
            <h3>Liste des pathologies</h3>
            <ul>
             
            </ul>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
              <button type="button" class="add" onclick="showtab2('ajout-maladie-form')" class="ajouter">
                <span class="button__text">+ Ajouter</span>
                <span class="button__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24"
                    stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" height="24"
                    fill="none" class="svg">
                    <line y2="19" y1="5" x2="12" x1="12"></line>
                    <line y2="12" y1="12" x2="19" x1="5"></line>
                  </svg></span>
              </button>
              <button class="show" onclick="showtab2('mlist')">
                <span class="text">Afficher</span>
                <span class="svgIcon">
                  <svg fill="white" viewBox="0 0 384 512" height="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M280 64h40c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128C0 92.7 28.7 64 64 64h40 9.6C121 27.5 153.3 0 192 0s71 27.5 78.4 64H280zM64 112c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320c8.8 0 16-7.2 16-16V128c0-8.8-7.2-16-16-16H304v24c0 13.3-10.7 24-24 24H192 104c-13.3 0-24-10.7-24-24V112H64zm128-8a24 24 0 1 0 0-48 24 24 0 1 0 0 48z">
                    </path>
                  </svg>
                </span>
              </button>
            </div>

          </td>
          <!--<td class="td1">
            <h3>Liste des traitements</h3>
             <ul>
             
            </ul>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
              <button type="button" class="add" onclick="showtab2('traitement-form')">
                <span class="button__text">+ Ajouter</span>
                <span class="button__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24"
                    stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" height="24"
                    fill="none" class="svg">
                    <line y2="19" y1="5" x2="12" x1="12"></line>
                    <line y2="12" y1="12" x2="19" x1="5"></line>
                  </svg></span>
              </button>
              <button class="show" onclick="showtab2('tlist')">
                <span class="text">Afficher</span>
                <span class="svgIcon">
                  <svg fill="white" viewBox="0 0 384 512" height="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M280 64h40c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128C0 92.7 28.7 64 64 64h40 9.6C121 27.5 153.3 0 192 0s71 27.5 78.4 64H280zM64 112c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320c8.8 0 16-7.2 16-16V128c0-8.8-7.2-16-16-16H304v24c0 13.3-10.7 24-24 24H192 104c-13.3 0-24-10.7-24-24V112H64zm128-8a24 24 0 1 0 0-48 24 24 0 1 0 0 48z">
                    </path>
                  </svg>
                </span>
              </button>
            </div>
          </td>-->
          <td class="td1">
            <h3>Liste des rendez-vous</h3>
            <ul>
            
            </ul>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
              <button type="button" class="add" onclick="showtab2('rdv-form')">
                <span class="button__text">+ Ajouter</span>
                <span class="button__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24"
                    stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" height="24"
                    fill="none" class="svg">
                    <line y2="19" y1="5" x2="12" x1="12"></line>
                    <line y2="12" y1="12" x2="19" x1="5"></line>
                  </svg></span>
              </button>
                           <button class="show" onclick="showtab2('rlist')">
                <span class="text">Afficher</span>
                <span class="svgIcon">
                  <svg fill="white" viewBox="0 0 384 512" height="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M280 64h40c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128C0 92.7 28.7 64 64 64h40 9.6C121 27.5 153.3 0 192 0s71 27.5 78.4 64H280zM64 112c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320c8.8 0 16-7.2 16-16V128c0-8.8-7.2-16-16-16H304v24c0 13.3-10.7 24-24 24H192 104c-13.3 0-24-10.7-24-24V112H64zm128-8a24 24 0 1 0 0-48 24 24 0 1 0 0 48z">
                    </path>
                  </svg>
                </span>
              </button>
            </div>
          </td>
          <td class="td1">
            <h3>Liste des documents</h3>
            <ul>
             
            </ul>
            <div style="display: flex; gap: 15px; margin-top: 10px;"><button type="button" class="add"
                onclick="showtab2('add-form')">
                <span class="button__text">+ Ajouter</span>
                <span class="button__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24"
                    stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" height="24"
                    fill="none" class="svg">
                    <line y2="19" y1="5" x2="12" x1="12"></line>
                    <line y2="12" y1="12" x2="19" x1="5"></line>
                  </svg></span>
              </button>
             <button class="show" onclick="showtab2('doc-form')">
                <span class="text">Afficher</span>
                <span class="svgIcon">
                  <svg fill="white" viewBox="0 0 384 512" height="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M280 64h40c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128C0 92.7 28.7 64 64 64h40 9.6C121 27.5 153.3 0 192 0s71 27.5 78.4 64H280zM64 112c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320c8.8 0 16-7.2 16-16V128c0-8.8-7.2-16-16-16H304v24c0 13.3-10.7 24-24 24H192 104c-13.3 0-24-10.7-24-24V112H64zm128-8a24 24 0 1 0 0-48 24 24 0 1 0 0 48z">
                    </path>
                  </svg>
                </span>
              </button>
            </div>
          </td>
        </tr>
        <tr class="tr2">
          <!--<td class="td1">
            <h3>Liste des rendez-vous</h3>
            <ul>
            
            </ul>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
              <button type="button" class="add" onclick="showtab2('rdv-form')">
                <span class="button__text">+ Ajouter</span>
                <span class="button__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24"
                    stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" height="24"
                    fill="none" class="svg">
                    <line y2="19" y1="5" x2="12" x1="12"></line>
                    <line y2="12" y1="12" x2="19" x1="5"></line>
                  </svg></span>
              </button>
                           <button class="show" onclick="showtab2('rlist')">
                <span class="text">Afficher</span>
                <span class="svgIcon">
                  <svg fill="white" viewBox="0 0 384 512" height="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M280 64h40c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128C0 92.7 28.7 64 64 64h40 9.6C121 27.5 153.3 0 192 0s71 27.5 78.4 64H280zM64 112c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320c8.8 0 16-7.2 16-16V128c0-8.8-7.2-16-16-16H304v24c0 13.3-10.7 24-24 24H192 104c-13.3 0-24-10.7-24-24V112H64zm128-8a24 24 0 1 0 0-48 24 24 0 1 0 0 48z">
                    </path>
                  </svg>
                </span>
              </button>
            </div>
          </td>
          <td class="td1">
            <h3>Liste des documents</h3>
            <ul>
             
            </ul>
            <div style="display: flex; gap: 15px; margin-top: 10px;"><button type="button" class="add"
                onclick="showtab2('add-form')">
                <span class="button__text">+ Ajouter</span>
                <span class="button__icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" viewBox="0 0 24 24"
                    stroke-width="2" stroke-linejoin="round" stroke-linecap="round" stroke="currentColor" height="24"
                    fill="none" class="svg">
                    <line y2="19" y1="5" x2="12" x1="12"></line>
                    <line y2="12" y1="12" x2="19" x1="5"></line>
                  </svg></span>
              </button>
             <button class="show" onclick="showtab2('doc-form')">
                <span class="text">Afficher</span>
                <span class="svgIcon">
                  <svg fill="white" viewBox="0 0 384 512" height="1em" xmlns="http://www.w3.org/2000/svg">
                    <path
                      d="M280 64h40c35.3 0 64 28.7 64 64V448c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V128C0 92.7 28.7 64 64 64h40 9.6C121 27.5 153.3 0 192 0s71 27.5 78.4 64H280zM64 112c-8.8 0-16 7.2-16 16V448c0 8.8 7.2 16 16 16H320c8.8 0 16-7.2 16-16V128c0-8.8-7.2-16-16-16H304v24c0 13.3-10.7 24-24 24H192 104c-13.3 0-24-10.7-24-24V112H64zm128-8a24 24 0 1 0 0-48 24 24 0 1 0 0 48z">
                    </path>
                  </svg>
                </span>
              </button>
            </div>
          </td>-->
        </tr>
      </table>
    </div>
  </div>


</body>
<script>
   
 
let formToSubmit = null;
let deleteUrl = null;

document.querySelectorAll('.supelem').forEach(a => {
  a.addEventListener('click', function (e) {
    e.preventDefault();
     deleteUrl = this.getAttribute('href');
    showtab2('confirmationBox');
  });
});



document.getElementById('confirmYesmsg').addEventListener('click', () => {
  formToSubmit.submit(); 
});


document.getElementById('confirmYes').addEventListener('click', () => {
 
 if (deleteUrl) {
    window.location.href = deleteUrl; 
  }
});

document.getElementById('confirmNo').addEventListener('click', () => {
 
  deleteUrl = null;
});

</script>
<script>
    function loading() {
    var loadingScreen = document.querySelector(".loadingScreen");
   
    window.addEventListener('load', function() {
        setTimeout(function() {loadingScreen.style.opacity = '0'}, 1500);
       setTimeout(function() {loadingScreen.style.display = 'none'}, 2000);

    });
}
loading();
  const dropArea = document.getElementById('drop-area');
  const fileInput = document.getElementById('fileToUpload');


  dropArea.addEventListener('click', () => fileInput.click());


  fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
      dropArea.querySelector('p').textContent = `Fichier sélectionné : ${fileInput.files[0].name}`;
    }
  });


  dropArea.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropArea.classList.add('dragover');
  });

  dropArea.addEventListener('dragleave', () => {
    dropArea.classList.remove('dragover');
  });

  dropArea.addEventListener('drop', (e) => {
    e.preventDefault();
    dropArea.classList.remove('dragover');

    if (e.dataTransfer.files.length > 0) {
      fileInput.files = e.dataTransfer.files;
      dropArea.querySelector('p').textContent = `Fichier déposé : ${e.dataTransfer.files[0].name}`;
    }
  });


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


  document.querySelectorAll("#edit-btn").forEach(button => {
    button.addEventListener("click", function () {
      document.getElementById("id").value = this.dataset.id;
      document.getElementById("nom").value = this.dataset.nom;
      document.getElementById("prenom").value = this.dataset.prenom;
      if (this.dataset.sexe === "Homme") {
        document.getElementById("homme").checked = true;
      } else {
        document.getElementById("femme").checked = true;
      }
      document.getElementById("date_naissance").value = this.dataset.date_naissance;
      document.getElementById("email").value = this.dataset.email;
      document.getElementById("telephone").value = this.dataset.telephone;
      document.getElementById("adresse").value = this.dataset.adresse;


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

</html>
<?php
$conn->close();
?>