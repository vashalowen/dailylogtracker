<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ✅ Fetch all locations
list($status, $data) = supabase_request('location?select=*', 'GET');
$locations = ($status === 200) ? $data : [];


$page_title = 'View location';
$page_heading = 'View Location list';
include 'partials/header.php';

?>
 <nav><a href="location_add.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">➕ Add New Location</a> </nav>
<hr>

<?php if ($status === 200 && !empty($locations)): ?>

<input type="text" id="searchLocation" placeholder="🔍 Search locations..." 
       style="padding:5px; width:250px;">
<div id="locationTable">

<table>
  <tr>
    <th>ID</th>
    <th>Location</th>
    <th>Address</th>
    <th>Email</th>
    <th>Actions</th>
  </tr>
  <?php foreach ($locations as $loc): ?>
  <tr>
    <td><?= htmlspecialchars($loc['id']) ?></td>
    <td><?= htmlspecialchars($loc['locations']) ?></td>
    <td><?= htmlspecialchars($loc['address']) ?></td>
    <td><?= htmlspecialchars($loc['email']) ?></td>
    <td>
      <a href="location_edit.php?id=<?= urlencode($loc['id']) ?>">✏️ Edit</a> |
      <a href="location_delete.php?id=<?= urlencode($loc['id']) ?>"
         onclick="return confirm('Delete this location?');">🗑️ Delete</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>


</div> <!-- END OF SEARCH -->
<?php else: ?>
<p>No locations found or failed to load (HTTP <?= $status ?>).</p>
<?php endif; ?>

<script>
const locInput = document.getElementById('searchLocation');
locInput.addEventListener('keyup', async () => {
    const q = locInput.value.trim();
    const res = await fetch('search_locations.php?q=' + encodeURIComponent(q));
    const data = await res.json();

    const container = document.getElementById('locationTable');
    if (!data.length) {
        container.innerHTML = '<p>No locations found.</p>';
        return;
    }

    let html = `<table border="1" cellpadding="6"><tr>
        <th>ID</th><th>Location</th><th>Address</th><th>Email</th></tr>`;

    data.forEach(l => {
        html += `<tr>
            <td>${l.id}</td>
            <td>${l.locations}</td>
            <td>${l.address || '-'}</td>
            <td>${l.email || '-'}</td>
        </tr>`;
    });
    html += '</table>';
    container.innerHTML = html;
});
</script>


</body>
</html>
