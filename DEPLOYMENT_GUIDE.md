# Deployment Guide - Step by Step

## 🚀 Pre-Deployment (Do Once)

### Step 1: Database Schema Initialization
```
1. Open browser to:
   http://localhost/xampp/htdocs/Digital_Library/studentpage/admin/archive_db_init.php

2. Expected Output:
   ✓ Added archived_at column (or already exists)
   ✓ Added archived_by column (or already exists)
   ✓ Added archive_reason column (or already exists)
   ✓ Archive log table ready
   Archive system initialized successfully!

3. If errors:
   - Check database connection in dbcon.php
   - Verify user has ALTER TABLE permissions
   - Verify MySQL server is running
```

### Step 2: Verify File Permissions
```
Ensure these files are readable by web server:
- /admin/archive_operations.php (readable + executable)
- /admin/ArchiveHistory.php (readable)
- /admin/ArchivedBooks.php (readable)
- /css/admin-design-system.css (readable)
- /css/admin-utilities.css (readable)

On Linux:
chmod 644 admin-design-system.css
chmod 644 admin-utilities.css
chmod 755 archive_operations.php
```

### Step 3: Test Database Connection
```
PHP Script to run:
<?php
include('dbcon.php');
if ($conn) {
    echo "✓ Database connection successful";
    
    // Test archive tables
    $result = $conn->query("SELECT * FROM archive_log LIMIT 1");
    echo "✓ Archive log table exists";
} else {
    echo "✗ Database connection failed";
}
?>

Expected: Both messages success
```

---

## 📋 Deployment Checklist

### Pre-Launch Verification (Run Verification Checklist First!)
```
CRITICAL: Complete VERIFICATION_CHECKLIST.md before deploying
This includes:
- Database initialization verification
- Dashboard page test
- Book management test
- Archive functionality test
- Restore functionality test
- Archive history verification
- Settings/Save Changes test
- User management test
- Responsiveness test
- Error handling test
- Security test
```

### File Verification
```
New Files Must Exist:
✓ /admin/archive_db_init.php (9KB)
✓ /admin/archive_operations.php (5KB)
✓ /admin/ArchiveHistory.php (12KB)
✓ /admin/ArchivedBooks.php (10KB)
✓ /css/admin-design-system.css (35KB)
✓ /css/admin-utilities.css (25KB)

Modified Files Saved:
✓ /admin/admindashboard.php (updated)
✓ /admin/AdminBookEdit.php (updated)
✓ /admin/AdminUserPage.php (updated)
✓ /admin/SettingAdmin.php (updated)

Documentation Files:
✓ /ARCHIVE_SYSTEM_IMPLEMENTATION.md
✓ /ADMIN_PANEL_QUICK_GUIDE.md
✓ /ADMIN_PANEL_TEST_REPORT.md
✓ /ADMIN_PANEL_COMPLETE_SUMMARY.md
✓ /VERIFICATION_CHECKLIST.md
```

---

## 🔒 Security Hardening

### Database Security
```sql
-- Verify prepared statements are used (check PHP code):
SELECT COUNT(*) FROM archive_log;

-- Verify foreign keys are in place:
ALTER TABLE archive_log 
  ADD CONSTRAINT fk_archive_book 
  FOREIGN KEY (book_id) REFERENCES books(id);

-- Verify indexes for performance:
CREATE INDEX idx_archive_book ON archive_log(book_id);
CREATE INDEX idx_archive_admin ON archive_log(admin_id);
CREATE INDEX idx_archive_date ON archive_log(action_date);
```

### File Permissions (Linux/Mac)
```bash
# Restrict archive operations API (executable only by web server)
chmod 640 admin/archive_operations.php

# Readable by all
chmod 644 admin/ArchiveHistory.php
chmod 644 admin/ArchivedBooks.php

# CSS readable
chmod 644 css/admin-design-system.css
chmod 644 css/admin-utilities.css

# Verify
ls -la admin/archive*
```

