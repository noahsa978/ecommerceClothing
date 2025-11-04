<?php
// Suppress any output before PDF generation
ob_start();
error_reporting(E_ERROR | E_PARSE);

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/db_connect.php';

// Simple auth check - just verify user is logged in
// Admin pages are typically accessed only by admins, so we trust the session
if (empty($_SESSION['user_id'])) {
  http_response_code(403);
  die('Access denied. Please log in.');
}

// Get date range from request
$from = isset($_GET['from']) ? trim($_GET['from']) : date('Y-m-01'); // Default: first day of current month
$to = isset($_GET['to']) ? trim($_GET['to']) : date('Y-m-d'); // Default: today

// Validate dates
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
  $from = date('Y-m-01');
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
  $to = date('Y-m-d');
}

// Load company settings
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

// Fetch orders within date range
$orders = [];
$totalRevenue = 0.0;
$paidOrders = 0;
$refundedOrders = 0;
$cancelledOrders = 0;

if ($conn instanceof mysqli) {
  $stmt = $conn->prepare("
    SELECT o.id, o.user_id, o.email, o.first_name, o.last_name, o.total_amount, 
           o.status, o.payment_method, o.created_at
    FROM orders o
    WHERE DATE(o.created_at) >= ? AND DATE(o.created_at) <= ?
    ORDER BY o.created_at DESC
  ");
  $stmt->bind_param('ss', $from, $to);
  if ($stmt->execute()) {
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
      $orders[] = $row;
      $amount = (float)($row['total_amount'] ?? 0);
      $status = strtolower((string)($row['status'] ?? ''));
      
      if (in_array($status, ['paid', 'completed', 'delivered'])) {
        $totalRevenue += $amount;
        $paidOrders++;
      } elseif ($status === 'refunded') {
        $refundedOrders++;
      } elseif ($status === 'cancelled') {
        $cancelledOrders++;
      }
    }
  }
  $stmt->close();
}

$totalOrders = count($orders);
$averageOrderValue = $paidOrders > 0 ? ($totalRevenue / $paidOrders) : 0;

// Clean any output buffer before generating PDF
ob_end_clean();

// Generate PDF
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
      
      $html = generateSalesReportHTML($companyInfo, $from, $to, $orders, $totalOrders, $paidOrders, $refundedOrders, $cancelledOrders, $totalRevenue, $averageOrderValue);
      
      $mpdf->WriteHTML($html);
      $mpdf->Output('Sales_Report_' . $from . '_to_' . $to . '.pdf', 'D');
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
      
      $html = generateSalesReportHTML($companyInfo, $from, $to, $orders, $totalOrders, $paidOrders, $refundedOrders, $cancelledOrders, $totalRevenue, $averageOrderValue);
      
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'portrait');
      $dompdf->render();
      $dompdf->stream('Sales_Report_' . $from . '_to_' . $to . '.pdf', ['Attachment' => 1]);
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
      
      $pdf->SetCreator($companyInfo['company_name']);
      $pdf->SetAuthor($companyInfo['company_name']);
      $pdf->SetTitle('Sales Report');
      $pdf->SetSubject('Sales Report');
      
      $pdf->setPrintHeader(false);
      $pdf->setPrintFooter(false);
      $pdf->SetMargins(15, 15, 15);
      $pdf->SetAutoPageBreak(TRUE, 15);
      
      $pdf->AddPage();
      $pdf->SetFont('helvetica', '', 10);
      
      $html = generateSalesReportHTML($companyInfo, $from, $to, $orders, $totalOrders, $paidOrders, $refundedOrders, $cancelledOrders, $totalRevenue, $averageOrderValue);
      
      $pdf->writeHTML($html, true, false, true, false, '');
      
      $pdf->Output('Sales_Report_' . $from . '_to_' . $to . '.pdf', 'D');
      $pdfGenerated = true;
      exit;
    } catch (Exception $e) {
      // Continue to fallback
    }
  }
}

