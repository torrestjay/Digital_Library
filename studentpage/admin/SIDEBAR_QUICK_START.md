# Admin Sidebar - Quick Start Guide

## Current Status
✅ The admin sidebar has been fully standardized across all admin pages.

## For Users/Admins

### How to Use the Sidebar
1. **Expand/Collapse:** Click the logo in the top-left corner
2. **Keyboard Shortcut:** Press `Alt + S` to toggle sidebar
3. **Mobile:** Sidebar automatically collapses on small screens
4. **State Persistence:** Your sidebar preference is remembered across page refreshes

### Sidebar Features
- **Dashboard** - View admin dashboard with statistics
- **Book Edit** - Manage library books
- **User Page** - Manage user accounts
- **Account Settings** - Update your admin profile
- **Archive History** - View archived items
- **Archived Books** - Manage archived books
- **Audit Logs** - View system audit trail
- **Security** - View security dashboard
- **Sign Out** - Logout from admin panel

---

## For Developers

### Adding a New Admin Page

**Step 1:** Create your new PHP file in `/admin/`
```php
<?php
session_start();
include('../dbcon.php');
// Your page logic here
?>
```

**Step 2:** Set up the HTML structure
```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Your Page Title</title>
  
  <!-- Design System CSS -->
  <link rel="stylesheet" href="../css/admin-design-system.css" />
  <link rel="stylesheet" href="../css/admin-utilities.css" />
</head>
<body>
  <!-- Sidebar Behavior Script -->
  <script src="includes/sidebar-behavior.js"></script>
  
  <div class="container">
    <!-- Include Standardized Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <a href="SettingAdmin.php">
            <img class="icon" src="../Images/profile.png">
          </a>
        </div>
      </header>
      
      <section class="content-section">
        <h2 class="section-title">Your Page Title</h2>
        <!-- Your content here -->
      </section>
    </main>
  </div>
</body>
</html>
```

**Step 3:** Register your page in the sidebar
Edit `admin/includes/admin_sidebar.php` and add your page to the `$admin_pages` array:

```php
$admin_pages = [
    'admindashboard.php' => ['icon' => 'dashboard.png', 'label' => 'Dashboard', 'order' => 1],
    'AdminBookEdit.php' => ['icon' => 'BookDetails.png', 'label' => 'Book Edit', 'order' => 2],
    // ... other pages ...
    'YourNewPage.php' => ['icon' => 'your-icon.png', 'label' => 'Your Page', 'order' => 9],
];
```

**Step 4:** Add your icon to `/Images/` folder
Place a PNG icon (24x24 recommended) in the `/Images/` folder with the name matching your config.

That's it! Your new page will:
- Have the same sidebar as all other pages
- Show as active when visited
- Include all sidebar functionality
- Be fully responsive

### CSS Architecture

#### Design System Variables
All styling uses CSS variables defined in `admin-design-system.css`:

```css
/* Colors */
--color-primary-dark: #0e3a5d
--color-primary: #1b678f
--color-primary-light: #2196F3
--color-success: #4CAF50
--color-danger: #F44336
--color-warning: #FF9800

/* Typography */
--font-family: 'Poppins', sans-serif
--font-size-base: 16px
--font-size-lg: 18px
--font-size-xl: 20px
--font-size-2xl: 24px

/* Spacing */
--space-8: 8px
--space-12: 12px
--space-16: 16px
--space-20: 20px
--space-24: 24px
--space-32: 32px

/* Shadows */
--shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05)
--shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08)
--shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12)
```

#### Usage in Your Page
```css
.my-element {
  background-color: var(--color-primary);
  color: var(--color-text-primary);
  padding: var(--space-16);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-md);
  font-family: var(--font-family);
  font-size: var(--font-size-base);
}
```

### JavaScript Functions

#### Available Functions
The `sidebar-behavior.js` script provides these functions:

```javascript
// Toggle sidebar between expanded/collapsed
toggleSidebar()

// Initialize sidebar state on page load (auto-called)
initSidebarState()

// Auto-collapse sidebar on small screens (auto-called)
autoCollapseSidebarOnMobile()
```

