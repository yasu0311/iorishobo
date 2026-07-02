let btn = document.querySelector(".toggle-btn");
let mask = document.querySelector("#mask");
let menu = document.querySelector("#menu");
let menuTitles = document.querySelectorAll(".menu__title");

btn.onclick = () => {
  menu.classList.toggle("open");
  mask.classList.toggle("open");
  btn.classList.toggle("open");
  document.querySelectorAll('.menu__list').forEach(function(element) {
    element.style.display = 'none';
  });
  document.querySelectorAll('.menu__title .none').forEach(function(element) {
    element.innerHTML = '';
    element.classList.replace('none', 'plus');
  });
};

mask.onclick = () => {
  menu.classList.remove("open");
  mask.classList.remove("open");
  btn.classList.remove("open");
};

document.addEventListener("DOMContentLoaded", function() {
  const menuTitles = document.querySelectorAll('.menu__title');
  menuTitles.forEach(function(title) {
    title.addEventListener('click', function() {
      const menuContent = title.nextElementSibling;
      if (menuContent.style.display === "none" || menuContent.style.display === "") {
        menuContent.style.display = "block";
        title.querySelector('span').innerHTML = '－';
        title.querySelector('span').classList.replace('plus', 'none');

      } else {
        menuContent.style.display = "none";
        title.querySelector('span').innerHTML = '＋';
        title.querySelector('span').classList.replace('plus', 'none');
      }
    });
  });
});

window.addEventListener('resize', function() {
  if (window.innerWidth > 900) {
    document.querySelectorAll('.menu__list').forEach(function(element) {
      element.style.display = 'block';
    });
    document.querySelectorAll('.menu__title .none').forEach(function(element) {
      element.innerHTML = '';
      element.classList.remove = 'plus';
    });
  }
});


