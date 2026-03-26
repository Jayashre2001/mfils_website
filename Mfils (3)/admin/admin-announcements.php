<?php
// admin-announcements.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$pageTitle = 'Manage Announcements – Mfills Admin';
require_once __DIR__ . '/includes/functions.php';
requireLogin();

// Check if user is admin (you'll need to add an is_admin column to users table)
$userId = currentUserId();
$user = getUser($userId);

// Simple admin check - you should implement proper admin authentication
if ($user['username'] !== 'admin') { // Change this to your admin username
    header('Location: /dashboard.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $pdo = db();
        
        // Create new announcement
        if ($_POST['action'] === 'create') {
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $type = $_POST['type'];
            $target_rank = $_POST['target_rank'] ?: null;
            $target_bv_min = $_POST['target_bv_min'] ?: null;
            $expires_at = $_POST['expires_at'] ?: null;
            $priority = (int)$_POST['priority'];
            
            $stmt = $pdo->prepare("
                INSERT INTO announcements (title, content, type, target_rank, target_bv_min, created_by, expires_at, priority)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            if ($stmt->execute([$title, $content, $type, $target_rank, $target_bv_min, $userId, $expires_at, $priority])) {
                $message = "Announcement created successfully!";
            } else {
                $error = "Failed to create announcement.";
            }
        }
        
        // Update announcement
        elseif ($_POST['action'] === 'update') {
            $id = (int)$_POST['id'];
            $title = trim($_POST['title']);
            $content = trim($_POST['content']);
            $type = $_POST['type'];
            $target_rank = $_POST['target_rank'] ?: null;
            $target_bv_min = $_POST['target_bv_min'] ?: null;
            $expires_at = $_POST['expires_at'] ?: null;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $priority = (int)$_POST['priority'];
            
            $stmt = $pdo->prepare("
                UPDATE announcements 
                SET title = ?, content = ?, type = ?, target_rank = ?, target_bv_min = ?, 
                    expires_at = ?, is_active = ?, priority = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$title, $content, $type, $target_rank, $target_bv_min, $expires_at, $is_active, $priority, $id])) {
                $message = "Announcement updated successfully!";
            } else {
                $error = "Failed to update announcement.";
            }
        }
        
        // Delete announcement
        elseif ($_POST['action'] === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
            if ($stmt->execute([$id])) {
                $message = "Announcement deleted successfully!";
            } else {
                $error = "Failed to delete announcement.";
            }
        }
    }
}

