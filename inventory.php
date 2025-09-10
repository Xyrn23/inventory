<?php
    session_start();
    if (! isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }

    try {
        $db = new PDO("sqlite:C:/Users/xyrn/Documents/inventory/inventory.db");
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    if (isset($_GET['delete'])) {
        $code = $_GET['delete'];

        $stmt = $db->prepare("SELECT image1, image2 FROM products WHERE code = ?");
        $stmt->execute([$code]);
        $images = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $db->prepare("DELETE FROM products WHERE code = ?");
        $stmt->execute([$code]);

        foreach (['image1', 'image2'] as $img) {
            if (! empty($images[$img]) && file_exists($images[$img])) {
                unlink($images[$img]);
            }
        }

        header("Location: inventory.php");
        exit();
    }

    $products = $db->query("SELECT * FROM products ORDER BY code DESC")->fetchAll(PDO::FETCH_ASSOC);
    function compressImage($source, $destination, $quality = 70)
    {
        $info = getimagesize($source);

        if ($info['mime'] === 'image/jpeg') {
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
        } elseif ($info['mime'] === 'image/png') {
            $image = imagecreatefrompng($source);
            $bg    = imagecreatetruecolor(imagesx($image), imagesy($image));
            $white = imagecolorallocate($bg, 255, 255, 255);
            imagefill($bg, 0, 0, $white);
            imagecopy($bg, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            imagejpeg($bg, $destination, $quality);
            imagedestroy($bg);
        }
    }

    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['original_code'])) {
        $original_code = $_POST['original_code'];
        $newCode       = $_POST['code'];
        $name          = $_POST['name'];
        $description   = $_POST['description'];
        $price         = $_POST['price'];
        $quantity      = $_POST['quantity'];

        
        $stmt = $db->prepare("SELECT image1, image2 FROM products WHERE code = ?");
        $stmt->execute([$original_code]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        $uploadDir   = "uploads/";
        $imagePaths  = [$current['image1'], $current['image2']];
        $allowedExts = ['jpg', 'jpeg', 'png'];
        $maxSize     = 2 * 1024 * 1024;

        for ($i = 1; $i <= 2; $i++) {
            if (isset($_FILES["image$i"]) && $_FILES["image$i"]['error'] === UPLOAD_ERR_OK) {
                $fileTmp  = $_FILES["image$i"]['tmp_name'];
                $fileName = basename($_FILES["image$i"]['name']);
                $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $fileSize = $_FILES["image$i"]['size'];

                if (! in_array($ext, $allowedExts) || $fileSize > $maxSize) {
                    continue; 
                }

                if (! empty($imagePaths[$i - 1]) && file_exists($imagePaths[$i - 1])) {
                    unlink($imagePaths[$i - 1]);
                }

                $newName  = uniqid("img{$i}_") . ".jpg"; 
                $destPath = $uploadDir . $newName;

                if (move_uploaded_file($fileTmp, $destPath)) {
                    compressImage($destPath, $destPath);
                    $imagePaths[$i - 1] = $destPath;
                }
            }
        }

        $stmt = $db->prepare("UPDATE products
                          SET code=?, name=?, description=?, price=?, quantity=?, image1=?, image2=?
                          WHERE code=?");
        $stmt->execute([$newCode, $name, $description, $price, $quantity, $imagePaths[0], $imagePaths[1], $original_code]);

        header("Location: inventory.php");
        exit();
    }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory</title>
    <link rel="stylesheet" href="styles/inventory.css">
  <link rel="icon" type="image/svg+xml" href="assets/logo.svg">

</head>
<body>
    <h1>Product Inventory</h1>
    
    <div class="search-sort-container">
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search by code or name...">
            <button onclick="searchProducts()">Search</button>
        </div>
        <div class="sort-container">
            <label for="sortSelect">Sort by:</label>
            <select id="sortSelect" onchange="sortProducts()">
                <option value="default">Default</option>
                <option value="name-asc">Name (A-Z)</option>
                <option value="name-desc">Name (Z-A)</option>
                <option value="price-asc">Price (Low to High)</option>
                <option value="price-desc">Price (High to Low)</option>
                <option value="quantity-asc">Quantity (Low to High)</option>
                <option value="quantity-desc">Quantity (High to Low)</option>
            </select>
        </div>
    </div>
    
    <div class="grid" id="productsGrid">
        <?php foreach ($products as $p): ?>
            <div class="card">
                <strong>Code:
                    <?php echo htmlspecialchars($p['code']); ?></strong><br>
                <?php if ($p['image1']): ?>
                    <img src="<?php echo htmlspecialchars($p['image1']); ?>" alt="Image 1">
                <?php endif; ?>
                <?php if ($p['image2']): ?>
                    <img src="<?php echo htmlspecialchars($p['image2']); ?>" alt="Image 2">
                <?php endif; ?>
                <h3><?php echo htmlspecialchars($p['name']); ?></h3>
                <p class="price">₱<?php echo number_format($p['price'], 2); ?></p>
                <p>Stock: <?php echo htmlspecialchars($p['quantity']); ?></p>
                <?php if (! empty($p['description'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($p['description'])); ?></p>
                <?php endif; ?>

 <div class="card-actions">
    <button onclick="openEditModal('<?php echo htmlspecialchars($p['code']); ?>',
                                    '<?php echo htmlspecialchars($p['name']); ?>',
                                    '<?php echo htmlspecialchars($p['description']); ?>',
                                    '<?php echo htmlspecialchars($p['price']); ?>',
                                    '<?php echo htmlspecialchars($p['quantity']); ?>')"
            class="edit">Edit</button>
    <button onclick="openDeleteModal('<?php echo htmlspecialchars($p['code']); ?>')"
            class="delete">Delete</button>
</div>
            </div>
        <?php endforeach; ?>
    </div>
  <div class="actions">
        <a href="dashboard.php" class="back">Back to Dashboard</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
<div id="editModal" class="modal">
  <div class="modal-content">
    <h2>Edit Product</h2>
    <button type="button" class="close" onclick="closeEditModal()">&times;</button>
    <form method="post" enctype="multipart/form-data" id="editForm">
      <input type="hidden" name="original_code" id="original_code">

      <label>Code:</label>
      <input type="text" name="code" id="edit_code" required>

      <label>Name:</label>
      <input type="text" name="name" id="edit_name" required>

      <label>Description:</label>
      <textarea name="description" id="edit_description"></textarea>

      <label>Price:</label>
      <input type="number" step="0.01" name="price" id="edit_price" required>

      <label>Quantity:</label>
      <input type="number" name="quantity" id="edit_quantity" required>

      <label>Replace Image 1:</label>
      <input type="file" name="image1" accept="image/jpeg,image/png">

      <label>Replace Image 2:</label>
      <input type="file" name="image2" accept="image/jpeg,image/png">

      <button type="submit">Save</button>
      <button type="button" onclick="closeEditModal()">Cancel</button>
    </form>
  </div>
</div>

<script src="scripts/inventory.js" ></script>
<script src="scripts/vanilla-tilt.js"></script>


<div id="deleteModal" class="modal">
  <div class="modal-content delete-modal-content">
    <h2>Confirm Deletion</h2>
    <button type="button" class="close" onclick="closeDeleteModal()">&times;</button>
    <p>Are you sure you want to delete this product?</p>
    <div class="delete-actions">
      <a href="#" id="confirmDelete" class="confirm-delete">Delete</a>
      <button type="button" onclick="closeDeleteModal()">Cancel</button>
    </div>
  </div>
</div>

<script>
    VanillaTilt.init(document.querySelectorAll(".card"), {
        max: 5,
        speed: 400,
        glare: true,
        "max-glare": 0.2,
    });
    
    VanillaTilt.init(document.querySelectorAll(".modal-content"), {
        max: 3,
        speed: 400,
        glare: true,
        "max-glare": 0.1,
    });
</script>
</body>
</html>
