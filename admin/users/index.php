<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('users.view');
$action = $_GET['action'] ?? 'index';
$pageTitle = 'Users | Admin';

if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    Auth::requirePermission('users.create');
    $result = Auth::register($_POST);
    if ($result['success']) {
        $roleId = intval($_POST['role_id'] ?? 6);
        if ($roleId === 1 && !Auth::isSuperAdmin()) {
            $roleId = 6;
        }
        Database::update('users', ['role_id' => $roleId], 'id = ?', [$result['user_id']]);
        Auth::logActivity('user_create', 'Created user: ' . ($_POST['username'] ?? ''));
        flash_set('success', 'User created.');
    } else { flash_set('error', $result['message']); }
    header('Location: ' . admin_url('users')); exit;
}
if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    Auth::requirePermission('users.edit');
    $id = intval($_POST['id'] ?? 0);
    $target = $id ? User::getById($id) : null;
    if (!$target) {
        flash_set('error', 'User not found.');
    } elseif (Auth::isProtectedUser($target) && !Auth::isSuperAdmin()) {
        flash_set('error', 'Only the superadmin can edit the superadmin account.');
    } else {
        $data = ['display_name'=>sanitize($_POST['display_name']??''),'email'=>sanitize($_POST['email']??'')];
        if (!Auth::isProtectedUser($target) || Auth::isSuperAdmin()) {
            $data['status'] = $_POST['status'] ?? 'active';
            if (!empty($_POST['role_id'])) {
                $roleId = intval($_POST['role_id']);
                if ($roleId === 1 && !Auth::isSuperAdmin()) {
                    $roleId = intval($target['role_id']);
                }
                if (Auth::isProtectedUser($target) && $roleId !== 1 && $id === Auth::id()) {
                    $roleId = 1;
                }
                $data['role_id'] = $roleId;
            }
        }
        Database::update('users', $data, 'id = ?', [$id]);
        if (Auth::isSuperAdmin() && !empty($_POST['new_password'])) {
            $pw = Auth::setPassword($id, (string) $_POST['new_password']);
            if (!$pw['success']) {
                flash_set('error', $pw['message']);
                header('Location: ' . admin_url('users&action=edit&id=' . $id));
                exit;
            }
            Auth::logActivity('user_password', 'Updated password for user #' . $id);
        }
        Auth::logActivity('user_update', 'Updated user #' . $id);
        flash_set('success', 'User updated.');
    }
    header('Location: ' . admin_url('users')); exit;
}
if ($action === 'delete' && verify_csrf($_GET['_token'] ?? '')) {
    Auth::requirePermission('users.delete');
    $id = intval($_GET['id'] ?? 0);
    $target = $id ? User::getById($id) : null;
    if (!$id || $id === Auth::id()) {
        flash_set('error', 'Cannot delete yourself.');
    } elseif (Auth::isProtectedUser($target) && !Auth::isSuperAdmin()) {
        flash_set('error', 'Only the superadmin can delete the superadmin account.');
    } else {
        Database::delete('users', 'id = ?', [$id]);
        Auth::logActivity('user_delete', 'Deleted user #' . $id);
        flash_set('success', 'User deleted.');
    }
    header('Location: ' . admin_url('users')); exit;
}

$users = Database::fetchAll("SELECT u.*, r.name as role_name, r.display_name as role_display FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.created_at DESC LIMIT 50");
$roles = Database::fetchAll("SELECT * FROM roles ORDER BY id");
$editUser = ($action === 'edit' && !empty($_GET['id'])) ? User::getById(intval($_GET['id'])) : null;
if ($editUser && Auth::isProtectedUser($editUser) && !Auth::isSuperAdmin()) {
    flash_set('error', 'Only the superadmin can edit the superadmin account.');
    header('Location: ' . admin_url('users'));
    exit;
}
$assignableRoles = array_values(array_filter($roles, function ($r) {
    return Auth::isSuperAdmin() || intval($r['id']) !== 1;
}));

include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"><h4 class="mb-0"><i class="bi bi-people me-2"></i>Users</h4></div>

