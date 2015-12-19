SockJS client side implementation for system integrations project
===

Deployment
 - Run `./rabbitmq/sbin/rabbitmq-server`
 - Run `node backend.js`
 - Open multiple index.html's in the browser, remember to set different SSN
 - Connect
 - Run `node aggregator <ssn> <msg>`, e.g:`node aggregator 123123-1234 awesome`