<?php
// voir_document.php

if (!isset($_GET['file'])) {
    echo "Paramètre 'file' manquant.";
    exit;
}

$file = basename($_GET['file']); 
$relativePath = "./documents/" . $file;
$absoluteFilePath = __DIR__ . '/' . $relativePath;

if (!file_exists($absoluteFilePath)) {
    echo "Fichier introuvable.";
    exit;
}


$host = $_SERVER['HTTP_HOST'];
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$fullUrlToPdf = "$protocol://$host/site2/documents/" . urlencode($file);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Voir le Document PDF</title>
    <style>
        html, body {
            margin: 0;
            height: 100%;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
   
    <iframe src="./pdfjs/web/viewer.html?file=<?= htmlspecialchars($fullUrlToPdf) ?>"></iframe>
</body>
</html>
