<?php require_once('header.php'); ?>

<?php
// Preventing the direct access of this page.
if (!isset($_REQUEST['id'])) {
    header('location: logout.php');
    exit;
}

// Check the id is valid or not
$statement = $pdo->prepare("SELECT * FROM tbl_top_category WHERE tcat_id=?");
$statement->execute(array($_REQUEST['id']));
$total = $statement->rowCount();
if ($total == 0) {
    header('location: logout.php');
    exit;
}

// Get all product IDs related to the top category and its subcategories
$statement = $pdo->prepare("SELECT p.p_id, p.p_featured_photo, pp.photo
                            FROM tbl_product p
                            LEFT JOIN tbl_product_photo pp ON p.p_id = pp.p_id
                            WHERE p.ecat_id IN (
                                SELECT ecat_id
                                FROM tbl_end_category
                                WHERE mcat_id IN (
                                    SELECT mcat_id
                                    FROM tbl_mid_category
                                    WHERE tcat_id = ?
                                )
                            )");
$statement->execute(array($_REQUEST['id']));
$products = $statement->fetchAll(PDO::FETCH_ASSOC);

foreach ($products as $product) {
    if (!empty($product['p_featured_photo'])) {
        unlink('../assets/uploads/' . $product['p_featured_photo']);
    }
    if (!empty($product['photo'])) {
        unlink('../assets/uploads/product_photos/' . $product['photo']);
    }

    // Delete related records from other tables (assuming foreign key constraints with CASCADE DELETE)
    $statement = $pdo->prepare("DELETE FROM tbl_product_size WHERE p_id = ?");
    $statement->execute(array($product['p_id']));

    $statement = $pdo->prepare("DELETE FROM tbl_product_color WHERE p_id = ?");
    $statement->execute(array($product['p_id']));

    $statement = $pdo->prepare("DELETE FROM tbl_rating WHERE p_id = ?");
    $statement->execute(array($product['p_id']));

    $statement = $pdo->prepare("DELETE FROM tbl_order WHERE product_id = ?");
    $statement->execute(array($product['p_id']));
}

// Delete all products related to the top category and its subcategories
$statement = $pdo->prepare("DELETE FROM tbl_product WHERE ecat_id IN (
                                SELECT ecat_id
                                FROM tbl_end_category
                                WHERE mcat_id IN (
                                    SELECT mcat_id
                                    FROM tbl_mid_category
                                    WHERE tcat_id = ?
                                )
                            )");
$statement->execute(array($_REQUEST['id']));

// Delete all end categories related to the top category and its subcategories
$statement = $pdo->prepare("DELETE FROM tbl_end_category WHERE mcat_id IN (
                                SELECT mcat_id
                                FROM tbl_mid_category
                                WHERE tcat_id = ?
                            )");
$statement->execute(array($_REQUEST['id']));

// Delete all mid categories related to the top category
$statement = $pdo->prepare("DELETE FROM tbl_mid_category WHERE tcat_id = ?");
$statement->execute(array($_REQUEST['id']));

// Delete the top category
$statement = $pdo->prepare("DELETE FROM tbl_top_category WHERE tcat_id = ?");
$statement->execute(array($_REQUEST['id']));

header('location: top-category.php');
?>
