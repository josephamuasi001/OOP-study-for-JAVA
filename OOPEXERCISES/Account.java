package OOPEXERCISES;

public class Account {
    private String accountNumber;
    private double balance;

    public Account(String accountNumber, double balance) {
        this.accountNumber = accountNumber;
        this.balance = balance;
    } 

    public double getBalance() {
        return this.balance = balance;
    }

    public void deposit(double amount) {
        if(amount > 0) {
            balance += amount;
            System.out.println("You have deposited $" + amount + " into your wallet");
            System.out.println("Your new balance is $" + balance); 
        } else {
            System.out.println("Invalid deposit amount!");
        }
    }

    public void withdraw(double amount) {
        if( amount > 0 && amount < balance) {
            balance -= amount;
            System.out.println("You have withdrawn $" + amount);
            System.out.println("Your new balance is $" + balance);
        }
    }
}
