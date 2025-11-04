<?php
  $page_title = 'Admin • Employees';
  require_once __DIR__ . '/../includes/db_connect.php';
  $flash = null;

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'add_employee' && ($conn instanceof mysqli)) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $salary = $_POST['salary'] !== '' ? (float)$_POST['salary'] : null;
    $hire_date = trim($_POST['hire_date'] ?? '');
    $status = in_array(($_POST['status'] ?? 'active'), ['active','inactive'], true) ? $_POST['status'] : 'active';
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
    if ($name === '' || $role === '') {
      $flash = ['type' => 'error', 'msg' => 'Please provide name and role. Email is optional.'];
      if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => $flash['msg']]); exit; }
    } else {
      // Treat empty email as NULL
      if ($email === '') { $email = null; }
      $stmt = $conn->prepare('INSERT INTO employees (name, email, phone, role, salary, hire_date, status) VALUES (?,?,?,?,?,?,?)');
      // bind: s(name) s(email) s(phone) s(role) d(salary) s(hire_date) s(status)
      $stmt->bind_param('ssssdss', $name, $email, $phone, $role, $salary, $hire_date, $status);
      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'Employee added successfully.'];
        if ($isAjax) {
          $newId = $conn->insert_id;
          header('Content-Type: application/json');
          echo json_encode([
            'success' => true,
            'message' => $flash['msg'],
            'employee' => [
              'id' => (int)$newId,
              'name' => $name,
              'email' => $email,
              'phone' => $phone,
              'role' => $role,
              'salary' => $salary,
              'hire_date' => $hire_date,
              'status' => $status,
            ]
          ]);
          $stmt->close();
          exit;
        }
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to add employee: ' . $stmt->error];
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => $flash['msg']]); $stmt->close(); exit; }
      }
      $stmt->close();
    }
  }

  // Update Employee
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'update_employee' && ($conn instanceof mysqli)) {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = trim($_POST['role'] ?? '');
    $salary = $_POST['salary'] !== '' ? (float)$_POST['salary'] : null;
    $hire_date = trim($_POST['hire_date'] ?? '');
    $status = in_array(($_POST['status'] ?? 'active'), ['active','inactive'], true) ? $_POST['status'] : 'active';
    if ($id <= 0 || $name === '' || $role === '') {
      $flash = ['type' => 'error', 'msg' => 'Please provide a valid name and role.'];
    } else {
      if ($email === '') { $email = null; }
      $stmt = $conn->prepare('UPDATE employees SET name=?, email=?, phone=?, role=?, salary=?, hire_date=?, status=? WHERE id=?');
      $stmt->bind_param('ssssdssi', $name, $email, $phone, $role, $salary, $hire_date, $status, $id);
      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'Employee updated.'];
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to update employee: ' . $stmt->error];
      }
      $stmt->close();
    }
  }

  // Delete Employee
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'delete_employee' && ($conn instanceof mysqli)) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
      $stmt = $conn->prepare('DELETE FROM employees WHERE id=?');
      $stmt->bind_param('i', $id);
      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'Employee deleted.'];
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to delete employee: ' . $stmt->error];
      }
      $stmt->close();
    } else {
      $flash = ['type' => 'error', 'msg' => 'Invalid employee ID.'];
    }
  }

  // Add Attendance
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'add_attendance' && ($conn instanceof mysqli)) {
    $employee_id = intval($_POST['employee_id'] ?? 0);
    $date = trim($_POST['date'] ?? '');
    $check_in = trim($_POST['check_in'] ?? '');
    $check_out = trim($_POST['check_out'] ?? '');
    $status = trim($_POST['status'] ?? 'Present');
    $remarks = trim($_POST['remarks'] ?? '');
    $isAjax = isset($_POST['ajax']) && $_POST['ajax'] === '1';
    
    if ($employee_id <= 0 || $date === '') {
      $flash = ['type' => 'error', 'msg' => 'Please provide employee and date.'];
      if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => $flash['msg']]); exit; }
    } else {
      // Check if attendance already exists for this employee on this date
      $checkStmt = $conn->prepare('SELECT id FROM attendance WHERE employee_id=? AND date=?');
      $checkStmt->bind_param('is', $employee_id, $date);
      $checkStmt->execute();
      $checkStmt->store_result();
      
      if ($checkStmt->num_rows > 0) {
        $flash = ['type' => 'error', 'msg' => 'Attendance record already exists for this employee on this date.'];
        if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => $flash['msg']]); $checkStmt->close(); exit; }
      } else {
        $check_in = $check_in !== '' ? $check_in : null;
        $check_out = $check_out !== '' ? $check_out : null;
        $remarks = $remarks !== '' ? $remarks : null;
        
        $stmt = $conn->prepare('INSERT INTO attendance (employee_id, date, check_in, check_out, status, remarks) VALUES (?,?,?,?,?,?)');
        $stmt->bind_param('isssss', $employee_id, $date, $check_in, $check_out, $status, $remarks);
        if ($stmt->execute()) {
          $flash = ['type' => 'success', 'msg' => 'Attendance record added successfully.'];
          if ($isAjax) {
            $newId = $conn->insert_id;
            // fetch employee name
            $empName = null;
            $empQ = $conn->prepare('SELECT name FROM employees WHERE id=?');
            if ($empQ) {
              $empQ->bind_param('i', $employee_id);
              $empQ->execute();
              $empQ->bind_result($empName);
              $empQ->fetch();
              $empQ->close();
            }
            header('Content-Type: application/json');
            echo json_encode([
              'success' => true,
              'message' => $flash['msg'],
              'attendance' => [
                'id' => (int)$newId,
                'employee_id' => (int)$employee_id,
                'employee_name' => $empName ?: 'Unknown',
                'date' => $date,
                'check_in' => $check_in,
                'check_out' => $check_out,
                'status' => $status,
                'remarks' => $remarks,
              ]
            ]);
            $stmt->close();
            $checkStmt->close();
            exit;
          }
        } else {
          $flash = ['type' => 'error', 'msg' => 'Failed to add attendance: ' . $stmt->error];
          if ($isAjax) { header('Content-Type: application/json'); echo json_encode(['success' => false, 'message' => $flash['msg']]); $stmt->close(); $checkStmt->close(); exit; }
        }
        $stmt->close();
      }
      $checkStmt->close();
    }
  }

  // Update Attendance
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'update_attendance' && ($conn instanceof mysqli)) {
    $id = intval($_POST['id'] ?? 0);
    $check_in = trim($_POST['check_in'] ?? '');
    $check_out = trim($_POST['check_out'] ?? '');
    $status = trim($_POST['status'] ?? 'Present');
    $remarks = trim($_POST['remarks'] ?? '');
    
    if ($id <= 0) {
      $flash = ['type' => 'error', 'msg' => 'Invalid attendance ID.'];
    } else {
      $check_in = $check_in !== '' ? $check_in : null;
      $check_out = $check_out !== '' ? $check_out : null;
      $remarks = $remarks !== '' ? $remarks : null;
      
      $stmt = $conn->prepare('UPDATE attendance SET check_in=?, check_out=?, status=?, remarks=? WHERE id=?');
      $stmt->bind_param('ssssi', $check_in, $check_out, $status, $remarks, $id);
      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'Attendance record updated.'];
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to update attendance: ' . $stmt->error];
      }
      $stmt->close();
    }
  }

  // Delete Attendance
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'delete_attendance' && ($conn instanceof mysqli)) {
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
      $stmt = $conn->prepare('DELETE FROM attendance WHERE id=?');
      $stmt->bind_param('i', $id);
      if ($stmt->execute()) {
        $flash = ['type' => 'success', 'msg' => 'Attendance record deleted.'];
      } else {
        $flash = ['type' => 'error', 'msg' => 'Failed to delete attendance: ' . $stmt->error];
      }
      $stmt->close();
    } else {
      $flash = ['type' => 'error', 'msg' => 'Invalid attendance ID.'];
    }
  }

  // Search and load lists
  $a_search = isset($_GET['a_search']) ? trim($_GET['a_search']) : '';
  $a_employee_id = isset($_GET['a_employee_id']) ? trim($_GET['a_employee_id']) : '';
  $a_employee_name = isset($_GET['a_employee_name']) ? trim($_GET['a_employee_name']) : '';
  $a_date_from = isset($_GET['a_date_from']) ? trim($_GET['a_date_from']) : '';
  $a_date_to = isset($_GET['a_date_to']) ? trim($_GET['a_date_to']) : '';
  
  // Attendance Matrix parameters
  $matrix_month = isset($_GET['matrix_month']) ? trim($_GET['matrix_month']) : date('Y-m');
  $matrix_year = isset($_GET['matrix_year']) ? (int)$_GET['matrix_year'] : (int)date('Y');
  $matrix_month_num = isset($_GET['matrix_month']) ? (int)date('n', strtotime($_GET['matrix_month'])) : (int)date('n');
  
  $employees_list = [];
  if ($conn instanceof mysqli) {
    $sqlAll = 'SELECT id, name, email, phone, role, salary, hire_date, status FROM employees ORDER BY id ASC LIMIT 1000';
    if ($res = $conn->query($sqlAll)) { while ($r = $res->fetch_assoc()) { $employees_list[] = $r; } $res->free(); }

    $employees_edit = [];
    $sqlEdit = 'SELECT id, name, email, phone, role, salary, hire_date, status FROM employees ORDER BY id ASC LIMIT 1000';
    if ($res2 = $conn->query($sqlEdit)) { while ($r = $res2->fetch_assoc()) { $employees_edit[] = $r; } $res2->free(); }
    
    // Load attendance records
    $attendance_list = [];
    $sqlAtt = 'SELECT a.id, a.employee_id, a.date, a.check_in, a.check_out, a.status, a.remarks, e.name as employee_name 
               FROM attendance a 
               LEFT JOIN employees e ON a.employee_id = e.id 
               WHERE 1=1';
    
    $conditions = [];
    // Back-compat combined search
    if ($a_search !== '') {
      $like = '%' . $conn->real_escape_string($a_search) . '%';
      $conditions[] = "(e.name LIKE '$like' OR CAST(a.employee_id AS CHAR) LIKE '$like')";
    }
    // Dedicated filters
    if ($a_employee_id !== '') {
      $idInt = (int)$a_employee_id;
      $conditions[] = "a.employee_id = $idInt";
    }
    if ($a_employee_name !== '') {
      $likeName = '%' . $conn->real_escape_string($a_employee_name) . '%';
      $conditions[] = "e.name LIKE '$likeName'";
    }
    if ($a_date_from !== '') {
      $date_from = $conn->real_escape_string($a_date_from);
      $conditions[] = "a.date >= '$date_from'";
    }
    if ($a_date_to !== '') {
      $date_to = $conn->real_escape_string($a_date_to);
      $conditions[] = "a.date <= '$date_to'";
    }
    
    if (!empty($conditions)) {
      $sqlAtt .= ' AND ' . implode(' AND ', $conditions);
    }
    
    $sqlAtt .= ' ORDER BY a.date DESC, a.employee_id ASC LIMIT 1000';
    
    if ($res3 = $conn->query($sqlAtt)) { 
      while ($r = $res3->fetch_assoc()) { 
        $attendance_list[] = $r; 
      } 
      $res3->free(); 
    }

    // Load attendance matrix data
    $attendance_matrix = [];
    $matrix_employees = [];
    $matrix_days = [];
    
    // Get all active employees for the matrix
    $sqlMatrixEmp = 'SELECT id, name FROM employees WHERE status = "active" ORDER BY name ASC';
    if ($res4 = $conn->query($sqlMatrixEmp)) {
      while ($r = $res4->fetch_assoc()) {
        $matrix_employees[] = $r;
      }
      $res4->free();
    }
    
    // Generate days for the selected month
    $first_day = date('Y-m-01', strtotime($matrix_month));
    $last_day = date('Y-m-t', strtotime($matrix_month));
    $current_day = $first_day;
    
    while ($current_day <= $last_day) {
      $matrix_days[] = [
        'date' => $current_day,
        'day' => (int)date('j', strtotime($current_day)),
        'weekday' => date('D', strtotime($current_day))
      ];
      $current_day = date('Y-m-d', strtotime($current_day . ' +1 day'));
    }
    
    // Get attendance data for the selected month
    $sqlMatrixAtt = 'SELECT a.employee_id, a.date, a.status, a.check_in, a.check_out 
                     FROM attendance a 
                     WHERE a.date >= ? AND a.date <= ? 
                     ORDER BY a.employee_id, a.date';
    $stmt = $conn->prepare($sqlMatrixAtt);
    $stmt->bind_param('ss', $first_day, $last_day);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
      $attendance_matrix[$row['employee_id']][$row['date']] = [
        'status' => $row['status'],
        'check_in' => $row['check_in'],
        'check_out' => $row['check_out']
      ];
    }
    $stmt->close();

    // If AJAX attendance search requested, return JSON and exit
    if (
      isset($_GET['ajax']) && $_GET['ajax'] === '1' &&
      (isset($_GET['a_search']) || isset($_GET['a_employee_id']) || isset($_GET['a_employee_name']) || isset($_GET['a_date_from']) || isset($_GET['a_date_to']))
    ) {
      header('Content-Type: application/json');
      echo json_encode([
        'success' => true,
        'attendance' => $attendance_list
      ]);
      exit;
    }
    
    // If AJAX attendance matrix requested, return JSON and exit
    if (isset($_GET['ajax']) && $_GET['ajax'] === '1' && isset($_GET['matrix_month'])) {
      header('Content-Type: application/json');
      echo json_encode([
        'success' => true,
        'employees' => $matrix_employees,
        'days' => $matrix_days,
        'attendance' => $attendance_matrix
      ]);
      exit;
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="../assets/css/global.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
      /* Attendance Matrix Styles */
      .attendance-present {
        background-color: #10b981 !important;
        color: white !important;
        font-weight: bold;
      }
      
      .attendance-absent {
        background-color: #ef4444 !important;
        color: white !important;
        font-weight: bold;
      }
      
      .attendance-late {
        background-color: #f59e0b !important;
        color: white !important;
        font-weight: bold;
      }
      
      .attendance-leave {
        background-color: #8b5cf6 !important;
        color: white !important;
        font-weight: bold;
      }
      
      .attendance-empty {
        background-color: #374151 !important;
        color: #9ca3af !important;
        border: 1px solid #4b5563;
      }
      
      .attendance-present:hover,
      .attendance-absent:hover,
      .attendance-late:hover,
      .attendance-leave:hover,
      .attendance-empty:hover {
        opacity: 0.8;
        transform: scale(1.05);
        transition: all 0.2s ease;
      }
      
      #attendance-matrix-table {
        border-collapse: collapse;
        font-size: 14px;
      }
      
      #attendance-matrix-table th,
      #attendance-matrix-table td {
        border: 1px solid #4b5563;
        padding: 8px;
        text-align: center;
      }
      
      #attendance-matrix-table th {
        background-color: #374151;
        color: #f9fafb;
        font-weight: 600;
      }
      
      #attendance-matrix-table tbody tr:nth-child(even) {
        background-color: #1f2937;
      }
      
      #attendance-matrix-table tbody tr:nth-child(odd) {
        background-color: #111827;
      }
      
      .matrix-container {
        max-height: 70vh;
        overflow: auto;
        border: 1px solid #4b5563;
        border-radius: 8px;
      }
    </style>
  </head>
  <body>

