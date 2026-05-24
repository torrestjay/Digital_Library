<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Password Modal</title>
  <link rel="stylesheet" href="../css/Create-Password-Modal.css">
</head>
<body>
  <main class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="createPasswordTitle" aria-describedby="createPasswordDesc">
    <section class="modal-box">
      <h2 id="createPasswordTitle">Create Password</h2>
      <p class="info-text" id="createPasswordDesc">Use a strong password with at least 8 characters, one uppercase letter, and one number.</p>
      <form>
        <div class="form-group">
          <label for="new-password">New Password</label>
          <input type="password" id="new-password" name="new-password" autocomplete="new-password" required>
        </div>
        <div class="form-group">
          <label for="confirm-password">Confirm Password</label>
          <input type="password" id="confirm-password" name="confirm-password" autocomplete="new-password" required>
        </div>
        <div class="button-group">
          <button type="submit" class="submit-btn">Save Password</button>
          <button type="button" class="cancel-btn" onclick="history.back()">Cancel</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
