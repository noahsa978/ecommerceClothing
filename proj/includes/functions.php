<!-- functions of php for the website -->
 <?php
function getProducts($conn) {
    $sql = "SELECT * FROM products ORDER BY created_at DESC";
    $result = $conn->query($sql);
    return $result;
}

function getProductDetails($conn, $id) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>
