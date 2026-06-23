<?php
// ===================================================================
// Lógica PHP en la parte superior para procesar peticiones y redirigir
// ===================================================================
$api_url = getenv('API_URL');
$success_message = '';
$error_message = '';

function callApi($method, $endpoint, $data = null)
{
    global $api_url;
    $url = $api_url . $endpoint;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    }
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    if ($curl_error) {
        return ['error' => "cURL Error ($method $endpoint): " . $curl_error];
    }
    $decoded_response = json_decode($response, true);
    if ($http_code >= 400) {
        $error_msg = isset($decoded_response['error']) ? $decoded_response['error'] : 'HTTP
Error: ' . $http_code . ' Response: ' . $response;

        return ['error' => $error_msg, 'status' => $http_code];
    }
    if ($http_code >= 200 && $http_code < 300 && $decoded_response !== null) {
        return ['data' => $decoded_response, 'status' => $http_code];
    }
    return ['data' => $response, 'status' => $http_code];
}

// ===================================================================
// Manejo de Acciones (Crear, Actualizar, Eliminar) via POST
// ===================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = $_POST['id'] ?? null;
    if ($action === 'create') {
        $new_product_data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
        ];
        $response = callApi('POST', '/products', $new_product_data);
        if (isset($response['data'])) {
            $success_message = 'Producto creado exitosamente.';
            header('Location: index.php?msg=' . urlencode($success_message));
            exit();
        } else {
            $error_message = 'Error al crear producto: ' . ($response['error'] ?? 'Unknown error');
        }
    } elseif ($action === 'update' && $id !== null) {
        $updated_product_data = [
            'name' => $_POST['name'] ?? '',
            'description' => $_POST['description'] ?? '',
            'price' => $_POST['price'] ?? 0,
        ];

        $response = callApi('PUT', "/products/{$id}", $updated_product_data);
        if (isset($response['data'])) {
            $success_message = 'Producto actualizado exitosamente.';
            header('Location: index.php?msg=' . urlencode($success_message));
            exit();
        } else {
            $error_message = 'Error al actualizar producto: ' . ($response['error'] ?? 'Unknown
error');
        }
    } elseif ($action === 'delete' && $id !== null) {
        $response = callApi('DELETE', "/products/{$id}");
        if (isset($response['data'])) {
            $success_message = 'Producto eliminado exitosamente.';
            header('Location: index.php?msg=' . urlencode($success_message));
            exit();
        } else {
            $error_message = 'Error al eliminar producto: ' . ($response['error'] ?? 'Unknown error');
        }
    }
}
if (isset($_GET['msg'])) {
    $success_message = htmlspecialchars(urldecode($_GET['msg']));
}
$view = 'list';
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'create') {
        $view = 'create';
    } elseif ($_GET['action'] === 'edit' && isset($_GET['id'])) {
        $product_id = $_GET['id'];
        $response = callApi('GET', "/products/{$product_id}");
        if (isset($response['data'])) {
            $product_to_edit = $response['data'];
            $view = 'edit';
        } else {
            $error_message = 'No se pudo obtener el producto para editar: ' . ($response['error']
                ?? 'Unknown error');
            $view = 'list';
        }
    }
}
$products = [];
if ($view === 'list') {
    $response = callApi('GET', '/products');
    if (isset($response['data']) && is_array($response['data'])) {
        $products = $response['data'];
    } else {
        $error_message = 'Error al cargar la lista de productos: ' . ($response['error']
            ?? 'Unknown error');
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Frontend E-commerce CRUD</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .product { border: 1px solid #ccc; margin-bottom: 15px; padding: 15px; border-radius: 5px; }
        .product h3 { margin-top: 0; color: #333; }
        .product p { margin-bottom: 5px; }
        .actions a, .actions button { margin-right: 10px; }
        .actions button { background-color: #f44336; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer; }
        .actions button:hover { background-color: #d32f2f; }
        form { margin-bottom: 20px; padding: 15px; border: 1px solid #eee; border-radius: 5px; background-color: #f9f9f9; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="number"], textarea { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button[type="submit"] { background-color: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; }
        button[type="submit"]:hover { background-color: #45a049; }
        .message-success { color: green; font-weight: bold; }
        .message-error { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Bienvenido a Innovate Solutions</h1>
    <p>Plataforma e-commerce en desarrollo</p>

    <?php
    if (!empty($success_message)) {
        echo "<p class='message-success'>" . $success_message . '</p>';
    }
    if (!empty($error_message)) {
        echo "<p class='message-error'>" . $error_message . '</p>';
    }
    ?>

    <?php if ($view === 'create'): ?>
        <h2>Crear Nuevo Producto</h2>
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="create">
            <label for="name">Nombre:</label>
            <input type="text" id="name" name="name" required>
            <label for="description">Descripción:</label>
            <textarea id="description" name="description"></textarea>
            <label for="price">Precio:</label>
            <input type="number" id="price" name="price" step="0.01" required>
            <button type="submit">Guardar Producto</button>
            <a href="index.php">Cancelar</a>
        </form>

    <?php elseif ($view === 'edit' && isset($product_to_edit)): ?>
        <h2>Editar Producto</h2>
        <form action="index.php" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product_to_edit['id']); ?>">
            <label for="name">Nombre:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product_to_edit['name']); ?>" required>
            <label for="description">Descripción:</label>
            <textarea id="description" name="description"><?php echo htmlspecialchars($product_to_edit['description'] ?? ''); ?></textarea>
            <label for="price">Precio:</label>
            <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product_to_edit['price']); ?>" required>
            <button type="submit">Actualizar Producto</button>
            <a href="index.php">Cancelar</a>
        </form>

    <?php else: ?>
        <h2>Lista de Productos</h2>
        <p><a href="index.php?action=create">Crear Nuevo Producto</a></p>

        <?php if (empty($products)): ?>
            <p>No hay productos disponibles.</p>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="product">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p><strong>Precio: $<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></strong></p>
                    <p><?php echo htmlspecialchars($product['description'] ?? 'No description'); ?></p>
                    <small>ID: <?php echo htmlspecialchars($product['id']); ?></small>
                    <div class="actions">
                        <a href="index.php?action=edit&id=<?php echo htmlspecialchars($product['id']); ?>">Editar</a>
                        <form action="index.php" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo htmlspecialchars($product['id']); ?>">
                            <button type="submit" onclick="return confirm('¿Estás seguro de que quieres eliminar este producto?');">Eliminar</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