// Get all announcements
$pdo = db();
$announcements = $pdo->query("
    SELECT a.*, u.username as creator_name 
    FROM announcements a
    LEFT JOIN users u ON a.created_by = u.id
    ORDER BY a.priority DESC, a.created_at DESC
")->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<style>
.admin-container {
    max-width: 1200px;
    margin: 2rem auto;
    padding: 0 1.5rem;
}
.admin-header {
    background: linear-gradient(135deg, var(--green-dd), var(--green-d));
    padding: 2rem;
    border-radius: 16px;
    margin-bottom: 2rem;
    color: white;
}
.admin-header h1 {
    font-family: 'Cinzel', serif;
    font-size: 2rem;
    margin-bottom: 0.5rem;
}
.announcement-form {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    border: 1px solid var(--ivory-dd);
}
.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}
.form-group {
    margin-bottom: 1.5rem;
}
.form-group.full-width {
    grid-column: span 2;
}
.form-label {
    display: block;
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--green-d);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
}
.form-control {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--ivory-dd);
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.2s;
}
.form-control:focus {
    border-color: var(--gold);
    outline: none;
    box-shadow: 0 0 0 3px rgba(200,146,42,0.1);
}
textarea.form-control {
    min-height: 120px;
    resize: vertical;
}
.btn {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 50px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s;
    font-size: 0.95rem;
}
.btn-primary {
    background: linear-gradient(135deg, var(--green-d), var(--green-m));
    color: white;
}
.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(26,59,34,0.3);
}
.btn-danger {
    background: var(--coral);
    color: white;
}
.btn-danger:hover {
    background: #d43f3a;
}
.announcements-table {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid var(--ivory-dd);
}
.table-header {
    background: linear-gradient(135deg, var(--green-d), var(--green-m));
    color: white;
    padding: 1rem 1.5rem;
    font-weight: 700;
}
table {
    width: 100%;
    border-collapse: collapse;
}
th {
    text-align: left;
    padding: 1rem 1.5rem;
    background: var(--ivory-d);
    font-size: 0.85rem;
    text-transform: uppercase;
    color: var(--green-d);
}
td {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--ivory-dd);
    vertical-align: middle;
}
.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
}
.badge-info { background: #e3f2fd; color: #1976d2; }
.badge-success { background: #e8f5e9; color: #2e7d32; }
.badge-warning { background: #fff3e0; color: #f57c00; }
.badge-urgent { background: #ffebee; color: #c62828; }
.action-btns {
    display: flex;
    gap: 0.5rem;
}
.action-btns button {
    padding: 0.4rem 1rem;
    font-size: 0.8rem;
}
</style>

<div class="admin-container">
    <div class="admin-header">
        <h1>📢 Manage Announcements</h1>
        <p>Create and manage company-wide announcements for Mfills partners</p>
    </div>
    
    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            ✅ <?= e($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
            ❌ <?= e($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Create New Announcement Form -->
    <div class="announcement-form">
        <h2 style="margin-bottom: 1.5rem; color: var(--green-d);">Create New Announcement</h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            
            <div class="form-grid">
                <div class="form-group full-width">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g., New Product Launch">
                </div>
                
                <div class="form-group full-width">
                    <label class="form-label">Content</label>
                    <textarea name="content" class="form-control" required placeholder="Announcement details..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-control">
                        <option value="info">ℹ️ Info</option>
                        <option value="success">✅ Success</option>
                        <option value="warning">⚠️ Warning</option>
                        <option value="urgent">🚨 Urgent</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Priority</label>
                    <input type="number" name="priority" class="form-control" value="0" min="0" max="10">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Target Rank (Optional)</label>
                    <select name="target_rank" class="form-control">
                        <option value="">All Ranks</option>
                        <option value="RSC">Rising Star Club</option>
                        <option value="PC">Prestige Club</option>
                        <option value="GAC">Global Ambassador Club</option>
                        <option value="CC">Chairman Club</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Min. BV Required (Optional)</label>
                    <input type="number" name="target_bv_min" class="form-control" placeholder="e.g., 50000">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Expires At (Optional)</label>
                    <input type="datetime-local" name="expires_at" class="form-control">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">📢 Create Announcement</button>
        </form>
    </div>
    
    <!-- Existing Announcements -->
    <div class="announcements-table">
        <div class="table-header">
            <h3>📋 Existing Announcements</h3>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Target</th>
                    <th>Created</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Priority</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($announcements as $ann): ?>
                <tr>
                    <td>#<?= $ann['id'] ?></td>
                    <td>
                        <strong><?= e($ann['title']) ?></strong><br>
                        <small style="color: #666;"><?= substr(e($ann['content']), 0, 50) ?>...</small>
                    </td>
                    <td>
                        <span class="badge badge-<?= $ann['type'] ?>">
                            <?= ucfirst($ann['type']) ?>
                        </span>
                    </td>
                    <td>
                        <?= $ann['target_rank'] ?? 'All' ?>
                        <?= $ann['target_bv_min'] ? '<br><small>BV ≥ '.number_format($ann['target_bv_min']).'</small>' : '' ?>
                    </td>
                    <td>
                        <?= date('d M Y', strtotime($ann['created_at'])) ?><br>
                        <small>by <?= e($ann['creator_name'] ?? 'System') ?></small>
                    </td>
                    <td><?= $ann['expires_at'] ? date('d M Y', strtotime($ann['expires_at'])) : 'Never' ?></td>
                    <td>
                        <span style="color: <?= $ann['is_active'] ? '#2e7d32' : '#c62828' ?>; font-weight: 700;">
                            <?= $ann['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td><?= $ann['priority'] ?></td>
                    <td>
                        <div class="action-btns">
                            <button onclick="editAnnouncement(<?= htmlspecialchars(json_encode($ann)) ?>)" class="btn btn-primary" style="padding: 0.3rem 1rem;">✏️</button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this announcement?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $ann['id'] ?>">
                                <button type="submit" class="btn btn-danger" style="padding: 0.3rem 1rem;">🗑️</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 2rem; max-width: 600px; width: 90%; max-height: 90vh; overflow-y: auto;">
        <h2 style="margin-bottom: 1.5rem; color: var(--green-d);">Edit Announcement</h2>
        <form method="POST" id="editForm">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            
            <div class="form-group">
                <label class="form-label">Title</label>
                <input type="text" name="title" id="edit_title" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Content</label>
                <textarea name="content" id="edit_content" class="form-control" required rows="4"></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Type</label>
                <select name="type" id="edit_type" class="form-control">
                    <option value="info">ℹ️ Info</option>
                    <option value="success">✅ Success</option>
                    <option value="warning">⚠️ Warning</option>
                    <option value="urgent">🚨 Urgent</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Target Rank</label>
                <select name="target_rank" id="edit_target_rank" class="form-control">
                    <option value="">All Ranks</option>
                    <option value="RSC">Rising Star Club</option>
                    <option value="PC">Prestige Club</option>
                    <option value="GAC">Global Ambassador Club</option>
                    <option value="CC">Chairman Club</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Min. BV Required</label>
                <input type="number" name="target_bv_min" id="edit_target_bv_min" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label">Expires At</label>
                <input type="datetime-local" name="expires_at" id="edit_expires_at" class="form-control">
            </div>
            
            <div class="form-group">
                <label class="form-label">Priority</label>
                <input type="number" name="priority" id="edit_priority" class="form-control" min="0" max="10">
            </div>
            
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1" checked>
                    <span>Active</span>
                </label>
            </div>
            
            <div style="display: flex; gap: 1rem; margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary" style="background: var(--ivory-d); color: var(--ink);">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function editAnnouncement(ann) {
    document.getElementById('edit_id').value = ann.id;
    document.getElementById('edit_title').value = ann.title;
    document.getElementById('edit_content').value = ann.content;
    document.getElementById('edit_type').value = ann.type;
    document.getElementById('edit_target_rank').value = ann.target_rank || '';
    document.getElementById('edit_target_bv_min').value = ann.target_bv_min || '';
    document.getElementById('edit_expires_at').value = ann.expires_at ? ann.expires_at.substring(0,16) : '';
    document.getElementById('edit_priority').value = ann.priority || 0;
    document.getElementById('edit_is_active').checked = ann.is_active == 1;
    
    document.getElementById('editModal').style.display = 'flex';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('editModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>