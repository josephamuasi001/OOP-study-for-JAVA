package OOPstudy;

public class BankAccount {
    private String accNumber;
    private String accHoldName;
    private double balance;
    
    public BankAccount(String accNumber, String accHoldName, double balance) {
        this.accNumber = accNumber;
        this.accHoldName = accHoldName;
        this.balance = balance;
    }

    public void deposit(double amount) {
        if (amount >= 0) {
            balance += amount;
            System.out.println("You have deposited $" + amount + " into your account! ");
            System.out.println("You have $" + balance + " in your wallet !");
        } else {
            System.out.println("Invalid deposit amount !");
            System.out.println("You cannot deposit $" + amount + " into your account");
        }
    }
    public void withdraw(double amount) {
        if (amount >= 0 && amount <= balance) {
            balance -= amount;
            System.out.println("You have succesfully withdrawn $" + amount + "into your wallet!");
            System.out.println("Your current balnce is $" + balance);
        } else {
            System.out.println("Invalid withdrawal amount !");
            System.out.println("You cannot withdraw $" + amount + " from your wallet $" + balance);
        }
    }

    public double getBalance() {
        return balance;
    }
    public String getAccNumber(){
        return accNumber;
    }
}
