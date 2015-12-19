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
    request = LoanRequest.new
    request.ssn = params[:ssn]
    request.creditScore = params[:creditScore]
    request.loanAmount = params[:loanAmount]
    request.loanDuration = params[:loanDuration]
    request.save

    #TODO: Get a job in a bank and find out how this are calculated
    randomInterestRate = randomInterest(1, 5).round(2)

    result = {
      interestRate: randomInterestRate,
      ssn: request.ssn
    }
    render :json => result.to_json
  end

  private

  def randomInterest (min, max)
    rand * (max-min) + min
  end
end
