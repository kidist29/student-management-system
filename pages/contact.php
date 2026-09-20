<?php
require_once __DIR__ . '/../includes/auth_check.php';

$pageTitle = 'Contact';
$activePublic = 'contact';

$errors = [];
$values = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$submitted = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    foreach ($values as $key => $default) {
        $values[$key] = trim($_POST[$key] ?? $default);
    }

    if ($values['name'] === '') $errors['name'] = 'Please enter your name.';
    if ($values['email'] === '' || !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if ($values['subject'] === '') $errors['subject'] = 'Please enter a subject.';
    if ($values['message'] === '') $errors['message'] = 'Please enter a message.';

    if (!$errors) {
        // Stored in the database rather than emailed: a stock XAMPP install has no
        // SMTP configured, so pretending mail() would deliver would be dishonest.
        // Staff can review submissions directly (e.g. via phpMyAdmin, or a future
        // admin inbox) in the contact_messages table.
        require_once __DIR__ . '/../includes/functions.php';
        $stmt = getDB()->prepare('INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)');
        $stmt->execute([$values['name'], $values['email'], $values['subject'], $values['message']]);

        $submitted = true;
        $values = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    }
}

require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/site-nav.php';
?>

<section class="section" id="main-content">
    <div class="container">
        <div class="section-head">
            <div class="eyebrow">GET IN TOUCH</div>
            <h2>We're here to help</h2>
        </div>

        <div class="contact-grid">
            <div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div><h4>Address</h4><p><?= h(INSTITUTION_ADDRESS) ?></p></div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fa-solid fa-phone"></i></div>
                    <div><h4>Phone</h4><p><?= h(INSTITUTION_PHONE) ?></p></div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fa-solid fa-envelope"></i></div>
                    <div><h4>Email</h4><p><?= h(INSTITUTION_EMAIL) ?></p></div>
                </div>
                <div class="contact-info-item">
                    <div class="icon"><i class="fa-solid fa-clock"></i></div>
                    <div><h4>Office Hours</h4><p><?= h(INSTITUTION_HOURS) ?></p></div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <?php if ($submitted): ?>
                        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i><span>Thanks — your message has been received. Our office will get back to you soon.</span></div>
                    <?php endif; ?>
                    <?php if ($errors): ?>
                        <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation"></i><span>Please fix the errors below and try again.</span></div>
                    <?php endif; ?>

                    <form method="post" action="" data-validate novalidate>
                        <?= csrfField() ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Your Name <span class="req">*</span></label>
                                <input type="text" id="name" name="name" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" value="<?= h($values['name']) ?>" required>
                                <?php if (isset($errors['name'])): ?><div class="field-error"><?= h($errors['name']) ?></div><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="email">Email <span class="req">*</span></label>
                                <input type="email" id="email" name="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" value="<?= h($values['email']) ?>" required>
                                <?php if (isset($errors['email'])): ?><div class="field-error"><?= h($errors['email']) ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject <span class="req">*</span></label>
                            <input type="text" id="subject" name="subject" class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" value="<?= h($values['subject']) ?>" required>
                            <?php if (isset($errors['subject'])): ?><div class="field-error"><?= h($errors['subject']) ?></div><?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="message">Message <span class="req">*</span></label>
                            <textarea id="message" name="message" class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" rows="5" required><?= h($values['message']) ?></textarea>
                            <?php if (isset($errors['message'])): ?><div class="field-error"><?= h($errors['message']) ?></div><?php endif; ?>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require __DIR__ . '/../includes/site-footer.php';
require __DIR__ . '/../includes/footer.php';
