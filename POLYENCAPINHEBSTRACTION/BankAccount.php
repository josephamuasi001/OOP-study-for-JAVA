<?php


class BankAccount {
    private $balance;

    public function __construct($balance) {
        $this->balance = 0;
    }

    public function deposit($amount) {
        if ($amount > 0) {
            $this->balance += $amount;
            echo "You have recieved $" . $amount;
        } else {
            echo "Invalid deposit amount";
        }
    }

    public function withdraw($amount) {
        if($amount > 0 && $amount <= $this->balance) {
            $this->balance -= $amount;
            echo "You have withrawn $$amount" . "<br>";
        } else {
            echo "Insufficient funds / Invalid withdrawal amount";
        }
    }

    public function getBalance() {
        return $this->balance;
    }
}

$b1 = new BankAccount(0);

$b1->deposit(500);

$b1->withdraw(200);

echo "Balance: ".$b1->getBalance();