### File Permissions (Windows)
```
Right-click file > Properties > Security > Edit
Grant Read & Execute to: IUSR, IIS_IUSRS
Grant Modify to: Administrator only
```

---

## 🧪 Post-Deployment Testing

### Test 1: Quick Smoke Test
```
Run through these in 5 minutes:
1. Admin Dashboard loads ✓
2. Book edit page shows books ✓
3. Archive button appears ✓
4. Settings form loads ✓
5. Save Changes works ✓
6. Archive History loads ✓
7. No console errors (F12) ✓
```

### Test 2: Archive Workflow
```
1. Archive a test book
   - Button click ✓
   - Dialog appears ✓
   - Success message ✓
   - Book disappears ✓
   - Archive log entry created ✓

2. Restore that book
   - Go to ArchivedBooks.php ✓
   - Restore button appears ✓
   - Dialog appears ✓
   - Success message ✓
   - Book reappears ✓
   - Restore log entry created ✓
```

### Test 3: Forms
```
1. Account Settings Save
   - Update full name ✓
   - Click Save ✓
   - Success message ✓
   - Database updates ✓

2. Personal Information Save
   - Update birth date ✓
   - Update contact ✓
   - Click Save ✓
   - Success message ✓
   - Database updates ✓
```

### Test 4: Error Scenarios
```
1. Invalid Form Data
   - Empty required field ✓
   - Validation error shown ✓
   - Form doesn't submit ✓

2. Network Issues
   - Throttle network to Slow 3G ✓
   - Try archive ✓
   - Loading indicator shown ✓
   - Success after wait ✓

3. Session Expiry
   - Clear cookies ✓
   - Try to access page ✓
   - Redirected to login ✓
```

---

## 📊 Monitoring & Maintenance

### Daily Monitoring
```
Check the following daily:
1. Archive History page - Any unusual patterns?
2. Database archive_log table - Growing normally?
3. Error logs - Any PHP errors?
4. User feedback - Any issues reported?
```

### Weekly Maintenance
```
1. Backup database:
   mysqldump -u root -p digital_library > backup_$(date +%Y%m%d).sql

2. Review archive operations:
   SELECT COUNT(*) as total_archives FROM archive_log 
   WHERE action = 'Archived' AND DATE(action_date) = DATE(NOW());

3. Check restored books:
   SELECT COUNT(*) as total_restores FROM archive_log 
   WHERE action = 'Restored' AND DATE(action_date) = DATE(NOW());

4. Clear old logs (optional - keep 1 year):
   DELETE FROM archive_log WHERE action_date < DATE_SUB(NOW(), INTERVAL 1 YEAR);
```

### Monthly Audits
```
1. Full archive history export:
   SELECT * FROM archive_log ORDER BY action_date DESC;

2. Compliance check:
   - All archives have logged reasons ✓
   - All archives properly attributed to admin ✓
   - No gaps in timestamps ✓

3. Database health:
   ANALYZE TABLE archive_log;
   OPTIMIZE TABLE archive_log;
```

---

## 🆘 Troubleshooting During Deployment

### If Database Init Fails
```
1. Check MySQL is running:
   systemctl status mysql (Linux)
   Services.msc (Windows)

2. Check dbcon.php connects:
   Run test script above

3. Check permissions:
   GRANT ALL ON database.* TO 'user'@'localhost';

4. Manual initialization:
   ALTER TABLE books ADD COLUMN archived_at TIMESTAMP NULL;
   ALTER TABLE books ADD COLUMN archived_by INT NULL;
   ALTER TABLE books ADD COLUMN archive_reason VARCHAR(500) NULL;
   
   CREATE TABLE archive_log (
     id INT AUTO_INCREMENT PRIMARY KEY,
     book_id INT NOT NULL,
     book_title VARCHAR(255) NOT NULL,
     admin_id INT NOT NULL,
     admin_email VARCHAR(100),
     action VARCHAR(50) NOT NULL,
     reason VARCHAR(500),
     action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
```

