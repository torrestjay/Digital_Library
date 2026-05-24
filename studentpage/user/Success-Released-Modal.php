<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Success Modal</title>
  <link rel="stylesheet" href="../css/Success-Released-Modal.css">
</head>
<body>
  <main class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="releaseSuccessTitle" aria-describedby="releaseSuccessDesc">
    <section class="modal-box">
      <img src="../Images/successful.png" alt="Success" class="check-icon">
      <h2 id="releaseSuccessTitle">Book Added Successfully</h2>
      <p class="book-title" id="releaseSuccessDesc">[BOOK TITLE] is now available in the digital library.</p>
      <div style="margin-top: 20px;">
        <button type="button" class="submit-btn" onclick="history.back()">Continue</button>
      </div>
    </section>
  </main>
</body>
</html>