<main class="container">
  <section class="admin-header">
    <h1>Employees</h1>
    <nav class="admin-nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="employees.php" class="active">Employees</a>
      <a href="users.php">Users</a>
      <a href="products.php">Products</a>
      <a href="orders.php">Orders</a>
      <a href="inventory.php">Inventory</a>
      <a href="reports.php">Reports</a>
      <a href="settings.php">Settings</a>
      <a href="../client/logout.php">Logout</a>
    </nav>
  </section>

  <!-- Quick Actions -->
  <section>
    <h2>Quick Actions</h2>
    <div class="quick-actions">
      <button class="btn primary" data-tab="add">Add Employee</button>
      <button class="btn primary" data-tab="edit">Edit Employees</button>
      <button class="btn primary" data-tab="attendance">Manage Attendance</button>
      <button class="btn primary" data-tab="matrix">Attendance Matrix</button>
    </div>
  </section>

  <!-- Tab Contents -->

  <!-- Add Employee -->
  <section class="tab-content" id="tab-add">
    <div class="cards">
      <div class="card">
        <div class="card-head"><strong>Add Employee</strong></div>
        <div class="card-body">
          <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
            <div class="flash-msg <?= $isErr ? 'error' : 'success' ?>" style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
              <?= htmlspecialchars($flash['msg']); ?>
            </div>
          <?php } ?>
          <div id="add-employee-message" style="margin-bottom:10px;"></div>
          <form id="add-employee-form" method="post" action="">
            <input type="hidden" name="form_type" value="add_employee" />
            <input type="text" name="name" placeholder="Employee Name" required />
            <input type="email" name="email" placeholder="Email" />
            <input type="text" name="phone" placeholder="Phone" />
            <select name="role" required>
              <option value="">Select Role</option>
              <option value="Manager">Manager</option>
              <option value="Delivery Person">Delivery Person</option>
              <option value="Ware House Staff">Ware House Staff</option>
              <option value="Sales Person">Sales Person</option>
            </select>
            <input type="number" name="salary" placeholder="Salary" step="0.01" min="0" />
            <input type="date" name="hire_date" placeholder="Hire Date" />
            <select name="status" required>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
            <button type="submit" class="btn primary">Save</button>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Edit Employees -->
  <section class="tab-content" id="tab-edit" style="display:none;">
    <div class="cards">
      <div class="card" style="grid-column: 1 / -1;">
        <div class="card-head"><strong>Edit Employees</strong></div>
        <div class="card-body">
          <div style="margin:0 0 12px;">
            <!-- Search by ID -->
            <div style="display:flex; gap:8px; margin-bottom:8px;">
              <input type="number" id="search-employee-by-id" placeholder="Search by ID" style="width:200px;" />
              <button class="btn primary" type="button" onclick="searchEmployeeById()">Search by ID</button>
              <button class="btn" type="button" onclick="clearEmployeeSearch()">Clear</button>
            </div>
            <!-- Real-time filter -->
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
              <input type="text" id="filter-name" placeholder="Filter by Name" style="flex:1; min-width:150px;" />
              <input type="text" id="filter-email" placeholder="Filter by Email" style="flex:1; min-width:150px;" />
              <input type="text" id="filter-phone" placeholder="Filter by Phone" style="flex:1; min-width:150px;" />
              <select id="filter-status" style="min-width:150px;">
                <option value="">Filter by Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
          </div>
          <table>
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Salary</th>
                <th>Hire Date</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="employees-tbody">
              <?php if (empty($employees_edit)) { ?>
                <tr><td colspan="9">No employees found.</td></tr>
              <?php } else { foreach ($employees_edit as $emp) { ?>
                <tr data-id="<?= (int)$emp['id']; ?>" data-name="<?= htmlspecialchars(strtolower($emp['name'])); ?>" data-email="<?= htmlspecialchars(strtolower((string)($emp['email'] ?? ''))); ?>" data-phone="<?= htmlspecialchars((string)($emp['phone'] ?? '')); ?>" data-status="<?= htmlspecialchars(strtolower($emp['status'])); ?>">
                  <form method="post" action="">
                    <input type="hidden" name="form_type" value="update_employee" />
                    <input type="hidden" name="id" value="<?= (int)$emp['id']; ?>" />
                    <td><?= (int)$emp['id']; ?></td>
                    <td><input type="text" name="name" value="<?= htmlspecialchars($emp['name']); ?>" /></td>
                    <td><input type="email" name="email" value="<?= htmlspecialchars((string)($emp['email'] ?? '')); ?>" /></td>
                    <td><input type="text" name="phone" value="<?= htmlspecialchars((string)($emp['phone'] ?? '')); ?>" /></td>
                    <td>
                      <select name="role">
                        <?php $eroles=['Manager','Delivery Person','Ware House Staff','Sales Person']; foreach ($eroles as $r) { $sel = ($emp['role']===$r)?'selected':''; ?>
                          <option value="<?= $r; ?>" <?= $sel; ?>><?= $r; ?></option>
                        <?php } ?>
                      </select>
                    </td>
                    <td><input type="number" step="0.01" min="0" name="salary" value="<?= htmlspecialchars((string)($emp['salary'] ?? '')); ?>" /></td>
                    <td><input type="date" name="hire_date" value="<?= htmlspecialchars((string)($emp['hire_date'] ?? '')); ?>" /></td>
                    <td>
                      <select name="status">
                        <option value="active" <?= ($emp['status']==='active')?'selected':''; ?>>Active</option>
                        <option value="inactive" <?= ($emp['status']==='inactive')?'selected':''; ?>>Inactive</option>
                      </select>
                    </td>
                    <td>
                      <div class="row-actions">
                        <button class="btn primary" type="submit">Save</button>
                        </form>
                        <form class="employee-delete-form" method="post" action="" onsubmit="return confirm('Remove this employee? This cannot be undone.');">
                          <input type="hidden" name="form_type" value="delete_employee" />
                          <input type="hidden" name="id" value="<?= (int)$emp['id']; ?>" />
                          <button class="btn" type="submit" style="border-color:#7f1d1d; background:#3f1d1d; color:#fecaca;">Remove</button>
                        </form>
                      </div>
                    </td>
                </tr>
              <?php } } ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>

  <!-- Manage Attendance -->
  <section class="tab-content" id="tab-attendance" style="display:none;">
    <div class="cards">
      <!-- Add Attendance Record -->
      <div class="card">
        <div class="card-head"><strong>Add Attendance Record</strong></div>
        <div class="card-body">
          <?php if (!empty($flash)) { $isErr = $flash['type'] === 'error'; ?>
            <div class="flash-msg <?= $isErr ? 'error' : 'success' ?>" style="margin-bottom:10px; padding:10px 12px; border:1px solid <?= $isErr ? '#7f1d1d' : '#065f46' ?>; background: <?= $isErr ? '#3f1d1d' : '#064e3b' ?>; color:#fff; border-radius:10px; font-size:14px;">
              <?= htmlspecialchars($flash['msg']); ?>
            </div>
          <?php } ?>
          <div id="add-attendance-message" style="margin-bottom:10px;"></div>
          <form id="add-attendance-form" method="post" action="">
            <input type="hidden" name="form_type" value="add_attendance" />
            <select name="employee_id" required>
              <option value="">Select Employee</option>
              <?php foreach ($employees_list as $emp) { ?>
                <option value="<?= (int)$emp['id']; ?>"><?= htmlspecialchars($emp['name']); ?> (ID: <?= (int)$emp['id']; ?>)</option>
              <?php } ?>
            </select>
            <input type="date" name="date" placeholder="Date" required />
            <input type="time" name="check_in" placeholder="Check In Time" />
            <input type="time" name="check_out" placeholder="Check Out Time" />
            <select name="status" required>
              <option value="Present">Present</option>
              <option value="Absent">Absent</option>
              <option value="Late">Late</option>
              <option value="On Leave">On Leave</option>
            </select>
            <textarea name="remarks" placeholder="Remarks (optional)" rows="3"></textarea>
            <button type="submit" class="btn primary">Add Record</button>
          </form>
        </div>
      </div>

      <!-- View/Edit Attendance Records -->
      <div class="card" style="grid-column: 1 / -1;">
        <div class="card-head"><strong>Attendance Records</strong></div>
        <div class="card-body">
          <form id="attendance-search-form" method="get" action="" style="margin:0 0 12px; display:flex; gap:8px; flex-wrap:wrap;">
            <input type="number" name="a_employee_id" value="<?= htmlspecialchars($a_employee_id ?? ''); ?>" placeholder="Search by Employee ID" style="width:200px;" />
            <input type="text" name="a_employee_name" value="<?= htmlspecialchars($a_employee_name ?? ''); ?>" placeholder="Filter by Employee Name" style="flex:1; min-width:200px;" />
            <input type="date" name="a_date_from" value="<?= htmlspecialchars($a_date_from ?? ''); ?>" placeholder="From Date" />
            <input type="date" name="a_date_to" value="<?= htmlspecialchars($a_date_to ?? ''); ?>" placeholder="To Date" />
            <button class="btn primary" type="submit">Search</button>
            <a class="btn" id="attendance-search-clear" href="employees.php">Clear</a>
          </form>
          <div style="overflow-x:auto;">
            <table>
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Employee</th>
                  <th>Date</th>
                  <th>Check In</th>
                  <th>Check Out</th>
                  <th>Status</th>
                  <th>Remarks</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($attendance_list)) { ?>
                  <tr><td colspan="8">No attendance records found.</td></tr>
                <?php } else { foreach ($attendance_list as $att) { ?>
                  <tr>
                    <form method="post" action="">
                      <input type="hidden" name="form_type" value="update_attendance" />
                      <input type="hidden" name="id" value="<?= (int)$att['id']; ?>" />
                      <td><?= (int)$att['id']; ?></td>
                      <td><?= htmlspecialchars($att['employee_name'] ?? 'Unknown'); ?> (ID: <?= (int)$att['employee_id']; ?>)</td>
                      <td><?= htmlspecialchars($att['date']); ?></td>
                      <td><input type="time" name="check_in" value="<?= htmlspecialchars((string)($att['check_in'] ?? '')); ?>" style="width:120px;" /></td>
                      <td><input type="time" name="check_out" value="<?= htmlspecialchars((string)($att['check_out'] ?? '')); ?>" style="width:120px;" /></td>
                      <td>
                        <select name="status" style="width:120px;">
                          <option value="Present" <?= ($att['status']==='Present')?'selected':''; ?>>Present</option>
                          <option value="Absent" <?= ($att['status']==='Absent')?'selected':''; ?>>Absent</option>
                          <option value="Late" <?= ($att['status']==='Late')?'selected':''; ?>>Late</option>
                          <option value="On Leave" <?= ($att['status']==='On Leave')?'selected':''; ?>>On Leave</option>
                        </select>
                      </td>
                      <td><input type="text" name="remarks" value="<?= htmlspecialchars((string)($att['remarks'] ?? '')); ?>" placeholder="Remarks" /></td>
                      <td>
                        <div class="row-actions">
                          <button class="btn primary" type="submit">Save</button>
                          </form>
                          <form class="attendance-delete-form" method="post" action="" onsubmit="return confirm('Delete this attendance record?');">
                            <input type="hidden" name="form_type" value="delete_attendance" />
                            <input type="hidden" name="id" value="<?= (int)$att['id']; ?>" />
                            <button class="btn" type="submit" style="border-color:#7f1d1d; background:#3f1d1d; color:#fecaca;">Delete</button>
                          </form>
                        </div>
                      </td>
                  </tr>
                <?php } } ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Attendance Matrix -->
  <section class="tab-content" id="tab-matrix" style="display:none;">
    <div class="cards">
      <div class="card" style="grid-column: 1 / -1;">
        <div class="card-head">
          <strong>Attendance Matrix - <span id="matrix-header-month"><?= date('F Y', strtotime($matrix_month)); ?></span></strong>
          <div style="display: flex; gap: 10px; align-items: center; margin-top: 10px;">
            <label for="matrix-month-select">Select Month:</label>
            <input type="month" id="matrix-month-select" value="<?= htmlspecialchars($matrix_month); ?>" style="padding: 5px;">
            <button class="btn primary" onclick="loadAttendanceMatrix()">Load Matrix</button>
          </div>
        </div>
        <div class="card-body">
          <div id="matrix-container" style="overflow-x: auto;">
            <table id="attendance-matrix-table" style="min-width: 800px;">
              <thead>
                <tr>
                  <th rowspan="2" style="min-width: 150px; position: sticky; left: 0; background: #1f2937; z-index: 10;">Employee</th>
                  <th rowspan="2" style="min-width: 80px; position: sticky; left: 150px; background: #1f2937; z-index: 10;">Total Days</th>
                  <th rowspan="2" style="min-width: 80px; position: sticky; left: 230px; background: #1f2937; z-index: 10;">Present</th>
                  <th rowspan="2" style="min-width: 80px; position: sticky; left: 310px; background: #1f2937; z-index: 10;">Absent</th>
                  <th rowspan="2" style="min-width: 80px; position: sticky; left: 390px; background: #1f2937; z-index: 10;">Late</th>
                  <th rowspan="2" style="min-width: 80px; position: sticky; left: 470px; background: #1f2937; z-index: 10;">Leave</th>
                  <th colspan="<?= count($matrix_days); ?>" style="text-align: center;" id="days-header">Days of <?= date('F Y', strtotime($matrix_month)); ?></th>
                </tr>
                <tr>
                  <?php foreach ($matrix_days as $day): ?>
                    <th style="min-width: 40px; padding: 8px 4px; text-align: center; font-size: 12px; background: #374151;">
                      <div><?= $day['day']; ?></div>
                      <div style="font-size: 10px; color: #9ca3af;"><?= $day['weekday']; ?></div>
                    </th>
                  <?php endforeach; ?>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($matrix_employees)): ?>
                  <tr><td colspan="<?= 6 + count($matrix_days); ?>" style="text-align: center; padding: 20px;">No active employees found.</td></tr>
                <?php else: ?>
                  <?php foreach ($matrix_employees as $employee): ?>
                    <?php 
                      $emp_id = (int)$employee['id'];
                      $emp_attendance = $attendance_matrix[$emp_id] ?? [];
                      $total_days = count($matrix_days);
                      $present_count = 0;
                      $absent_count = 0;
                      $late_count = 0;
                      $leave_count = 0;
                      
                      foreach ($emp_attendance as $att) {
                        switch ($att['status']) {
                          case 'Present': $present_count++; break;
                          case 'Absent': $absent_count++; break;
                          case 'Late': $late_count++; break;
                          case 'On Leave': $leave_count++; break;
                        }
                      }
                    ?>
                    <tr>
                      <td style="position: sticky; left: 0; background: #1f2937; font-weight: 500; padding: 10px;">
                        <?= htmlspecialchars($employee['name']); ?>
                      </td>
                      <td style="position: sticky; left: 150px; background: #1f2937; text-align: center; font-weight: 500;">
                        <?= $total_days; ?>
                      </td>
                      <td style="position: sticky; left: 230px; background: #1f2937; text-align: center; color: #10b981; font-weight: 500;">
                        <?= $present_count; ?>
                      </td>
                      <td style="position: sticky; left: 310px; background: #1f2937; text-align: center; color: #ef4444; font-weight: 500;">
                        <?= $absent_count; ?>
                      </td>
                      <td style="position: sticky; left: 390px; background: #1f2937; text-align: center; color: #f59e0b; font-weight: 500;">
                        <?= $late_count; ?>
                      </td>
                      <td style="position: sticky; left: 470px; background: #1f2937; text-align: center; color: #8b5cf6; font-weight: 500;">
                        <?= $leave_count; ?>
                      </td>
                      <?php foreach ($matrix_days as $day): ?>
                        <?php 
                          $day_att = $emp_attendance[$day['date']] ?? null;
                          $status_class = '';
                          $status_text = '';
                          $tooltip = '';
                          
                          if ($day_att) {
                            switch ($day_att['status']) {
                              case 'Present':
                                $status_class = 'attendance-present';
                                $status_text = 'P';
                                $tooltip = 'Present' . ($day_att['check_in'] ? ' (In: ' . $day_att['check_in'] . ')' : '');
                                break;
                              case 'Absent':
                                $status_class = 'attendance-absent';
                                $status_text = 'A';
                                $tooltip = 'Absent';
                                break;
                              case 'Late':
                                $status_class = 'attendance-late';
                                $status_text = 'L';
                                $tooltip = 'Late' . ($day_att['check_in'] ? ' (In: ' . $day_att['check_in'] . ')' : '');
                                break;
                              case 'On Leave':
                                $status_class = 'attendance-leave';
                                $status_text = 'L';
                                $tooltip = 'On Leave';
                                break;
                            }
                          } else {
                            $status_class = 'attendance-empty';
                            $status_text = '';
                            $tooltip = 'No record';
                          }
                        ?>
                        <td class="<?= $status_class; ?>" 
                            style="text-align: center; padding: 8px 4px; min-width: 40px; cursor: pointer;"
                            title="<?= $tooltip; ?>"
                            onclick="openAttendanceModal(<?= $emp_id; ?>, '<?= $day['date']; ?>', '<?= $day_att['status'] ?? ''; ?>', '<?= $day_att['check_in'] ?? ''; ?>', '<?= $day_att['check_out'] ?? ''; ?>')">
                          <?= $status_text; ?>
                        </td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Attendance Modal -->
  <div id="attendance-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #1f2937; padding: 20px; border-radius: 10px; min-width: 400px;">
      <h3 style="margin: 0 0 20px; color: #f9fafb;">Update Attendance</h3>
      <form id="attendance-modal-form" method="post" action="">
        <input type="hidden" name="form_type" value="add_attendance" />
        <input type="hidden" id="modal-employee-id" name="employee_id" />
        <input type="hidden" id="modal-date" name="date" />
        
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; color: #d1d5db;">Employee:</label>
          <input type="text" id="modal-employee-name" readonly style="width: 100%; padding: 8px; background: #374151; border: 1px solid #4b5563; color: #f9fafb; border-radius: 5px;" />
        </div>
        
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; color: #d1d5db;">Date:</label>
          <input type="date" id="modal-date-display" readonly style="width: 100%; padding: 8px; background: #374151; border: 1px solid #4b5563; color: #f9fafb; border-radius: 5px;" />
        </div>
        
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; color: #d1d5db;">Check In:</label>
          <input type="time" id="modal-check-in" name="check_in" style="width: 100%; padding: 8px; background: #374151; border: 1px solid #4b5563; color: #f9fafb; border-radius: 5px;" />
        </div>
        
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; color: #d1d5db;">Check Out:</label>
          <input type="time" id="modal-check-out" name="check_out" style="width: 100%; padding: 8px; background: #374151; border: 1px solid #4b5563; color: #f9fafb; border-radius: 5px;" />
        </div>
        
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; color: #d1d5db;">Status:</label>
          <select id="modal-status" name="status" required style="width: 100%; padding: 8px; background: #374151; border: 1px solid #4b5563; color: #f9fafb; border-radius: 5px;">
            <option value="Present">Present</option>
            <option value="Absent">Absent</option>
            <option value="Late">Late</option>
            <option value="On Leave">On Leave</option>
          </select>
        </div>
        
        <div style="margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 5px; color: #d1d5db;">Remarks:</label>
          <textarea id="modal-remarks" name="remarks" rows="3" style="width: 100%; padding: 8px; background: #374151; border: 1px solid #4b5563; color: #f9fafb; border-radius: 5px;"></textarea>
        </div>
        
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
          <button type="button" class="btn" onclick="closeAttendanceModal()" style="background: #6b7280; color: #f9fafb;">Cancel</button>
          <button type="submit" class="btn primary" style="background: #3b82f6; color: #f9fafb;">Save</button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
