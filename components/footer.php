<?php
/**
 * Shared page footer component
 * @param array $scripts Additional script file names
 */
function renderFooter(array $scripts = []): void {
    $base = APP_URL . '/assets/js';
    
    // Core scripts always loaded
    $coreScripts = ['main.js', 'sidebar.js'];
    
    // Merge, deduplicate — prevent double loading
    $allScripts = array_unique(array_merge($coreScripts, $scripts));
?>
  <?php foreach ($allScripts as $s): ?>
  <script src="<?= $base ?>/<?= htmlspecialchars($s) ?>"></script>
  <?php endforeach; ?>
</body>
</html>
<?php
}

