<?php
/**
 * Standard Admin Sidebar Component
 * Include this file in all admin pages for consistent sidebar navigation
 * 
 * USAGE: <?php include 'includes/admin_sidebar.php'; ?>
 * 
 * The sidebar will automatically set the active page based on the current file name.
 * No customization needed - just include and the sidebar will work correctly.
 */

// Get the current page filename
$current_page = basename($_SERVER['PHP_SELF']);

// Define all admin pages with their nav items
$admin_pages = [
    'admindashboard.php' => ['icon' => 'dashboard.png', 'label' => 'Dashboard', 'order' => 1],
    'AdminBookEdit.php' => ['icon' => 'BookDetails.png', 'label' => 'Book Edit', 'order' => 2],
    'AdminUserPage.php' => ['icon' => 'userpage.png', 'label' => 'User Page', 'order' => 3],
    'ArchivedBooks.php' => ['icon' => 'archive.png', 'label' => 'Archived Books', 'order' => 4],
];

// Sort by order (uasort preserves array keys, unlike usort which reindexes)
uasort($admin_pages, function($a, $b) {
    return $a['order'] <=> $b['order'];
});
?>

<!-- Standard Admin Sidebar -->
<aside class="sidebar" id="sidebar">
  <!-- Logo -->
  <div class="logo" onclick="toggleSidebar()">
    <img src="../Images/logo.png" alt="Readly Logo" />
  </div>
  
  <!-- Navigation Menu -->
  <nav class="nav">
    <?php foreach ($admin_pages as $page => $info): ?>
      <a href="<?php echo $page; ?>" <?php echo ($current_page === $page) ? 'class="active"' : ''; ?>>
        <img class="icon" src="../Images/<?php echo $info['icon']; ?>" alt="<?php echo $info['label']; ?> Icon" />
        <span><?php echo $info['label']; ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  
  <!-- Sign Out Section -->
  <div class="sign-out">
    <a href="../logout.php">
      <img class="icon" src="../Images/signout.png" alt="Sign Out Icon" />
      <span>Sign Out</span>
    </a>
  </div>
</aside>
