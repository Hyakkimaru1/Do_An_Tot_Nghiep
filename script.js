'use strict';

Promise.all([faceapi.nets.tinyFaceDetector.loadFromUri('/user/profile/field/myprofilefield/weights'),
    faceapi.nets.faceLandmark68Net.loadFromUri('/user/profile/field/myprofilefield/weights'),
    faceapi.nets.faceRecognitionNet.loadFromUri('/user/profile/field/myprofilefield/weights'),
    faceapi.nets.faceExpressionNet.loadFromUri('/user/profile/field/myprofilefield/weights')]);

// On this codelab, you will be streaming only video (video: true).
const mediaStreamConstraints = {
    video: true,
};

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

function snapPhoto() {
    photoContext.drawImage(video, 0, 0, photo.width, photo.height);
    //show(photo, sendBtn);
}

video.addEventListener('play',() => {
    const canvas = faceapi.createCanvasFromMedia(video);
    canvas.id = "mycanvas";
    document.getElementById("videoCanvas").append(canvas);
    const displaySize = { width: 640 , height: 480 };
    console.log(displaySize);
    faceapi.matchDimensions(canvas,displaySize);
    setInterval(async () => {
        const detections = await faceapi.detectAllFaces(localVideo, new faceapi.TinyFaceDetectorOptions())
            .withFaceLandmarks()
            .withFaceExpressions();
        const resizeDetections = faceapi.resizeResults(detections,displaySize);
        canvas.getContext('2d').clearRect(0,0,canvas.width,canvas.height);
        faceapi.draw.drawDetections(canvas,resizeDetections)
        faceapi.draw.drawFaceLandmarks(canvas,resizeDetections)
        leftEyePosition();
    },100)

    async function leftEyePosition() {
        const landmarks = await faceapi.detectFaceLandmarks(video)
        const a = landmarks.getRefPointsForAlignment();
        // a[0] mắt trái
        // a[1]: mắt phải
        // a[2]: miệng
        const b = landmarks.getJawOutline();
        // lấy b[2], b[14]
        const c = landmarks.getNose();
        // c[6]: gốc mũi
        // b[2],c[6] => má trái
        // b[14],c[6] => má phải

        const maphai_x = (b[14].x + c[4].x)/2;
        const maphai_y = (b[14].y + c[4].y)/2;
        const A =[a[1].x, a[1].y];
        const C =[maphai_x,maphai_y];
        const B = [c[4].x,c[4].y];

        console.log(A);
        console.log(B);
        console.log(C);


        console.log(find_angle(A,B,C));

        if(find_angle(A,B,C) >= 36)
            snapPhoto();
    }



})

/**
 * Calculates the angle ABC (in radians)
 *
 * A first point, ex: {x: 0, y: 0}
 * C second point
 * B center point
 */
function find_angle(A,B,C) {
    var AB = Math.sqrt(Math.pow(B[0]-A[0],2)+ Math.pow(B[1]-A[1],2));
    var BC = Math.sqrt(Math.pow(B[0]-C[0],2)+ Math.pow(B[1]-C[1],2));
    var AC = Math.sqrt(Math.pow(C[0]-A[0],2)+ Math.pow(C[1]-A[1],2));
    var result = Math.acos((BC*BC+AB*AB-AC*AC)/(2*BC*AB))*180/Math.PI;
    if(result>=180)
        result = result%180;
    return result;
}