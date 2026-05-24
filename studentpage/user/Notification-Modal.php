<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notification Modal</title>
  <link rel="stylesheet" href="../css/Notification-Modal.css">
</head>
<body>
  <main class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="notificationTitle" aria-describedby="notificationDesc">
    <section class="notification-modal">
      <h2 id="notificationTitle">Notifications</h2>
      <p class="intro-text" id="notificationDesc">
        You will receive notifications when your requests or submissions are reviewed.
      </p>
      <!-- Approved Book 1 -->
      <div class="notification-item">
        <img src="../Images/book1.jpg" alt="Book 1">
        <div class="notification-text">
          <p><strong>“The Book”</strong> is available.<br>Tap to borrow now.</p>
          <button type="button" class="borrow-btn">Borrow Now</button>
        </div>
      </div>
      <!-- Approved Book 2 -->
      <div class="notification-item">
        <img src="../Images/book2.jpg" alt="Book 2">
        <div class="notification-text">
          <p><strong>“Another Book”</strong> is available.<br>Tap to borrow now.</p>
          <button type="button" class="borrow-btn">Borrow Now</button>
        </div>
      </div>
      <!-- Rejected Notification -->
      <div class="rejection-box">
        <div class="rejection-content">
          <img src="../Images/Book4.png" alt="Rejected" class="rejected-img">
          <div>
            <p>Oops! Your borrow request wasn’t approved.<br>Want to try submitting it again?</p>
            <button type="button" class="submit-btn">Submit Again</button>
          </div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
