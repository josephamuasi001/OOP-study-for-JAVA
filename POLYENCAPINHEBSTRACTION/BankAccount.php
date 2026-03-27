<?php


class BankAccount {
    private $balance;
    //Constructor 
    public function __construct($balance) {
        $this->balance = 0;
    }
    //Deposit Method
    public function deposit($amount) {
        if ($amount > 0) {
            $this->balance += $amount;
            echo "You have recieved $" . $amount;
        } else {
            echo "Invalid deposit amount";
        }
    }
    //Withdraw Method
    public function withdraw($amount) {
        if($amount > 0 && $amount <= $this->balance) {
            $this->balance -= $amount;
            echo "You have withrawn $$amount" . "<br>";
        } else {
            echo "Insufficient funds / Invalid withdrawal amount";
        }
    }
    //Get balance
    public function getBalance() {
        return $this->balance;
    }
}

$b1 = new BankAccount(0);

$b1->deposit(500);

$b1->withdraw(200);

echo "Balance: ".$b1->getBalance();