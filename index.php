<!DOCTYPE html>
<html>
  <head>
	<link href="https://fonts.googleapis.com/css2?family=Noto+Sans&display=swap" rel="stylesheet">
	<style>
body {
  font-family: 'Noto Sans', sans-serif;
  background-color: #333;
  color: #fff;
}
.container {
  width: 80%;
  margin: auto;
  border: 2px solid #2ecc71;
  border-radius: 25px;
  padding: 20px;
  background-color: #444;
}
.nav a {
  text-decoration: none;
  color: #fff;
  padding: 10px 20px;
  border: 2px solid #2ecc71;
  border-radius: 25px;
  background-color: #333;
  transition: all 0.3s ease-in-out;
}
.nav a:hover {
  background-color: #2ecc71;
  color: #333;
  cursor: pointer;
  transition: all 0.3s ease-in-out;
}

.video-container {
  width: 80%;
  margin: 20px auto;
  border: 2px solid #2ecc71;
  border-radius: 25px;
  overflow: hidden;
  padding-bottom: 56.25%;
  position: relative;
}
.video-container iframe {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
}

header {
  text-align: left;
}
.logo {
  height: 60px;
  width: 60px;
  display: inline-block;
  vertical-align: middle;
  margin-right: 10px;
}
.title {
  display: inline-block;
  font-size: 48px;
  line-height: 60px;
  vertical-align: middle;
}

      div.footer {
  width: 80%;
  margin: auto;
  border: 2px solid #2ecc71;
  border-radius: 25px;
  padding: 20px;
  background-color: #444;
          display: flex;
        justify-content: center;
        align-items: center;
      }
    </style>
	<title>WhyKorp</title>
  </head>
  <body>
    <div class="container">
<header>
  <a href="https://www.youtube.com/@whykioh" target="_blank">
    <img class="logo" src="img/logo.png" alt="Logo">
  </a>
  <h1 class="title">WhyKorp</h1>
</header>
<style>
  header {
    opacity: 0;
    transition: opacity 1s;
  }
  .show {
    opacity: 1;
  }
</style>

<script>
  window.addEventListener("load", function() {
    document.querySelector("header").classList.add("show");
  });
</script>
      <div class="nav">
        <a href="#">Accueil</a>
        <a href="#">Infos</a>
		<a href="https://artists.magroove.com/en/lt/whykioh/" target="_blank">Link Tree</a>
		<a href="#">Outils</a>
		<a href="#">Ruty</a>
		<a href="#">LoLivator</a>
		<a href="#">SNT</a>
		<a href="#">Archives</a>
      </div>
      <div class="video-container">
        <iframe width="560" height="315" src="https://www.youtube.com/embed/N0UmI6L86YY" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
      </div>
    </div>
	<div class="footer">
        Copyright | Whykorp® 2021-2024
      </div>
  </body>
</html>
