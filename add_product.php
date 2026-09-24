<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <div class="top_bar">
        <span class="title">OWN Cashier</span>
        <button class="back_btn" onclick="location.href='index.php'">Back to Home</button>
    </div>
    <div class="form_container">
        <div class="form_card">
            <form action="actions/do_add_product.php" method="POST">
                <div class="form_item">
                    <label for="name">Product Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form_item">
                    <label for="price">Product Price</label>
                    <input type="number" id="price" name="price" required>
                </div>
                <div class="form_item">
                    <label for="stock">Product Stock</label>
                    <input type="number" id="stock" name="stock" required>
                </div>
                <div class="form_item">
                    <label for="category">Product Category</label>
                    <select id="category" name="category" required>
                        <option value="main_course">Main Course</option>
                        <option value="snack">Snack</option>
                        <option value="drink">Drink</option>
                        <option value="dessert">Dessert</option>
                    </select>
                </div>
                <div class="form_item">
                    <button type="submit">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</body>

</html>