var sock;

function connect() {
    sock = new SockJS('localhost:9999/bestLoanBank');

    sock.onopen = function() {
        console.log('open');
        document.getElementById("msg_received").innerHTML += "\n" + "SOCKET OPENED";

        setTimeout(function() {
            sendMessage();
            document.getElementById("msg_received").innerHTML += "\n" + "MSG SENT";
        }) 
    };

    sock.onmessage = function(e) {
        console.log('message', e.data);
        document.getElementById("msg_received").innerHTML += "\n" + e.data;
    };

    sock.onclose = function() {
        console.log('close');
        document.getElementById("msg_received").innerHTML += "\n" + "SOCKET CLOSED";
    };

}

function sendMessage() {
    sock.send(JSON.stringify({
        ssn: document.getElementById("ssn").value,
        load_amount: document.getElementById("load_amount").value,
        load_duration: document.getElementById("load_duration").value,
    }));
}

function disconnect() {
    sock.close();
}