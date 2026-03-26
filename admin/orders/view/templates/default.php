<?php
// Generate QR Code URL
$qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=' . urlencode($storeWebsite . '/admin/orders/view/print.php?id=' . $orderId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Order #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        @media print {
            body {
                margin: 0;
                padding: 0;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .no-print {
                display: none !important;
            }
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #1f2937;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .document {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: white;
            padding: 40px;
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .brand h1 {
            font-size: 32px;
            font-weight: 800;
            color: #111827;
            margin: 0 0 5px 0;
            letter-spacing: -0.5px;
        }
        .brand p {
            margin: 0;
            color: #6b7280;
            font-size: 13px;
        }
        .brand .contact-info {
            margin-top: 8px;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h2 {
            font-size: 36px;
            font-weight: 800;
            color: #e5e7eb;
            margin: 0;
            line-height: 1;
            letter-spacing: -1px;
            text-transform: uppercase;
        }
        .invoice-title .meta {
            margin-top: 10px;
            font-size: 13px;
            color: #4b5563;
        }
        
        /* Status Bar */
        .status-bar {
            display: flex;
            justify-content: space-between;
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
        }
        .status-item {
            flex: 1;
        }
        .status-item label {
            display: block;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #6b7280;
            margin-bottom: 2px;
            font-weight: 600;
        }
        .status-item span {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
        }
        
        /* Grid Layout */
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 30px;
        }
        .box h3 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin: 0 0 10px 0;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .box-content {
            font-size: 13px;
        }
        .box-content p {
            margin: 2px 0;
        }
        .box-content strong {
            display: block;
            margin-bottom: 4px;
            color: #111827;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th {
            text-align: left;
            padding: 12px 10px;
            font-size: 10px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 2px solid #e5e7eb;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .items-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: top;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .item-desc {
            font-weight: 500;
            color: #111827;
        }
        .item-meta {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }
        
        /* Totals */
        .totals-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        .totals {
            width: 250px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .total-row span:first-child {
            color: #6b7280;
        }
        .total-row span:last-child {
            font-weight: 600;
            color: #111827;
        }
        .total-row.grand-total {
            border-bottom: none;
            border-top: 2px solid #111827;
            padding-top: 12px;
            margin-top: 5px;
        }
        .total-row.grand-total span {
            font-size: 16px;
            font-weight: 800;
            color: #111827;
        }

        /* Footer */
        .footer {
            margin-top: auto;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .footer-info {
            font-size: 11px;
            color: #9ca3af;
        }
        .footer-info p {
            margin: 2px 0;
        }
        
        /* Print Button */
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 12px 24px;
            background: #111827;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .print-btn:hover {
            background: #374151;
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> Print Invoice
    </button>

    <div class="document">
        <div class="header">
            <div class="brand">
                <h1><?php echo htmlspecialchars($storeName); ?></h1>
                <?php
                $addressLines = array_filter(array_map('trim', explode("\n", $storeAddress)));
                foreach ($addressLines as $line) {
                    echo '<p>' . htmlspecialchars($line) . '</p>';
                }
                if (!empty($storeEmail) || !empty($storePhone)) {
                    echo '<div class="contact-info">';
                    if (!empty($storeEmail)) echo '<p>' . htmlspecialchars($storeEmail) . '</p>';
                    if (!empty($storePhone)) echo '<p>' . htmlspecialchars($storePhone) . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="invoice-title">
                <h2>INVOICE</h2>
                <div class="meta">
                    #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?><br>
                    <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                </div>
            </div>
        </div>

        <div class="status-bar">
            <div class="status-item">
                <label>Status</label>
                <span><?php echo htmlspecialchars(ucfirst($statusOptions[$order['status']] ?? $order['status'])); ?></span>
            </div>
            <div class="status-item">
                <label>Payment</label>
                <span><?php echo htmlspecialchars(ucfirst($order['payment_status'] ?? 'Pending')); ?></span>
            </div>
            <div class="status-item">
                <label>Method</label>
                <span><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $order['payment_method'] ?? 'N/A'))); ?></span>
            </div>
            <div class="status-item" style="text-align: right;">
                <label>Amount Due</label>
                <span>R<?php echo number_format($order['total_amount'] ?? 0, 2); ?></span>
            </div>
        </div>

        <div class="grid-2">
            <div class="box">
                <h3>Billed To</h3>
                <div class="box-content">
                    <strong><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></strong>
                    <?php echo htmlspecialchars($order['customer_email'] ?? ''); ?><br>
                    <?php if (!empty($order['customer_phone'])) echo htmlspecialchars($order['customer_phone']); ?>
                    <?php if (!empty($customerAccountId)) echo '<br><span style="color:#6b7280; font-size:11px">ID: ' . htmlspecialchars($customerAccountId) . '</span>'; ?>
                </div>
            </div>
            <div class="box">
                <h3>Shipped To</h3>
                <div class="box-content">
                    <?php echo formatAddressCompact($shippingAddress); ?>
                </div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Item Description</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orderItems as $item): 
                    $sku = $item['product_sku'] ?? '';
                    $brand = $item['product_brand'] ?? '';
                ?>
                <tr>
                    <td>
                        <div class="item-desc"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <div class="item-meta">
                            <?php if ($sku) echo 'SKU: ' . htmlspecialchars($sku); ?>
                            <?php if ($brand) echo ' • ' . htmlspecialchars($brand); ?>
                            <?php if (!empty($item['product_weight'])) echo ' • ' . htmlspecialchars($item['product_weight']) . 'kg'; ?>
                        </div>
                    </td>
                    <td style="text-align: center;"><?php echo (int)$item['quantity']; ?></td>
                    <td style="text-align: right;">R<?php echo number_format($item['unit_price'] ?? 0, 2); ?></td>
                    <td style="text-align: right; font-weight: 600;">R<?php echo number_format($item['total_price'] ?? 0, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-container">
            <div class="totals">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>R<?php echo number_format($order['subtotal'] ?? 0, 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Delivery</span>
                    <span>R<?php echo number_format($order['shipping_amount'] ?? 0, 2); ?></span>
                </div>
                <?php if (($order['tax_amount'] ?? 0) > 0): ?>
                <div class="total-row">
                    <span>Tax</span>
                    <span>R<?php echo number_format($order['tax_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
                <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                <div class="total-row" style="color: #16a34a;">
                    <span>Discount</span>
                    <span>-R<?php echo number_format($order['discount_amount'], 2); ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row grand-total">
                    <span>Total</span>
                    <span>R<?php echo number_format($order['total_amount'] ?? 0, 2); ?></span>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="footer-info">
                <p>Thank you for your business!</p>
                <p>For questions, contact <?php echo htmlspecialchars($storeEmail); ?></p>
                <div style="margin-top: 10px;">
                    <?php if (!empty($paymentMethodName) && $paymentMethodName !== 'N/A'): ?>
                        <strong>Payment Method:</strong> <?php echo htmlspecialchars($paymentMethodName); ?><br>
                    <?php endif; ?>

                    <?php if (!empty($paymentCustomFields)): ?>
                        <div style="margin-top: 8px;">
                            <strong>Payment Details:</strong><br>
                            <?php foreach ($paymentCustomFields as $fieldName => $fieldValue): ?>
                                <?php if (!empty($fieldValue)): ?>
                                    <span style="font-size: 12px;"><?php echo htmlspecialchars($fieldName); ?>: <?php echo htmlspecialchars($fieldValue); ?></span><br>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php elseif (!empty($bankName) && !empty($bankAccountNumber)): ?>
                        <strong>Bank Details:</strong> <?php echo htmlspecialchars($bankName); ?> • Acc: <?php echo htmlspecialchars($bankAccountNumber); ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (!empty($qrCodeUrl)): ?>
            <div class="qr-code">
                 <p style="font-size: 11px; margin-bottom: 5px; color: #666;">Scan to Pay:</p>
                 <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="Payment QR Code" style="width:80px;height:80px;">
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
