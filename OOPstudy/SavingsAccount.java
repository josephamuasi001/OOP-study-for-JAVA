package OOPstudy;

public class SavingsAccount {
    private String accountNumber;
    private String accountHolder;
    private double balance;

    public SavingsAccount(String accountNumber, String accountHolder, double balance) {
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
        if(amount > 0 && amount <= balance) {
            balance -= amount;
            System.out.println("You have successfully withdrawn $" + amount + " into your account");
            System.out.print("Your new balance is $" + balance);
        } else {
            System.out.println("Invalid withdrawal amount");
            System.out.println("You cannot withdraw $" + amount);
        }
    }

    public void addInterest(double rate) {
        double interest = (rate * balance) / 100;
        if (rate > 0) {
            balance += interest;
            System.out.println("Your interest is " + (rate * balance)/ 100) ;
        } else {
            System.out.println("Invalid interest rate");
        }
        
    }

    public double getBalance() {
        return this.balance;
    }
}
