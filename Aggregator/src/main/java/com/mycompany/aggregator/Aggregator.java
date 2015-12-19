/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package com.mycompany.aggregator;

import com.rabbitmq.client.*;
import java.io.IOException;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.Map.Entry;
import java.util.concurrent.TimeoutException;
import java.util.logging.Level;
import java.util.logging.Logger;
import org.json.JSONObject;

/**
 *
 * @author adamv
 */
public class Aggregator {

    private static final String QUEUE_NAME = "aggregator";
    private static final String QUEUE_BEST_NAME = "best_queue_1";
    
    private static final String HOST = "localhost";
    private static final int PORT = 5672;
    private static final String USERNAME = "test";
    private static final String PASSWORD = "test";

    private static final long TIMEOUT = 3000;

    private Map<String, ResponseBuffer> responseQueue;
    private Map<String, ResponseBuffer> responseQueueBuffer;
    
    //Response queue
    private Connection responseConnection;
    private Channel responseChannel;

    public Aggregator() throws IOException, TimeoutException {
        responseQueue = new HashMap<>();
        responseQueueBuffer = new HashMap<>();
        initializeResponseQueue();
    }
    
    public void subscribe() throws Exception, TimeoutException, IOException {
        ConnectionFactory factory = new ConnectionFactory();
        factory.setHost("localhost");
        factory.setUsername(USERNAME);
        factory.setPassword(PASSWORD);

        Connection connection = factory.newConnection();
        Channel channel = connection.createChannel();

        channel.queueDeclare(QUEUE_NAME, false, false, false, null);
        System.out.println(" [*] Waiting for messages. To exit press CTRL+C");

        Consumer consumer = new DefaultConsumer(channel) {
            @Override
            public void handleDelivery(String consumerTag, Envelope envelope, AMQP.BasicProperties properties, byte[] body) throws IOException {
                String response = new String(body, "UTF-8");
                       
                System.out.println("Received message!!");
                
                synchronized (responseQueueBuffer) {
                    if (responseQueueBuffer.get(properties.getCorrelationId()) == null) {
                        responseQueueBuffer.put(properties.getCorrelationId(), new ResponseBuffer());
                    }
                    if (response != null) {
                        responseQueueBuffer.get(properties.getCorrelationId()).addResponse(new JSONObject(response));
                    }
                }
            }
        };
        
                
        channel.basicConsume(QUEUE_NAME, true, consumer);
           
        Thread task = new Thread() {
            @Override
            public void run() {
                while (true) {
                    syncQueues();
                    for (Map.Entry<String, ResponseBuffer> pair : responseQueue.entrySet()) {
                        if (responseQueue.get(pair.getKey()).getCreatedAt() + TIMEOUT < System.currentTimeMillis()) {
                            JSONObject bestResponse = responseQueue.get(pair.getKey()).getBestResponse();
                            try {
                                forwardToSockets(bestResponse, (String)pair.getKey());
                                System.out.println("Should return " + bestResponse);
                                responseQueue.get(pair.getKey()).setFinished();
                            } catch (IOException ex) {
                                Logger.getLogger(Aggregator.class.getName()).log(Level.SEVERE, null, ex);
                            } catch (TimeoutException ex) {
                                Logger.getLogger(Aggregator.class.getName()).log(Level.SEVERE, null, ex);
                            }
                            
                        }
                    }
                }
            }
            
            
        };
        task.start();
    }
        
    public void initializeResponseQueue() throws IOException, TimeoutException{
        ConnectionFactory factory = new ConnectionFactory();
        factory.setHost("localhost");
        factory.setPort(5672);
        factory.setUsername("test");
        factory.setPassword("test");

        responseConnection = factory.newConnection();
        responseChannel = responseConnection.createChannel();
        responseChannel.queueDeclare(QUEUE_BEST_NAME, false, false, false, null);
    }
    
    public void forwardToSockets(JSONObject response, String correlationId) throws IOException, TimeoutException{
         AMQP.BasicProperties props = new  AMQP.BasicProperties
                                .Builder()
                                .correlationId(correlationId)
                                .build();
        
        String message = response.toString();
        responseChannel.basicPublish("", QUEUE_BEST_NAME, props, message.getBytes());
        System.out.println(" [x] Sent '" + message + "'");   
    }

    public void syncQueues() {
        synchronized (responseQueueBuffer) {
            for (Map.Entry<String, ResponseBuffer> pair : responseQueueBuffer.entrySet()) {
                if (responseQueue.get(pair.getKey()) == null) {
                    responseQueue.put(pair.getKey(), new ResponseBuffer());
                }
                for (JSONObject response : responseQueueBuffer.get(pair.getKey()).getResponses()) {
                    responseQueue.get(pair.getKey()).addResponse(response);
                }
            }
            responseQueue.clear();
            
            //In order to avoid ConcurrentModificationException
            List<String> deleteKeys = new ArrayList<>();
  
            for (Map.Entry<String, ResponseBuffer> pair : responseQueue.entrySet()) {
                if(responseQueue.get(pair.getKey()).canDelete()){
                    deleteKeys.add(pair.getKey());
                }
            }
            
            for(String key : deleteKeys){
                responseQueue.remove(key);
            }
        }

    }
}
