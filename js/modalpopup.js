let popup = document.getElementById("popup");
let popup1 = document.getElementById("popup1");

function openPopup() {
    popup.classList.add("open-popup");
    popup1.classList.remove("open-popup1");
}
function closePopup() {
    popup.classList.remove("open-popup");
}

function openPopup1() {
    popup.classList.remove("open-popup");
    popup1.classList.add("open-popup1");
}
function closePopup1() {
    popup1.classList.remove("open-popup1");
}

