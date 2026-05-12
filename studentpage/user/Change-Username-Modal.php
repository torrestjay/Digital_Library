<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Username Modal</title>
  <link rel="stylesheet" href="../css/Change-Username-Modal.css">
</head>
<body>

  <div class="modal-overlay">
    <div class="modal-box">
      <h2>Change Username</h2>
      <p class="info-text">
        Your username is important, here are a few things you need to know before you change it:
      </p>
      <ul class="info-list">
        <li>It takes up to 24 hours for all changes to take effect. Your profile and stories may be inaccessible during this time.</li>
        <li>Your user profile and widget links will change and the old ones will no longer work.</li>
        <li>You will need to re-login using your new username.</li>
      </ul>

      <form>
        <div class="form-group">
          <label for="new-username">New Username</label>
          <input type="text" id="new-username" name="new-username" required>
        </div>
        <div class="form-group">
          <label for="password">Confirm Password</label>
          <input type="password" id="password" name="password" required>
        </div>

        <div class="button-group">
          <button type="submit" class="submit-btn">SUBMIT</button>
          <button type="button" class="cancel-btn">CANCEL</button>
        </div>
      </form>
    </div>
  </div>

</body>
</html>
