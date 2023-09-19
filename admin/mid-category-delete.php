<?php require_once('header.php'); ?>

<?php
// Preventing the direct access of this page.
if (!isset($_REQUEST['id'])) {
    header('location: logout.php');
    exit;
}

// Check the id is valid or not
$statement = $pdo->prepare("SELECT * FROM tbl_mid_category WHERE mcat_id=?");
$statement->execute(array($_REQUEST['id']));
$total = $statement->rowCount();
if ($total == 0) {
    header('location: logout.php');
    exit;
}

// Getting all ecat ids
$statement = $pdo->prepare("SELECT ecat_id FROM tbl_end_category WHERE mcat_id=?");
$statement->execute(array($_REQUEST['id']));
$result = $statement->fetchAll(PDO::FETCH_COLUMN);
$ecat_ids = $result ? $result : [];

if (!empty($ecat_ids)) {
    // Delete all products related to the end categories
    $statement = $pdo->prepare("SELECT p_id, p_featured_photo FROM tbl_product WHERE ecat_id IN (" . implode(',', $ecat_ids) . ")");
    $statement->execute();
    $products = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($products as $product) {
        if (!empty($product['p_featured_photo'])) {
            unlink('../assets/uploads/' . $product['p_featured_photo']);
        }

        // Delete related records from other tables (assuming foreign key constraints with CASCADE DELETE)
        $pdo->prepare("DELETE FROM tbl_product_size WHERE p_id = ?")->execute([$product['p_id']]);
        $pdo->prepare("DELETE FROM tbl_product_color WHERE p_id = ?")->execute([$product['p_id']]);
        $pdo->prepare("DELETE FROM tbl_rating WHERE p_id = ?")->execute([$product['p_id']]);
        $pdo->prepare("DELETE FROM tbl_order WHERE product_id = ?")->execute([$product['p_id']]);
    }

    // Delete all products related to the end categories
    $pdo->prepare("DELETE FROM tbl_product WHERE ecat_id IN (" . implode(',', $ecat_ids) . ")")->execute();

    // Delete all end categories related to the mid category
    $pdo->prepare("DELETE FROM tbl_end_category WHERE mcat_id = ?")->execute([$_REQUEST['id']]);
}

// Delete the mid category
$pdo->prepare("DELETE FROM tbl_mid_category WHERE mcat_id = ?")->execute([$_REQUEST['id']]);

header('location: mid-category.php');
?>
