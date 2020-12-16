'use strict';

Promise.all([faceapi.nets.tinyFaceDetector.loadFromUri('/user/profile/field/myprofilefield/weights'),
    faceapi.nets.faceLandmark68Net.loadFromUri('/user/profile/field/myprofilefield/weights'),
    faceapi.nets.faceRecognitionNet.loadFromUri('/user/profile/field/myprofilefield/weights'),
    faceapi.nets.faceExpressionNet.loadFromUri('/user/profile/field/myprofilefield/weights')]);

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
}

// Handles error by logging a message to the console with the error message.
function handleLocalMediaStreamError(error) {
    console.log('navigator.getUserMedia error: ', error);
}

// Initializes media stream.
navigator.mediaDevices.getUserMedia(mediaStreamConstraints)
    .then(gotLocalMediaStream).catch(handleLocalMediaStreamError);

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

var photo = document.getElementById('photo');
var photoContext = photo.getContext('2d');
const photoCenter = document.getElementById('photoCenter');
const photoRight = document.getElementById('photoRight');
const photoLeft = document.getElementById('photoLeft');


function snapPhoto(photoSnap,sourceCanvas) {
    const photoContext = photoSnap.getContext('2d');
    photoContext.drawImage(sourceCanvas, 0, 0,100,100);
    //show(photo, sendBtn);
}

video.addEventListener('play',async() => {

    const canvas = faceapi.createCanvasFromMedia(video);
    const regionsToExtract = [
        new faceapi.Rect(0, 0, 100, 100)
    ]
    // actually extractFaces is meant to extract face regions from bounding boxes
    // but you can also use it to extract any other region

    canvas.id = "mycanvas";
    document.getElementById("videoCanvas").append(canvas);
    const displaySize = { width: 640 , height: 480 };
    faceapi.matchDimensions(canvas,displaySize);

    setInterval(async () => {
        const detections = await faceapi.detectAllFaces(localVideo, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceExpressions();
        const resizeDetections = faceapi.resizeResults(detections, displaySize);
        //console.log(resizeDetections)
        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
        faceapi.draw.drawDetections(canvas, resizeDetections, {withScore: true})
        faceapi.draw.drawFaceLandmarks(canvas, resizeDetections)
        if (resizeDetections.length>0){
            const detection = resizeDetections[0].detection._box;

        }

        leftEyePosition();
    },100)

    async function leftEyePosition() {
        faceapi
            .detectSingleFace(video, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .then(async(res) => {
                // Face is detected
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
                    /*console.log(
                        res.detection.score, //Face detection score
                        ry, //Closest to 0 is looking forward
                        rx // Closest to 0.5 is looking forward, closest to 0 is looking up
                    );*/
                    const regionsToExtract = [
                        new faceapi.Rect(detection.x, detection.y, detection.width, detection.height)
                    ]
                    // actually extractFaces is meant to extract face regions from bounding boxes
                    // but you can also use it to extract any other region
                    const canvases = await faceapi.extractFaces(video, regionsToExtract);
                    let state = "undetected";
                    if (res.detection.score > 0.7) {
                        state = "front";
                        if (rx > 0.02) {
                            state = "top";
                        } else {
                            if (ry < -0.06) {

                                    state = "left";
                                    snapPhoto(photoLeft,canvases[0]);


                            }
                            if (ry > 0.06) {
                                   state = "right";
                                    snapPhoto(photoRight,canvases[0]);
                            }
                            if (ry > -0.005 && ry < 0.001) {
                                    state = "center";
                                    snapPhoto(photoCenter,canvases[0]);
                            }
                        }
                    }
                } else {
                    // Face was not detected
                }
            })
    }

})


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
