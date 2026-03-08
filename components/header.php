<?php
/**
 * Shared HTML <head> component
 * @param string $title Page title
 * @param string $css   Additional CSS file name (optional)
 */
function renderHead(string $title = 'MailFlow', string $css = 'dashboard'): void {
    $base = APP_URL . '/assets';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title><?= htmlspecialchars($title) ?> — MailFlow</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $base ?>/css/main.css">
  <?php if ($css): ?>
  <link rel="stylesheet" href="<?= $base ?>/css/<?= $css ?>.css">
  <?php endif; ?>
  <link rel="icon" href="<?= $base ?>/images/logo.svg" type="image/svg+xml">
</head>
<body>
<?php
}