// Tab switching
const buttons = document.querySelectorAll('.quick-actions button');
const tabs = document.querySelectorAll('.tab-content');

buttons.forEach(btn => {
  btn.addEventListener('click', () => {
    const target = btn.getAttribute('data-tab');
    tabs.forEach(tab => tab.style.display = (tab.id === 'tab-' + target) ? 'block' : 'none');
  });
});

// Escaper for HTML injection safety when injecting strings
function escapeHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

// Search Employee by ID
function searchEmployeeById() {
  const searchId = document.getElementById('search-employee-by-id').value.trim();
  
  if (searchId === '') {
    alert('Please enter an Employee ID');
    return;
  }
  
  const employeesRows = document.querySelectorAll('#employees-tbody tr');
  let found = false;
  
  employeesRows.forEach(row => {
    if (!row.hasAttribute('data-id')) {
      return;
    }
    
    const rowId = row.getAttribute('data-id');
    
    if (rowId === searchId) {
      row.style.display = '';
      found = true;
    } else {
      row.style.display = 'none';
    }
  });
  
  // Show/hide "no results" message
  const tbody = document.getElementById('employees-tbody');
  let noResultsRow = tbody.querySelector('.no-results-row');
  
  if (!found) {
    if (!noResultsRow) {
      noResultsRow = document.createElement('tr');
      noResultsRow.className = 'no-results-row';
      noResultsRow.innerHTML = '<td colspan="9" style="text-align:center; padding:20px;">No employee found with ID ' + searchId + '</td>';
      tbody.appendChild(noResultsRow);
    } else {
      noResultsRow.innerHTML = '<td colspan="9" style="text-align:center; padding:20px;">No employee found with ID ' + searchId + '</td>';
    }
    noResultsRow.style.display = '';
  } else if (noResultsRow) {
    noResultsRow.style.display = 'none';
  }
  
  // Clear real-time filters when searching by ID
  document.getElementById('filter-name').value = '';
  document.getElementById('filter-email').value = '';
  document.getElementById('filter-phone').value = '';
  const fs = document.getElementById('filter-status');
  if (fs) { fs.value = ''; }
}

