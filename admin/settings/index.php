<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_once __DIR__ . '/../../includes/functions.php';
requireRole('super_admin');

$db = getDB();
$pageTitle = 'Settings';
$activeNav = 'settings';

$fields = [
    'institution_name'    => 'Institution Name',
    'institution_address' => 'Address',
    'institution_email'   => 'Contact Email',
    'institution_phone'   => 'Contact Phone',
    'institution_hours'   => 'Office Hours',
];

$errors = [];
$values = [
    'institution_name'    => INSTITUTION_NAME,
    'institution_address' => INSTITUTION_ADDRESS,
    'institution_email'   => INSTITUTION_EMAIL,
    'institution_phone'   => INSTITUTION_PHONE,
    'institution_hours'   => INSTITUTION_HOURS,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($values as $key => $default) {
        $values[$key] = trim($_POST[$key] ?? $default);
    }

    if ($values['institution_name'] === '') $errors['institution_name'] = 'Institution name is required.';
    if ($values['institution_email'] === '' || !filter_var($values['institution_email'], FILTER_VALIDATE_EMAIL)) {
        $errors['institution_email'] = 'A valid email address is required.';
    }
    if ($values['institution_address'] === '') $errors['institution_address'] = 'Address is required.';

    if (!$errors) {
        $stmt = $db->prepare('
            INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
        ');
        foreach ($values as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        setFlash('success', 'Settings updated. Changes apply across the whole site immediately.');
        redirect('admin/settings/index.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="admin-shell">
    <?php require __DIR__ . '/../../includes/sidebar.php'; ?>
    <div class="main-content" id="main-content">
        <?php require __DIR__ . '/../../includes/navbar.php'; ?>
        <div class="page-content">
            <div class="page-header">
                <div>
                    <div class="breadcrumb"><a href="<?= BASE_URL ?>admin/dashboard.php">Dashboard</a> / Settings</div>
                    <h1>System Settings</h1>
                </div>
            </div>

            <?php if ($errors): ?>
                <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>Please fix the errors below and try again.</span></div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header"><h3>Institution Details</h3></div>
                <div class="card-body">
                    <p style="margin:0 0 20px;color:var(--ink-600);font-size:0.9rem;">
                        These values drive the site name, footer, transcript header, and contact page
                        everywhere across the system — changing them here updates the whole site at once.
                    </p>
                    <form method="post" action="" data-validate novalidate style="max-width:560px;">
                        <?= csrfField() ?>
                        <?php foreach ($fields as $key => $label): ?>
                            <div class="form-group">
                                <label for="<?= $key ?>"><?= h($label) ?> <span class="req">*</span></label>
                                <input type="text" id="<?= $key ?>" name="<?= $key ?>" class="form-control <?= isset($errors[$key]) ? 'is-invalid' : '' ?>" value="<?= h($values[$key]) ?>" required>
                                <?php if (isset($errors[$key])): ?><div class="field-error"><?= h($errors[$key]) ?></div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
