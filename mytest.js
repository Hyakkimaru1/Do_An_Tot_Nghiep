'use strict';

Promise.all([faceapi.nets.tinyFaceDetector.loadFromUri('/user/profile/field/myprofilefield/weights'),
    faceapi.nets.faceLandmark68Net.loadFromUri('/user/profile/field/myprofilefield/weights'),
    ]);


// On this codelab, you will be streaming only video (video: true).
const mediaStreamConstraints = {
    video: true,
};

var initvalues,user;
function init(Y, initvariables){
    initvalues = initvariables;
    console.log(initvalues);
}
function myuser(Y, initvariables){
    user = initvariables;
    console.log(user);
}

// Video element where stream will be placed.
const localVideo = document.querySelector('video');

// Local stream that will be reproduced on the video.
let localStream;

// Handles success by adding the MediaStream to the video element.
function gotLocalMediaStream(mediaStream) {
    localStream = mediaStream;
    localVideo.srcObject = mediaStream;
    document.getElementById("loading").style.display="none";
    document.getElementById("camera").style.display="block";
}

// Handles error by logging a message to the console with the error message.
function handleLocalMediaStreamError(error) {
    console.log('navigator.getUserMedia error: ', error);
    document.getElementById("loading").style.display="none";
    document.getElementById("camera").style.display="block";
}

// Initializes media stream.
function handleClickOpenCam(){
    document.getElementById("button-snap").style.display = "none";
    document.getElementById("dontshow").style.display= "block";
    navigator.mediaDevices.getUserMedia(mediaStreamConstraints)
        .then(gotLocalMediaStream).catch(handleLocalMediaStreamError);
}

var video = document.getElementById('camera');

function grabWebCamVideo() {
    console.log('Getting user media (video) ...');
    navigator.mediaDevices.getUserMedia({
        video: true
    })
        .then(gotStream)
        .catch(function(e) {
            alert('getUserMedia() error: ' + e.name);
        });
}

const photoCenter = document.getElementById('photoCenter');
const photoRight = document.getElementById('photoRight');
const photoLeft = document.getElementById('photoLeft');
const textCenter = document.getElementById('text-center');
const textRight = document.getElementById('text-right');
const textLeft = document.getElementById('text-left');
const recapCenter = document.getElementById('recapture-center');
const recapRight = document.getElementById('recapture-right');
const recapLeft = document.getElementById('recapture-left');
const buttonSubmit = document.getElementById('button-submit');

function snapPhoto(photoSnap,sourceCanvas) {
    const photoContext = photoSnap.getContext('2d');
    photoContext.drawImage(sourceCanvas, 0, 0,100,100);
    if (!isCanvasBlank(photoRight) && !isCanvasBlank(photoLeft) && !isCanvasBlank(photoCenter)){
        buttonSubmit.classList.remove("button-disable");
    }
    //show(photo, sendBtn);
}

function handleResetPicture(){
    photoCenter.getContext('2d').clearRect(0, 0, photoCenter.width, photoCenter.height);
    photoRight.getContext('2d').clearRect(0, 0, photoRight.width, photoRight.height);
    photoLeft.getContext('2d').clearRect(0, 0, photoLeft.width, photoLeft.height);
    textCenter.style.display = "block";
    textRight.style.display = "block";
    textLeft.style.display = "block";
}

function handleResetLeftPicture(){
    buttonSubmit.classList.add("button-disable");
    photoLeft.getContext('2d').clearRect(0, 0, photoLeft.width, photoLeft.height);
    textLeft.style.display = "block";
}

function handleResetRightPicture(){
    buttonSubmit.classList.add("button-disable");
    photoRight.getContext('2d').clearRect(0, 0, photoLeft.width, photoLeft.height);
    textRight.style.display = "block";
}

function handleResetCenterPicture(){
    buttonSubmit.classList.add("button-disable");
    photoCenter.getContext('2d').clearRect(0, 0, photoLeft.width, photoLeft.height);
    textCenter.style.display = "block";
}

function handleSubmitPicture(){
    if ( !isCanvasBlank(photoRight) && !isCanvasBlank(photoLeft) && !isCanvasBlank(photoCenter)) {
        alert('Ảnh của bạn đã được lưu');
    }
}

video.addEventListener("play", async () => {
    const canvas = faceapi.createCanvasFromMedia(video);
    canvas.id = "mycanvas";
    document.getElementById("dontshow").append(canvas);
    const displaySize = { width: 640 , height: 480 };

    faceapi.matchDimensions(canvas,displaySize);
    setInterval(async () =>{
        const res = await faceapi.detectSingleFace(video,new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
        const resizeDetections = faceapi.resizeResults(res,displaySize);
        if (res) {
            const eye_right = getMeanPosition(res.landmarks.getRightEye());
            const eye_left = getMeanPosition(res.landmarks.getLeftEye());
            const nose = getMeanPosition(res.landmarks.getNose());
            const mouth = getMeanPosition(res.landmarks.getMouth());
            const jaw = getTop(res.landmarks.getJawOutline());

            const rx = (jaw - mouth[1]) / res.detection.box.height + 0.5;
            const ry = (eye_left[0] + (eye_right[0] - eye_left[0]) / 2 - nose[0]) /
                res.detection.box.width;
            const detection = res.detection.box;

            const regionsToExtract = [
                new faceapi.Rect(detection.x, detection.y, detection.width, detection.height)
            ]
            // actually extractFaces is meant to extract face regions from bounding boxes
            // but you can also use it to extract any other region
            const canvases = await faceapi.extractFaces(video, regionsToExtract);

            let state = "undetected";
            if (res.detection.score > 0.7) {
                state = "front";
                if (rx > 0.2) {
                    state = "top";
                } else {
                    if (ry < -0.06) {
                        state = "left";
                        if (isCanvasBlank(photoLeft)){
                            textLeft.style.display = "none";
                            recapLeft.style.display="block";
                            snapPhoto(photoLeft, canvases[0]);
                        }
                    }
                    if (ry > 0.06) {
                        state = "right";
                        if (isCanvasBlank(photoRight)){
                            textRight.style.display = "none";
                            recapRight.style.display="block";
                            snapPhoto(photoRight, canvases[0]);
                        }
                    }
                    if (ry > -0.002 && ry < 0.002) {
                        state = "center";
                        if (isCanvasBlank(photoCenter)){
                            textCenter.style.display = "none";
                            recapCenter.style.display="block";
                            snapPhoto(photoCenter, canvases[0]);
                        }
                    }
                }
            }
        }
        //clear canvas
        //canvas.getContext('2d').clearRect(0,0,canvas.width,canvas.height);

        //faceapi.draw.drawDetections(canvas,resizeDetections);
        //faceapi.draw.drawFaceLandmarks(canvas,resizeDetections);
        if (resizeDetections.length>0){
           const detection = resizeDetections[0].detection._box;
        }
    },100)
})

function isCanvasBlank(canvas) {
    return !canvas.getContext('2d')
        .getImageData(0, 0, canvas.width, canvas.height).data
        .some(channel => channel !== 0);
}

function getTop(l) {
    return l
        .map((a) => a.y)
        .reduce((a, b) => Math.min(a, b));
}

function getMeanPosition(l) {
    return l
        .map((a) => [a.x, a.y])
        .reduce((a, b) => [a[0] + b[0], a[1] + b[1]])
        .map((a) => a / l.length)
}