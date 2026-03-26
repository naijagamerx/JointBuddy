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
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
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
            font-family: 'Roboto Mono', monospace;
            font-size: 11px;
            line-height: 1.4;
            color: #000;
            background-color: #fff;
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
            border: 1px solid #ddd;
        }
        
        /* Header */
        .header {
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .brand h1 {
            font-size: 32px;
            font-weight: 700;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: -1px;
        }
        .brand p {
            margin: 0;
            font-size: 11px;
        }
        .brand .contact-info {
            margin-top: 8px;
        }
        .invoice-details {
            text-align: right;
        }
        .invoice-details h2 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 10px 0;
            background: #000;
            color: #fff;
            display: inline-block;
            padding: 4px 12px;
        }
        .invoice-details table {
            margin-left: auto;
            border-collapse: collapse;
        }
        .invoice-details td {
            padding: 2px 0 2px 15px;
            text-align: right;
        }
        .invoice-details .label {
            font-weight: 700;
        }

        /* Addresses */
        .addresses {
            display: flex;
            margin-bottom: 30px;
            gap: 40px;
        }
        .address-col {
            flex: 1;
            border: 1px solid #000;
            padding: 15px;
        }
        .address-col h3 {
            margin: 0 0 10px 0;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        
        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 1px solid #000;
        }
        .items-table th {
            background: #f0f0f0;
            border-bottom: 1px solid #000;
            padding: 10px;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        .items-table tr:last-child td {
            border-bottom: none;
        }
        .items-table .col-right {
            text-align: right;
        }
        .items-table .col-center {
            text-align: center;
        }

        /* Totals */
        .totals-section {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 40px;
        }
        .totals-table {
            width: 300px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px 0;
            text-align: right;
        }
        .totals-table .label {
            padding-right: 20px;
            font-weight: 700;
        }
        .totals-table .grand-total {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            font-size: 14px;
            font-weight: 700;
            padding: 10px 0;
        }

        /* Footer */
        .footer {
            border-top: 1px solid #000;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .payment-info {
            flex: 1;
        }
        .payment-info h4 {
            margin: 0 0 5px 0;
            text-transform: uppercase;
        }
        .qr-code {
            text-align: right;
        }
        
        /* Print Button */
        .print-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            padding: 12px 24px;
            background: #000;
            color: white;
            border: none;
            font-family: 'Roboto Mono', monospace;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 4px 4px 0 rgba(0,0,0,0.2);
        }
        .print-btn:hover {
            transform: translate(2px, 2px);
            box-shadow: 2px 2px 0 rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        <i class="fas fa-print"></i> PRINT INVOICE
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
                    if (!empty($storeEmail)) echo '<p>Email: ' . htmlspecialchars($storeEmail) . '</p>';
                    if (!empty($storePhone)) echo '<p>Phone: ' . htmlspecialchars($storePhone) . '</p>';
                    echo '</div>';
                }
                ?>
            </div>
            <div class="invoice-details">
                <h2>INVOICE</h2>
                <table>
                    <tr>
                        <td class="label">Invoice No:</td>
                        <td><?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Date:</td>
                        <td><?php echo date('Y-m-d', strtotime($order['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Status:</td>
                        <td><?php echo htmlspecialchars(strtoupper($order['status'])); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Payment:</td>
                        <td><?php echo htmlspecialchars(strtoupper($order['payment_status'] ?? 'PENDING')); ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="addresses">
            <div class="address-col">
                <h3>Bill To</h3>
                <strong><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></strong><br>
                <?php echo htmlspecialchars($order['customer_email'] ?? ''); ?><br>
                <?php if (!empty($order['customer_phone'])) echo htmlspecialchars($order['customer_phone']); ?>
            </div>
            <div class="address-col">
                <h3>Ship To</h3>
                <?php echo formatAddressCompact($shippingAddress); ?>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">Description</th>
                    <th class="col-center" style="width: 10%;">Qty</th>
                    <th class="col-right" style="width: 20%;">Unit Price</th>
                    <th class="col-right" style="width: 20%;">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $i = 1;
                foreach ($orderItems as $item): 
                    $sku = $item['product_sku'] ?? '';
                ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                        <?php if ($sku) echo '<br><small>SKU: ' . htmlspecialchars($sku) . '</small>'; ?>
                    </td>
                    <td class="col-center"><?php echo (int)$item['quantity']; ?></td>
                    <td class="col-right">R<?php echo number_format($item['unit_price'] ?? 0, 2); ?></td>
                    <td class="col-right">R<?php echo number_format($item['total_price'] ?? 0, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td>R<?php echo number_format($order['subtotal'] ?? 0, 2); ?></td>
                </tr>
                <tr>
                    <td class="label">Delivery:</td>
                    <td>R<?php echo number_format($order['shipping_amount'] ?? 0, 2); ?></td>
                </tr>
                <?php if (($order['tax_amount'] ?? 0) > 0): ?>
                <tr>
                    <td class="label">Tax:</td>
                    <td>R<?php echo number_format($order['tax_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <?php if (($order['discount_amount'] ?? 0) > 0): ?>
                <tr>
                    <td class="label">Discount:</td>
                    <td>-R<?php echo number_format($order['discount_amount'], 2); ?></td>
                </tr>
                <?php endif; ?>
                <tr class="grand-total">
                    <td class="label">TOTAL:</td>
                    <td>R<?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <div class="payment-info">
                <?php if (!empty($bankName) && !empty($bankAccountNumber)): ?>
                    <h4>Banking Details</h4>
                    <p>
                        <strong>Bank:</strong> <?php echo htmlspecialchars($bankName); ?><br>
                        <strong>Account:</strong> <?php echo htmlspecialchars($bankAccountNumber); ?><br>
                        <strong>Branch:</strong> <?php echo htmlspecialchars($bankBranchCode); ?>
                    </p>
                <?php endif; ?>
                <p style="margin-top: 15px; font-style: italic;">Thank you for your business.</p>
                <?php if (!empty($paymentMethodName) && $paymentMethodName !== 'N/A'): ?>
                    <p style="margin-top: 10px;"><strong>Payment Method:</strong> <?php echo htmlspecialchars($paymentMethodName); ?></p>
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
                <?php endif; ?>
            </div>
            <?php if (!empty($qrCodeUrl)): ?>
            <div class="qr-code">
                 <p style="font-size: 11px; margin-bottom: 5px; color: #666;">Scan to Pay:</p>
                 <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="Payment QR Code" style="width:80px;height:80px; border: 1px solid #000; padding: 5px;">
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
