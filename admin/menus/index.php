<?php
if (!defined('ADMIN')) die('Direct access not permitted.');
Auth::requirePermission('menus.manage');
$action = $_GET['action'] ?? 'index';
$pageTitle = 'Menus | Admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['_token'] ?? '')) {
    if (!empty($_POST['menu_id']) && !empty($_POST['title'])) {
        Database::insert('menu_items', ['menu_id'=>intval($_POST['menu_id']),'title'=>sanitize($_POST['title']),'url'=>sanitize($_POST['url']??''),'sort_order'=>intval($_POST['sort_order']??0),'item_type'=>$_POST['item_type']??'custom']);
        Auth::logActivity('menu_item_create', 'Added menu item: ' . sanitize($_POST['title']));
        flash_set('success', 'Menu item added.');
    }
    header('Location: ' . admin_url('menus')); exit;
}
if ($action === 'delete_item' && verify_csrf($_GET['_token'] ?? '')) {
    $itemId = intval($_GET['id'] ?? 0);
    Database::delete('menu_items', 'id = ?', [$itemId]);
    Auth::logActivity('menu_item_delete', 'Deleted menu item #' . $itemId);
    flash_set('success', 'Item deleted.');
    header('Location: ' . admin_url('menus'));
    exit;
}

$menus = Database::fetchAll("SELECT * FROM menus WHERE is_active = 1 ORDER BY name");
$selectedMenu = intval($_GET['menu'] ?? 1);
$menuItems = Database::fetchAll("SELECT * FROM menu_items WHERE menu_id = ? AND parent_id IS NULL AND is_active = 1 ORDER BY sort_order, title", [$selectedMenu]);
$pages = Page::getMenuPages();
$categories = Category::getAll();

include ADMIN_PATH . 'layout/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2"><h4 class="mb-0"><i class="bi bi-menu-button me-2"></i>Menus</h4></div>
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 mb-4"><div class="card-header bg-white"><div class="d-flex gap-2 flex-wrap"><?php foreach($menus as $m): ?><a href="<?php echo admin_url('menus&menu='.$m['id']); ?>" class="btn btn-sm <?php echo $selectedMenu==$m['id']?'btn-success':'btn-outline-secondary'; ?>"><?php echo $m['name']; ?></a><?php endforeach; ?></div></div><div class="card-body">
            <div class="list-group">
                <?php if(empty($menuItems)): ?><p class="text-muted">No items in this menu.</p><?php endif; ?>
                <?php foreach($menuItems as $item): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="min-width-0"><i class="bi bi-grip-vertical text-muted me-2"></i><strong><?php echo $item['title']; ?></strong><br><small class="text-muted text-break"><?php echo $item['url']; ?> | <?php echo $item['item_type']; ?></small></div>
                    <a href="<?php echo admin_url('menus&action=delete_item&id='.$item['id'].'&_token='.csrf_token()); ?>" class="btn btn-sm btn-outline-danger delete-confirm"><i class="bi bi-trash"></i></a>
                </div>
                <?php endforeach; ?>
            </div>
        </div></div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0"><div class="card-header bg-white"><h6 class="mb-0">Add Menu Item</h6></div><div class="card-body">
            <form method="POST"><input type="hidden" name="_token" value="<?php echo csrf_token(); ?>"><input type="hidden" name="menu_id" value="<?php echo $selectedMenu; ?>">
            <div class="mb-3"><label class="form-label">Title</label><input type="text" name="title" class="form-control" required></div>
            <div class="mb-3"><label class="form-label">URL</label><input type="text" name="url" class="form-control" placeholder="/path or http://..."></div>
            <div class="mb-3"><label class="form-label">Type</label><select name="item_type" class="form-select"><option value="custom">Custom</option><option value="page">Page</option><option value="category">Category</option></select></div>
            <div class="mb-3"><label class="form-label">Sort Order</label><input type="number" name="sort_order" class="form-control" value="0"></div>
            <button type="submit" class="btn btn-success w-100">Add Item</button>
            </form>
        </div></div>
    </div>
</div>
<?php include ADMIN_PATH . 'layout/footer.php'; ?>