function clearEmployeeSearch() {
  document.getElementById('search-employee-by-id').value = '';
  document.getElementById('filter-name').value = '';
  document.getElementById('filter-email').value = '';
  document.getElementById('filter-phone').value = '';
  const fs2 = document.getElementById('filter-status');
  if (fs2) { fs2.value = ''; }
  
  const employeesRows = document.querySelectorAll('#employees-tbody tr');
  employeesRows.forEach(row => {
    if (row.hasAttribute('data-id')) {
      row.style.display = '';
    }
  });
  
  const tbody = document.getElementById('employees-tbody');
  const noResultsRow = tbody.querySelector('.no-results-row');
  if (noResultsRow) {
    noResultsRow.style.display = 'none';
  }
}

// Real-time employee filtering
const filterName = document.getElementById('filter-name');
const filterEmail = document.getElementById('filter-email');
const filterPhone = document.getElementById('filter-phone');
const filterStatus = document.getElementById('filter-status');

function filterEmployees() {
  const nameValue = filterName.value.toLowerCase().trim();
  const emailValue = filterEmail.value.toLowerCase().trim();
  const phoneValue = filterPhone.value.trim();
  const statusValue = (filterStatus ? filterStatus.value.toLowerCase().trim() : '');
  
  // Clear ID search when using filters
  document.getElementById('search-employee-by-id').value = '';
  
  let visibleCount = 0;
  const employeesRows = document.querySelectorAll('#employees-tbody tr');
  
  employeesRows.forEach(row => {
    // Skip if it's the "no employees" message row
    if (!row.hasAttribute('data-id')) {
      return;
    }
    
    const rowName = row.getAttribute('data-name') || '';
    const rowEmail = row.getAttribute('data-email') || '';
    const rowPhone = row.getAttribute('data-phone') || '';
    const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();
    
    const nameMatch = nameValue === '' || rowName.includes(nameValue);
    const emailMatch = emailValue === '' || rowEmail.includes(emailValue);
    const phoneMatch = phoneValue === '' || rowPhone.includes(phoneValue);
    const statusMatch = statusValue === '' || rowStatus === statusValue;
    
    if (nameMatch && emailMatch && phoneMatch && statusMatch) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });
  
  // Show/hide "no results" message
  const tbody = document.getElementById('employees-tbody');
  let noResultsRow = tbody.querySelector('.no-results-row');
  
  if (visibleCount === 0 && employeesRows.length > 0) {
    if (!noResultsRow) {
      noResultsRow = document.createElement('tr');
      noResultsRow.className = 'no-results-row';
      noResultsRow.innerHTML = '<td colspan="9" style="text-align:center; padding:20px;">No employees match your filters.</td>';
      tbody.appendChild(noResultsRow);
    }
    noResultsRow.style.display = '';
  } else if (noResultsRow) {
    noResultsRow.style.display = 'none';
  }
}

