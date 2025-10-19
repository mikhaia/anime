let isTV = false;
let isChromecast = false;

if(/SmartTV/i.test(navigator.userAgent)) {
	document.body.classList.add('tv');
	isTV = true;
}

if(/Chromecast/i.test(navigator.userAgent)) {
    document.body.classList.add('chromecast');
    isChromecast = true;
}

const gapWidth = parseInt($('.list').css('gap')); 
const marginWidth = parseInt($('.list .item').css('margin'));
const itemWidth = $('.item').width()+gapWidth;
const itemsInRow = Math.floor(window.innerWidth / itemWidth);
let listWidth = itemsInRow*itemWidth;
if (!isTV) {
	listWidth -= gapWidth; 
}
$('.list').width(listWidth);

const $menuToggle = $('#menu-toggle');
const $nav = $('.nav');

$menuToggle.on('click', function () {
  $nav.toggleClass('open');
});

const $userItem = $('.has-submenu');
const $userLink = $userItem.children('a').first();

$userLink.on('click', function (e) {
  e.preventDefault();
  $userItem.toggleClass('open');
});

if (isChromecast) {
    $('.item').on('mouseenter', function() {
        $(this).focus();
    });
} 

$(document).on('keydown', function (e) {
	//  alert('Down key: ' + e.key + '\ncode: ' + e.code + '\nkeyCode: ' + e.keyCode);
  switch (e.key) {
    case 'ArrowUp': up(); break;
    case 'ArrowDown': down(); break;
    case 'ArrowLeft': left(); break;
    case 'ArrowRight': right(); break;
  }

  if (isTV) {
    switch (e.keyCode) {
      case 403: location.href = '/'; break; // TV Red Button
      case 404: location.href = '/list?mode=favorites'; break; // TV Green Button
      case 405: location.href = '/new'; break; // TV Yellow Button
      case 407: location.href = '/top'; break;  // TV Blue Button
    };
  }
});

function up() {
  if (!$(':focus').length) $('.item:first').focus();
  else {
    const above = getAbove(); if (above) above.focus();
  }
}

function down() {
  if (!$(':focus').length) $('.item:first').focus(); 
  else {
    const below = getBelow(); if (below) below.focus(); 
  }
}

function left() {
  if (!$(':focus').length) {
    $('#main-nav li:first a').focus();
  }
  else if($(':focus').is('#main-nav li a')) {
    $(':focus').closest('li').prev().find('a').focus();
  }
  else {
    $(':focus').prev().focus();
  }
}

function right() {
  if (!$(':focus').length) {
    $('#main-nav li:first a').focus();
  }
  else if($(':focus').is('#main-nav li a')) {
    $(':focus').closest('li').next().find('a').focus();
  }
  else {
    $(':focus').next().focus();
  }
}

function getBelow() {
  const current = $(':focus');
  const curTop = current.offset().top; const curLeft = current.offset().left;
  const found = $('.item').filter(function () { const el = $(this); const top = el.offset().top; const left = el.offset().left; return top > curTop && Math.abs(left - curLeft) < current.outerWidth(); }).sort((a, b) => $(a).offset().top - $(b).offset().top).first();
  return found.length ? found : null;
}

function getAbove() {
  const current = $(':focus');
  const curTop = current.offset().top; const curLeft = current.offset().left;
  const found = $('.item').filter(function () { const el = $(this); const top = el.offset().top; const left = el.offset().left; return top < curTop && Math.abs(left - curLeft) < current.outerWidth(); }).sort((a, b) => $(b).offset().top - $(a).offset().top).first();
  return found.length ? found : null;
}



