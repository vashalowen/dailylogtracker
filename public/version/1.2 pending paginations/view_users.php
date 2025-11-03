<?php
require_once __DIR__ . '/../config/supabase.php';

// Fetch all profiles
list($status, $profiles) = supabase_request('profiles?select=*', 'GET');

$page_title = 'View Users';
$page_heading = 'Users List';
include 'partials/header.php';
?>


  <?php if ($status === 200 && !empty($profiles)): ?>
    <table>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Department</th>
        <th>Role</th>
        <th>Created At</th>
      </tr>
      <?php foreach ($profiles as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['id']) ?></td>
          <td><?= htmlspecialchars($p['name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['email'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['department'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['role'] ?? '-') ?></td>
          <td><?= htmlspecialchars($p['created_at'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>No users found or failed to fetch (HTTP <?= $status ?>)</p>
  <?php endif; ?>




<?php include 'partials/footer.php'; ?>