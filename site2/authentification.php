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

$message = "";
$cnmessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST["nom"], $_POST["prenom"], $_POST["mdpins"], $_POST["emailins"])) {
        $nom = htmlspecialchars($_POST["nom"]);
        $prenom = htmlspecialchars($_POST["prenom"]);
        $mdp = htmlspecialchars($_POST["mdpins"]);
        $email = htmlspecialchars($_POST["emailins"]);

        $stmt = $conn->prepare("SELECT id FROM medecins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "L'email a été déjà utilisée";
        } else {
            $hash_password = password_hash($mdp, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO medecins (nom, prenom, email, mot_de_passe) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nom, $prenom, $email, $hash_password);

            if ($stmt->execute()) {
                header("Location: tableau.php");
                $message = "Inscrit avec succès";
            } else {
                $message = "Erreur lors de l'inscription.";
            }
        }
        $stmt->close();
    } elseif (isset($_POST["emailcn"], $_POST["mdpcn"])) {
        $email = htmlspecialchars($_POST["emailcn"]);
        $mdp = htmlspecialchars($_POST["mdpcn"]);

        $stmt = $conn->prepare("SELECT id, mot_de_passe FROM medecins WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $hashedPassword);
            $stmt->fetch();

            if (password_verify($mdp, $hashedPassword)) {
                session_start();
                $_SESSION['medecin_id'] = $id;
                header("Location: tableau.php");
                exit();
            } else {
                $cnmessage = "Mot de passe incorrect.";
            }
        } else {
            $cnmessage = "Email non trouvé.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification</title>
</head>
<style>
        @font-face {
        font-family: myfont;
        src: url(res/LobsterTwo-BoldItalic.ttf);
    }
        body {
         font-family: Arial, sans-serif;
         text-align: center; 
         margin:0;
         background: radial-gradient(circle,  rgba(255, 212, 230, 1) 0%,  rgb(243, 245, 247)38%, rgba(254, 213, 231, 1) 98%);
    }
      section { display: flex; justify-content: center; align-items: center; height: 90vh; }
      
      #ins { display: none; }
      #cn{
         display: block; 
         height:300px;
         border-radius:30px;

    }
      form { 
        background-color: rgb(255, 255, 255); 
        display: inline-block; 
        padding: 20px;
        border: 1px solid #ccc; 
        border-radius: 5px; 
        margin: 20px; 
    }

      #creer, #conne {
         cursor: pointer; 
         color: blue; 
         text-decoration: underline; }

      input { 
        display: block;
        margin: 10px auto; 
        padding: 8px; 
        width: 200px; 
        color: rgb(8, 15, 81);
        font-weight:bold;
        border-radius:9px;
        background-color:white;
    }
      button { 
        padding: 10px; 
        cursor: pointer; 
    }
      #lang {
         position: absolute; top: 10px; right: 10px;
         width: 160px;
         height:40px;
         font-size:20px;
         color:rgb(76, 163, 210);
    }
    #ln{
        font-size:30px;
    }
    #login-title{
        font-size:30px;
        color:rgb(191, 58, 107);
        padding-top:50px;
    }
    #cnform{
        min-height:250px;
        max-height:250px;
        border:2px solid rgb(191, 58, 107);
        /*background-color:rgb(221, 239, 245);*/
        background-color:rgb(223, 133, 166);
    }
    #cnform p{
      font-family: "Ubuntu Mono", system-ui, -apple-system, "Segoe UI", Arial, sans-serif;
      font-weight: bold;
      text-align: center;
    }
    #btncn{
      position: absolute;
        top:560px;
        margin-right: 0px;
        margin-left:0px;
        border:2px solid rgb(191, 58, 107);
        /*color:rgb(76, 163, 210);*/
        color: rgb(191, 58, 107);
        font-weight:bold;
    }
    #btncn:hover{
        background-color:rgb(191, 58, 107);
        color:white;
    }
    #emailcn{
        margin-top:35px;
        /*border:2px solid rgb(76, 163, 210);*/
        border:none;
    }
    #mdpcn{
        margin-top:20px;
        border:none;
        /*border:2px solid rgb(76, 163, 210);*/
    }
      header{
        font-weight: bold;
        /*background-color:rgb(8, 15, 81);*/
        height:60px;
        width: 100%;
        position:fixed;
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
        top:0;
    }
    .logo{
        position: absolute;
        display: flex;
        align-items:center;
        float:left;
        left: 0;
        top: 0;
    }
    .nom{
        font-size:30px;
        display:flex;
        gap:5px;
        font-family: myfont;
        color:rgb(76, 163, 210);
    }
    .globe{
        display: flex;
        gap:1190px;
        margin-left:0px;
    }
    .W{
        color:rgb(191, 58, 107);
        font-size:35px;
    }
    .WW{
        color:rgb(191, 58, 107);
        font-size:45px;
    }
    .M{
        color:rgb(191, 58, 107);
        font-size:35px;
    }
    .MM{
        color:rgb(191, 58, 107);
        font-size :45px;
    }
    .div1{
        width:900px;
    }
    .div2{
        text-align:left;
    }
    .div3{
        display: flex;
    }
    .div4{
        font-family: myfont;
        display:flex;
        gap:5px;
        color: rgb(76, 163, 210);
        margin-left:130px;
    }
    .logopic{
        width: 90px;
    }
    .principal{
        display: flex;
        text-align:right;
        align-items:center;
        gap:100px;
    }
    .input_container {
  width: 100%;
  height: fit-content;
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 5px;
}
.input_field {
  width: auto;
  height: 40px;
  padding: 0 0 0 40px;
  border-radius: 7px;
  outline: none;
  border: 1px solid #e5e5e5;
  filter: drop-shadow(0px 1px 0px #efefef)
    drop-shadow(0px 1px 0.5px rgba(239, 239, 239, 0.5));
  transition: all 0.3s cubic-bezier(0.15, 0.83, 0.66, 1);
}
.all{
    background-color:rgb(247, 216, 230);
    width:40%;
    margin-left:30%;
    margin-top:10%;
    height:500px;
    /*background-color:rgb(221, 239, 245);*/
    border:2px solid rgb(224, 65, 150);
    border-radius:8px;
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
}
/*pc*/
/*.loader {
  position: absolute;
  z-index: 10;
  width: 160px;
  height: 100px;
  margin-left: -80px;
  margin-top: -50px;
  border-radius: 5px;
  /*background: #1e3f57;
  background:rgb(235, 96, 133);
  animation: dot1_ 3s cubic-bezier(0.55,0.3,0.24,0.99) infinite;
}

.loader:nth-child(2) {
  z-index: 11;
  width: 150px;
  height: 90px;
  margin-top: -45px;
  margin-left: -75px;
  border-radius: 3px;
  /*background: #3c517d;
  background:rgb(124, 194, 232);
  animation-name: dot2_;
}

.loader:nth-child(3) {
  z-index: 12;
  width: 40px;
  height: 20px;
  margin-top: 50px;
  margin-left: -20px;
  border-radius: 0 0 5px 5px;
  background: #6bb2cd;
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
.all{
    display: block;
}
.container{
    height:70px;
    margin-top:55px;
}*/


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
</style>
<body>
    
 <div id="message" class="form1" style="display:none;">
  
    <p id="msgp"><?php  echo $cnmessage ?></p>
    <div>
        <button class="annuler" id="confirmYesmsg">Ok</button>
    
    </div>
 
</div>
<
<header>
    <div class="globe">
    <div class="logo">
        <div>
            <img src="img/logo.png" class="logopic">
        </div>
        <div class="nom">
            <div class="div3"><div class="W">W</div>eb</div>  
            <div class="div3"><div class="M">M</div>edical</div>
        </div>
    </div>
   <div class="buttons">
                   
                    <button class ="extend" id="logout" onclick="window.location.href='index.html'">
                        <!--<svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#000000"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg>-->
                        <!--<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="rgb(191, 58, 107)"><path d="M523-523Zm-86 86Zm43 297q57 0 111.5-18.5T691-212q-47-33-100.5-53.5T480-286q-57 0-110.5 20.5T269-212q45 35 99.5 53.5T480-140Zm86-339-43-43q17-11 25.5-28t8.5-37q0-32-22.5-54.5T480-664q-20 0-37 8.5T415-630l-43-43q20-25 48-38t60-13q57 0 97 40t40 97q0 32-13 60t-38 48Zm236 236-41-41q29-44 44-93.5T820-480q0-142-99-241t-241-99q-53 0-102.5 15T285-760l-42-42q52-38 112.5-58T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 64-20 124.5T802-243ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-64 19.5-124.5T157-716L26-848l43-43L876-84l-43 43-634-633q-30 43-44.5 92.5T140-480q0 62 21.5 119.5T222-255q58-40 123-65.5T480-346q46 0 89.5 11t85.5 31l107 107q-57 57-129.5 87T480-80Z"/></svg>-->
                        <svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="rgb(191, 58, 107)" font-weight="bolder"><path d="M200-120q-33 0-56.5-23.5T120-200v-560q0-33 23.5-56.5T200-840h280v80H200v560h280v80H200Zm440-160-55-58 102-102H360v-80h327L585-622l55-58 200 200-200 200Z"/></svg>
                        <span>Retour</span>
                    </button>
                </div>
    </header>
    <!--<select id="lang">
        <option value="fr">Français</option>
        <option value="en">English</option>
        <option value="tz">Thamazight (Latin)</option>
        <option value="ar">العربية</option>
    </select>-->
    <div class="all">
        <div id="cn">
            <h2 id="login-title" >Connecter a votre compte</h2>
            <form id="cnform" method="POST" action="">
                <input type="email" id="emailcn" name="emailcn" placeholder="Votre email" required>
                <input type="password" id="mdpcn" name="mdpcn" placeholder="Votre mot de pass" required>
                 <p id="cnmessage"><?php  echo $cnmessage ?></p>
                <button type="submit" id="btncn">Se Connecter</button>
                
            </form>
            <!--<p id="no-account">Vous n'avez pas un compte? 
                <a id="creer" onclick="showSignup()">S'inscrire?</a></p>-->
           
        </div>
        <div id="ins">
            <h2 id="signup-title">Inscription</h2>
            <form id="insform" method="POST" action="">
                <input type="text" id="nom" name="nom" placeholder="Votre nom" required>
                <input type="text" id="prenom" name="prenom" placeholder="Votre prénom" required>
                <input type="email" id="emailins" name="emailins" placeholder="Votre email" required>
                <input type="password" id="mdpins" name="mdpins" placeholder="Votre mot de pass" required>
                <button type="submit" id="btnins">S'Inscrire</button>
            </form>
            <p id="have-account">Vous avez déjà un compte? 
            <a id="conne" onclick="showLogin()">Se connecter?</a></p>
            <p id="insmessage"><?php echo $message; ?></p>
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
        const translations = {
            en: {
                "login-title": "Login",
                "login-username": "Username",
                "login-password": "Password",
                "login-btn": "Login",
                "no-account": "Don't have an account? <a id='creer' onclick='showSignup()'>Sign up?</a>",
                "signup-title": "Sign Up",
                "signup-username": "Your Username",
                "signup-password": "Your Password",
                "signup-btn": "Sign Up",
                "have-account": "Already have an account? <a id='conne' onclick='showLogin()'>Log in?</a>"
            },
            fr: {
                "login-title": "Connecter",
                "login-username": "Nom Utilisateur",
                "login-password": "Mot de Pass",
                "login-btn": "Se Connecter",
                "no-account": "Vous n'avez pas un compte? <a id='creer' onclick='showSignup()'>S'inscrire?</a>",
                "signup-title": "Inscription",
                "signup-username": "Votre Nom Utilisateur",
                "signup-password": "Votre Mot de Pass",
                "signup-btn": "Sign Up",
                "have-account": "Vous avez déjà un compte? <a id='conne' onclick='showLogin()'>Se connecter?</a>"
            },
            tz: {
                "login-title": "Kcem",
                "login-username": "Isem-ik",
                "login-password": "Awal n uccu",
                "login-btn": "Kcem",
                "no-account": "Ur tesẓi ara? <a id='creer' onclick='showSignup()'>Rnu aḍris?</a>",
                "signup-title": "Rnu aḍris",
                "signup-username": "Isem-ik",
                "signup-password": "Awal n uccu-ik",
                "signup-btn": "Rnu",
                "have-account": "Tesẓi yakan? <a id='conne' onclick='showLogin()'>Kcem?</a>"
            },
            ar: {
                "login-title": "تسجيل الدخول",
                "login-username": "اسم المستخدم",
                "login-password": "كلمة المرور",
                "login-btn": "تسجيل الدخول",
                "no-account": "ليس لديك حساب؟ <a id='creer' onclick='showSignup()'>اشترك</a>",
                "signup-title": "إنشاء حساب",
                "signup-username": "اسم المستخدم الخاص بك",
                "signup-password": "كلمة المرور الخاصة بك",
                "signup-btn": "اشتراك",
                "have-account": "هل لديك حساب بالفعل؟ <a id='conne' onclick='showLogin()'>تسجيل الدخول</a>"
            }
        };

        document.getElementById("lang").addEventListener("change", function() {
            let lang = this.value;
            for (let key in translations[lang]) {
                let element = document.getElementById(key);
                if (element) {
                    if (key.includes("username") || key.includes("password")) {
                        element.placeholder = translations[lang][key];
                    } else {
                        element.innerHTML = translations[lang][key];
                    }
                }
            }
        });

        function showSignup() {
            document.getElementById("ins").style.display = "block";
            document.getElementById("cn").style.display = "none";
        }

        function showLogin() {
            document.getElementById("ins").style.display = "none";
            document.getElementById("cn").style.display = "block";
        }

    </script>
    <!--<div class="loadingScreen">
<!--Mets ton truc dagi-->
<!--<div class="container">
	                <div class="loader"></div>
	                <div class="loader"></div>
	                <div class="loader"></div>
                </div>
            </div>
      <h1>Webmed loading</h1>
    </div>

    <script>
        setTimeout(function() {
    var loadingScreen = document.querySelector(".loadingScreen");
    if (loadingScreen) {
        loadingScreen.style.display = 'none';
    }
}, 5000 = le temps de ton animation);
    </script>-->

</body>
</html>
<?php
$conn->close();
?>