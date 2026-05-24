<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Username Modal</title>
  <link rel="stylesheet" href="../css/Change-Username-Modal.css">
</head>
<body>
  <main class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="changeUsernameTitle" aria-describedby="changeUsernameDesc">
    <section class="modal-box">
      <h2 id="changeUsernameTitle">Change Username</h2>
      <p class="info-text" id="changeUsernameDesc">Choose a username that is easy to remember and secure to share.</p>
      <ul class="info-list">
        <li>Changes may take up to 24 hours to fully reflect across your account.</li>
        <li>Old profile links may stop working after the update.</li>
        <li>You will need to sign in again using the new username.</li>
      </ul>
      <form>
        <div class="form-group">
          <label for="new-username">New Username</label>
          <input type="text" id="new-username" name="new-username" autocomplete="username" required>
        </div>
        <div class="form-group">
          <label for="password">Confirm Password</label>
          <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <div class="button-group">
          <button type="submit" class="submit-btn">Save Username</button>
          <button type="button" class="cancel-btn" onclick="history.back()">Cancel</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