// Add event listeners for real-time filtering
if (filterName && filterEmail && filterPhone) {
  filterName.addEventListener('input', filterEmployees);
  filterEmail.addEventListener('input', filterEmployees);
  filterPhone.addEventListener('input', filterEmployees);
  if (filterStatus) {
    filterStatus.addEventListener('change', filterEmployees);
  }
}

// Prevent page reload on Enter for ID search and trigger search
const searchIdInput = document.getElementById('search-employee-by-id');
if (searchIdInput) {
  searchIdInput.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
      e.preventDefault();
      searchEmployeeById();
    }
  });
}

// AJAX helper for POSTing forms
async function postFormData(url, formData) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
    body: formData
  });
  const ct = res.headers.get('Content-Type') || '';
  if (ct.includes('application/json')) {
    return res.json();
  }
  return { success: res.ok };
}

// Add Employee via AJAX
const addEmployeeForm = document.getElementById('add-employee-form');
const addEmployeeMsg = document.getElementById('add-employee-message');
if (addEmployeeForm && addEmployeeMsg) {
  addEmployeeForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(addEmployeeForm);
    fd.append('ajax', '1');
    addEmployeeMsg.innerHTML = '';
    try {
      const resp = await postFormData('', fd);
      const ok = !!resp && resp.success;
      const msg = (resp && resp.message) ? resp.message : (ok ? 'Employee added successfully.' : 'Failed to add employee');
      addEmployeeMsg.innerHTML = `<div class="flash-msg ${ok ? 'success' : 'error'}" style="padding:10px 12px; border:1px solid ${ok ? '#065f46' : '#7f1d1d'}; background:${ok ? '#064e3b' : '#3f1d1d'}; color:#fff; border-radius:10px; font-size:14px;">${msg}</div>`;
      if (ok) {
        addEmployeeForm.reset();
        // auto dismiss after 3s
        setTimeout(() => { addEmployeeMsg.innerHTML = ''; }, 3000);
    // Append to employees edit table
    const tbody = document.getElementById('employees-tbody');
    if (tbody && resp.employee) {
      const e = resp.employee;
      const tr = document.createElement('tr');
      tr.setAttribute('data-id', String(e.id));
      tr.setAttribute('data-name', (e.name || '').toLowerCase());
      tr.setAttribute('data-email', (e.email || '').toLowerCase());
      tr.setAttribute('data-phone', e.phone || '');
      tr.setAttribute('data-status', (e.status || '').toLowerCase());
      tr.innerHTML = `
        <form method=\"post\" action=\"\">
          <input type=\"hidden\" name=\"form_type\" value=\"update_employee\" />
          <input type=\"hidden\" name=\"id\" value=\"${e.id}\" />
          <td>${e.id}</td>
          <td><input type=\"text\" name=\"name\" value=\"${escapeHtml(e.name || '')}\" /></td>
          <td><input type=\"email\" name=\"email\" value=\"${escapeHtml(e.email || '')}\" /></td>
          <td><input type=\"text\" name=\"phone\" value=\"${escapeHtml(e.phone || '')}\" /></td>
          <td>
            <select name=\"role\">
              ${['Manager','Delivery Person','Ware House Staff','Sales Person'].map(r => `<option value=\"${r}\" ${e.role===r?'selected':''}>${r}</option>`).join('')}
            </select>
          </td>
          <td><input type=\"number\" step=\"0.01\" min=\"0\" name=\"salary\" value=\"${e.salary != null ? String(e.salary) : ''}\" /></td>
          <td><input type=\"date\" name=\"hire_date\" value=\"${e.hire_date || ''}\" /></td>
          <td>
            <select name=\"status\">
              <option value=\"active\" ${e.status==='active'?'selected':''}>Active</option>
              <option value=\"inactive\" ${e.status==='inactive'?'selected':''}>Inactive</option>
            </select>
          </td>
          <td>
            <div class=\"row-actions\">
              <button class=\"btn primary\" type=\"submit\">Save</button>
              </form>
              <form class=\"employee-delete-form\" method=\"post\" action=\"\" onsubmit=\"return confirm('Remove this employee? This cannot be undone.');\">
                <input type=\"hidden\" name=\"form_type\" value=\"delete_employee\" />
                <input type=\"hidden\" name=\"id\" value=\"${e.id}\" />
                <button class=\"btn\" type=\"submit\" style=\"border-color:#7f1d1d; background:#3f1d1d; color:#fecaca;\">Remove</button>
              </form>
            </div>
          </td>
      `;
      tbody.appendChild(tr);
    }
    // Append to employee dropdown in attendance add form
    const empSelect = document.querySelector('form#add-attendance-form select[name="employee_id"]');
    if (empSelect && resp.employee) {
      const opt = document.createElement('option');
      opt.value = String(resp.employee.id);
      opt.textContent = `${resp.employee.name} (ID: ${resp.employee.id})`;
      empSelect.appendChild(opt);
    }
      }
    } catch (err) {
      addEmployeeMsg.innerHTML = `<div style="padding:10px 12px; border:1px solid #7f1d1d; background:#3f1d1d; color:#fff; border-radius:10px; font-size:14px;">Request failed</div>`;
    }
  });
}

// Add Attendance via AJAX
const addAttendanceForm = document.getElementById('add-attendance-form');
const addAttendanceMsg = document.getElementById('add-attendance-message');
if (addAttendanceForm && addAttendanceMsg) {
  addAttendanceForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(addAttendanceForm);
    fd.append('ajax', '1');
    addAttendanceMsg.innerHTML = '';
    try {
      const resp = await postFormData('', fd);
      const ok = !!resp && resp.success;
      const msg = (resp && resp.message) ? resp.message : (ok ? 'Attendance record added successfully.' : 'Failed to add attendance');
      addAttendanceMsg.innerHTML = `<div class="flash-msg ${ok ? 'success' : 'error'}" style="padding:10px 12px; border:1px solid ${ok ? '#065f46' : '#7f1d1d'}; background:${ok ? '#064e3b' : '#3f1d1d'}; color:#fff; border-radius:10px; font-size:14px;">${msg}</div>`;
      if (ok) {
        addAttendanceForm.reset();
        // auto dismiss after 3s
        setTimeout(() => { addAttendanceMsg.innerHTML = ''; }, 3000);
    // Append to attendance table
    const tbody = document.querySelector('#tab-attendance tbody');
    if (tbody && resp.attendance) {
      const a = resp.attendance;
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <form method=\"post\" action=\"\">\n          <input type=\"hidden\" name=\"form_type\" value=\"update_attendance\" />\n          <input type=\"hidden\" name=\"id\" value=\"${a.id}\" />\n          <td>${a.id}</td>\n          <td>${escapeHtml(a.employee_name)} (ID: ${a.employee_id})</td>\n          <td>${escapeHtml(a.date)}</td>\n          <td><input type=\"time\" name=\"check_in\" value=\"${a.check_in || ''}\" style=\"width:120px;\" /></td>\n          <td><input type=\"time\" name=\"check_out\" value=\"${a.check_out || ''}\" style=\"width:120px;\" /></td>\n          <td>\n            <select name=\"status\" style=\"width:120px;\">\n              ${['Present','Absent','Late','On Leave'].map(s => `<option value=\\\"${s}\\\" ${a.status===s?'selected':''}>${s}</option>`).join('')}\n            </select>\n          </td>\n          <td><input type=\"text\" name=\"remarks\" value=\"${escapeHtml(a.remarks || '')}\" placeholder=\"Remarks\" /></td>\n          <td>\n            <div class=\"row-actions\">\n              <button class=\"btn primary\" type=\"submit\">Save</button>\n              </form>\n              <form class=\"attendance-delete-form\" method=\"post\" action=\"\" onsubmit=\"return confirm('Delete this attendance record?');\">\n                <input type=\"hidden\" name=\"form_type\" value=\"delete_attendance\" />\n                <input type=\"hidden\" name=\"id\" value=\"${a.id}\" />\n                <button class=\"btn\" type=\"submit\" style=\"border-color:#7f1d1d; background:#3f1d1d; color:#fecaca;\">Delete</button>\n              </form>\n            </div>\n          </td>\n      `;
      tbody.prepend(tr);
    }
      }
    } catch (err) {
      addAttendanceMsg.innerHTML = `<div style=\"padding:10px 12px; border:1px solid #7f1d1d; background:#3f1d1d; color:#fff; border-radius:10px; font-size:14px;\">Request failed</div>`;
    }
  });
}

