<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile Modal</title>
  <link rel="stylesheet" href="../css/Edit Profile-Modal.css">
</head>
<body>

  <div class="modal-overlay">
    <div class="edit-profile-modal">
      <p class="modal-info">
        Your pronouns, bio, and location will be visible to others.
        Share only what you're comfortable with.
        <a href="support.php">Learn more about safe sharing.</a>
      </p>

      <form class="edit-profile-form">
        <div class="form-group">
          <label for="pronouns">Pronouns</label>
          <input type="text" id="pronouns" name="pronouns" placeholder="e.g. she/her, they/them">
        </div>

        <div class="form-group">
          <label for="about">About</label>
          <textarea id="about" name="about" placeholder="Write something about yourself..."></textarea>
        </div>

        <div class="form-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" placeholder="City, Country">
        </div>

        <div class="form-buttons">
          <button type="submit" class="save-btn">Save Changes</button>
          <button type="button" class="cancel-btn">Cancel</button>
        </div>
      </form>
    </div>
  </div>

</body>
</html>
