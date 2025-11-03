<?php
session_start();
require_once __DIR__ . '/../config/supabase.php';

// ✅ Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Fetch user profile
list($p_status, $profile_data) = supabase_request('profiles?id=eq.' . $user_id, 'GET');
if ($p_status === 200 && !empty($profile_data)) {
    $profile = $profile_data[0];
    $user_role = $profile['role'] ?? 'User';
    $user_name = $profile['name'] ?? 'Unknown';
} else {
    $user_role = 'User';
    $user_name = 'Unknown';
}

// ✅ Fetch daily reports (role-based)
$select = 'id,report_date,activity,notes,photo_url,created_at,location_id,timestamp,' .
          'profiles(name,department,position,role),' .
          'location:location_id(locations,address,email)';

$endpoint = ($user_role === 'Admin')
    ? "daily_reports?select=$select"
    : "daily_reports?select=$select&user_id=eq.$user_id";

// list($status, $reports) = supabase_request($endpoint, 'GET');

// === Pagination setup ===
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page  = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// === Count total reports ===
$count_endpoint = ($user_role === 'Admin')
    ? "daily_reports?select=id"
    : "daily_reports?select=id&user_id=eq.$user_id";

list($count_status, $count_data) = supabase_request($count_endpoint, 'GET');
$total_records = ($count_status === 200 && !empty($count_data)) ? count($count_data) : 0;
$total_pages = max(1, ceil($total_records / $limit));

// ✅ Sort by latest date first (or creation time if available)
$endpoint .= "&order=created_at.desc&limit=$limit&offset=$offset";
list($status, $reports) = supabase_request($endpoint, 'GET');


$page_title = 'View Reports';
$page_heading = 'Daily Reports';
include 'partials/header.php';
?>



<style>
.table-wrapper {
  width: 100%;
  overflow-x: auto;
  background: transparent;
}

table {
  width: 100%;
  border-collapse: collapse;
  table-layout: auto;
  background: linear-gradient(145deg, #101a2e, #0e1624);
  margin-top: 20px;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.4);
}

th, td {
  border: 1px solid rgba(50, 70, 110, 0.5);
  padding: 10px 12px;
  text-align: left;
  vertical-align: top;
  white-space: normal;
  word-break: break-word;
  font-size: 14px;
  color: #d1d9ec;
}

th {
  background-color: rgba(30, 45, 70, 0.9);
  color: #9bb3e6;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  border-bottom: 1px solid rgba(70, 100, 150, 0.5);
}

tr:nth-child(even) {
  background-color: rgba(20, 30, 50, 0.8);
}

tr:nth-child(odd) {
  background-color: rgba(15, 25, 45, 0.8);
}

tr:hover {
  background-color: rgba(45, 117, 255, 0.15);
  transition: background 0.2s ease;
}

.photo-container img {
  width: 85px;
  height: auto;
  border-radius: 5px;
  margin: 3px;
  cursor: pointer;
  transition: transform 0.15s, box-shadow 0.2s;
  border: 1px solid rgba(45, 117, 255, 0.25);
}
.photo-container img:hover {
  transform: scale(1.08);
  box-shadow: 0 0 6px rgba(74, 168, 255, 0.5);
}

/* --- Buttons --- */
.action-btn {
  display: inline-block;
  padding: 5px 10px;
  border-radius: 6px;
  text-decoration: none;
  color: #fff;
  font-size: 13px;
  font-weight: 600;
  margin-right: 6px;
  transition: all 0.2s ease;
  letter-spacing: 0.3px;
  border: none;
}