// Intercept deletes to avoid page reload and update UI live
document.addEventListener('submit', async (e) => {
  const form = e.target;
  if (!(form instanceof HTMLFormElement)) return;
  const isEmpDelete = form.classList.contains('employee-delete-form');
  const isAttDelete = form.classList.contains('attendance-delete-form');
  if (!isEmpDelete && !isAttDelete) return;
  // respect confirm already on form's onsubmit
  e.preventDefault();
  const fd = new FormData(form);
  fd.append('ajax', '1');
  try {
    const resp = await postFormData('', fd);
    if (resp && resp.success) {
      // remove the table row
      let row = form.closest('tr');
      if (row) row.remove();
      // show a temporary success toast
      const toast = document.createElement('div');
      toast.className = 'flash-msg success';
      toast.style.cssText = 'position:fixed; right:16px; bottom:16px; padding:10px 12px; border:1px solid #065f46; background:#064e3b; color:#fff; border-radius:10px; font-size:14px; z-index:9999;';
      toast.textContent = isEmpDelete ? 'Employee deleted.' : 'Attendance record deleted.';
      document.body.appendChild(toast);
      setTimeout(() => { if (toast.parentElement) toast.parentElement.removeChild(toast); }, 3000);
      // also remove from attendance employee select if employee deleted
      if (isEmpDelete) {
        const id = fd.get('id');
        const empSelect = document.querySelector('form#add-attendance-form select[name="employee_id"]');
        if (empSelect && id) {
          const opt = empSelect.querySelector(`option[value="${CSS.escape(String(id))}"]`);
          if (opt) opt.remove();
        }
      }
    } else {
      alert((resp && resp.message) ? resp.message : 'Delete failed');
    }
  } catch (err) {
    alert('Request failed');
  }
});