### If Archive Button Doesn't Work
```
1. Check JavaScript console (F12):
   - Any errors about archive function?
   - Any errors about Swal or SweetAlert2?

2. Verify archive_operations.php:
   - File exists and is readable?
   - PHP syntax correct?

3. Test with curl:
   curl -X POST -F "action=archive" -F "book_id=1" \
        http://localhost/admin/archive_operations.php

4. Check database update:
   SELECT * FROM books WHERE id = 1;
```

### If Save Changes Not Working
```
1. Check form method is POST:
   <form method="POST" action="">

2. Check submit button type:
   <button type="submit" name="save_changes">Save Changes</button>

3. Check PHP handler exists:
   Check if $_POST is being processed

4. Check database updates:
   Query table to verify data changed

5. Console errors (F12):
   Look for JavaScript errors
```

### If Restore Not Working
```
1. Verify archived book data:
   SELECT * FROM books WHERE archived_at IS NOT NULL;

2. Check restore button appears:
   Go to ArchivedBooks.php
   Check for archived books

3. Test restore manually:
   curl -X POST -F "action=restore" -F "book_id=X" \
        http://localhost/admin/archive_operations.php

4. Check log entry created:
   SELECT * FROM archive_log WHERE book_id = X ORDER BY action_date DESC;
```

---

## 📈 Performance Optimization

### Database Optimization
```sql
-- After first week of use, optimize:
ANALYZE TABLE archive_log;
OPTIMIZE TABLE archive_log;

-- Check indexes are working:
EXPLAIN SELECT * FROM archive_log WHERE book_id = 1;
EXPLAIN SELECT * FROM archive_log WHERE admin_id = 1;
EXPLAIN SELECT * FROM archive_log WHERE action_date > DATE_SUB(NOW(), INTERVAL 7 DAY);
```

### Cache Headers
```php
// Add to archive_operations.php for performance:
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
```

### Query Performance
```php
// Log slow queries in dbcon.php:
// Set slow query threshold to 1 second
SET SESSION long_query_time = 1;
SET SESSION log_slow_admin_statements = ON;

// Review slow queries:
SELECT * FROM mysql.slow_log LIMIT 10;
```

---

## ✅ Post-Deployment Sign-Off

```
Date: _______________
Deployed By: _______________
Verified By: _______________

Checklist:
[ ] Database initialized successfully
[ ] All files deployed and accessible
[ ] Smoke tests passed (5 tests)
[ ] Archive workflow tested (both directions)
[ ] Forms tested and working
[ ] No console errors
[ ] No PHP errors in logs
[ ] All SweetAlert2 modals displaying
[ ] Mobile responsiveness verified
[ ] Security checks passed
[ ] Monitoring setup complete

Issues Found: 
_________________________________________________

Deployment Status: ✅ SUCCESS / ⚠️ WITH ISSUES / ❌ FAILED

Notes:
_________________________________________________
```

---

## 🔗 Quick Reference Links

- Admin Dashboard: `/admin/admindashboard.php`
- Book Management: `/admin/AdminBookEdit.php`
- Archive History: `/admin/ArchiveHistory.php`
- Archived Books: `/admin/ArchivedBooks.php`
- Settings: `/admin/SettingAdmin.php`
- User Management: `/admin/AdminUserPage.php`
- Database Init: `/admin/archive_db_init.php`

---

## 📞 Support

**For Technical Issues:**
1. Check VERIFICATION_CHECKLIST.md first
2. Review troubleshooting section above
3. Check browser console (F12)
4. Check PHP error logs
5. Contact database admin

**For Feature Questions:**
1. Read ADMIN_PANEL_QUICK_GUIDE.md
2. Check ADMIN_PANEL_TEST_REPORT.md
3. Review ARCHIVE_SYSTEM_IMPLEMENTATION.md

---

**Deployment Guide Version:** 1.0
**Last Updated:** May 26, 2026
**Status:** Ready for Production ✅

