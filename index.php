<head>
<title>PeerJs</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<style>
    .mt-50{
        margin-top:100px;
    }
    .mt-10{
        margin-top:10px;
    }
    audio{
        display:none;
    }
    video{
        width:90%;
    }
    .streams{
        display:none;
    }
    .answer-call{
        display:none;
    }
</style>
</head>
<body>
<audio id="audio-send" src="/send.mp3"></audio>
<audio id="audio-recive" src="/recive.mp3"></audio>
<audio id="call-recive" src="/call.mp3" loop></audio>
<div class="container-fluid mt-50">
    <div class="row">
        <div class="col-6">
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">Connection ID</span>
                </div>
                <input type="text" class="form-control connect-to" placeholder="Connection ID">
            </div>
            <button class="btn btn-primary connect-peer">Connect</button>
            <div class="form-group mt-10">
                <label for="exampleInputPassword1">Your ID</label>
                <input type="text" class="form-control my-id" readonly>
            </div>
            <div class="row mt-10 streams">
                <div class="col-6">
                    <video id="stream-local" autoplay playsinline></video>
                </div>
                <div class="col-6">
                    <video id="stream-peer" autoplay playsinline></video>
                </div>
            </div>
            <button class="btn btn-primary call mt-10">Video Call</button>
            <button class="btn btn-primary answer-call mt-10">Answer Video Call</button>
        </div>
        <div class="col-6">
            <div class="input-group mb-3">
                <div class="input-group-prepend">
                    <span class="input-group-text" id="basic-addon1">Message</span>
                </div>
                <input type="text" class="form-control message" placeholder="Type your message">
            </div>
            <button class="btn btn-primary send-msg">Send</button>
            <div class="chat mt-10"></div>
        </div>
    </div>
</div>
</body>

<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/3.1.2/rollups/aes.js" integrity="sha256-/H4YS+7aYb9kJ5OKhFYPUjSJdrtV6AeyJOtTkw6X72o=" crossorigin="anonymous"></script>
<script src="/peerjs.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<script>
var peer = "";
var conn =  "";
var me = "";
var other = "";


function builMessage(message){
    $('.chat').append('<div class="alert alert-dark" role="alert">' + message + '</div>');
};

function initialize() {
    // Create own peer object with connection to shared PeerJS server
    peer = new Peer(null, {
        debug: 2
    });

    peer.on('open', function (id) {
        if(peer.id != ""){
            $('.my-id').val(peer.id);
            lastPeerId = peer.id;
            me = peer.id;
            console.log('ID: ' + peer.id);
        }else{
            alert('Error generating an id');
        }
    });
    peer.on('connection', function (c) {
        // Allow only a single connection
        if (conn && conn.open) {
            c.on('open', function() {
                c.send("Already connected to another client");
                setTimeout(function() { c.close(); }, 500);
            });
            return;
        }
        conn = c;
        console.log("Connected to: " + conn.peer);
        builMessage("Connected to: " + conn.peer);
        other = conn.peer;
        recive()
    });
    peer.on('disconnected', function () {
        console.log('Connection lost. Please reconnect');

        // Workaround for peer.reconnect deleting previous id
        peer.id = lastPeerId;
        peer._lastServerId = lastPeerId;
        peer.reconnect();
    });
    peer.on('close', function() {
        conn = null;
        console.log('Connection destroyed');
    });
    peer.on('error', function (err) {
        console.log(err);
        alert('' + err);
    });

    var getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
    peer.on('call', function(call) {
        $('.call').hide();
        $('.answer-call').show();
        const audio = document.getElementById("call-recive");
        //audio.volume = 0.2;
        audio.play();
        $('.answer-call').click(function(){
            getUserMedia({video: true, audio: true}, function(stream) {
                call.answer(stream); // Answer the call with an A/V stream.
                call.on('stream', function(remoteStream) {
                    document.getElementById('stream-local').srcObject = stream;
                    document.getElementById('stream-peer').srcObject = remoteStream;
                    $('.streams').show();
                    $('.call').hide();
                    $('.answer-call').hide();
                    audio.pause();
                });
            }, function(err) {
                console.log('Failed to get local stream' ,err);
            });
        });
    });
};
function recive(){
    conn.on('data', function (data) {
        console.log('recieved: ' + data);
        //builMessage(data);
        $('.chat').append('<div class="alert alert-primary" role="alert">' + data + '</div>');
        const audio = document.getElementById("audio-recive");
        //audio.volume = 0.2;
        audio.play();
    });
    conn.on('close', function () {
        builMessage("Connection closed");
    });
};

function join(id) {
    // Close old connection
    if (conn) {
        conn.close();
    }

    // Create connection to destination peer specified in the input field
    conn = peer.connect(id, {
        reliable: true
    });
    conn.on('open', function () {
        builMessage("Connected to: " + conn.peer);
        console.log("Connected to: " + conn.peer);
    });
    other = id;
    recive();
};
$('.connect-peer').click(function(){
    var id = $('.connect-to').val();
    builMessage('connecting to '+id);
    join(id);
});
$('.send-msg').click(function(){
    var msg = $('.message').val();
    if (conn && conn.open) {
        conn.send(msg);
        $('.chat').append('<div class="alert alert-success" role="alert">' + msg + '</div>');
        $('.message').val('');
        const audio = document.getElementById("audio-send");
        //audio.volume = 0.2;
        audio.play();
    }else{
        alert('connection closed');
    }
    
});
$('.call').click(function(){
    if (conn) {
        var getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
        getUserMedia({video: true, audio: true}, function(stream) {
        var call = peer.call(other, stream);
        call.on('stream', function(remoteStream) {
            document.getElementById('stream-local').srcObject = stream;
            document.getElementById('stream-peer').srcObject = remoteStream;
            $('.streams').show();
            $('.call').hide();
        });
        }, function(err) {
            console.log('Failed to get local stream' ,err);
        });
    }else{
        alert('No connection found');
    }
});
initialize()
</script>