// AJAX attendance search: prevent reload and render results
const attSearchForm = document.getElementById('attendance-search-form');
if (attSearchForm) {
  attSearchForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const params = new URLSearchParams(new FormData(attSearchForm));
    params.append('ajax', '1');
    const url = `?${params.toString()}`;
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      if (!data || !data.success) throw new Error('Search failed');
      const tbody = attSearchForm.parentElement?.querySelector('tbody');
      if (!tbody) return;
      tbody.innerHTML = '';
      if (!data.attendance || data.attendance.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="8">No attendance records found.</td>';
        tbody.appendChild(tr);
        return;
      }
      data.attendance.forEach((a) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <form method=\"post\" action=\"\">\n            <input type=\"hidden\" name=\"form_type\" value=\"update_attendance\" />\n            <input type=\"hidden\" name=\"id\" value=\"${a.id}\" />\n            <td>${a.id}</td>\n            <td>${escapeHtml(a.employee_name || 'Unknown')} (ID: ${a.employee_id})</td>\n            <td>${escapeHtml(a.date)}</td>\n            <td><input type=\"time\" name=\"check_in\" value=\"${a.check_in || ''}\" style=\"width:120px;\" /></td>\n            <td><input type=\"time\" name=\"check_out\" value=\"${a.check_out || ''}\" style=\"width:120px;\" /></td>\n            <td>\n              <select name=\"status\" style=\"width:120px;\">\n                ${['Present','Absent','Late','On Leave'].map(s => `<option value=\\\"${s}\\\" ${a.status===s?'selected':''}>${s}</option>`).join('')}\n              </select>\n            </td>\n            <td><input type=\"text\" name=\"remarks\" value=\"${escapeHtml(a.remarks || '')}\" placeholder=\"Remarks\" /></td>\n            <td>\n              <div class=\"row-actions\">\n                <button class=\"btn primary\" type=\"submit\">Save</button>\n                </form>\n                <form class=\"attendance-delete-form\" method=\"post\" action=\"\" onsubmit=\"return confirm('Delete this attendance record?');\">\n                  <input type=\"hidden\" name=\"form_type\" value=\"delete_attendance\" />\n                  <input type=\"hidden\" name=\"id\" value=\"${a.id}\" />\n                  <button class=\"btn\" type=\"submit\" style=\"border-color:#7f1d1d; background:#3f1d1d; color:#fecaca;\">Delete</button>\n                </form>\n              </div>\n            </td>\n        `;
        tbody.appendChild(tr);
      });
    } catch (err) {
      alert('Search failed');
    }
  });
}

// Handle Clear: reset fields and fetch all via AJAX
const attSearchClear = document.getElementById('attendance-search-clear');
if (attSearchClear && attSearchForm) {
  attSearchClear.addEventListener('click', async (e) => {
    e.preventDefault();
    const inputs = attSearchForm.querySelectorAll('input[name="a_employee_id"], input[name="a_employee_name"], input[name="a_date_from"], input[name="a_date_to"]');
    inputs.forEach(i => { i.value = ''; });
    const params = new URLSearchParams();
    params.append('ajax', '1');
    try {
      const res = await fetch(`?${params.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const data = await res.json();
      if (!data || !data.success) throw new Error('Fetch failed');
      const tbody = attSearchForm.parentElement?.querySelector('tbody');
      if (!tbody) return;
      tbody.innerHTML = '';
      if (!data.attendance || data.attendance.length === 0) {
        const tr = document.createElement('tr');
        tr.innerHTML = '<td colspan="8">No attendance records found.</td>';
        tbody.appendChild(tr);
        return;
      }
      data.attendance.forEach((a) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <form method=\"post\" action=\"\">\n            <input type=\"hidden\" name=\"form_type\" value=\"update_attendance\" />\n            <input type=\"hidden\" name=\"id\" value=\"${a.id}\" />\n            <td>${a.id}</td>\n            <td>${escapeHtml(a.employee_name || 'Unknown')} (ID: ${a.employee_id})</td>\n            <td>${escapeHtml(a.date)}</td>\n            <td><input type=\"time\" name=\"check_in\" value=\"${a.check_in || ''}\" style=\"width:120px;\" /></td>\n            <td><input type=\"time\" name=\"check_out\" value=\"${a.check_out || ''}\" style=\"width:120px;\" /></td>\n            <td>\n              <select name=\"status\" style=\"width:120px;\">\n                ${['Present','Absent','Late','On Leave'].map(s => `<option value=\\\"${s}\\\" ${a.status===s?'selected':''}>${s}</option>`).join('')}\n              </select>\n            </td>\n            <td><input type=\"text\" name=\"remarks\" value=\"${escapeHtml(a.remarks || '')}\" placeholder=\"Remarks\" /></td>\n            <td>\n              <div class=\"row-actions\">\n                <button class=\"btn primary\" type=\"submit\">Save</button>\n                </form>\n                <form class=\"attendance-delete-form\" method=\"post\" action=\"\" onsubmit=\"return confirm('Delete this attendance record?');\">\n                  <input type=\"hidden\" name=\"form_type\" value=\"delete_attendance\" />\n                  <input type=\"hidden\" name=\"id\" value=\"${a.id}\" />\n                  <button class=\"btn\" type=\"submit\" style=\"border-color:#7f1d1d; background:#3f1d1d; color:#fecaca;\">Delete</button>\n                </form>\n              </div>\n            </td>\n        `;
        tbody.appendChild(tr);
      });
    } catch (err) {
      alert('Fetch failed');
    }
  });
}

