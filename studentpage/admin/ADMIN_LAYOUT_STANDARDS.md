# Admin Panel Layout & Navigation Standards

## Overview
This document outlines the standardized layout, navigation, and component standards for all Digital Library Admin Panel pages. All admin pages follow these standards for consistency, accessibility, and user experience.

---

## Navigation Structure

### Standardized Sidebar Component
**File:** `/admin/includes/admin_sidebar.php`  
**Usage:** `<?php include 'includes/admin_sidebar.php'; ?>`

The sidebar is a reusable PHP component that automatically:
- Detects the current page and highlights it as active
- Maintains consistent styling across all pages
- Provides uniform navigation structure
- Implements responsive collapse behavior

### Navigation Menu Items (In Order)
1. **Dashboard** → `admindashboard.php`
2. **Book Edit** → `AdminBookEdit.php`
3. **User Page** → `AdminUserPage.php`
4. **Account Settings** → `SettingAdmin.php`
5. **Archive History** → `ArchiveHistory.php`
6. **Audit Logs** → `AuditLogs.php`
7. **Security** → `SecurityDashboard.php`

### Sidebar Styling
- **Logo Section**: 70px height, click-to-toggle functionality
- **Navigation Container**: Flex column layout, grows to fill space
- **Menu Items**: Equal padding, consistent icon sizing
- **Active State**: Highlighted with 25% white overlay + left border
- **Hover State**: 15% white overlay transition
- **Sign-out Section**: Border-top separator, margin-top auto

---

## Page Layout Structure

### Standard HTML Structure
```html
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Meta & Links -->
</head>
<body>
  <div class="container">
    <!-- Standardized Sidebar -->
    <?php include 'includes/admin_sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content">
      <!-- Header -->
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png"></a>
        </div>
      </header>
      
      <!-- Content Section -->
      <section class="content-section">
        <!-- Page Content -->
      </section>
    </main>
  </div>
</body>
</html>
```

### Container System
- **`.container`**: Flex layout, 100vh height, sidebar + main-content
- **`.sidebar`**: 
  - Width: 250px (expanded) / 70px (collapsed)
  - Transition: var(--transition-slow)
  - Background: Linear gradient #0e3a5d → #1b678f
  
- **`.main-content`**: 
  - Flex: 1 (grows to fill space)
  - Display: flex flex-direction: column
  - Background: var(--color-background)

---

## Header Standards

### Header Component Structure
```html
<header class="header">
  <div class="spacer"></div>
  <div class="header-icons">
    <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png"></a>
  </div>
</header>
```

### Header Styling
- **Height**: 70px (matches sidebar logo height)
- **Background**: white with shadow
- **Layout**: Flex, space-between
- **Icon Size**: 24px
- **Spacing**: var(--space-16) padding
- **Responsive**: Maintains height at all breakpoints

---

## Content Section Standards

### Content Section Structure
```html
<section class="content-section">
  <h2 class="section-title">Page Title</h2>
  <!-- Page-specific content -->
</section>
```

### Content Section Styling
- **Max-width**: 1400px (desktop), 1024px (tablet), 100% (mobile)
- **Margins**: 0 auto (centered)
- **Padding**: var(--space-32) (desktop), var(--space-16) (mobile)
- **Background**: Transparent
- **Grid Support**: CSS Grid for card layouts

---

## Typography Standards

### Headings
- **Page Title (h2.section-title)**:
  - Font-size: 28px
  - Font-weight: 700
  - Color: var(--color-text-primary)
  - Margin-bottom: var(--space-24)

- **Section Headings (h3)**:
  - Font-size: 18px
  - Font-weight: 600
  - Margin-bottom: var(--space-16)

- **Card Titles (h4)**:
  - Font-size: 14px
  - Font-weight: 600
  - Uppercase with letter-spacing

### Body Text
- **Font**: Poppins
- **Size**: 14px or 16px
- **Color**: var(--color-text-primary) or var(--color-text-secondary)
- **Line-height**: 1.6

---

## Color Palette

### Primary Colors
- **Dark Primary**: #0e3a5d (sidebar background)
- **Medium Primary**: #1b678f (sidebar gradient)
- **Light Primary**: #2196F3 (accent)

### Status Colors
- **Success**: #4CAF50 (green)
- **Error**: #F44336 (red)
- **Warning**: #FF9800 (orange)
- **Info**: #2196F3 (blue)

### Neutral Colors
- **Text Primary**: #2c3e50
- **Text Secondary**: #7f8c8d
- **Background**: #f5f7fa
- **Border**: #e0e6ed

