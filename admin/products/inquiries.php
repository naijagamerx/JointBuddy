<?php
// Include bootstrap (loads all core services)
require_once __DIR__ . '/../../includes/bootstrap.php';

// Require authentication (admin only)
AuthMiddleware::requireAdmin();

// Get database connection from services
$db = Services::db();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    CsrfMiddleware::validate();
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'update_status') {
                if (empty($_POST['id']) || empty($_POST['status'])) {
                    throw new Exception('Inquiry ID and status are required');
                }

                $stmt = $db->prepare('UPDATE product_inquiries SET status = ?, updated_at = NOW() WHERE id = ?');
                $result = $stmt->execute([$_POST['status'], $_POST['id']]);

                if ($result) {
                    $message = 'Inquiry status updated successfully';
                } else {
                    throw new Exception('Failed to update inquiry status');
                }
            } elseif ($_POST['action'] === 'delete') {
                if (empty($_POST['id'])) {
                    throw new Exception('Inquiry ID is required');
                }

                $stmt = $db->prepare('DELETE FROM product_inquiries WHERE id = ?');
                $result = $stmt->execute([$_POST['id']]);

                if ($result) {
                    $message = 'Inquiry deleted successfully';
                } else {
                    throw new Exception('Failed to delete inquiry');
                }
            }
        } catch (Exception $e) {
            $error = AppError::handleDatabaseError($e, 'Failed to process inquiry');
        }
    }
}

