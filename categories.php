<?php
require_once 'config/db.php';
include 'includes/header.php';
include 'includes/sidebar.php';

$message = "";

// 1. Handle Add Category
if (isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
        $stmt->execute([$name]);
        $message = "<div class='alert alert-success border-0 shadow-sm rounded-3'><i class='fas fa-check-circle me-2'></i>".__('category_added')."</div>";
    }
}

// 2. Handle Update Category
if (isset($_POST['update_category'])) {
    $id = $_POST['category_id'];
    $name = trim($_POST['category_name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("UPDATE categories SET category_name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        $message = "<div class='alert alert-info border-0 shadow-sm rounded-3'><i class='fas fa-info-circle me-2'></i>".__('category_updated')."</div>";
    }
}

// 3. Handle Delete Category
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $check = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $check->execute([$id]);
    if ($check->fetchColumn() > 0) {
        $message = "<div class='alert alert-danger border-0 shadow-sm rounded-3'><i class='fas fa-exclamation-triangle me-2'></i>".__('cannot_delete_category')."</div>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $message = "<div class='alert alert-warning border-0 shadow-sm rounded-3'><i class='fas fa-trash-alt me-2'></i>".__('category_deleted')."</div>";
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name ASC")->fetchAll();
?>

<div class="container-fluid py-2">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="h3 mb-0 fw-800 text-dark"><i class="fas fa-tags text-primary me-2"></i> <?= __('categories') ?></h2>
            <p class="text-muted small mb-0"><?= __('manage_groups') ?></p>
        </div>
        <button class="btn btn-primary rounded-pill shadow-sm px-4 fw-bold border-0 py-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal" style="background: var(--accent-red);">
            <i class="fas fa-plus me-2"></i> <?= __('add_category') ?>
        </button>
    </div>

    <?= $message ?>

    <div class="row g-4">
        <!-- Main Category Table -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm overflow-hidden rounded-4">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted fw-bold">
                                <th class="ps-4" style="width: 100px;">ID</th>
                                <th><?= __('category_name') ?></th>
                                <th><?= __('items_in_category') ?></th>
                                <th class="text-center"><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
                            <?php foreach ($categories as $cat): 
                                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
                                $countStmt->execute([$cat['id']]);
                                $itemCount = $countStmt->fetchColumn();
                            ?>
                            <tr class="category-row">
                                <td class="ps-4 text-muted fw-bold">#<?= $cat['id'] ?></td>
                                <td><div class="fw-bold text-dark fs-6"><?= htmlspecialchars($cat['category_name']) ?></div></td>
                                <td>
                                    <span class="badge bg-primary-soft text-primary px-3 py-2 rounded-pill fw-bold">
                                        <?= $itemCount ?> <?= __('items') ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-light text-primary border-0 rounded-3 me-2 shadow-none" 
                                            onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars($cat['category_name'], ENT_QUOTES) ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <a href="categories.php?delete=<?= $cat['id'] ?>" 
                                       class="btn btn-sm btn-light text-danger border-0 rounded-3 shadow-none" 
                                       onclick="return confirm('<?= __('confirm_delete_category') ?>')">
                                        <i class="fas fa-trash-alt"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Helpful Tips Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4" style="background: #111827; color: white;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary rounded-circle p-2 me-3">
                            <i class="fas fa-lightbulb text-white"></i>
                        </div>
                        <h6 class="fw-bold mb-0 text-white"><?= __('tips') ?></h6>
                    </div>
                    <p class="small opacity-75 mb-0" style="line-height: 1.7;">
                        <?= __('category_tips_msg') ?>
                    </p>
                    <hr class="my-4 opacity-25">
                    <div class="text-center">
                        <i class="fas fa-wine-glass-alt fa-4x opacity-10"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="categories.php" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold ms-2"><?= __('add_category') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <label class="form-label small fw-bold text-muted text-uppercase"><?= __('category_name') ?></label>
                <input type="text" name="category_name" class="form-control form-control-lg rounded-3 shadow-none border-light-subtle" placeholder="e.g. Whiskey, Beer, Wine" required>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="submit" name="add_category" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm" style="background: var(--accent-red); border: none;"><?= __('save_category') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="categories.php" method="POST" class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold ms-2"><?= __('edit_category') ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="category_id" id="edit_category_id">
                <label class="form-label small fw-bold text-muted text-uppercase"><?= __('category_name') ?></label>
                <input type="text" name="category_name" id="edit_category_name" class="form-control form-control-lg rounded-3 shadow-none border-light-subtle" required>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="submit" name="update_category" class="btn btn-primary w-100 py-3 fw-bold rounded-pill shadow-sm" style="background: var(--accent-red); border: none;"><?= __('update_category') ?></button>
            </div>
        </form>
    </div>
</div>

<script>
function editCategory(id, name) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}
</script>

<style>
    .fw-800 { font-weight: 800; }
    .bg-primary-soft { background-color: rgba(79, 70, 229, 0.08) !important; }
    .category-row:hover { background-color: #f8fafc !important; }
</style>

<?php include 'includes/footer.php'; ?>