class CreateLoanRequests < ActiveRecord::Migration
  def change
    create_table :loan_requests do |t|
      t.integer :ssn
      t.integer :creditScore
      t.float :loanAmount
      t.integer :loanDuration

      t.timestamps null: false
    end
  end
end
