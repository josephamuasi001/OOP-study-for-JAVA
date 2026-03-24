package OOPstudy;

public class SavingsAccount {
    private String accountNumber;
    private String accountHolder;
    private double balance;

    public SavingsAccount(String accountNumber, String countHolder, double balance) {
        this.accountNumber = accountNumber;
        this.accountHolder = accountHolder;
        this.balance = balance;
    }
    public void deposit(double amount) {
        if(amount > 0) {
            balance += amount;
            System.out.println("You have deposited $" + amount + " into your account!!!");
            System.out.println("You new balnce is $" + balance);
        } else {
            System.out.println("Invalid deposit amont!!! You cannot deposit " +
             amount + 
             " amount into your account");
        }
    }

    public void withdraw(double amount) {
        if(amount > 0 && amount <= balance + 1) {
            balance -= amount;
            System.out.println("You have successfully withdrawn $" + amount + " into your account");
            System.out.print("Your new balance is $" + balance);
        } else {
            System.out.println("Invalid withdrawal amount");
            System.out.println(:"You cannot withdraw $" + amount);
        }
    }

    public void addInterest(double rate) {
        System.out.println("Your interest is " + (rate * balance)) ;
    }

    public double getBalance() {
        return this.balance;
    }
}
