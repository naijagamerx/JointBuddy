<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Handle category deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $categoryId = (int)$_GET['id'];

    if ($db) {
        try {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$categoryId]);
            $_SESSION['success'] = 'Category deleted successfully!';
        } catch (Exception $e) {
            $_SESSION['error'] = AppError::handleDatabaseError($e, 'Error deleting category');
        }
    }
    redirect('/admin/categories.php');
}

// Handle form submission for add/edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    CsrfMiddleware::validate();
    $action = $_POST['action'];

    if ($action === 'add' || $action === 'edit') {
        $name = trim($_POST['name']);
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $description = trim($_POST['description'] ?? '');
        $imagePath = trim($_POST['image_path'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);

        if (empty($name)) {
            $_SESSION['error'] = 'Category name is required';
        } else {
            try {
                // Handle image upload
                if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
                    $imageFile = $_FILES['category_image'];

                    // Validate file type
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    $fileType = mime_content_type($imageFile['tmp_name']);
                    if (!in_array($fileType, $allowedTypes)) {
                        throw new Exception('Invalid file type. Only JPG, PNG, WebP, and GIF are allowed.');
                    }

                    // Validate file size (2MB max)
                    if ($imageFile['size'] > 2 * 1024 * 1024) {
                        throw new Exception('File size too large. Maximum size is 2MB.');
                    }

                    // Upload image
                    $uploadDir = __DIR__ . '/../assets/images/categories/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $imageFileName = 'category_' . time() . '_' . uniqid() . '.' . pathinfo($imageFile['name'], PATHINFO_EXTENSION);
                    $imagePath = $uploadDir . $imageFileName;

                    if (move_uploaded_file($imageFile['tmp_name'], $imagePath)) {
                        $imagePath = assetPath('images/categories/' . $imageFileName);
                    } else {
                        throw new Exception('Failed to upload image.');
                    }
                } elseif (!empty($_POST['image_path'])) {
                    // Use manually entered path if provided
                    $imagePath = trim($_POST['image_path']);
                }

                // Check if slug exists (for new category or when changing slug)
                $checkStmt = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
                $checkStmt->execute([$slug, $_POST['id'] ?? 0]);
                if ($checkStmt->rowCount() > 0) {
                    $slug .= '-' . time();
                }

                if ($action === 'add') {
                    $stmt = $db->prepare("INSERT INTO categories (name, slug, description, image, is_active, sort_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$name, $slug, $description, $imagePath, $isActive, $sortOrder]);
                    $_SESSION['success'] = 'Category created successfully!';
                } else {
                    $categoryId = (int)$_POST['id'];
                    $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, image = ?, is_active = ?, sort_order = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$name, $slug, $description, $imagePath, $isActive, $sortOrder, $categoryId]);
                    $_SESSION['success'] = 'Category updated successfully!';
                }

                redirect('/admin/categories.php');
            } catch (Exception $e) {
                $_SESSION['error'] = AppError::handleDatabaseError($e, 'Error saving category');
            }
        }
    }
}

// Fetch all categories
$categories = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM categories ORDER BY sort_order ASC, name ASC");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        AppError::handleDatabaseError($e, 'Error fetching categories');
    }
}

// Generate admin page content
ob_start();
?>

<div class="w-full max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Categories</h1>
            <p class="text-gray-600 mt-1">Manage product categories</p>
        </div>
        <button onclick="showAddModal()" style="background: #16a34a; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; border: none; cursor: pointer;">
            + Add Category
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div style="background: #d1fae5; border: 1px solid #10b981; color: #065f46; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo  htmlspecialchars($_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo  htmlspecialchars($_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Categories Table -->
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937; margin-bottom: 16px;">All Categories (<?php echo  count($categories) ?>)</h2>

        <?php if (empty($categories)): ?>
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <p>No categories found.</p>
                <button onclick="showAddModal()" style="color: #16a34a; text-decoration: none; font-weight: 600; border: none; background: none; cursor: pointer; margin-top: 10px;">
                    Add your first category
                </button>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Image</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Name</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Slug</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Sort Order</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Status</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px;">
                                    <?php if (!empty($category['image'])): ?>
                                        <img src="<?php echo  htmlspecialchars(url($category['image'])) ?>" alt="<?php echo  htmlspecialchars($category['name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <span style="font-size: 1.5rem;">📁</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; font-weight: 600;"><?php echo  htmlspecialchars($category['name']) ?></td>
                                <td style="padding: 12px; color: #6b7280;"><?php echo  htmlspecialchars($category['slug']) ?></td>
                                <td style="padding: 12px;"><?php echo  (int)$category['sort_order'] ?></td>
                                <td style="padding: 12px;">
                                    <span style="background: <?php echo  $category['is_active'] ? '#10b981' : '#ef4444' ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                        <?php echo  $category['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <button onclick="editCategory(<?php echo  htmlspecialchars(json_encode($category)) ?>)" style="color: #16a34a; text-decoration: none; font-weight: 600; margin-right: 15px; border: none; background: none; cursor: pointer;">
                                        Edit
                                    </button>
                                    <a href="<?php echo  adminUrl('/categories.php?delete=1&id=' . $category['id']) ?>" onclick="return confirm('Are you sure you want to delete this category?')" style="color: #ef4444; text-decoration: none; font-weight: 600;">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Category Modal -->
