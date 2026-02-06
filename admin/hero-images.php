<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

// Handle hero image deletion
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $imageId = (int)$_GET['id'];

    if ($db) {
        try {
            $stmt = $db->prepare("DELETE FROM hero_images WHERE id = ?");
            $stmt->execute([$imageId]);
            $_SESSION['success'] = 'Hero image deleted successfully!';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error deleting hero image: ' . $e->getMessage();
        }
    }
    redirect(adminUrl('/hero-images.php'));
}

// Handle demo images creation
if (isset($_GET['add_demo'])) {
    if ($db) {
        try {
            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../assets/images/hero/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Demo images data
            $demoImages = [
                [
                    'title' => 'Premium Cannabis Accessories',
                    'subtitle' => 'Discover the JointBuddy Collection',
                    'link_url' => shopUrl('/'),
                    'sort_order' => 1,
                    'image_name' => 'demo1.jpg'
                ],
                [
                    'title' => 'Eco-Friendly & Durable',
                    'subtitle' => 'Made with PLA+PETG materials',
                    'link_url' => url('/about/'),
                    'sort_order' => 2,
                    'image_name' => 'demo2.jpg'
                ],
                [
                    'title' => 'Join the Community',
                    'subtitle' => 'Connect with fellow cannabis enthusiasts',
                    'link_url' => url('/register/'),
                    'sort_order' => 3,
                    'image_name' => 'demo3.jpg'
                ]
            ];

            $count = 0;
            foreach ($demoImages as $demo) {
                // Create a placeholder image (simple colored rectangle)
                $imagePath = $uploadDir . $demo['image_name'];

                // Create a simple image using GD (if available)
                if (extension_loaded('gd')) {
                    $width = 1600;
                    $height = 900;
                    $image = imagecreate($width, $height);

                    // Random colors for each image
                    $colors = [
                        [41, 128, 185],  // Blue
                        [46, 204, 113],  // Green
                        [155, 89, 182],  // Purple
                        [230, 126, 34],  // Orange
                        [231, 76, 60]    // Red
                    ];
                    $color = $colors[$count % count($colors)];

                    $bgColor = imagecolorallocate($image, $color[0], $color[1], $color[2]);
                    $textColor = imagecolorallocate($image, 255, 255, 255);

                    // Add text
                    $text = 'Demo Image ' . ($count + 1);
                    imagestring($image, 5, $width/2 - 100, $height/2 - 20, $text, $textColor);

                    imagejpeg($image, $imagePath, 90);
                    imagedestroy($image);
                } else {
                    // Fallback: create empty file
                    file_put_contents($imagePath, '');
                }

                $publicPath = assetUrl('images/hero/' . $demo['image_name']);

                // Insert into database
                $stmt = $db->prepare("INSERT INTO hero_images (title, subtitle, image_path, link_url, is_active, sort_order, slider_height, padding_top, padding_bottom, show_text, created_at, updated_at) VALUES (?, ?, ?, ?, 1, ?, 'auto', '0', '0', 1, NOW(), NOW())");
                $stmt->execute([$demo['title'], $demo['subtitle'], $publicPath, $demo['link_url'], $demo['sort_order']]);
                $count++;
            }

            $_SESSION['success'] = 'Demo images added successfully!';
        } catch (Exception $e) {
            $_SESSION['error'] = AppError::handleDatabaseError($e, 'Error adding demo images');
        }
    }
    redirect(adminUrl('/hero-images.php'));
}

