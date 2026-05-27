# 🧪 Testing Checklist - Quick Verification

Use this checklist to verify the complete workflow is working.

---

## ✅ Pre-Testing Setup

**Before you start testing, ensure:**
- [ ] XAMPP is running (Apache + MySQL)
- [ ] Database: `Digital_Library` exists
- [ ] You have admin login credentials
  - Email: testadmin@test.com
  - Password: password123
- [ ] You have a student/user login
  - Email: Use any existing user or create one
  - Password: Test123!

---

## 🧪 Test 1: Verify Admin Dashboard Access

**Steps:**
1. Go to: `http://localhost/Digital_Library/studentpage/admin/admindashboard.php`
2. Log in with admin credentials (testadmin@test.com)

**Expected:**
- [ ] Page loads without errors
- [ ] Dashboard displays
- [ ] You see admin navigation menu

---

## 🧪 Test 2: Check ManageBorrows.php

**Steps:**
1. Go to: `http://localhost/Digital_Library/studentpage/admin/ManageBorrows.php`

**Expected:**
- [ ] Page loads
- [ ] Shows 4 statistics cards (Available, Unavailable, Pending, Active)
- [ ] Shows "Pending Requests" section
- [ ] Shows "Active Loans" section (may be empty)
- [ ] All buttons are visible and clickable

---

## 🧪 Test 3: Verify Book Availability

**Steps:**
1. Go to: `http://localhost/Digital_Library/studentpage/user/librarypage.php`
2. Log in as a student user
3. Look at the books grid

**Expected:**
- [ ] All books show with cover images
- [ ] All books show "AVAILABLE" badge (green)
- [ ] Search/filter works
- [ ] No books show as "unavailable"

---

## 🧪 Test 4: User Borrows a Book

**Steps:**
1. On library page, find any book
2. Click "Borrow" button
3. Select return date (default is fine)
4. Click "Submit"

**Expected:**
- [ ] Success message appears
- [ ] Message says: "Borrow request submitted! Waiting for admin approval."
- [ ] Redirects to Borrowed Books page
- [ ] Book appears in list with status "Pending"
- [ ] Book shows a "Waiting for approval" indicator

---

## 🧪 Test 5: Admin Approves Request

**Steps:**
1. Log in as admin
2. Go to: `/admin/ManageBorrows.php`
3. Find the pending request you just created
4. Click "Approve" button
5. Confirm in the dialog

**Expected:**
- [ ] Request item updates (or disappears from pending section)
- [ ] Success notification appears: "✓ Approved borrow request for: [Book Title]"
- [ ] Request moves to "Active Loans" section
- [ ] Database shows status='borrowed'

**Database check:**
```sql
SELECT user_id, book_id, status FROM borrowed_books 
WHERE status='borrowed' ORDER BY id DESC LIMIT 1;
```
Should show: status = 'borrowed'

---

## 🧪 Test 6: Book is Now Readable by User

**Steps:**
1. Log in as the student user
2. Go to: `http://localhost/Digital_Library/studentpage/user/borrowed-books.php`
3. Find the book you just borrowed

**Expected:**
- [ ] Book now shows with status "Borrowed" (not "Pending")
- [ ] "Read" button is visible and clickable
- [ ] Click "Read" → Opens the book reader

---

## 🧪 Test 7: User Can Read the Book

**Steps:**
1. Click "Read" on the borrowed book

**Expected:**
- [ ] Book content loads in reader
- [ ] Pages are viewable
- [ ] Navigation works (next/prev page)
- [ ] No error messages
- [ ] URL contains `/read.php?book_id=XX`

---

## 🧪 Test 8: Admin Rejects a Request

**Steps:**
1. Have another user create a borrow request
2. Go to `/admin/ManageBorrows.php` as admin
3. Click "Reject" on a pending request
4. Confirm in dialog

**Expected:**
- [ ] Request deleted from "Pending Requests"
- [ ] Success message: "✓ Rejected borrow request"
- [ ] Request disappears immediately
- [ ] Book availability unchanged

---

## 🧪 Test 9: Verify System Health

**Steps:**
1. Go to: `http://localhost/Digital_Library/studentpage/admin/verify_system.php`

**Expected:**
- [ ] Page loads
- [ ] Shows 8 test results
- [ ] At least 6-8 tests show "✓ PASS"
- [ ] No critical failures

**Tests that should pass:**
- [ ] Book availability system
- [ ] Borrow tracking system  
- [ ] Pending requests system
- [ ] User pages accessible
- [ ] Admin pages accessible

---

## 🧪 Test 10: Archive Button Still Works

**Steps:**
1. Go to admin book management
2. Create or find a test book
3. Try to delete/archive it
4. Confirm

**Expected:**
- [ ] Book deletes from database
- [ ] Success message appears
- [ ] Book no longer in system

---

## 📊 Summary Results

### Statistics Check

After running tests, check database:

```sql
SELECT 
  'Total Books' as metric, COUNT(*) as count FROM books
UNION ALL
SELECT 'Available Books', COUNT(*) FROM books WHERE availability > 0
UNION ALL
SELECT 'Pending Requests', COUNT(*) FROM borrowed_books WHERE status='pending'
UNION ALL
SELECT 'Active Borrows', COUNT(*) FROM borrowed_books WHERE status='borrowed' AND return_date IS NULL
UNION ALL
SELECT 'Completed Returns', COUNT(*) FROM borrowed_books WHERE return_date IS NOT NULL;
```

**Expected output:**
```
Total Books: 12 (or less if you deleted some)
Available Books: 12 (or less)
Pending Requests: 0 or higher (depends on tests)
Active Borrows: 1 or higher (from your approvals)
Completed Returns: 17 (existing data)
```

---

## ✅ All Tests Passing?

If all tests above pass, you're ready to go live!

**The workflow is:**
✅ User can request books  
✅ Admin can approve requests  
✅ Admin can reject requests  
✅ User can read approved books  
✅ System tracks everything  
✅ All books are available  

---

## ⚠️ Troubleshooting

### Issue: Page shows "Unauthorized"
**Fix:**
1. Make sure you're logged in as admin
2. Check that user role = 'admin' in database
3. Clear browser cache

### Issue: Pending requests not showing
**Fix:**
1. Check database: `SELECT * FROM borrowed_books WHERE status='pending';`
2. If empty, create a test borrow request
3. Refresh the admin page

### Issue: Approve button doesn't work
**Fix:**
1. Check browser console (F12) for errors
2. Verify database connectivity
3. Try rejecting instead to test if forms work
4. Check server error logs

### Issue: Book not showing as approved
**Fix:**
1. Refresh the page
2. Check database for status='borrowed'
3. Look at Active Loans section instead of Pending

### Issue: User can't read approved book
**Fix:**
1. Make sure status='borrowed' in database
2. Make sure return_date IS NULL
3. Check user_id and book_id match
4. Try logging out and back in

---

## 🎉 Success Criteria

You'll know it's working when:
1. ✅ All 12 books show as available
2. ✅ User can click "Borrow" 
3. ✅ Admin sees pending request
4. ✅ Admin clicks "Approve"
5. ✅ Request moves to "Active Loans"
6. ✅ User can now click "Read"
7. ✅ Book opens in reader
8. ✅ Everything is logged in database

**If all of those work, the system is complete!**

---

## 📝 Notes for Testing

- **Test with real data**: Use actual book and user accounts
- **Test multiple requests**: Approve several to see system handle multiple
- **Test rejections**: Make sure reject button works
- **Test timing**: Check that dates are calculated correctly
- **Test UI**: Verify buttons and messages are user-friendly

---

**Ready to start testing? Begin with Test 1 above!**
