<h2>Employee Attendance</h2>
<form method="get" action="">
  <label
    >Date: <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>"
  /></label>
  <button type="submit">Filter</button>
</form>

<table border="1" cellpadding="6">
  <tr>
    <th>Employee</th>
    <th>Check-In</th>
    <th>Check-Out</th>
    <th>Status</th>
    <th>Remarks</th>
  </tr>
  <?php
  $date = $_GET['date'] ?? date('Y-m-d');
  $result = $conn->query(" SELECT e.full_name, a.check_in, a.check_out,
  a.status, a.remarks FROM attendance a JOIN employees e ON e.id = a.employee_id
  WHERE a.date = '$date' "); while ($row = $result->fetch_assoc()) { echo "
  <tr>
    <td>{$row['full_name']}</td>
    <td>{$row['check_in']}</td>
    <td>{$row['check_out']}</td>
    <td>{$row['status']}</td>
    <td>{$row['remarks']}</td>
  </tr>
  "; } ?>
</table>
