<?php
// Prevent caching
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    if (isset($_POST['update_slide'])) {
        $slideId = $_POST['slide_id'];
        $title = $_POST['title'] ?? '';
        $subtitle = $_POST['subtitle'] ?? '';
        $linkUrl = $_POST['link_url'] ?? '';
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sliderHeight = $_POST['slider_height'] ?? '500px';
        $paddingTop = $_POST['padding_top'] ?? '0';
        $paddingBottom = $_POST['padding_bottom'] ?? '0';

        // Handle image upload
        $imagePath = $_POST['image_path'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imageFile = $_FILES['image'];

            // Validate file type and size (allow common formats including WEBP, max 2MB)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif', 'image/webp'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'];

            $extension = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                $error = 'Invalid file type. Only JPG, PNG, SVG, GIF, and WEBP are allowed.';
            } else {
                $fileType = null;
                if (function_exists('mime_content_type')) {
                    $fileType = mime_content_type($imageFile['tmp_name']);
                }
                if ($fileType && !in_array($fileType, $allowedTypes, true)) {
                    $error = 'Invalid file type. Only JPG, PNG, SVG, GIF, and WEBP are allowed.';
                } elseif ($imageFile['size'] > 2 * 1024 * 1024) {
                    $error = 'Image too large. Maximum size is 2MB.';
                } else {
                    $uploadDir = __DIR__ . '/../../assets/images/slider/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = 'slide_' . $slideId . '_' . time() . '.' . $extension;
                    $uploadPath = $uploadDir . $fileName;
                    $publicPath = assetPath('images/slider/' . $fileName);

                    if (move_uploaded_file($imageFile['tmp_name'], $uploadPath)) {
                        $imagePath = $publicPath;
                        $message = 'Slide updated with new image!';
                    } else {
                        $error = 'Error uploading image. Using existing image path.';
                    }
                }
            }
        }

        try {
            // Try to update with new fields first
            $stmt = $db->prepare("UPDATE homepage_slider SET title = ?, subtitle = ?, link_url = ?, sort_order = ?, is_active = ?, image_path = ?, slider_height = ?, padding_top = ?, padding_bottom = ? WHERE id = ?");
            $result = $stmt->execute([$title, $subtitle, $linkUrl, $sortOrder, $isActive, $imagePath, $sliderHeight, $paddingTop, $paddingBottom, $slideId]);

            if ($result) {
                if (empty($message)) {
                    $message = 'Slide ' . $sortOrder . ' saved successfully!';
                }
            }
        } catch (Exception $e) {
            // If the new columns don't exist, fall back to the old query
            try {
                $stmt = $db->prepare("UPDATE homepage_slider SET title = ?, subtitle = ?, link_url = ?, sort_order = ?, is_active = ?, image_path = ? WHERE id = ?");
                $stmt->execute([$title, $subtitle, $linkUrl, $sortOrder, $isActive, $imagePath, $slideId]);
                if (empty($message)) {
                    $message = 'Slide ' . $sortOrder . ' saved successfully!';
                }
            } catch (Exception $e2) {
                $error = AppError::handleDatabaseError($e2, 'Error updating slide');
            }
        }
    }

    // Handle hero section updates
    if (isset($_POST['update_hero'])) {
        $heroId = $_POST['hero_id'];
        $title = $_POST['title'] ?? '';
        $subtitle = $_POST['subtitle'] ?? '';
        $buttonText = $_POST['button_text'] ?? '';
        $buttonLink = $_POST['button_link'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        // Handle background image upload
        $backgroundImage = '';
        if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
            $imageFile = $_FILES['background_image'];

            // Validate file type and size (allow common formats including WEBP, max 2MB)
            $allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/gif', 'image/webp'];
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'svg', 'gif', 'webp'];

            $extension = strtolower(pathinfo($imageFile['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                $error = 'Invalid file type. Only JPG, PNG, SVG, GIF, and WEBP are allowed.';
            } else {
                $fileType = null;
                if (function_exists('mime_content_type')) {
                    $fileType = mime_content_type($imageFile['tmp_name']);
                }
                if ($fileType && !in_array($fileType, $allowedTypes, true)) {
                    $error = 'Invalid file type. Only JPG, PNG, SVG, GIF, and WEBP are allowed.';
                } elseif ($imageFile['size'] > 2 * 1024 * 1024) {
                    $error = 'Image too large. Maximum size is 2MB.';
                } else {
                    $uploadDir = __DIR__ . '/../../assets/images/heroes/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }

                    $fileName = 'hero_' . $heroId . '_' . time() . '.' . $extension;
                    $uploadPath = $uploadDir . $fileName;
                    $publicPath = assetPath('images/heroes/' . $fileName);

                    if (move_uploaded_file($imageFile['tmp_name'], $uploadPath)) {
                        $backgroundImage = $publicPath;
                        $message = 'Hero section updated with new background!';
                    } else {
                        $error = 'Error uploading background image.';
                    }
                }
            }
        } else {
            // Keep existing background image if no new upload
            $stmt = $db->prepare("SELECT background_image FROM homepage_hero_sections WHERE id = ?");
            $stmt->execute([$heroId]);
            $existingHero = $stmt->fetch(PDO::FETCH_ASSOC);
            $backgroundImage = $existingHero['background_image'] ?? '';
        }

        try {
            $stmt = $db->prepare("UPDATE homepage_hero_sections SET title = ?, subtitle = ?, button_text = ?, button_link = ?, background_image = ?, is_active = ? WHERE id = ?");
            $result = $stmt->execute([$title, $subtitle, $buttonText, $buttonLink, $backgroundImage, $isActive, $heroId]);

            if ($result) {
                if (empty($message)) {
                    $message = 'Hero section updated successfully!';
                }
            }
        } catch (Exception $e) {
            $error = 'Error updating hero section: ' . $e->getMessage();
        }
    }
}

