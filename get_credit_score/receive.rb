#!/usr/bin/env ruby
# encoding: utf-8

require "bunny"
require "savon"
require "json"


conn = Bunny.new
conn.start

ch   = conn.create_channel
q    = ch.queue("credit_score")

puts " [*] Waiting for messages in #{q.name}. To exit press CTRL+C"
q.subscribe(:block => true) do |delivery_info, properties, body|
  parsed_body = JSON.parse(body)
  ssn = parsed_body["ssn"]

  # Connect to the credit bureau
  credit_bureau_client = Savon.client(wsdl: 'http://datdb.cphbusiness.dk:8080/CreditBureau/CreditScoreService?wsdl')

  # Get credit score
  result  = credit_bureau_client.call(:credit_score, message: {ssn: ssn})
  credit_score = result.body[:credit_score_response][:return]

  puts " [x] Received SSN #{ssn}"
  puts "Received credit score: #{credit_score}"

  conn = Bunny.new(host: 'localhost', user: "test", pass: "test")
  conn.start

  ch = conn.create_channel
  q = ch.queue("get_banks")

  puts parsed_body

  parsed_body["credit_score"] = credit_score

  # corr_id = "#{rand}#{rand}#{rand}"

  ch.default_exchange.publish(parsed_body.to_json,
      :routing_key    => q.name,
      :correlation_id => corr_id)
end