.edit-btn {
  background: linear-gradient(90deg, #2d75ff, #4aa8ff);
  box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

.delete-btn {
  background: linear-gradient(90deg, #e05656, #ff6b6b);
  box-shadow: 0 2px 5px rgba(0,0,0,0.3);
}

.edit-btn:hover, .delete-btn:hover {
  filter: brightness(1.15);
  transform: translateY(-2px);
}

/* --- Search Bar --- */
#searchReports {
  background: #0f1a2c;
  border: 1px solid #23385f;
  color: #cfd8f3;
  border-radius: 6px;
  padding: 8px 10px;
  margin-bottom: 15px;
  width: 280px;
  font-size: 14px;
  transition: all 0.2s ease;
}

#searchReports:focus {
  border-color: #4aa8ff;
  box-shadow: 0 0 0 2px rgba(74,168,255,0.3);
  outline: none;
}

.pagination {
  margin-top: 20px;
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

<div class="content">
<h2>Daily Reports</h2>
<p><strong>Welcome:</strong> <?= htmlspecialchars($user_name) ?> (<?= htmlspecialchars($user_role) ?>)</p>

<input type="text" id="searchReports" placeholder="🔍 Search reports...">

<div id="reportsTable">
<?php if ($status === 200 && !empty($reports)): ?>
<div class="table-wrapper">
<table>
  <thead>
    <tr>
      <th>User Name</th>
   <!--<th>Department</th>
      <th>Position</th>
      <th>Role</th>  -->
      <th>Location</th>
      <th>Date</th>
	  <th>timestamp</th>
      <th>Activity</th>
      <th>Notes</th>
      <th>Photos</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($reports as $r): ?>
  <tr data-id="<?= htmlspecialchars($r['id']) ?>">
    <td><?= htmlspecialchars($r['profiles']['name'] ?? '-') ?></td>
   <!--  <td><?//= //htmlspecialchars($r['profiles']['department'] ?? '-') ?></td>
    <td><?//= //htmlspecialchars($r['profiles']['position'] ?? '-') ?></td>
    <td><?//= //htmlspecialchars($r['profiles']['role'] ?? '-') ?></td>  -->
    <td><?= htmlspecialchars($r['location']['locations'] ?? '-') ?></td>
    <td><?= htmlspecialchars($r['report_date']) ?></td>
	<td><?= htmlspecialchars($r['timestamp']) ?></td>
    <td><?= nl2br(htmlspecialchars($r['activity'])) ?></td>
    <td><?= nl2br(htmlspecialchars($r['notes'] ?? '')) ?></td>
    <td>
      <div class="photo-container">
      <?php
        $photos = $r['photo_url'];
        if (is_string($photos)) {
          $decoded = json_decode($photos, true);
          if (json_last_error() === JSON_ERROR_NONE) $photos = $decoded;
        }
        if (!empty($photos) && is_array($photos)) {
          foreach ($photos as $p) {
            echo '<img src="../' . htmlspecialchars($p) . '" alt="photo">';
          }
        } else echo '—';
      ?>
      </div>
    </td>
    <td>
      <a href="#" class="action-btn edit-btn" data-id="<?= htmlspecialchars($r['id']) ?>">Update</a>
 <?php if($user_role === 'Admin') {  ?>
 <a href="delete_report.php?id=<?= urlencode($r['id']) ?>" class="action-btn delete-btn"
         onclick="return confirm('Are you sure you want to delete this report?');">Delete</a>
<?php } ?>
		 
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>





<?php else: ?>
<p>No reports found or failed to fetch (HTTP <?= $status ?>)</p>
<?php endif; ?>
</div>


<!-- === Pagination controls === -->
<?php if ($total_pages > 1): ?>
<div class="pagination">
  <?php if ($page > 1): ?>
    <a href="?page=<?= $page - 1 ?>&limit=<?= $limit ?>" class="page-btn">&laquo; Prev</a>
  <?php endif; ?>

  <?php for ($i = 1; $i <= $total_pages; $i++): ?>
    <a href="?page=<?= $i ?>&limit=<?= $limit ?>"
       class="page-btn <?= $i == $page ? 'active' : '' ?>">
       <?= $i ?>
    </a>
  <?php endfor; ?>

  <?php if ($page < $total_pages): ?>
    <a href="?page=<?= $page + 1 ?>&limit=<?= $limit ?>" class="page-btn">Next &raquo;</a>
  <?php endif; ?>
</div>
<?php endif; ?>



</div>

<!-- ===== Edit Modal ===== -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Edit Report</h3>
      <span class="close-btn" onclick="closeModal('editModal')">&times;</span>
    </div>
    <div class="modal-body">
      <form id="editForm">
        <input type="hidden" id="edit_id">
        <label>Date</label>
        <input type="date" id="edit_date" <?= $user_role === 'User' ? 'readonly' : '' ?>>
        <label>Activity</label>
        <textarea id="edit_activity"></textarea>
        <label>Notes</label>
        <textarea id="edit_notes"></textarea>
        <label>Location</label>
        <input type="text" id="edit_location" readonly>
        <?php if ($user_role === 'Admin'): ?>
          <label>Replace Photos</label>
          <input type="file" id="edit_photos" multiple accept="image/*">
        <?php else: ?>
          <label>Existing Photos</label>
          <div id="edit_photos_preview" style="display:flex;flex-wrap:wrap;gap:5px;"></div>
        <?php endif; ?>
        <button type="submit" class="save-btn">Save Changes</button>
        <div class="modal-overlay" id="loadingOverlay">
          <div class="loader"></div>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ===== Image Preview Modal ===== -->
<div id="imageModal" class="modal image-modal" onclick="closeModal('imageModal')">
  <img id="modalImage" src="">
</div>


<script>
// === Modal Controls ===
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// === Photo click preview ===
document.querySelectorAll('.photo-container img').forEach(img => {
  img.addEventListener('click', () => {
    document.getElementById('modalImage').src = img.src;
    openModal('imageModal');
  });
});

// === Edit button click ===
document.querySelectorAll('.edit-btn').forEach(btn => {
  btn.addEventListener('click', async e => {
    e.preventDefault();
    const id = btn.getAttribute('data-id');
    const res = await fetch(`get_report.php?id=${id}`);
    const data = await res.json();
    if (!data) return;

    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_date').value = data.report_date || '';
    document.getElementById('edit_activity').value = data.activity || '';
    document.getElementById('edit_notes').value = data.notes || '';
    document.getElementById('edit_location').value = data.location?.locations || '';

    const preview = document.getElementById('edit_photos_preview');
    if (preview) {
      preview.innerHTML = '';
      if (data.photo_url) {
        const photos = JSON.parse(data.photo_url);
        photos.forEach(p => {
          const img = document.createElement('img');
          img.src = '../' + p;
          img.style.width = '70px';
          img.style.borderRadius = '4px';
          preview.appendChild(img);
        });
      }
    }
    openModal('editModal');
  });
});

// === Save changes ===
document.getElementById('editForm').addEventListener('submit', async e => {
  e.preventDefault();
  const overlay = document.getElementById('loadingOverlay');
  overlay.style.display = 'flex';

  const id = document.getElementById('edit_id').value;
  const payload = {
    report_date: document.getElementById('edit_date').value,
    activity: document.getElementById('edit_activity').value,
    notes: document.getElementById('edit_notes').value,
    location: document.getElementById('edit_location').value
  };

  const res = await fetch(`update_report.php?id=${id}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

  overlay.style.display = 'none';
  if (res.ok) {
    alert('✅ Report updated successfully.');
    location.reload();
  } else {
    alert('❌ Failed to update report.');
  }
});
</script>

<script>
const searchInput = document.getElementById('searchReports');

searchInput.addEventListener('keyup', async () => {
    const q = searchInput.value.trim();
    const response = await fetch('search_reports.php?q=' + encodeURIComponent(q));
    const data = await response.json();

    const tableContainer = document.getElementById('reportsTable');

    if (!data.length) {
        tableContainer.innerHTML = '<p>No reports found.</p>';
        return;
    }

    let html = `
    <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>User Name</th>
        <!--  <th>Department</th>
          <th>Position</th>
          <th>Role</th>  -->
          <th>Location</th>
          <th>Date</th>
		  <th>timestamp</th>
          <th>Activity</th>
          <th>Notes</th>
          <th>Photos</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
    `;

    data.forEach(r => {
        const profile = r.profiles || {};
        const location = r.location || {};
        const photos = (r.photo_url ? JSON.parse(r.photo_url) : []);
        html += `
        <tr data-id="${r.id}">
          <td>${profile.name || '-'}</td>
       <!--   <td>${profile.department || '-'}</td>
          <td>${profile.position || '-'}</td>
          <td>${profile.role || '-'}</td>  -->
          <td>${location.locations || '-'}</td>
          <td>${r.report_date || '-'}</td>
		  <th>${r.timestamp || '-'}</th>
          <td>${r.activity || '-'}</td>
          <td>${r.notes || ''}</td>
          <td>
            <div class="photo-container">
              ${photos && photos.length ? photos.map(p => 
                `<img src="../${p}" alt="photo" style="width:80px;height:auto;border-radius:4px;cursor:pointer;">`
              ).join('') : '—'}
            </div>
          </td>
          <td>
            <a href="#" class="action-btn edit-btn" data-id="${r.id}">Edit</a>
          <?php if($user_role === 'Admin') {  ?>  <a href="delete_report.php?id=${r.id}" class="action-btn delete-btn"
		  onclick="return confirm('Are you sure you want to delete this report?');">Delete</a> <?php }  ?>
          </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    tableContainer.innerHTML = html;

    // Re-attach photo click listeners for preview modal
    document.querySelectorAll('.photo-container img').forEach(img => {
        img.addEventListener('click', () => {
            document.getElementById('modalImage').src = img.src;
            openModal('imageModal');
        });
    });

    // Re-attach edit button listeners
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', async e => {
            e.preventDefault();
            const id = btn.getAttribute('data-id');
            const res = await fetch(`get_report.php?id=${id}`);
            const data = await res.json();
            if (!data) return;

            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_date').value = data.report_date || '';
            document.getElementById('edit_activity').value = data.activity || '';
            document.getElementById('edit_notes').value = data.notes || '';
            document.getElementById('edit_location').value = data.location?.locations || '';

            const preview = document.getElementById('edit_photos_preview');
            if (preview) {
                preview.innerHTML = '';
                if (data.photo_url) {
                    const photos = JSON.parse(data.photo_url);
                    photos.forEach(p => {
                        const img = document.createElement('img');
                        img.src = '../' + p;
                        img.style.width = '70px';
                        img.style.borderRadius = '4px';
                        preview.appendChild(img);
                    });
                }
            }
            openModal('editModal');
        });
    });
});
</script>
<script>
function openModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;

  // Immediately set display
  modal.style.display = "flex";

  // Trigger reflow (this forces browser to calculate before transition)
  void modal.offsetHeight;

  modal.classList.add("active");
}

function closeModal(id) {
  const modal = document.getElementById(id);
  if (!modal) return;

  modal.classList.remove("active");

  // Wait for opacity transition, then hide
  setTimeout(() => {
    modal.style.display = "none";
  }, 250);
}
</script>




<?php include 'partials/footer.php'; ?>
