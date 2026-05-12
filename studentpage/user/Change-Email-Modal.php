<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Change Email Modal</title>
  <link rel="stylesheet" href="../css/Change-Email-Modal.css" />
</head>
<body>

  <div class="modal-overlay">
    <div class="modal-box">
      <h2>Change Email</h2>
      <p class="info-text">
        You registered your account with <strong>danieljohn@gmail.com</strong>. If this is incorrect, you can change your email here.
      </p>

      <form>
        <div class="form-group">
          <label for="new-email">New Email</label>
          <input type="email" id="new-email" name="new-email" required />
        </div>

        <div class="form-group">
          <label for="confirm-email">Confirm Email</label>
          <input type="email" id="confirm-email" name="confirm-email" required />
        </div>

        <div class="form-group">
          <label for="password">Confirm Password</label>
          <input type="password" id="password" name="password" required />
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
