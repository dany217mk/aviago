const slidePage = document.querySelector(".slide-page");
const nextBtnFirst = document.querySelector(".firstNext");
const prevBtnSec = document.querySelector(".prev-1");
const nextBtnSec = document.querySelector(".next-1");
const prevBtnThird = document.querySelector(".prev-2");
const nextBtnThird = document.querySelector(".next-2");
const prevBtnFourth = document.querySelector(".prev-3");
const submitBtn = document.querySelector(".submit");
const progressText = document.querySelectorAll(".step p");
const progressCheck = document.querySelectorAll(".step .check");
const bullet = document.querySelectorAll(".step .bullet");
let current = 1;

nextBtnFirst.addEventListener("click", function(event){
  event.preventDefault();
  slidePage.style.marginLeft = "-25%";
  bullet[current - 1].classList.add("active");
  progressCheck[current - 1].classList.add("active");
  progressText[current - 1].classList.add("active");
  current += 1;
});
nextBtnSec.addEventListener("click", function(event){
  event.preventDefault();
  slidePage.style.marginLeft = "-50%";
  bullet[current - 1].classList.add("active");
  progressCheck[current - 1].classList.add("active");
  progressText[current - 1].classList.add("active");
  current += 1;
});
nextBtnThird.addEventListener("click", function(event){
  event.preventDefault();
  slidePage.style.marginLeft = "-75%";
  bullet[current - 1].classList.add("active");
  progressCheck[current - 1].classList.add("active");
  progressText[current - 1].classList.add("active");
  current += 1;
});
submitBtn.addEventListener("click", function(){
  bullet[current - 1].classList.add("active");
  progressCheck[current - 1].classList.add("active");
  progressText[current - 1].classList.add("active");
  current += 1;
  setTimeout(function(){
    alert("Your Form Successfully Signed up");
    location.reload();
  },800);
});

prevBtnSec.addEventListener("click", function(event){
  event.preventDefault();
  slidePage.style.marginLeft = "0%";
  bullet[current - 2].classList.remove("active");
  progressCheck[current - 2].classList.remove("active");
  progressText[current - 2].classList.remove("active");
  current -= 1;
});
prevBtnThird.addEventListener("click", function(event){
  event.preventDefault();
  slidePage.style.marginLeft = "-25%";
  bullet[current - 2].classList.remove("active");
  progressCheck[current - 2].classList.remove("active");
  progressText[current - 2].classList.remove("active");
  current -= 1;
});
prevBtnFourth.addEventListener("click", function(event){
  event.preventDefault();
  slidePage.style.marginLeft = "-50%";
  bullet[current - 2].classList.remove("active");
  progressCheck[current - 2].classList.remove("active");
  progressText[current - 2].classList.remove("active");
  current -= 1;
});




let btn_auth = document.getElementById('auth-button');
  btn_auth.onclick = function(){
    document.getElementById('layer_bg').classList.toggle('active');
    document.getElementById('auth').classList.toggle('active');
    document.getElementById('close-area').classList.add('active');
    //controlInput(true);
  }

  document.getElementById('close-auth').onclick = function(){
    document.getElementById('layer_bg').classList.toggle('active');
    document.getElementById('auth').classList.toggle('active');
    document.getElementById('close-area').classList.remove('active');
    //controlInput(false);
  }

  document.getElementById('close-area').onclick = function(){
    document.getElementById('layer_bg').classList.remove('active');
    document.getElementById('auth').classList.remove('active');
    document.getElementById('join').classList.remove('active');
    document.getElementById('close-area').classList.remove('active');

   // controlInput(false);

  }

  let btn_reg = document.getElementById('reg-button');
  btn_reg.onclick = function(){
    document.getElementById('layer_bg').classList.toggle('active');
    document.getElementById('join').classList.toggle('active');
    document.getElementById('close-area').classList.add('active');
 //   controlInput(true);
  }

  document.getElementById('close-reg').onclick = function(){
    document.getElementById('layer_bg').classList.toggle('active');
    document.getElementById('join').classList.toggle('active');
    document.getElementById('close-area').classList.remove('active');
  //  controlInput(false);
  }








