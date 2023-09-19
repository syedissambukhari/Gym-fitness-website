<?php require_once('header.php'); ?>

<?php
// Preventing the direct access of this page.
if (!isset($_REQUEST['id'])) {
    header('location: logout.php');
    exit;
}

// Check if the ecat_id is valid or not
$statement = $pdo->prepare("SELECT * FROM tbl_end_category WHERE ecat_id=?");
$statement->execute(array($_REQUEST['id']));
$total = $statement->rowCount();
if ($total == 0) {
    header('location: logout.php');
    exit;
}

// Getting all product IDs related to the end category
$statement = $pdo->prepare("SELECT p_id, p_featured_photo FROM tbl_product WHERE ecat_id=?");
$statement->execute(array($_REQUEST['id']));
$result = $statement->fetchAll(PDO::FETCH_ASSOC);
$p_ids = array_column($result, 'p_id');

foreach ($result as $row) {
    if (!empty($row['p_featured_photo'])) {
        unlink('../assets/uploads/' . $row['p_featured_photo']);
    }

    // Delete related records from other tables (assuming foreign key constraints with CASCADE DELETE)
    $pdo->prepare("DELETE FROM tbl_product_size WHERE p_id = ?")->execute([$row['p_id']]);
    $pdo->prepare("DELETE FROM tbl_product_color WHERE p_id = ?")->execute([$row['p_id']]);
    $pdo->prepare("DELETE FROM tbl_rating WHERE p_id = ?")->execute([$row['p_id']]);

    // Delete from tbl_payment
    $statement = $pdo->prepare("SELECT payment_id FROM tbl_order WHERE product_id=?");
    $statement->execute([$row['p_id']]);
    $payment_ids = array_column($statement->fetchAll(PDO::FETCH_ASSOC), 'payment_id');
    $pdo->prepare("DELETE FROM tbl_payment WHERE payment_id IN (" . implode(',', $payment_ids) . ")")->execute();

    // Delete from tbl_order
    $pdo->prepare("DELETE FROM tbl_order WHERE product_id=?")->execute([$row['p_id']]);
}

// Delete all products related to the end category
$pdo->prepare("DELETE FROM tbl_product WHERE ecat_id = ?")->execute([$_REQUEST['id']]);

// Delete the end category
$pdo->prepare("DELETE FROM tbl_end_category WHERE ecat_id = ?")->execute([$_REQUEST['id']]);

header('location: end-category.php');
?>
