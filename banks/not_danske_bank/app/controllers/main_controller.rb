class MainController < ApplicationController

  def index
    result = 'Hello'
    render :json => result.to_json
  end

  #TODO: better error handling
  def createLoanRequest
    puts 'GOT REQUEST'
    puts params.inspect

    # Persist to the database for some reason
    req = LoanRequest.new
    req.ssn = params[:ssn]
    req.creditScore = params[:creditScore]
    req.loanAmount = params[:loanAmount]
    req.loanDuration = params[:loanDuration]
    req.save

    reply_to = request.headers['HTTP_REPLY_TO']

    #TODO: Get a job in a bank and find out how this are calculated
    randomInterestRate = randomInterest(1, 5).round(2)

    result = {
      interestRate: randomInterestRate,
      ssn: req.ssn
    }

    conn = Bunny.new
    conn.start
    ch = conn.create_channel
    q = ch.queue(reply_to)

    ch.default_exchange.publish(result.to_json, routing_key: q.name)

    render :json => result.to_json
  end

  private

  def randomInterest (min, max)
    rand * (max-min) + min
  end
end
