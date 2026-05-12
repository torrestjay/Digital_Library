<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FAVORITES</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body, html {
      height: 100%;
      font-family: 'Montserrat';
      background-color: #f9f7f4;
    }

    .content {
      padding: 30px 40px;
      background-color: #f5f5f5;
      position: relative;
    }

    .close-button {
      position: absolute;
      top: 20px;
      right: 30px;
      font-size: 24px;
      font-weight: bold;
      color: #333;
      cursor: pointer;
      background: none;
      border: none;
    }

    .dashboard-header h2 {
      font-size: 30px;
      font-weight: bold;
      margin-top: 20px;
      margin-bottom: 30px;
      color: #121111;
    }

    .search-bar {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 20px;
    }

    .search-bar input {
      padding: 5px 15px;
      border: 1px solid #1a1919;
      border-radius: 4px 0 0 4px;
      outline: none;
    }

    .search-bar button {
      padding: 8px;
      background-color: #0e3a5d;
      border: none;
      border-radius: 0 4px 4px 0;
      cursor: pointer;
    }

    .search-bar img {
      width: 15px;
      height: 10px;
      filter: brightness(0) invert(1);
    }

    .book-category {
      margin-bottom: 30px;
    }

    .book-category h3 {
      font-size: 16px;
      color: #333;
      margin-bottom: 10px;
      box-shadow: 0 10px 10px rgba(0,0,0,0.05);
    }

    .book-row {
      display: flex;
      flex-wrap: wrap;
      gap: 20px;
    }

    .book-row img {
      width: 110px;
      height: auto;
      border-radius: 5px;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
      transition: transform 0.3s ease;
      cursor: pointer;
    }

    .book-row img:hover {
      transform: scale(1.05);
    }
  </style>
</head>
<body>
  <section class="content" id="bookSection">
    <!-- Close Button -->
    <button class="close-button" onclick="document.getElementById('bookSection').style.display='none'">✖</button>

    <div class="dashboard-header">
      <h2>Favorite Books</h2>
    </div>

    <div class="search-bar">
      <input type="text" placeholder="Search" />
      <button><img src="../Images/Search.jpg" alt="Search" /></button>
    </div>

    <div class="book-category">
      <h3>Science Fiction</h3>
      <div class="book-row">
        <img src="../Images/Book4.png" alt="Book Cover" />
        <img src="../Images/Book3.jpg" alt="Book Cover" />
        <img src="../Images/Book4.png" alt="Book Cover" />
        <img src="../Images/Book3.jpg" alt="Book Cover" />
        <img src="../Images/Book4.png" alt="Book Cover" />
        <img src="../Images/Book3.jpg" alt="Book Cover" />
        <img src="../Images/Book4.png" alt="Book Cover" />
      </div>
    </div>

    <div class="book-category">
      <h3>Thriller</h3>
      <div class="book-row">
        <img src="../Images/Book4.png" alt="Book Cover" />
        <img src="../Images/Book3.jpg" alt="Book Cover" />
        <img src="../Images/Book4.png" alt="Book Cover" />
        <img src="../Images/Book3.jpg" alt="Book Cover" />
        <img src="../Images/Book4.png" alt="Book Cover" />
        <img src="../Images/Book3.jpg" alt="Book Cover" />
        <img src="../Images/Book4.png" alt="Book Cover" />
      </div>
    </div>

    <div class="book-category">
      <h3>Romance</h3>
      <div class="book-row">
        <img src="../Images/Book4.png" alt="Book Cover" />
        <img src="../Images/Book3.jpg" alt="Book Cover" />
        <img src="../Images/Book4.png" alt="Book Cover" />
        <img src="../Images/Book3.jpg" alt="Book Cover" />
        <img src="../Images/Book4.png" alt="Book Cover" />
        <img src="../Images/Book3.jpg" alt="Book Cover" />
        <img src="../Images/Book4.png" alt="Book Cover" />
      </div>
    </div>
  </section>
</body>
</html>
