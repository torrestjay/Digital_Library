# 🚀 Digital Library - Quick Reference Card

## 📋 What Works Now

✅ **Book Management** - Add books with auto-availability=1  
✅ **Book Browsing** - Users can filter/search available books  
✅ **Borrow Requests** - Users request to borrow books  
✅ **Admin Approval** - Admins approve/reject borrow requests  
✅ **Book Reading** - Users can read borrowed books  
✅ **Book Archiving** - Delete books from library  
✅ **System Diagnostics** - Verify everything is working  

---

## 🔑 Key Admin URLs

```
Verify System Status
→ http://localhost/Digital_Library/studentpage/admin/verify_system.php

Approve Borrow Requests
→ http://localhost/Digital_Library/studentpage/admin/BorrowRequests.php

Fix Book Issues
→ http://localhost/Digital_Library/studentpage/admin/system_maintenance.php

Manage Books
→ http://localhost/Digital_Library/studentpage/admin/AdminBookEdit.php

Main Dashboard
→ http://localhost/Digital_Library/studentpage/admin/admindashboard.php
```

---

## 🔑 Key User URLs

```
Browse Library
→ http://localhost/Digital_Library/studentpage/user/librarypage.php

My Borrowed Books
→ http://localhost/Digital_Library/studentpage/user/borrowed-books.php

User Dashboard
→ http://localhost/Digital_Library/studentpage/user/homepage.php

My Profile
→ http://localhost/Digital_Library/studentpage/user/profile.php
```

---

## 🎯 How to Test Everything

### **Test 1: Verify System** (5 min)
1. Log in as admin
2. Go to: `/admin/verify_system.php`
3. Check that all tests PASS ✓

### **Test 2: Check Availability** (2 min)
1. Go to: `/admin/system_maintenance.php`
2. Check "Available Books" stat
3. If needed, click "Fix Book Availability"

### **Test 3: Complete Borrow Workflow** (10 min)
1. **Add a Book** (as admin):
   - Go to `/admin/AdminBookEdit.php`
   - Click "Add Book"
   - Fill in details and save
   - Verify availability = 1
   
2. **Request to Borrow** (as user):
   - Go to `/user/librarypage.php`
   - Find the book you just added
   - Click "Borrow"
   
3. **Approve Request** (as admin):
   - Go to `/admin/BorrowRequests.php`
   - Click "Approve" on the pending request
   - Verify status changed to "borrowed"
   
4. **Read the Book** (as user):
   - Go to `/user/borrowed-books.php`
   - Should see the book
   - Click "Read" to open it

---

## 💾 Database Quick Commands

### **Check Books Availability**
```sql
SELECT COUNT(*) FROM books WHERE availability > 0;
```

### **Check Pending Requests**
```sql
SELECT * FROM borrowed_books WHERE status='pending';
```

### **Check Active Borrows**
```sql
SELECT * FROM borrowed_books WHERE status='borrowed' AND return_date IS NULL;
```

### **Fix All Unavailable Books** (if needed)
```sql
UPDATE books SET availability = 1 WHERE availability IS NULL OR availability = 0;
```

---

## 🔐 Test Accounts

| Role | Email | Status |
|------|-------|--------|
| Admin | admin@example.com | ✅ Created |
| Admin | testadmin@test.com | ✅ Created (password: password123) |
| Student | Any existing user | ✅ Ready |

---

## ⚡ Critical Files

| File | What | Where |
|------|------|-------|
| AdminBookEdit.php | Add/edit books | `/admin/` |
| BorrowRequests.php | Approve requests | `/admin/` |
| system_maintenance.php | Fix issues | `/admin/` |
| verify_system.php | Test everything | `/admin/` |
| librarypage.php | Browse books | `/user/` |
| borrow.php | Submit requests | `/user/` |
| read.php | Read books | `/user/` |

---

## 🆘 Common Issues & Fixes

### **Issue: "All books unavailable"**
**Fix**: Go to `/admin/system_maintenance.php` → Click "Fix Book Availability"

### **Issue: "Can't see borrow requests"**
**Fix**: 
1. Verify you're logged in as admin
2. Refresh the page
3. Check database: `SELECT * FROM borrowed_books WHERE status='pending';`

### **Issue: "Can't approve request"**
**Fix**:
1. Check browser console (F12) for errors
2. Verify MySQL is running
3. Try clearing browser cache

### **Issue: "Availability didn't decrease after approval"**
**Fix**:
1. Go to `/admin/BorrowRequests.php` and refresh
2. Check database to verify status changed
3. Check MySQL logs for errors

---

## 📊 Expected Behavior

### **When Adding a Book**
- New book gets `availability = 1`
- Book appears in library with green "Available" badge
- Users can immediately borrow it

### **When Requesting to Borrow**
- Request goes into database with `status='pending'`
- Admin sees it in BorrowRequests.php
- Book availability stays at 1 (request pending)

### **When Admin Approves**
- Status changes to `status='borrowed'`
- Book availability decreases by 1
- User can now see it in "Borrowed Books"
- User can click "Read" to view it

### **When User Returns**
- (To be implemented in return_book.php)
- Status changes to `status='returned'`
- Book availability increases by 1

---

## ✅ Your Next Steps

1. **Test the system** using Test Procedure above
2. **Monitor system** using `/admin/verify_system.php`
3. **Fix issues** using `/admin/system_maintenance.php`
4. **Manage requests** using `/admin/BorrowRequests.php`

---

**Everything is ready to use!** 🎉

**Questions?** Check the full documentation in `IMPLEMENTATION_COMPLETE.md`
