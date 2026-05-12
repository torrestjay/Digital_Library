<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Notification Modal</title>
  <link rel="stylesheet" href="../css/Notification-Modal.css">
</head>
<body>

  <div class="modal-overlay">
    <div class="notification-modal">
      <h2>Notification</h2>
      <p class="intro-text">
        You’ll receive notifications when your requests or submissions are reviewed.
      </p>

      <!-- Approved Book 1 -->
      <div class="notification-item">
        <img src="../Images/book1.jpg" alt="Book 1">
        <div class="notification-text">
          <p><strong>“The Book”</strong> is available.<br>Tap to borrow now.</p>
          <button class="borrow-btn">Borrow</button>
        </div>
      </div>

      <!-- Approved Book 2 -->
      <div class="notification-item">
        <img src="../Images/book2.jpg" alt="Book 2">
        <div class="notification-text">
          <p><strong>“Another Book”</strong> is available.<br>Tap to borrow now.</p>
          <button class="borrow-btn">Borrow</button>
        </div>
      </div>

      <!-- Rejected Notification -->
      <div class="rejection-box">
        <div class="rejection-content">
          <img src="../Images/Book4.png" alt="Rejected" class="rejected-img">
          <div>
            <p>Oops! Your borrow request wasn’t approved.<br>Want to try submitting it again?</p>
            <button class="submit-btn">SUBMIT</button>
          </div>
        </div>
      </div>

    </div>
  </div>

</body>
</html>
