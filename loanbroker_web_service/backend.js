var http = require('http'),
    sockjs = require('sockjs'),
    amqp = require('amqplib'),
    uuid = require('node-uuid'),
    when = require('when'),
    defer = when.defer,
    echo = sockjs.createServer({ sockjs_url: 'http://cdn.jsdelivr.net/sockjs/1.0.1/sockjs.min.js' }),
    server = http.createServer(),
    sockets = [],
    bestQuoteQueueName = "best_queue_1"; // global que name


amqp.connect('amqp://localhost').then(function(backendQueue) {
    process.once('SIGINT', function() { console.log('Silently close backend connection'); backendQueue.close(); });
    return backendQueue.createChannel().then(function(ch) {
        var ok = ch.assertQueue(bestQuoteQueueName, {durable: false})
                .then(function() {
                    ch.prefetch(1);
                    return ch.consume(bestQuoteQueueName, on_response);
                })
                .then(function() {
                    console.log('Que is listening on key: ' + bestQuoteQueueName);
                });

        function on_response(msg) {
            if(!msg) return;
            // Check whether the message contains the same ssn, if yes, send a msg
            console.log('Message object: ');
            console.log(msg);
            
            sockets[msg.fields.correlationId].write(msg.content.toString());
            delete sockets[msg.fields.correlationId];
        }

        //Socket connection callback/promise
        echo.on('connection', function(conn) { // When socket is open with fronend

            conn.on('data', function(message) { // Send rabbit MQ msg to other server

                var corrId = uuid(),
                    customerData = JSON.parse(message);

                sockets[corrId] = conn;

                getInterest(corrId, {ssn: customerData.ssn, 
                                        amount: customerData.load_amount, 
                                        duration: '12'});
            }); // socket data received

            conn.on('close', function() {
                
            });
            
        }); // close front end socket
    });
}).then(null, console.warn);

function getInterest(corrId, data) {

    console.log(data);

    amqp.connect('amqp://localhost').then(function(conn) {
        return when(conn.createChannel().then(function(ch) {    
            var answer = defer();
            // Connect to queue
            var ok = ch.assertQueue("credit_score", {durable: false, 
                                                    exclusive: false})
                    .then(function(qok) { return qok.queue; })

                    // Make request
                    .then(function(queue) {
                        ch.sendToQueue('credit_score', new Buffer(JSON.stringify(data).toString()), {
                            correlationId: corrId
                        });

                        return answer.promise;
                    });

            return ok.then(function() {
              console.log('asd');
            });
        })).ensure(function() { conn.close(); });
    }).then(null, console.warn);
}

echo.installHandlers(server, {prefix:'/bestLoanBank'});
server.listen(9999, 'localhost');