<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Password Modal</title>
  <link rel="stylesheet" href="../css/Create-Password-Modal.css">
</head>
<body>

  <div class="modal-overlay">
    <div class="modal-box">
      <h2>Create Password</h2>

      <form>
        <div class="form-group">
          <label for="new-password">New Password</label>
          <input type="password" id="new-password" name="new-password" required>
        </div>

        <div class="form-group">
          <label for="confirm-password">Confirm Password</label>
          <input type="password" id="confirm-password" name="confirm-password" required>
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
