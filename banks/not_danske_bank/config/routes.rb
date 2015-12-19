Rails.application.routes.draw do
  root 'main#index'
  post '/createLoanRequest' => 'main#createLoanRequest'

end
