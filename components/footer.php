<?php
/**
 * Shared page footer component
 * @param array $scripts Additional script file names
 */
function renderFooter(array $scripts = []): void {
    $base = APP_URL . '/assets/js';
?>
  <script src="<?= $base ?>/main.js"></script>
  <script src="<?= $base ?>/sidebar.js"></script>
  <?php foreach ($scripts as $s): ?>
  <script src="<?= $base ?>/<?= $s ?>"></script>
  <?php endforeach; ?>
</body>
</html>
<?php
}