// Fallback: Generate HTML and use browser's print-to-PDF
if (!$pdfGenerated) {
  $html = generateSalesReportHTML($companyInfo, $from, $to, $orders, $totalOrders, $paidOrders, $refundedOrders, $cancelledOrders, $totalRevenue, $averageOrderValue);
  
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

function generateSalesReportHTML($companyInfo, $from, $to, $orders, $totalOrders, $paidOrders, $refundedOrders, $cancelledOrders, $totalRevenue, $averageOrderValue) {
  $companyName = htmlspecialchars($companyInfo['company_name'] ?? 'Ecom Clothing');
  $companyEmail = htmlspecialchars($companyInfo['contact_email'] ?? 'info@ecomclothing.com');
  $companyPhone = htmlspecialchars($companyInfo['phone'] ?? '+251-XXX-XXXX');
  $companyAddress = htmlspecialchars($companyInfo['address'] ?? '');
  
  $generatedOn = date('Y-m-d H:i:s');
  $fromDate = date('F d, Y', strtotime($from));
  $toDate = date('F d, Y', strtotime($to));
  $reportPeriod = date('F Y', strtotime($from));
  
  $html = '
  <!DOCTYPE html>
  <html>
  <head>
    <meta charset="UTF-8">
    <style>
      body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 20px; }
      .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #7c3aed; padding-bottom: 20px; }
      .header h1 { margin: 0; color: #7c3aed; font-size: 28px; text-transform: uppercase; }
      .header .subtitle { margin: 5px 0; color: #666; font-size: 16px; font-weight: bold; }
      .header p { margin: 5px 0; color: #666; font-size: 14px; }
      .meta-info { margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px; }
      .meta-info p { margin: 5px 0; font-size: 14px; }
      .meta-info strong { color: #7c3aed; }
      .section-title { font-size: 18px; font-weight: bold; margin: 25px 0 10px; color: #7c3aed; border-bottom: 1px solid #ddd; padding-bottom: 5px; }
      .sales-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px; }
      .sales-table th { background: #7c3aed; color: white; padding: 10px 8px; text-align: left; }
      .sales-table td { padding: 8px; border-bottom: 1px solid #ddd; }
      .sales-table tr:last-child td { border-bottom: none; }
      .sales-table tr:nth-child(even) { background: #f9f9f9; }
      .summary-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
      .summary-card { padding: 15px; background: #f0f0f0; border-radius: 8px; border-left: 4px solid #7c3aed; }
      .summary-card .label { font-size: 12px; color: #666; margin-bottom: 5px; }
      .summary-card .value { font-size: 24px; font-weight: bold; color: #333; }
      .footer { margin-top: 40px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #ddd; padding-top: 20px; }
      .footer p { margin: 5px 0; }
      .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; }
      .status-paid { background: #d1fae5; color: #065f46; }
      .status-pending { background: #fef3c7; color: #92400e; }
      .status-refunded { background: #fee2e2; color: #991b1b; }
      .status-cancelled { background: #e5e7eb; color: #374151; }
    </style>
  </head>
  <body>
    <div class="header">
      <h1>' . $companyName . '</h1>
      <div class="subtitle">Sales Report</div>' . 
      ($companyAddress ? '<p>' . $companyAddress . '</p>' : '') . '
      <p>Email: ' . $companyEmail . ' | Phone: ' . $companyPhone . '</p>
    </div>
    
    <div class="meta-info">
      <p><strong>Report Period:</strong> ' . htmlspecialchars($fromDate) . ' to ' . htmlspecialchars($toDate) . '</p>
      <p><strong>Generated On:</strong> ' . htmlspecialchars($generatedOn) . '</p>
    </div>
    
    <h2 class="section-title">Sales Summary</h2>
    <table class="sales-table">
      <thead>
        <tr>
          <th>Order ID</th>
          <th>Customer</th>
          <th>Date</th>
          <th>Payment Method</th>
          <th>Status</th>
          <th style="text-align: right;">Total Amount</th>
        </tr>
      </thead>
      <tbody>';
  
  if (empty($orders)) {
    $html .= '<tr><td colspan="6" style="text-align: center; padding: 20px;">No sales data for this period.</td></tr>';
  } else {
    foreach ($orders as $order) {
      $orderId = (int)$order['id'];
      $customerName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
      if (empty($customerName)) {
        $customerName = htmlspecialchars($order['email'] ?? 'N/A');
      } else {
        $customerName = htmlspecialchars($customerName);
      }
      $orderDate = date('M d, Y H:i', strtotime($order['created_at']));
      $paymentMethod = ucfirst(htmlspecialchars($order['payment_method'] ?? 'N/A'));
      $status = strtolower($order['status'] ?? 'pending');
      $statusClass = 'status-pending';
      $statusLabel = ucfirst($status);
      
      if (in_array($status, ['paid', 'completed', 'delivered'])) {
        $statusClass = 'status-paid';
        $statusLabel = 'Paid';
      } elseif ($status === 'refunded') {
        $statusClass = 'status-refunded';
        $statusLabel = 'Refunded';
      } elseif ($status === 'cancelled') {
        $statusClass = 'status-cancelled';
        $statusLabel = 'Cancelled';
      }
      
      $amount = number_format((float)$order['total_amount'], 2);
      
      $html .= '<tr>
        <td>#' . $orderId . '</td>
        <td>' . $customerName . '</td>
        <td>' . htmlspecialchars($orderDate) . '</td>
        <td>' . $paymentMethod . '</td>
        <td><span class="status-badge ' . $statusClass . '">' . $statusLabel . '</span></td>
        <td style="text-align: right;">ETB ' . $amount . '</td>
      </tr>';
    }
  }
  
  $html .= '
      </tbody>
    </table>
    
    <h2 class="section-title">Totals Summary</h2>
    <div class="summary-grid">
      <div class="summary-card">
        <div class="label">Total Orders</div>
        <div class="value">' . $totalOrders . '</div>
      </div>
      <div class="summary-card">
        <div class="label">Paid Orders</div>
        <div class="value">' . $paidOrders . '</div>
      </div>
      <div class="summary-card">
        <div class="label">Refunded Orders</div>
        <div class="value">' . $refundedOrders . '</div>
      </div>
      <div class="summary-card">
        <div class="label">Cancelled Orders</div>
        <div class="value">' . $cancelledOrders . '</div>
      </div>
      <div class="summary-card" style="grid-column: span 2; border-left-color: #10b981;">
        <div class="label">Total Revenue (Paid Orders)</div>
        <div class="value" style="color: #10b981;">ETB ' . number_format($totalRevenue, 2) . '</div>
      </div>
      <div class="summary-card" style="grid-column: span 2;">
        <div class="label">Average Order Value</div>
        <div class="value">ETB ' . number_format($averageOrderValue, 2) . '</div>
      </div>
    </div>
    
    <div class="footer">
      <p><strong>Generated by ' . $companyName . ' Admin System</strong></p>
      <p>Report Period: ' . htmlspecialchars($reportPeriod) . '</p>
      <p>Thank you for using our reporting system.</p>
    </div>
  </body>
  </html>';
  
  return $html;
}
