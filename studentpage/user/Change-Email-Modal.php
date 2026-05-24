<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Change Email Modal</title>
  <link rel="stylesheet" href="../css/Change-Email-Modal.css" />
</head>
<body>
  <main class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="changeEmailTitle" aria-describedby="changeEmailDesc">
    <section class="modal-box">
      <h2 id="changeEmailTitle">Change Email</h2>
      <p class="info-text" id="changeEmailDesc">
        You registered your account with <strong>danieljohn@gmail.com</strong>. If this is incorrect, update it below.
      </p>
      <form>
        <div class="form-group">
          <label for="new-email">New Email</label>
          <input type="email" id="new-email" name="new-email" autocomplete="email" inputmode="email" required />
        </div>
        <div class="form-group">
          <label for="confirm-email">Confirm Email</label>
          <input type="email" id="confirm-email" name="confirm-email" autocomplete="email" inputmode="email" required />
        </div>
        <div class="form-group">
          <label for="password">Confirm Password</label>
          <input type="password" id="password" name="password" autocomplete="current-password" required />
        </div>
        <div class="button-group">
          <button type="submit" class="submit-btn">Save Email</button>
          <button type="button" class="cancel-btn" onclick="history.back()">Cancel</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