try {
    $stmt = $db->query("SELECT * FROM homepage_slider ORDER BY sort_order ASC, id ASC");
    $slides = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Error fetching slider data: ' . $e->getMessage();
    $slides = [];
}

$defaultSliderImage = assetUrl('images/slider/slide1.jpg');
$content = '<div class="max-w-7xl mx-auto">';

if ($message) {
    $content .= adminAlert($message, 'success');
}
if ($error) {
    $content .= adminAlert($error, 'error');
}

$content .= '
<div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-900 mb-2">Homepage Slider Management</h2>
    <p class="text-gray-600">Manage the 4 slides that appear on the homepage slider</p>
</div>';

if (!empty($slides)) {
    $content .= '<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">';
    foreach ($slides as $slide) {
        $checked = $slide['is_active'] ? 'checked' : '';
        $content .= '
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">'
                . 'Slide ' . safe_html($slide['sort_order']) . ' - ' . safe_html($slide['title']) .
                '</h3>
            </div>
            <div class="p-6">
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Current Image Preview</label>
                    <div class="h-48 bg-gradient-to-br from-green-50 to-green-100 rounded-lg flex items-center justify-center overflow-hidden rounded-lg">';
        if (!empty($slide['image_path'])) {
            $content .= '<img src="' . safe_html(url($slide['image_path'])) . '" alt="Slide Image" class="w-full h-full object-cover">';
        } else {
            $content .= '
                        <div class="text-center">
                            <div class="text-4xl mb-2">🖼️</div>
                            <p class="text-gray-600">No image set</p>
                        </div>';
        }
        $content .= '
                    </div>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    ' . csrf_field() . '
                    <input type="hidden" name="slide_id" value="' . $slide['id'] . '">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Upload Image</label>
                            <input type="file" name="image" accept="image/*"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <p class="text-xs text-gray-500 mt-1">Recommended: 1600x900px (16:9 aspect ratio). Max size: 2MB</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                            <input type="text" name="title" value="' . safe_html($slide['title']) . '"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle</label>
                            <input type="text" name="subtitle" value="' . safe_html($slide['subtitle']) . '"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Link URL</label>
                            <input type="url" name="link_url" value="' . safe_html($slide['link_url']) . '"
                                   placeholder="https://example.com/page"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <p class="text-xs text-gray-500 mt-1">Where users go when they click this slide</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order (1-4)</label>
                            <input type="number" name="sort_order" value="' . $slide['sort_order'] . '" min="1" max="4"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                        </div>

                        <!-- Customization Section -->
                        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3 flex items-center">
                                <span class="mr-2">🎨</span> Slider Customization
                            </h4>

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Slider Height</label>
                                    <select name="slider_height"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <option value="500px"' . (($slide['slider_height'] ?? '500px') === '500px' ? ' selected' : '') . '>500px</option>
                                        <option value="600px"' . (($slide['slider_height'] ?? '500px') === '600px' ? ' selected' : '') . '>600px</option>
                                        <option value="700px"' . (($slide['slider_height'] ?? '500px') === '700px' ? ' selected' : '') . '>700px</option>
                                        <option value="full"' . (($slide['slider_height'] ?? '500px') === 'full' ? ' selected' : '') . '>Full Screen (100vh)</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Padding Top</label>
                                        <input type="text" name="padding_top" value="' . safe_html($slide['padding_top'] ?? '0') . '"
                                               placeholder="0"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <p class="text-xs text-gray-500 mt-1">e.g., 0, 20px, 2rem</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Padding Bottom</label>
                                        <input type="text" name="padding_bottom" value="' . safe_html($slide['padding_bottom'] ?? '0') . '"
                                               placeholder="0"
                                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                        <p class="text-xs text-gray-500 mt-1">e.g., 0, 20px, 2rem</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="active_' . $slide['id'] . '" value="1" ' . $checked . '
                                   class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="active_' . $slide['id'] . '" class="ml-2 block text-sm text-gray-700">Active (visible on homepage)</label>
                        </div>
                        <button type="submit" name="update_slide"
                                class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 font-semibold shadow-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>';
    }
    $content .= '</div>';
} else {
    $content .= '<div class="bg-white rounded-lg shadow-md p-8 text-center">
        <p class="text-gray-600">No slider records found. Please run the database setup script.</p>
    </div>';
}

