#!/usr/bin/env ruby
# encoding: utf-8

require "bunny"
require "savon"
require "json"

#TODO: Coming from the front-end
#puts "Please type security number"
#ssn = gets

#puts "Please type loan amount"
#amount = gets

#puts "Please type loan duration in months"
#duration  = gets

conn = Bunny.new(host: "localhost")
conn.start

ch = conn.create_channel
q  = ch.queue("credit_score")

# DTO
#obj = {
#  ssn: ssn,
#  amount: amount,
#  duration: duration
#}

obj = {ssn: '120693-2201', amount: '100000', duration: '12'}

ch.default_exchange.publish(obj.to_json, :routing_key => q.name)
conn.close