<div id="categoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 12px; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 id="modalTitle" style="font-size: 1.5rem; font-weight: bold; color: #1f2937; margin-bottom: 20px;">Add Category</h2>

        <form method="POST" action="<?php echo  adminUrl('/categories.php') ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="categoryId" value="">

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Category Name *</label>
                <input type="text" name="name" id="categoryName" required
                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                       placeholder="e.g., Tinctures">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Description</label>
                <textarea name="description" id="categoryDescription" rows="3"
                          style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                          placeholder="Brief description of the category"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Category Image</label>
                <input type="file" name="category_image" id="categoryImage" accept="image/*"
                       onchange="previewImage(this)"
                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem; background: white;">
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px;">Upload a category image (JPG, PNG, WebP, or GIF - max 2MB)</p>
                <div id="imagePreview" style="margin-top: 10px;"></div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Image Path (Alternative)</label>
                <input type="text" name="image_path" id="categoryImagePath"
                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                       placeholder="<?php echo  assetUrl('images/categories/category-name.png') ?>">
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px;">Or enter the full path to an existing image (leave empty if uploading a new image)</p>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Sort Order</label>
                <input type="number" name="sort_order" id="sortOrder" value="0"
                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px;">Lower numbers appear first (0 = first)</p>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="is_active" id="isActive" checked
                           style="margin-right: 10px; width: 18px; height: 18px;">
                    <span style="font-weight: 600; color: #374151;">Active</span>
                </label>
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px; margin-left: 28px;">Inactive categories won't show on the homepage</p>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeModal()"
                        style="padding: 12px 24px; border: 1px solid #d1d5db; border-radius: 8px; background: white; color: #374151; font-weight: 600; cursor: pointer;">
                    Cancel
                </button>
                <button type="submit"
                        style="padding: 12px 24px; border: none; border-radius: 8px; background: #16a34a; color: white; font-weight: 600; cursor: pointer;">
                    Save Category
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Add Category';
    document.getElementById('formAction').value = 'add';
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryName').value = '';
    document.getElementById('categoryDescription').value = '';
    document.getElementById('categoryImage').value = '';
    document.getElementById('categoryImagePath').value = '';
    document.getElementById('imagePreview').innerHTML = '';
    document.getElementById('sortOrder').value = '0';
    document.getElementById('isActive').checked = true;
    document.getElementById('categoryModal').style.display = 'flex';
}

function editCategory(category) {
    document.getElementById('modalTitle').textContent = 'Edit Category';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('categoryId').value = category.id;
    document.getElementById('categoryName').value = category.name;
    document.getElementById('categoryDescription').value = category.description || '';
    document.getElementById('categoryImage').value = '';
    document.getElementById('categoryImagePath').value = category.image || '';
    const imageUrl = category.image ? '<?php echo  addslashes(url('')) ?>' + category.image.replace(/^\/+/, '') : '';
    document.getElementById('imagePreview').innerHTML = category.image ? '<img src="' + imageUrl + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 10px;">' : '';
    document.getElementById('sortOrder').value = category.sort_order || '0';
    document.getElementById('isActive').checked = category.is_active == 1;
    document.getElementById('categoryModal').style.display = 'flex';
}

function previewImage(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = '<img src="' + e.target.result + '" style="width: 150px; height: 150px; object-fit: cover; border-radius: 8px; margin-top: 10px;">';
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

// Close modal when clicking outside
document.getElementById('categoryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php
$content = ob_get_clean();
echo renderAdminPage('Categories - CannaBuddy Admin', $content, 'categories');
?>