---

## Responsive Design

### Breakpoints
- **Desktop**: 1400px and up (sidebar 250px expanded)
- **Tablet**: 1024px - 1399px (sidebar remains 250px)
- **Mobile**: 768px - 1023px (sidebar auto-collapses)
- **Small Mobile**: Below 768px (sidebar always collapsed)

### Responsive Behaviors
1. **Sidebar**: Auto-collapses at 1024px
2. **Content**: Padding reduces on smaller screens
3. **Grid Layouts**: Adapt from 3 columns → 2 → 1
4. **Header Icons**: Hide some icons on small screens

---

## Component Standards

### Cards (.card)
- **Background**: white
- **Border-radius**: var(--radius-lg)
- **Padding**: var(--space-20)
- **Shadow**: var(--shadow-md)
- **Margin-bottom**: var(--space-16)

### Buttons
- **Primary (.btn-primary)**: Dark blue background, white text
- **Secondary (.btn-secondary)**: Light background, dark text
- **Danger (.btn-danger)**: Red background, white text
- **Padding**: var(--space-8) var(--space-16)
- **Border-radius**: var(--radius-md)
- **Transition**: background-color var(--transition-base)

### Forms
- **Input Fields**: 
  - Border: 1px solid #e0e6ed
  - Padding: var(--space-8) var(--space-12)
  - Border-radius: var(--radius-md)
  - Focus: Blue border, shadow
  
- **Labels**: 
  - Font-weight: 600
  - Font-size: 12px
  - Text-transform: uppercase
  - Margin-bottom: var(--space-8)

### Tables
- **Header**: Dark background, white text, font-weight: 600
- **Rows**: Alternate row coloring (zebra striping)
- **Cells**: Padding var(--space-12)
- **Borders**: Subtle 1px borders

---

## Icon Standards

### Icon Sizing
- **Sidebar Icons**: 24px × 24px
- **Header Icons**: 24px × 24px
- **Button Icons**: 16px × 16px (within buttons)
- **Card Icons**: 32px × 32px (decorative)

### Icon Images
Located in `/student page/Images/`:
- `dashboard.png` - Dashboard
- `BookDetails.png` - Book Edit
- `userpage.png` - User Page
- `settings.png` - Settings/Account
- `archive.png` - Archive
- `audit.png` - Audit Logs
- `security.png` - Security
- `signout.png` - Sign Out
- `profile.png` - Profile (header)
- `logo.png` - Logo

---

## JavaScript Standards

### Sidebar Toggle Function
All pages must include:
```javascript
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  sidebar.classList.toggle("collapsed");
}
```

### Modal Management
- Use SweetAlert2 for alerts/confirmations
- Use custom modal overlays for forms
- Always include proper close functionality

---

## Required CSS Files

All admin pages must include:
1. **admin-design-system.css** - Core design tokens and component styles
2. **admin-utilities.css** - Utility classes and helpers
3. **SweetAlert2** - Modal/confirmation dialogs (via CDN)
4. **FontAwesome** - Icons (via CDN for fas classes)

---

## Page-Specific Implementations

### admindashboard.php
- Multi-chart dashboard with Chart.js
- Card-grid layout for statistics
- Real-time updates
- Activity graphs

### AdminBookEdit.php
- Book management interface
- Add/edit/delete functionality
- Category-based organization
- Image upload handling

### AdminUserPage.php
- User borrowing records
- Filter and search functionality
- Status tracking
- User analytics

### SettingAdmin.php
- Account profile management
- Personal information editing
- MFA toggle
- Vulnerability alerts
- Security recommendations

### AuditLogs.php
- Audit trail filtering
- Admin action logging
- Filter by: admin, action, resource, date
- Pagination (500 records max)

### ArchiveHistory.php
- Book archival history
- Admin who archived tracking
- Date tracking
- Filter and search

### ArchivedBooks.php
- Display archived books
- Restore functionality
- Status tracking

### SecurityDashboard.php
- Security statistics
- Failed login monitoring
- Account lockout tracking
- Vulnerability scanner results
- Auto-refresh every 30 seconds

---

## Security Standards

### Error Handling
- All database operations must check prepare() return value
- Wrap prepare/bind/execute in try-catch patterns
- Display user-friendly errors, log detailed errors
- Never expose SQL errors to users

### Database Connections
- Use prepared statements for all queries
- Bind parameters using parameter markers (?)
- Validate input on both client and server
- Implement CSRF token validation on forms

