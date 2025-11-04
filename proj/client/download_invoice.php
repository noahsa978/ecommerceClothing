<?php
// Suppress any output before PDF generation
ob_start();
error_reporting(E_ERROR | E_PARSE);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db_connect.php';

// Auth: require login
if (empty($_SESSION['user_id'])) {
  http_response_code(302);
  header('Location: ./loginc.php');
  exit;
}
$uid = (int)$_SESSION['user_id'];

// Get order id
$oid = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($oid <= 0) {
  http_response_code(400);
  die('Invalid order id');
}

// Load order (only if it belongs to user)
$order = null;
if ($conn instanceof mysqli) {
  if ($st = $conn->prepare('SELECT id, user_id, email, first_name, last_name, city, delivery_location, phone_number, payment_method, transaction_ref, order_items, total_amount, status, created_at FROM orders WHERE id=? AND user_id=? LIMIT 1')) {
    $st->bind_param('ii', $oid, $uid);
    if ($st->execute()) {
      $res = $st->get_result();
      $order = $res->fetch_assoc() ?: null;
    }
    $st->close();
  }
}

if (!$order) {
  http_response_code(404);
  die('Order not found');
}

// Decode items
$items = [];
$raw = $order['order_items'] ?? '[]';
$decoded = json_decode($raw, true);
if (is_array($decoded)) { $items = $decoded; }

// Enrich each item with product/variant details if missing name
function enrich_item($conn, $item) {
  if (!empty($item['name'])) return $item;
  $vid = isset($item['variant_id']) ? (int)$item['variant_id'] : 0;
  if ($vid > 0 && ($conn instanceof mysqli)) {
    $sql = 'SELECT p.name AS product_name, v.size, v.color FROM product_variants v INNER JOIN products p ON p.id=v.product_id WHERE v.id=? LIMIT 1';
    if ($st = $conn->prepare($sql)) {
      $st->bind_param('i', $vid);
      if ($st->execute()) {
        $res = $st->get_result();
        if ($r = $res->fetch_assoc()) {
          $item['name'] = trim(($r['product_name'] ?? 'Variant #'.$vid) . ' ' . ($r['size'] ? ('['.$r['size'].']') : '') . ' ' . ($r['color'] ? ('- '.$r['color']) : ''));
        }
      }
      $st->close();
    }
  }
  if (empty($item['name'])) $item['name'] = 'Variant #'.$vid;
  return $item;
}

$subtotal = 0.0;
$qty_sum = 0;
$enriched = [];
foreach ($items as $it) {
  $it = enrich_item($conn, $it);
  $qty = isset($it['quantity']) ? (int)$it['quantity'] : 1;
  $price = isset($it['price']) ? (float)$it['price'] : 0.0;
  $line_total = $qty * $price;
  $qty_sum += max(0, $qty);
  $subtotal += $line_total;
  $it['_qty'] = $qty;
  $it['_price'] = $price;
  $it['_line_total'] = $line_total;
  $enriched[] = $it;
}

$total_amount = (float)($order['total_amount'] ?? $subtotal);
$shipping = $subtotal >= 100 ? 0.0 : 9.99;
$tax = $subtotal * 0.084;

$full_name = trim(($order['first_name'] ?? '').' '.($order['last_name'] ?? ''));
$created = substr((string)($order['created_at'] ?? ''), 0, 19);
$status = ucfirst((string)($order['status'] ?? 'pending'));
$addr = trim(($order['city'] ?? '').($order['delivery_location'] ? (' - '.$order['delivery_location']) : ''));
$phone = (string)($order['phone_number'] ?? '');
$email = (string)($order['email'] ?? '');
$payment = ucfirst((string)($order['payment_method'] ?? ''));
$tx = (string)($order['transaction_ref'] ?? 'N/A');

// Load company settings from database
$companyInfo = [
  'company_name' => 'Ecom Clothing',
  'address' => '123 Main Street, City',
  'contact_email' => 'info@ecomclothing.com',
  'phone' => '+251-XXX-XXXX',
];
if ($conn instanceof mysqli) {
  if ($res = $conn->query('SELECT company_name, address, contact_email, phone FROM company_settings WHERE id=1 LIMIT 1')) {
    if ($row = $res->fetch_assoc()) {
      $companyInfo = array_merge($companyInfo, $row);
    }
  }
}

// Clean any output buffer before generating PDF
ob_end_clean();

// Try to use mPDF (lightweight and commonly available)
$mpdfPath = __DIR__ . '/../vendor/autoload.php';
$vendorPath = __DIR__ . '/../../vendor/autoload.php';

