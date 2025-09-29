<?php
// Debug temporal para ver qué datos están llegando
session_start();
echo "<h3>Debugging POST Data:</h3>";
echo "<pre>";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "\$_POST contents:\n";
var_dump($_POST);
echo "\nSpecific field checks:\n";
echo "title: '" . ($_POST['title'] ?? 'NOT_SET') . "'\n";
echo "description: '" . ($_POST['description'] ?? 'NOT_SET') . "'\n";
echo "project_type_id: '" . ($_POST['project_type_id'] ?? 'NOT_SET') . "'\n";

echo "\nEmpty checks:\n";
echo "empty(title): " . (empty($_POST['title']) ? 'TRUE' : 'FALSE') . "\n";
echo "empty(description): " . (empty($_POST['description']) ? 'TRUE' : 'FALSE') . "\n";
echo "empty(project_type_id): " . (empty($_POST['project_type_id']) ? 'TRUE' : 'FALSE') . "\n";

echo "\nTrimmed checks:\n";
if (isset($_POST['description'])) {
    $desc = trim($_POST['description']);
    echo "trim(description): '" . $desc . "'\n";
    echo "empty(trim(description)): " . (empty($desc) ? 'TRUE' : 'FALSE') . "\n";
    echo "strlen(trim(description)): " . strlen($desc) . "\n";
}
echo "</pre>";
?>