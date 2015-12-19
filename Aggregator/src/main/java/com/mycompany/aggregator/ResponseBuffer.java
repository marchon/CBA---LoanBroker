/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package com.mycompany.aggregator;

import java.util.ArrayList;
import java.util.List;
import org.json.JSONObject;

/**
 *
 * @author adamv
 */
public class ResponseBuffer {
    
    private final long createdAt;
    private List<JSONObject> responses;
    private boolean finished;

    public ResponseBuffer() {
        createdAt = System.currentTimeMillis();
        responses = new ArrayList<>();
        finished = false;
    }
    
    public void addResponse(JSONObject response){
        responses.add(response);
    }

    public long getCreatedAt() {
        return createdAt;
    }
    
    public JSONObject getBestResponse(){
        
        JSONObject bestResponse = null;
        
        for (JSONObject response : responses) {
            if(bestResponse == null){
                bestResponse = response;
                continue;
            }
            if(bestResponse.getDouble("interestRate") < response.getDouble("interestRate")){
                bestResponse = response;
            }
        }
        return bestResponse;
    }

    public List<JSONObject> getResponses() {
        return responses;
    }

    public void setFinished() {
        finished = true;
    }
    
    public boolean canDelete(){
        return finished;
    }
}