// Get all inquiries with product details
$inquiries = [];
if ($db) {
    try {
        $stmt = $db->query('
            SELECT i.*, p.name AS product_name, p.slug AS product_slug, p.images AS product_images
            FROM product_inquiries i
            JOIN products p ON p.id = i.product_id
            ORDER BY i.created_at DESC
        ');
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = AppError::handleDatabaseError($e, 'Unable to load inquiries');
    }
}

// Get status counts for dashboard
$statusCounts = [
    'new' => 0,
    'in_progress' => 0,
    'closed' => 0
];

foreach ($inquiries as $inq) {
    if (isset($statusCounts[$inq['status']])) {
        $statusCounts[$inq['status']]++;
    }
}

// Generate content
$content = '<div class="max-w-7xl mx-auto">';

if ($message) {
    $content .= '<div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-check-circle text-green-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-green-700">' . htmlspecialchars($message) . '</p>
            </div>
        </div>
    </div>';
}

if ($error) {
    $content .= '<div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded-lg">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-circle text-red-400"></i>
            </div>
            <div class="ml-3">
                <p class="text-sm text-red-700">' . htmlspecialchars($error) . '</p>
            </div>
        </div>
    </div>';
}

$content .= '<div class="mb-8">
    <h2 class="text-2xl font-bold text-gray-900">Product Inquiries</h2>
    <p class="text-gray-600 mt-1">Manage customer inquiries about products (' . count($inquiries) . ' inquiries)</p>
</div>

<!-- Status Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6 border border-blue-200">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                <i class="fas fa-inbox text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500">New Inquiries</p>
                <h3 class="text-2xl font-bold">' . $statusCounts['new'] . '</h3>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6 border border-yellow-200">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                <i class="fas fa-clock text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500">In Progress</p>
                <h3 class="text-2xl font-bold">' . $statusCounts['in_progress'] . '</h3>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-lg shadow p-6 border border-gray-200">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-gray-100 text-gray-500 mr-4">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <div>
                <p class="text-gray-500">Closed</p>
                <h3 class="text-2xl font-bold">' . $statusCounts['closed'] . '</h3>
            </div>
        </div>
    </div>
</div>';

// Inquiries table
if (!empty($inquiries)) {
    $content .= '<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">';

    foreach ($inquiries as $inq) {
        // Parse product image
        $productImage = '';
        if (!empty($inq['product_images'])) {
            $images = explode(',', $inq['product_images']);
            $productImage = trim($images[0]);
        }

        // Get status badge
        $statusClass = '';
        $statusLabel = '';
        switch ($inq['status']) {
            case 'new':
                $statusClass = 'bg-blue-100 text-blue-800';
                $statusLabel = 'New';
                break;
            case 'in_progress':
                $statusClass = 'bg-yellow-100 text-yellow-800';
                $statusLabel = 'In Progress';
                break;
            case 'closed':
                $statusClass = 'bg-gray-100 text-gray-800';
                $statusLabel = 'Closed';
                break;
        }

        $content .= '<tr class="hover:bg-gray-50">
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">';

        if ($productImage) {
            $content .= '<img src="' . htmlspecialchars($productImage) . '" alt="' . htmlspecialchars($inq['product_name']) . '" class="w-12 h-12 rounded-lg object-cover mr-4">';
        } else {
            $content .= '<div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center mr-4">
                            <i class="fas fa-box text-gray-500"></i>
                        </div>';
        }

        $content .= '<div>
                    <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($inq['product_name']) . '</div>
                    <a href="' . adminUrl('/products/view/' . urlencode($inq['product_slug'])) . '" class="text-xs text-blue-600 hover:text-blue-800">
                        <i class="fas fa-external-link-alt mr-1"></i>View Product
                    </a>
                </div>
            </div>
        </td>
        <td class="px-6 py-4">
            <div class="text-sm font-medium text-gray-900">' . htmlspecialchars($inq['name']) . '</div>
            ' . (!empty($inq['phone']) ? '<div class="text-sm text-gray-500"><i class="fas fa-phone mr-1"></i>' . htmlspecialchars($inq['phone']) . '</div>' : '') . '
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">
                <a href="mailto:' . htmlspecialchars($inq['email']) . '" class="text-blue-600 hover:text-blue-800">
                    <i class="fas fa-envelope mr-1"></i>' . htmlspecialchars($inq['email']) . '
                </a>
            </div>
        </td>
        <td class="px-6 py-4 whitespace-nowrap">
            <span class="px-2 py-1 text-xs font-medium ' . $statusClass . ' rounded-full">' . $statusLabel . '</span>
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
            ' . date('M j, Y g:i A', strtotime($inq['created_at'])) . '
        </td>
        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
            <button onclick="viewInquiry(' . htmlspecialchars(json_encode($inq)) . ')" class="text-blue-600 hover:text-blue-900 mr-4">
                <i class="fas fa-eye mr-1"></i>View
            </button>
            <button onclick="editInquiryStatus(' . $inq['id'] . ', \'' . addslashes($inq['status']) . '\')" class="text-green-600 hover:text-green-900 mr-4">
                <i class="fas fa-edit mr-1"></i>Update
            </button>
            <form method="POST" class="inline" onsubmit="return confirm(\'Are you sure you want to delete this inquiry?\')">
                ' . csrf_field() . '
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="' . $inq['id'] . '">
                <button type="submit" class="text-red-600 hover:text-red-900">
                    <i class="fas fa-trash mr-1"></i>Delete
                </button>
            </form>
        </td>
    </tr>';
    }

    $content .= '</tbody>
        </table>
    </div>
</div>';
} else {
    $content .= '<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-12 text-center">
        <i class="fas fa-inbox text-6xl text-gray-300 mb-4"></i>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No inquiries yet</h3>
        <p class="text-gray-600">Customer inquiries about products will appear here</p>
    </div>';
}

$content .= '</div>';

// View Inquiry Modal
$content .= '
<!-- View Inquiry Modal -->
<div id="inquiryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900" id="inquiryModalTitle">View Inquiry</h3>
            </div>
            <div class="px-6 py-4" id="inquiryModalContent">
                <!-- Content will be filled by JavaScript -->
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
                <button type="button" onclick="closeInquiryModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Update Inquiry Status</h3>
            </div>
            <form method="POST" class="px-6 py-4">
                ' . csrf_field() . '
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" id="statusInquiryId">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                    <select name="status" id="statusSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="new">New</option>
                        <option value="in_progress">In Progress</option>
                        <option value="closed">Closed</option>
                    </select>
                </div>

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeStatusModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>';

echo adminSidebarWrapper('Inquiries', $content, 'products');
?>

<script>
function viewInquiry(inquiry) {
    document.getElementById("inquiryModalTitle").textContent = "Inquiry from " + inquiry.name;
    document.getElementById("inquiryModalContent").innerHTML = `
        <div class="space-y-4">
            <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Product</label>
                <p class="mt-1 text-sm text-gray-900">${inquiry.product_name}</p>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Customer Name</label>
                <p class="mt-1 text-sm text-gray-900">${inquiry.name}</p>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Email</label>
                <p class="mt-1 text-sm text-gray-900">
                    <a href="mailto:${inquiry.email}" class="text-blue-600 hover:text-blue-800">${inquiry.email}</a>
                </p>
            </div>

            ${inquiry.phone ? `
            <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</label>
                <p class="mt-1 text-sm text-gray-900">${inquiry.phone}</p>
            </div>
            ` : ''}

            <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Message</label>
                <div class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded-lg">
                    ${inquiry.message ? inquiry.message.replace(/\\n/g, "<br>") : 'No message'}
                </div>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Status</label>
                <p class="mt-1">
                    <span class="px-2 py-1 text-xs font-medium rounded-full ${getStatusClass(inquiry.status)}">
                        ${getStatusLabel(inquiry.status)}
                    </span>
                </p>
            </div>

            <div>
                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date</label>
                <p class="mt-1 text-sm text-gray-900">${new Date(inquiry.created_at).toLocaleString()}</p>
            </div>
        </div>
    `;
    document.getElementById("inquiryModal").classList.remove("hidden");
}

function getStatusClass(status) {
    switch(status) {
        case 'new': return 'bg-blue-100 text-blue-800';
        case 'in_progress': return 'bg-yellow-100 text-yellow-800';
        case 'closed': return 'bg-gray-100 text-gray-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

function getStatusLabel(status) {
    switch(status) {
        case 'new': return 'New';
        case 'in_progress': return 'In Progress';
        case 'closed': return 'Closed';
        default: return status;
    }
}

function closeInquiryModal() {
    document.getElementById("inquiryModal").classList.add("hidden");
}

function editInquiryStatus(id, currentStatus) {
    document.getElementById("statusInquiryId").value = id;
    document.getElementById("statusSelect").value = currentStatus;
    document.getElementById("statusModal").classList.remove("hidden");
}

function closeStatusModal() {
    document.getElementById("statusModal").classList.add("hidden");
}

// Close modals when clicking outside
document.getElementById("inquiryModal").addEventListener("click", function(e) {
    if (e.target === this) {
        closeInquiryModal();
    }
});

document.getElementById("statusModal").addEventListener("click", function(e) {
    if (e.target === this) {
        closeStatusModal();
    }
});
</script>';

// Render the page with sidebar
echo adminSidebarWrapper('Product Inquiries', $content, 'products');
