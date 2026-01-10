<?php
require_once '../config/dbconfig.php';

echo "<h2>Food Items Table Structure:</h2>";
$result = $conn->query("SHOW COLUMNS FROM food_items");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
while($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>Categories Table Structure:</h2>";
$result2 = $conn->query("SHOW COLUMNS FROM categories");
if($result2) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    while($row = $result2->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "Categories table doesn't exist!";
}

echo "<h2>Test Query:</h2>";
$test_query = "SELECT fi.food_items_id, fi.category_id, fi.food_name, fi.description, fi.price, c.name 
          FROM food_items fi 
          LEFT JOIN categories c ON fi.category_id = c.category_id
          LIMIT 5";
          
echo "Query: " . $test_query . "<br><br>";

$test_result = $conn->query($test_query);
if($test_result) {
    echo "Rows returned: " . $test_result->num_rows . "<br><br>";
    while($item = $test_result->fetch_assoc()) {
        echo "<pre>";
        print_r($item);
        echo "</pre>";
    }
} else {
    echo "ERROR: " . $conn->error;
}
?>