$pdfGenerated = false;

// Try mPDF first
if (file_exists($mpdfPath) || file_exists($vendorPath)) {
  $autoloadPath = file_exists($mpdfPath) ? $mpdfPath : $vendorPath;
  require_once $autoloadPath;
  
  if (class_exists('\Mpdf\Mpdf')) {
    try {
      $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'margin_left' => 15,
        'margin_right' => 15,
        'margin_top' => 15,
        'margin_bottom' => 15,
      ]);
      
      $html = generateInvoiceHTML($oid, $full_name, $email, $phone, $addr, $created, $status, $payment, $tx, $enriched, $subtotal, $shipping, $tax, $total_amount, $companyInfo);
      
      $mpdf->WriteHTML($html);
      $mpdf->Output('Invoice_' . $oid . '.pdf', 'D');
      $pdfGenerated = true;
      exit;
    } catch (Exception $e) {
      // Continue to next option
    }
  }
  
  // Try DomPDF
  if (!$pdfGenerated && class_exists('\Dompdf\Dompdf')) {
    try {
      $options = new \Dompdf\Options();
      $options->set('isHtml5ParserEnabled', true);
      $options->set('isRemoteEnabled', false);
      
      $dompdf = new \Dompdf\Dompdf($options);
      
      $html = generateInvoiceHTML($oid, $full_name, $email, $phone, $addr, $created, $status, $payment, $tx, $enriched, $subtotal, $shipping, $tax, $total_amount, $companyInfo);
      
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'portrait');
      $dompdf->render();
      $dompdf->stream('Invoice_' . $oid . '.pdf', ['Attachment' => 1]);
      $pdfGenerated = true;
      exit;
    } catch (Exception $e) {
      // Continue to next option
    }
  }
  
  // Try TCPDF
  if (!$pdfGenerated && class_exists('TCPDF')) {
    try {
      $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
      
      $pdf->SetCreator('Ecom Clothing');
      $pdf->SetAuthor('Ecom Clothing');
      $pdf->SetTitle('Invoice #' . $oid);
      $pdf->SetSubject('Order Invoice');
      
      $pdf->setPrintHeader(false);
      $pdf->setPrintFooter(false);
      $pdf->SetMargins(15, 15, 15);
      $pdf->SetAutoPageBreak(TRUE, 15);
      
      $pdf->AddPage();
      $pdf->SetFont('helvetica', '', 10);
      
      $html = generateInvoiceHTML($oid, $full_name, $email, $phone, $addr, $created, $status, $payment, $tx, $enriched, $subtotal, $shipping, $tax, $total_amount, $companyInfo);
      
      $pdf->writeHTML($html, true, false, true, false, '');
      
      $pdf->Output('Invoice_' . $oid . '.pdf', 'D');
      $pdfGenerated = true;
      exit;
    } catch (Exception $e) {
      // Continue to fallback
    }
  }
}