// Attendance Matrix Functions
function loadAttendanceMatrix() {
  const month = document.getElementById('matrix-month-select').value;
  if (!month) {
    alert('Please select a month');
    return;
  }
  
  // Update the headers immediately
  updateMonthHeaders(month);
  
  const params = new URLSearchParams();
  params.append('ajax', '1');
  params.append('matrix_month', month);
  
  fetch(`?${params.toString()}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      renderAttendanceMatrix(data);
    } else {
      alert('Failed to load attendance matrix');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to load attendance matrix');
  });
}

function updateMonthHeaders(month) {
  // Convert month string (YYYY-MM) to readable format
  const date = new Date(month + '-01');
  const monthYear = date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
  
  // Update main header
  const mainHeader = document.getElementById('matrix-header-month');
  if (mainHeader) {
    mainHeader.textContent = monthYear;
  }
  
  // Update table header
  const daysHeader = document.getElementById('days-header');
  if (daysHeader) {
    daysHeader.textContent = 'Days of ' + monthYear;
  }
}

function renderAttendanceMatrix(data) {
  const table = document.getElementById('attendance-matrix-table');
  const tbody = table.querySelector('tbody');
  
  // Clear existing content
  tbody.innerHTML = '';
  
  if (!data.employees || data.employees.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td colspan="${6 + data.days.length}" style="text-align: center; padding: 20px;">No active employees found.</td>`;
    tbody.appendChild(tr);
    return;
  }
  
  // Render each employee row
  data.employees.forEach(employee => {
    const empId = employee.id;
    const empAttendance = data.attendance[empId] || {};
    
    // Calculate counts
    let presentCount = 0, absentCount = 0, lateCount = 0, leaveCount = 0;
    Object.values(empAttendance).forEach(att => {
      switch (att.status) {
        case 'Present': presentCount++; break;
        case 'Absent': absentCount++; break;
        case 'Late': lateCount++; break;
        case 'On Leave': leaveCount++; break;
      }
    });
    
    const tr = document.createElement('tr');
    
    // Employee name cell
    const nameCell = document.createElement('td');
    nameCell.style.cssText = 'position: sticky; left: 0; background: #1f2937; font-weight: 500; padding: 10px;';
    nameCell.textContent = employee.name;
    tr.appendChild(nameCell);
    
    // Summary cells
    const totalDaysCell = document.createElement('td');
    totalDaysCell.style.cssText = 'position: sticky; left: 150px; background: #1f2937; text-align: center; font-weight: 500;';
    totalDaysCell.textContent = data.days.length;
    tr.appendChild(totalDaysCell);
    
    const presentCell = document.createElement('td');
    presentCell.style.cssText = 'position: sticky; left: 230px; background: #1f2937; text-align: center; color: #10b981; font-weight: 500;';
    presentCell.textContent = presentCount;
    tr.appendChild(presentCell);
    
    const absentCell = document.createElement('td');
    absentCell.style.cssText = 'position: sticky; left: 310px; background: #1f2937; text-align: center; color: #ef4444; font-weight: 500;';
    absentCell.textContent = absentCount;
    tr.appendChild(absentCell);
    
    const lateCell = document.createElement('td');
    lateCell.style.cssText = 'position: sticky; left: 390px; background: #1f2937; text-align: center; color: #f59e0b; font-weight: 500;';
    lateCell.textContent = lateCount;
    tr.appendChild(lateCell);
    
    const leaveCell = document.createElement('td');
    leaveCell.style.cssText = 'position: sticky; left: 470px; background: #1f2937; text-align: center; color: #8b5cf6; font-weight: 500;';
    leaveCell.textContent = leaveCount;
    tr.appendChild(leaveCell);
    
    // Day cells
    data.days.forEach(day => {
      const dayAtt = empAttendance[day.date] || null;
      const dayCell = document.createElement('td');
      
      let statusClass = 'attendance-empty';
      let statusText = '';
      let tooltip = 'No record';
      
      if (dayAtt) {
        switch (dayAtt.status) {
          case 'Present':
            statusClass = 'attendance-present';
            statusText = 'P';
            tooltip = 'Present' + (dayAtt.check_in ? ' (In: ' + dayAtt.check_in + ')' : '');
            break;
          case 'Absent':
            statusClass = 'attendance-absent';
            statusText = 'A';
            tooltip = 'Absent';
            break;
          case 'Late':
            statusClass = 'attendance-late';
            statusText = 'L';
            tooltip = 'Late' + (dayAtt.check_in ? ' (In: ' + dayAtt.check_in + ')' : '');
            break;
          case 'On Leave':
            statusClass = 'attendance-leave';
            statusText = 'L';
            tooltip = 'On Leave';
            break;
        }
      }
      
      dayCell.className = statusClass;
      dayCell.style.cssText = 'text-align: center; padding: 8px 4px; min-width: 40px; cursor: pointer;';
      dayCell.title = tooltip;
      dayCell.textContent = statusText;
      dayCell.onclick = () => openAttendanceModal(
        empId, 
        day.date, 
        dayAtt ? dayAtt.status : '', 
        dayAtt ? dayAtt.check_in : '', 
        dayAtt ? dayAtt.check_out : ''
      );
      
      tr.appendChild(dayCell);
    });
    
    tbody.appendChild(tr);
  });
}

function openAttendanceModal(employeeId, date, status, checkIn, checkOut) {
  const modal = document.getElementById('attendance-modal');
  const form = document.getElementById('attendance-modal-form');
  
  // Find employee name
  const employeeName = document.querySelector(`#attendance-matrix-table td[onclick*="${employeeId}"]`)?.textContent || 'Unknown';
  
  // Populate form
  document.getElementById('modal-employee-id').value = employeeId;
  document.getElementById('modal-employee-name').value = employeeName;
  document.getElementById('modal-date').value = date;
  document.getElementById('modal-date-display').value = date;
  document.getElementById('modal-check-in').value = checkIn || '';
  document.getElementById('modal-check-out').value = checkOut || '';
  document.getElementById('modal-status').value = status || 'Present';
  document.getElementById('modal-remarks').value = '';
  
  modal.style.display = 'block';
}

function closeAttendanceModal() {
  document.getElementById('attendance-modal').style.display = 'none';
}


// Handle modal form submission
document.getElementById('attendance-modal-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const formData = new FormData(this);
  formData.append('ajax', '1');
  
  try {
    const response = await fetch('', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    
    if (result.success) {
      closeAttendanceModal();
      loadAttendanceMatrix(); // Refresh the matrix
      
      // Show success message
      const toast = document.createElement('div');
      toast.className = 'flash-msg success';
      toast.style.cssText = 'position:fixed; right:16px; bottom:16px; padding:10px 12px; border:1px solid #065f46; background:#064e3b; color:#fff; border-radius:10px; font-size:14px; z-index:9999;';
      toast.textContent = 'Attendance record updated successfully.';
      document.body.appendChild(toast);
      setTimeout(() => { if (toast.parentElement) toast.parentElement.removeChild(toast); }, 3000);
    } else {
      alert(result.message || 'Failed to update attendance record');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('Failed to update attendance record');
  }
});

// Close modal when clicking outside
document.getElementById('attendance-modal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeAttendanceModal();
  }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') {
    closeAttendanceModal();
  }
});

// Auto-update headers when month selector changes
document.addEventListener('DOMContentLoaded', function() {
  const monthSelector = document.getElementById('matrix-month-select');
  if (monthSelector) {
    monthSelector.addEventListener('change', function() {
      updateMonthHeaders(this.value);
    });
  }
});
</script>

  </body>
</html>
