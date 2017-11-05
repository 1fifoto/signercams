var clearButton = document.querySelector("[data-action=clear]");
var generateXCamButton = document.querySelector("[data-action=generate-x-cam]");
var generateYCamButton = document.querySelector("[data-action=generate-y-cam]");
var generateZCamButton = document.querySelector("[data-action=generate-z-cam]");
var canvas = document.querySelector("canvas");
var signatureCSV = document.querySelector("[name=signatureCSV]");
var signaturePad = new SignaturePad(canvas);

// Adjust canvas coordinate space taking into account pixel ratio,
// to make it look crisp on mobile devices.
// This also causes canvas to be cleared.
function resizeCanvas() {
  // When zoomed out to less than 100%, for some very strange reason,
  // some browsers report devicePixelRatio as less than 1
  // and only part of the canvas is cleared then.
  var ratio =  Math.max(window.devicePixelRatio || 1, 1);

  // This part causes the canvas to be cleared
  canvas.width = canvas.offsetWidth * ratio;
  canvas.height = canvas.offsetHeight * ratio;
  canvas.getContext("2d").scale(ratio, ratio);

  // This library does not listen for canvas changes, so after the canvas is automatically
  // cleared by the browser, SignaturePad#isEmpty might still return false, even though the
  // canvas looks empty, because the internal data of this library wasn't cleared. To make sure
  // that the state of this library is consistent with visual state of the canvas, you
  // have to clear it manually.
  signaturePad.clear();
}

// On mobile devices it might make more sense to listen to orientation change,
// rather than window resize events.
window.onresize = resizeCanvas;
resizeCanvas();

function download(dataURL, filename) {
  var blob = dataURLToBlob(dataURL);
  var url = window.URL.createObjectURL(blob);

  var a = document.createElement("a");
  a.style = "display: none";
  a.href = url;
  a.download = filename;

  document.body.appendChild(a);
  a.click();

  window.URL.revokeObjectURL(url);
}

// One could simply use Canvas#toBlob method instead, but it's just to show
// that it can be done using result of SignaturePad#toDataURL.
function dataURLToBlob(dataURL) {
  // Code taken from https://github.com/ebidel/filer.js
  var parts = dataURL.split(';base64,');
  var contentType = parts[0].split(":")[1];
  var raw = window.atob(parts[1]);
  var rawLength = raw.length;
  var uInt8Array = new Uint8Array(rawLength);

  for (var i = 0; i < rawLength; ++i) {
    uInt8Array[i] = raw.charCodeAt(i);
  }

  return new Blob([uInt8Array], { type: contentType });
}

clearButton.addEventListener("click", function (event) {
  signaturePad.clear();
});

generateXCamButton.addEventListener("click", function (event) {
  if (signaturePad.isEmpty()) {
    alert("Please provide initials first.");
  } else {
    const data = signaturePad.toData();
    console.log(data);
    signatureCSV.value =  signaturePad.toDataURL("text/csv");
  }
});

generateYCamButton.addEventListener("click", function (event) {
  if (signaturePad.isEmpty()) {
    alert("Please provide initials first.");
  } else {
    const data = signaturePad.toData();
    console.log(data);
    signatureCSV.value =  signaturePad.toDataURL("text/csv");
  }
});

generateZCamButton.addEventListener("click", function (event) {
  if (signaturePad.isEmpty()) {
    alert("Please provide initials first.");
  } else {
    const data = signaturePad.toData();
    console.log(data); 
    signatureCSV.value =  signaturePad.toDataURL("text/csv");
  }
});

