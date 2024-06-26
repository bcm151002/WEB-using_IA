<?php
// Include Azure SDK for PHP
require_once 'vendor/autoload.php';
use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $target_dir = "uploads/";
    $target_file = $target_dir . basename($_FILES["imageToUpload"]["name"]);
    move_uploaded_file($_FILES["imageToUpload"]["tmp_name"], $target_file);

    // Call Azure Face API
    $url = 'https://eastus.api.cognitive.microsoft.com/face/v1.0/detect';
    $headers = array(
        "Ocp-Apim-Subscription-Key: key",
        "Content-Type: application/octet-stream"
    );
    $data = file_get_contents($target_file);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $fileText = "";
    if ($httpcode == 200) {
        $faces = json_decode($response);
        foreach ($faces as $face) {
            $fileText .= "Face ID: " . $face->faceId . "\n";
            $fileText .= "Face rectangle: Top: " . $face->faceRectangle->top . ", Left: " . $face->faceRectangle->left . ", Width: " . $face->faceRectangle->width . ", Height: " . $face->faceRectangle->height . "\n";
            $fileText .= "Gender: " . $face->faceAttributes->gender . "\n";
    	    $fileText .= "Age: " . $face->faceAttributes->age . "\n";
    	    $fileText .= "Emotions: " . json_encode($face->faceAttributes->emotion) . "\n";
	}
    } else {
        die("Error calling Face API: " . $httpcode . " - Response: " . $response);
    }

    // Create a BlobRestProxy object
    $connectionString = 'connectionString';
    $blobClient = BlobRestProxy::createBlobService($connectionString);

    // Upload file to Blob Storage
    $containerName = "container";
    $blobName = $_FILES["imageToUpload"]["name"];
    $content = fopen($target_file, "r");
    $blobClient->createBlockBlob($containerName, $blobName, $content);

    // Get the URL of the uploaded file
    $blobUrl = sprintf('https://%s.blob.core.windows.net/%s/%s', 'storagelabstd', $containerName, $blobName);

    // Delete the file from App Service
    unlink($target_file);

    // Create a connection to the database
    try {
        $conn = new PDO("sqlsrv:server = tcp:serverstd.database.windows.net,1433; Database = STD", "username", "{parola}");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $e) {
        print("Error connecting to SQL Server.");
        die(print_r($e));
    }

    // Insert the file information into the database
    $sql = "INSERT INTO fileinfo (filename, blob_store_addr, time, file_text) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$blobName, $blobUrl, date("Y-m-d H:i:s"), $fileText]);

    echo "File uploaded and processed successfully.";
}
?>
