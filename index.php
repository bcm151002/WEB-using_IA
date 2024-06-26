<!DOCTYPE html>
<html>
<head>
    <title>Face Detection</title>
</head>
<body>
<h2>Incarcare poza</h2>
<form action="ceva.php" method="post" enctype="multipart/form-data">
  Selecteaza fisier pentru incarcare:
  <input type="file" name="imageToUpload" id="imageToUpload">
  <input type="submit" value="Incarca Fisier" name="submit">
</form>
    <?php
    // PHP Data Objects(PDO) Sample Code:
    try {
        $conn = new PDO("sqlsrv:server = tcp:serverstd.database.windows.net,1433; Database = STD", "username", "{parola}");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch (PDOException $e) {
        print("Error connecting to SQL Server.");
        die(print_r($e));
    }

    // SQL Server Extension Sample Code:
    $connectionInfo = array("UID" => "username", "pwd" => "{parola}", "Database" => "STD", "LoginTimeout" => 30, "Encrypt" => 1, "TrustServerCertificate" => 0);
    $serverName = "tcp:serverstd.database.windows.net,1433";
    $conn = sqlsrv_connect($serverName, $connectionInfo);

    $sql = "SELECT COUNT(*) as count FROM [dbo].[fileinfo]";
    $getResults = sqlsrv_query($conn, $sql);
    if ($getResults == FALSE) {
        die(print_r(sqlsrv_errors(), true));
    }
    $row = sqlsrv_fetch_array($getResults, SQLSRV_FETCH_ASSOC);
    echo "Numarul de randuri din tabela fileinfo: " . $row['count'] . "<br />";

    // Selectarea datelor din baza de date
    $sql = "SELECT * FROM [dbo].[fileinfo]";
    $getResults = sqlsrv_query($conn, $sql);
    if ($getResults == FALSE) {
        die(print_r(sqlsrv_errors(), true));
    }

    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Filename</th><th>Link</th><th>File Text</th></tr>";
    while ($row = sqlsrv_fetch_array($getResults, SQLSRV_FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['filename'] . "</td>";
        echo "<td><a href='" . $row['blob_store_addr'] . "' target='_blank'>Link</a></td>";
        echo "<td>" . $row['file_text'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    sqlsrv_free_stmt($getResults);
    ?>
</body>
</html>
