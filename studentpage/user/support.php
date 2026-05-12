<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Support Page</title>
  <link rel="stylesheet" href="../css/support.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
    *{
      
    font-family: 'Poppins', sans-serif;

    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
      <div class="logo" onclick="toggleSidebar()">
        <img src="../Images/logo.png" alt="Readly Logo" />
      </div>
      <nav class="nav">
       <a href="homepage.php" onclick="toggleSidebar()"><img class="icon" src="../Images\dashboard.png" alt="Dashboard Icon" /><span>Dashboard</span></a>
        <a href="librarypage.php" onclick="toggleSidebar()"><img class="icon" src="../Images\Library.png" alt="Library Icon" /><span>Library</span></a>
        <a href="Book-Details.php" onclick="toggleSidebar()"><img class="icon" src="../Images\Details.png" alt="Details Icon" /><span>Book Details</span></a>
        <a href="track&record.php" onclick="toggleSidebar()"><img class="icon" src="../Images\Track.png" alt="Track Icon" /><span>Track and Record</span></a>
        <a href="support.php" onclick="toggleSidebar()"><img class="icon" src="../Images\Support.png" alt="Support Icon" /><span>Support Page</span></a>
        <a href="setting.php" onclick="toggleSidebar()"><img class="icon" src="../Images\settings.png" alt="Settings Icon" /><span>Account Settings</span></a>       
      </nav>
      <div class="sign-out">
      <a href="../logout.php"><img class="icon" src="../Images\signout.png" alt="Signout Icon" /><span>Sign Out</span></a>
      </div>
    </aside>

    
    <!-- Main Content -->
    <main class="main-content help-center">
      <!-- Header -->
      <header class="header">
        <div class="spacer"></div>
        <div class="header-icons">
          <a href="setting.php"><img class="icon" src="../Images/profile.png"></a> 
        </div>
      </header>

      <!-- Help Center Content -->
      <section class="help-search">
        <h1>HELP CENTER</h1>
        <div class="help-search-box">
          <div class="search-left">
            <img src="../Images/person.png" alt="Help Image" />
          </div>
          <div class="search-middle">
            <h2>How can we help?</h2>
          </div>
          <div class="search-right">
            <img src="../Images/agent.png" alt="Agent Illustration" />
          </div>
        </div>
      </section>

      <!-- FAQ Section -->
      <section class="faq-section">
        <h2>Frequently asked question</h2>
        <div class="accordion">
          <details>
            <summary>How do I track the books I’ve borrowed?</summary>
            <p>Go to Track & Record > Borrowed Books tab to view your list.</p>
          </details>
          <details>
            <summary> Can I borrow multiple books at the same time??</summary>
            <p>Yes, you can! But there may be a limit depending on your user role. Most users can borrow up to 3 books at a time.</p>
          </details>
          <details>
            <summary>How do I know if my borrow request was approved?</summary>
            <p>If a book is overdue, you may temporarily lose access to new borrowing until the overdue item is returned. Repeated late returns may affect your borrowing privileges.</p>
          </details>
          <details>
            <summary>How long can I borrow a book for?</summary>
            <p>You can borrow a book for up to 14 days. You will get reminders before the due date so you can return or renew it on time.</p>
          </details>
          <details>
            <summary>What happens if I don’t return the book on time?</summary>
            <p>If a book is overdue, you may temporarily lose access to new borrowing until the overdue item is returned. Repeated late returns may affect your borrowing privileges.</p>
          </details>
          <details>
            <summary>Can I suggest a book to be added to the library?</summary>
            <p>Absolutely! Go to the “Support” or “Feedback” page and submit your book suggestion. Include the title, author, and a brief description.</p>
          </details>
          <details>
            <summary> Can I read books offline?</summary>
            <p>Currently, books can only be read within the digital library system online. Offline access is not yet supported.</p>
          </details>
          <!-- Add more questions here if needed -->
        </div>
      </section>

      <!-- Contact Section -->
      <section class="contact-section">
        <div class="contact-form">
          <h3>Ask a different question</h3>
          <p>Feel free to contact us and send a message — we’re here to help you out!</p>
          <input type="text" placeholder="Name" />
          <input type="email" placeholder="Email" />
          <textarea placeholder="Message"></textarea>
          <button>SUBMIT</button>
        </div>
        <div class="contact-options">
          <h4>Call us</h4>
          <p>Need help? Give us a call — we’re ready to assist you!</p>
          <h4>LIVE CHAT</h4>
          <p>Chat with us live and get the help you need right away!</p>
          <h4>Connect With Us</h4>
          <p>Reach out online and we’ll get back to you as soon as possible!</p>
        </div>
      </section>
    </main>
  </div>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById("sidebar");
      sidebar.classList.toggle("collapsed");
    }
  </script>
</body>
</html>
