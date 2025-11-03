<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// ✅ Pagination setup
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// ✅ Fetch total locations count
list($count_status, $count_data) = supabase_request('location?select=id', 'GET');
$total_records = ($count_status === 200 && !empty($count_data)) ? count($count_data) : 0;
$total_pages = max(1, ceil($total_records / $limit));

// ✅ Fetch paginated locations
list($status, $data) = supabase_request("location?select=*&limit=$limit&offset=$offset", 'GET');
$locations = ($status === 200) ? $data : [];

$page_title = 'View Location';
$page_heading = 'View Location List';
include 'partials/header.php';
?>

<nav>
  <a href="location_add.php">➕ Add New Location</a>
</nav>
<hr>

<?php if ($status === 200 && !empty($locations)): ?>

<input type="text" id="searchLocation" placeholder="🔍 Search locations..." 
       style="padding:5px; width:250px; margin-bottom:10px;">
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


<?php else: ?>
<p>No locations found or failed to load (HTTP <?= $status ?>).</p>
<?php endif; ?>



<!-- === Pagination === -->
<?php if ($total_pages > 1): ?>
<div class="pagination">
  <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>" class="page-btn">&laquo; Prev</a>
  <?php endif; ?>

  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="?page=<?= $i ?>&limit=<?= $limit ?>" class="page-btn <?= $i == $page ? 'active' : '' ?>">
      <?= $i ?>
    </a>
  <?php endfor; ?>

  <?php if ($page < $total_pages): ?>
    <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>" class="page-btn">Next &raquo;</a>
  <?php endif; ?>
</div>
<?php endif; ?>

</div> <!-- END OF SEARCH -->

<style>
.pagination {
  margin-top: 15px;
  text-align: center;
}
.page-btn {
  display: inline-block;
  background: #1b2a46;
  color: #cfd8f3;
  padding: 6px 12px;
  margin: 3px;
  border-radius: 5px;
  text-decoration: none;
  font-weight: 500;
  border: 1px solid #2a3e63;
  transition: 0.2s;
}
.page-btn:hover {
  background: #2d75ff;
  color: #fff;
}
.page-btn.active {
  background: #4aa8ff;
  color: #fff;
  pointer-events: none;
}
</style>

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
        <th>ID</th><th>Location</th><th>Address</th><th>Email</th><th>Actions</th></tr>`;

    data.forEach(l => {
        html += `<tr>
            <td>${l.id}</td>
            <td>${l.locations}</td>
            <td>${l.address || '-'}</td>
            <td>${l.email || '-'}</td>
            <td>
              <a href="location_edit.php?id=${l.id}">✏️ Edit</a> |
              <a href="location_delete.php?id=${l.id}"
                 onclick="return confirm('Delete this location?');">🗑️ Delete</a>
            </td>
        </tr>`;
    });
    html += '</table>';
    container.innerHTML = html;
});
</script>

</body>
</html>
