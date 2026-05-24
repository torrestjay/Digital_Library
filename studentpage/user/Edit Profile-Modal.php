<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile Modal</title>
  <link rel="stylesheet" href="../css/Edit Profile-Modal.css">
</head>
<body>
  <main class="modal-overlay" role="dialog" aria-modal="true" aria-labelledby="editProfileTitle" aria-describedby="editProfileDesc">
    <section class="edit-profile-modal">
      <h2 id="editProfileTitle" style="margin-bottom: 10px; color: #0e3a5d;">Edit Profile</h2>
      <p class="modal-info" id="editProfileDesc">
        Your pronouns, bio, and location are visible to others. Share only what you are comfortable with.
        <a href="support.php">Learn more about safe sharing.</a>
      </p>
      <form class="edit-profile-form">
        <div class="form-group">
          <label for="pronouns">Pronouns</label>
          <input type="text" id="pronouns" name="pronouns" placeholder="e.g. she/her, they/them" autocomplete="off">
        </div>
        <div class="form-group">
          <label for="about">About</label>
          <textarea id="about" name="about" placeholder="Write something about yourself..."></textarea>
        </div>
        <div class="form-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" placeholder="City, Country" autocomplete="address-level2">
        </div>
        <div class="form-buttons">
          <button type="submit" class="save-btn">Save Changes</button>
          <button type="button" class="cancel-btn" onclick="history.back()">Cancel</button>
        </div>
      </form>
    </section>
  </main>
</body>
</html>