#### localStorage Keys
- `sidebarCollapsed` - Boolean value (true/false)

#### Events
- `resize` - Triggers auto-collapse on small screens
- `DOMContentLoaded` - Initializes sidebar state
- `keydown` - Checks for Alt+S shortcut

### File Structure
```
studentpage/
├── admin/
│   ├── includes/
│   │   ├── admin_sidebar.php
│   │   └── sidebar-behavior.js
│   ├── admindashboard.php
│   ├── AdminBookEdit.php
│   ├── AdminUserPage.php
│   ├── SettingAdmin.php
│   ├── ArchiveHistory.php
│   ├── ArchivedBooks.php
│   ├── AuditLogs.php
│   └── SecurityDashboard.php
├── css/
│   ├── admin-design-system.css
│   └── admin-utilities.css
├── Images/
│   ├── logo.png
│   ├── dashboard.png
│   ├── BookDetails.png
│   ├── userpage.png
│   ├── settings.png
│   ├── archive.png
│   ├── audit.png
│   ├── security.png
│   ├── signout.png
│   └── profile.png
└── SIDEBAR_STANDARDIZATION_COMPLETE.md
```

### Common CSS Classes

#### Layout Classes
```css
.container       /* Main flex container */
.sidebar         /* Sidebar navigation */
.sidebar.collapsed /* Collapsed state */
.main-content    /* Main content area */
.header          /* Page header */
.content-section /* Content wrapper */
```

#### Typography Classes
```css
.section-title   /* Page title */
.card            /* Card component */
.btn             /* Button */
.btn-primary     /* Primary button */
.btn-danger      /* Danger button */
```

#### Utility Classes
From `admin-utilities.css`:
```css
.text-center
.text-right
.mt-8
.mt-16
.mb-8
.mb-16
.p-16
.p-24
.flex
.flex-between
.flex-center
.grid-cols-2
.gap-16
```

---

## Troubleshooting

### Sidebar Not Showing
1. Check that `admin_sidebar.php` include is present
2. Verify the include path: `<?php include 'includes/admin_sidebar.php'; ?>`
3. Ensure `/Images/` folder exists with icon files

### Sidebar Toggle Not Working
1. Check that `sidebar-behavior.js` is included
2. Verify the script path: `<script src="includes/sidebar-behavior.js"></script>`
3. Check browser console for JavaScript errors

### Styling Issues
1. Verify `admin-design-system.css` is linked
2. Verify `admin-utilities.css` is linked
3. Check CSS variable names in `admin-design-system.css`
4. Clear browser cache (Ctrl+Shift+Delete)

### Mobile Responsive Issues
1. Add viewport meta tag: `<meta name="viewport" content="width=device-width, initial-scale=1.0"/>`
2. Check window resize event is firing
3. Verify breakpoints: 1024px for mobile collapse

---

## Best Practices

### DO ✅
- Use CSS variables from design system
- Include both design-system.css AND utilities.css
- Follow the HTML structure provided
- Use semantic HTML elements
- Test at multiple screen sizes
- Use SweetAlert2 for alerts/modals
- Log actions to audit trail

### DON'T ❌
- Don't create duplicate sidebar code
- Don't override design system CSS
- Don't use inline styles for layout
- Don't hardcode colors (use CSS variables)
- Don't skip the viewport meta tag
- Don't forget mobile testing
- Don't mix old CSS files

---

## Resources

### Links
- CSS Design System: `css/admin-design-system.css`
- JavaScript Reference: `admin/includes/sidebar-behavior.js`
- Complete Documentation: `admin/SIDEBAR_STANDARDIZATION_COMPLETE.md`
- Admin Layout Standards: `admin/ADMIN_LAYOUT_STANDARDS.md`

### Examples
- Dashboard: `admin/admindashboard.php`
- Book Management: `admin/AdminBookEdit.php`
- User Management: `admin/AdminUserPage.php`

---

## Support

For issues or questions about the admin sidebar:
1. Check the troubleshooting section above
2. Review the complete documentation file
3. Check the example pages
4. Examine the CSS variables and utilities

---

**Last Updated:** May 27, 2026
**Sidebar Status:** ✅ STANDARDIZED
