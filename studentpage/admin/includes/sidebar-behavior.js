/**
 * Standardized Sidebar Behavior
 * Include this JavaScript file in all admin pages for consistent sidebar functionality
 * 
 * USAGE: <script src="includes/sidebar-behavior.js"></script>
 */

/**
 * Toggle sidebar between expanded and collapsed states
 * Automatically saves preference to localStorage
 */
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  if (sidebar) {
    sidebar.classList.toggle("collapsed");
    // Save preference to localStorage
    const isCollapsed = sidebar.classList.contains("collapsed");
    localStorage.setItem("sidebarCollapsed", isCollapsed);
  }
}

/**
 * Initialize sidebar state from localStorage on page load
 * Restores user's previous sidebar preference
 */
function initSidebarState() {
  const sidebar = document.getElementById("sidebar");
  if (sidebar) {
    const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
    if (isCollapsed) {
      sidebar.classList.add("collapsed");
    }
  }
}

/**
 * Collapse sidebar on small screens (mobile/tablet)
 * Improve mobile responsiveness
 */
function autoCollapseSidebarOnMobile() {
  const sidebar = document.getElementById("sidebar");
  if (sidebar) {
    if (window.innerWidth <= 1024) {
      sidebar.classList.add("collapsed");
    } else {
      const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
      if (!isCollapsed) {
        sidebar.classList.remove("collapsed");
      }
    }
  }
}

/**
 * Handle window resize events for responsive behavior
 */
window.addEventListener("resize", function() {
  autoCollapseSidebarOnMobile();
});

/**
 * Initialize sidebar when DOM is fully loaded
 */
document.addEventListener("DOMContentLoaded", function() {
  initSidebarState();
  autoCollapseSidebarOnMobile();
});

/**
 * Add keyboard shortcut support (Alt+S to toggle sidebar)
 */
document.addEventListener("keydown", function(event) {
  if (event.altKey && event.key === "s") {
    event.preventDefault();
    toggleSidebar();
  }
});