<?php if($action === 'create'): ?>
<div class="card border-0"><div class="card-body">
    <h6 class="mb-3">Create User</h6>
    <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>">
    <div class="mb-3"><label class="form-label">Username</label><input type="text" name="username" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Display Name</label><input type="text" name="display_name" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required minlength="8"></div>
    <div class="mb-3"><label class="form-label">Confirm Password</label><input type="password" name="password_confirm" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Role</label><select name="role_id" class="form-select"><?php foreach($assignableRoles as $r): ?><option value="<?php echo $r['id']; ?>"><?php echo esc($r['display_name']); ?></option><?php endforeach; ?></select></div>
    <button type="submit" class="btn btn-success">Create User</button><a href="<?php echo admin_url('users'); ?>" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div></div>
<?php elseif($editUser): ?>
<div class="card border-0"><div class="card-body">
    <h6 class="mb-3">Edit User</h6>
    <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>"><input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
    <div class="mb-3"><label class="form-label">Display Name</label><input type="text" name="display_name" class="form-control" value="<?php echo esc_attr($editUser['display_name']??''); ?>"></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="<?php echo esc_attr($editUser['email']); ?>"></div>
    <div class="mb-3"><label class="form-label">Role</label><select name="role_id" class="form-select"><?php foreach($assignableRoles as $r): ?><option value="<?php echo $r['id']; ?>" <?php echo $editUser['role_id']==$r['id']?'selected':''; ?>><?php echo esc($r['display_name']); ?></option><?php endforeach; ?></select></div>
    <div class="mb-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?php echo $editUser['status']==='active'?'selected':''; ?>>Active</option><option value="inactive" <?php echo $editUser['status']==='inactive'?'selected':''; ?>>Inactive</option><option value="banned" <?php echo $editUser['status']==='banned'?'selected':''; ?>>Banned</option></select></div>
    <?php if (Auth::isSuperAdmin()): ?>
    <div class="mb-3"><label class="form-label">Set new password</label><input type="password" name="new_password" class="form-control" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current password"><div class="form-text">Superadmin can set any user password without the current one.</div></div>
    <?php endif; ?>
    <button type="submit" class="btn btn-success">Update User</button><a href="<?php echo admin_url('users'); ?>" class="btn btn-outline-secondary">Cancel</a>
    </form>
</div></div>
<?php else: ?>
<div class="card border-0"><div class="card-body">
    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2"><div></div><a href="<?php echo admin_url('users&action=create'); ?>" class="btn btn-success btn-sm"><i class="bi bi-person-plus me-1"></i>New User</a></div>
    <div class="table-responsive">
    <table class="table table-hover"><thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody><?php foreach($users as $u): ?><tr>
        <td><div class="d-flex align-items-center gap-2"><div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width:35px;height:35px;font-size:14px;font-weight:600;background-color:var(--tp-primary)!important"><?php echo strtoupper(substr($u['display_name']??$u['username'],0,1)); ?></div><div><strong><?php echo $u['display_name']??$u['username']; ?></strong><br><small class="text-muted"><?php echo $u['email']; ?></small></div></div></td>
        <td><span class="badge bg-light text-dark"><?php echo $u['role_display']; ?></span></td>
        <td><span class="badge bg-<?php echo $u['status']==='active'?'success':($u['status']==='banned'?'danger':'secondary'); ?>"><?php echo $u['status']; ?></span></td>
        <td><small><?php echo format_date($u['created_at']); ?></small></td>
        <td><?php $protected = Auth::isProtectedUser($u); $canManage = !$protected || Auth::isSuperAdmin(); if ($canManage): ?><a href="<?php echo admin_url('users&action=edit&id='.$u['id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a><?php endif; ?> <?php if($u['id']!==Auth::id() && $canManage): ?><a href="<?php echo admin_url('users&action=delete&id='.$u['id'].'&_token='.csrf_token()); ?>" class="btn btn-sm btn-outline-danger delete-confirm"><i class="bi bi-trash"></i></a><?php endif; ?></td>
    </tr><?php endforeach; ?></tbody>
</table></div>
</div></div>
<?php endif; include ADMIN_PATH . 'layout/footer.php'; ?>