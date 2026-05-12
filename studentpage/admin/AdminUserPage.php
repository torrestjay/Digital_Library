<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <!-- For .png file -->
  <link rel="icon" href="../Images/logo.png" type="image/png">
  <title>Admin Users Page</title>
  <link rel="stylesheet" href="../css/AdminUserPage.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

    * {
      font-family: 'Poppins', sans-serif;

    }
  </style>
</head>

<body>
  <div class="container">
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()">
        <img src="../Images/logo.png" alt="Readly Logo" />
      </div>
      <nav class="nav">
        <a href="admindashboard.php" onclick="toggleSidebar()"><img class="icon" src="../Images/dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="AdminBookEdit.php" onclick="toggleSidebar()"><img class="icon" src="../Images/BookDetails.png" alt="Book Edit Icon" /><span>Book Edit</span></a>
        <a href="AdminUserPage.php" onclick="toggleSidebar()"><img class="icon" src="../Images/userpage.png" alt="User Page Icon" /><span>User Page</span></a>
        <a href="SettingAdmin.php" onclick="toggleSidebar()"><img class="icon" src="../Images/settings.png" alt="Settings Icon" /><span>Account Settings</span></a>
      </nav>
      <div class="sign-out">
        <a href="../logout.php" onclick="toggleSidebar()"><img class="icon" src="../Images/signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
      </div>
    </aside>

    <main class="main-content">
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <!-- <img class="icon" src="../Images/notif.png"> -->
          <a href="SettingAdmin.php"><img class="icon" src="../Images/profile.png"></a>
        </div>
      </header>

      <div class="users-content">
        <h2 class="section-title">USERS</h2>
        <!-- Add this above your table -->
        <div class="table-filters">
          <div class="filter-group">
            <label for="name-filter">Name:</label>
            <input type="text" id="name-filter" placeholder="Filter by name...">
          </div>
          <div class="filter-group">
            <label for="book-filter">Book:</label>
            <input type="text" id="book-filter" placeholder="Filter by book...">
          </div>
          <div class="filter-group">
            <label for="status-filter">Status:</label>
            <select id="status-filter">
              <option value="">All Statuses</option>
              <option value="pending">Pending</option>
              <option value="borrowed">Borrowed</option>
              <option value="returned">Returned</option>
              <option value="rejected">Rejected</option>
            </select>
          </div>
          <button id="reset-filters" class="filter-btn">Reset Filters</button>
        </div>
        <div class="users-table">
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Borrowed Book</th>
                <th>Borrow Date</th>
                <th>Due Date</th>
                <th>Return Date</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Database connection
              include "../dbcon.php";


              // Join query to fetch users who borrowed books
              $query = "SELECT 
                          u.id, 
                          u.fullname, 
                          u.email, 
                          u.role, 
                          b.title AS book_title,
                          b.id AS book_id,
                          bb.id AS borrow_id,
                          bb.borrow_date,
                          bb.due_date,
                          bb.return_date,
                          bb.status AS borrow_status
                        FROM users u
                        JOIN borrowed_books bb ON u.id = bb.user_id
                        JOIN books b ON bb.book_id = b.id
                        WHERE u.role = 'student'
                        ORDER BY bb.borrow_date DESC";

              $result = $conn->query($query);

              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  echo "<tr data-borrow-id='" . $row['borrow_id'] . "'>
                              <td>" . htmlspecialchars($row['fullname']) . "</td>
                              <td>" . htmlspecialchars($row['email']) . "</td>
                              <td>" . htmlspecialchars($row['book_title']) . "</td>
                              <td>" . htmlspecialchars($row['borrow_date']) . "</td>
                              <td>" . htmlspecialchars($row['due_date']) . "</td>
                              <td>" . htmlspecialchars($row['return_date'] ? $row['return_date'] : 'Not returned') . "</td>
                              <td class='status-cell'>" . htmlspecialchars(ucfirst($row['borrow_status'])) . "</td>
                              <td class='action-cell'>";

                  // Action buttons based on status
                  if ($row['borrow_status'] == 'pending') {
                    echo "<button type='button' class='approve-btn' onclick='approveRequest(" . $row['borrow_id'] . ")'>Approve</button>
                                <button type='button' class='reject-btn' onclick='rejectRequest(" . $row['borrow_id'] . ")'>Reject</button>";
                  } elseif ($row['borrow_status'] == 'borrowed' && !$row['return_date']) {
                    echo "<button type='button' class='return-btn' onclick='markAsReturned(" . $row['borrow_id'] . ", " . $row['book_id'] . ")'>Mark as Returned</button>";
                  } else {
                    echo "-";
                  }

                  echo "</td>
                            </tr>";
                }
              } else {
                echo "<tr><td colspan='9'>No borrowing records found</td></tr>";
              }

              $conn->close();
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
    }

    function approveRequest(borrowId) {
      updateStatus(borrowId, 'borrowed');
    }

    function rejectRequest(borrowId) {
      updateStatus(borrowId, 'rejected');
    }

    function markAsReturned(borrowId, bookId) {
      // First update the status to returned
      updateStatus(borrowId, 'returned', bookId);
    }

    function updateStatus(borrowId, newStatus, bookId = null) {
      // First show confirmation dialog
      let confirmConfig = {
        title: 'Are you sure?',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, proceed!'
      };

      // Customize messages based on action type
      switch (newStatus) {
        case 'borrowed':
          confirmConfig.title = 'Approve Borrow Request?';
          confirmConfig.text = "This will approve the book borrowing request.";
          confirmConfig.icon = 'question';
          break;
        case 'rejected':
          confirmConfig.title = 'Reject Borrow Request?';
          confirmConfig.text = "This will reject the book borrowing request.";
          confirmConfig.icon = 'warning';
          break;
        case 'returned':
          confirmConfig.title = 'Mark as Returned?';
          confirmConfig.text = "This will mark the book as returned and update availability.";
          confirmConfig.icon = 'question';
          break;
        default:
          confirmConfig.text = "You're about to update this record's status.";
          confirmConfig.icon = 'info';
      }

      Swal.fire(confirmConfig).then((result) => {
        if (result.isConfirmed) {
          // User confirmed - proceed with the update
          const formData = new FormData();
          formData.append('borrow_id', borrowId);
          formData.append('new_status', newStatus);
          if (bookId) {
            formData.append('book_id', bookId);
          }

          // Show loading indicator
          Swal.fire({
            title: 'Processing...',
            html: 'Please wait while we update the status',
            allowOutsideClick: false,
            didOpen: () => {
              Swal.showLoading();
            }
          });

          fetch('update_borrow_status.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              Swal.close(); // Close loading dialog

              if (data.success) {
                // Find the row in the table
                const row = document.querySelector(`tr[data-borrow-id="${borrowId}"]`);

                if (row) {
                  // Update status cell
                  const statusCell = row.querySelector('.status-cell');
                  if (statusCell) {
                    statusCell.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
                  }

                  // Update action cell
                  const actionCell = row.querySelector('.action-cell');
                  if (actionCell) {
                    if (newStatus === 'borrowed') {
                      actionCell.innerHTML = `<button class='return-btn' onclick='markAsReturned(${borrowId}, ${bookId})'>Mark as Returned</button>`;
                    } else if (newStatus === 'rejected' || newStatus === 'returned') {
                      actionCell.innerHTML = '-';
                    }
                  }

                  // Update return date if marked as returned
                  if (newStatus === 'returned') {
                    const returnDateCell = row.cells[5]; // 6th cell (0-based index)
                    if (returnDateCell) {
                      const today = new Date();
                      returnDateCell.textContent = today.toISOString().split('T')[0];
                    }
                  }
                }

                // Show success message
                Swal.fire({
                  title: 'Success!',
                  text: 'Status has been updated successfully',
                  icon: 'success',
                  confirmButtonText: 'OK'
                }).then(() => {
                  window.location.reload(); // Refresh to ensure consistency
                });
              } else {
                // Show error message
                Swal.fire({
                  title: 'Error!',
                  text: data.message || 'Failed to update status',
                  icon: 'error',
                  confirmButtonText: 'OK'
                });
              }
            })
            .catch(error => {
              Swal.close(); // Close loading dialog
              console.error('Error:', error);
              Swal.fire({
                title: 'Error!',
                text: 'An error occurred while updating the status.',
                icon: 'error',
                confirmButtonText: 'OK'
              });
            });
        }
      });
    }
  </script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Get filter elements
      const nameFilter = document.getElementById('name-filter');
      const bookFilter = document.getElementById('book-filter');
      const statusFilter = document.getElementById('status-filter');
      const resetBtn = document.getElementById('reset-filters');
      const tableRows = document.querySelectorAll('tbody tr');

      // Filter function
      function applyFilters() {
        const nameValue = nameFilter.value.toLowerCase();
        const bookValue = bookFilter.value.toLowerCase();
        const statusValue = statusFilter.value.toLowerCase();

        tableRows.forEach(row => {
          const name = row.cells[0].textContent.toLowerCase();
          const book = row.cells[2].textContent.toLowerCase();
          const status = row.cells[6].textContent.toLowerCase();

          const nameMatch = name.includes(nameValue);
          const bookMatch = book.includes(bookValue);
          const statusMatch = statusValue === '' || status === statusValue;

          if (nameMatch && bookMatch && statusMatch) {
            row.style.display = '';
            row.classList.add('filter-match');
          } else {
            row.style.display = 'none';
            row.classList.remove('filter-match');
          }
        });
      }

      // Event listeners
      nameFilter.addEventListener('input', applyFilters);
      bookFilter.addEventListener('input', applyFilters);
      statusFilter.addEventListener('change', applyFilters);

      // Reset filters
      resetBtn.addEventListener('click', function() {
        nameFilter.value = '';
        bookFilter.value = '';
        statusFilter.value = '';
        tableRows.forEach(row => {
          row.style.display = '';
          row.classList.remove('filter-match');
        });
      });

      // Initialize filters
      applyFilters();
    });
  </script>
</body>

</html>