### Session Management
- Require session_start() at top of each page
- Check $_SESSION['user_id'] exists
- Redirect to login if not authenticated
- Set appropriate HTTP headers (no-cache)

---

## Accessibility Standards

### Semantic HTML
- Use `<header>`, `<nav>`, `<main>`, `<section>` elements
- Use `<label>` for all form inputs
- Use `<table>` for tabular data only

### ARIA Attributes
- Add `aria-label` to icon-only buttons
- Add `aria-hidden="true"` to decorative elements
- Use `role="main"` on main content area

### Keyboard Navigation
- All interactive elements must be keyboard accessible
- Use Tab order sensibly
- Provide visual focus indicators
- Implement escape key to close modals

### Color Contrast
- Ensure 4.5:1 contrast ratio for normal text
- Ensure 3:1 contrast ratio for large text
- Don't rely solely on color to convey information

---

## Internationalization (i18n)

### Text Handling
- Use `htmlspecialchars()` for all user-provided output
- Use `htmlspecialchars()` in href attributes
- Store strings in variables for future translation
- Avoid hardcoded text in HTML when possible

---

## Performance Standards

### CSS
- All CSS centralized in design-system.css
- Use CSS variables for values
- Minimize inline styles
- Leverage CSS Grid and Flexbox (no floats)

### JavaScript
- Minimize DOM manipulation
- Use event delegation where possible
- Debounce resize and scroll listeners
- Cache DOM references

### Images
- Optimize all images before upload
- Use appropriate formats (PNG for logos, JPG for photos)
- Provide alt text for all images
- Set width/height attributes

---

## Testing Checklist

### Before Deployment
- [ ] Page loads without PHP errors
- [ ] Sidebar displays correctly (expanded/collapsed)
- [ ] Active page is highlighted in sidebar
- [ ] All navigation links work
- [ ] Profile icon links to Settings
- [ ] Responsive design works at all breakpoints
- [ ] All modals/overlays function correctly
- [ ] Form submissions work
- [ ] Database operations use prepared statements
- [ ] No console errors in DevTools
- [ ] Page displays correctly in Firefox, Chrome, Safari, Edge
- [ ] Mobile viewport is properly configured
- [ ] Accessibility: Tab navigation works throughout
- [ ] Accessibility: Screen reader reads headings correctly

---

## File Organization

```
admin/
  ├── includes/
  │   └── admin_sidebar.php          (standardized sidebar)
  ├── admindashboard.php             (dashboard page)
  ├── AdminBookEdit.php              (book management)
  ├── AdminUserPage.php              (user records)
  ├── AdminNotif.php                 (notifications)
  ├── AdminRules.php                 (rules management)
  ├── AdminUserPage.php              (user page)
  ├── SettingAdmin.php               (account settings)
  ├── ArchiveHistory.php             (archive logs)
  ├── ArchivedBooks.php              (archived books)
  ├── AuditLogs.php                  (audit trail)
  ├── SecurityDashboard.php          (security monitoring)
  ├── security_utils.php             (security functions)
  ├── security_db_init.php           (security schema)
  ├── archive_operations.php         (archive functions)
  ├── archive_db_init.php            (archive schema)
  └── [other utility files]
  
css/
  ├── admin-design-system.css        (main design system)
  └── admin-utilities.css            (utility classes)
  
Images/
  ├── logo.png
  ├── dashboard.png
  ├── BookDetails.png
  ├── userpage.png
  ├── settings.png
  ├── archive.png
  ├── audit.png
  ├── security.png
  ├── signout.png
  └── profile.png
```

---

## Maintenance Guidelines

### Adding New Pages
1. Create new PHP file in `/admin/`
2. Include: `<?php include 'includes/admin_sidebar.php'; ?>`
3. Add entry to `$admin_pages` array in `admin_sidebar.php`
4. Follow standard HTML structure from documentation
5. Use design-system.css classes
6. Test sidebar navigation and active states

### Updating Styles
1. Modify `admin-design-system.css` only
2. Use CSS variables (--color-, --space-, --font-, etc.)
3. Maintain responsive breakpoints
4. Test at all breakpoints
5. Ensure backward compatibility

### Adding Features
1. Always use prepared statements for database queries
2. Add error handling: `if (!$stmt) { error_log(...); }`
3. Test with both valid and invalid inputs
4. Add appropriate success/error messages
5. Log significant actions to audit trail

---

## Contact & Support
For questions about standards or clarifications needed, refer to this document and the design system CSS files for source of truth on specific styling.
