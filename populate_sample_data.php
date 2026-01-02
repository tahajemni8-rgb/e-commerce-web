<?php
require_once 'db_config.php';

$conn = getDBConnection();

// Insert sample categories
$categories = [
    ['name' => 'homme'],
    ['name' => 'femme'],
    ['name' => 'enfant'],
    ['name' => 'accessoires']
];

foreach ($categories as $category) {
    $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?) ON DUPLICATE KEY UPDATE name = name");
    $stmt->bind_param("s", $category['name']);
    $stmt->execute();
    $stmt->close();
}

echo "Categories inserted.\n";

// Get category IDs
$category_ids = [];
$result = $conn->query("SELECT id, name FROM categories");
while ($row = $result->fetch_assoc()) {
    $category_ids[$row['name']] = $row['id'];
}

// Insert sample products
$products = [
    ['name' => 'T-Shirt Homme Blanc', 'price' => 25.99, 'category' => 'homme', 'stock' => 50, 'image' => 'assets/images/none.jpg', 'description' => 'T-shirt blanc confortable pour homme'],
    ['name' => 'Jean Slim Femme', 'price' => 45.99, 'category' => 'femme', 'stock' => 30, 'image' => 'assets/images/none.jpg', 'description' => 'Jean slim tendance pour femme'],
    ['name' => 'Robe d\'Été', 'price' => 35.99, 'category' => 'femme', 'stock' => 25, 'image' => 'assets/images/none.jpg', 'description' => 'Robe légère parfaite pour l\'été'],
    ['name' => 'Pull Enfant', 'price' => 20.99, 'category' => 'enfant', 'stock' => 40, 'image' => 'assets/images/none.jpg', 'description' => 'Pull chaud pour enfant'],
    ['name' => 'Chaussures de Sport Homme', 'price' => 65.99, 'category' => 'homme', 'stock' => 20, 'image' => 'assets/images/none.jpg', 'description' => 'Chaussures de sport confortables'],
    ['name' => 'Veste Femme', 'price' => 75.99, 'category' => 'femme', 'stock' => 15, 'image' => 'assets/images/none.jpg', 'description' => 'Veste élégante pour femme'],
    ['name' => 'Short Enfant', 'price' => 15.99, 'category' => 'enfant', 'stock' => 35, 'image' => 'assets/images/none.jpg', 'description' => 'Short confortable pour enfant'],
    ['name' => 'Chemise Homme', 'price' => 40.99, 'category' => 'homme', 'stock' => 28, 'image' => 'assets/images/none.jpg', 'description' => 'Chemise classique pour homme'],
    ['name' => 'Jupe Femme', 'price' => 30.99, 'category' => 'femme', 'stock' => 22, 'image' => 'assets/images/none.jpg', 'description' => 'Jupe élégante pour femme'],
    ['name' => 'T-Shirt Enfant', 'price' => 18.99, 'category' => 'enfant', 'stock' => 45, 'image' => 'assets/images/none.jpg', 'description' => 'T-shirt coloré pour enfant'],
    ['name' => 'Pantalon Homme', 'price' => 50.99, 'category' => 'homme', 'stock' => 18, 'image' => 'assets/images/none.jpg', 'description' => 'Pantalon classique pour homme'],
    ['name' => 'Manteau Femme', 'price' => 85.99, 'category' => 'femme', 'stock' => 12, 'image' => 'assets/images/none.jpg', 'description' => 'Manteau chaud pour femme'],
    ['name' => 'Chapeau Enfant', 'price' => 12.99, 'category' => 'enfant', 'stock' => 50, 'image' => 'assets/images/none.jpg', 'description' => 'Chapeau fun pour enfant']
];

foreach ($products as $product) {
    $category_id = $category_ids[$product['category']] ?? 1;
    $stmt = $conn->prepare("INSERT INTO products (category_id, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issdis", $category_id, $product['name'], $product['description'], $product['price'], $product['stock'], $product['image']);
    $stmt->execute();
    $stmt->close();
}

echo "Products inserted.\n";

$conn->close();
echo "Sample data insertion completed!\n";
?>