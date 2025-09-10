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

    function generateProductCode($db, $name)
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 3));
        if ($prefix === '') {
            $prefix = 'PRD';
        }

        $stmt = $db->prepare("SELECT code FROM products WHERE code LIKE ? ORDER BY code DESC LIMIT 1");
        $stmt->execute([$prefix . '-%']);
        $lastCode = $stmt->fetchColumn();

        if ($lastCode) {
            $lastNumber = (int) substr($lastCode, 4);
            $newNumber  = str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
        } else {
            $newNumber = "000001";
        }

        return $prefix . '-' . $newNumber;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name        = $_POST['name'] ?? '';
        $description = $_POST['description'] ?? '';
        $price       = $_POST['price'] ?? 0;
        $quantity    = $_POST['quantity'] ?? 0;

        $code = generateProductCode($db, $name);

        $uploadDir = "uploads/";
        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $allowedExts = ['jpg', 'jpeg', 'png'];
        $maxSize     = 2 * 1024 * 1024;
        $imagePaths  = [null, null];

        for ($i = 1; $i <= 2; $i++) {
            if (isset($_FILES["image$i"]) && $_FILES["image$i"]['error'] === UPLOAD_ERR_OK) {
                $fileTmp  = $_FILES["image$i"]['tmp_name'];
                $fileName = basename($_FILES["image$i"]['name']);
                $ext      = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $fileSize = $_FILES["image$i"]['size'];

                if (! in_array($ext, $allowedExts)) {
                    echo "Image $i must be JPEG or PNG.<br>";
                    continue;
                }
                if ($fileSize > $maxSize) {
                    echo "Image $i exceeds 2MB.<br>";
                    continue;
                }

                $newName  = uniqid("img{$i}_") . "." . $ext;
                $destPath = $uploadDir . $newName;

                if (move_uploaded_file($fileTmp, $destPath)) {
                    compressImage($destPath, $destPath);
                    $imagePaths[$i - 1] = $destPath;
                }
            }
        }

        $stmt = $db->prepare("INSERT INTO products (code, name, description, price, quantity, image1, image2)
                      VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $name, $description, $price, $quantity, $imagePaths[0], $imagePaths[1]]);

        $_SESSION['success'] = "Product <b>$name</b> added successfully!";
        header("Location: dashboard.php");
        exit();

    }
?>
<!DOCTYPE html>
<?php if (! empty($_SESSION['success'])): ?>
<div class="notification"><?php echo $_SESSION['success']; ?></div>
<script>
    setTimeout(() => {
        document.querySelector('.notification').style.display = 'none';
    }, 3000);
</script>
<?php unset($_SESSION['success']);endif; ?>


<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Add Products</title>
    <link rel="stylesheet" href="styles/dashboard.css">
  <link rel="icon" type="image/svg+xml" href="assets/logo.svg">

</head>
<body>


    <h1>Welcome,                                                                                 <?php echo htmlspecialchars($_SESSION['user']); ?>!</h1>

    <h2>Add New Product</h2>
 <form method="post" enctype="multipart/form-data" >
    <div class="__product_name">
        <label for="name">Product Name</label>
        <input type="text" id="name" name="name" placeholder="Enter product name." required>
    </div>
    <div class="__description-field">
        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="Describe your product..."></textarea>
    </div>
    <div class="__product_price">
        <label for="price">Price</label>
        <input type="number" id="price" step="0.01" name="price" placeholder="" required>
    </div>
    <div class="__product_quantity">
        <label for="quantity">Quantity</label>
        <input type="number" id="quantity" name="quantity" placeholder="" required>
    </div>
    <div class="__product_image_1">
        <label for="image1">Image 1:</label>
        <input type="file" id="image1" name="image1" class="__image_upload" accept="image/jpeg,image/png">
    </div>
    <div class="__product_image_2">
        <label for="image2">Image 2:</label>
        <input type="file" id="image2" name="image2" class="__image_upload" accept="image/jpeg,image/png">
    </div>
    <button type="submit" class="__add_product_btn">Add Product</button>
</form>

    <div class="actions">
        <a href="inventory.php" class="inventory">Show Inventory</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>
    <script src="scripts/vanilla-tilt.js"></script>
</body>
</html>