$content .= '
<div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
    <h3 class="text-lg font-semibold text-blue-900 mb-3">📝 Instructions</h3>
    <ul class="text-blue-800 space-y-2 text-sm">
        <li>• <strong>Upload Image:</strong> Choose a file from your computer (recommended: 1600x900px, max 2MB)</li>
        <li>• <strong>Title:</strong> The main heading displayed on the slide</li>
        <li>• <strong>Subtitle:</strong> Additional text below the title</li>
        <li>• <strong>Link URL:</strong> The page users visit when clicking the slide</li>
        <li>• <strong>Sort Order:</strong> Position of the slide (1 = first, 4 = last)</li>
        <li>• <strong>Slider Height:</strong> Control the height of the slider (500px, 600px, 700px, or Full Screen)</li>
        <li>• <strong>Padding Top/Bottom:</strong> Add spacing above and below the slider (e.g., 0, 20px, 2rem)</li>
        <li>• <strong>Active:</strong> Uncheck to hide a slide from the homepage</li>
        <li>• <strong>Upload Directory:</strong> Images are saved to /assets/images/slider/</li>
    </ul>
</div>';

// Fetch hero sections (all hero sections for display)
try {
    $stmt = $db->query("SELECT * FROM homepage_hero_sections ORDER BY hero_number ASC");
    $heroSections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = 'Error fetching hero sections: ' . $e->getMessage();
    $heroSections = [];
}

// Hero Sections Management Section
$content .= '
<div x-data="{ open: false }" class="mt-12">
    <button @click="open = !open" class="w-full flex items-center justify-between p-4 bg-white rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow">
        <div class="flex items-center">
            <span class="text-2xl mr-3">🏠</span>
            <div class="text-left">
                <h3 class="text-xl font-bold text-gray-900">Hero Sections Management</h3>
                <p class="text-sm text-gray-600">Control the hero banners on the homepage</p>
            </div>
        </div>
        <i :class="open ? \'fas fa-chevron-up\' : \'fas fa-chevron-down\'" class="fas text-gray-500 transition-transform"></i>
    </button>

    <div x-show="open" x-transition class="mt-4">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">';

if (!empty($heroSections)) {
    foreach ($heroSections as $hero) {
        $checked = $hero['is_active'] ? 'checked' : '';
        $content .= '
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="bg-gray-50 px-6 py-4 border-b">
                    <h4 class="text-lg font-semibold text-gray-900">Hero Section ' . $hero['hero_number'] . '</h4>
                </div>
                <div class="p-6">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Background Image Preview</label>
                        <div class="h-48 bg-gradient-to-br from-gray-100 to-gray-200 rounded-lg flex items-center justify-center overflow-hidden">';
        if (!empty($hero['background_image'])) {
            $content .= '<img src="' . safe_html(url($hero['background_image'])) . '" alt="Background Image" class="w-full h-full object-cover">';
        } else {
            $content .= '
                            <div class="text-center">
                                <div class="text-4xl mb-2">🖼️</div>
                                <p class="text-gray-600">No background image set</p>
                            </div>';
        }
        $content .= '
                        </div>
                    </div>
                    <form method="POST" action="" enctype="multipart/form-data">
                        ' . csrf_field() . '
                        <input type="hidden" name="hero_id" value="' . $hero['id'] . '">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Upload Background Image</label>
                                <input type="file" name="background_image" accept="image/*"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                <p class="text-xs text-gray-500 mt-1">Recommended: 1920x1080px (16:9 aspect ratio). Max size: 2MB</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                                <input type="text" name="title" value="' . safe_html($hero['title']) . '"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Subtitle</label>
                                <textarea name="subtitle" rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">' . safe_html($hero['subtitle']) . '</textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Button Text</label>
                                    <input type="text" name="button_text" value="' . safe_html($hero['button_text']) . '"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Button Link</label>
                                    <input type="text" name="button_link" value="' . safe_html($hero['button_link']) . '"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                                </div>
                            </div>
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="hero_active_' . $hero['id'] . '" value="1" ' . $checked . '
                                       class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                                <label for="hero_active_' . $hero['id'] . '" class="ml-2 block text-sm text-gray-700">Active (visible on homepage)</label>
                            </div>
                            <button type="submit" name="update_hero"
                                    class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 font-semibold shadow-sm">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>';
    }
} else {
    $content .= '
            <div class="col-span-2 bg-white rounded-lg shadow-md p-8 text-center">
                <p class="text-gray-600">No hero sections found. Please run the hero sections setup script.</p>
            </div>';
}

$content .= '
        </div>
    </div>
</div>';

$content .= '</div>';

echo adminSidebarWrapper('Homepage Slider', $content, 'slider');
