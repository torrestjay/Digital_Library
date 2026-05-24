<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Success Modal</title>
  <link rel="stylesheet" href="../css/successful-modal.css">
</head>
<body>
  <main class="success-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="successTitle" aria-describedby="successDesc">
    <section class="success-modal">
      <img src="../Images/successful.png" alt="Success" class="success-icon">
      <h2 id="successTitle">Request Sent</h2>
      <p id="successDesc">Your request is now under review.<br>Kindly wait for approval.</p>
      <button type="button" class="continue-btn" onclick="history.back()">Continue</button>
    </section>
  </main>
</body>
</html>