// Fallback: Generate HTML and use browser's print-to-PDF
if (!$pdfGenerated) {
  $html = generateInvoiceHTML($oid, $full_name, $email, $phone, $addr, $created, $status, $payment, $tx, $enriched, $subtotal, $shipping, $tax, $total_amount, $companyInfo);
  
  // Add print styles and auto-print script
  $html = str_replace('</head>', '
    <style>
      @media print {
        body { margin: 0; padding: 20px; }
        .no-print { display: none; }
      }
    </style>
    <script>
      window.onload = function() {
        // Auto-trigger print dialog
        setTimeout(function() {
          window.print();
        }, 500);
      };
    </script>
  </head>', $html);
  
  $html = str_replace('<body>', '<body>
    <div class="no-print" style="position: fixed; top: 10px; right: 10px; background: #7c3aed; color: white; padding: 10px 20px; border-radius: 8px; cursor: pointer;" onclick="window.print();">
      Print / Save as PDF
    </div>', $html);
  
  echo $html;
  exit;
}

function generateInvoiceHTML($oid, $full_name, $email, $phone, $addr, $created, $status, $payment, $tx, $enriched, $subtotal, $shipping, $tax, $total_amount, $companyInfo) {
  $companyName = htmlspecialchars($companyInfo['company_name'] ?? 'Ecom Clothing');
  $companyEmail = htmlspecialchars($companyInfo['contact_email'] ?? 'info@ecomclothing.com');
  $companyPhone = htmlspecialchars($companyInfo['phone'] ?? '+251-XXX-XXXX');
  $companyAddress = htmlspecialchars($companyInfo['address'] ?? '');
  
  $html = '
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="UTF-8">
    <style>
      body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 20px; }
      .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #7c3aed; padding-bottom: 20px; }
      .header h1 { margin: 0; color: #7c3aed; font-size: 28px; text-transform: uppercase; }
      .header p { margin: 5px 0; color: #666; font-size: 14px; }
      .invoice-info { margin-bottom: 30px; }
      .invoice-info table { width: 100%; }
      .invoice-info td { padding: 5px; }
      .invoice-info .label { font-weight: bold; width: 150px; }
      .section-title { font-size: 18px; font-weight: bold; margin: 20px 0 10px; color: #7c3aed; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
      .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
      .items-table th { background: #7c3aed; color: white; padding: 10px; text-align: left; }
      .items-table td { padding: 8px; border-bottom: 1px solid #ddd; }
      .items-table tr:last-child td { border-bottom: none; }
      .totals { margin-top: 20px; text-align: right; }
      .totals table { margin-left: auto; width: 300px; }
      .totals td { padding: 5px 10px; }
      .totals .label { font-weight: bold; }
      .totals .total-row { font-size: 18px; font-weight: bold; border-top: 2px solid #333; }
      .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
    </style>
  </head>
  <body>
    <div class="header">
      <h1>' . $companyName . '</h1>
      <p>Premium Fashion for Everyone</p>' . 
      ($companyAddress ? '<p>' . $companyAddress . '</p>' : '') . '
      <p>Email: ' . $companyEmail . ' | Phone: ' . $companyPhone . '</p>
    </div>
    
    <div class="invoice-info">
      <h2 class="section-title">Invoice #' . $oid . '</h2>
      <table>
        <tr>
          <td class="label">Invoice Date:</td>
          <td>' . htmlspecialchars($created) . '</td>
          <td class="label">Status:</td>
          <td>' . htmlspecialchars($status) . '</td>
        </tr>
        <tr>
          <td class="label">Customer Name:</td>
          <td>' . htmlspecialchars($full_name) . '</td>
          <td class="label">Payment Method:</td>
          <td>' . htmlspecialchars($payment) . '</td>
        </tr>
        <tr>
          <td class="label">Email:</td>
          <td>' . htmlspecialchars($email) . '</td>
          <td class="label">Transaction Ref:</td>
          <td>' . htmlspecialchars($tx) . '</td>
        </tr>
        <tr>
          <td class="label">Phone:</td>
          <td>' . htmlspecialchars($phone) . '</td>
          <td class="label">Delivery Address:</td>
          <td>' . htmlspecialchars($addr) . '</td>
        </tr>
      </table>
    </div>
    
    <h2 class="section-title">Order Items</h2>
    <table class="items-table">
      <thead>
        <tr>
          <th>Item</th>
          <th style="text-align: center;">Quantity</th>
          <th style="text-align: right;">Unit Price</th>
          <th style="text-align: right;">Total</th>
        </tr>
      </thead>
      <tbody>';
  
  if (empty($enriched)) {
    $html .= '<tr><td colspan="4" style="text-align: center;">No items</td></tr>';
  } else {
    foreach ($enriched as $it) {
      $html .= '<tr>
        <td>' . htmlspecialchars($it['name'] ?? 'Item') . '</td>
        <td style="text-align: center;">' . (int)$it['_qty'] . '</td>
        <td style="text-align: right;">ETB ' . number_format((float)$it['_price'], 2) . '</td>
        <td style="text-align: right;">ETB ' . number_format((float)$it['_line_total'], 2) . '</td>
      </tr>';
    }
  }
  
  $html .= '
      </tbody>
    </table>
    
    <div class="totals">
      <table>
        <tr>
          <td class="label">Subtotal:</td>
          <td style="text-align: right;">ETB ' . number_format($subtotal, 2) . '</td>
        </tr>
        <tr>
          <td class="label">Shipping:</td>
          <td style="text-align: right;">' . ($shipping == 0 ? 'FREE' : 'ETB ' . number_format($shipping, 2)) . '</td>
        </tr>
        <tr>
          <td class="label">Tax (8.4%):</td>
          <td style="text-align: right;">ETB ' . number_format($tax, 2) . '</td>
        </tr>
        <tr class="total-row">
          <td class="label">Total:</td>
          <td style="text-align: right;">ETB ' . number_format($total_amount, 2) . '</td>
        </tr>
      </table>
    </div>
    
    <div class="footer">
      <p>Thank you for your business!</p>
      <p>This is a computer-generated invoice and does not require a signature.</p>
    </div>
  </body>
  </html>';
  
  return $html;
}
