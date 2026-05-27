# ✅ Digital Library Admin Guide - Complete Workflow

## 🎯 What Changed

The book borrowing workflow now works in **2 steps** instead of automatic approval:

```
Step 1: User requests to borrow → Creates request with status='pending'
Step 2: Admin approves → Status changes to 'borrowed' + availability decreases
```

---

## 📖 Complete Workflow

### **User Side**
```
1. User goes to Library (/user/librarypage.php)
2. User clicks "Borrow" on a book
3. Book added to "Pending Requests" list
4. User sees message: "Borrow request submitted! Waiting for admin approval"
5. Book availability is NOT decreased yet
6. User waits for admin approval
```

### **Admin Side**
```
1. Admin goes to Manage Borrows (/admin/ManageBorrows.php)
2. Admin sees all pending requests
3. Admin clicks "Approve" button
4. Automatically:
   - Status changes to 'borrowed'
   - Book availability decreases by 1
   - User can now read the book
5. Admin can also "Reject" to deny the request
```

### **User Reading**
```
1. User goes to Borrowed Books (/user/borrowed-books.php)
2. Can only see books with status='borrowed'
3. User clicks "Read" to open book reader
4. Book opens in read.php for viewing
```

---

## 🔑 Important URLs

### Admin Pages
| Page | URL | Purpose |
|------|-----|---------|
| **Main Dashboard** | `/admin/admindashboard.php` | Overview |
| **Manage Borrows** ⭐ | `/admin/ManageBorrows.php` | **NEW - Approve/Reject requests** |
| **Book Management** | `/admin/AdminBookEdit.php` | Add/edit/delete books |
| **System Maintenance** | `/admin/system_maintenance.php` | Diagnostics |
| **Verify System** | `/admin/verify_system.php` | Test everything |

---

## 🎓 How to Use the Admin Panel

### **To Approve a Borrow Request**

1. Go to: `/admin/ManageBorrows.php`
2. See section: "Pending Requests"
3. For each request, you'll see:
   - Book cover image
   - Book title & author
   - User who requested it
   - Request date & time
   - User's email
4. Click **"Approve"** button
5. Confirm in the dialog
6. ✅ Request approved automatically

**What happens after approval:**
- Status changes from `pending` → `borrowed`
- Book availability decreases by 1
- User can now read the book
- Book moves to "Active Loans" section

### **To Reject a Borrow Request**

1. Same steps, but click **"Reject"** instead
2. Confirm in the dialog
3. ✅ Request deleted
4. User will see the rejected request is gone
5. Book availability stays the same

---

## 📊 Admin Dashboard Sections

### **Statistics Cards** (Top of page)
- **Books Available**: Total books with availability > 0
- **Books Unavailable**: Books with availability = 0
- **Pending Requests**: Waiting for admin approval
- **Active Loans**: Books currently borrowed by users

### **Pending Requests Section**
Shows all requests waiting for action with:
- Book cover image
- Book details
- User information
- Approve/Reject buttons

### **Active Loans Section**
Shows all approved loans with:
- Book title
- Who borrowed it
- Borrow date
- Due date
- Status (ACTIVE, DUE SOON, OVERDUE)

---

## 🚨 Important Notes

### All Books are Now Available
- We've set `availability = 1` for ALL books
- Users can request any book
- Don't worry about availability - there's plenty

### Approval Process is Simple
- Click "Approve" → Done
- No need to manually adjust availability
- It's automatic!

### Rejected Requests
- If you reject a request, the request is deleted
- User doesn't get notified (consider improving this)
- User can request again anytime

---

## 🔍 Monitoring Active Loans

In the "Active Loans" table, you can see:
- **ACTIVE**: Books borrowed, plenty of time left
- **DUE SOON**: Books due in less than 3 days
- **OVERDUE**: Books past due date (should have been returned)

---

## 🐛 Troubleshooting

### **Issue: User can't see their borrowed book**
**Reason**: Book status is still 'pending' (not approved yet)
**Solution**: Go to ManageBorrows.php and click "Approve"

### **Issue: Pending requests not showing**
**Reason**: They might be from yesterday or have been rejected
**Solution**: 
1. Check database: `SELECT * FROM borrowed_books WHERE status='pending';`
2. If empty, no pending requests (all handled)

### **Issue: User says they can't read a borrowed book**
**Reason**: Book might not have status='borrowed'
**Solution**: 
1. Check database for their borrow record
2. If status='pending', approve it
3. If status='rejected', they need to request again

### **Issue: Book availability stuck at 0**
**Reason**: Multiple users have borrowed it
**Solution**: 
1. Check how many users have it (status='borrowed')
2. When they return it, availability goes back up
3. Or manually set it: `UPDATE books SET availability = 1 WHERE id = XX;`

---

## 💡 Pro Tips

1. **Monitor Overdue Books**: Check "Active Loans" table regularly for overdue items
2. **Quick Batch Approvals**: You can approve multiple requests quickly by clicking one after another
3. **Keep Availability High**: If availability drops too low, books will seem "unavailable" to users
4. **Check Statistics**: Top stats cards show overall system health

---

## 📋 Quick Commands for Admins

If you need to check things in the database:

### See all pending requests
```sql
SELECT u.fullname, b.title, bb.borrow_date 
FROM borrowed_books bb
JOIN users u ON bb.user_id = u.id
JOIN books b ON bb.book_id = b.id
WHERE bb.status='pending'
ORDER BY bb.borrow_date ASC;
```

### See all active loans
```sql
SELECT u.fullname, b.title, bb.due_date
FROM borrowed_books bb
JOIN users u ON bb.user_id = u.id
JOIN books b ON bb.book_id = b.id
WHERE bb.status='borrowed' AND bb.return_date IS NULL
ORDER BY bb.due_date ASC;
```

### Check book availability
```sql
SELECT title, availability FROM books ORDER BY availability DESC;
```

### Set all books to available (if needed)
```sql
UPDATE books SET availability = 1;
```

---

## ✅ Daily Routine for Admin

1. **Morning**: 
   - Go to `/admin/ManageBorrows.php`
   - Check "Pending Requests" section
   - Approve valid requests
   - Reject any suspicious ones

2. **Throughout day**:
   - Keep admin page open
   - New requests will appear
   - Approve as they come in

3. **End of day**:
   - Check "Active Loans" for overdue items
   - Note any books past due date
   - Plan follow-ups for next day

---

## 🎉 You're All Set!

Everything is ready. Just go to `/admin/ManageBorrows.php` and start approving requests!

**Questions?** Check the technical docs or database queries above.