// Handle form submission for add/edit
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add' || $action === 'edit') {
        $title = trim($_POST['title']);
        $subtitle = trim($_POST['subtitle'] ?? '');
        $linkUrl = trim($_POST['link_url'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $sliderHeight = trim($_POST['slider_height'] ?? 'auto');
        $paddingTop = trim($_POST['padding_top'] ?? '0');
        $paddingBottom = trim($_POST['padding_bottom'] ?? '0');
        $showText = isset($_POST['show_text']) ? 1 : 0;

        // Handle custom height
        if ($sliderHeight === 'custom') {
            $customHeight = trim($_POST['custom_height'] ?? '');
            if (!empty($customHeight)) {
                $sliderHeight = $customHeight;
            } else {
                $sliderHeight = 'auto';
            }
        }

        $imagePath = '';

        // Handle file upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../assets/images/hero/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'hero_' . time() . '_' . rand(1000, 9999) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $uploadPath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $imagePath = assetUrl('images/hero/' . $fileName);
            } else {
                $_SESSION['error'] = 'Error uploading image. Please try again.';
            }
        } else if (!empty($_POST['image_path'])) {
            // Fallback to manually entered path
            $imagePath = trim($_POST['image_path']);
        }

        if (empty($title) || empty($imagePath)) {
            $_SESSION['error'] = 'Title and image are required';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $db->prepare("INSERT INTO hero_images (title, subtitle, image_path, link_url, is_active, sort_order, slider_height, padding_top, padding_bottom, show_text, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$title, $subtitle, $imagePath, $linkUrl, $isActive, $sortOrder, $sliderHeight, $paddingTop, $paddingBottom, $showText]);
                    $_SESSION['success'] = 'Hero image created successfully!';
                } else {
                    $imageId = (int)$_POST['id'];
                    $stmt = $db->prepare("UPDATE hero_images SET title = ?, subtitle = ?, image_path = ?, link_url = ?, is_active = ?, sort_order = ?, slider_height = ?, padding_top = ?, padding_bottom = ?, show_text = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$title, $subtitle, $imagePath, $linkUrl, $isActive, $sortOrder, $sliderHeight, $paddingTop, $paddingBottom, $showText, $imageId]);
                    $_SESSION['success'] = 'Hero image updated successfully!';
                }

                redirect(adminUrl('/hero-images.php'));
            } catch (Exception $e) {
                error_log("Error saving hero image: " . $e->getMessage());
                $_SESSION['error'] = 'Error saving hero image: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all hero images
$heroImages = [];
if ($db) {
    try {
        $stmt = $db->query("SELECT * FROM hero_images ORDER BY sort_order ASC, id ASC");
        $heroImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching hero images: " . $e->getMessage());
    }
}

// Check if we're on add or edit page
$isEditPage = isset($_GET['edit']);
$isAddPage = isset($_GET['add']);
$editImage = null;

if ($isEditPage && isset($_GET['id'])) {
    $imageId = (int)$_GET['id'];
    if ($db) {
        try {
            $stmt = $db->prepare("SELECT * FROM hero_images WHERE id = ?");
            $stmt->execute([$imageId]);
            $editImage = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error fetching hero image: " . $e->getMessage());
        }
    }
}

// Generate admin page content
ob_start();
?>

<?php if ($isAddPage || $isEditPage): ?>
<!-- Add/Edit Page -->
<div class="w-full max-w-7xl mx-auto">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="<?php echo  adminUrl('/hero-images.php') ?>" style="display: inline-flex; align-items: center; color: #16a34a; text-decoration: none; font-weight: 600;">
            <i class="fas fa-arrow-left mr-2"></i> Back to Hero Images
        </a>
    </div>

    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900"><?php echo  $isEditPage ? 'Edit Hero Image' : 'Add New Hero Image' ?></h1>
        <p class="text-gray-600 mt-1"><?php echo  $isEditPage ? 'Update hero image details' : 'Create a new hero image for the slider' ?></p>
    </div>

    <!-- Form -->
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <form method="POST" action="<?php echo  adminUrl('/hero-images.php' . ($isEditPage ? '?edit=1&id=' . $editImage['id'] : '?add=1')) ?>" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="<?php echo  $isEditPage ? 'edit' : 'add' ?>">
            <?php if ($isEditPage): ?>
                <input type="hidden" name="id" value="<?php echo  $editImage['id'] ?>">
            <?php endif; ?>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Title *</label>
                <input type="text" name="title" required
                       value="<?php echo  $isEditPage ? htmlspecialchars($editImage['title']) : '' ?>"
                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                       placeholder="e.g., Premium Cannabis Products">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Subtitle</label>
                <textarea name="subtitle" rows="3"
                          style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                          placeholder="Brief description or tagline"><?php echo  $isEditPage ? htmlspecialchars($editImage['subtitle'] ?? '') : '' ?></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Upload Image (16:9 aspect ratio) *</label>
                <input type="file" name="image" accept="image/*"
                       style="width: 100%; padding: 12px; border: 2px dashed #d1d5db; border-radius: 8px; font-size: 1rem; cursor: pointer;"
                       <?php echo  !$isEditPage ? 'required' : '' ?>>
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px;">Recommended: 1600x900px (16:9 aspect ratio). Max size: 2MB</p>
                <?php if ($isEditPage && !empty($editImage['image_path'])): ?>
                    <div style="margin-top: 10px;">
                        <p style="color: #6b7280; font-size: 0.875rem; margin-bottom: 5px;">Current Image:</p>
                        <img src="<?php echo  htmlspecialchars($editImage['image_path']) ?>" alt="Current Image" style="max-width: 300px; border-radius: 8px; border: 1px solid #e5e7eb;">
                        <p style="color: #6b7280; font-size: 0.75rem; margin-top: 5px;">Upload a new image to replace, or leave empty to keep current</p>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Or Image Path (URL)</label>
                <input type="text" name="image_path"
                       value="<?php echo  $isEditPage ? htmlspecialchars($editImage['image_path'] ?? '') : '' ?>"
                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                       placeholder="<?php echo  assetUrl('images/hero/image-name.jpg') ?>">
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px;">Alternative: Enter the full path to an existing image</p>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Link URL</label>
                <input type="text" name="link_url"
                       value="<?php echo  $isEditPage ? htmlspecialchars($editImage['link_url'] ?? '') : '' ?>"
                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                       placeholder="<?php echo  shopUrl('/') ?>">
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px;">Optional: Where the image should link to</p>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Sort Order</label>
                <input type="number" name="sort_order"
                       value="<?php echo  $isEditPage ? htmlspecialchars($editImage['sort_order'] ?? 0) : '0' ?>"
                       style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px;">Lower numbers appear first (0 = first)</p>
            </div>

            <!-- Customization Section -->
            <div style="margin-bottom: 20px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                <h3 style="font-weight: 700; color: #374151; margin-bottom: 15px; display: flex; align-items: center;">
                    <span style="margin-right: 8px;">🎨</span> Slider Customization
                </h3>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Slider Height</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <select name="slider_height" id="sliderHeight"
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                            <option value="auto" <?php echo  ($isEditPage && ($editImage['slider_height'] ?? 'auto') === 'auto') ? 'selected' : '' ?>>Auto (16:9 aspect ratio)</option>
                            <option value="full" <?php echo  ($isEditPage && ($editImage['slider_height'] ?? '') === 'full') ? 'selected' : '' ?>>Full Screen (100vh)</option>
                            <option value="500px" <?php echo  ($isEditPage && ($editImage['slider_height'] ?? '') === '500px') ? 'selected' : '' ?>>500px</option>
                            <option value="600px" <?php echo  ($isEditPage && ($editImage['slider_height'] ?? '') === '600px') ? 'selected' : '' ?>>600px</option>
                            <option value="700px" <?php echo  ($isEditPage && ($editImage['slider_height'] ?? '') === '700px') ? 'selected' : '' ?>>700px</option>
                            <option value="custom" <?php echo  ($isEditPage && !in_array($editImage['slider_height'] ?? '', ['auto', 'full', '500px', '600px', '700px', ''])) ? 'selected' : '' ?>>Custom</option>
                        </select>
                        <input type="text" name="custom_height" id="customHeight"
                               value="<?php echo  $isEditPage && !in_array($editImage['slider_height'] ?? '', ['auto', 'full', '500px', '600px', '700px', '']) ? htmlspecialchars($editImage['slider_height']) : '' ?>"
                               placeholder="e.g., 800px, 75vh"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;">
                    </div>
                    <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px;">Choose a preset or enter custom height (e.g., 800px, 75vh)</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Padding Top</label>
                        <input type="text" name="padding_top"
                               value="<?php echo  $isEditPage ? htmlspecialchars($editImage['padding_top'] ?? '0') : '0' ?>"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                               placeholder="0">
                        <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px;">e.g., 0, 20px, 2rem</p>
                    </div>

                    <div>
                        <label style="display: block; font-weight: 600; color: #374151; margin-bottom: 8px;">Padding Bottom</label>
                        <input type="text" name="padding_bottom"
                               value="<?php echo  $isEditPage ? htmlspecialchars($editImage['padding_bottom'] ?? '0') : '0' ?>"
                               style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 1rem;"
                               placeholder="0">
                        <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px;">e.g., 0, 20px, 2rem</p>
                    </div>
                </div>

                <div style="margin-top: 15px;">
                    <label style="display: flex; align-items: center; cursor: pointer;">
                        <input type="checkbox" name="show_text"
                               <?php echo  (!$isEditPage || ($editImage['show_text'] ?? 1) == 1) ? 'checked' : '' ?>
                               style="margin-right: 10px; width: 18px; height: 18px;">
                        <span style="font-weight: 600; color: #374151;">Show Text Overlay</span>
                    </label>
                    <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px; margin-left: 28px;">Uncheck to hide title and subtitle on this slide</p>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" name="is_active"
                           <?php echo  (!$isEditPage || ($editImage['is_active'] ?? 1) == 1) ? 'checked' : '' ?>
                           style="margin-right: 10px; width: 18px; height: 18px;">
                    <span style="font-weight: 600; color: #374151;">Active</span>
                </label>
                <p style="color: #6b7280; font-size: 0.875rem; margin-top: 5px; margin-left: 28px;">Inactive images won't show on the homepage</p>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="<?php echo  adminUrl('/hero-images.php') ?>"
                   style="padding: 12px 24px; border: 1px solid #d1d5db; border-radius: 8px; background: white; color: #374151; font-weight: 600; text-decoration: none;">
                    Cancel
                </a>
                <button type="submit"
                        style="padding: 12px 24px; border: none; border-radius: 8px; background: #16a34a; color: white; font-weight: 600; cursor: pointer;">
                    <?php echo  $isEditPage ? 'Update Hero Image' : 'Create Hero Image' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Handle custom height visibility
document.getElementById('sliderHeight').addEventListener('change', function() {
    const customHeightInput = document.getElementById('customHeight');
    if (this.value === 'custom') {
        customHeightInput.style.display = 'block';
        customHeightInput.focus();
    } else {
        customHeightInput.style.display = 'none';
    }
});

// Initialize on page load
document.getElementById('sliderHeight').dispatchEvent(new Event('change'));
</script>

<?php else: ?>
<!-- List Page -->
<div class="w-full max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Hero Images</h1>
            <p class="text-gray-600 mt-1">Manage 16:9 hero images for homepage slider</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?php echo  adminUrl('/hero-images.php?add_demo=1') ?>"
               style="background: #3b82f6; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; border: none; cursor: pointer;">
                <i class="fas fa-magic mr-2"></i> Add Demo Images
            </a>
            <a href="<?php echo  adminUrl('/hero-images.php?add=1') ?>"
               style="background: #16a34a; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; border: none; cursor: pointer;">
                + Add Hero Image
            </a>
        </div>
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

    <!-- Hero Images Table -->
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="font-size: 1.25rem; font-weight: bold; color: #1f2937; margin-bottom: 16px;">All Hero Images (<?php echo  count($heroImages) ?>)</h2>

        <?php if (empty($heroImages)): ?>
            <div style="text-align: center; padding: 40px; color: #6b7280;">
                <p>No hero images found.</p>
                <button onclick="showAddModal()" style="color: #16a34a; text-decoration: none; font-weight: 600; border: none; background: none; cursor: pointer; margin-top: 10px;">
                    Add your first hero image
                </button>
            </div>
        <?php else: ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Image</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Title</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Subtitle</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Sort Order</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Status</th>
                            <th style="text-align: left; padding: 12px; font-weight: 600; color: #374151;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($heroImages as $image): ?>
                            <tr style="border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 12px;">
                                    <?php if (!empty($image['image_path'])): ?>
                                        <div style="width: 100px; height: 56px; border-radius: 8px; overflow: hidden; background: #f3f4f6;">
                                            <img src="<?php echo  htmlspecialchars($image['image_path']) ?>" alt="<?php echo  htmlspecialchars($image['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    <?php else: ?>
                                        <div style="width: 100px; height: 56px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                            <span style="font-size: 1.5rem;">🖼️</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; font-weight: 600;"><?php echo  htmlspecialchars($image['title']) ?></td>
                                <td style="padding: 12px; color: #6b7280; max-width: 300px;">
                                    <?php echo  htmlspecialchars($image['subtitle'] ?? 'N/A') ?>
                                </td>
                                <td style="padding: 12px;"><?php echo  (int)$image['sort_order'] ?></td>
                                <td style="padding: 12px;">
                                    <span style="background: <?php echo  $image['is_active'] ? '#10b981' : '#ef4444' ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8rem;">
                                        <?php echo  $image['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td style="padding: 12px;">
                                    <a href="<?php echo  adminUrl('/hero-images.php?edit=1&id=' . $image['id']) ?>" style="color: #16a34a; text-decoration: none; font-weight: 600; margin-right: 15px;">
                                        Edit
                                    </a>
                                    <a href="<?php echo  adminUrl('/hero-images.php?delete=1&id=' . $image['id']) ?>" onclick="return confirm('Are you sure you want to delete this hero image?')" style="color: #ef4444; text-decoration: none; font-weight: 600;">
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
<?php endif; ?>

<?php
$content = ob_get_clean();
echo renderAdminPage('Hero Images - CannaBuddy Admin', $content, 'hero-images');
?>
